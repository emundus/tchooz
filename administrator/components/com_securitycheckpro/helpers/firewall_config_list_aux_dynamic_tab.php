<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel $firewallconfigmodel */
?>
	<div class="box-content">
  <div class="alert alert-info">
    <p><?php echo Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_DESCRIPTION'); ?></p>
  </div>

  <?php if (!empty($this->dynamic_blacklist_elements)) : ?>
    <div id="dynamic_blacklist_buttons" class="d-flex mb-3">
      <div class="ms-auto btn-group">
        <button class="btn btn-danger" id="deleteip_dynamic_blacklist_button" type="button">
          <i class="icon-trash icon-white"></i> <?php echo Text::_('COM_SECURITYCHECKPRO_DELETE'); ?>
        </button>
      </div>
    </div>
  <?php endif; ?> 
    
	<?php if (!empty($this->pagination_dynamic_blacklist)) : ?>
	  <input type="hidden" name="start_dynamic_blacklist" id="start_dynamic_blacklist"
			 value="<?php echo (int) ($this->pagination_dynamic_blacklist->limitstart); ?>" />
	<?php endif; ?>
	<?php echo $firewallconfigmodel->getLimitBox('dynamic_blacklist', $this->pagination_dynamic_blacklist); ?>


    <table id="dynamic_blacklist_table" class="table table-striped table-bordered margin-top-10">
      <thead>
        <tr>
          <th class="text-center"><?php echo Text::_("Ip"); ?></th>
          <th class="text-center">
            <input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($this->dynamic_blacklist_elements)) :
          $k = 0;
          foreach ($this->dynamic_blacklist_elements as $row_dynamic) :           
			/** @var stdClass $row_dynamic */			
            $ip = (string) ($row_dynamic->ip ?? '');
        ?>
          <tr>
            <td class="text-center">
              <?php echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'); ?>
            </td>
            <td class="text-center">
              <?php echo HTMLHelper::_('grid.id', $k, $ip, '', 'dynamic_blacklist_cid'); ?>
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

    <?php if (isset($this->pagination_dynamic_blacklist)) : ?>
      <?php echo $this->pagination_dynamic_blacklist->getListFooter(); ?>
    <?php endif; ?>
  
</div>