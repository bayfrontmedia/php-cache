<?php /** @noinspection PhpUnused */

namespace Bayfront\Cache\Adapters;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Cache\Exceptions\CacheException;
use Bayfront\Cache\Exceptions\InvalidArgumentException;
use Bayfront\Cache\Interfaces\CacheItemPoolInterface;
use Bayfront\Cache\Items\RedisCacheItem;
use Bayfront\Cache\Utilities\CacheUtilities;
use Bayfront\StringHelpers\Str;
use Psr\Cache\CacheItemInterface;
use Redis;
use RedisException;

class RedisAdapter extends CacheUtilities implements CacheItemPoolInterface
{

    private Redis $redis;
    private array $config;

    /**
     * @param Redis $redis
     * @param array $config
     * @throws CacheException
     */
    public function __construct(Redis $redis, array $config = [])
    {
        $this->redis = $redis;
        $this->config = Arr::only(array_merge([
            'prefix' => '', // Key prefix automatically applied to all items within the Redis database
            'tags' => [], // Unremovable tags automatically added to all items
            'compression' => self::COMPRESSION_METHOD_GZIP, // Compression method
            'compression_min_bytes' => 1024, // Minimum item size required before applying compression
            'serialization' => self::SERIALIZATION_METHOD_PHP, // Serialization method
            'lock_ttl' => 30 // Default lock TTL, in seconds
        ], $config), [
            'prefix',
            'tags',
            'compression',
            'compression_min_bytes',
            'serialization',
            'lock_ttl'
        ]);

        if (!$this::isValidCompressionMethod($this->getConfig('compression', ''))) {
            throw new CacheException('Unable to instantiate RedisAdapter: Invalid compression method (' . $this->getConfig('compression', ''));
        }

        if (!$this::isValidSerializationMethod($this->getConfig('serialization', ''))) {
            throw new CacheException('Unable to instantiate RedisAdapter: Invalid serialization method (' . $this->getConfig('serialization', ''));
        }

    }

    /**
     * Get prefixed item key.
     *
     * @param string $key
     * @return string
     */
    private function getPrefixedItemKey(string $key): string
    {
        return $this->getConfig('prefix', '') . 'item|' . $key;
    }

    /**
     * Get prefixed lock key.
     *
     * @param string $key
     * @return string
     */
    private function getPrefixedLockKey(string $key): string
    {
        return $this->getConfig('prefix', '') . 'lock|' . $key;
    }

    /**
     * Get prefixed meta key.
     *
     * @param string $key
     * @return string
     */
    private function getPrefixedMetaKey(string $key): string
    {
        return $this->getConfig('prefix', '') . 'meta|' . $key;
    }

    /**
     * Get prefixed tag key.
     *
     * @param string $key
     * @return string
     */
    private function getPrefixedTagKey(string $key): string
    {
        return $this->getConfig('prefix', '') . 'tag|' . $key;
    }

    /**
     * Get item key without prefix.
     *
     * @param string $key
     * @return string
     */
    private function getItemKeyWithoutPrefix(string $key): string
    {

        if (str_starts_with($key, $this->getConfig('prefix', '') . 'item|')) {
            return substr($key, strlen($this->getConfig('prefix', '') . 'item|'));
        }

        return $key;

    }

    /**
     * @param string $key (Non-prefixed key)
     * @param mixed $value
     * @param array|false $meta
     * @return CacheItemInterface
     */
    private function returnCacheItemFromRedisValue(string $key, mixed $value, array|false $meta): CacheItemInterface
    {

        if ($value === false) {
            return new RedisCacheItem($key, null, false);
        }

        $config = json_decode(Arr::get($meta, 'config', '{}'), true);

        $meta['config'] = $config;

        /*
         * If no value exists in Redis, default to "none" (do not process value)
         */

        if (Arr::get($config, 'compressed') === true) {
            $value = CacheUtilities::decompressValue($value, Arr::get($config, 'compression', CacheUtilities::COMPRESSION_METHOD_NONE));
        }

        $value = CacheUtilities::unserializeValue($value, Arr::get($config, 'serialization', CacheUtilities::SERIALIZATION_METHOD_NONE));

        return new RedisCacheItem($key, $value, true, $meta);

    }

