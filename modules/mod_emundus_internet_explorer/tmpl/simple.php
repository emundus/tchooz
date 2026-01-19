<?php

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;
header('Content-Type: text/html; charset=utf-8');
?>
<style>
    .alerte-message-container a {
        font-size: inherit;
        color: white;
        text-decoration: underline;
    }

    .alerte-message-container a:hover {
        color: var(--blue-500);
    }
</style>

<?php if (!$compatible) : ?>
    <div class="alerte-message-container tw-text-center tw-w-full tw-bg-red-500" style="padding: 8px 24px;">
        <p style="font-weight: 500; color: #fff;">
        <span style="font-size: 16pt;">
            <?php echo Text::_($message); ?>
            <noscript>
                <?php echo Text::_('ENABLE_JAVASCRIPT'); ?>
            </noscript>
        </span>
        </p>
    </div>
<?php else : ?>
    <noscript>
        <div class="alerte-message-container tw-text-center tw-w-full tw-bg-red-500" style="padding: 8px 24px;">
            <p style="font-weight: 500; color: #fff;">
                <span style="font-size: 16pt;">
                    <?php echo Text::_('ENABLE_JAVASCRIPT'); ?>
                </span>
            </p>
        </div>
    </noscript>
<?php endif; ?>