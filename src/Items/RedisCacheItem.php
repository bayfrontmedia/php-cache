<?php /** @noinspection PhpUnused */

namespace Bayfront\Cache\Items;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Cache\Interfaces\CacheItemInterface;
use Bayfront\Cache\Utilities\CacheUtilities;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

class RedisCacheItem implements CacheItemInterface
{

    private string $key; // Non-prefixed key
    private mixed $value;
    private bool $is_hit;
    private ?int $expiration;
    private array $tags;
    private array $meta;
    private int $hits;
    private ?int $created_at;
    private ?int $last_updated;
    private array $config;

    public function __construct(string $key, mixed $value = null, bool $is_hit = false, false|array $meta = false)
    {
        $this->key = $key;
        $this->value = $value;
        $this->is_hit = $is_hit;

        if (is_array($meta)) {

            $expiration = Arr::get($meta, 'expires_at');

            if ($expiration === '') { // Redis hash fields with null values return as empty string
                $expiration = null;
            }

            $this->expiration = $expiration;
            $this->tags = json_decode(Arr::get($meta, 'tags', '{}'), true);
            $this->meta = json_decode(Arr::get($meta, 'meta', '{}'), true);
            $this->hits = Arr::get($meta, 'hits', 0);
            $this->created_at = Arr::get($meta, 'created_at');
            $this->last_updated = Arr::get($meta, 'last_updated');
            $this->config = Arr::get($meta, 'config', []);
            return;

        }

        $this->expiration = null;
        $this->tags = [];
        $this->meta = [];
        $this->hits = 0;
        $this->created_at = null;
        $this->last_updated = null;
        $this->config = [];

    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function isHit(): bool
    {
        return $this->is_hit;
    }

    /**
     * @inheritDoc
     */
    public function set(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {

        if ($expiration instanceof DateTimeInterface) {
            $this->expiration = $expiration->getTimestamp();
        } else {
            $this->expiration = null;
        }

        return $this;

    }

    /**
     * @inheritDoc
     */
    public function expiresAfter(DateInterval|int|null $time): static
    {

        if ($time instanceof DateInterval) {

            $base_date = new DateTimeImmutable();
            $exp_date = $base_date->add($time);
            $this->expiration = $exp_date->getTimestamp();

        } else if (is_int($time)) {
            $this->expiration = time() + $time;
        } else {
            $this->expiration = null;
        }

        return $this;

    }

    /**
     * @inheritDoc
     */
    public function hasExpiration(): bool
    {
        return is_int($this->expiration);
    }

    /**
     * @inheritDoc
     */
    public function getExpirationTimestamp(): ?int
    {
        return $this->expiration;
    }

    /**
     * @inheritDoc
     */
    public function getTimeUntilExpiration(): ?int
    {

        if (!is_int($this->expiration)) {
            return null;
        }

        return max(0, $this->expiration - time());

    }

    private array $removed_tags = [];

    /**
     * @inheritDoc
     */
    public function setTags(array $tags): static
    {
        $this->removed_tags = array_unique(array_merge($this->removed_tags, array_diff($this->tags, $tags)));
        $this->tags = $tags;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addTags(array $tags): static
    {

        foreach ($tags as $tag) {
            if (!in_array($tag, $this->tags)) {
                $this->tags[] = $tag;
            }
        }

        return $this;

    }

    /**
     * @inheritDoc
     */
    public function removeTags(array $tags): static
    {
        $this->removed_tags = array_unique(array_merge($this->removed_tags, $tags));
        $this->tags = Arr::exceptValues($this->tags, $tags);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRemovedTags(): array
    {
        return $this->removed_tags;
    }

    /**
     * @inheritDoc
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @inheritDoc
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags);
    }

    /**
     * @inheritDoc
     */
    public function hasAnyTags(array $tags): bool
    {
        return Arr::hasAnyValues($this->tags, $tags);
    }

    /**
     * @inheritDoc
     */
    public function hasAllTags(array $tags): bool
    {
        return Arr::hasAllValues($this->tags, $tags);
    }

    private array $removed_meta = [];

    /**
     * @inheritDoc
     */
    public function setMeta(array $meta): static
    {
        $this->removed_meta = array_unique(array_merge($this->removed_meta, array_keys(array_diff($this->meta, $meta))));
        $this->meta = $meta;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;

    }

    /**
     * @inheritDoc
     */
    public function removeMeta(array $keys): static
    {
        $this->removed_meta = array_unique(array_merge($this->removed_meta, $keys));
        $this->meta = Arr::except($this->meta, $keys);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRemovedMetaKeys(): array
    {
        return $this->removed_meta;
    }

    /**
     * @inheritDoc
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @inheritDoc
     */
    public function getMetaValue(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->meta, $key, $default);
    }

    /**
     * @inheritDoc
     */
    public function hasMeta(string $key): bool
    {
        return array_key_exists($key, $this->meta);
    }

    /**
     * @inheritDoc
     */
    public function getHits(): int
    {
        return $this->hits;
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?int
    {
        return $this->created_at;
    }

    /**
     * @inheritDoc
     */
    public function getLastUpdated(): ?int
    {
        return $this->last_updated;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function setCompressionMethod(string $method): static
    {

        if (CacheUtilities::isValidCompressionMethod($method)) {
            $this->config['compression'] = $method;
        }

        return $this;

    }

    /**
     * @inheritDoc
     */
    public function getCompressionMethod(): ?string
    {
        return Arr::get($this->config, 'compression');
    }

    /**
     * @inheritDoc
     */
    public function setSerializationMethod(string $method): static
    {

        if (CacheUtilities::isValidSerializationMethod($method)) {
            $this->config['serialization'] = $method;
        }

        return $this;

    }

    /**
     * @inheritDoc
     */
    public function getSerializationMethod(): ?string
    {
        return Arr::get($this->config, 'serialization');
    }

}