    /**
     * @param CacheItemInterface $item
     * @return void
     */
    private function saveItem(CacheItemInterface $item): void
    {

        $time = time();

        $expiration = $item->getExpirationTimestamp();

        if (is_int($expiration) && $expiration > 0) {
            $expiration = max(0, $expiration - $time);
        }

        // Get meta

        $meta = [
            'tags' => array_unique(array_merge($this->getConfig('tags', []), $item->getTags())),
            'removed_tags' => $item->getRemovedTags(),
            'hits' => $item->getHits(),
            'last_updated' => $time,
            'expires_at' => $item->getExpirationTimestamp(),
            'config' => array_merge([
                'compressed' => false,
                'compression' => $this->getConfig('compression'),
                'serialization' => $this->getConfig('serialization')
            ], $item->getConfig()) // Saved value overwrites defaults
        ];


        if ($item->getCreatedAt() === null) {
            $meta['created_at'] = $time;
        } else {
            $meta['created_at'] = $item->getCreatedAt();
        }

        $item_meta = $item->getMeta();

        if (!empty($item_meta)) {
            $meta['meta'] = json_encode($item_meta);
        }

        // Get item

        $item_value = $item->get();

        // Serialization

        $serialization_method = Arr::get($meta, 'config.serialization');

        if ($serialization_method !== CacheUtilities::SERIALIZATION_METHOD_NONE) {
            $item_value = CacheUtilities::serializeValue($item_value, $serialization_method);
        }

        // Compression

        $compression_method = Arr::get($meta, 'config.compression');

        if ($compression_method !== CacheUtilities::COMPRESSION_METHOD_NONE && strlen($item_value) >= $this->getConfig('compression_min_bytes', 1024)) {
            $item_value = CacheUtilities::compressValue($item_value, $compression_method);
            $meta['config']['compressed'] = true;
        }

        // Create item array

        $meta['config'] = json_encode(Arr::get($meta, 'config', []));

        $tags = $meta['tags'];
        $meta['tags'] = json_encode($meta['tags']);

        $item_arr = [
            'key' => $this->getPrefixedItemKey($item->getKey()),
            'value' => $item_value,
            'expiration' => $expiration,
            'meta_key' => $this->getPrefixedMetaKey($item->getKey()),
            'meta_value' => Arr::except($meta, 'removed_tags'),
            'tags' => $tags,
            'removed_tags' => $meta['removed_tags']
        ];

        if (is_int($item_arr['expiration']) && $item_arr['expiration'] > 0) {
            $this->redis->hMSet($item_arr['meta_key'], $item_arr['meta_value']);
            $this->redis->expire($item_arr['meta_key'], $item_arr['expiration']);
        } else {
            $this->redis->hMSet($item_arr['meta_key'], $item_arr['meta_value']);
            $this->redis->persist($item_arr['meta_key']);
        }

        // Update tag index with non-prefixed key
        foreach ($item_arr['tags'] as $tag) {
            $this->redis->sAdd($this->getPrefixedTagKey($tag), $item->getKey());
        }

        foreach ($item_arr['removed_tags'] as $tag) {
            $this->redis->sRem($this->getPrefixedTagKey($tag), $item->getKey());
        }

        if (is_int($item_arr['expiration']) && $item_arr['expiration'] > 0) {
            $this->redis->set($item_arr['key'], $item_arr['value'], $item_arr['expiration']);
        } else {
            $this->redis->set($item_arr['key'], $item_arr['value']);
        }

    }

    /**
     * Create associative meta array from Lua result.
     *
     * @param array $meta
     * @return array
     */
    private function createAssociativeMetaArray(array $meta): array
    {

        $return = [];

        $count = count($meta);
        for ($i = 0; $i < $count; $i += 2) {
            $return[$meta[$i]] = $meta[$i + 1];
        }

        return $return;

    }

