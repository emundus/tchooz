<?php
/**
 * Repeat group add button for table format
 */

defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

$d = $displayData;
?>

<a class="addGroup em-flex-row em-pointer"  href="#">
    <span class="material-symbols-outlined em-mr-8">add</span>
    <p><?php echo Text::_('COM_FABRIK_ADD_GROUP');?></p>
</a>