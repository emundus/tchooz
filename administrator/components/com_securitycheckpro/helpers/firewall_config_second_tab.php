<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-layer-group text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LABEL'); ?>
		</h5>

		<div class="row g-4">
			<!-- Left: settings -->
			<div class="col-lg-5">
				<div class="row g-4">
					<div class="col-sm-6 col-lg-12">
						<label class="form-label" for="second_level">
							<?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_LABEL'); ?>
						</label>
						<?php echo $basemodel->renderSelect(
							'second_level',
							'boolean',
							['class' => 'form-select', 'id' => 'second_level'],
							$this->second_level,
							false
						); ?>
						<div class="form-text">
							<?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_DESCRIPTION'); ?>
						</div>
					</div>

					<div class="col-sm-6 col-lg-12">
						<label class="form-label" for="second_level_redirect">
							<?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_IF_PATTERN_LABEL'); ?>
						</label>
						<?php echo $basemodel->renderSelect(
							'second_level_redirect',
							['1' => 'COM_SECURITYCHECKPRO_YES'],
							['class' => 'form-select', 'id' => 'second_level_redirect'],
							$this->second_level_redirect,
							false,
							true
						); ?>
						<div class="form-text">
							<?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_IF_PATTERN_DESCRIPTION'); ?>
						</div>
					</div>

					<div class="col-sm-6 col-lg-12">
						<label class="form-label" for="second_level_limit_words">
							<?php echo Text::_('PLG_SECURITYCHECKPRO_LIMIT_WORDS_LABEL'); ?>
						</label>
						<input type="number" class="form-control" id="second_level_limit_words"
							   name="second_level_limit_words" min="1" max="99"
							   value="<?php echo (int) $this->second_level_limit_words; ?>" />
						<div class="form-text">
							<?php echo Text::_('PLG_SECURITYCHECKPRO_LIMIT_WORDS_DESCRIPTION'); ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Right: words list (Base64-encoded, decoded by JS on focus) -->
			<div class="col-lg-7">
				<label class="form-label" for="second_level_words">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_WORDS_LABEL'); ?>
				</label>
				<textarea id="second_level_words" name="second_level_words"
						  class="form-control font-monospace"
						  style="height:260px"><?php echo htmlspecialchars((string) $this->second_level_words, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_WORDS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
	</div>
</div>
