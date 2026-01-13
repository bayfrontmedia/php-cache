# [PHP-Cache](../README.md) > Interfaces > CacheItemPoolInterface

The `Bayfront\Cache\Interfaces\CacheItemPoolInterface` implements `\Psr\Cache\CacheItemPoolInterface`.

## Methods

- [getConfig](#getconfig)
- [getItem](#getitem)
- [getItems](#getitems)
- [hasItem](#hasitem)
- [clear](#clear)
- [deleteItem](#deleteitem)
- [deleteItems](#deleteitems)
- [save](#save)
- [saveDeferred](#savedeferred)
- [commit](#commit)
- [getItemsWithPrefix](#getitemswithprefix)
- [deleteItemsWithPrefix](#deleteitemswithprefix)
- [getItemsWithTag](#getitemswithtag)
- [deleteItemsWithTag](#deleteitemswithtag)
- [deleteTag](#deletetag)
- [lockItem](#lockitem)
- [unlockItem](#unlockitem)
- [forceUnlockItem](#forceunlockitem)
- [renewItemLock](#renewitemlock)
- [itemIsLocked](#itemislocked)

## getConfig

Get config key value, or default value if not existing.

**Parameters:**

- `$key` (string)
- `$default = null` (mixed)

**Returns:**

- (mixed)

## getItem

Returns a Cache Item representing the specified key.

**Parameters:**

- `$key` (string)

**Returns:**

- [CacheItemInterface](cacheiteminterface.md)

**Throws:**

- `Bayfront\Cache\Exceptions\InvalidArgumentException`

## getItems

Returns a traversable set of cache items.

**Parameters:**

- `$keys = []` (array)

**Returns:**

- (array): Array of [CacheItemInterface](cacheiteminterface.md)

**Throws:**

- `Bayfront\Cache\Exceptions\InvalidArgumentException`

## hasItem

Confirms if the cache contains specified cache item

**Parameters:**

- `$key` (string)

**Returns:**

- (bool)

**Throws:**

- `Bayfront\Cache\Exceptions\InvalidArgumentException`

## clear

Deletes all items in the pool

**Parameters:**

- None

**Returns:**

- (bool)

## deleteItem

Removes the item from the pool.

**Parameters:**

- `$key` (string)

**Returns:**

- (bool)

**Throws:**

- `Bayfront\Cache\Exceptions\InvalidArgumentException`

## deleteItems

Removes multiple items from the pool.

**Parameters:**

- `$keys` (array): Array of keys to remove

**Returns:**

- (bool)

**Throws:**

- `Bayfront\Cache\Exceptions\InvalidArgumentException`

## save

Persists a cache item immediately.

**Parameters:**

- `$item` (`CacheItemInterface`): A [CacheItemInterface](cacheiteminterface.md) instance

**Returns:**

- (bool)

## saveDeferred

Sets a cache item to be persisted later.

**Parameters:**

- `$item` (`CacheItemInterface`): A [CacheItemInterface](cacheiteminterface.md) instance

**Returns:**

- (bool)

## commit

Persists any deferred cache items.

**Parameters:**

- None

**Returns:**

- (bool)

## getItemsWithPrefix

Get all items whose key begins with prefix.

**Parameters:**

- `$prefix` (string)
- `$batch_size = 500` (int)

**Returns:**

- (array): Array of [CacheItemInterface](cacheiteminterface.md)

**Throws:**

- `Bayfront\Cache\Exceptions\InvalidArgumentException`

## deleteItemsWithPrefix

Delete all items whose key begins with prefix.

**Parameters:**

- `$prefix` (string)
- `$batch_size = 500` (int)

**Returns:**

- (bool)

**Throws:**

- `Bayfront\Cache\Exceptions\InvalidArgumentException`

## getItemsWithTag

Get all items with tag.

**Parameters:**

- `$tag` (string)

**Returns:**

- (array): Array of [CacheItemInterface](cacheiteminterface.md)

## deleteItemsWithTag

Delete all items with tag.

**Parameters:**

- `$tag` (string)

**Returns:**

- (bool)

## deleteTag

Delete tag and remove from item meta for all items with tag.

**Parameters:**

- `$tag` (string)

**Returns:**

- (int): Number of items which contained the deleted tag

## lockItem

Lock item.

Locking prevents the item from being updated.

**Parameters:**

- `$key` (string)
- `$token = null` (string|null): A UUID v7 token will be created if `null`
- `$ttl = null` (int|null): In seconds. Uses adapter's `lock_ttl` defined in the configuration array if `null`

**Returns:**

- (false|string): Returns token on success

## unlockItem

Unlock item.

**Parameters:**

- `$key` (string)
- `$token` (string)

**Returns:**

- (bool)

## forceUnlockItem

Force unlock an item.

NOTE: Use caution when using this method as it negates the entire purpose of locking an item.
This method exists as a last resort measure, if needed.

**Parameters:**

- `$key` (string)

**Returns:**

- (bool)

## renewItemLock

Renew item lock.

**Parameters:**

- `$key` (string)
- `$token` (string)
- `$ttl = null` (int|null): In seconds. Uses adapter's `lock_ttl` defined in the configuration array if `null`

**Returns:**

- (bool)

## itemIsLocked

Is item locked?

**Parameters:**

- `$key` (string)

**Returns:**

- (bool)