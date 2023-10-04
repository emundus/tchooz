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
<div class="com-users-login login">
	<?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header flex flex-column items-center">
			<?php if (file_exists('images/custom/favicon.png')) : ?>
                <a href="index.php" alt="Logo" class="em-profile-picture mb-8"
                   style="width: 50px;height: 50px;background-image: url('images/custom/favicon.png')">
                </a>
			<?php endif; ?>
            <h1 class="em-mb-8">
				<?php echo JText::_('JLOGIN'); ?>
            </h1>
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

            <div class="em-w-100 em-flex-row em-flex-end">
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
                    <div class="control-group em-float-right">
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
