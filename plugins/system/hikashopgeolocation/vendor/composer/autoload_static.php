<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php


namespace Composer\Autoload;

class ComposerStaticInitfd8551cf43a81e48355841d37d2fe6ed
{
    public static $prefixLengthsPsr4 = array (
        'I' => 
        array (
            'IP2LocationIO\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'IP2LocationIO\\' => 
        array (
            0 => __DIR__ . '/..' . '/ip2location/ip2location-io-php/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfd8551cf43a81e48355841d37d2fe6ed::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfd8551cf43a81e48355841d37d2fe6ed::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitfd8551cf43a81e48355841d37d2fe6ed::$classMap;

        }, null, ClassLoader::class);
    }
}
