<?php
/**
 * @package       Joomla
 * @subpackage    eMundus
 * @link          http://www.emundus.fr
 * @copyright     Copyright (C) 2018 eMundus SAS. All rights reserved.
 * @license       GNU/GPL
 * @author        eMundus SAS
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();

$offset = $app->getConfig()->get('offset');
$app->getSession()->set('application_layout', 'attachment');
$lang = $app->getLanguage();

$can_export          = EmundusHelperAccess::asAccessAction(8, 'c', $this->_user->id, $this->fnum);
$can_see_attachments = EmundusHelperAccess::getUserAllowedAttachmentIDs($this->_user->id);

require_once(JPATH_SITE . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash() . rand(0, 99999);
?>


<div class="row">
    <div class="panel panel-default widget em-container-attachment em-container-form">
        <div class="panel-heading em-container-form-heading !tw-bg-profile-full">
            <h3 class="panel-title">
                <span class="material-symbols-outlined">file_present</span>
				<?= JText::_('COM_EMUNDUS_ONBOARD_DOCUMENTS') . ' - ' . $this->attachmentsProgress . ' % ' . JText::_('COM_EMUNDUS_APPLICATION_SENT'); ?>
            </h3>
            <div class="btn-group pull-right">
                <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_back</span>
                </button>
                <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div id="em-component-vue"
     component="Attachments"
     class="com_emundus_vue"
     user="<?= $this->_user->id ?>"
     fnum="<?= $this->fnum ?>"
     currentLanguage="<?= $lang->getTag() ?>"
     base="<?= Uri::base() ?>"
     attachments="<?= base64_encode(json_encode($this->userAttachments)) ?>"
     rights="<?= base64_encode(json_encode(['can_export' => $can_export, 'can_see' => $can_see_attachments])) ?>"
     columns="<?= base64_encode(json_encode($this->columns)) ?>"
     is_applicant="<?php echo $this->is_applicant ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
