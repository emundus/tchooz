<?php 
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;

// Cargamos los archivos javascript necesarios
$document = Factory::getDocument();

$document->addScript(Uri::root().'media/com_securitycheckpro/new/js/sweetalert.min.js');

$sweet = "media/com_securitycheckpro/stylesheets/sweetalert.css";
HTMLHelper::stylesheet($sweet);

// Add style declaration
$media_url = "media/com_securitycheckpro/stylesheets/cpanelui.css";
HTMLHelper::stylesheet($media_url);
?>

<?php 
// Cargamos el contenido común
require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/common.php';

// ... y el contenido específico
require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/ruleslog.php';
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=ruleslogs');?>" class="margin-top-minus18" method="post" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>
                        
           
            <div class="card mb-6">
                <div class="card-body">
                    <div>
						<div class="input-group margin-bottom-10">
							<input type="text" name="filter_rules_search" class="form-control" placeholder="<?php echo JText::_('JSEARCH_FILTER_LABEL'); ?>" id="filter_rules_search" value="<?php echo $this->escape($this->state->get('filter.rules_search')); ?>" title="<?php echo JText::_('JSEARCH_FILTER'); ?>" />
							<button class="btn btn-outline-secondary" type="submit" rel="tooltip" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
							<button class="btn btn-outline-secondary" type="button" id="filter_rules_search_button" rel="tooltip" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
							<div class="btn-group margin-left-10">
                                <?php 
									if (!empty($this->pagination)) {
										echo $this->pagination->getLimitBox();
									}
								?>
                            </div>
						</div>							
                        
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th class="rules-logs">
										<?php echo JText::_("Ip"); ?>
                                    </th>
                                    <th class="rules-logs">
										<?php echo JText::_('COM_SECURITYCHECKPRO_USER'); ?>
                                    </th>
                                    <th class="rules-logs">
										<?php echo JText::_('COM_SECURITYCHECKPRO_RULES_LOGS_LAST_ENTRY'); ?>
                                    </th>
                                    <th class="rules-logs">
										<?php echo JText::_('COM_SECURITYCHECKPRO_RULES_LOGS_REASON_HEADER'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
								<?php
								$k = 0;
								foreach ($this->log_details as &$row) {    
									?>
									<tr class="row<?php echo $k % 2; ?>">
										<td class="rules-logs">
											<?php echo $row->ip; ?>    
										</td>
										<td class="rules-logs">
											<?php echo $row->username; ?>    
										</td>
										<td class="rules-logs">
											<?php echo $row->last_entry; ?>    
										</td>
										<td class="rules-logs">
											<?php echo $row->reason; ?>    
										</td>
									</tr>
								<?php
									$k = $k+1;
								}
								?>
                            </tbody>
                        </table>

						<?php
						if (!empty($this->log_details) ) {        
							?>
										<div class="margen">
											<div>
							<?php echo $this->pagination->getListFooter();  ?>
											</div>
										</div>
							<?php
						}
						?>

                        </div>

                        <div class="card" class="margin-top-10 margin-left-10 width-40rem">
                            <div class="card-body card-header">
                                <?php echo JText::_('COM_SECURITYCHECKPRO_COPYRIGHT'); ?><br/>                                
                            </div>                                
                        </div>            
                </div>
            </div>            
        </div>
</div>


<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="ruleslogs" />
</form>
