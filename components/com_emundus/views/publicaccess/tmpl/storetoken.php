<?php
/**
 * @package     com_emundus
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2005-2026 eMundus - All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

$token = $this->compositeKey;
?>

<div class="tw-flex tw-items-center tw-justify-center tw-min-h-[60vh] tw-mb-4">
	<div class="emundus-form !tw-p-6 tw-rounded-coordinator-cards tw-shadow-standard tw-border tw-border-neutral-300 !tw-bg-white applicant-form tw-max-w-[500px]">
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

		<!-- Composite key -->
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
					class="tw-px-3 tw-py-2 tw-border tw-border-neutral-300 tw-rounded tw-bg-white hover:tw-bg-neutral-100 tw-transition-colors tw-flex tw-items-center tw-gap-1"
					title="<?php echo Text::_('COM_EMUNDUS_STORETOKEN_COPY'); ?>"
				>
					<span class="material-symbols-outlined tw-text-lg" id="copy-token-icon">content_copy</span>
				</button>
			</div>
		</div>

		<!-- Continue button -->
		<button
			type="button"
			id="continue-btn"
			disabled
			class="tw-w-full tw-btn-primary tw-opacity-50 tw-cursor-not-allowed tw-transition-all"
		>
			<?php echo Text::_('COM_EMUNDUS_STORETOKEN_CONTINUE'); ?>
		</button>

        <?php if (!$this->renew) : ?>
            <!-- allow user to abandon the process the first time he arrives here, leading to file deletion -->

            <hr class="tw-mt-4"/>
            <a
                type="button"
                id="abort-btn"
                class="tw-text-red-500 tw-w-full tw-flex tw-flex-center tw-justify-center tw-items-center tw-gap-2 tw-cursor-pointer"
            >
                <span class="material-symbols-outlined tw-text-red-500">
                  logout
                </span>
                <span class="tw-cursor-pointer"><?php echo Text::_('COM_EMUNDUS_STORETOKEN_ABORT'); ?></span>
            </a>
        <?php endif; ?>
	</div>
</div>