<?php
/**
 * @package     Mywalks.Administrator
 * @subpackage  com_mywalks
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
//HTMLHelper::_('script', 'system/multiselect.js', false, true);
//HTMLHelper::_('script','system/multiselect.js', ['relative' => true]);
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user = Factory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$editIcon = '<span class="fa fa-pen-square me-2" aria-hidden="true"></span>';
?>

<form action="<?= Route::_('index.php?option=com_fabrik&view=groups'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-sm-12">
			<div id="j-main-container" class="j-main-container">
				<?= LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
				<?php if (empty($this->items)) : ?>
					<div class="alert alert-info">
						<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?= Text::_('INFO'); ?></span>
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
			                        <?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'g.id', $listDirn, $listOrder); ?>
								</th>
			                    <th scope="col">
			                        <?= HTMLHelper::_('searchtools.sort', 'COM_FABRIK_NAME', 'g.name', $listDirn, $listOrder); ?>
								</th>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'COM_FABRIK_LABEL', 'g.label', $listDirn, $listOrder); ?>
								</th>
								<th scope="col">
									<?= HTMLHelper::_('searchtools.sort', 'COM_FABRIK_FORM', 'f.label', $listDirn, $listOrder); ?>
								</th>
								<th scope="col"  class="w-10 d-none d-md-table-cell">
									<?= Text::_('COM_FABRIK_ELEMENTS'); ?>
								</th>
								<th  scope="col" class="w-3 d-none d-md-table-cell">
									<?= HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'g.published', $listDirn, $listOrder); ?>
								</th>
							</tr>
						</thead>
						<tbody>
						<?php
						$n = count($this->items);
						foreach ($this->items as $i => $item) : //echo '<pre>'.print_r($item, true); die;
							$ordering = ($listOrder == 'ordering');
							$groupEditLink = Route::_('index.php?option=com_fabrik&task=group.edit&id=' . (int) $item->id);
							$formEditLink = Route::_('index.php?option=com_fabrik&task=form.edit&id=' . (int) $item->form_id);
							$canCreate = $user->authorise('core.create', 'com_fabrik.group.' . $item->form_id);
							$canEdit = $user->authorise('core.edit', 'com_fabrik.group.' . $item->form_id);
							$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->get('id') || $item->checked_out == 0;
							$canChange = $user->authorise('core.edit.state', 'com_fabrik.group.' . $item->form_id) && $canCheckin;
							?>
							<tr class="row<?= $i % 2; ?>">
								<td class="text-center">
									<?= HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td>
									<?= $item->id; ?>
								</td>
								<td sclass="has-context">
									<?php if ($item->checked_out) : ?>
										<?= HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'groups.', $canCheckin); ?>
									<?php endif; ?>
									<?php
										if ($item->checked_out && ($item->checked_out != $user->get('id'))) :
											echo $item->name;
										else :
									?>
									<a  class="hasTooltip" href="<?= $groupEditLink; ?>">
										<?= $this->escape($item->name); ?>
									</a>
									<?php endif; ?>
								</th>
								<td class="">
									<?= Text::_($item->label); ?>
								</td>
								<td class="">
									<a href="<?= $formEditLink; ?>">
										<i class="icon-pencil"></i> <?= $item->flabel; ?>
									</a>
								</td>
								<td class="">
									<a href="index.php?option=com_fabrik&view=element&layout=edit&filter_groupId=<?= $item->id ?>">
										<i class="icon-plus"></i>
										<?= Text::_('COM_FABRIK_ADD')?>
									</a>
									<span class="badge bg-info"><?= $item->_elementCount; ?></span>
								</td>
								<td class="text-center">
									<?= HTMLHelper::_('jgrid.published', $item->published, $i, 'groups.', $canChange); ?>
								</td>
							</tr>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					</div>

					<?php // load the pagination. ?>
					<?= $this->pagination->getListFooter(); ?>

				<?php endif; ?>
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<?= HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>