    /**
     * Iterate results in chunks of 3 and return an array of MetaCacheItems.
     *
     * @param array $results
     * @return array
     */
    private function getLuaItems(array $results): array
    {

        $items = [];

        foreach (array_chunk($results, 3) as $result_chunk) {

            // $result_chunk[0] = full item key name, $result_chunk[1] = item value, $result_chunk[2] = meta

            $items[] = $this->returnCacheItemFromRedisValue($this->getItemKeyWithoutPrefix($result_chunk[0]), $result_chunk[1], $this->createAssociativeMetaArray($result_chunk[2]));

        }

        return $items;

    }

    /*
     * |--------------------------------------------------------------------------
     * | Lua scripts
     * |--------------------------------------------------------------------------
     */

    /**
     * Get single item with meta and increment hit counter.
     *
     * @var string
     */
    private string $lua_get_item = <<<'LUA'
local item_key = KEYS[1]
local meta_key = KEYS[2]
local value = redis.call('GET', item_key)
local meta = {}
if value ~= false then
    redis.call('HINCRBY', meta_key, 'hits', 1)
    meta = redis.call('HGETALL', meta_key)
end
return {value, meta}
LUA;

    /**
     * Get multiple items with meta by key and increment hit counter.
     *
     * @var string
     */
    private string $lua_get_items = <<<'LUA'
local result = {}
for i = 1, #KEYS, 2 do
    local item_key = KEYS[i]
    local meta_key = KEYS[i + 1]
    local value = redis.call('GET', item_key)
    local meta = {}
    if value ~= false then
        redis.call('HINCRBY', meta_key, 'hits', 1)
        meta = redis.call('HGETALL', meta_key)
        table.insert(result, item_key)
        table.insert(result, value)
        table.insert(result, meta)
    end
end
return result
LUA;

