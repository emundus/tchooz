<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Path;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use Joomla\CMS\Application\CMSApplication;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Controlcenter\HtmlView $this */

Session::checkToken('get') or die('Invalid Token');

// Comportamientos mínimos
HTMLHelper::_('behavior.core');

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
$basemodel = $this->basemodel;

// Helper para atributos seguros (excepto URLs/tooltips que pides sin proteger)
$attr = static function ($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=controlcenter&' . Session::getFormToken() . '=1'); ?>" class="margin-left-10 margin-right-10" method="post" name="adminForm" id="adminForm">

   <?php
    // Navegación (include robusto)
    $navFile = Path::clean(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php');
    if (is_file($navFile)) {
        require $navFile;
    }
    ?>

    <?php if (function_exists('openssl_encrypt')) { ?>

        <div id="toast" class="col-12 toast align-items-center margin-bottom-10" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong id="toast-auto" class="me-auto"></strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div id="toast-body" class="toast-body"></div>
        </div>

        <div class="card mb-6">
            <div class="card-body">
                <div class="row">
                    <div class="alert alert-info">
                        <?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_EXPLAIN'); ?>
                    </div>

                    <div class="col-xl-12 mb-12">
                        <div class="card-header text-white bg-primary">
                            <?php echo Text::_('COM_SECURITYCHECKPRO_GLOBAL_PARAMETERS'); ?>
                        </div>

                        <div class="card-body">

                            <!-- Control Center enabled -->
                            <div class="input-group mb-3">
                                <button
                                    type="button"
                                    class="btn btn-info js-info"
                                    data-title="<?php echo $attr(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_EXPLAIN')); ?>"
                                    data-body="<?php echo $attr(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_TEXT')); ?>"
                                ><?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?></button>

                                <!-- Tooltip SIN escapar a petición -->
                                <span class="input-group-text" id="control_center_enabled_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_EXPLAIN'); ?>">
                                    <?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENABLED_TEXT'); ?>
                                </span>

                                <?php echo $basemodel->renderSelect('control_center_enabled', 'boolean', ['class' => 'form-select'], (int) $this->control_center_enabled, false); ?>
                            </div>

                            <!-- Token -->
                            <div class="input-group mb-3">
                                <button
                                    type="button"
                                    class="btn btn-info js-info"
                                    data-title="<?php echo $attr(Text::_('COM_SECURITYCHECKPRO_TOKEN_EXPLAIN')); ?>"
                                    data-body="<?php echo $attr(Text::_('COM_SECURITYCHECKPRO_TOKEN_TEXT')); ?>"
                                ><?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?></button>

                                <span class="input-group-text" id="token_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_TOKEN_EXPLAIN'); ?>">
                                    <?php echo Text::_('COM_SECURITYCHECKPRO_TOKEN_TEXT'); ?>
                                </span>

                                <input class="form-control" type="text" name="token" id="token" value="<?php echo $attr($this->token ?? ''); ?>">
                            </div>

                            <!-- Secret key -->
                            <div class="input-group mb-3">
                                <button
                                    type="button"
                                    class="btn btn-info js-info"
                                    data-title="<?php echo $attr(Text::_('COM_SECURITYCHECKPRO_SECRET_KEY_EXPLAIN')); ?>"
                                    data-body="<?php echo $attr(Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT')); ?>"
                                ><?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?></button>

                                <span class="input-group-text" id="generate_key_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_SECRET_KEY_EXPLAIN'); ?>">
                                    <?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT'); ?>
                                </span>

                                <input class="form-control" type="text" name="secret_key" id="secret_key" value="<?php echo $attr($this->secret_key ?? ''); ?>" readonly>

                                <button class="btn btn-outline-secondary" type="button" onclick='document.getElementById("secret_key").value = Password.generate(32)'>
                                    <?php echo Text::_('COM_SECURITYCHECKPRO_HIDE_BACKEND_GENERATE_KEY_TEXT'); ?>
                                </button>
                            </div>

                            <!-- Control center URL (SIN escapar, a petición) -->
                            <div class="input-group mb-3">
                                <button
                                    type="button"
                                    class="btn btn-info js-info"
                                    data-title="<?php echo $attr(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL_EXPLAIN')); ?>"
                                    data-body="<?php echo $attr(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL')); ?>"
                                ><?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?></button>

                                <span class="input-group-text" id="conrol_center_label" title="<?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL_EXPLAIN'); ?>">
                                    <?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL'); ?>
                                </span>

                                <input class="form-control" type="text" name="control_center_url" id="control_center_url"
                                       value="<?php echo (string) ($this->control_center_url ?? ''); ?>"
                                       placeholder="<?php echo $attr(Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_URL_PLACEHOLDER')); ?>">
                            </div>

                            <?php
								/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
                                $mainframe   = Factory::getApplication();
                                $cc_status   = $mainframe->getUserState('download_controlcenter_log', null);
                                $errorExists = (int) ($this->error_file_exists ?? 0) === 1;

                                if (!empty($cc_status) || $errorExists) :
                            ?>
                            <div id="button_show_log" class="card-footer">
                                <h4 class="card-title"><?php echo Text::_('COM_SECURITYCHECKPRO_CONFIG_FILE_MANAGER_LOG_PATH_LABEL'); ?></h4>
                                <blockquote><p class="small text-body-secondary"><small><?php echo Text::_('COM_SECURITYCHECKPRO_LOG_FILE_EXPLAIN'); ?></small></p></blockquote>

                                <?php if (!empty($cc_status)) : ?>
                                    <button class="btn btn-success" type="button" onclick="Joomla.submitbutton('download_controlcenter_log');">
                                        <i class="fa fa-download"></i><?php echo Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_LOG'); ?>
                                    </button>
                                <?php endif; ?>

                                <?php if ($errorExists) : ?>
                                    <button class="btn btn-danger" type="button" onclick="add_element_to_form('error_log','1'); Joomla.submitbutton('download_controlcenter_log');">
                                        <i class="fa fa-download"></i><?php echo Text::_('COM_SECURITYCHECKPRO_DOWNLOAD_ERROR_LOG'); ?>
                                    </button>
                                <?php endif; ?>

                                <button class="btn btn-warning" type="button" onclick="Joomla.submitbutton('delete_controlcenter_log');">
                                    <i class="fa fa-trash"></i><?php echo Text::_('COM_SECURITYCHECKPRO_CONFIG_FILE_MANAGER_DELETE_LOG_FILE_LABEL'); ?>
                                </button>
                            </div>
                            <?php endif; ?>

                        </div><!-- /.card-body -->
                    </div>
                </div>
            </div>
        </div>

    <?php } else { ?>
        <div class="alert alert-error">
            <?php echo Text::_('COM_SECURITYCHECKPRO_CONTROLCENTER_ENCRYPT_LIBRARY_NOT_PRESENT'); ?>
        </div>
    <?php } ?>

    <input type="hidden" name="option" value="com_securitycheckpro" />
    <input type="hidden" name="view" value="controlcenter" />
    <input type="hidden" name="boxchecked" value="1" />
    <input type="hidden" name="task" id="task" value="" />
    <input type="hidden" name="controller" value="controlcenter" />
</form>