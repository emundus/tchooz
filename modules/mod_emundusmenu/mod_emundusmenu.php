<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_emundusmenu
 *
 * @copyright      Copyright (C) 2016 emundus.fr, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Module\Emundusmenu\Site\Helper\EmundusmenuHelper;

require_once JPATH_ROOT . '/components/com_emundus/models/profile.php';
require_once JPATH_ROOT . '/components/com_emundus/models/settings.php';
require_once JPATH_ROOT . '/components/com_emundus/helpers/menu.php';

$app = Factory::getApplication();

$class_sfx         = htmlspecialchars($params->get('class_sfx'));
$tag         = $params->get('tag_id');

$document = $app->getDocument();
$wa       = $document->getWebAssetManager();
$wa->registerAndUseStyle('mod_emundusmenu', 'modules/mod_emundusmenu/style/mod_emundusmenu.css');

$user = $app->getSession()->get('emundusUser');


$m_profile = $app->bootComponent('com_emundus')->getMVCFactory()->createModel('Profile', 'EmundusModel');

global $gantry;
if (!empty($gantry)) {
	$gantry->addLess('menu.less', 'menu.css', 1, array('menustyle' => $gantry->get('menustyle', 'light'), 'menuHoverColor' => $gantry->get('linkcolor'), 'menuDropBack' => $gantry->get('accentcolor')));
	$gantry->addLess('menu-responsive.less', 'menu-responsive.css', 1, array('menustyle' => $gantry->get('menustyle', 'light'), 'menuHoverColor' => $gantry->get('linkcolor'), 'menuDropBack' => $gantry->get('accentcolor')));
	$layout = 'default';
}
else {
	$layout = 'gantry5';
}

$menu_style = $params->get('menu_style', $layout);
$layout     = $params->get('layout', $layout);

if ($params->get('menu_style') == 'tchooz_vertical') {
	$layout = $menu_style;
} else {
	$layout = 'default';
	$wa->registerAndUseStyle('mod_emundusmenu_applicant', 'modules/mod_emundusmenu/style/mod_emundusmenu_applicant.css');
}

$display_applicant_menu = $params->get('display_applicant_menu', 1);
$applicant_menu         = $params->get('applicant_menu', '');
$display_tchooz         = $params->get('displayTchooz', 1);
$favicon_link = EmundusHelperMenu::getHomepageLink($params->get('favicon_link', 'index.php'));

$m_settings = $app->bootComponent('com_emundus')->getMVCFactory()->createModel('Settings', 'EmundusModel');
$favicon = $m_settings->getFavicon();

if ((!empty($user->applicant) || !empty($user->fnum)) && $display_applicant_menu == 0) {
	return;
}

$list        = array();
$tchooz_list = array();
if (isset($user->menutype) && empty($user->applicant) || isset($user->menutype) && !empty($user->applicant) && empty($applicant_menu)) {
	$list            = EmundusmenuHelper::getList($params);
	$current_profile = $m_profile->getProfileById($user->profile);
	if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id) && $current_profile->applicant == 0) {
		$tchooz_list = EmundusmenuHelper::getList($params, 'onboardingmenu');
	}
	$help_list = EmundusmenuHelper::getList($params, 'usermenu');
}
elseif (!empty($applicant_menu)) {
	$list   = EmundusmenuHelper::getList($params, $applicant_menu);
	$layout = 'default';

	$wa->registerAndUseStyle('mod_emundusmenu_applicant', 'modules/mod_emundusmenu/style/mod_emundusmenu_applicant.css');
}

$menu              = $app->getMenu();
$active            = $menu->getActive();
$active_id         = isset($active) ? $active->id : $menu->getDefault()->id;
$path              = isset($active) ? $active->tree : array();

if (!$list) {
	return;
}

require ModuleHelper::getLayoutPath('mod_emundusmenu', $layout);