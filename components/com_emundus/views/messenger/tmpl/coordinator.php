<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$language     = $app->getLanguage();
$current_lang = $language->getTag();

$language->load('com_emundus', JPATH_SITE . '/components/com_emundus', $current_lang, true);

$fnum   = $app->input->getString('fnum', '');
$user   = $app->getIdentity();
$euser = $app->getSession()->get('emundusUser');
$name = $euser->name;

$applicant = !\EmundusHelperAccess::asPartnerAccessLevel($user->id);
if (!$applicant)
{
	$current_profile = $euser->profile;

	if (!class_exists('EmundusModelProfile'))
	{
		require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
	}
	$m_profile          = new \EmundusModelProfile();
	$applicant_profiles = $m_profile->getApplicantsProfilesArray();

	if (in_array($current_profile, $applicant_profiles))
	{
		$applicant = true;
	}
}

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash().rand(0, 10000);

$datas = [
	'fnum' => $fnum,
	'fullname' => $name,
    'applicant' => $applicant
];
?>

<style link="media/com_emundus_vue/app_emundus.css?<?php echo $hash ?>"></style>

<div id="em-component-vue"
     component="Messenger/Messages"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
