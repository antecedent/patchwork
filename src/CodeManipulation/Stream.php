<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2023 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation;

use Patchwork\Utils;

class Stream
{
    const STREAM_OPEN_FOR_INCLUDE = 128;
    const STAT_MTIME_NUMERIC_OFFSET = 9;
    const STAT_MTIME_ASSOC_OFFSET = 'mtime';

    protected static $protocols = ['file', 'phar'];
    protected static $otherWrapperClass;

    public $context;
    public $resource;

    public static function discoverOtherWrapper()
    {
        $handle = fopen(__FILE__, 'r');
        $meta = stream_get_meta_data($handle);
        if ($meta && isset($meta['wrapper_data']) && is_object($meta['wrapper_data']) && !($meta['wrapper_data'] instanceof self)) {
            static::$otherWrapperClass = get_class($meta['wrapper_data']);
        }
    }

    public static function wrap()
    {
        foreach (static::$protocols as $protocol) {
            stream_wrapper_unregister($protocol);
            stream_wrapper_register($protocol, get_called_class());
        }
    }

    public static function unwrap()
    {
        foreach (static::$protocols as $protocol) {
            set_error_handler(function() {});
            stream_wrapper_restore($protocol);
            restore_error_handler();
        }
    }

    public static function reinstateWrapper()
    {
        static::discoverOtherWrapper();
        static::unwrap();
        static::wrap();
    }

    public function stream_open($path, $mode, $options, &$openedPath)
    {
        $including = (bool) ($options & self::STREAM_OPEN_FOR_INCLUDE);

        // `parse_ini_file()` also sets STREAM_OPEN_FOR_INCLUDE.
        if ($including) {
            $frame = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            if (empty($frame['class']) && $frame['function'] === 'parse_ini_file') {
                $including = false;
            }
        }

        if ($including && shouldTransform($path)) {
            $this->resource = transformAndOpen($path);
            return $this->resource !== false;
        }

        $this->resource = static::fopen($path, $mode, $options, $this->context);
        return $this->resource !== false;
    }

    public static function getOtherWrapper($context)
    {
        if (isset(static::$otherWrapperClass)) {
            $class = static::$otherWrapperClass;
            $otherWrapper = new $class;
            if ($context !== null) {
                $otherWrapper->context = $context;
            }
            return $otherWrapper;
        }
    }

    public static function alternate(callable $internal, $resource, $wrapped, array $args = [], array $extraArgs = [], $context = null, $shouldReturnResource = false)
    {
        $shouldAddResourceArg = true;
        if ($resource === null) {
            $resource = static::getOtherWrapper($context);
            $shouldAddResourceArg = false;
        }
        if (is_object($resource)) {
            $args = array_merge($args, $extraArgs);
            $ladder = function() use ($resource, $wrapped, $args) {
                switch (count($args)) {
                    case 0:
                        return $resource->$wrapped();
                    case 1:
                        return $resource->$wrapped($args[0]);
                    case 2:
                        return $resource->$wrapped($args[0], $args[1]);
                    default:
                        return call_user_func_array([$resource, $wrapped], $args);
                }
            };
            $result = $ladder();
            static::unwrap();
            static::wrap();
        } else {
            if ($shouldAddResourceArg) {
                array_unshift($args, $resource);
            }
            if ($context !== null) {
                $args[] = $context;
            }
            $result = static::bypass(function() use ($internal, $args) {
                switch (count($args)) {
                    case 0:
                        return $internal();
                    case 1:
                        return $internal($args[0]);
                    case 2:
                        return $internal($args[0], $args[1]);
                    default:
                        return call_user_func_array($internal, $args);
                }
            });
        }
        if ($shouldReturnResource) {
            return ($result !== false) ? $resource : false;
        }
        return $result;
    } 

    public static function fopen($path, $mode, $options, $context = null)
    {
        $otherWrapper = static::getOtherWrapper($context);
        if ($otherWrapper !== null) {
            $openedPath = null;
            $result = $otherWrapper->stream_open($path, $mode, $options, $openedPath);
            return $result !== false ? $otherWrapper : false;
        }
        return static::bypass(function() use ($path, $mode, $options, $context) {
            if ($context === null) {
                return fopen($path, $mode, $options);
            }
            return fopen($path, $mode, $options, $context);
        });
    }

    public function stream_close()
    {
        return static::fclose($this->resource);
    }

    public static function fclose($resource)
    {
        return static::alternate('fclose', $resource, 'stream_close');
    }

    public static function fread($resource, $count)
    {
        return static::alternate('fread', $resource, 'stream_read', [$count]);
    }

    public static function feof($resource)
    {
        return static::alternate('feof', $resource, 'stream_eof');
    }

    public function stream_eof()
    {
        return static::feof($this->resource);
    }

    public function stream_flush()
    {
        return static::alternate('fflush', $this->resource, 'stream_flush');
    }

