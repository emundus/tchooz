<?php
/**
 * Read-only element form layout.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.emundusreadonly
 * @copyright   (C) 2008-present eMundus
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die('Restricted access');

$d = $displayData;
?>
<div id="<?= $d->id; ?>" class="fabrikinput fabrikElementReadOnly tw-text-gray-800">
	<?= $d->value !== '' ? $d->value : ($d->placeholder ?? ''); ?>
</div>
<input type="hidden" name="<?= $d->name; ?>" value="<?= $d->value; ?>" />
