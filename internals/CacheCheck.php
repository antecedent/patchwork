<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\CacheCheck;

function run()
{
    foreach (getIniSettingMapping() as $driver => $setting) {
        if (ini_get($setting)) {
            trigger_error("Patchwork cannot operate when $driver is enabled", E_USER_ERROR);
            return;
        }
    }
}

function getIniSettingMapping()
{
    return array(
        "APC" => "apc.enabled",
        "eAccelerator" => "eaccelerator.enable",
        "WinCache" => "wincache.ocenabled",
        "XCache" => "xcache.cacher",
        "Zend Optimizer+" => "zend_optimizerplus.enable",
    );
}
