<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>

<!-- Whitelist Import file modal -->
    <div class="modal fade" id="select_whitelist_file_to_upload" tabindex="-1" role="dialog" aria-labelledby="whitelistfileuploadLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header alert alert-info">
                    <h2 class="modal-title" id="whitelistfileuploadLabel"><?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_SETTINGS'); ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">    
                   <div id="div_messages" class="margen-loading">
                        <label class="red"><?php echo Text::_('COM_SECURITYCHECKPRO_OVERWRITE_WARNING'); ?></label>
                        <h5><?php echo Text::_('COM_SECURITYCHECKPRO_SELECT_EXPORTED_FILE'); ?></h5>                        
                        <div class="controls">
							<input class="input_box" id="file_to_import_whitelist" name="file_to_import_whitelist" type="file" size="57" />
                        </div>
                    </div>                                                                                
                </div>
                <div class="modal-footer" id="div_boton_subida_whitelist">
                    <input class="btn btn-primary" id="import_whitelist_button" type="button" value="<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOAD_AND_IMPORT'); ?>" />
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?></button>
                </div>              
            </div>
        </div>
    </div>
    <div class="box-content">
		<div class="alert alert-info">
            <p><?php echo Text::_('COM_SECURITYCHECKPRO_WHITELIST_DESCRIPTION'); ?></p>
        </div>
        <div class="alert alert-info">
			<a class="close" href="#" data-dismiss="alert">×</a>
            <p><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_HEADER'); ?></p>
            <ol>
                <li>
					<b><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_SINGLE'); ?></b>, i.e.<var><?php echo $this->current_ip; ?></var>
                </li>
                <li>
                    <b><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_RANGE'); ?></b>, i.e.<var><?php echo $this->range_example; ?></var>
                </li>
            </ol>
            <p>
                <?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_CURRENT'); ?>
                <code><?php echo $this->current_ip; ?></code>        
                <button type="button" id="add_ip_whitelist_button" class="btn btn-sm btn-success" href="#">
					<?php echo Text::_('COM_SECURITYCHECKPRO_ADD_TO_WHITELIST'); ?>
                </button>
            </p>
        </div>
        <div id="blacklist_buttons">
            <div class="btn-group pull-left">
                <input type="text" class="form-control" name="whitelist_add_ip" placeholder="<?php echo Text::_('COM_SECURITYCHECKPRO_NEW_IP'); ?>" id="whitelist_add_ip" value="" title="<?php echo Text::_('COM_SECURITYCHECKPRO_NEW_IP_LABEL'); ?>" />
            </div>
            <div class="btn-group pull-left" class="margin-left-10 margin-bottom-20">
                <button class="btn btn-success" id="addip_whitelist_button" href="#">
					<i class="fa fa-plus"> </i>
                    <?php echo Text::_('COM_SECURITYCHECKPRO_ADD'); ?>
                </button>
            </div>
            <div class="btn-group pull-left" class="margin-left-10">
                <a href="#select_whitelist_file_to_upload" role="button" class="btn btn-secondary" data-bs-toggle="modal"><i class="icon-upload"></i><?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_IPS'); ?></a>                                
            </div>
            <div class="btn-group pull-left" class="margin-left-10">
                <button class="btn btn-info" id="export_whitelist_button" href="#">
                    <i class="icon-new icon-white"> </i>
                    <?php echo Text::_('COM_SECURITYCHECKPRO_EXPORT_IPS'); ?>
                </button>
            </div>
            <?php
               if (count($this->whitelist_elements)>0 ) {                                                                        
            ?>
            <div class="btn-group pull-right">
                <button class="btn btn-danger" id="deleteip_whitelist_button" href="#">
                    <i class="icon-trash icon-white"> </i>
					<?php echo Text::_('COM_SECURITYCHECKPRO_DELETE'); ?>
                </button>
            </div>    
            <?php } ?>
        </div>
        <table class="table table-striped table-bordered bootstrap-datatable datatable margin-top-10">
			<thead>
				<tr>
                    <th class="center"><?php echo Text::_("Ip"); ?></th>                                                                              
                    <th class="center">
						<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
                    </th>
                </tr>
            </thead>   
            <tbody>
                <?php
                if (count($this->whitelist_elements)>0 ) {
                    $k = 0;
                    foreach ($this->whitelist_elements as &$row) { 
                ?>
                <tr>
					<td class="center"><?php echo $row; ?></td>                                                                            
                    <td class="center">
						<?php echo HTMLHelper::_('grid.id', $k, $row, '', 'whitelist_cid'); ?>
					</td>
                </tr>
                <?php 
                    $k++;
                    } 
                }    ?>
            </tbody>
        </table>
    </div>
	 