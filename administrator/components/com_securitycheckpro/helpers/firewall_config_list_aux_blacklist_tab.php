<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel $firewallconfigmodel */
?>

<!-- Blacklist Import file modal -->
<div class="modal fade" id="select_blacklist_file_to_upload" tabindex="-1" aria-labelledby="blacklistfileuploadLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header alert alert-info">
        <h2 class="modal-title" id="blacklistfileuploadLabel">
          <?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_SETTINGS'); ?>
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
      </div>

      <div class="modal-body">
        <div id="div_messages" class="margen-loading">          
          <h5><?php echo Text::_('COM_SECURITYCHECKPRO_SELECT_EXPORTED_FILE'); ?></h5>
          <div class="controls">
            <input class="form-control" id="file_to_import_blacklist" name="file_to_import_blacklist" type="file" />
          </div>
        </div>
      </div>

      <div class="modal-footer" id="div_boton_subida_blacklist">
        <input class="btn btn-primary" id="upload_import_button" type="button" value="<?php echo Text::_('COM_SECURITYCHECKPRO_UPLOAD_AND_IMPORT'); ?>" />
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <?php echo Text::_('COM_SECURITYCHECKPRO_CLOSE'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="box-content">
  <div class="alert alert-info">
    <p><?php echo Text::_('COM_SECURITYCHECKPRO_BLACKLIST_DESCRIPTION'); ?></p>
  </div>

  <div class="alert alert-info alert-dismissible fade show" role="alert">
    <p><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_HEADER'); ?></p>

    <ol class="mb-2">
      <b><?php echo Text::_('COM_SECURITYCHECKPRO_IPV4'); ?></b>
      <li>
        <b><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_SINGLE'); ?></b>,
        i.e. <var><?php echo htmlspecialchars((string) $this->current_ip, ENT_QUOTES, 'UTF-8'); ?></var>
      </li>
      <li>
        <b><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_RANGE'); ?></b>,
        i.e. <var><?php echo htmlspecialchars((string) $this->range_example, ENT_QUOTES, 'UTF-8'); ?></var>
      </li>
      <li>
        <b><?php echo Text::_('COM_SECURITYCHECKPRO_CIDR'); ?></b>,
        i.e. <var><?php echo htmlspecialchars((string) $this->cidr_v4_example, ENT_QUOTES, 'UTF-8'); ?></var>
      </li>
    </ol>

    <ol class="mb-2">
      <b><?php echo Text::_('COM_SECURITYCHECKPRO_IPV6'); ?></b>
      <li>
        <b><?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_SINGLE'); ?></b><?php echo ", i.e. 2001:13d0::1"; ?>
      </li>
      <li>
        <b><?php echo Text::_('COM_SECURITYCHECKPRO_CIDR'); ?></b><?php echo ", i.e. 2001:13d0::/29"; ?>
      </li>
    </ol>

    <p class="mb-0">
      <?php echo Text::_('COM_SECURITYCHECKPRO_ADD_IP_CURRENT'); ?>
      <code><?php echo htmlspecialchars((string) $this->current_ip, ENT_QUOTES, 'UTF-8'); ?></code>
      <button type="button" id="add_ip_whitelist_button2" class="btn btn-sm btn-success">
        <?php echo Text::_('COM_SECURITYCHECKPRO_ADD_TO_WHITELIST'); ?>
      </button>
    </p>

    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
  </div>

  <div id="blacklist_buttons" class="d-flex flex-wrap gap-2 mb-3">
    <div class="btn-group">
      <input type="text"
             class="form-control"
             name="blacklist_add_ip"
             id="blacklist_add_ip"
             placeholder="<?php echo Text::_('COM_SECURITYCHECKPRO_NEW_IP'); ?>"
             title="<?php echo Text::_('COM_SECURITYCHECKPRO_NEW_IP_LABEL'); ?>" />
    </div>

    <div class="btn-group">
      <button class="btn btn-success" id="add_ip_blacklist_button" type="button">
        <i class="fa fa-plus-square"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_ADD'); ?>
      </button>
    </div>

    <div class="btn-group">
      <a href="#select_blacklist_file_to_upload"
         id="select_blacklist_file_to_upload_btn"
         role="button"
         class="btn btn-secondary"
         data-bs-toggle="modal">
        <i class="icon-upload"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_IMPORT_IPS'); ?>
      </a>
    </div>

    <div class="btn-group">
      <button class="btn btn-info" id="export_blacklist_button" type="button">
        <i class="icon-new icon-white"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_EXPORT_IPS'); ?>
      </button>
    </div>

    <?php if (!empty($this->blacklist_elements)) : ?>
      <div class="ms-auto btn-group">
        <button class="btn btn-danger" id="delete_ip_blacklist_button" type="button">
          <i class="icon-trash icon-white"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_DELETE'); ?>
        </button>
      </div>
    <?php endif; ?>
  </div>    
	
	<input type="hidden" name="active_tab" id="active_tab" value="blacklist" />
	<?php if (!empty($this->pagination_blacklist)) : ?>
	  <input type="hidden" name="start_blacklist" id="start_blacklist"
			 value="<?php echo (int) ($this->pagination_blacklist->limitstart); ?>" />
	<?php endif; ?>
	<?php echo $firewallconfigmodel->getLimitBox('blacklist', $this->pagination_blacklist); ?>

	
    <table class="table table-striped table-bordered margin-top-10">
      <thead>
        <tr>
          <th class="text-center"><?php echo Text::_("Ip"); ?></th>
          <th class="text-center">
            <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($this->blacklist_elements)) :
          $k = 0;
          foreach ($this->blacklist_elements as $row) :
            /** @var stdClass $row */
			$ip = (string) ($row->ip ?? '');
        ?>
          <tr>
            <td class="text-center">
              <?php echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td class="text-center">
              <?php echo HTMLHelper::_('grid.id', $k, $ip); ?>
            </td>
          </tr>
        <?php
            $k++;
          endforeach;
        else : ?>
          <tr><td colspan="2" class="text-center text-muted"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if (isset($this->pagination_blacklist)) : ?>
      <?php echo $this->pagination_blacklist->getListFooter(); ?>
    <?php endif; ?>
  
</div>