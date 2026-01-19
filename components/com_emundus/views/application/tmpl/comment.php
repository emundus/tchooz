<?php
/**
 * Created by PhpStorm.
 * User: brivalland
 * Date: 13/11/14
 * Time: 11:24
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

defined('_JEXEC') or die('Restricted access');

$data = LayoutFactory::prepareVueData();

$app = Factory::getApplication();
$app->getSession()->set('application_layout', 'comment');
$em_config = ComponentHelper::getParams('com_emundus');

require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
$m_users = new EmundusModelUsers();
$emundus_user = $app->getSession()->get('emundusUser');
$applicant_profiles = $m_users->getApplicantProfiles();
$applicant_profile_ids = array_map(function($profile) {
	return $profile->id;
}, $applicant_profiles);

$is_applicant = in_array($emundus_user->profile, $applicant_profile_ids);

$user_comment_access = [
	'c' => EmundusHelperAccess::asAccessAction(10, 'c', $this->_user->id, $this->fnum) || $is_applicant,
	'r' => EmundusHelperAccess::asAccessAction(10, 'r', $this->_user->id, $this->fnum) || $is_applicant,
	'u' => EmundusHelperAccess::asAccessAction(10, 'u', $this->_user->id, $this->fnum),
	'd' => EmundusHelperAccess::asAccessAction(10, 'd', $this->_user->id, $this->fnum),
];
?>

<div class="row">
    <div class="panel panel-default widget em-container-comments em-container-form">
        <div class="panel-heading em-container-form-heading !tw-bg-profile-full">
            <h3 class="panel-title">
                <span class="material-symbols-outlined">comment</span>
				<?= Text::_('COM_EMUNDUS_COMMENTS') ?>
            </h3>
            <div class="btn-group pull-right">
                <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_back</span></button>
                <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_forward</span></button>
            </div>
        </div>
    </div>
</div>

<div id="em-component-vue"
     component="Comments"
     class="com_emundus_vue"
     user="<?= $this->_user->id ?>"
     ccid="<?= $this->ccid ?>"
     fnum="<?= $this->fnum ?>"
     access='<?= json_encode($user_comment_access); ?>'
     is_applicant="<?= $is_applicant; ?>"
     applicants_allowed_to_comment="<?= ($em_config->get('allow_applicant_to_comment', false) ? 1 : 0); ?>"
     current_form="<?= 0 ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
     shortLang="<?= $data['short_lang'] ?>"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>"
     border="0"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo uniqid(); ?>"></script>