<?php

namespace Bayfront\Cache\Interfaces;

interface CacheItemInterface extends \Psr\Cache\CacheItemInterface
{

    /**
     * Does item have expiration?
     *
     * @return bool
     */
    public function hasExpiration(): bool;

    /**
     * Get expiration timestamp, or null if no expiration exists.
     *
     * @return int|null
     */
    public function getExpirationTimestamp(): ?int;

    /**
     * Get time until expiration (in seconds), or null if no expiration exists.
     *
     * @return int|null
     */
    public function getTimeUntilExpiration(): ?int;

    /**
     * Set the item's tags, overwriting any which may already exist.
     *
     * @param array $tags
     * @return $this
     */
    public function setTags(array $tags): static;

    /**
     * Add tags to item.
     *
     * @param array $tags
     * @return $this
     */
    public function addTags(array $tags): static;

    /**
     * Remove tags from item.
     *
     * @param array $tags
     * @return $this
     */
    public function removeTags(array $tags): static;

    /**
     * Get tags which have been removed from the item since it was retrieved.
     *
     * @return array
     */
    public function getRemovedTags(): array;

    /**
     * Get item tags.
     *
     * @return array
     */
    public function getTags(): array;

    /**
     * Does item have tag?
     *
     * @param string $tag
     * @return bool
     */
    public function hasTag(string $tag): bool;

    /**
     * Does item have any tags?
     *
     * @param array $tags
     * @return bool
     */
    public function hasAnyTags(array $tags): bool;

    /**
     * Does item have all tags?
     *
     * @param array $tags
     * @return bool
     */
    public function hasAllTags(array $tags): bool;

    /**
     * Set the item's metadata, overwriting any which may already exist.
     *
     * @param array $meta (Key/value pairs)
     * @return $this
     */
    public function setMeta(array $meta): static;

    /**
     * Add metadata to item.
     *
     * @param array $meta (Key/value pairs)
     * @return $this
     */
    public function addMeta(array $meta): static;

    /**
     * Remove metadata from item.
     *
     * @param array $keys
     * @return $this
     */
    public function removeMeta(array $keys): static;

    /**
     * Get metadata keys which have been removed from the item since it was retrieved.
     *
     * @return array
     */
    public function getRemovedMetaKeys(): array;

    /**
     * Get entire item metadata array.
     *
     * @return array
     */
    public function getMeta(): array;

    /**
     * Get metadata value for key.
     *
     * @param string $key (Key in dot notation)
     * @param mixed $default (Default value to return if not existing)
     * @return mixed
     */
    public function getMetaValue(string $key, mixed $default = null): mixed;

    /**
     * Does item have metadata key?
     *
     * @param string $key
     * @return bool
     */
    public function hasMeta(string $key): bool;

    /**
     * Get item hits, or 0 if not yet saved.
     * A "hit" represents the number of times the item has been retrieved from the pool.
     *
     * @return int
     */
    public function getHits(): int;

    /**
     * Get item created at timestamp, or null if not yet saved.
     *
     * @return int|null
     */
    public function getCreatedAt(): ?int;

    /**
     * Get item last updated timestamp, or null if not yet saved.
     *
     * @return int|null
     */
    public function getLastUpdated(): ?int;

    /**
     * Get item configuration.
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Set compression method for item, if valid.
     * This will override the default compression method used by the adapter.
     *
     * @param string $method
     * @return $this
     */
    public function setCompressionMethod(string $method): static;

    /**
     * Get compression method for item, or null if not set.
     *
     * @return string|null
     */
    public function getCompressionMethod(): ?string;

    /**
     * Set serialization method for item, if valid.
     * This will override the default serialization method used by the adapter.
     *
     * @param string $method
     * @return $this
     */
    public function setSerializationMethod(string $method): static;

    /**
     * Get serialization method for item, or null if not set.
     *
     * @return string|null
     */
    public function getSerializationMethod(): ?string;

}