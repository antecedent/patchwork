<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation;

class StreamFilter extends \php_user_filter
{
    const FILTER_NAME = 'patchwork';

    private static $nextImport;
    private $data;

    static function register()
    {
        stream_filter_register(self::FILTER_NAME, __CLASS__);
    }

    static function rewritePath($path)
    {
        self::$nextImport = $path;
        StreamWrapper::unwrap();
        return sprintf('php://filter/read=%s/resource=%s', self::FILTER_NAME, $path);
    }

    function filter($in, $out, &$consumed, $closing)
    {
         while ($bucket = stream_bucket_make_writeable($in)) {
             $this->data .= $bucket->data;
         }
         if ($closing || feof($this->stream)) {
             $consumed = strlen($this->data);
             notifyAboutImport(self::$nextImport);
             if (shouldTransform(self::$nextImport)) {
                 $this->data = transformString($this->data, self::$nextImport);
             }
             $bucket = stream_bucket_new($this->stream, $this->data);
             stream_bucket_append($out, $bucket);
             $this->data = '';
             return PSFS_PASS_ON;
         }
         StreamWrapper::wrap();
         return PSFS_FEED_ME;
     }
}
