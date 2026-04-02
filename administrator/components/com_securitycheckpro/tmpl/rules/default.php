<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Filesystem\Path;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Rules\HtmlView $this */

// Estado de búsqueda actual
$search = (string) $this->escape($this->state->get('filter.acl_search', ''));

// Paginación: limitbox opcional
$limitBox = isset($this->pagination) ? $this->pagination->getLimitBox() : '';

$canView = $this->user->authorise('core.manage', 'com_securitycheckpro')
    || $this->user->authorise('core.admin', 'com_securitycheckpro');

if (!$canView) {
    // Muestra un aviso y corta la plantilla
    echo '<div class="alert alert-danger" role="alert">'
        . Text::_('JERROR_ALERTNOAUTHOR')
        . '</div>';

    return; // Sale del layout sin imprimir el formulario
}
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=rules'); ?>" method="post" name="adminForm" id="adminForm" class="container-fluid px-3">

	<?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

	<div class="card mb-4">
		<div class="card-body">
			<div class="row g-2 align-items-center">
				<div class="col-12 col-md">
					<label for="filter_acl_search" class="visually-hidden">
						<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>
					</label>
					<div class="input-group">
						<input
							type="text"
							name="filter_acl_search"
							id="filter_acl_search"
							class="form-control"
							placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>"
							value="<?php echo $search; ?>"
							autocomplete="off"
						/>
						<button class="btn btn-outline-secondary" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
							<span class="icon-search" aria-hidden="true"></span>
							<span class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></span>
						</button>
						<button class="btn btn-outline-secondary" type="button" id="filter_acl_search_button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>">
							<span class="icon-remove" aria-hidden="true"></span>
							<span class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></span>
						</button>
					</div>
				</div>

				<?php if ($limitBox) : ?>
					<div class="col-auto">
						<div class="btn-group">
							<?php echo $limitBox; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="alert alert-info mt-3" role="alert">
				<?php echo Text::_('COM_SECURITYCHECKPRO_RULES_GUEST_USERS'); ?>
			</div>

			<div class="table-responsive mt-3">
				<table class="table table-bordered table-hover align-middle">
					<thead class="table-light">
						<tr>
							<th scope="col" class="text-center" style="width:1%;">
								<input type="checkbox" name="checkall-toggle" value=""
									title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
									onclick="Joomla.checkAll(this)" />
							</th>
							<th scope="col">
								<?php echo Text::_('COM_SECURITYCHECKPRO_RULES_GROUP_TITLE'); ?>
							</th>
							<th scope="col" style="width:20%;">
								<?php echo Text::_('COM_SECURITYCHECKPRO_RULES_RULES_APPLIED'); ?>
							</th>
							<th scope="col" class="text-nowrap" style="width:5%;">
								<?php echo Text::_('JGRID_HEADING_ID'); ?>
							</th>
							<th scope="col" style="width:30%;">
								<?php echo Text::_('COM_SECURITYCHECKPRO_RULES_LAST_CHANGE'); ?>
							</th>
                        </tr>
					</thead>
					<tbody>
						<?php if (!empty($this->items)) : ?>
							<?php $i = 0; ?>
							<?php foreach ($this->items as $row) : ?>
								<tr>
									<td class="text-center">
										<?php echo HTMLHelper::_('grid.id', $i, (int) $row->id); ?>
									</td>

									<td>
										<?php echo str_repeat('<span class="gi">|&mdash;</span>', (int) $row->level); ?>
										<?php echo $this->escape((string) $row->title); ?>
									</td>

									<td class="rules-logs">
										<?php echo HTMLHelper::_(
											'jgrid.state',
											[
												0 => [
													'task'           => 'apply_rules',
													'text'           => '',
													'active_title'   => 'COM_SECURITYCHECKPRO_RULES_NOT_APPLIED_AND_TOGGLE',
													'inactive_title' => '',
													'tip'            => true,
													'active_class'   => 'unpublish',
													'inactive_class' => 'unpublish',
												],
												1 => [
													'task'           => 'not_apply_rules',
													'text'           => '',
													'active_title'   => 'COM_SECURITYCHECKPRO_RULES_APPLIED_AND_TOGGLE',
													'inactive_title' => '',
													'tip'            => true,
													'active_class'   => 'publish',
													'inactive_class' => 'publish',
												],
											],
											(int) $row->rules_applied,
											$i,
											'rules.',   // <--- prefijo del controlador/vista
											'cb'        // <--- id base de los checkboxes (cb0, cb1, ...)
										); ?>

									</td>

									<td class="rules-logs">
										<?php echo (int) $row->id; ?>
									</td>

									<td class="rules-logs">
										<?php
										echo $row->last_change
											? HTMLHelper::_('date', $row->last_change, Text::_('DATE_FORMAT_LC2'))
											: '-';
										?>
									</td>
								</tr>
								<?php $i++; ?>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5" class="text-center text-muted">
									<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<?php if (!empty($this->items) && isset($this->pagination)) : ?>
				<div class="mt-2">
					<?php echo $this->pagination->getListFooter(); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
