<?php

namespace Bayfront\Cache\Utilities;

use Bayfront\Cache\Exceptions\InvalidArgumentException;

abstract class CacheUtilities
{

    /**
     * Ensure key adheres to PSR-6.
     *
     * @param string $key
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateKey(string $key): void
    {
        if ($key === '' || preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new InvalidArgumentException('Invalid key (' . $key . ')');
        }
    }

    public const COMPRESSION_METHOD_BZIP2 = 'bzip2';
    public const COMPRESSION_METHOD_GZIP = 'gzip';
    public const COMPRESSION_METHOD_ZLIB = 'zlib';
    public const COMPRESSION_METHOD_NONE = 'none';
    public const SERIALIZATION_METHOD_IGBINARY = 'ig';
    public const SERIALIZATION_METHOD_PHP = 'php';
    public const SERIALIZATION_METHOD_NONE = 'none';

    /**
     * Is method a valid compression method?
     *
     * @param string $method
     * @return bool
     */
    public static function isValidCompressionMethod(string $method): bool
    {
        return in_array($method, [
            self::COMPRESSION_METHOD_BZIP2,
            self::COMPRESSION_METHOD_GZIP,
            self::COMPRESSION_METHOD_ZLIB,
            self::COMPRESSION_METHOD_NONE
        ]);
    }

    /**
     * Get compressed value.
     *
     * @param mixed $value
     * @param string $method
     * @return string
     */
    protected function compressValue(mixed $value, string $method): string
    {

        if ($method === self::COMPRESSION_METHOD_BZIP2 && function_exists('bzcompress')) {

            $result = bzcompress($value);

            if (is_string($result)) {
                return $result;
            }

        } else if ($method === self::COMPRESSION_METHOD_GZIP && function_exists('gzencode')) {

            $result = gzencode($value);

            if (is_string($result)) {
                return $result;
            }

        } else if ($method === self::COMPRESSION_METHOD_ZLIB && function_exists('gzcompress')) {

            $result = gzcompress($value);

            if (is_string($result)) {
                return $result;
            }

        }

        return $value;

    }

    /**
     * Get decompressed value.
     *
     * @param mixed $value
     * @param string $method
     * @return string
     */
    protected function decompressValue(mixed $value, string $method): string
    {

        if ($method === self::COMPRESSION_METHOD_BZIP2 && function_exists('bzdecompress')) {

            $result = bzdecompress($value);

            if (is_string($result)) {
                return $result;
            }

        } else if ($method === self::COMPRESSION_METHOD_GZIP && function_exists('gzdecode')) {

            $result = gzdecode($value);

            if (is_string($result)) {
                return $result;
            }

        } else if ($method === self::COMPRESSION_METHOD_ZLIB && function_exists('gzuncompress')) {

            $result = gzuncompress($value);

            if (is_string($result)) {
                return $result;
            }

        }

        return $value;

    }

    /**
     * Is method a valid serialization method?
     *
     * @param string $method
     * @return bool
     */
    public static function isValidSerializationMethod(string $method): bool
    {
        return in_array($method, [
            self::SERIALIZATION_METHOD_IGBINARY,
            self::SERIALIZATION_METHOD_PHP,
            self::SERIALIZATION_METHOD_NONE
        ]);
    }

    /**
     * Get serialized value.
     *
     * @param mixed $value
     * @param string $method
     * @return string
     */
    protected function serializeValue(mixed $value, string $method): string
    {
        if ($method === self::SERIALIZATION_METHOD_IGBINARY) {

            if (function_exists('igbinary_serialize')) {

                $result = igbinary_serialize($value);

                if (is_string($result)) {
                    return $result;
                }

            }

        } else if ($method === self::SERIALIZATION_METHOD_PHP) {
            return serialize($value);
        }

        return $value;

    }

    /**
     * Get unserialized value.
     *
     * @param string $value
     * @param string $method
     * @return mixed
     */
    protected function unserializeValue(string $value, string $method): mixed
    {

        if ($method === self::SERIALIZATION_METHOD_IGBINARY) {

            if (function_exists('igbinary_unserialize')) {

                $result = igbinary_unserialize($value);

                if ($result !== false) {
                    return $result;
                }

            }

        } else if ($method === self::SERIALIZATION_METHOD_PHP) {
            return unserialize($value);
        }

        return $value;

    }

}