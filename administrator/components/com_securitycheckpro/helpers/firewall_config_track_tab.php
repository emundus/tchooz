 <?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
?>
<!-- Track actions -->
<?php if ($this->plugin_trackactions_installed) { ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-xl-3 mb-3">
                                                <div class="card-header text-white bg-primary">
                                                    <?php echo Text::_('PLG_TRACKACTIONS_LABEL') ?>
                                                </div>
                                                <div class="card-body">
                                                                                                    
                                                    <h4 class="card-title"><?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_DELETE_PERIOD'); ?></h4>
                                                    <div class="controls">
                                                        <input type="number" size="3" maxlength="3" id="delete_period" name="delete_period" value="<?php echo $this->delete_period ?>" title="" />   
                                                    </div>
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_DELETE_PERIOD_DESC') ?></small></p></blockquote>
                                                                                                       
                                                    <h4 class="card-title"><?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_IP_LOGGING'); ?></h4>
                                                    <div class="controls">
                                                        <?php echo booleanlist('ip_logging', array(), $this->ip_logging) ?>
                                                    </div>
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_IP_LOGGING_DESC') ?></small></p></blockquote>     
                                                </div>
                                            </div>
                                            
                                            <div class="col-xl-3 mb-3">
                                                <div class="card-header text-white bg-primary">
                                                    <?php echo Text::_('PLG_TRACKACTIONS_LABEL') ?>
                                                </div>
                                                <div class="card-body">
                                                    <h4 class="card-title"><?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_EXTENSIONS'); ?></h4>
                                                    <div class="controls">
                                                        <?php
                                                        // Listamos todas las extensiones 
                                                        $db = Factory::getContainer()->get(DatabaseInterface::class);
                                                        $query = "SELECT extension from #__securitycheckpro_trackactions_extensions" ;            
                                                        $db->setQuery($query);
                                                        $groups = $db->loadRowList();    
                                                        foreach ($groups as $key=>$value) {                                
                                                            $options_trackactions[] = HTMLHelper::_('select.option', $value[0], $value[0]);                            
                                                        }
                                                        echo HTMLHelper::_('select.genericlist', $options_trackactions, 'loggable_extensions[]', 'class="form-select" multiple="multiple"', 'value', 'text',  $this->loggable_extensions);                                                 
                                                        ?>                    
                                                    </div>    
													<blockquote><p class="text-info"><small><?php echo Text::_('PLG_SYSTEM_TRACKACTIONS_LOG_EXTENSIONS_DESC') ?></small></p></blockquote> 
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        <?php } else { ?>
                                    <div class="alert alert-warning centrado">
            <?php echo Text::_('COM_SECURITYCHECKPRO_TRACKACTIONS_NOT_INSTALLED'); ?>    
                                    </div>    
        <?php }  ?>