<?php
/**
 * Admin Connections List Tmpl
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

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
//HTMLHelper::_('script','system/multiselect.js',false,true);
//HTMLHelper::_('script','system/multiselect.js', ['relative' => true]);
$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
    ->useScript('multiselect');

$user	= Factory::getUser();
$userId	= $user->get('id');
$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
?>
<form action="<?= Route::_('index.php?option=com_fabrik&view=connections'); ?>" method="post" name="adminForm" id="adminForm">
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
					<?= HTMLHelper::_( 'grid.sort', 'JGRID_HEADING_ID', 'c.id', $listDirn, $listOrder); ?>
				</th>
				<th scope="col" class="w-10 d-none d-md-table-cell">
					<?= Text::_('COM_FABRIK_LABEL'); ?>
				</th>
				<th scope="col" class="w-10 d-none d-md-table-cell">
					<?= Text::_('COM_FABRIK_HOST'); ?>
				</th>
				<th scope="col" class="w-10 d-none d-md-table-cell">
					<?= Text::_('COM_FABRIK_DATABASE'); ?>
				</th>
				<th scope="col" class="w-3 d-none d-md-table-cell">
					<?= Text::_('COM_FABRIK_DEFAULT'); ?>
				</th>
				<th scope="col" class="w-3 d-none d-md-table-cell">
					<?= Text::_('JPUBLISHED'); ?>
				</th>
				<th width="20%">
					<?= Text::_('COM_FABRIK_TEST_CONNECTION'); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="8">
					<?= $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) :
			$ordering	= ($listOrder == 'ordering');
			$link = Route::_('index.php?option=com_fabrik&task=connection.edit&id='.(int) $item->id);
			$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
			$canChange	= true;
			?>

			<tr class="row<?= $i % 2; ?>">
				<td><?= HTMLHelper::_('grid.id', $i, $item->id); ?></td>
				<td>
					<?= $item->id; ?>
				</td>
				<td>
					<?php if ($item->checked_out) : ?>
						<?= HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'connections.', $canCheckin); ?>
					<?php endif; ?>
					<?php
					if ($item->checked_out && ( $item->checked_out != $user->get('id'))) {
						echo $item->description;
					} else {
					?>
						<a href="<?= $link;?>" >
							<?= $item->description;?>
						</a>
					<?php
					}
					?>
				</td>
				<td>
					<?= $item->host;?>
				</td>
				<td>
					<?= $item->database; ?>
				</td>
				<td class="text-center">
					<?= HTMLHelper::_('jgrid.isdefault', $item->default, $i, 'connections.', $canChange);?>
				</td>
				<td class="text-center">
					<?= HTMLHelper::_('jgrid.published', $item->published, $i, 'connections.', $canChange);?>
				</td>
				<td>
					<a href="#edit" onclick="return Joomla.listItemTask('cb<?= $i; ?>','connection.test')">
						<?= Text::_('COM_FABRIK_TEST_CONNECTION'); ?>
					</a>
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
