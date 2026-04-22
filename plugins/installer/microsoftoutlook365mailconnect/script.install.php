<?php
/* ======================================================
 # Microsoft/Outlook 365 Mail Connect for Joomla! - v1.0.9 (pro version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x, v5.x, v6.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2026 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Demo: 
 # Support: support@web357.com
 # Last modified: Tuesday 14 April 2026, 10:47:44 AM
 ========================================================= */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgInstallerMicrosoftoutlook365mailconnectInstallerScript extends PlgInstallerMicrosoftoutlook365mailconnectInstallerScriptHelper
{
	public $name           	= 'Microsoft/Outlook 365 Mail Connect';
	public $alias          	= 'microsoftoutlook365mailconnect';
	public $extension_type 	= 'plugin';
	public $plugin_folder   = 'installer';
}