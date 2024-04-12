<?php 

/**
 * @ author Jose A. Luque
 * @ Copyright (c) 2011 - Jose A. Luque
 *
 * @license GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;

Session::checkToken('get') or die('Invalid Token');

$kind_array = array(HTMLHelper::_('select.option', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE'), Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FILE')),
            HTMLHelper::_('select.option', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER'), Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_FOLDER')));

$status_array = array(HTMLHelper::_('select.option', '0', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_WRONG')),
            HTMLHelper::_('select.option', '1', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_OK')),
            HTMLHelper::_('select.option', '2', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TITLE_EXCEPTIONS')));

$data_dismiss = "data-bs-dismiss";
?>

<!-- Modal view file -->
<div class="modal" id="view_file" tabindex="-1" role="dialog" aria-labelledby="viewfileLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header alert alert-info">
                <h2 class="modal-title" id="viewfileLabel"><?php echo Text::_('COM_SECURITYCHECKPRO_FILE_CONTENT'); ?></h2>
                <button type="button" class="close" <?php echo $data_dismiss; ?>="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="overflow-y: scroll;">    
                <?php 
                    $mainframe = Factory::getApplication();
                    $contenido = $mainframe->getUserState('contenido', "vacio");
                    echo$contenido;
                ?>                
            </div>
            <div class="modal-footer">                    
                <button type="button" class="btn btn-default" <?php echo $data_dismiss; ?>="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
            </div>              
        </div>
    </div>
</div>


<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=onlinechecks&'. Session::getFormToken() .'=1');?>" method="post" class="margin-left-10 margin-right-10" name="adminForm" id="adminForm">

<?php 
        
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
?>
        
          
            <div class="alert alert-warning">
                <?php echo Text::_('COM_SECURITYCHECKPRO_PROFESSIONAL_HELP'); ?>
                <p>    <a href="https://securitycheck.protegetuordenador.com/index.php/contact-us" target="_blank"  rel="noopener noreferrer" class="btn btn-primary btn-success btn-large">
        <?php echo Text::_('COM_SECURITYCHECKPRO_CONTACT_US'); ?></a>
                </p>
            </div>
            
            <!-- Contenido principal -->            
            <div> 
							<div id="filter-bar" class="filter-search-bar btn-group">
								<div class="input-group">
									<input type="text" class="form-control" name="filter_onlinechecks_search" placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>" id="filter_onlinechecks_search" value="<?php echo $this->escape($this->state->get('filter_onlinechecks_search')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
									<span class="filter-search-bar__label visually-hidden">
									<label id="filter_search-lbl" for="filter_search">Filter:</label>
									</span>
									<button type="submit" class="filter-search-bar__button btn btn-primary" aria-label="Search">
										<span class="filter-search-bar__button-icon icon-search" aria-hidden="true"></span>
									</button>
									<button class="btn btn-dark" type="button" id="filter_onlinechecks_search_button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
								</div>
							</div>													
                                            
                            <div style="text-align: right;">
                                <span class="badge" style="background-color: #19AAFF; padding: 10px 10px 10px 10px;"><?php echo Text::_('COM_SECURITYCHECKPRO_ONLINE_CHECK_LOGS');?></span>
                            </div>
                            <div class="table-responsive">
                                    <table id="onlinechecks_logs_table" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FILES_SCANNED'); ?>
                                            </th>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_THREATS_FOUND'); ?>                
                                            </th>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_INFECTED_FILES'); ?>                
                                            </th>
                                            <th class="center">
                                                <?php echo Text::_('COM_SECURITYCHECKPRO_CREATION_DATE'); ?>
                                            </th>                                            
                                            <th class="center">
                                                <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                                            </th>        
                                        </tr>
                                    </thead>
            <?php
            $k = 0;
            if (!empty($this->items) ) {    
                foreach ($this->items as &$row) {        
                    ?>
                                        <tr>
                                        <td class="center">
                    <?php
                    $span = "<span class=\"badge bg-dark\">";
                    echo $span . $row[2]; ?>
                                            </span>                    
                                        </td>
                                        <td class="center">
                    <?php 
                    if ($row[3] == 0 ) {
                        $span = "<span class=\"badge bg-success\">";
                        echo $span . $row[3];
                    } else  {
                        $span = "<span class=\"badge bg-danger\">";
                        echo $span . $row[3];
                    }
                    ?>
                                            </span>                    
                                        </td>
                                        <td class="center">
                    <?php
                    if (empty($row[5]) ) {
                          $span = "<span class=\"badge bg-success\">";
                          echo $span . Text::_('COM_SECURITYCHECKPRO_NONE') . "</span>";
                    } else {
                        // Decodificamos los nombres, que vendrán en formato json
                        $infected_files = json_decode($row[5], true);
                        // Contamos los elementos, puesto que vamos a mostrar sólo 3 nombres en la tabla por motivos de claridad.
                        $elements = count($infected_files);
                        $cont = 0;
                        while ( ($cont <=2) && ($cont < $elements) ) {
                               $span = "<span class=\"badge bg-warning\">";
                               echo $span . $infected_files[$cont] . "</span><br/>";
                               $cont++;
                        }
                        // Si hay más elementos, lo indicamos
                        if ($cont < $elements ) {
                            $span = "<span class=\"badge\">";
                            echo $span . Text::sprintf('COM_SECURITYCHECKPRO_MORE_FILES', $elements - $cont) . "</span><br/>";
                        }                    
                    }                
                    ?></td>                
                                        <td class="center" class="font-size-14"><?php echo $row[4]; ?></td>
                                        </td>                                        
                                        <td class="center">
                    <?php echo HTMLHelper::_('grid.id', $k, $row[1], '', 'onlinechecks_logs_table'); ?>
                                        </td>
                                    </tr>
                    <?php 
                    $k++;
                } 
            }    ?>
                                    </table>
                            </div>

                                <?php
                                if (!empty($this->items) ) {        
                                    ?>
                                    <div>                                        
                                    <?php echo $this->pagination->getListFooter(); echo $this->pagination->getLimitBox(); ?>
                                    </div>
                                <?php } ?>
            
            </div>
            
        </div>
</div>        

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="onlinechecks" />
</form>
