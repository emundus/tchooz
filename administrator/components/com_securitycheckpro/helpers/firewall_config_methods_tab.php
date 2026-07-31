<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-filter text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('PLG_SECURITYCHECKPRO_METHODS_INSPECTED_LABEL'); ?>
		</h5>

		<div class="row">
			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="methods">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_METHODS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'methods',
					['GET,POST,REQUEST' => 'Get, Post, Request'],
					['class' => 'form-select', 'id' => 'methods'],
					$this->methods,
					false,
					true
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_METHODS_INSPECTED_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
	</div>
</div>
