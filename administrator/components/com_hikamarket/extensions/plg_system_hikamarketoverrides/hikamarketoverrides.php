<?php
/**
 * @package    HikaMarket for Joomla!
 * @version    5.0.0
 * @author     Obsidev S.A.R.L.
 * @copyright  (C) 2011-2024 OBSIDEV. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
jimport('joomla.plugin.plugin');
class plgSystemHikamarketoverrides extends JPlugin {
	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
		$this->init();
	}

	private function init() {
		static $init = null;
		if($init !== null)
			return;
		$init = true;

		$hikashopHelper = rtrim(JPATH_ADMINISTRATOR,DIRECTORY_SEPARATOR).'/components/com_hikashop/helpers/helper.php';
		$marketHelper = rtrim(JPATH_ADMINISTRATOR,DIRECTORY_SEPARATOR).'/components/com_hikamarket/helpers/helper.php';
		if(!file_exists($hikashopHelper) || !file_exists($marketHelper))
			return;

		$db = JFactory::getDBO();
		$db->setQuery('SELECT config_value FROM #__hikashop_config WHERE config_namekey = ' . $db->Quote('version'));
		$version = $db->loadResult();

		jimport('joomla.filesystem.folder');

		$path = rtrim(JPATH_ADMINISTRATOR,DIRECTORY_SEPARATOR).'/components/com_hikamarket/overrides/'.$version.'/';
		if(!JFolder::exists($path))
			return;

		$allFiles = JFolder::files($path);
		if(empty($allFiles))
			return;
		include_once $hikashopHelper;
		foreach($allFiles as $oneFile) {
			if(substr($oneFile, -4) != '.php')
				continue;
			if(substr($oneFile, 0, 6) == 'class.') {
				$originalFile = rtrim(JPATH_ADMINISTRATOR,DIRECTORY_SEPARATOR).'/components/com_hikashop/classes/'.substr($oneFile, 6);
				if(file_exists($originalFile)) {
					include_once $originalFile;
					include_once $path . $oneFile;
				}
			} else {
				include_once $path . $oneFile;
			}
		}
	}
}
