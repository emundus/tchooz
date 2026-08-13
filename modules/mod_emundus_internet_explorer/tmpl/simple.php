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

    .alerte-message-container .em-browser-copy + .em-browser-copy {
        display: none;
    }

    @media (max-width: 767px) {
        .alerte-message-container .em-browser-scroll span {
            display: inline-block;
            font-size: 14px !important;
        }

        .alerte-message-container .em-browser-scroll.is-scrolling {
            overflow: hidden;
            white-space: nowrap;
            text-align: left;
        }

        .alerte-message-container .em-browser-scroll.is-scrolling .em-browser-copy,
        .alerte-message-container .em-browser-scroll.is-scrolling .em-browser-copy + .em-browser-copy {
            display: inline-block;
            margin-right: 48px;
        }

        .alerte-message-container .em-browser-scroll.is-scrolling .em-browser-track {
            display: inline-block;
            animation: em-browser-marquee var(--em-browser-duration, 15s) linear infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .alerte-message-container .em-browser-scroll.is-scrolling {
                overflow: visible;
                white-space: normal;
                text-align: center;
            }

            .alerte-message-container .em-browser-scroll.is-scrolling .em-browser-track {
                animation: none;
            }

            .alerte-message-container .em-browser-scroll.is-scrolling .em-browser-copy {
                margin-right: 0;
            }

            .alerte-message-container .em-browser-scroll.is-scrolling .em-browser-copy + .em-browser-copy {
                display: none;
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

<script>
    (function () {
        var PAUSE = 3;    // secondes d'immobilite avant chaque depart
        var SPEED = 70;   // pixels par seconde, constant quelle que soit la longueur
        var GAP   = 48;   // ecart entre la fin du texte et sa reprise

        var banner = document.querySelector('.em-browser-scroll');

        // Sans support des variables CSS (IE), on sort : le texte reste sur
        // plusieurs lignes plutot que d'etre coupe sans jamais defiler.
        if (!banner || !window.CSS || !CSS.supports || !CSS.supports('--a', '0px')) {
            return;
        }

        var text = banner.querySelector('span');
        if (!text) {
            return;
        }

        // Le texte est double : quand le premier exemplaire a fini de sortir a
        // gauche, le second occupe exactement sa place de depart. Le retour a
        // zero est donc invisible, et le texte semble revenir par la droite.
        var track = document.createElement('span');
        track.className = 'em-browser-track';

        var copy = document.createElement('span');
        copy.className = 'em-browser-copy';
        text.parentNode.insertBefore(track, text);
        copy.appendChild(text);

        var clone = copy.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        track.appendChild(copy);
        track.appendChild(clone);

        var keyframes = document.createElement('style');
        document.head.appendChild(keyframes);

        function refresh() {
            banner.classList.remove('is-scrolling');

            // Mesure forcee sur une ligne, sinon le texte revient a la ligne et
            // ne deborde jamais : on ne detecterait aucun debordement.
            banner.style.whiteSpace = 'nowrap';
            var textWidth = copy.offsetWidth;
            banner.style.whiteSpace = '';

            if (textWidth <= banner.clientWidth) {
                return;
            }

            var distance = textWidth + GAP;
            var duration = PAUSE + (distance / SPEED);
            var pausePercent = (PAUSE / duration) * 100;

            keyframes.textContent =
                '@keyframes em-browser-marquee{'
                + '0%,' + pausePercent.toFixed(2) + '%{transform:translateX(0)}'
                + '100%{transform:translateX(-' + distance + 'px)}}';

            banner.style.setProperty('--em-browser-duration', duration.toFixed(2) + 's');
            banner.classList.add('is-scrolling');
        }

        document.addEventListener('DOMContentLoaded', refresh);
        // Les polices peuvent changer la largeur du texte apres DOMContentLoaded.
        window.addEventListener('load', refresh);
        window.addEventListener('resize', refresh);
    })();
</script>