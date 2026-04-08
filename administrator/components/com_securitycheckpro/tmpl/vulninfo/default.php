<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Vulninfo\HtmlView $this */

// Lenguaje del plugin (área admin)
$app  = Factory::getApplication();
$lang = $app->getLanguage();
$lang->load('plg_system_securitycheckpro', JPATH_ADMINISTRATOR);

// Utilidades (solo para el badge visual)
$major = (int) explode('.', JVERSION)[0];
$joomlaVersionBadge = '<i class="icon-joomla" aria-hidden="true"></i> ' . $major;

?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=vulninfo'); ?>"
      method="post" name="adminForm" id="adminForm" class="mx-2">

    <?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

    <div class="card mb-3">
        <div class="d-flex justify-content-between align-items-center p-3">
            <span class="badge" style="background:#FFADF5;">
                <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_LIST')); ?>
                <?php echo $joomlaVersionBadge; ?>
            </span>

            <!-- Limitbox + contador -->
            <div class="d-flex align-items-center gap-3">
                <div class="text-muted small">
					<?php echo $this->pagination ? $this->pagination->getResultsCounter() : ''; ?>
				</div>
				<div>
					<?php echo $this->pagination ? $this->pagination->getLimitBox() : ''; ?>
				</div>

            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="dataTable" width="100%" cellspacing="0"
                       aria-describedby="vulnTableCaption">
                    <caption id="vulnTableCaption" class="visually-hidden">
                        <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_LIST')); ?>
                    </caption>
                    <thead class="table-light">
                    <tr>
                        <th width="15%" class="text-center" scope="col">
                            <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_PRODUCT')); ?>
                        </th>
                        <th class="text-center" scope="col">
                            <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_DETAILS')); ?>
                        </th>
                        <th class="text-center" scope="col" width="15%">
                            <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_CLASS')); ?>
                        </th>
                        <th class="text-center" scope="col" width="12%">
                            <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_PUBLISHED')); ?>
                        </th>
                        <th class="text-center" scope="col" width="18%">
                            <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_VULNERABLE')); ?>
                        </th>
                        <th class="text-center" scope="col" width="20%">
                            <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_SOLUTION')); ?>
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (empty($this->items)) : ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                <?php echo $this->escape(Text::_('JGLOBAL_NO_MATCHING_RESULTS')); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php $k = 0; foreach ($this->items as $row) : ?>
                            <tr class="<?php echo 'row' . ($k++ % 2); ?>">
                                <td class="text-center">
                                    <?php echo $this->escape((string)($row['Product'] ?? '—')); ?>
                                </td>
                                <td>
                                    <?php echo nl2br($this->escape((string)($row['description'] ?? ''))); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $this->escape((string)($row['vuln_class'] ?? '')); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $this->escape((string)($row['published'] ?? '')); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $this->escape((string)($row['vulnerable'] ?? '')); ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $type = (string)($row['solution_type'] ?? '');
                                    $sol  = (string)($row['solution'] ?? '');
                                    if ($type === 'update') {
                                        echo Text::_('COM_SECURITYCHECKPRO_SOLUTION_TYPE_update') . ' ' . htmlspecialchars($sol, ENT_QUOTES, 'UTF-8');
                                    } elseif ($type === 'none') {
                                        echo Text::_('COM_SECURITYCHECKPRO_SOLUTION_TYPE_NONE');
                                    } else {
                                        echo htmlspecialchars($sol, ENT_QUOTES, 'UTF-8');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-success text-center my-3" role="status">
                <?php echo $this->escape(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_EXPLAIN_1')); ?>
            </div>

			 <?php if (!empty($this->items)) : ?>
              <div class="margen">
                <div>
                  <?php echo $this->pagination->getListFooter(); ?>
                </div>
              </div>
            <?php endif; ?>

        </div>
    </div>

    <input type="hidden" name="option" value="com_securitycheckpro">
    <input type="hidden" name="view" value="vulninfo">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
