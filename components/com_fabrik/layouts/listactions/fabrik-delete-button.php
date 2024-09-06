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

use Joomla\CMS\Language\Text;
$d = $displayData;
$icon = '<span class="fa fa-trash"></span>';
if ($d->list_delete_icon !== 'delete') {
	$icon = FabrikHelperHTML::image($d->list_delete_icon, 'list', '', array('alt' => $d->label));
}
?>
<a href="#" class="<?php echo $d->btnClass;?>delete" data-listRef="list_<?php echo $d->renderContext;?>"
	title="<?php echo $d->label; ?>">
	<?php echo $icon.$d->text;?></a>