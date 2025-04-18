<?php
/**
 * Admin Crons List Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

require_once JPATH_COMPONENT . '/helpers/adminhtml.php';
HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
//HTMLHelper::_('script', 'system/multiselect.js', false, true);
//HTMLHelper::_('script','system/multiselect.js', ['relative' => true]);
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user = Factory::getUser();
$userId = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$alts = array('JPUBLISHED', 'JUNPUBLISHED', 'COM_FABRIK_ERR_CRON_RUN_TIME');
$imgs = array('publish_x.png', 'tick.png', 'publish_y.png');
$tasks = array('publish', 'unpublish', 'publish');

?>
<form action="<?= Route::_('index.php?option=com_fabrik&view=crons'); ?>" method="post" name="adminForm" id="adminForm">
<div class="row">
<div class="col-sm-12">
	<div id="j-main-container" class="j-main-container">
		<?= LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-info">
				<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?= Text::_('INFO'); ?></span>
				<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
		 <div class="table-responsive">
	<table class="table table-striped">
		<thead>
			<tr>
				<td class="w-1 text-center">
					<?= HTMLHelper::_('grid.checkall'); ?>
				</td>
				<th scope="col" class="w-1 text-center d-none d-md-table-cell">
                    <?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'c.id', $listDirn, $listOrder); ?>
				</th>
                <th scope="col">
                    <?= HTMLHelper::_('searchtools.sort', 'COM_FABRIK_LABEL', 'c.label', $listDirn, $listOrder); ?>
				</th>
                <th scope="col">
                    <?= HTMLHelper::_('searchtools.sort', 'COM_FABRIK_CRON_FREQUENCY', 'c.frequency', $listDirn, $listOrder); ?>
				</th>
                <th scope="col">
                    <?= HTMLHelper::_('searchtools.sort', 'COM_FABRIK_CRON_FIELD_LAST_RUN_LABEL', 'c.lastrun', $listDirn, $listOrder); ?>
				</th>
				<th scope="col" class="w-3 d-none d-md-table-cell">
                    <?= HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'c.published', $listDirn, $listOrder); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="6">
					<?= $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) :
	$ordering = ($listOrder == 'ordering');
	$link = Route::_('index.php?option=com_fabrik&task=cron.edit&id=' . (int) $item->id);
	$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
	$canChange = true;
			   ?>

			<tr class="row<?= $i % 2; ?>">
					<td><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
					<td>
						<?= $item->id; ?>
					</td>
					<td>
						<?php if ($item->checked_out) : ?>
							<?= HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'crons.', $canCheckin); ?>
						<?php endif; ?>
						<?php
						if ($item->checked_out && ($item->checked_out != $user->get('id'))) :
							echo $item->label;
						else:
						?>
						<a href="<?= $link; ?>">
							<?= $item->label; ?>
						</a>
					<?php endif; ?>
					</td>
					<td>
						<?= $item->frequency .' '. $item->unit; ?>
					</td>
					<td>
						<?= HTMLHelper::_('date', $item->lastrun, 'Y-m-d H:i:s'); ?>
					</td>
					<td class="text-center">
						<?= HTMLHelper::_('jgrid.published', $item->published, $i, 'crons.', $canChange);?>
					</td>
				</tr>

			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
	<?php endif; ?>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?= HTMLHelper::_('form.token'); ?>
	</div>
</form>
