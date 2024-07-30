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

if (!empty($this->campaign)) {
	$session->set('login_campaign_id', $this->campaign);
}
else {
	$session->clear('login_campaign_id');
}

$displayRegistration = true;
$user_module = ModuleHelper::getModule('mod_emundus_user_dropdown');
if($user_module->id) {
    $params = json_decode($user_module->params);
    if($params->show_registration == 2) {
        $displayRegistration = false;
    }
}
?>
<div class="com-users-login login">
	<?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header tw-flex tw-flex-col tw-items-center">
	        <?php if (file_exists($this->favicon)) : ?>
                <a href="index.php" alt="Logo" class="em-profile-picture tw-mb-8" style="width: 50px;height: 50px;background-image: url(<?php echo $this->favicon ?>)">
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

    <?php if(!empty($this->oauth2Config)) : ?>
        <div class="tw-mt-8 tw-w-full tw-flex tw-flex-col tw-items-center">
            <div class="tw-w-full tw-flex tw-flex-col tw-items-center tw-gap-4">
                <?php foreach($this->oauth2Config->configurations as $configuration) : ?>
                    <?php if ($configuration->display_on_login == 1) : ?>
                    <a class="tw-w-full tw-flex tw-items-center tw-justify-center tw-border tw-py-3 tw-px-2 tw-rounded-applicant tw-border-profile-full tw-text-profile-full tw-gap-4 hover:tw-bg-profile-full hover:tw-text-white"
                       href="<?php echo $configuration->auth_url; ?>?response_type=code&client_id=<?php echo $configuration->client_id; ?>&scope=<?php echo str_replace(',','+',$configuration->scopes); ?>&redirect_uri=<?php echo $configuration->redirect_url ?>&state=<?php echo $this->state; ?>&nonce=<?php echo $this->nonce; ?>&type=<?php echo $configuration->type; ?>">
                        <?php if ($configuration->button_type == 'google') : ?>
                        <div class="tw-w-[20px] tw-h-[20px]">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                                <path fill="none" d="M0 0h48v48H0z"></path>
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

        <fieldset>
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
        </fieldset>
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
    document.addEventListener("DOMContentLoaded", function() {
        let username_field = document.querySelector('#username');
        if(username_field) {
            username_field.setAttribute('placeholder', '<?php echo JText::_('COM_USERS_LOGIN_EMAIL_PLACEHOLDER'); ?>');
            username_field.setAttribute('aria-describedby', 'alert-message-text');
            username_field.setAttribute('autocomplete', 'email');
            username_field.focus();
        }
        let password_field = document.querySelector('#password');
        if(password_field) {
            password_field.setAttribute('aria-describedby', 'alert-message-text');
            password_field.setAttribute('autocomplete', 'current-password');
        }
    });

    /* Modification de la couleur du background avec les formes */
    let emProfileColor1 = getComputedStyle(document.documentElement).getPropertyValue('--em-profile-color');
    let iframeElements = document.querySelectorAll("#background-shapes2");

    if(iframeElements !== null) {
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