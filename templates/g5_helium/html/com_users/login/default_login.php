<?php

/**
 * @package         Joomla.Site
 * @subpackage      com_users
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Users\Site\View\Login\HtmlView $cookieLogin */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');
$wa->registerAndUseStyle('com_users.login', 'templates/g5_helium/html/com_users/login/style/com_users_login.css');

$usersConfig = ComponentHelper::getParams('com_users');
$eMConfig    = ComponentHelper::getParams('com_emundus');

$session = JFactory::getApplication()->getSession();

if (!empty($this->campaign))
{
	$session->set('login_campaign_id', $this->campaign);
}
else
{
	$session->clear('login_campaign_id');
}

$displayRegistration = true;
$user_module         = ModuleHelper::getModule('mod_emundus_user_dropdown');
if ($user_module->id)
{
	$params = json_decode($user_module->params);
	if ($params->show_registration == 2)
	{
		$displayRegistration = false;
	}
}
?>
<div class="com-users-login login">
	<?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header tw-flex tw-flex-col tw-items-center">
			<?php if (file_exists($this->favicon)) : ?>
                <a href="index.php" alt="Logo" class="em-profile-picture tw-mb-8"
                   style="width: 50px;height: 50px;background-image: url(<?php echo $this->favicon ?>)">
                    <span class="sr-only">Logo</span>
                </a>
			<?php endif; ?>
            <h1 class="tw-mb-4">
				<?php echo Text::_('JLOGIN'); ?>
            </h1>
            <p class="em-applicant-text-color em-applicant-default-font"><?php echo Text::_('JLOGIN_DESC'); ?></p>
        </div>
	<?php endif; ?>

	<?php if (($this->params->get('logindescription_show') == 1 && str_replace(' ', '', $this->params->get('login_description', '')) != '') || $this->params->get('login_image') != '') : ?>
    <div class="com-users-login__description login-description">
		<?php endif; ?>

		<?php if ($this->params->get('logindescription_show') == 1) : ?>
			<?php echo $this->params->get('login_description'); ?>
		<?php endif; ?>

		<?php if ($this->params->get('login_image') != '') : ?>
			<?php echo HTMLHelper::_('image', $this->params->get('login_image'), empty($this->params->get('login_image_alt')) && empty($this->params->get('login_image_alt_empty')) ? false : $this->params->get('login_image_alt'), ['class' => 'com-users-login__image login-image']); ?>
		<?php endif; ?>

		<?php if (($this->params->get('logindescription_show') == 1 && str_replace(' ', '', $this->params->get('login_description', '')) != '') || $this->params->get('login_image') != '') : ?>
    </div>
    <?php endif; ?>

	<?php if (!empty($this->oauth2Directories)) : ?>
        <div class="tw-mt-8 tw-w-full tw-flex tw-flex-col tw-items-center">
            <div class="tw-w-full tw-flex tw-flex-col tw-items-center tw-gap-4">
				<?php foreach ($this->oauth2Directories as $configuration) : ?>
					<?php if (!empty($configuration->client_id)) : ?>
                        <a class="tw-w-full tw-flex tw-items-center tw-justify-center tw-border tw-py-3 tw-px-2 tw-rounded-applicant tw-border-profile-full tw-text-profile-full tw-gap-4 hover:tw-bg-profile-full hover:tw-text-white"
                           href="<?php echo $configuration->auth_url; ?>?response_type=code&client_id=<?php echo $configuration->client_id; ?>&scope=<?php echo str_replace(',', '+', $configuration->scopes); ?>&redirect_uri=<?php echo $configuration->redirect_url ?>&state=<?php echo $this->state; ?>&nonce=<?php echo $this->nonce; ?>&type=<?php echo $configuration->type; ?>&kc_idp_hint=<?php echo $configuration->type; ?>">
							<?php if ($configuration->button_type == 'google') : ?>
                                <div class="tw-w-[20px] tw-h-[20px]">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"
                                         xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
                                        <path fill="#EA4335"
                                              d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                                        <path fill="#4285F4"
                                              d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                                        <path fill="#FBBC05"
                                              d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                                        <path fill="#34A853"
                                              d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                                        <path fill="none" d="M0 0h48v48H0z"></path>
                                    </svg>
                                </div>
							<?php elseif ($configuration->button_type == 'microsoft') : ?>
                                <div style="width: 20px;height: 20px; display: flex">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23">
                                        <path fill="#f3f3f3" d="M0 0h23v23H0z"/>
                                        <path fill="#f35325" d="M1 1h10v10H1z"/>
                                        <path fill="#81bc06" d="M12 1h10v10H12z"/>
                                        <path fill="#05a6f0" d="M1 12h10v10H1z"/>
                                        <path fill="#ffba08" d="M12 12h10v10H12z"/>
                                        <script xmlns=""/>
                                    </svg>
                                </div>
							<?php elseif ($configuration->button_type == 'emundus') : ?>
                                <div style="width: 20px;height: 20px; display: flex">
                                    <svg version="1.1" id="Calque_1" xmlns="http://www.w3.org/2000/svg"
                                         xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                         viewBox="0 0 980 1020" style="enable-background:new 0 0 980 1020;"
                                         xml:space="preserve">
