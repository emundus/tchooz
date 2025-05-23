<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Users\Site\Model\CaptiveModel;
use Joomla\Component\Users\Site\View\Captive\HtmlView;
use Joomla\Utilities\ArrayHelper;

/**
 * @var HtmlView     $this  View object
 * @var CaptiveModel $model The model
 */
$model = $this->getModel();

$this->getDocument()->getWebAssetManager()
    ->useScript('com_users.two-factor-focus');

?>
<div class="users-mfa-captive tw-rounded-applicant tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card">
    <a
        class="tw-mb-3 tw-group tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular"
        href="<?php echo Route::_('index.php?option=com_users&task=user.logout&' . Factory::getApplication()->getFormToken() . '=1') ?>"
    >
        <span class="material-symbols-outlined tw-mr-1 tw-text-link-regular">navigate_before</span>
        <span class="group-hover:tw-underline"><?php echo Text::_('COM_USERS_MFA_LOGOUT'); ?></span>
    </a>

    <h2 id="users-mfa-title">
        <?php if (!empty($this->renderOptions['help_url'])) : ?>
            <span class="float-end">
        <a href="<?php echo $this->renderOptions['help_url'] ?>"
                class="btn btn-sm btn-secondary"
                target="_blank"
        >
            <span class="icon icon-question-sign" aria-hidden="true"></span>
            <span class="visually-hidden"><?php echo Text::_('JHELP') ?></span>
        </a>
        </span>
        <?php endif;?>
        <?php if (!empty($this->title)) : ?>
            <?php echo $this->title ?> <small> &ndash;
        <?php endif; ?>
        <?php if (!$this->allowEntryBatching) : ?>
            <?php echo $this->escape($this->record->title) ?>
        <?php else : ?>
            <?php echo $this->escape($this->getModel()->translateMethodName($this->record->method)) ?>
        <?php endif; ?>
        <?php if (!empty($this->title)) : ?>
        </small>
        <?php endif; ?>
    </h2>

    <?php if ($this->renderOptions['pre_message']) : ?>
        <div class="users-mfa-captive-pre-message text-muted mb-3">
            <?php echo $this->renderOptions['pre_message'] ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo Route::_('index.php?option=com_users&task=captive.validate&record_id=' . ((int) $this->record->id)) ?>"
            id="users-mfa-captive-form"
            method="post"
            class="form-horizontal"
    >
        <?php echo HTMLHelper::_('form.token') ?>

        <div id="users-mfa-captive-form-method-fields">
            <?php if ($this->renderOptions['field_type'] == 'custom') : ?>
                <?php echo $this->renderOptions['html']; ?>
            <?php endif; ?>
            <div class="tw-flex tw-flex-col tw-mb-2">
                <?php if ($this->renderOptions['label']) : ?>
                <label for="users-mfa-code">
                    <?php echo $this->renderOptions['label'] ?>
                </label>
                <?php endif; ?>
                <div class="tw-w-auto <?php echo $this->renderOptions['label'] ? '' : 'offset-sm-3' ?>">
                    <?php
                    $attributes = array_merge(
                        [
                            'type'         => $this->renderOptions['input_type'],
                            'name'         => 'code',
                            'value'        => '',
                            'placeholder'  => $this->renderOptions['placeholder'] ?? null,
                            'id'           => 'users-mfa-code',
                            'class'        => '!tw-w-auto form-control',
                            'autocomplete' => $this->renderOptions['autocomplete'] ?? 'one-time-code'
                        ],
                        $this->renderOptions['input_attributes']
                    );

                    if (strpos($attributes['class'], 'form-control') === false) {
                        $attributes['class'] .= ' form-control';
                    }
                    ?>
                    <input <?php echo ArrayHelper::toString($attributes) ?>>
                </div>
            </div>
        </div>

        <div id="users-mfa-captive-form-standard-buttons" class="row">
            <div class="tw-flex tw-justify-between tw-items-start tw-w-full tw-p-0">
                <div class="tw-flex-col tw-items-center tw-gap-4">
                    <?php if ($this->record->method === 'email') : ?>
                    <a class="hover:tw-underline tw-cursor-pointer" onclick="window.location.reload()">
	                    <?php echo Text::_('COM_USERS_MFA_RESEND_EMAIL'); ?>
                    </a>
                    <?php endif; ?>
	                <?php if (count($this->records) > 1) : ?>
                        <div id="users-mfa-captive-form-choose-another">
                            <a class="hover:tw-underline tw-cursor-pointer" href="<?php echo Route::_('index.php?option=com_users&view=captive&task=select') ?>">
				                <?php echo Text::_('COM_USERS_MFA_USE_DIFFERENT_METHOD'); ?>
                            </a>
                        </div>
	                <?php endif; ?>
                </div>


                <button class="btn btn-primary <?php echo $this->renderOptions['submit_class'] ?>"
                        id="users-mfa-captive-button-submit"
                        style="<?php echo $this->renderOptions['hide_submit'] ? 'display: none' : '' ?>"
                        type="submit">
		            <?php echo Text::_($this->renderOptions['submit_text']); ?>
                </button>
            </div>
        </div>
    </form>

    <?php if ($this->renderOptions['post_message']) : ?>
        <div class="users-mfa-captive-post-message">
            <?php echo $this->renderOptions['post_message'] ?>
        </div>
    <?php endif; ?>

</div>
