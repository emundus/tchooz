<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_whosonline
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the whosonline functions only once
JLoader::register('ModWhosonlineHelper', __DIR__ . '/helper.php');

$showmode = $params->get('showmode', 0);

if ($showmode == 0 || $showmode == 2) {
	$count = ModWhosonlineHelper::getOnlineCount();
}

if ($showmode > 0) {
	$names = ModWhosonlineHelper::getOnlineUserNames($params);
}

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');

require JModuleHelper::getLayoutPath('mod_emunduswhosonline', $params->get('layout', 'default'));
