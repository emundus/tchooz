<?php
/**
 * Layout: list row buttons - rendered as a Bootstrap dropdown
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
$d = $displayData;
$icon = '<span class="fa fa-edit"></span>';
if ($d->list_edit_link_icon !== 'edit') {
	$icon = FabrikHelperHTML::image($d->list_edit_link_icon, 'list', '', array('alt' => $d->editLabel));
}
?>
<a data-loadmethod="<?php echo $d->loadMethod; ?>" 
    class="<?php echo $d->class;?> btn-default" <?php echo $d->editAttributes;?>
    data-isajax="<?php echo $d->isAjax; ?>"
    data-list="<?php echo $d->dataList;?>" 
    data-rowid="<?php echo $d->rowId; ?>"
    data-iscustom="<?php if ($d->isCustom) echo '1'; else echo '0'; ?>"
    href="<?php echo $d->editLink;?>" 
    title="<?php echo $d->editLabel;?>">
	<?php echo $icon.$d->editText; ?></a>