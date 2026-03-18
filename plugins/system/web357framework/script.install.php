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
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

if ( ! class_exists('PlgSystemWeb357frameworkInstallerScript'))
{
	require_once __DIR__ . '/script.install.helper.php';

	class PlgSystemWeb357frameworkInstallerScript extends PlgSystemWeb357frameworkInstallerScriptHelper
	{
		public $name           = 'Web357 Framework';
		public $alias          = 'web357framework';
		public $extension_type = 'plugin';

		public function onBeforeInstall($route)
		{
			// Check if is new version
			if ( ! $this->isNewer())
			{
				$this->softbreak = true;
				//return false;
			}

			return true;
		}

		public function onAfterInstall($route)
		{
			$this->deleteOldFiles();
		}

		private function deleteOldFiles()
		{
			// Delete old files
			if (is_file(JPATH_SITE . '/plugins/system/web357framework/web357framework.script.php')) {
				File::delete(JPATH_SITE . '/plugins/system/web357framework/web357framework.script.php');
			}

			// Delete folder
			if (is_dir(JPATH_SITE . '/plugins/system/web357framework/elements/assets')) {
				Folder::delete(JPATH_SITE . '/plugins/system/web357framework/elements/assets');
			}
		}
	}
}