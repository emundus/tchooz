<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');

$document = JFactory::getDocument();
$document->addStyleSheet("templates/g5_helium/html/com_users/reset/style/com_users_reset.css");

?>
<div class="reset-confirm<?php echo $this->pageclass_sfx; ?>">

		<div class="page-header">
            <?php if (file_exists('images/custom/favicon.png')) : ?>
                <a href="/" class="em-profile-picture em-mb-32" style="width: 50px;height: 50px;background-image: url('images/custom/favicon.png')">
                </a>
            <?php endif; ?>
            <p class="em-mb-8 em-h3">
                <?php echo $this->escape($this->params->get('page_heading')); ?>
            </p>
		</div>

        <form action="<?php echo JRoute::_('index.php?option=com_users&task=reset.confirm'); ?>" method="post" class="form-validate form-horizontal well">
            <?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
                <fieldset>
                    <?php if (isset($fieldset->label)) : ?>
                        <p><?php echo JText::_($fieldset->label); ?></p>
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
