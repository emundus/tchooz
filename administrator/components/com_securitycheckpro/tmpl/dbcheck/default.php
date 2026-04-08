<?php 
/**
 * @Securitycheckpro component
 * © 2011 - Jose A. Luque / Securitycheck Extensions
 * GNU GPL v3+
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Dbcheck\HtmlView $this */

Session::checkToken('get') or die('Invalid Token');

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

// Helper de escape para contenido potencialmente inyectable (NO para URLs ni tooltips)
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=dbcheck'); ?>" class="margin-left-10 margin-right-10" method="post" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/navigation.php';
    
    if ($this->supported) { ?>        
                    
        <!-- Contenido principal -->
        <div class="row">
        
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-center">                        
                    <div class="card-body">  
                        <span class="fa fa-2x fa-search" style="color:orange"></span> 
                        <div class="margin-top-5"><?php echo Text::_('COM_SECURITYCHECKPRO_SHOW_TABLES'); ?></div>
                        <div class="margin-top-5">
                            <span class="label label-info"><?php echo (string) ($this->show_tables ?? 'All'); ?></span>
                        </div>                                                                 
                    </div>
                    <div class="card-footer">                       
                        <a href="#" id="show_tables" data-bs-toggle="tooltip" title="<?php echo Text::_('COM_SECURITYCHECKPRO_DB_CONTENT'); ?>">
                            <?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-center">                        
                    <div class="card-body">        
                        <span class="fa fa-2x fa-calendar-day" style="color:orange"></span>
                        <div class="margin-top-5"><?php echo Text::_('COM_SECURITYCHECKPRO_LAST_OPTIMIZATION_LABEL'); ?></div>
                        <div class="margin-top-5">
                            <span class="label label-info"><?php echo $e($this->last_check_database ?? ''); ?></span>
                        </div>
                    </div>
                    <div class="card-footer">                       
                        <a href="#" id="last_optimization_tooltip" data-bs-toggle="tooltip" title="<?php echo Text::_('COM_SECURITYCHECKPRO_LAST_OPTIMIZATION_DESCRIPTION'); ?>">
                            <?php echo Text::_('COM_SECURITYCHECKPRO_MORE_INFO'); ?>
                        </a>
                    </div>                                                             
                </div>
            </div>
            
            <div class="col-lg-12">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fa fa-database"></i>
                        <?php echo ' ' . Text::_('COM_SECURITYCHECKPRO_DB_OPTIMIZATION'); ?>
                    </div>
                    <div class="card-body">
                        <div id="buttondatabase" class="text-center">
                            <button class="btn btn-primary" id="start_db_check" type="button">
                                <i class="fa fa-fire"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_START_BUTTON'); ?>
                            </button>
                        </div>
                        
                        <div id="securitycheck-bootstrap-main-content">        
                            <div id="securitycheck-bootstrap-database" class="securitycheck-bootstrap-content-box hidden">
                                <div class="securitycheck-bootstrap-content-box-content">
                                    <div class="securitycheck-bootstrap-progress" id="securitycheck-bootstrap-database-progress">
                                        <div class="securitycheckpro-bar width-0"></div>
                                    </div>
                                    <table id="securitycheck-bootstrap-database-table">
                                        <thead>
                                            <tr>
                                                <th width="20%" nowrap="nowrap"><?php echo Text::_('COM_SECURITYCHECKPRO_TABLE_NAME'); ?></th>
                                                <th width="1%"  nowrap="nowrap"><?php echo Text::_('COM_SECURITYCHECKPRO_TABLE_ENGINE'); ?></th>
                                                <th width="1%"  nowrap="nowrap"><?php echo Text::_('COM_SECURITYCHECKPRO_TABLE_COLLATION'); ?></th>
                                                <th width="1%"  nowrap="nowrap"><?php echo Text::_('COM_SECURITYCHECKPRO_TABLE_ROWS'); ?></th>
                                                <th width="1%"  nowrap="nowrap"><?php echo Text::_('COM_SECURITYCHECKPRO_TABLE_DATA'); ?></th>
                                                <th width="1%"  nowrap="nowrap"><?php echo Text::_('COM_SECURITYCHECKPRO_TABLE_INDEX'); ?></th>
                                                <th width="1%"  nowrap="nowrap"><?php echo Text::_('COM_SECURITYCHECKPRO_TABLE_OVERHEAD'); ?></th>
                                                <th><?php echo Text::_('COM_SECURITYCHECKPRO_RESULT'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (($this->tables ?? []) as $i => $table) { ?>
                                            <tr class="securitycheck-bootstrap-table-row <?php echo ($i % 2) ? 'alt-row' : ''; ?> hidden">
                                                <td width="20%" nowrap="nowrap"><?php echo $e($table->Name ?? ''); ?></td>
                                                
                                                <?php $isMyIsam = isset($table->Engine) && strtolower((string) $table->Engine) === 'myisam'; ?>
                                                <td width="1%" <?php echo $isMyIsam ? 'style="color:#00FF00;"' : ''; ?> nowrap="nowrap">
                                                    <?php echo $e($table->Engine ?? ''); ?>
                                                </td>

                                                <td width="1%" nowrap="nowrap"><?php echo $e($table->Collation ?? ''); ?></td>
                                                <td width="1%" nowrap="nowrap"><?php echo (int) ($table->Rows ?? 0); ?></td>
                                                <td width="1%" nowrap="nowrap"><?php echo $e($this->bytes_to_kbytes($table->Data_length ?? 0)); ?></td>
                                                <td width="1%" nowrap="nowrap"><?php echo $e($this->bytes_to_kbytes($table->Index_length ?? 0)); ?></td>

                                                <td width="1%" nowrap="nowrap">
                                                    <?php if (!empty($table->Data_free)) { ?>
                                                        <?php if ($isMyIsam) { ?>
                                                            <b class="securitycheck-bootstrap-level-high"><?php echo $e($this->bytes_to_kbytes($table->Data_free)); ?></b>
                                                        <?php } else { ?>
                                                            <em><?php echo Text::_('COM_SECURITYCHECKPRO_NOT_SUPPORTED'); ?></em>
                                                        <?php } ?>
                                                    <?php } else { ?>
                                                        <?php echo $e($this->bytes_to_kbytes($table->Data_free ?? 0)); ?>
                                                    <?php } ?>
                                                </td>

                                                <?php if ($isMyIsam) { ?>
                                                    <td id="result<?php echo (int) $i; ?>"></td>
                                                <?php } else { ?>
                                                    <td id="result"><?php echo Text::_('COM_SECURITYCHECKPRO_NO_OPTIMIZATION_NEEDED'); ?></td>
                                                <?php } ?>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div> <!-- /#securitycheck-bootstrap-main-content -->
                    </div>
                </div>
            </div>
        <!-- End contenido principal -->
        </div>            
    <?php } else { ?>
        <div class="alert alert-error"><?php echo Text::_('COM_SECURITYCHECKPRO_DB_CHECK_UNSUPPORTED'); ?></div>
    <?php } ?>            
    </div>
</div>    

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="dbcheck" />
</form>