<?php

namespace Bayfront\Cache\Interfaces;

use Bayfront\Cache\Exceptions\InvalidArgumentException;

interface CacheItemPoolInterface extends \Psr\Cache\CacheItemPoolInterface
{

    /**
     * Get config key value, or default value if not existing.
     *
     * @param string $key (Key in dot notation)
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $key, mixed $default = null): mixed;

    /**
     * Get all items whose key begins with prefix.
     *
     * @param string $prefix
     * @param int $batch_size
     * @return CacheItemInterface[]
     * @throws InvalidArgumentException
     */
    public function getItemsWithPrefix(string $prefix, int $batch_size = 500): array;

    /**
     * Delete all items whose key begins with prefix.
     *
     * @param string $prefix
     * @param int $batch_size
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteItemsWithPrefix(string $prefix, int $batch_size = 500): bool;

    /**
     * Get all items with tag.
     *
     * @param string $tag
     * @return CacheItemInterface[]
     */
    public function getItemsWithTag(string $tag): array;

    /**
     * Delete all items with tag.
     *
     * @param string $tag
     * @return bool
     */
    public function deleteItemsWithTag(string $tag): bool;

    /**
     * Delete tag and remove from item meta for all items with tag.
     *
     * @param string $tag
     * @return int
     */
    public function deleteTag(string $tag): int;

    /**
     * Lock item.
     *
     * Locking prevents the item from being updated.
     *
     * @param string $key
     * @param string|null $token (A UUID v7 token will be created if null)
     * @param int|null $ttl (In seconds. Uses adapter's lock_ttl defined in the configuration array if null)
     * @return false|string (Returns token on success)
     */
    public function lockItem(string $key, ?string $token = null, ?int $ttl = null): false|string;

    /**
     * Unlock item.
     *
     * @param string $key
     * @param string $token
     * @return bool
     */
    public function unlockItem(string $key, string $token): bool;

    /**
     * Force unlock an item.
     *
     * NOTE: Use caution when using this method as it negates the entire purpose of locking an item.
     * This method exists as a last resort measure, if needed.
     *
     * @param string $key
     * @return bool
     */
    public function forceUnlockItem(string $key): bool;

    /**
     * Renew item lock.
     *
     * @param string $key
     * @param string $token
     * @param int|null $ttl (In seconds. Uses adapter's lock_ttl defined in the configuration array if null)
     * @return bool
     */
    public function renewItemLock(string $key, string $token, ?int $ttl = null): bool;

    /**
     * Is item locked?
     *
     * @param string $key
     * @return bool
     */
    public function itemIsLocked(string $key): bool;

}