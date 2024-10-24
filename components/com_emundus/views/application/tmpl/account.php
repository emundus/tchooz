<?php

use Joomla\CMS\Uri\Uri;

if (!class_exists('EmundusModelUsers'))
{
	require_once JPATH_SITE . 'com_emundus/models/users.php';
}
$m_users   = new EmundusModelUsers;
$applicant = $m_users->getUserById($this->sid)[0];

?>

<div id="form_profile" class="tw-pl-4">
    <div class="tw-flex tw-items-center tw-mt-4 tw-gap-6">
        <div class="em-flex-row em-small-flex-column em-small-align-items-start">
            <div class="em-profile-picture-big no-hover"
				<?php if (empty($applicant->profile_picture)) : ?>
                    style="background-image:url(<?php echo Uri::base() ?>/media/com_emundus/images/profile/default-profile.jpg)"
				<?php else : ?>
                    style="background-image:url(<?php echo Uri::base() ?>/<?php echo $applicant->profile_picture ?>)"
				<?php endif; ?>
            >
            </div>
        </div>
        <div>
            <p class="tw-font-medium">
				<?php echo $applicant->lastname . ' ' . $applicant->firstname; ?>
            </p>
        </div>
    </div>
    <iframe id="iframe"
            src="<?php echo Uri::base(); ?>index.php?option=com_fabrik&view=details&formid=374&tmpl=component&iframe=1&rowid=<?php echo $this->sid; ?>"
            height="600" width="100%"
    </iframe>
</div>




