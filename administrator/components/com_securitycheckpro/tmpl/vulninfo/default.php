<?php 
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

Session::checkToken('get') or die('Invalid Token');

// Load plugin language
$lang2 = Factory::getApplication()->getLanguage();
$lang2->load('plg_system_securitycheckpro');

$type_array = array(HTMLHelper::_('select.option', 'Component', Text::_('COM_SECURITYCHECKPRO_TITLE_COMPONENT')),
            HTMLHelper::_('select.option', 'Plugin', Text::_('COM_SECURITYCHECKPRO_TITLE_PLUGIN')),
            HTMLHelper::_('select.option', 'Module', Text::_('COM_SECURITYCHECKPRO_TITLE_MODULE')));
            
$vulnerable_array = array(HTMLHelper::_('select.option', 'Si', Text::_('COM_SECURITYCHECKPRO_HEADING_VULNERABLE')),
            HTMLHelper::_('select.option', 'No', Text::_('COM_SECURITYCHECKPRO_GREEN_COLOR')));
?>


<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=securitycheckpro&'. Session::getFormToken() .'=1');?>" class="margin-left-10 margin-right-10" method="post" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>
        
                    
            <!-- Contenido principal -->            
            <div class="card mb-3">
                <div class="margin-left-10 margin-right-10 margin-top-10">
					<?php
						$local_joomla_branch = explode(".", JVERSION); 
						// Construimos la cabecera de la versión de Joomla para la que se muestran vulnerabilidades según la versión instalada
						$joomla_version_header = "<i class=\"fa fa-fw icon-joomla\"> " . $local_joomla_branch[0] ."</i>";       
					?>
                    <span class="badge background-FFADF5 padding-10-10-10-10 float-right"><?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_LIST'); echo $joomla_version_header; ?></span>
                </div>
                <div class="card-body">                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th width="5" class="vulnerabilities-list text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_PRODUCT'); ?>
                                        </th>
                                        <th class="vulnerabilities-list text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_DETAILS'); ?>
                                        </th>
                                        <th class="vulnerabilities-list text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_CLASS'); ?>
                                        </th>
                                        <th class="vulnerabilities-list text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_PUBLISHED'); ?>
                                        </th>
                                        <th class="vulnerabilities-list text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_VULNERABLE'); ?>
                                        </th>
                                        <th class="vulnerabilities-list text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_SOLUTION'); ?>
                                        </th>
                                    </tr>
                                </thead>
                                <?php
                                $k = 0;
                                $local_joomla_branch = explode(".", JVERSION); // Versión de Joomla instalada
                                foreach ($this->vuln_details as &$row) {
                                    // Variable que indica cuándo se ha de mostrar la información de cada elemento del array
                                    $to_list = false;
                                    /* Array con todas las versiones y modificadores para las que es vulnerable el producto */
                                    $vuln_joomla_version_array = explode(",", $row['Joomlaversion']);
                                    foreach ($vuln_joomla_version_array as $joomla_version) {
                                        $vulnerability_branch = explode(".", $joomla_version);
                                        if ($vulnerability_branch[0] == $local_joomla_branch[0] ) {                            
                                            $to_list = true;
                                            break;
                                        }
                                    }
                                    // Hemos de mostrar la información porque la vulnerabilidad es aplicable a nuestra versión de Joomla
                                    if ($to_list ) {
                                        ?>
                                <tr class="<?php echo "row$k"; ?>">
                                    <td class="text-center">
                                        <?php echo $row['Product']; ?>    
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row['description']; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row['vuln_class']; ?>    
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row['published']; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row['vulnerable']; ?>    
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $solution_type = $row['solution_type'];            
                                        if ($solution_type == 'update' ) {
                                            echo Text::_('COM_SECURITYCHECKPRO_SOLUTION_TYPE_' . $row['solution_type']) . ' ' . $row['solution'];
                                        }else if ($solution_type == 'none' ) {
                                            echo Text::_('COM_SECURITYCHECKPRO_SOLUTION_TYPE_NONE');
                                        }
                                            
                                        ?>
                                    </td>                            
                                </tr>
                                        <?php
                                        $k = $k+1;
                                    }
                                }
                                ?>                            
                            </table>                        
                        </div>    
                        
                        <div class="alert alert-success centrado">
        <?php echo Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_EXPLAIN_1'); ?>    
                        </div>

        <?php
        if (!empty($this->vuln_details) ) {        
            ?>
                        <div>
            <?php echo $this->pagination->getListFooter(); ?>
                        </div>                    
        <?php }    ?>
                    </div>                              
                </div>
        </div>
</div>        
 

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="view" value="vulninfo" />
<input type="hidden" name="controller" value="securitycheckpro" />
</form>
