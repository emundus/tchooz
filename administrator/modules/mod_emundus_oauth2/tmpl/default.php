<?php

/**
 * @package         Joomla.Administrator
 * @subpackage      mod_version
 *
 * @copyright   (C) 2012 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

?>
<?php if (!empty($active_directories)) : ?>
<style>
    .sso-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 32px;
        width: 100%;
    }
    .sso-block__directories {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
        width: 100%;
    }
    .sso-block__directories-directory {
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #000;
        padding: 8px;
        border-radius: 4px;
        color: #000;
        gap: 8px;
        width: 100%;
    }
    .sso-block__directories-directory:hover {
        background-color: #f0f0f0;
    }
    .tw-w-full {
        width: 100%;
    }
    .tw-h-full {
        height: 100%;
    }
</style>
    <div class="sso-block">
        <div class="sso-block__directories">
			<?php foreach ($active_directories as $configuration) : ?>
                <a class="sso-block__directories-directory"
                   href="<?php echo $configuration->auth_url; ?>?response_type=code&client_id=<?php echo $configuration->client_id; ?>&scope=<?php echo str_replace(',', '+', $configuration->scopes); ?>&redirect_uri=<?php echo $configuration->redirect_url ?>&state=<?php echo $state; ?>&nonce=<?php echo $nonce; ?>&type=<?php echo $configuration->type; ?>&kc_idp_hint=<?php echo $configuration->type; ?>">
					<?php if ($configuration->button_type == 'google') : ?>
                        <div style="width: 20px;height: 20px">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
                                <path fill="#EA4335"
                                      d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                                <path fill="#4285F4"
                                      d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                                <path fill="#FBBC05"
                                      d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                                <path fill="#34A853"
                                      d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                                <path fill="none" d="M0 0h48v48H0z"></path>
                            </svg>
                        </div>
					<?php elseif ($configuration->button_type == 'custom' && !empty($configuration->button_icon)) : ?>
                        <div style="width: 20px;height: 20px">
							<?php echo HTMLHelper::_('image', $configuration->button_icon, '', ['class' => 'tw-w-full tw-h-full']); ?>
                        </div>
					<?php endif; ?>
					<?php echo Text::_($configuration->button_label); ?>
                </a>
			<?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
