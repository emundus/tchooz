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

    @media (max-width: 767px) {
        .alerte-message-container .em-browser-scroll {
            overflow: hidden;
            white-space: nowrap;
        }

        /* !important pour passer devant le style inline (16pt) du span. */
        .alerte-message-container .em-browser-scroll > span {
            display: inline-block;
            font-size: 14px !important;
            padding-left: 100%;
            animation: em-browser-marquee 15s linear infinite;
        }

        @keyframes em-browser-marquee {
            from { transform: translateX(0); }
            to { transform: translateX(-100%); }
        }

        @media (prefers-reduced-motion: reduce) {
            .alerte-message-container .em-browser-scroll > span {
                animation: none;
                padding-left: 0;
                white-space: normal;
            }
        }
    }
</style>

<?php if (!$compatible) : ?>
    <div class="alerte-message-container tw-text-center tw-w-full tw-bg-red-500" style="padding: 8px 24px;">
        <p class="em-browser-scroll" style="font-weight: 500; color: #fff;">
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
            <p class="em-browser-scroll" style="font-weight: 500; color: #fff;">
                <span style="font-size: 16pt;">
                    <?php echo Text::_('ENABLE_JAVASCRIPT'); ?>
                </span>
            </p>
        </div>
    </noscript>
<?php endif; ?>