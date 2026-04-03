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
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

$baseUrl = Uri::base();
?>

<div class="tw-flex tw-items-center tw-justify-center tw-min-h-[60vh]">
	<div class="emundus-form !tw-p-6 tw-rounded-coordinator-cards tw-shadow-standard tw-border tw-border-neutral-300 !tw-bg-white applicant-form">

		<?php if ($this->isAlreadyAuthenticated) : ?>
			<!-- Already authenticated: redirect to the file -->
			<div class="tw-text-center">
				<span class="material-symbols-outlined tw-text-5xl tw-text-green-500 tw-mb-4">check_circle</span>
				<h2 class="tw-text-xl tw-font-semibold tw-mb-2">
					<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_ALREADY_AUTHENTICATED'); ?>
				</h2>
				<p class="tw-text-neutral-600 tw-mb-6">
					<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_ALREADY_AUTHENTICATED_DESC'); ?>
				</p>
				<a href="<?php echo Route::_('/index.php?option=com_emundus&task=openfile&fnum=' . $this->escape($this->fnum)); ?>"
				   class="tw-btn-primary tw-mt-4">
					<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_OPEN_FILE'); ?>
				</a>
			</div>

		<?php else : ?>
			<!-- Token entry form -->
			<div class="tw-text-center tw-mb-6">
				<span class="material-symbols-outlined tw-text-5xl tw-text-profile-color tw-mb-2">key</span>
				<h1 class="tw-text-2xl tw-font-semibold tw-mb-2">
					<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_TITLE'); ?>
				</h1>
				<p class="tw-text-neutral-600">
					<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_DESC'); ?>
				</p>
			</div>

			<?php if ($this->hasError) : ?>
				<div class="tw-mb-4 tw-p-3 tw-bg-red-50 tw-border tw-border-red-200 tw-rounded tw-flex tw-items-start tw-gap-2">
					<span class="material-symbols-outlined tw-text-red-500 tw-text-lg tw-mt-0.5">error</span>
					<p class="tw-text-red-700 tw-text-sm tw-m-0">
						<?php echo !empty($this->errorMessage)
							? $this->escape($this->errorMessage)
							: Text::_('COM_EMUNDUS_PUBLIC_ACCESS_INVALID_TOKEN'); ?>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo Route::_('index.php?option=com_emundus&task=authenticatepublicaccess'); ?>" id="publicAccessForm">
				<?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>

				<!-- Access token -->
				<div class="tw-mb-6">
					<label for="pa_access_token" class="tw-block tw-text-sm tw-font-medium tw-text-neutral-700 tw-mb-1">
						<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_TOKEN_LABEL'); ?>
					</label>
					<input
						type="password"
						id="pa_access_token"
						name="access_token"
						required
						autocomplete="off"
						spellcheck="false"
						placeholder="<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_TOKEN_PLACEHOLDER'); ?>"
					/>
				</div>

				<button
					type="submit"
					class="tw-w-full tw-btn-primary">
					<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_SUBMIT'); ?>
				</button>
			</form>

			<p class="tw-text-xs tw-text-neutral-500 tw-text-center tw-mt-4">
				<?php echo Text::_('COM_EMUNDUS_PUBLIC_ACCESS_HELP'); ?>
			</p>
		<?php endif; ?>

	</div>
</div>


