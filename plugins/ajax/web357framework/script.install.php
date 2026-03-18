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

if ( ! class_exists('PlgAjaxWeb357frameworkInstallerScript'))
{
	require_once __DIR__ . '/script.install.helper.php';

	class PlgAjaxWeb357frameworkInstallerScript extends PlgAjaxWeb357frameworkInstallerScriptHelper
	{
		public $name           	= 'Web357 Framework';
		public $alias          	= 'web357framework';
		public $extension_type 	= 'plugin';
		public $plugin_folder   = 'ajax';
	}
}