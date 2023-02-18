<?php

class StreamWrapperForTesting
{
    const PROTOCOL = 'file';
    const STAT_MTIME_NUMERIC_OFFSET = 9;
    const STAT_MTIME_ASSOC_OFFSET = 'mtime';

    public static $pathsOpened = [];

    public $context;

    private $resource;

    public static function wrap()
    {
        stream_wrapper_unregister(self::PROTOCOL);
        stream_wrapper_register(self::PROTOCOL, get_called_class());
    }

    public static function unwrap()
    {
        set_error_handler(function() {});
        stream_wrapper_restore(self::PROTOCOL);
        restore_error_handler();
    }

    public function stream_open($path, $mode, $options, &$openedPath)
    {
        $this->unwrap();
        $pieces = preg_split('/[\\\\\\/]/', $path);
        self::$pathsOpened[] = $pieces[count($pieces) - 1];
        $this->resource = fopen($path, $mode, $options);
        $this->wrap();
        return $this->resource !== false;
    }

    public function stream_read($count)
    {
        return fread($this->resource, $count);
    }

    public function stream_close()
    {
        return fclose($this->resource);
    }

    public function stream_eof()
    {
        return feof($this->resource);
    }

    public function stream_stat()
    {
        $result = fstat($this->resource);
        if ($result) {
            $result[self::STAT_MTIME_ASSOC_OFFSET]++;
            $result[self::STAT_MTIME_NUMERIC_OFFSET]++;
        }
        return $result;
    }

    public function url_stat($path, $flags)
    {
        $func = ($flags & STREAM_URL_STAT_LINK) ? 'lstat' : 'stat';
        $this->unwrap();
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
        $this->wrap();
        if ($result) {
            $result[self::STAT_MTIME_ASSOC_OFFSET]++;
            $result[self::STAT_MTIME_NUMERIC_OFFSET]++;
        }
        return $result;
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
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
    }

    public function stream_write($data)
    {
        return fwrite($this->resource, $data);
    }

    public function stream_flush()
    {
        return fflush($this->resource);
    }
}

stream_wrapper_unregister('file');
stream_wrapper_register('file', 'StreamWrapperForTesting');