    public function stream_read($count)
    {
        return static::fread($this->resource, $count);
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        if (is_object($this->resource)) {
            return $this->resource->stream_seek($offset, $whence);
        }
        return fseek($this->resource, $offset, $whence) === 0;
    }

    public function stream_stat()
    {
        if (is_object($this->resource)) {
            return $this->resource->stream_stat();
        }
        $result = fstat($this->resource);
        if ($result) {
            $result[self::STAT_MTIME_ASSOC_OFFSET]++;
            $result[self::STAT_MTIME_NUMERIC_OFFSET]++;
        }
        return $result;
    }

    public function stream_tell()
    {
        return static::alternate('ftell', $this->resource, 'stream_tell');
    }

    public static function bypass(callable $action)
    {
        static::unwrap();
        $result = $action();
        static::wrap();
        return $result;
    }

    public function url_stat($path, $flags)
    {
        $internal = function($path, $flags) {
            $func = ($flags & STREAM_URL_STAT_LINK) ? 'lstat' : 'stat';
            clearstatcache();
            if ($flags & STREAM_URL_STAT_QUIET) {
                set_error_handler(function() {});
                try {
                    $result = call_user_func($func, $path);
                } catch (\Exception $e) {
                    $result = null;
                }
                restore_error_handler();
            } else {
                $result = call_user_func($func, $path);
            }
            clearstatcache();
            if ($result) {
                $result[self::STAT_MTIME_ASSOC_OFFSET]++;
                $result[self::STAT_MTIME_NUMERIC_OFFSET]++;
            }
            return $result;
        };
        return static::alternate($internal, null, __FUNCTION__, [$path, $flags], [], $this->context);
    }

    public function dir_closedir()
    {
        return static::alternate('closedir', $this->resource, 'dir_closedir') ?: true;
    }

    public function dir_opendir($path, $options)
    {
        $this->resource = static::alternate('opendir', null, __FUNCTION__, [$path], [$options], $this->context);
        return $this->resource !== false;
    }

    public function dir_readdir()
    {
        return static::alternate('readdir', $this->resource, __FUNCTION__);
    }

    public function dir_rewinddir()
    {
        return static::alternate('rewinddir', $this->resource, __FUNCTION__);
    }

    public function mkdir($path, $mode, $options)
    {
        return static::alternate('mkdir', null, __FUNCTION__, [$path, $mode, $options], [], $this->context);
    }

    public function rename($pathFrom, $pathTo)
    {
        return static::alternate('rename', null, __FUNCTION__, [$pathFrom, $pathTo], [], $this->context);
    }

    public function rmdir($path, $options)
    {
        return static::alternate('rmdir', null, __FUNCTION__, [$path], [$options], $this->context);
    }

    public function stream_cast($castAs)
    {
        return static::alternate(function() {
            return $this->resource;
        }, null, __FUNCTION__, [$castAs]);
    }

    public function stream_lock($operation)
    {
        if ($operation === '0' || $operation === 0) {
            $operation = LOCK_EX;
        }
        return static::alternate('flock', $this->resource, __FUNCTION__, [$operation]);
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        $internal = function($option, $arg1, $arg2) {
            switch ($option) {
                case STREAM_OPTION_BLOCKING:
                    return stream_set_blocking($this->resource, $arg1);
                case STREAM_OPTION_READ_TIMEOUT:
                    return stream_set_timeout($this->resource, $arg1, $arg2);
                case STREAM_OPTION_WRITE_BUFFER:
                    return stream_set_write_buffer($this->resource, $arg1);
                case STREAM_OPTION_READ_BUFFER:
                    return stream_set_read_buffer($this->resource, $arg1);
            }
        };
        return static::alternate($internal, $this->resource, __FUNCTION__, [$option, $arg1, $arg2]);
    }

    public function stream_write($data)
    {
        return static::fwrite($this->resource, $data);
    }

    public static function fwrite($resource, $data)
    {
        return static::alternate('fwrite', $resource, 'stream_write', [$data]);
    }

    public function unlink($path)
    {
        return static::alternate('unlink', $this->resource, __FUNCTION__, [$path], [], $this->context);
    }

    public function stream_metadata($path, $option, $value)
    {
        $internal = function($path, $option, $value) {
            switch ($option) {
                case STREAM_META_TOUCH:
                    if (empty($value)) {
                        return touch($path);
                    } else {
                        return touch($path, $value[0], $value[1]);
                    }
                case STREAM_META_OWNER_NAME:
                case STREAM_META_OWNER:
                    return chown($path, $value);
                case STREAM_META_GROUP_NAME:
                case STREAM_META_GROUP:
                    return chgrp($path, $value);
                case STREAM_META_ACCESS:
                    return chmod($path, $value);
            }
        };
        return static::alternate($internal, null, __FUNCTION__, [$path, $option, $value]);
    }

    public function stream_truncate($newSize)
    {
        return static::alternate('ftruncate', $this->resource, __FUNCTION__, [$newSize]);
    }
}
