# [PHP-Cache](../README.md) > Interfaces > CacheItemInterface

The `Bayfront\Cache\Interfaces\CacheItemInterface` implements `\Psr\Cache\CacheItemPoolInterface`.

## Methods

- [getKey](#getkey)
- [get](#get)
- [isHit](#ishit)
- [set](#set)
- [expiresAt](#expiresat)
- [expiresAfter](#expiresafter)
- [hasExpiration](#hasexpiration)
- [getExpirationTimestamp](#getexpirationtimestamp)
- [getTimeUntilExpiration](#gettimeuntilexpiration)
- [setTags](#settags)
- [addTags](#addtags)
- [removeTags](#removetags)
- [getRemovedTags](#getremovedtags)
- [getTags](#gettags)
- [hasTag](#hastag)
- [hasAnyTags](#hasanytags)
- [hasAllTags](#hasalltags)
- [setMeta](#setmeta)
- [addMeta](#addmeta)
- [removeMeta](#removemeta)
- [getRemovedMetaKeys](#getremovedmetakeys)
- [getMeta](#getmeta)
- [getMetaValue](#getmetavalue)
- [hasMeta](#hasmeta)
- [getHits](#gethits)
- [getCreatedAt](#getcreatedat)
- [getLastUpdated](#getlastupdated)
- [getConfig](#getconfig)
- [setCompressionMethod](#setcompressionmethod)
- [getCompressionMethod](#getcompressionmethod)
- [setSerializationMethod](#setserializationmethod)
- [getSerializationMethod](#getserializationmethod)

## getKey

Returns the key for the current cache item.

**Parameters:**

- None

**Returns:**

- (string)

## get

Retrieves the value of the item from the cache associated with this object's key.

**Parameters:**

- None

**Returns:**

- (mixed)

## isHit

Confirms if the cache item lookup resulted in a cache hit.

**Parameters:**

- None

**Returns:**

- (bool)

## set

Sets the value represented by this cache item.

**Parameters:**

- `$value` (mixed)

**Returns:**

- (self)

## expiresAt

Sets the expiration time for this cache item.
Passing `null` will remove any expiration.

**Parameters:**

- `$expiration` (`?DateTimeInterface`)

**Returns:**

- (self)

## expiresAfter

Sets the expiration time for this cache item.
Passing `null` will remove any expiration.

**Parameters:**

- `$time` (`DateInterval|int|null`): Integer as time in seconds

**Returns:**

- (self)

## hasExpiration

Does item have expiration?

**Parameters:**

- None

**Returns:**

- (bool)

## getExpirationTimestamp

Get expiration timestamp, or `null` if no expiration exists.

**Parameters:**

- None

**Returns:**

- (int|null)

## getTimeUntilExpiration

Get time until expiration (in seconds), or `null` if no expiration exists.

**Parameters:**

- None

**Returns:**

- (int|null)

## setTags

Set the item's tags, overwriting any which may already exist.

**Parameters:**

- `$tags` (array)

**Returns:**

- (self)

## addTags

Add tags to item.

**Parameters:**

- `$tags` (array)

**Returns:**

- (self)

## removeTags

Remove tags from item.

**Parameters:**

- `$tags` (array)

**Returns:**

- (self)

## getRemovedTags

Get tags which have been removed from the item since it was retrieved.

**Parameters:**

- None

**Returns:**

- (array)

## getTags

Get item tags.

**Parameters:**

- None

**Returns:**

- (array)

## hasTag

Does item have tag?

**Parameters:**

- `$tag` (string)

**Returns:**

- (bool)

## hasAnyTags

Does item have any tags?

**Parameters:**

- `$tags` (array)

**Returns:**

- (bool)

## hasAllTags

Does item have all tags?

**Parameters:**

- `$tags` (array)

**Returns:**

- (bool)

## setMeta

Set the item's metadata, overwriting any which may already exist.

**Parameters:**

- `$meta` (array): Key/value pairs

**Returns:**

- (self)

## addMeta

Add metadata to item.

**Parameters:**

- `$meta` (array): Key/value pairs

**Returns:**

- (self)

## removeMeta

Remove metadata from item.

**Parameters:**

- `$keys` (array)

**Returns:**

- (self)

## getRemovedMetaKeys

Get metadata keys which have been removed from the item since it was retrieved.

**Parameters:**

- None

**Returns:**

- (array)

## getMeta

Get entire item metadata array.

**Parameters:**

- None

**Returns:**

- (array)

## getMetaValue

**Description:**

Get metadata value for key.

**Parameters:**

- `$key` (string): Key in dot notation
- `$default = null` (mixed): Default value to return if not existing

**Returns:**

- (mixed)

## hasMeta

Does item have metadata key?

**Parameters:**

- `$key` (string)

**Returns:**

- (bool)

## getHits

Get item hits, or `0` if not yet saved.
A "hit" represents the number of times the item has been retrieved from the pool.

**Parameters:**

- None

**Returns:**

- (int)

## getCreatedAt

Get item created at timestamp, or `null` if not yet saved.

**Parameters:**

- None

**Returns:**

- (int|null)

## getLastUpdated

Get item last updated timestamp, or `null` if not yet saved.

**Parameters:**

- None

**Returns:**

- (int|null)

## getConfig

Get item configuration.

**Parameters:**

- None

**Returns:**

- (array)

## setCompressionMethod

Set [compression method](../README.md#constants) for item, if valid.
This will override the default compression method used by the [adapter](../README.md#adapters).

**Parameters:**

- `$method` (string)

**Returns:**

- (self)

## getCompressionMethod

Get compression method for item, or `null` if not set.

**Parameters:**

- None

**Returns:**

- (string|null)

## setSerializationMethod

Set [serialization method](../README.md#constants) for item, if valid.
This will override the default serialization method used by the [adapter](../README.md#adapters).

**Parameters:**

- `$method` (string)

**Returns:**

- (self)

## getSerializationMethod

Get serialization method for item, or null if not set.

**Parameters:**

- None

**Returns:**

- (string|null)