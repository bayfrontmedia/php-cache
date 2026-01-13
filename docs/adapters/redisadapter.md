# [PHP-Cache](../README.md) > Adapters > RedisAdapter

- Storage location: Redis
- Description: PSR-6 Redis pool

Due to the advanced features of the `RedisAdapter`, additional Redis keys are created for pool items
used to store metadata and tag sets.

Example:

```php
try {
    $redis->pconnect('10.0.0.1', 6379, 2, 'cache');
} catch (RedisException $e) {
    $redis->connect('10.0.0.1', 6379, 2);
}

$redis->auth([
    'USERNAME',
    'PASSWORD'
]);

$config = [
    'prefix' => '', // Key prefix automatically applied to all items within the Redis database
    'tags' => [], // Unremovable tags automatically added to all items
    'compression' => CacheUtilities::COMPRESSION_METHOD_GZIP, // Compression method
    'compression_min_bytes' => 1024, // Minimum item size required before applying compression
    'serialization' => CacheUtilities::SERIALIZATION_METHOD_PHP, // Serialization method
    'lock_ttl' => 30 // Default lock TTL, in seconds
];

$pool = new RedisAdapter($redis, $config);
```

The `$config` array shown above represents the default values which will automatically be set if not defined.
[CacheUtilities constants](../README.md#constants) should be used for the `compression` and `serialization` values.

The `RedisAdapter` constructor will throw a `Bayfront\Cache\Exceptions\CacheException`
if there is an error with the configuration array.

## Methods

For a list of methods included with the `RedisAdapter`, see [CacheItemPoolInterface](../interfaces/cacheitempoolinterface.md).