<style type="text/css">
    .st0 {
        fill: url(#SVGID_1_);
    }

    .st1 {
        fill: url(#SVGID_2_);
    }

    .st2 {
        fill: #1B1F3C;
    }

    .st3 {
        fill: #3B75B8;
    }

    .st4 {
        fill: #DE6339;
    }
</style>
                                        <linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="582.7164"
                                                        y1="791.5424"
                                                        x2="420.2053" y2="639.3168">
                                            <stop offset="0" style="stop-color:#1B1F3C"/>
                                            <stop offset="1" style="stop-color:#0F1326"/>
                                        </linearGradient>
                                        <path class="st0" d="M835,664.04c-39.09-39.08-102.46-39.08-141.54,0c-47.26,47.26-110.09,73.29-176.93,73.29
	c-61.23,0-117.38-22.13-160.91-58.79l0,0c-0.79-0.66-1.58-1.32-2.36-2c-0.93-2.18-3.32-4.33-4.28-4.1
	c-9.15,2.24-11.87,2.9-12.48,3.05c-28.7,6.74-52.92,22.71-70.03,46.18c-14.66,20.1-23.06,44.76-23.06,67.63
	c0,23.92,9.08,54.34,32.73,78.94c11.64,7.75,24.8,15.23,39.68,22.29c-3.31-1.57-6.47-3.26-9.52-5.03c0,0,0,0,0,0
	c2.3,1.22,4.63,2.4,6.96,3.58c0.87,0.44,1.75,0.89,2.62,1.32c0,0,0,0,0,0l0,0c8.3,4.13,16.74,8.03,25.32,11.66
	c55.59,23.51,114.58,35.43,175.34,35.43c60.76,0,119.75-11.92,175.34-35.43c53.64-22.69,101.8-55.15,143.13-96.48
	C874.08,766.5,874.08,703.13,835,664.04z"/>
                                        <linearGradient id="SVGID_2_" gradientUnits="userSpaceOnUse" x1="416.9879"
                                                        y1="109.5545"
                                                        x2="481.8727" y2="376.1081">
                                            <stop offset="0" style="stop-color:#1B1F3C"/>
                                            <stop offset="1" style="stop-color:#0F1326"/>
                                        </linearGradient>
                                        <path class="st1" d="M758.38,109.05c0.07,0.05,0.14,0.11,0.2,0.16c-0.36-0.28-0.72-0.57-1.08-0.85
	C685.81,62.63,602.3,37.5,516.34,37.5c-60.76,0-119.75,11.92-175.34,35.43c-53.64,22.69-101.8,55.15-143.13,96.48
	c-31.34,31.34-57.56,66.62-78.31,105.23c0.04-0.09,0.09-0.18,0.13-0.27c-7.49,14.54-13.53,28.47-18.32,41.75
	c-3.21,11.64-0.16,30.66,0.47,38.1c2.73,32.33,13.89,62.18,31.42,84.07c16.97,21.18,46.15,43.17,94.21,43.17
	c11.74,0,24.63-1.33,38.72-4.29c0.07-1.73,0.17-3.45,0.27-5.17c-0.65,0.14-1.29,0.27-1.94,0.4c0.71-0.15,1.42-0.29,2.13-0.44
	c8.24-130.58,117.07-234.29,249.69-234.29c89.27,0,169.27,47.68,213.62,120.01c-0.05,0.02-0.1,0.05-0.15,0.07
	c0.88,1.43,1.74,2.87,2.59,4.33c62.1-29.39,85.96-99.4,85.96-141.68C818.36,188.3,795.17,134.29,758.38,109.05z"/>
                                        <path class="st2" d="M932.07,316.11c-35.54-92.84-101.28-162.7-180.38-211.46c31.81,21.52,61.66,63.26,61.66,115.74
	c0,45.85-29.59,126.94-108.49,146.13C625,385.95,272.94,470.51,272.28,470.67c-156.35,38.02-188.98-122.64-152.6-196.3
	c-26.7,51.8-34.82,95.86-34.82,130.05c0,105.54,89.48,169.51,174.12,148.46c40.64-10.11,407.57-95.15,474.57-111.45
	c98.52-23.96,127.98-81.18,131.31-141.48c29.78,53.43,38.69,160.34-102.89,194.77C674.04,516.11,305.9,608.67,298.4,610.49
	c-57.35,13.84-106.77,58.89-106.77,121.14c0,40.73,20.93,109.91,124.18,158.9c-47.35-22.47-67.41-66.3-67.41-101.23
	c0-41.34,29.2-94.85,89.24-108.95c9.16-2.15,379.17-93.22,451.49-110.33C910.39,541.36,972.24,421.05,932.07,316.11z"/>
                                        <circle class="st3" cx="85.81" cy="143.07" r="50.09"/>
                                        <circle class="st4" cx="291.62" cy="130.07" r="90"/>
                                        <path class="st3" d="M688.89,306.95c24.19-11.02,41.02-35.4,41.02-63.72c0-38.66-31.34-70-70-70c-38.66,0-70,31.34-70,70
	c0,1.9,0.1,3.78,0.25,5.65C627.38,260.45,661.12,280.53,688.89,306.95z"/>
                                        <path class="st4" d="M491.55,736.08c-0.87,5.02-1.33,10.19-1.33,15.46c0,49.71,40.29,90,90,90s90-40.29,90-90
	c0-20.3-6.72-39.02-18.06-54.08c-40.06,25.95-86.78,39.88-135.64,39.88C508.1,737.33,499.77,736.89,491.55,736.08z"/>
                                        <circle class="st3" cx="787.79" cy="937.5" r="45"/>
</svg>
                                </div>
							<?php elseif ($configuration->button_type == 'custom' && !empty($configuration->button_icon)) : ?>
                                <div class="tw-w-[20px] tw-h-[20px]">
									<?php echo HTMLHelper::_('image', $configuration->button_icon, '', ['class' => 'tw-w-full tw-h-full']); ?>
                                </div>
							<?php endif; ?>
							<?php echo Text::_($configuration->button_label); ?>
                        </a>
					<?php endif; ?>
				<?php endforeach; ?>
            </div>
            <!-- SEPARATOR -->
            <div class="tw-mt-6 tw-flex tw-items-center tw-justify-center tw-gap-2 tw-w-full">
                <hr class="tw-w-full">
                <span class="tw-text-neutral-500"><?php echo Text::_('OU'); ?></span>
                <hr class="tw-w-full">
            </div>
        </div>
	<?php endif; ?>


    <form action="<?php echo (!empty($this->redirect)) ? 'index.php?option=com_users&task=user.login&redirect=' . $this->redirect : 'index.php?option=com_users&task=user.login'; ?>"
          method="post" class="form-validate form-horizontal well" id="com-users-login__form">

			<?php echo $this->form->renderFieldset('credentials', ['class' => 'com-users-login__input']); ?>

            <div class="tw-full tw-flex tw-items-center tw-justify-end">
				<?php if (PluginHelper::isEnabled('system', 'remember')) : ?>
                    <div class="control-group">
                        <div class="control-label">
                            <label for="remember">
								<?php echo JText::_('COM_USERS_LOGIN_REMEMBER_ME'); ?>
                            </label>
                        </div>
                        <div class="controls">
                            <input id="remember" type="checkbox" name="remember" class="inputbox" value="yes"/>
                        </div>
                    </div>
				<?php endif; ?>

				<?php if ($this->displayForgotten) : ?>
                    <div class="control-group tw-float-right">
                        <div class="control-label">
                            <a class="em-text-underline" href="<?php echo JRoute::_($this->forgottenLink); ?>">
								<?php echo JText::_('COM_USERS_LOGIN_RESET'); ?>
                            </a>
                        </div>
                    </div>
				<?php endif; ?>
            </div>

			<?php foreach ($this->extraButtons as $button) :
				$dataAttributeKeys = array_filter(array_keys($button), function ($key) {
					return substr($key, 0, 5) == 'data-';
				});
				?>
                <div class="com-users-login__submit control-group">
                    <div class="controls">
                        <button type="button"
                                class="btn btn-secondary w-100 <?php echo $button['class'] ?? '' ?>"
						<?php foreach ($dataAttributeKeys as $key) : ?>
							<?php echo $key ?>="<?php echo $button[$key] ?>"
						<?php endforeach; ?>
						<?php if ($button['onclick']) : ?>
                            onclick="<?php echo $button['onclick'] ?>"
						<?php endif; ?>
                        title="<?php echo Text::_($button['label']) ?>"
                        id="<?php echo $button['id'] ?>"
                        >
						<?php if (!empty($button['icon'])) : ?>
                            <span class="<?php echo $button['icon'] ?>"></span>
						<?php elseif (!empty($button['image'])) : ?>
							<?php echo HTMLHelper::_('image', $button['image'], Text::_($button['tooltip'] ?? ''), [
								'class' => 'icon',
							], true) ?>
						<?php elseif (!empty($button['svg'])) : ?>
							<?php echo $button['svg']; ?>
						<?php endif; ?>
						<?php echo Text::_($button['label']) ?>
                        </button>
                    </div>
                </div>
			<?php endforeach; ?>

            <div class="com-users-login__submit control-group">
                <div class="controls">
                    <button type="submit" class="btn btn-primary">
						<?php echo Text::_('JLOGIN'); ?>
                    </button>
                </div>
            </div>

			<?php $return = $this->form->getValue('return', '', $this->params->get('login_redirect_url', $this->params->get('login_redirect_menuitem', ''))); ?>
            <input type="hidden" name="return" value="<?php echo base64_encode($return); ?>">
			<?php echo HTMLHelper::_('form.token'); ?>
    </form>

	<?php if ($usersConfig->get('allowUserRegistration') && $displayRegistration) : ?>
        <div>
			<?php echo JText::_('COM_USERS_LOGIN_NO_ACCOUNT'); ?>
            <a class="em-text-underline" href="<?php echo Route::_($this->registrationLink); ?>">
				<?php echo Text::_('COM_USERS_LOGIN_REGISTER'); ?>
            </a>
        </div>
	<?php endif; ?>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        let username_field = document.querySelector('#username');
        if (username_field) {
            username_field.setAttribute('placeholder', '<?php echo JText::_('COM_USERS_LOGIN_EMAIL_PLACEHOLDER'); ?>');
            username_field.setAttribute('aria-describedby', 'alert-message-text');
            username_field.setAttribute('autocomplete', 'email');
            username_field.focus();
        }
        let password_field = document.querySelector('#password');
        if (password_field) {
            password_field.setAttribute('aria-describedby', 'alert-message-text');
            password_field.setAttribute('autocomplete', 'current-password');
        }
    });

    /* Modification de la couleur du background avec les formes */
    let emProfileColor1 = getComputedStyle(document.documentElement).getPropertyValue('--em-profile-color');
    let iframeElements = document.querySelectorAll("#background-shapes2");

    if (iframeElements !== null) {
        iframeElements.forEach((iframeElement) => {
            iframeElement.addEventListener("load", function () {

                let iframeDocument = iframeElement.contentDocument || iframeElement.contentWindow.document;
                let pathElements = iframeDocument.querySelectorAll("path");

                let styleElement = iframeDocument.querySelector("style");

                if (styleElement) {
                    let styleContent = styleElement.textContent;
                    styleContent = styleContent.replace(/fill:#[0-9A-Fa-f]{6};/, "fill:" + emProfileColor1 + ";");
                    styleElement.textContent = styleContent;
                }

                if (pathElements) {
                    pathElements.forEach((pathElement) => {
                        let pathStyle = pathElement.getAttribute("style");
                        if (pathStyle && pathStyle.includes("fill:grey;")) {
                            pathStyle = pathStyle.replace(/fill:grey;/, "fill:" + emProfileColor1 + ";");
                            pathElement.setAttribute("style", pathStyle);
                        }
                    });
                }
            });
        });
    }

    let displayTchoozy = getComputedStyle(document.documentElement).getPropertyValue('--display-corner-bottom-left-background');
    let displayTchoozy2 = getComputedStyle(document.documentElement).getPropertyValue('--display-corner-top-right-background');
    if (displayTchoozy == 'none' || displayTchoozy2 == 'none') {
        document.querySelector(".background-shaped-top").style.display = 'none';
        document.querySelector(".background-shaped-bottom").style.display = 'none';
    }

    let conteneur = document.querySelector("#g-page-surround");
    let divShapesLeft = document.createElement("div");
    let divShapesRight = document.createElement("div");
    divShapesLeft.id = "login-background-shapes-left";
    divShapesRight.id = "login-background-shapes-right";

    let firstDiv = conteneur.firstChild;

    conteneur.insertBefore(divShapesLeft, firstDiv);
    conteneur.insertBefore(divShapesRight, firstDiv);

</script>