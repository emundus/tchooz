<?php
/**
 * @package        Joomla
 * @subpackage     eMundus
 * @copyright      Copyright (C) 2019 emundus.fr. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

$app      = Factory::getApplication();
$lang_tag = $app->getLanguage()->getTag();
$document = $app->getDocument();
$wa       = $document->getWebAssetManager();
$wa->useScript('core');
$wa->registerAndUseStyle('mod_emundus_help', 'modules/mod_emundus_help/style/mod_emundus_help.css');
$wa->registerAndUseStyle('popover', 'modules/mod_emundus_help/style/popover.css');


// Get release version
$xmlDoc = new DOMDocument();
if ($xmlDoc->load(JPATH_SITE . '/administrator/components/com_emundus/emundus.xml')) {
	$file_version = $xmlDoc->getElementsByTagName('version')->item(0)->textContent;
}
//

$current_lang = substr($lang_tag, 0, 2);

require JModuleHelper::getLayoutPath('mod_emundus_help', 'default');


