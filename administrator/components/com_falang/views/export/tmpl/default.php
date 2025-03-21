<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2021. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

//need for Joomla.submitbutton
HTMLHelper::_('behavior.core');


$params = ComponentHelper::getParams( 'com_falang' );
/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('form.validate');

?>
<script type="text/javascript">
    Joomla.submitbutton = function(task) {
        if (task == 'export.cancel' || document.formvalidator.isValid(document.getElementById('upload-form')) ) {
            Joomla.submitform(task, document.getElementById('upload-form'));
        } else {
            alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
        }
    };
</script>

<div id="j-main-container">
    <form action="<?php Route::_('index.php?option=com_falang'); ?>" method="post" name="adminForm" id="upload-form" class="form-validate"  enctype="multipart/form-data">
        <ul class="unstyled">

            <li><?php echo Text::_('COM_FALANG_EXPORT_SRC_LANGUAGE_LBL').' : '.$this->sourceLanguages;?></li>

            <?php
            $fields = $this->form->getFieldset();
            foreach($fields as $field) {
                echo '<li>'.$field->label.$field->input.'</li>';
            }

            ?>
        </ul>
        <button type="submit" class="btn btn-small btn-success" onclick="Joomla.submitbutton('export.process')"><?php echo Text::_('COM_FALANG_EXPORT_BTN_PROCESS');?></button>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>


