<?php
/* ======================================================
 # Web357 Framework for Joomla! - v2.0.0 (free version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Support: support@web357.com
 # Last modified: Monday 27 October 2025, 03:04:38 PM
 ========================================================= */

defined('_JEXEC') or die;

// Registers Web357 framework's namespace
JLoader::registerNamespace('Web357Framework', __DIR__ . '/Web357Framework/', false, false, 'psr4');

JLoader::registerAlias('Functions', '\\Web357Framework\\Functions');
JLoader::registerAlias('VersionChecker', '\\Web357Framework\\VersionChecker');

/* Create aliases for Fields eg \Web357Framework\Asset\Select2Asset to JFormFieldweb357_registerasset */
//$files = scandir(__DIR__ . '/Web357Framework/Field');
//foreach ($files as $file) {
//    if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
//        continue;
//    }
//
//    $className = 'Web357Framework\\Field\\' . basename($file, '.php');
//
//    /* Remove "Field" suffix and convert to lowercase */
//    $legacyClassName = 'JFormField' . strtolower(substr(basename($file, '.php'), 0, -5));
//    JLoader::registerAlias($legacyClassName, $className);
//}
