<?php
/**
 * @package     com_emundus
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2005-2026 eMundus - All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die('Restricted access');

$token = $this->accessToken;
$fnum  = $this->storetokenFnum;
?>

<div class="tw-flex tw-items-center tw-justify-center tw-min-h-[60vh]">
	<div class="tw-w-full tw-max-w-lg tw-p-8 tw-bg-white tw-rounded-coordinator tw-shadow">

		<!-- Header -->
		<div class="tw-text-center tw-mb-6">
			<span class="material-symbols-outlined tw-text-5xl tw-text-orange-500 tw-mb-2">warning</span>
			<h1 class="tw-text-2xl tw-font-semibold tw-mb-2">
				<?php echo Text::_('COM_EMUNDUS_STORETOKEN_TITLE'); ?>
			</h1>
			<p class="tw-text-neutral-600">
				<?php echo Text::_('COM_EMUNDUS_STORETOKEN_DESC'); ?>
			</p>
		</div>

		<!-- Alert -->
		<div class="tw-mb-6 tw-p-4 tw-bg-orange-50 tw-border tw-border-orange-300 tw-rounded-coordinator tw-flex tw-items-start tw-gap-3">
			<span class="material-symbols-outlined tw-text-orange-600 tw-text-xl tw-mt-0.5">info</span>
			<p class="tw-text-orange-800 tw-text-sm tw-m-0 tw-leading-relaxed">
				<?php echo Text::_('COM_EMUNDUS_STORETOKEN_WARNING'); ?>
			</p>
		</div>

		<!-- File number -->
		<div class="tw-mb-4">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-neutral-700 tw-mb-1">
				<?php echo Text::_('COM_EMUNDUS_STORETOKEN_FNUM_LABEL'); ?>
			</label>
			<div class="tw-flex tw-items-center tw-gap-2">
				<input
					type="text"
					id="storetoken-fnum"
					value="<?php echo $this->escape($fnum); ?>"
					readonly
					class="tw-flex-1 tw-px-4 tw-py-2 tw-border tw-border-neutral-300 tw-rounded tw-bg-neutral-50 tw-font-mono tw-text-sm tw-select-all"
				/>
				<button
					type="button"
					id="copy-fnum-btn"
					onclick="copyToClipboard('storetoken-fnum', 'copy-fnum-btn', 'fnum')"
					class="tw-px-3 tw-py-2 tw-border tw-border-neutral-300 tw-rounded tw-bg-white hover:tw-bg-neutral-100 tw-transition-colors tw-flex tw-items-center tw-gap-1"
					title="<?php echo Text::_('COM_EMUNDUS_STORETOKEN_COPY'); ?>"
				>
					<span class="material-symbols-outlined tw-text-lg" id="copy-fnum-icon">content_copy</span>
				</button>
			</div>
		</div>

		<!-- Access key -->
		<div class="tw-mb-6">
			<label class="tw-block tw-text-sm tw-font-medium tw-text-neutral-700 tw-mb-1">
				<?php echo Text::_('COM_EMUNDUS_STORETOKEN_TOKEN_LABEL'); ?>
			</label>
			<div class="tw-flex tw-items-center tw-gap-2">
				<input
					type="text"
					id="storetoken-token"
					value="<?php echo $this->escape($token); ?>"
					readonly
					class="tw-flex-1 tw-px-4 tw-py-2 tw-border tw-border-neutral-300 tw-rounded tw-bg-neutral-50 tw-font-mono tw-text-sm tw-select-all"
				/>
				<button
					type="button"
					id="copy-token-btn"
					onclick="copyToClipboard('storetoken-token', 'copy-token-btn', 'token')"
					class="tw-px-3 tw-py-2 tw-border tw-border-neutral-300 tw-rounded tw-bg-white hover:tw-bg-neutral-100 tw-transition-colors tw-flex tw-items-center tw-gap-1"
					title="<?php echo Text::_('COM_EMUNDUS_STORETOKEN_COPY'); ?>"
				>
					<span class="material-symbols-outlined tw-text-lg" id="copy-token-icon">content_copy</span>
				</button>
			</div>
		</div>

		<!-- Checklist -->
		<div class="tw-mb-6 tw-p-3 tw-bg-neutral-50 tw-border tw-border-neutral-200 tw-rounded-coordinator">
			<p class="tw-text-sm tw-font-medium tw-text-neutral-700 tw-mb-2 tw-m-0">
				<?php echo Text::_('COM_EMUNDUS_STORETOKEN_CHECKLIST_TITLE'); ?>
			</p>
			<div class="tw-flex tw-items-center tw-gap-2 tw-mb-1">
				<span class="material-symbols-outlined tw-text-lg" id="check-fnum">radio_button_unchecked</span>
				<span class="tw-text-sm tw-text-neutral-600" id="check-fnum-label">
					<?php echo Text::_('COM_EMUNDUS_STORETOKEN_CHECKLIST_FNUM'); ?>
				</span>
			</div>
			<div class="tw-flex tw-items-center tw-gap-2">
				<span class="material-symbols-outlined tw-text-lg" id="check-token">radio_button_unchecked</span>
				<span class="tw-text-sm tw-text-neutral-600" id="check-token-label">
					<?php echo Text::_('COM_EMUNDUS_STORETOKEN_CHECKLIST_TOKEN'); ?>
				</span>
			</div>
		</div>

		<!-- Continue button -->
		<button
			type="button"
			id="continue-btn"
			disabled
			class="tw-w-full tw-btn-primary tw-opacity-50 tw-cursor-not-allowed tw-transition-all"
			onclick="window.location.href='<?php echo Route::_('index.php?option=com_emundus&task=openfile&fnum=' . $this->escape($fnum), false); ?>'"
		>
			<?php echo Text::_('COM_EMUNDUS_STORETOKEN_CONTINUE'); ?>
		</button>

		<p class="tw-text-xs tw-text-neutral-500 tw-text-center tw-mt-4">
			<?php echo Text::_('COM_EMUNDUS_STORETOKEN_HELP'); ?>
		</p>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const copiedItems = { fnum: false, token: false };

	window.copyToClipboard = function(inputId, btnId, itemKey) {
		const input = document.getElementById(inputId);
		const text = input.value;

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function() {
				markCopied(btnId, itemKey);
			}).catch(function() {
				fallbackCopy(input, btnId, itemKey);
			});
		} else {
			fallbackCopy(input, btnId, itemKey);
		}
	};

	function fallbackCopy(input, btnId, itemKey) {
		input.select();
		input.setSelectionRange(0, 99999);
		try {
			document.execCommand('copy');
			markCopied(btnId, itemKey);
		} catch (err) {
			console.error('Copy failed', err);
		}
	}

	function markCopied(btnId, itemKey) {
		// Update button icon
		const iconId = btnId.replace('btn', 'icon');
		const icon = document.getElementById(iconId);
		if (icon) {
			icon.textContent = 'check';
			icon.classList.add('tw-text-green-600');
		}

		// Update checklist
		const checkIcon = document.getElementById('check-' + itemKey);
		const checkLabel = document.getElementById('check-' + itemKey + '-label');
		if (checkIcon) {
			checkIcon.textContent = 'check_circle';
			checkIcon.classList.add('tw-text-green-600');
		}
		if (checkLabel) {
			checkLabel.classList.add('tw-text-green-700', 'tw-font-medium');
		}

		// Track copied items
		copiedItems[itemKey] = true;

		// Enable continue button if both items are copied
		if (copiedItems.fnum && copiedItems.token) {
			const continueBtn = document.getElementById('continue-btn');
			if (continueBtn) {
				continueBtn.disabled = false;
				continueBtn.classList.remove('tw-opacity-50', 'tw-cursor-not-allowed');

                fetch('/index.php?option=com_emundus&task=markPublicAccessKeyAsStored', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo \Joomla\CMS\Session\Session::getFormToken(); ?>'
                    }
                }).catch(function(err) {
                    console.error('Failed to mark token as copied', err);
                });
			}
		}
	}
});
</script>
