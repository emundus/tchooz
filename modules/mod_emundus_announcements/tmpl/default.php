<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_custom
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$class = 'tw-bg-red-600';
if($announcement_type === 'info') {
    $class = 'tw-bg-blue-600';
} elseif($announcement_type === 'warning') {
    $class = 'tw-bg-orange-500';
}
?>

<?php if($announcement_type === 'urgency') : ?>
<style>
    @media (max-width: 767px) {
        .alerte-message-container .em-announcement-scroll span {
            display: inline-block;
            font-size: 14px !important;
        }

        .alerte-message-container .em-announcement-copy + .em-announcement-copy {
            display: none;
        }

        .alerte-message-container .em-announcement-scroll.is-scrolling {
            overflow: hidden;
            white-space: nowrap;
            text-align: left;
        }

        .alerte-message-container .em-announcement-scroll.is-scrolling .em-announcement-copy,
        .alerte-message-container .em-announcement-scroll.is-scrolling .em-announcement-copy + .em-announcement-copy {
            display: inline-block;
            margin-right: 48px;
        }

        .alerte-message-container .em-announcement-scroll.is-scrolling .em-announcement-track {
            display: inline-block;
            animation: em-announcement-marquee var(--em-announcement-duration, 15s) linear infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .alerte-message-container .em-announcement-scroll.is-scrolling {
                overflow: visible;
                white-space: normal;
                text-align: center;
            }

            .alerte-message-container .em-announcement-scroll.is-scrolling .em-announcement-track {
                animation: none;
            }

            .alerte-message-container .em-announcement-scroll.is-scrolling .em-announcement-copy {
                margin-right: 0;
            }

            .alerte-message-container .em-announcement-scroll.is-scrolling .em-announcement-copy + .em-announcement-copy {
                display: none;
            }
        }
    }
</style>
<?php endif; ?>

<div class="alerte-message-container tw-text-center tw-w-full <?php echo $class; ?>" style="padding: 8px 24px;">
    <p <?php if($announcement_type === 'urgency') : ?>class="em-announcement-scroll"<?php endif; ?> style="font-weight: 500; color: #fff;">
        <span style="font-size: 16pt;"><?php echo $announcement_content ?></span>
    </p>
    <span id="close-preprod-alerte-container" aria-hidden="true" class="material-symbols-outlined em-pointer"
          style="color:white;position:absolute;top:10px;right:5px;">close</span>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        let sidebar_menu = document.querySelector('#g-navigation,#g-header #header-b');
        if(sidebar_menu) {
            sidebar_menu.style.top = document.getElementsByClassName('alerte-message-container')[0].offsetHeight + 'px';
        }

        let switch_menu_icon = document.querySelector('.switch-sidebar-icon');
        if(switch_menu_icon) {
            switch_menu_icon.style.top = (document.getElementsByClassName('alerte-message-container')[0].offsetHeight*1.6) + 'px';
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target.id === 'close-preprod-alerte-container') {
            document.querySelector('.alerte-message-container').classList.add('hidden');
            let navigation = document.querySelector('#g-navigation, #g-header');
            if(navigation) {
                navigation.style.top = '0';
            }
        }
    });

    (function () {
        var PAUSE = 3;    // secondes d'immobilite avant chaque depart
        var SPEED = 70;   // pixels par seconde, constant quelle que soit la longueur
        var GAP   = 48;   // ecart entre la fin du texte et sa reprise

        var banner = document.querySelector('.em-announcement-scroll');
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
        track.className = 'em-announcement-track';

        var copy = document.createElement('span');
        copy.className = 'em-announcement-copy';
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
                '@keyframes em-announcement-marquee{'
                + '0%,' + pausePercent.toFixed(2) + '%{transform:translateX(0)}'
                + '100%{transform:translateX(-' + distance + 'px)}}';

            banner.style.setProperty('--em-announcement-duration', duration.toFixed(2) + 's');
            banner.classList.add('is-scrolling');
        }

        document.addEventListener('DOMContentLoaded', refresh);
        // Les polices peuvent changer la largeur du texte apres DOMContentLoaded.
        window.addEventListener('load', refresh);
        window.addEventListener('resize', refresh);
    })();
</script>
