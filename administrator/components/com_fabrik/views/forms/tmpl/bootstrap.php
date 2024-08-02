<?php
/**
 * Admin Forms List Tmpl
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
//HTMLHelper::_('script', 'system/multiselect.js', false, true);
//HTMLHelper::_('script','system/multiselect.js', ['relative' => true]);

//HTMLHelper::_('behavior.multiselect');
//HTMLHelper::_('formbehavior.chosen', 'select');
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>
<form action="<?= Route::_('index.php?option=com_fabrik&view=forms'); ?>" method="post" name="adminForm" id="adminForm">
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
                        <?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'f.id', $listDirn, $listOrder); ?>
					</th>
                    <th scope="col">
                        <?= HTMLHelper::_('searchtools.sort', 'COM_FABRIK_LABEL', 'f.label', $listDirn, $listOrder); ?>
					</th>
					<th scope="col" class="w-10 d-none d-md-table-cell">
						<?= Text::_('COM_FABRIK_ELEMENT'); ?>
					</th>
					<th scope="col" class="w-10 d-none d-md-table-cell">
						<?= Text::_('COM_FABRIK_CONTENT_TYPE'); ?>
					</th>
					<th scope="col" class="w-10 d-none d-md-table-cell">
						<?= Text::_('COM_FABRIK_UPDATE_DATABASE'); ?>
					</th>
					<th scope="col" class="w-10 d-none d-md-table-cell">
						<?= Text::_('COM_FABRIK_VIEW_DATA'); ?>
					</th>
					<th scope="col" class="w-3 d-none d-md-table-cell">
                        <?= HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'f.published', $listDirn, $listOrder); ?>
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
				<?php 
				//Select group form_ids for marking invalid forms
				$fdb    = Factory::getContainer()->get('DatabaseDriver');
				$query = $fdb->getQuery(true);
				$query->select('form_id');
				$query->from('#__fabrik_formgroup');
				$fdb->setQuery($query);
				$group_form_ids = $fdb->loadColumn();
				
				foreach ($this->items as $i => $item) :
					$ordering    = ($listOrder == 'ordering');
					$link       = Route::_('index.php?option=com_fabrik&task=form.edit&id=' . (int) $item->id);
					$canCreate  = $user->authorise('core.create', 'com_fabrik.form.1');
					$canEdit    = $user->authorise('core.edit', 'com_fabrik.form.1');
					$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->get('id') || $item->checked_out == 0;
					$canChange  = $user->authorise('core.edit.state', 'com_fabrik.form.1') && $canCheckin;
					$params     = new Registry($item->params);
					$invalid_form = !in_array($item->id,$group_form_ids);

					$elementLink = Route::_('index.php?option=com_fabrik&task=element.edit&id=0&filter_groupId=' . $item->group_id);
					?>
					<tr class="row<?= $i % 2; ?>">
						<td><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
						<td><?= $item->id; ?></td>
						<td>
							<?php if ($invalid_form) : ?>
								<span class="hasTooltip text-bg-danger fa icon-warning" data-isicon="true" title="<?= 'Invalid form, no groups'; ?>"></span>
							<?php endif; ?>
							<?php if ($item->checked_out) : ?>
								<?= HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'forms.', $canCheckin); ?>
							<?php endif; ?>
							<?php
							if ($item->checked_out && ($item->checked_out != $user->get('id')))
							{ ?>
								<span class="editlinktip hasTooltip"
									title="foo <?= Text::_($item->label) . "::" . $params->get('note'); ?>"> <?= Text::_($item->label); ?>
								</span>
							<?php }
							else
							{
								?>
								<a href="<?= $link; ?>">
									<span class="editlinktip hasTooltip" title="<?= Text::_($item->label) . "::" . $params->get('note'); ?>">
										<?= Text::_($item->label); ?>
									</span>
								</a>
							<?php } ?>
						</td>
						<td>
							<a href="<?= $elementLink ?>">
								<i class="icon-plus"></i> <?= Text::_('COM_FABRIK_ADD'); ?>
							</a>
						</td>
						<td>
							<div class="btn-group">
								
								<button data-bs-toggle="dropdown" class="dropdown-toggle btn btn-sm">
									<span class="caret"></span>
									<span class="element-invisible">Actions for: COM_FABRIK_EXPORT_CONTENT_TYPE</span>
								</button>
								<ul class="dropdown-menu">
									<li>
										<a href="javascript://" onclick="Joomla.listItemTask('cb<?= $i; ?>', 'form.createContentType')">
											<span class="icon-upload text-nowrap"> <?= Text::_('COM_FABRIK_CONTENT_TYPE_EXPORT'); ?></span>
										</a>
									</li>
									<?php
									if ($params->get('content_type_path', '') !== '') :?>
										<li>
											<a href="index.php?option=com_fabrik&task=form.downloadContentType&cid=<?= $item->id; ?>">
												<span class="icon-download text-nowrap"> <?= Text::_('COM_FABRIK_CONTENT_TYPE_DOWNLOAD'); ?></span>
											</a>
										</li>
										<?php

									endif;
									?>
								</ul>
							</div>
						</td>
						<td>
							<a href="#edit" onclick="return Joomla.listItemTask('cb<?= $i; ?>','forms.updateDatabase')">
								<i class="icon-refresh"></i> <?= Text::_('COM_FABRIK_UPDATE_DATABASE'); ?>
							</a>
						</td>
						<td>
							<a href="index.php?option=com_fabrik&task=list.view&listid=<?= $item->list_id ?>">
								<i class="icon-list-view"></i> <?= Text::_('COM_FABRIK_VIEW_DATA'); ?>
							</a>
						</td>
						<td class="text-center">
							<?= HTMLHelper::_('jgrid.published', $item->published, $i, 'forms.', $canChange); ?>
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
</form>
