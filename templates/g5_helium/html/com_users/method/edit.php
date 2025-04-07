<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Prevent direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Users\Site\View\Method\HtmlView;
use Joomla\Utilities\ArrayHelper;

/** @var  HtmlView  $this */

$cancelURL = Route::_('index.php?option=com_users&task=methods.display&user_id=' . $this->user->id);

if (!empty($this->returnURL)) {
	$cancelURL = $this->escape(base64_decode($this->returnURL));
}

$recordId     = (int) $this->record->id ?? 0;
$method       = $this->record->method ?? $this->getModel()->getState('method');
$userId       = (int) $this->user->id ?? 0;
$headingLevel = 2;
$hideSubmit   = !$this->renderOptions['show_submit'] && !$this->isEditExisting
?>
<style>
    .table-striped tbody tr:nth-child(2n+1) td {
        background-color: var(--neutral-100);
    }
</style>
<div class="tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card tw-mb-6">
    <a
        class="tw-mb-4 tw-text-link-regular tw-cursor-pointer tw-font-semibold tw-flex tw-items-center tw-group"
        href="<?php echo JRoute::_('index.php?option=com_users&view=profile&layout=edit') ?>"
    >
        <span class="material-symbols-outlined tw-mr-1">navigate_before</span>
        <span class="group-hover:tw-underline"><?php echo Text::_('GO_BACK'); ?></span>
    </a>

	<form action="<?php echo Route::_(sprintf("index.php?option=com_users&task=method.save&id=%d&method=%s&user_id=%d", $recordId, $method, $userId)) ?>"
	      class="form form-horizontal tw-flex tw-flex-col tw-gap-2" id="com-users-method-edit" method="post">
		<?php echo HTMLHelper::_('form.token') ?>
		<?php if (!empty($this->returnURL)) : ?>
			<input type="hidden" name="returnurl" value="<?php echo $this->escape($this->returnURL) ?>">
		<?php endif; ?>

		<?php if (!empty($this->renderOptions['hidden_data'])) : ?>
			<?php foreach ($this->renderOptions['hidden_data'] as $key => $value) : ?>
				<input type="hidden" name="<?php echo $this->escape($key) ?>" value="<?php echo $this->escape($value) ?>">
			<?php endforeach; ?>
		<?php endif; ?>

		<?php if (!empty($this->title)) : ?>
			<?php if (!empty($this->renderOptions['help_url'])) : ?>
				<span class="float-end">
                <a href="<?php echo $this->renderOptions['help_url'] ?>"
                   class="btn btn-sm btn-dark"
                   target="_blank"
                >
                    <span class="icon icon-question-sign" aria-hidden="true"></span>
                    <span class="visually-hidden"><?php echo Text::_('JHELP') ?></span>
                </a>
            </span>
			<?php endif;?>
			<h<?php echo $headingLevel ?> id="com-users-method-edit-head">
				<?php echo Text::_($this->title) ?>
			</h<?php echo $headingLevel ?>>
			<?php $headingLevel++ ?>
		<?php endif; ?>

		<div class="row tw-flex tw-flex-col">
			<label class="col-sm-3 col-form-label"
			       for="com-users-method-edit-title">
				<?php echo Text::_('COM_USERS_MFA_EDIT_FIELD_TITLE'); ?>
			</label>
			<div class="col-sm-9">
				<input type="text"
				       class="form-control !tw-px-2 !tw-h-[38px]"
				       id="com-users-method-edit-title"
				       name="title"
				       value="<?php echo $this->escape($this->record->title) ?>"
				       aria-describedby="com-users-method-edit-help">
				<p class="form-text" id="com-users-method-edit-help">
					<?php echo $this->escape(Text::_('COM_USERS_MFA_EDIT_FIELD_TITLE_DESC')) ?>
				</p>
			</div>
		</div>

		<div class="row">
			<div class="tw-pl-0">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" id="com-users-is-default-method" <?php echo $this->record->default ? 'checked="checked"' : ''; ?> name="default">
					<label class="form-check-label" for="com-users-is-default-method">
						<?php echo Text::_('COM_USERS_MFA_EDIT_FIELD_DEFAULT'); ?>
					</label>
				</div>
			</div>
		</div>

		<?php if (!empty($this->renderOptions['pre_message'])) : ?>
			<div class="com-users-method-edit-pre-message mt-4 mb-3">
				<?php echo $this->renderOptions['pre_message'] ?>
			</div>
		<?php endif; ?>

		<?php if (!empty($this->renderOptions['tabular_data'])) : ?>
			<div class="com-users-method-edit-tabular-container">
				<?php if (!empty($this->renderOptions['table_heading'])) : ?>
					<h<?php echo $headingLevel ?> class="h3 border-bottom mb-3">
						<?php echo $this->renderOptions['table_heading'] ?>
					</h<?php echo $headingLevel ?>>
				<?php endif; ?>
				<table class="table table-striped" style="border-color: transparent">
					<tbody>
					<?php foreach ($this->renderOptions['tabular_data'] as $cell1 => $cell2) : ?>
						<tr>
                            <?php if(!empty($cell1)) : ?>
                                <td>
                                    <?php echo $cell1 ?>
                                </td>
                            <?php endif; ?>
							<td <?php if(empty($cell1)) : ?>colspan="2"<?php endif; ?>>
								<?php echo $cell2 ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<?php if ($this->renderOptions['field_type'] == 'custom') : ?>
			<?php echo $this->renderOptions['html']; ?>
		<?php endif; ?>
		<div class="row tw-flex tw-flex-col tw-gap-1 mb-3 <?php echo $this->renderOptions['input_type'] === 'hidden' ? 'd-none' : '' ?>">
			<?php if ($this->renderOptions['label']) : ?>
				<label class="tw-pl-0" for="com-users-method-code">
					<?php echo $this->renderOptions['label']; ?>
				</label>
			<?php endif; ?>
			<div class="col-sm-9" <?php echo $this->renderOptions['label'] ? '' : 'offset-sm-3' ?>>
				<?php
				$attributes = array_merge(
					[
						'type'             => $this->renderOptions['input_type'],
						'name'             => 'code',
						'value'            => $this->escape($this->renderOptions['input_value']),
						'id'               => 'com-users-method-code',
						'class'            => 'form-control',
						'aria-describedby' => 'com-users-method-code-help',
					],
					$this->renderOptions['input_attributes']
				);

				if (strpos($attributes['class'], 'form-control') === false) {
					$attributes['class'] .= ' form-control';
				}
				?>
				<input <?php echo ArrayHelper::toString($attributes) ?>>

				<p class="form-text" id="com-users-method-code-help">
					<?php echo $this->escape($this->renderOptions['placeholder']) ?>
				</p>
			</div>
		</div>

		<div class="row">
			<div class="tw-flex tw-justify-end tw-p-0">
				<button type="submit" class="tw-w-fit tw-btn tw-btn-primary me-3 <?php echo $hideSubmit ? 'd-none' : '' ?> <?php echo $this->renderOptions['submit_class'] ?>">
					<?php echo Text::_($this->renderOptions['submit_text']); ?>
				</button>
			</div>
		</div>

		<?php if (!empty($this->renderOptions['post_message'])) : ?>
			<div class="com-users-method-edit-post-message text-muted">
				<?php echo $this->renderOptions['post_message'] ?>
			</div>
		<?php endif; ?>
	</form>
</div>
