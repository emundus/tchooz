<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */

$excludeVulnerable  = isset($this->exclude_exceptions_if_vulnerable) ? (bool) $this->exclude_exceptions_if_vulnerable : false;
$checkHeaderReferer = isset($this->check_header_referer)             ? (bool) $this->check_header_referer             : false;
$checkBase64        = isset($this->check_base_64)                    ? (bool) $this->check_base_64                    : false;
$stripAllTags       = isset($this->strip_all_tags)                   ? (bool) $this->strip_all_tags                   : false;
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-shield-halved text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('PLG_SECURITYCHECKPRO_EXCEPTIONS_LABEL'); ?>
		</h5>

		<!-- Global setting -->
		<div class="row mb-4">
			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="exclude_exceptions_if_vulnerable">
					<?php echo Text::_('COM_SECURITYCHECKPRO_EXCLUDE_EXCEPTIONS_IF_VULNERABLE_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'exclude_exceptions_if_vulnerable',
					'boolean',
					['class' => 'form-select', 'id' => 'exclude_exceptions_if_vulnerable'],
					(int) $excludeVulnerable,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_EXCLUDE_EXCEPTIONS_IF_VULNERABLE_DESCRIPTION'); ?>
				</div>
			</div>
		</div>

		<!-- Sub-tabs (kept as joomla-tab — tracked by ExceptionsTabs TABSETS watcher) -->
		<?php echo HTMLHelper::_('uitab.startTabSet', 'ExceptionsTabs', [
			'active'     => $this->activeExceptions,
			'breakpoint' => 768,
		]); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_header_referer_tab', Text::_('PLG_SECURITYCHECKPRO_CHECK_HEADER_REFERER_LABEL')); ?>
		<div class="row g-4 mt-1">
			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="check_header_referer">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_HEADER_REFERER_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'check_header_referer',
					'boolean',
					['class' => 'form-select', 'id' => 'check_header_referer'],
					(int) $checkHeaderReferer,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_HEADER_REFERER_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_base64_tab', Text::_('PLG_SECURITYCHECKPRO_CHECK_BASE64_LABEL')); ?>
		<div class="row g-4 mt-1">
			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="check_base_64">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_BASE64_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'check_base_64',
					'boolean',
					['class' => 'form-select', 'id' => 'check_base_64'],
					(int) $checkBase64,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_CHECK_BASE64_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-12">
				<label class="form-label" for="base64_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_BASE64_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="base64_exceptions" name="base64_exceptions"
						  class="form-control font-monospace"
						  style="height:90px"><?php echo htmlspecialchars((string) $this->base64_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_BASE64_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_xss_tab', Text::_('XSS')); ?>
		<div class="row g-4 mt-1">
			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="strip_all_tags">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_STRIP_ALL_TAGS_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'strip_all_tags',
					'boolean',
					['class' => 'form-select', 'id' => 'strip_all_tags', 'onchange' => 'Disable()'],
					(int) $stripAllTags,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_STRIP_ALL_TAGS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6 col-lg-8" id="tags_to_filter_div">
				<label class="form-label" for="tags_to_filter">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_TAGS_TO_FILTER_LABEL'); ?>
				</label>
				<textarea id="tags_to_filter" name="tags_to_filter"
						  class="form-control font-monospace"
						  style="height:90px"><?php echo htmlspecialchars((string) $this->tags_to_filter, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_TAGS_TO_FILTER_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-12">
				<label class="form-label" for="strip_tags_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_STRIP_TAGS_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="strip_tags_exceptions" name="strip_tags_exceptions"
						  class="form-control font-monospace"
						  style="height:80px"><?php echo htmlspecialchars((string) $this->strip_tags_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_STRIP_TAGS_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_sql_tab', Text::_('SQL Injection')); ?>
		<div class="row g-4 mt-1">
			<div class="col-md-6">
				<label class="form-label" for="duplicate_backslashes_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_DUPLICATE_BACKSLASHES_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="duplicate_backslashes_exceptions" name="duplicate_backslashes_exceptions"
						  class="form-control font-monospace"
						  style="height:80px"><?php echo htmlspecialchars((string) $this->duplicate_backslashes_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_DUPLICATE_BACKSLASHES_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6">
				<label class="form-label" for="line_comments_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LINE_COMMENTS_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="line_comments_exceptions" name="line_comments_exceptions"
						  class="form-control font-monospace"
						  style="height:80px"><?php echo htmlspecialchars((string) $this->line_comments_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LINE_COMMENTS_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6">
				<label class="form-label" for="sql_pattern_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SQL_PATTERN_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="sql_pattern_exceptions" name="sql_pattern_exceptions"
						  class="form-control font-monospace"
						  style="height:80px"><?php echo htmlspecialchars((string) $this->sql_pattern_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SQL_PATTERN_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6">
				<label class="form-label" for="if_statement_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_IF_STATEMENT_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="if_statement_exceptions" name="if_statement_exceptions"
						  class="form-control font-monospace"
						  style="height:80px"><?php echo htmlspecialchars((string) $this->if_statement_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_IF_STATEMENT_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6">
				<label class="form-label" for="using_integers_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_USING_INTEGERS_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="using_integers_exceptions" name="using_integers_exceptions"
						  class="form-control font-monospace"
						  style="height:80px"><?php echo htmlspecialchars((string) $this->using_integers_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_USING_INTEGERS_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6">
				<label class="form-label" for="escape_strings_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_ESCAPE_STRINGS_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="escape_strings_exceptions" name="escape_strings_exceptions"
						  class="form-control font-monospace"
						  style="height:80px"><?php echo htmlspecialchars((string) $this->escape_strings_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_ESCAPE_STRINGS_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_lfi_tab', Text::_('PLG_SECURITYCHECKPRO_LFI_EXCEPTIONS_LABEL')); ?>
		<div class="row g-4 mt-1">
			<div class="col-12">
				<label class="form-label" for="lfi_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LFI_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="lfi_exceptions" name="lfi_exceptions"
						  class="form-control font-monospace"
						  style="height:90px"><?php echo htmlspecialchars((string) $this->lfi_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_LFI_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'ExceptionsTabs', 'li_secondlevel_tab', Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_EXCEPTIONS_LABEL')); ?>
		<div class="row g-4 mt-1">
			<div class="col-12">
				<label class="form-label" for="second_level_exceptions">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_EXCEPTIONS_LABEL'); ?>
				</label>
				<textarea id="second_level_exceptions" name="second_level_exceptions"
						  class="form-control font-monospace"
						  style="height:90px"><?php echo htmlspecialchars((string) $this->second_level_exceptions, ENT_QUOTES, 'UTF-8'); ?></textarea>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_SECOND_LEVEL_EXCEPTIONS_DESCRIPTION'); ?>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>
</div>
