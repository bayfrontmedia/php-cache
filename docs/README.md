# PHP-Cache

A PSR-6 cache implementation which supports tags, metadata, hit counters, and more.

For more information, see [PSR-6 specifications](https://www.php-fig.org/psr/psr-6/).

## Exceptions

All exceptions thrown by this library extend `Bayfront\Cache\Exceptions\CacheException`,
so you can choose to catch exceptions as narrowly or broadly as you like.

## Adapters

All adapters implement `Bayfront\Cache\Interfaces\CacheItemPoolInterface`.
As such, the adapter serves as the cache pool, which represents a collection of items.

For more information, see [CacheItemPoolInterface](interfaces/cacheitempoolinterface.md).

Available adapters include:

- [RedisAdapter](adapters/redisadapter.md)

### Items

All adapters return items as a `Bayfront\Cache\Interfaces\CacheItemInterface`.

For more information, see [CacheItemInterface](interfaces/cacheiteminterface.md).

## Utilities

The `Bayfront\Cache\Utilities\CacheUtilities` class contains some static constants and methods used by this library.

### Constants

- `COMPRESSION_METHOD_BZIP2`: Compression using bzip2 (PHP's `bzcompress` function)
- `COMPRESSION_METHOD_GZIP`: Compression using gzip (PHP's `gzencode` function)
- `COMPRESSION_METHOD_ZLIB`: Compression using the `ZLIB` data format (PHP's `gzcompress` function)
- `COMPRESSION_METHOD_NONE`: No compression
- `SERIALIZATION_METHOD_IGBINARY`: Serialization using compact binary format (PHP's `igbinary_serialize` function)
- `SERIALIZATION_METHOD_PHP`: Serialization using standard PHP serialization (PHP's `serialize` function)
- `SERIALIZATION_METHOD_NONE`: No serialization

### Static methods

#### isValidCompressionMethod

Is method a valid compression method?

**Parameters:**

- `$method` (string)

**Returns:**

- (bool)

<hr />

#### isValidSerializationMethod

Is method a valid serialization method?

**Parameters:**

- `$method` (string)

**Returns:**

- (bool)