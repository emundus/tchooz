<?php
/**
 * Admin Lists List Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
FabrikHelperHTML::formvalidation();
//HTMLHelper::_('script','system/multiselect.js', false, true);
//HTMLHelper::_('script','system/multiselect.js', ['relative' => true]);
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');



$user	= Factory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');

?>
<form action="<?= Route::_('index.php?option=com_fabrik&view=lists'); ?>" method="post" name="adminForm" id="adminForm">
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

		<table class="table table-striped">
			<thead>
				<tr>
					<td class="w-1 text-center">
						<?= HTMLHelper::_('grid.checkall'); ?>
					</td>
					<th scope="col" class="w-1 text-center d-none d-md-table-cell">
                        <?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'l.id', $listDirn, $listOrder); ?>
					</th>
                    <th scope="col">
                        <?= HTMLHelper::_('searchtools.sort', 'COM_FABRIK_LIST_NAME', 'l.label', $listDirn, $listOrder); ?>
					</th>
                    <th scope="col">
                        <?= HTMLHelper::_('searchtools.sort', 'COM_FABRIK_DB_TABLE_NAME', 'l.db_table_name', $listDirn, $listOrder); ?>
					</th>
					<th scope="col" class="w-10 d-none d-md-table-cell">
						<?= Text::_('COM_FABRIK_ELEMENT');?>
					</th>
					<th scope="col" class="w-10 d-none d-md-table-cell">
						<?= Text::_('COM_FABRIK_FORM'); ?>
					</th>
					<th scope="col" class="w-10 d-none d-md-table-cell">
						<?= Text::_('COM_FABRIK_VIEW_DATA');?>
					</th>
					<th scope="col" class="w-10 d-none d-md-table-cell">
						<?= Text::_('COM_FABRIK_VIEW_DETAILS'); ?>
					</th>
					<th scope="col" class="w-3 d-none d-md-table-cell">
                        <?= HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'l.published', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="9">
						<?= $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php foreach ($this->items as $i => $item) :
					$ordering = ($listOrder == 'ordering');
					$link = Route::_('index.php?option=com_fabrik&task=list.edit&id=' . $item->id);
					$params = new Registry($item->params);
					$elementLink = Route::_('index.php?option=com_fabrik&task=element.edit&id=0&filter_groupId=' . $this->table_groups[$item->id]->group_id);
					$formLink = Route::_('index.php?option=com_fabrik&task=form.edit&id=' . $item->form_id);
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
							<?= HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'lists.', $canCheckin); ?>
						<?php endif; ?>
						<?php
						if ($item->checked_out && ( $item->checked_out != $user->get('id'))) : ?>
						<span class="editlinktip hasTip"
							title="foo <?= Text::_($item->label) . "::" . $params->get('note'); ?>"> <?= Text::_($item->label); ?>
						</span>
						<?php else : ?>
						<a href="<?= $link;?>">
							<span class="editlinktip hasTip" title="<?= Text::_($item->label) . "::" . $params->get('note'); ?>">
								<?= Text::_($item->label); ?>
							</span>
						</a>
						<?php endif; ?>
					</td>
					<td>
						<?= $item->db_table_name;?>
					</td>
					<td>
						<a href="<?= $elementLink?>">
							<i class="icon-plus"></i> <?= Text::_('COM_FABRIK_ADD');?>
						</a>
					</td>
					<td>
						<a href="<?= $formLink; ?>">
							<i class="icon-pencil"></i> <?= Text::_('COM_FABRIK_EDIT'); ?>
						</a>
					</td>
					<td>
						<a href="index.php?option=com_fabrik&task=list.view&listid=<?= $item->id;?>">
							<i class="icon-list-view"></i> <?= Text::_('COM_FABRIK_VIEW_DATA');?>
						</a>
					</td>
					<td>
						<a href="#showlinkedelements" onclick="return Joomla.listItemTask('cb<?= $i;?>','list.showLinkedElements');">
							<?= Text::_('COM_FABRIK_VIEW_DETAILS');?>
						</a>
					</td>
					<td class="text-center">
						<?= HTMLHelper::_('jgrid.published', $item->published, $i, 'lists.', $canChange);?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?= HTMLHelper::_('form.token'); ?>

	</div>
</div>
</div>
</form>
