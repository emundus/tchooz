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
?>
<iframe id="background-shapes2" class="background-shaped-top" src="/modules/mod_emundus_campaign/assets/fond-clair.svg" alt="<?= JText::_('MOD_EM_FORM_IFRAME') ?>"></iframe>
<iframe id="background-shapes2" class="background-shaped-bottom" src="/modules/mod_emundus_campaign/assets/fond-clair.svg" alt="<?= JText::_('MOD_EM_FORM_IFRAME') ?>"></iframe>
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

	<?php if ($usersConfig->get('allowUserRegistration')) : ?>
        <div>
			<?php echo JText::_('COM_USERS_LOGIN_NO_ACCOUNT'); ?>
            <a class="em-text-underline" href="<?php echo Route::_($this->registrationLink); ?>">
				<?php echo Text::_('COM_USERS_LOGIN_REGISTER'); ?>
            </a>
        </div>
	<?php endif; ?>
</div>
<script>


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

</script>