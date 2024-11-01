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

require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'settings.php');
$m_settings = new EmundusModelsettings();

$favicon = $m_settings->getFavicon();

$new_account = Factory::getApplication()->getSession()->get('new_account', 0);

$title = $this->escape($this->params->get('page_heading'));
if($new_account == 1) {
	$title = Text::_('COM_USERS_ACCOUNT_CREATION_PASSWORD');
}

Factory::getApplication()->getSession()->clear('new_account');

?>
<div class="reset-complete<?php echo $this->pageclass_sfx; ?>">
	<?php if ($this->params->get('show_page_heading')) : ?>
		<div class="page-header">
            <?php if (file_exists($favicon)) : ?>
                <a href="index.php" alt="Logo" class="em-profile-picture tw-mb-8" style="width: 50px;height: 50px;background-image: url(<?php echo $favicon ?>)">
                </a>
            <?php endif; ?>
            <h3 class="tw-mb-4">
                <?php echo $title; ?>
            </h3>
		</div>
	<?php endif; ?>
	<form action="<?php echo JRoute::_('index.php?option=com_users&task=reset.complete'); ?>" method="post" class="form-validate form-horizontal well">
		<?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
			<fieldset>
				<?php if (isset($fieldset->label) && $new_account != 1) : ?>
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
    document.addEventListener("DOMContentLoaded", function() {
        var password_rules = document.getElementById('jform[password1]-rules');
        if(password_rules) {
            var new_location = document.querySelector(".password-group");
            new_location.appendChild(password_rules);
        }
        var password = document.getElementById('jform_password1');
        password.addEventListener('input', function() {
            checkPasswordSymbols(password);
        });

        <?php if($new_account == 1) : ?>
        document.title = <?php echo json_encode($title); ?>;
        <?php endif; ?>
    });

    function checkPasswordSymbols(element) {
        var wrong_password_title = ['Invalid password', 'Mot de passe invalide'];
        var wrong_password_description = ['The #$\{\};<> characters are forbidden, as are spaces.', 'Les caractères #$\{\};<> sont interdits ainsi que les espaces'];

        var site_url = window.location.toString();
        var site_url_lang_regexp = /\w+.\/en/d;

        var index = 0;

        if(site_url.match(site_url_lang_regexp) === null) { index = 1; }

        var regex = /[#$\{\};<> ]/;
        var password_value = element.value;

        if (password_value.match(regex) != null) {
            Swal.fire({
                icon: 'error',
                title: wrong_password_title[index],
                text: wrong_password_description[index],
                reverseButtons: true,
                customClass: {
                    title: 'em-swal-title',
                    confirmButton: 'em-swal-confirm-button',
                    actions: 'em-swal-single-action',
                }
            });

            element.value = '';
        }
    }
</script>
