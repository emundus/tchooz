<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;


// Precarga textos JS (evita hardcodear cadenas en el script)
Text::script('COM_SECURITYCHECKPRO_IMPORT_SETTINGS_FILE_REQUIRED');
Text::script('COM_SECURITYCHECKPRO_IMPORT_SETTINGS_FILE_INVALID');
Text::script('JGLOBAL_VALIDATION_FORM_FAILED');
?>

<form
    action="<?php echo Route::_('index.php?option=com_securitycheckpro'); ?>"
    method="post"
    enctype="multipart/form-data"
    name="adminForm"
    id="adminForm"
    class="scpro-form container-fluid"
    aria-describedby="importHelp"
>
    <?php
    // Navegación superior del componente
    require JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php';
    ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="alert alert-warning mb-4" role="alert">
                <?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_SETTINGS_ALERT'); ?>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="file_to_import" class="form-label">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_SETTINGS'); ?>
                    </label>
                    <input
                        type="file"
                        id="file_to_import"
                        name="file_to_import"
                        class="form-control"
                        accept=".json,application/json"
                        aria-describedby="fileHelp"
                        required
                    />                    
                    <div id="importHelp" class="visually-hidden">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_SETTINGS'); ?>
                    </div>
                </div>

                <div class="col-12 col-md-6 d-flex gap-2">
                    <button
                        type="button"
                        id="read_file_button"
                        class="btn btn-primary"
                        disabled
                        data-task="upload.read_file"
                    >
                        <span class="btn-label"><?php echo Text::_('COM_SECURITYCHECKPRO_UPLOAD_AND_IMPORT'); ?></span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>

                    <button type="reset" class="btn btn-outline-secondary" id="reset_button">
                        <?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Campos ocultos estándar Joomla -->
    <input type="hidden" name="option" value="com_securitycheckpro" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="1" />
    <input type="hidden" name="controller" value="upload" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>