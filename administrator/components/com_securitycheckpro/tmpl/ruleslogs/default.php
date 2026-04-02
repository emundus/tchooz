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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Filesystem\Path;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Ruleslogs\HtmlView $this */
$app = Factory::getApplication();
$filterSearch = (string) ($this->state->get('filter.rules_search') ?? '');
$hasRows = !empty($this->log_details) && \is_iterable($this->log_details);
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=ruleslogs'); ?>"
      method="post"
      name="adminForm"
      id="adminForm"
      class="scp-ruleslogs">

    <?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

    <div class="card mb-4">
        <div class="card-body">

            <!-- Filtros / Buscador -->
            <div class="row g-2 align-items-center mb-3">
                <div class="col">
                    <label for="filter_rules_search" class="visually-hidden">
                        <?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>
                    </label>
                    <div class="input-group">
                        <input
                            type="text"
                            name="filter_rules_search"
                            id="filter_rules_search"
                            class="form-control"
                            placeholder="<?php echo $this->escape(Text::_('JSEARCH_FILTER_LABEL')); ?>"
                            value="<?php echo $this->escape($filterSearch); ?>"
                            autocomplete="off"
                            aria-label="<?php echo $this->escape(Text::_('JSEARCH_FILTER_LABEL')); ?>"
                        />
                        <button class="btn btn-outline-secondary" type="submit">
                            <span class="icon-search" aria-hidden="true"></span>
                            <span class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></span>
                        </button>
                        <button class="btn btn-outline-secondary" type="button" id="filter_rules_clear">
                            <span class="icon-remove" aria-hidden="true"></span>
                            <span class="visually-hidden"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></span>
                        </button>
                    </div>
                </div>

                <div class="col-auto">
                    <?php
                    // Limitbox (paginación por página) si existe
                    if (isset($this->pagination)) {
                        echo $this->pagination->getLimitBox();
                    }
                    ?>
                </div>
            </div>

            <!-- Tabla de resultados -->
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle mb-0">
                    <caption class="visually-hidden">
                        <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_RULES_LOGS')); ?>
                    </caption>
                    <thead>
                    <tr>
                        <th class="text-center">
                            <?php echo Text::_('Ip'); ?>
                        </th>
                        <th class="text-center">
                            <?php echo Text::_('COM_SECURITYCHECKPRO_USER'); ?>
                        </th>
                        <th class="text-center">
                            <?php echo Text::_('COM_SECURITYCHECKPRO_RULES_LOGS_LAST_ENTRY'); ?>
                        </th>
                        <th class="text-center">
                            <?php echo Text::_('COM_SECURITYCHECKPRO_RULES_LOGS_REASON_HEADER'); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($hasRows): ?>
                        <?php foreach ($this->log_details as $row): ?>
                            <tr>
                                <td class="text-center">
                                    <?php echo $this->escape($row->ip ?? ''); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $this->escape($row->username ?? ''); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $this->escape($row->last_entry ?? ''); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $this->escape($row->reason ?? ''); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <div class="alert alert-info d-inline-flex align-items-center mb-0" role="status" aria-live="polite">
                                    <span class="icon-info-circle me-2" aria-hidden="true"></span>
                                    <span>
                                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                                        <?php if ($filterSearch !== ''): ?>
                                            — <a href="#" id="resetEmptyState" class="alert-link"><?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?></a>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($hasRows && isset($this->pagination)) : ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="small text-muted">
                        <?php echo $this->pagination->getResultsCounter(); ?>
                    </div>
                    <div>
                        <?php echo $this->pagination->getListFooter(); ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="card mt-3 w-100">
        <div class="card-header">
            <?php echo Text::_('COM_SECURITYCHECKPRO_COPYRIGHT'); ?><br/>
        </div>
    </div>

    <!-- Hidden fields -->
    <input type="hidden" name="option" value="com_securitycheckpro">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <input type="hidden" name="controller" value="ruleslogs">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>