    /**
     * Get multiple items with meta by tag and increment hit counter.
     *
     * Removes orphaned keys from the tag set.
     *
     * @var string
     */
    private string $lua_get_items_with_tag = <<<'LUA'
-- KEYS[1]: Prefixed tag key (e.g., tag:my-tag)
-- ARGV[1]: Item key prefix (e.g., item:)
-- ARGV[2]: Meta key prefix (e.g., meta:)
local tag_key = KEYS[1]
local item_prefix = ARGV[1]
local meta_prefix = ARGV[2]
local keys = redis.call('SMEMBERS', tag_key)
local result = {}
local orphaned = {}
for _, full_key in ipairs(keys) do
    -- Extract the base key (removing the prefix used in the SET if necessary)
    local item_key = item_prefix .. full_key
    local meta_key = meta_prefix .. full_key
    local value = redis.call('GET', item_key)
    if value == false then
        -- Mark for removal if the item doesn't exist
        table.insert(orphaned, full_key)
    else
        -- Increment hits and get metadata
        redis.call('HINCRBY', meta_key, 'hits', 1)
        local meta = redis.call('HGETALL', meta_key)
        -- Store the base key, value, and meta for the client
        table.insert(result, item_key)
        table.insert(result, value)
        table.insert(result, meta)
    end
end
-- Cleanup orphaned keys from the tag set
if #orphaned > 0 then
    for i = 1, #orphaned, 5000 do
        -- Slice the table in batches of 5000 to stay within the Lua stack limits
        local batch = {}
        for j = i, math.min(i + 4999, #orphaned) do
            table.insert(batch, orphaned[j])
        end
        redis.call('SREM', tag_key, unpack(batch))
    end
end
return result
LUA;

    /**
     * Delete tag and update all tagged item meta.
     *
     * @var string
     */
    private string $lua_delete_tag = <<<'LUA'
local tag_key = KEYS[1]
local meta_prefix = ARGV[1]
local tag_value = ARGV[2]
local members = redis.call('SMEMBERS', tag_key)
for i = 1, #members do
    local item_key = members[i]
    local meta_key = meta_prefix .. item_key
    local tags_json = redis.call('HGET', meta_key, 'tags')
    if tags_json then
        local ok, tags = pcall(cjson.decode, tags_json)
        if ok and type(tags) == 'table' then
            local new_tags = {}
            local found = false
            for _, t in ipairs(tags) do
                if t ~= tag_value then
                    table.insert(new_tags, t)
                else
                    found = true
                end
            end
            if found then
                if #new_tags > 0 then
                    redis.call('HSET', meta_key, 'tags', cjson.encode(new_tags))
                else
                    redis.call('HSET', meta_key, 'tags', '[]')
                end
            end
        end
    end
end
redis.call('DEL', tag_key)
return #members
LUA;

    /**
     * Release lock if token matches.
     * Returns a 0 or 1.
     *
     * @var string
     */
    private string $lua_release_lock = <<<'LUA'
if redis.call("get", KEYS[1]) == ARGV[1] then
    return redis.call("del", KEYS[1])
else
    return 0
end
LUA;

    /**
     * Renew lock TTL if token matches.
     * Returns a 0 or 1.
     *
     * @var string
     */
    private string $lua_renew_lock = <<<'LUA'
if redis.call("get", KEYS[1]) == ARGV[1] then
    return redis.call("expire", KEYS[1], ARGV[2])
else
    return 0
end
LUA;

    /*
     * |--------------------------------------------------------------------------
     * | Public functions
     * |--------------------------------------------------------------------------
     */

    /**
     * @inheritDoc
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function getItem(string $key): CacheItemInterface
    {

        $this->validateKey($key);

        $item_key = $this->getPrefixedItemKey($key);
        $meta_key = $this->getPrefixedMetaKey($key);

        $sha = sha1($this->lua_get_item);

        try {

            $results = $this->redis->evalsha($sha, [$item_key, $meta_key], 2);

        } catch (RedisException $e) {

            if (str_contains($e->getMessage(), 'NOSCRIPT')) { // Fallback
                $results = false; // Redis may return false instead of throwing an error
            } else {
                throw $e;
            }

        }

        if ($results === false) {
            $results = $this->redis->eval($this->lua_get_item, [$item_key, $meta_key], 2);
        }

        // Flatten meta

        $meta = $this->createAssociativeMetaArray($results[1]);

        return $this->returnCacheItemFromRedisValue($key, $results[0], $meta);

    }

    /**
     * @inheritDoc
     * @return CacheItemInterface[]
     * @throws InvalidArgumentException
     */
    public function getItems(array $keys = []): iterable
    {

        if (empty($keys)) {
            return [];
        }

        $lua_keys = [];

        foreach ($keys as $key) {
            $this->validateKey($key);
            $lua_keys[] = $this->getPrefixedItemKey($key);
            $lua_keys[] = $this->getPrefixedMetaKey($key);
        }

        $sha = sha1($this->lua_get_items);

        try {

            $results = $this->redis->evalsha($sha, $lua_keys, count($lua_keys));

        } catch (RedisException $e) {

            if (str_contains($e->getMessage(), 'NOSCRIPT')) { // Fallback
                $results = false; // Redis may return false instead of throwing an error
            } else {
                throw $e;
            }

        }

        if ($results === false) {
            $results = $this->redis->eval($this->lua_get_items, $lua_keys, count($lua_keys));
        }

        return $this->getLuaItems($results);

    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function hasItem(string $key): bool
    {
        $this->validateKey($key);
        return (bool)$this->redis->exists($this->getPrefixedItemKey($key));
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {

        $this->delete_all = true;

        try {
            return $this->deleteItemsWithPrefix('');
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function deleteItem(string $key): bool
    {

        /*
         * NOTE:
         * Tag set is not updated on delete since that would require two round trips to Redis.
         * Tag sets are not in-sync anyway since items can expire and self-delete.
         *
         * Orphaned items are removed from tag sets with getItemsWithTag()
         */

        $this->validateKey($key);

        return $this->redis->unlink([$this->getPrefixedItemKey($key), $this->getPrefixedMetaKey($key)]) > 0;

    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function deleteItems(array $keys): bool
    {

        if (empty($keys)) {
            return true;
        }

        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        $delete_keys = [];

        foreach ($keys as $key) {
            $delete_keys[] = $this->getPrefixedItemKey($key);
            $delete_keys[] = $this->getPrefixedMetaKey($key);
        }

        return $this->redis->unlink($delete_keys) > 0;

    }

    /**
     * @inheritDoc
     *
     * TODO:
     * Optimize
     */
    public function save(CacheItemInterface $item): bool
    {

        if ($this->itemIsLocked($item->getKey())) {
            return false;
        }

        $this->redis->multi(Redis::PIPELINE);
        $this->saveItem($item);
        $results = $this->redis->exec();

        return is_array($results) && !empty($results);

    }

    private array $deferred = [];

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {

        $this->deferred[] = $item;
        return true;

    }

    /**
     * @inheritDoc
     *
     * TODO:
     * Optimize
     */
    public function commit(): bool
    {

        if (empty($this->deferred)) {
            return true;
        }

        /*
         * itemIsLocked cannot run inside pipe
         */

        foreach ($this->deferred as $k => $item) {
            if ($this->itemIsLocked($item->getKey())) {
                unset($this->deferred[$k]);
            }
        }

        $this->redis->multi(Redis::PIPELINE);

        /** @var CacheItemInterface $item */
        foreach ($this->deferred as $item) {
            $this->saveItem($item);
        }

        $results = $this->redis->exec();

        return is_array($results) && !empty($results);

    }

    /**
     * @inheritDoc
     */
    public function getItemsWithPrefix(string $prefix, int $batch_size = 500): array
    {

        $this->validateKey($prefix);

        $iterator = null;
        $items = [];

        do {

            $keys = $this->redis->scan($iterator, $this->getPrefixedItemKey($prefix) . '*');

            if (is_array($keys) && count($keys) > 0) {

                foreach (array_chunk($keys, $batch_size) as $chunk) {

                    if (!empty($chunk)) {

                        $lua_keys = [];

                        foreach ($chunk as $key) {
                            $lua_keys[] = $key;
                            $lua_keys[] = $this->getPrefixedMetaKey($this->getItemKeyWithoutPrefix($key));
                        }

                        $sha = sha1($this->lua_get_items);

                        try {

                            $results = $this->redis->evalSha($sha, $lua_keys, count($lua_keys));

                        } catch (RedisException $e) {

                            if (str_contains($e->getMessage(), 'NOSCRIPT')) { // Fallback
                                $results = false; // Redis may return false instead of throwing an error
                            } else {
                                throw $e;
                            }

                        }

                        if ($results === false) {
                            $results = $this->redis->eval($this->lua_get_items, $lua_keys, count($lua_keys));
                        }

                        $items = array_merge($items, $this->getLuaItems($results));

                    }

                }

            }

        } while ($iterator !== 0);

        return $items;

    }

    private bool $delete_all = false;

    /**
     * @inheritDoc
     */
    public function deleteItemsWithPrefix(string $prefix, int $batch_size = 500): bool
    {

        if ($this->delete_all === false) {
            $this->validateKey($prefix);
            $pattern = $this->getPrefixedItemKey($prefix) . '*';
        } else {
            $this->delete_all = false; // Reset
            $pattern = $this->getConfig('prefix', '') . '*';
        }

        $iterator = null;
        $items = [];

        do {

            $keys = $this->redis->scan($iterator, $pattern);

            if (is_array($keys) && count($keys) > 0) {

                foreach (array_chunk($keys, $batch_size) as $chunk) {

                    if (!empty($chunk)) {

                        foreach ($chunk as $key) {
                            $items[] = $key;
                            $items[] = $this->getPrefixedMetaKey($this->getItemKeyWithoutPrefix($key));

                        }

                    }

                }

            }

        } while ($iterator !== 0);

        if (!empty($items)) {
            return $this->redis->unlink($items) > 0;
        }

        return true;

    }

    /**
     * @inheritDoc
     */
    public function getItemsWithTag(string $tag): array
    {

        $tag_key = $this->getPrefixedTagKey($tag);

        $item_prefix = $this->getPrefixedItemKey('');
        $meta_prefix = $this->getPrefixedMetaKey('');

        $sha = sha1($this->lua_get_items_with_tag);

        try {

            $results = $this->redis->evalsha($sha, [$tag_key, $item_prefix, $meta_prefix], 1);

        } catch (RedisException $e) {

            if (str_contains($e->getMessage(), 'NOSCRIPT')) { // Fallback
                $results = false; // Redis may return false instead of throwing an error
            } else {
                throw $e;
            }

        }

        if ($results === false) {
            $results = $this->redis->eval($this->lua_get_items_with_tag, [$tag_key, $item_prefix, $meta_prefix], 1);
        }

        return $this->getLuaItems($results);

    }

    /**
     * @inheritDoc
     */
    public function deleteItemsWithTag(string $tag): bool
    {

        $items = $this->redis->sMembers($this->getPrefixedTagKey($tag));

        $delete_keys = [];

        foreach ($items as $key) {
            $delete_keys[] = $this->getPrefixedItemKey($key);
            $delete_keys[] = $this->getPrefixedMetaKey($key);
        }

        $delete_keys[] = $this->getPrefixedTagKey($tag);

        return $this->redis->unlink($delete_keys) > 0;

    }

    /**
     * @inheritDoc
     */
    public function deleteTag(string $tag): int
    {

        $sha = sha1($this->lua_delete_tag);

        try {

            $results = $this->redis->evalsha($sha,
                [
                    $this->getPrefixedTagKey($tag),
                    $this->getPrefixedMetaKey(''),
                    $tag
                ],
                1);

        } catch (RedisException $e) {

            if (str_contains($e->getMessage(), 'NOSCRIPT')) { // Fallback
                $results = false; // Redis may return false instead of throwing an error
            } else {
                throw $e;
            }

        }

        if ($results === false) {
            return $this->redis->eval(
                $this->lua_delete_tag,
                [
                    $this->getPrefixedTagKey($tag),
                    $this->getPrefixedMetaKey(''),
                    $tag
                ],
                1);
        }

        return $results;

    }

    /**
     * @inheritDoc
     */
    public function lockItem(string $key, ?string $token = null, ?int $ttl = null): false|string
    {

        if ($token === null) {
            $token = Str::uuid7();
        }

        if ($ttl === null) {
            $ttl = $this->getConfig('lock_ttl', 30);
        }

        if ($ttl <= 0) {
            return false;
        }

        $result = $this->redis->set($this->getPrefixedLockKey($key), $token, [
            'nx',
            'ex' => $ttl
        ]);

        if ($result === true) {
            return $token;
        }

        return false;

    }

    /**
     * @inheritDoc
     */
    public function unlockItem(string $key, string $token): bool
    {
        return (bool)$this->redis->eval($this->lua_release_lock, [$this->getPrefixedLockKey($key), $token], 1);
    }

    /**
     * @inheritDoc
     */
    public function forceUnlockItem(string $key): bool
    {
        return $this->redis->del($this->getPrefixedLockKey($key)) > 0;
    }

    /**
     * @inheritDoc
     */
    public function renewItemLock(string $key, string $token, ?int $ttl = null): bool
    {

        if ($ttl === null) {
            $ttl = $this->getConfig('lock_ttl', 30);
        }

        if ($ttl <= 0) {
            return false;
        }

        return (bool)$this->redis->eval($this->lua_renew_lock, [$this->getPrefixedLockKey($key), $token, $ttl], 1);

    }

    /**
     * @inheritDoc
     */
    public function itemIsLocked(string $key): bool
    {
        return (bool)$this->redis->exists($this->getPrefixedLockKey($key));
    }

}