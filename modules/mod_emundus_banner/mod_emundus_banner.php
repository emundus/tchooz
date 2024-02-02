<?php
/**
 * @package        Joomla
 * @subpackage     eMundus
 * @copyright      Copyright (C) 2019 emundus.fr. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

include_once(JPATH_BASE . '/components/com_emundus/models/profile.php');
$m_profiles = new EmundusModelProfile();
$app_prof   = $m_profiles->getApplicantsProfilesArray();

$user = Factory::getApplication()->getSession()->get('emundusUser');

$display = false;
if (!empty($user)) {
	if (in_array($user->profile, $app_prof)) {
		$display = true;
	}
}
else {
	$display = true;
}

if ($display) {
	$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
	$wa->registerAndUseStyle('mod_emundus_banner','modules/mod_emundus_banner/style/mod_emundus_banner.css');

	$image_link = $params->get('mod_em_banner_image', '/images/custom/default_banner.png');

	require ModuleHelper::getLayoutPath('mod_emundus_banner', 'default');
}


