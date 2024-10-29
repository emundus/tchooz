<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');

$document = Factory::getApplication()->getDocument();
$document->addStyleSheet("templates/g5_helium/html/com_users/reset/style/com_users_reset.css");
$username    = Factory::getApplication()->input->getString('username');
$new_account = Factory::getApplication()->input->getInt('new_account', 0);
$session     = Factory::getSession();
$session->set('new_account', $new_account);

$this->form->setValue('username', '', $username);

require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'settings.php');
$m_settings = new EmundusModelsettings();

$favicon = $m_settings->getFavicon();

$title = $this->escape($this->params->get('page_heading'));
if ($new_account == 1)
{
	$title = Text::_('COM_USERS_ACCOUNT_CREATION_PASSWORD');
}

?>
<div class="reset-confirm<?php echo $this->pageclass_sfx; ?>">

    <div class="page-header">
		<?php if (file_exists($favicon)) : ?>
            <a href="index.php" alt="Logo" class="em-profile-picture tw-mb-4"
               style="width: 50px;height: 50px;background-image: url(<?php echo $favicon ?>)">
            </a>
		<?php endif; ?>
        <h1 class="em-mb-8">
			<?php echo $title; ?>
        </h1>
    </div>

    <form id="confirm_reset_password" action="<?php echo JRoute::_('index.php?option=com_users&task=reset.confirm'); ?>"
          method="post" class="form-validate form-horizontal well">
		<?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
            <fieldset>
				<?php if (isset($fieldset->label)) : ?>
                    <p class="mb-4"><?php echo JText::_($fieldset->label); ?></p>
				<?php endif; ?>
				<?php echo $this->form->renderFieldset($fieldset->name); ?>
            </fieldset>
		<?php endforeach; ?>
        <div class="control-group">
            <div class="controls">
                <button type="submit" class="btn btn-primary validate">
					<?php echo JText::_('JSUBMIT'); ?>
                </button>
            </div>
        </div>
		<?php echo JHtml::_('form.token'); ?>
    </form>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var email_input = document.getElementById('jform_username');
        var token = document.getElementById('jform_token');

        if (email_input.value !== '' && token.value !== '') {
            document.getElementById('confirm_reset_password').submit();
        }
    });
</script>

