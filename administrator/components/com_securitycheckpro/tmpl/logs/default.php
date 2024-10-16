<?php 
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

// Load plugin language
$lang2 = Factory::getLanguage();
$lang2->load('plg_system_securitycheckpro');
            
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>


<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=logs');?>" class="margin-left-10 margin-right-10" method="post" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>

    <?php if (!($this->logs_attacks)) { ?>
            <div class="alert alert-danger text-center margen_inferior">
                <h2><?php echo Text::_('COM_SECURITYCHECKPRO_LOGS_RECORD_DISABLED'); ?></h2>
                <div id="top"><?php echo Text::_('COM_SECURITYCHECKPRO_LOGS_RECORD_DISABLED_TEXT'); ?></div>
            </div>
    <?php } ?>
        
             
            <!-- Contenido principal -->            
            <div class="card mb-3">
                <div class="card-body">           
					<?php
						echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
					?>                   
                </div>                
                <div class="logs-style">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead>
							<tr>
                                <th class="center">
									<?php echo HTMLHelper::_('grid.sort', 'Ip', 'ip', $listDirn, $listOrder); ?>                
                                </th>                                        
                                <th class="center">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_TIME', 'time', $listDirn, $listOrder); ?>                
                                </th>
								<th class="center">
									<?php echo Text::_('COM_SECURITYCHECKPRO_USER'); ?>
                                </th>
                                <th class="center">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_DESCRIPTION', 'description', $listDirn, $listOrder); ?>            
                                </th>
                                <th class="center width-35">
									<?php echo Text::_('COM_SECURITYCHECKPRO_LOG_URI'); ?>
                                </th>
                                <th class="center">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_TYPE_COMPONENT', 'component', $listDirn, $listOrder); ?>                
                                </th>
                                <th class="center">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_TYPE', 'type', $listDirn, $listOrder); ?>                    
                                </th>
                                <th class="center">
									<?php echo HTMLHelper::_('grid.sort', 'COM_SECURITYCHECKPRO_LOG_READ', 'marked', $listDirn, $listOrder); ?>                
                                </th>
                                <th class="center">
                                    <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                                </th>                                        
                            </tr>
                        </thead>
                        <?php                                
                            if (!empty($this->items) ) {        
                                $k = 0;
                                foreach ($this->items as &$row) {    
                        ?>
							<tr>
                                <td align="center">
                                    <?php 
                                    $ip_sanitized =  htmlentities($row->ip);                                                                
                                    echo '<a href="https://www.whois.com/whois/' . $ip_sanitized . '" id="whois_button" target="_blank" data-bs-toggle="tooltip" title="'. Text::_('COM_SECURITYCHECKPRO_WHOIS') .'" rel="noopener noreferrer">'. " " . $ip_sanitized.'</a>';                                                    
                                    ?>                                                        
                                </td>                                        
                                <td align="center">
                                    <?php echo $row->time; ?>    
                                </td>
                                <td align="center">
									<?php 
                                    $username_sanitized =  htmlentities($row->username);
									echo $username_sanitized; ?>    
                                </td>
                                <td align="center">
                                    <?php $title = Text::_('COM_SECURITYCHECK_ORIGINAL_STRING'); ?>
                                    <?php $decoded_string = base64_decode($row->original_string); ?>
									<?php $decoded_string = htmlentities($decoded_string, ENT_QUOTES, "UTF-8"); ?>
									<?php $description_sanitized =  htmlentities($row->description); ?>
                                    <?php echo Text::_('COM_SECURITYCHECKPRO_' .$row->tag_description); ?>
                                    <?php echo Text::_(':' .$description_sanitized); ?>
                                    <?php echo "<br />"; ?>
										<textarea cols="30" rows="1" readonly><?php echo $decoded_string ?></textarea>
                                </td>    
                                <td align="center; style=\"word-break:break-all\"">
                                    <?php $uri_sanitized =  htmlentities($row->uri); echo $uri_sanitized;?>
                                </td>
                                        <td align="center">
                                        <?php $component_sanitized = htmlentities($row->component);
                                        echo substr(($component_sanitized), 0, 40);    ?>    
                                        </td>
                                        <td align="center">
                                        <?php 
                                        $type_sanitized =  htmlentities($row->type);
                                        $type = $type_sanitized;            
                                        if ($type == 'XSS' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/xss.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'XSS_BASE64' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/xss_base64.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'SQL_INJECTION' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/sql_injection.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'SQL_INJECTION_BASE64' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/sql_injection_base64.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'LFI' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/local_file_inclusion.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'LFI_BASE64' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/local_file_inclusion_base64.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'IP_PERMITTED' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/permitted.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'IP_BLOCKED' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/blocked.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'IP_BLOCKED_DINAMIC' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/dinamically_blocked.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'SECOND_LEVEL' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/second_level.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'USER_AGENT_MODIFICATION' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/http.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'REFERER_MODIFICATION' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/http.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'SESSION_PROTECTION' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/session_protection.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'SESSION_HIJACK_ATTEMPT' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/session_hijack.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if (($type == 'MULTIPLE_EXTENSIONS') || ($type == 'FORBIDDEN_EXTENSION') ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/upload_scanner.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'SPAM_PROTECTION' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/spam_protection.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }else if ($type == 'URL_INSPECTOR' ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/url_inspector.png" title="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'" alt="' . Text::_('COM_SECURITYCHECKPRO_TITLE_' .$row->type) .'">');
                                        }            
                                        ?>
                                        </td>
                                        <td align="center">
                                        <?php 
                                        $marked = $row->marked;            
                                        if ($marked == 1 ) {
                                            echo ('<img src="../media/com_securitycheckpro/images/read.png" title="' . Text::_('COM_SECURITYCHECKPRO_LOG_READ') .'" alt="' . Text::_('COM_SECURITYCHECKPRO_LOG_READ') .'">');
                                        } else {
                                            echo ('<img src="../media/com_securitycheckpro/images/no_read.png" title="' . Text::_('COM_SECURITYCHECKPRO_LOG_UNREAD') .'" alt="' . Text::_('COM_SECURITYCHECKPRO_LOG_UNREAD') .'">');
                                        }
                                        ?>
                                        </td>
                                        <td align="center">
                                        <?php echo HTMLHelper::_('grid.id', $k, $row->id); ?>
                                        </td>
                                    </tr>
                                        <?php
                                        $k = $k+1;
                                    }
                                }
                                ?>                            
                            </table>                        
                        </div>    
                        
        <?php
        if (!empty($this->items) ) {        
            ?>
            <div class="margin-left-10">
				<?php echo $this->pagination->getListFooter();?>                            
            </div>                            
        <?php }    ?>                        
                      
            <div class="col-md-6 col-lg-8 mb-md-0 mb-4 margin-top-10">
                <p class="mb-0">
                    <?php echo Text::_('COM_SECURITYCHECKPRO_COPYRIGHT'); ?> | <?php echo Text::_('COM_SECURITYCHECKPRO_ICONS_ATTRIBUTION'); ?>
                </p>                                
            </div>
        </div>                              
        </div>
    </div>
</div>

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="securitycheckpro" />
<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
</form>
