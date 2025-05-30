<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

require_once(dirname(__FILE__)) . '/src/Helper/FalangHelper.php';

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\String\StringHelper;
use Joomla\CMS\Language\Text;
use Faboba\Module\Falang\Site\Helper\FalangHelper;

if (!FaLangHelper::isFalangDriverActive()){
	echo Text::_("MOD_FALANG_PLUGIN_DRIVER_NOT_ENABLED");
	return;
}

$headerText	= StringHelper::trim($params->get('header_text',''));
$footerText	= StringHelper::trim($params->get('footer_text',''));



/* >>> [PAID] >>> */
$optionsPath = StringHelper::trim($params->get('imagespath',''));
$imagesType = StringHelper::trim($params->get('imagestype','gif'));
$relativePath = empty($optionsPath)?true:false;
$imagesPath = empty($optionsPath)?'mod_falang/':$optionsPath;
/* <<< [PAID] <<< */

$list   = FaLangHelper::getList($params);

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''));

require ModuleHelper::getLayoutPath('mod_falang', $params->get('layout', 'default'));
