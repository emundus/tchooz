<?php
/**
 * @package        Joomla.Site
 * @subpackage     mod_menu
 * @copyright      Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;
// Note. It is important to remove spaces between elements.

require_once(JPATH_SITE . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();

Text::script('COM_EMUNDUS_SWITCH_PROFILE_ERROR');

?>

<link rel="stylesheet" href="modules/mod_emundus_user_dropdown/style/mod_emundus_user_dropdown.css?<?php echo $hash; ?>"
      type="text/css"/>

<?php
$guest = JFactory::getUser()->guest;

if ($user != null)
{

// background color of the home page
	include_once(JPATH_BASE . '/components/com_emundus/models/profile.php');
	$m_profiles = new EmundusModelProfile();
	$app_prof   = $m_profiles->getApplicantsProfilesArray();
	if (!empty($user->profile))
	{
		$user_profile = $m_profiles->getProfileById($user->profile);
	}

	$user = JFactory::getSession()->get('emundusUser');

	if (in_array($user->profile, $app_prof))
	{

		?>
        <style>
            .gantry.homepage #g-page-surround {
                background: var(--em-applicant-bg);
            }
        </style>

		<?php
	}

	else
	{

		?>
        <style>
            .gantry.homepage #g-page-surround {
                background: var(--em-coordinator-bg);
            }
        </style>

		<?php
	}
	?>

    <style>
        .dropdown-header {
            display: block;
            font-size: unset;
            line-height: 1.42857143;
            white-space: nowrap;
            padding: unset;
        }

        .dropdown-menu-right {
            right: 0;
            left: auto;
        }

        #userDropdownIcon:hover,
        #userDropdownIcon.active {
            border: 1px solid;
            box-shadow: inset 0 0 20px rgba(255, 255, 255, .5), 0 0 20px rgba(255, 255, 255, .2);
            outline-color: rgba(255, 255, 255, 0);
            outline-offset: 15px;
            color: #fff;
        }

        #userDropdownMenu .divider {
            height: 1px;
            margin: 9px 1px;
            overflow: hidden;
            background-color: #e5e5e5;
            border-bottom: 1px solid #fff;
        }

        #userDropdownMenu li > a:hover,
        #userDropdownMenu .active > a {
            background: #<?= $secondary_color; ?>;
        }

        .select {
            text-align: left;
            width: 100%;
        }

        .select .profile-select {
            height: 35px;
            padding: 0 32px 0 12px !important;
            border: 1px solid #e5e5e5;
            background-position-x: 95%;
            background-position-y: 54%;
            -webkit-appearance: none;
            background-image: url('../../../../images/emundus/arrow.svg');
            background-size: 8px;
            background-repeat: no-repeat;
            -moz-appearance: none;
            -webkit-appearance: none;
            width: 100%;
            color: #353544;
            background-color: var(--neutral-50);
            font-family: var(--em-profile-font), Inter, sans-serif;
        }

        .select .profile-select:hover {
            background-color: var(--neutral-0) !important;
        }

        .select .profile-select:focus {
            background-color: var(--neutral-0) !important;
        }

        .dropdown-menu > li > a {
            padding: unset;
        }

        .userDropdown-tip {
            position: fixed;
            width: 100vw !important;
            height: 100vw;
            left: 0;
            top: 0;
            background-color: rgba(60, 60, 60, 0.65);
            z-index: 20;
        }

        .userDropdownLabel-tip {
            position: fixed;
            right: 0;
            top: 18px;
            z-index: 999999;
            background: var(--neutral-0);
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }

        #g-navigation .g-container #header-c .userDropdownIcon-tip {
            margin: 23px 30px !important;
        }

        .em-user-dropdown-tip {
            background: var(--neutral-0);
            position: fixed;
            right: 280px;
            padding: 10px;
            border-radius: 2px;
            top: 15px;
            transition: opacity 0.2s ease-in-out;
        }

        .em-user-dropdown-tip-link {
            float: right;
            color: var(--main-500);
            cursor: pointer;
        }

        .em-user-dropdown-icon {
            color: var(--em-profile-color);
            border: solid 3px var(--transparent);
        }

        .em-profile-container p:nth-child(2) {
            overflow: hidden;
            max-width: 140px;
            max-height: 30px;
            font-family: var(--em-profile-font), Inter, sans-serif;
            font-size: 12px;
            font-style: normal;
            font-weight: 400;
            line-height: 15px;
            letter-spacing: 0.004em;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .em-user-dropdown-icon:before {
            background-color: var(--em-profile-color);
            color: white;
            opacity: 1;
            content: attr(data-initials);
            display: inline-block;
            font-weight: bold;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 0.5em;
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            transition: all 0.3s ease-in-out;
        }

        @supports (background-color: hsl(from white h s 90%)) {
            .em-user-dropdown-icon:before {
                background-color: hsl(from var(--em-profile-color) h s 90%);
                color: var(--em-profile-color);
            }
        }

        .em-user-dropdown-icon-big {
            font-size: 24px;
        }

        .em-user-dropdown-icon-big:before {
            width: 68px;
            height: 68px;
            line-height: 68px;
        }

        .em-user-dropdown-icon-xxl {
            font-size: 28px;
        }

        .em-user-dropdown-icon-xxl:before {
            width: 100px;
            height: 100px;
            line-height: 100px;
        }

        .em-user-dropdown-icon-xxl:hover:before {
            color: transparent;
        }
    </style>

	<?= $intro; ?>

    <!-- Button which opens up the dropdown menu. -->
    <div class='dropdown' tabindex="0" id="userDropdown" style="float: right;">
		<?php if ($display_svg == 1) : ?>
            <div id="background-shapes"></div>
		<?php endif; ?>
		<?php if (!empty($profile_picture)): ?>
            <div id="userDropdownLabel">
                <div class="em-flex-row em-flex-end em-profile-container" onclick="manageHeight()">
                    <div class="tw-mr-4">
						<?php if (!empty($user)) : ?>
                            <p class="em-text-neutral-900 em-font-weight-500"><?= $user->firstname . ' ' . $user->lastname[0] . '.'; ?></p>
						<?php endif; ?>
						<?php if (!empty($profile_label)) : ?>
                            <p class="em-profile-color em-text-italic"
                               title="<?= $profile_label; ?>"><?= $profile_label; ?></p>
						<?php endif; ?>
                    </div>
                    <div class="em-profile-picture em-pointer em-user-dropdown-button"
                         style="background-image:url('<?php echo $profile_picture ?>');">
                    </div>
                </div>
            </div>
		<?php else : ?>
            <div id="userDropdownLabel" onclick="manageHeight()">
                <div class="em-flex-row em-flex-end em-profile-container">
                    <div class="tw-mr-4">
						<?php if (!empty($user)) : ?>
                            <p class="em-text-neutral-900 em-font-weight-500"><?= $user->firstname . ' ' . $user->lastname[0] . '.'; ?></p>
						<?php endif; ?>
						<?php if (!empty($profile_label)) : ?>
                            <p class="em-profile-color em-text-italic"><?= $profile_label; ?></p>
						<?php endif; ?>
                    </div>
                    <div class="em-user-dropdown-button" aria-haspopup="true" aria-expanded="false">
                        <span class="em-user-dropdown-icon"
                              data-initials="<?php echo substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1); ?>"
                              alt="<?php echo JText::_('PROFILE_ICON_ALT') ?>"></span>
                    </div>

                </div>
            </div>
		<?php endif; ?>
        <input type="hidden" value="<?= $switch_profile_redirect; ?>" id="switch_profile_redirect">
        <ul class="dropdown-menu dropdown-menu-right" id="userDropdownMenu" aria-labelledby="userDropdownLabel">
			<?php if ($is_anonym_user): ?>
                <p><?= JText::_('ANONYM_SESSION') ?></p>
                <div class=" em-w-100">
                    <label for="anonym_token"><?= JText::_('TOKEN') ?></label>
                    <input onclick="copyTokenToClipBoard()" style="cursor:copy;" class="em-w-100" name="anonym_token"
                           type="text" value="<?= $user->anonym_token; ?>">
                </div>
			<?php else: ?>
                <div class="em-flex-column-default em-w-100">
					<?php if (!empty($profile_picture)): ?>
                        <div class="em-profile-picture-modal"
                             style="background-image:url('<?php echo $profile_picture ?>');">
                        </div>
					<?php else : ?>
                        <span class="em-user-dropdown-icon em-user-dropdown-icon-big"
                              data-initials="<?php echo substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1); ?>"
                              alt="<?php echo JText::_('PROFILE_ICON_ALT') ?>"></span>
					<?php endif; ?>
                    <li class="dropdown-header em-text-align-center em-font-weight-500 em-text-neutral-900"><?= $user->firstname . ' ' . $user->lastname; ?></li>
                    <li class="dropdown-header em-text-align-center em-text-neutral-600"
                        title="<?= $user->email; ?>"><?= $user->email; ?></li>
                </div>
			<?php endif; ?>

            <hr style="width: 100%">

			<?php
			$ids_array = array();
			if (isset($user->fnums) && $user->fnums)
			{
				foreach ($user->fnums as $fnum)
				{
					$ids_array[$fnum->profile_id] = $fnum->fnum;
				}
			}

			if (!empty($user->emProfiles) && sizeof($user->emProfiles) > 1 && (!$only_applicant))
			{
				echo '<h5 class="mb-2 em-profile-font">' . JText::_('SELECT_PROFILE') . '</h5>';
				echo '<div class="select">';
				echo '<select class="profile-select" id="profile" name="profiles" onchange="postCProfile()"> ';
				foreach ($user->emProfiles as $profile)
				{
					if ($profile->published && !$applicant_option)
					{
						echo '<option  value="' . $profile->id . "." . $ids_array[$profile->id] . '"' . (in_array($user->profile, $app_prof) ? 'selected="selected"' : "") . '>' . JText::_('APPLICANT') . '</option>';
						$applicant_option = true;
					}
                    elseif (!$profile->published)
					{
						echo '<option  value="' . $profile->id . "." . '"' . (($user->profile == $profile->id) ? 'selected="selected"' : "") . '>' . trim($profile->label) . '</option>';
					}
				}
				echo '</select></div><br/>';
			}
			?>

			<?php if ($show_update == '1' && !$is_anonym_user) : ?>
                <li><a class="edit-button-user em-flex-row em-flex-important em-flex-center"
                       href="<?= $link_edit_profile ?>" style="margin-top: 0"><span
                                class="material-icons-outlined tw-mr-2">person_outline</span><?= JText::_('COM_EMUNDUS_USER_MENU_PROFILE_LABEL') ?>
                    </a></li>
			<?php endif; ?>
			<?php if (!empty($custom_actions))
			{
				foreach ($custom_actions as $custom_action)
				{
					if (!empty($custom_action->link) || !empty($custom_action->onclick))
					{
						?>
                        <li>
							<?php
							switch ($custom_action->type)
							{
								case 'button':
									echo '<a type="button" onclick="' . $custom_action->onclick . '" class="edit-button-user em-pointer">' . JText::_($custom_action->title) . '</a>';
									break;
								case 'link':
								default:
									echo '<a href="' . $custom_action->link . '" target="_blank" class="edit-button-user em-pointer">' . JText::_($custom_action->title) . '</a>';
									break;
							}
							?>
                        </li>
						<?php
					}
				}
			} ?>

            <hr style="width: 100%">

			<?php if ($show_logout == '1') : ?>
				<?= '<li><a class="logout-button-user em-flex-important em-flex-row em-flex-center" href="' . JURI::base() . 'index.php?option=com_users&task=user.logout&' . JSession::getFormToken() . '=1"><span class="material-icons-outlined mr-2">logout</span>' . JText::_('COM_EMUNDUS_USER_MENU_LOGOUT_ACTION') . '</a></li>'; ?>
			<?php endif; ?>

        </ul>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('profile_chzn') != null) {
                document.getElementById('profile_chzn').style.display = 'none';
                document.getElementById('profile').style.display = 'block';
                document.querySelector('#header-c .g-content').style.alignItems = 'start';
            }

            let elmnt2 = document.getElementById("g-top");
            if (elmnt2 !== null) {
                let hauteurTotaleElem = elmnt2.offsetHeight;
                document.getElementById("g-navigation").style.top = hauteurTotaleElem + 'px';
            }
        });

        function displayUserOptions() {
            var dropdown = document.getElementById('userDropdown');
            var icon = document.getElementById('userDropdownIcon');

            // get message module elements
            var messageDropdown = document.getElementById('messageDropdown');
            var messageIcon = document.getElementById('messageDropdownIcon');

            if (dropdown.classList.contains('open')) {
                jQuery("#userDropdownMenu").css("transform", "translate(300px)")
                setTimeout(() => {
                    dropdown.classList.remove('open');
                    jQuery("#userDropdownMenu").css("transform", "unset")
                    if (icon !== null) {
                        icon.classList.remove('active');
                    }
                }, 300);
            } else {
                // remove message classes if message module is on page
                if (messageDropdown || messageIcon) {
                    messageDropdown.classList.remove('open');
                    messageIcon.classList.remove('active');
                    messageIcon.classList.remove('open');
                }
                dropdown.classList.add('open');
                if (icon !== null) {
                    icon.classList.add('open');
                }
            }
        }

        // This counters all of the issues linked to using BootstrapJS.
        document.getElementById('userDropdownLabel').addEventListener('click', function (e) {
            e.stopPropagation();
            displayUserOptions();
        });

        function postCProfile() {
            var current_fnum = document.getElementById("profile").value;
            var redirect_url = document.getElementById("switch_profile_redirect").value;

            var url = window.location.origin.toString() + '/' + redirect_url;

            var formData = new FormData();
            formData.append('profnum', current_fnum);

            fetch('/index.php?option=com_emundus&task=switchprofile', {
                method: 'POST',
                body: formData,
            }).then((response) => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
            }).then((result) => {
                if (result.status) {
                    window.location.href = url;
                }
            });
        }

        document.addEventListener('click', function (e) {
            let clickInsideModule = false;

            e.composedPath().forEach((pathElement) => {
                if (pathElement.id == "userDropdownMenu") {
                    clickInsideModule = true;
                }
            });

            if (!clickInsideModule) {
                const dropdown = document.getElementById('userDropdown');
                const icon = document.getElementById('userDropdownIcon');

                jQuery("#userDropdownMenu").css("transform", "translate(250px)")
                setTimeout(() => {
                    dropdown.classList.remove('open');
                    jQuery("#userDropdownMenu").css("transform", "unset")
                    if (icon !== null) {
                        icon.classList.remove('active');
                    }
                }, 300);
            }
        });

        function manageHeight() {
            let elmnt = document.getElementById("g-navigation");
            let elmnt2 = document.getElementById("g-top");
            if (elmnt2 !== null) {
                let hauteurTotaleElem = elmnt.offsetHeight + elmnt2.offsetHeight;
                jQuery("#userDropdownMenu").css("top", hauteurTotaleElem + 'px');
            } else {
                let hauteurTotaleElem = elmnt.offsetHeight;
                jQuery("#userDropdownMenu").css("top", hauteurTotaleElem + 'px');
            }

        }

        function copyTokenToClipBoard() {
            const tokenInput = document.querySelector('input[name="anonym_token"]');
            tokenInput.select();
            tokenInput.setSelectionRange(0, 99999); // For mobile devices

            // Copy the text inside the text field
            navigator.clipboard.writeText(tokenInput.value);
        }
    </script>
<?php } else { ?>
	<?php if ($display_svg == 1) : ?>
        <iframe id="background-shapes" alt="<?= JText::_('COM_EMUNDUS_USERDROPDOWN_IFRAME') ?>"></iframe>
	<?php endif; ?>
    <div class="header-right" style="text-align: right;">
		<?php if ($show_registration) { ?>
            <a class="btn btn-danger" href="<?= JRoute::_($link_register); ?>"
               data-toggle="sc-modal"><?= JText::_('CREATE_ACCOUNT_LABEL'); ?></a>
		<?php } ?>
        <a class="btn btn-danger btn-creer-compte" href="<?= JRoute::_($link_login); ?>"
           data-toggle="sc-modal"><?= JText::_('CONNEXION_LABEL'); ?></a>
    </div>
    <script>
		<?php if ($guest): ?>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelector('#g-navigation .g-container').style.padding = '16px 12px';
        });
		<?php endif; ?>
    </script>
<?php }
?>

<div class="em-page-loader" style="display: none"></div>