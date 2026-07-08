<?php
defined('_JEXEC') or die();
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;

/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig\HtmlView $this */
/** @var \SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel $basemodel */

/** @var \Joomla\CMS\Application\CMSApplication $app */
$app          = Factory::getApplication();
$globalEditor = $app->getConfig()->get('editor');
$userEditor   = $app->getIdentity()->getParam('editor');
$selectedEditor = ($userEditor && $userEditor !== 'JEditor') ? $userEditor : $globalEditor;
if (empty($selectedEditor)) {
	$selectedEditor = 'none';
}
$editor = Editor::getInstance($selectedEditor);
?>
<div class="card shadow-soft mb-3">
	<div class="card-body">
		<h5 class="fw-semibold mb-4">
			<i class="fa fa-right-from-bracket text-primary me-2" aria-hidden="true"></i>
			<?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECTION_LABEL'); ?>
		</h5>

		<div class="row g-4 mb-4">
			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="redirect_after_attack">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_AFTER_ATTACK_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'redirect_after_attack',
					'boolean',
					['class' => 'form-select', 'id' => 'redirect_after_attack'],
					$this->redirect_after_attack,
					false
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_AFTER_ATTACK_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-md-6 col-lg-4">
				<label class="form-label" for="redirect_options">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_LABEL'); ?>
				</label>
				<?php echo $basemodel->renderSelect(
					'redirect_options',
					[
						'1' => 'PLG_SECURITYCHECKPRO_JOOMLA_PATH_LABEL',
						'2' => 'COM_SECURITYCHECKPRO_REDIRECTION_OWN_PAGE',
					],
					['class' => 'form-select', 'id' => 'redirect_options', 'onchange' => 'Disable()'],
					$this->redirect_options,
					false,
					true
				); ?>
				<div class="form-text">
					<?php echo Text::_('PLG_SECURITYCHECKPRO_REDIRECT_DESCRIPTION'); ?>
				</div>
			</div>

			<div class="col-lg-8">
				<label class="form-label" for="redirect_url">
					<?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECTION_URL_TEXT'); ?>
				</label>
				<small class="text-muted d-block mb-1">
					<i class="fa fa-globe me-1" aria-hidden="true"></i><?php echo htmlspecialchars((string) $this->siteUrl, ENT_QUOTES, 'UTF-8'); ?>
				</small>
				<input type="text" class="form-control" id="redirect_url" name="redirect_url"
					   value="<?php echo htmlspecialchars((string) $this->redirect_url, ENT_QUOTES, 'UTF-8'); ?>" />
				<div class="form-text">
					<?php echo Text::_('COM_SECURITYCHECKPRO_REDIRECTION_URL_EXPLAIN'); ?>
				</div>
			</div>
		</div>

		<div>
			<label class="form-label">
				<?php echo Text::_('COM_SECURITYCHECKPRO_EDITOR_TEXT'); ?>
			</label>
			<div class="form-text mb-2">
				<?php echo Text::_('COM_SECURITYCHECKPRO_EDITOR_EXPLAIN'); ?>
			</div>
			<?php
			try {
				echo $editor->display(
					'custom_code',
					$this->custom_code,
					'100%',
					'200',
					10,
					10,
					true,
					null,
					null,
					null,
					['smilies' => '0', 'style' => '1', 'layer' => '0', 'table' => '0', 'clear_entities' => '0']
				);
			} catch (\Throwable $e) {
				echo '<textarea name="custom_code" id="custom_code" rows="10" class="form-control w-100">' . htmlspecialchars((string) $this->custom_code, ENT_QUOTES, 'UTF-8') . '</textarea>';
			}
			?>
		</div>
	</div>
</div>
