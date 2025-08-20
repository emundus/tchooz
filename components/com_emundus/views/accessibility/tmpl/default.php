<?php
/**
 * @version        $Id: default.php 14401 2014-09-16 14:10:00Z brivalland $
 * @package        Joomla
 * @subpackage     Emundus
 * @copyright      Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license        GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

$siteUrl = Uri::base();
$siteName = Factory::getApplication()->get('sitename');
$contactEmail = 'accessibilite@emundus.fr';
?>


<div class="tw-mt-4 tw-mb-4">
    <h1 class="tw-text-center tw-mb-4"><?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_TITLE'); ?></h1>

    <div class="tw-flex tw-flex-col tw-gap-6">
        <div id="declaration">
            <h2 class="tw-mb-3"><?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_TITLE_2'); ?>
                <a href="#declaration" class="tw-hidden"><span class="material-symbols-outlined">tag</span></a>
            </h2>
            <p>
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_INTRODUCTION'); ?>
            </p>
            <br/>

            <!-- Bilan -->
            <div>
                <p>
                    <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_INTRODUCTION_BILAN'); ?>
                </p>
                <ul>
                    <li>
                        <a href="/images/accessibility/schema-pluriannuel-2025-2027.pdf" download><?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_SCHEMA_PLURIANNUEL'); ?></a>
                    </li>
                    <li>
                        <a href="/images/accessibility/schema-pluriannuel-2025-2027.pdf" download><?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_PLAN'); ?></a>
                    </li>
                </ul>
                <br/>
                <p>
                    <?php echo Text::sprintf('COM_EMUNDUS_ACCESSIBILITY_DECLARATION_SITE', $siteName, $siteUrl, $siteUrl); ?>
                </p>
            </div>
        </div>

        <!-- État de conformité -->
        <div id="etat-conformite">
            <h2 class="tw-mb-3"><?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_ETAT_CONFORMITE_TITLE'); ?>
                <a href="#etat-conformite" class="tw-hidden"><span class="material-symbols-outlined">tag</span></a>
            </h2>

            <p class="tw-mt-4">
                <?php echo Text::sprintf('COM_EMUNDUS_ACCESSIBILITY_ETAT_CONFORMITE', $siteName); ?>
            </p>
        </div>

        <!-- Resultats des tests -->
        <div id="tests">
            <h2 class="tw-mb-3">
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_RESULTATS_TESTS_TITLE'); ?>
                <a href="#tests" class="tw-hidden"><span class="material-symbols-outlined">tag</span></a>
            </h2>
            <p>
                <?php echo Text::sprintf('COM_EMUNDUS_ACCESSIBILITY_RESULTATS_TESTS_INTRO', 34); ?>
            </p>
            <ul>
                <li>
                    <?php echo Text::sprintf('COM_EMUNDUS_ACCESSIBILITY_RESULTATS_TESTS_ITEM_1', 17); ?>
                </li>
                <li>
		            <?php echo Text::sprintf('COM_EMUNDUS_ACCESSIBILITY_RESULTATS_TESTS_ITEM_2', 29); ?>
                </li>
                <li>
		            <?php echo Text::sprintf('COM_EMUNDUS_ACCESSIBILITY_RESULTATS_TESTS_ITEM_3', 2); ?>
                </li>
            </ul>
        </div>

        <!-- Contenus non accessible -->
        <div id="contenus-non-accessibles">
            <h2 class="tw-mb-3">
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_CONTENUS_NON_ACCESSIBLES_TITLE'); ?>
                <a href="#contenus-non-accessibles" class="tw-hidden"><span class="material-symbols-outlined">tag</span></a>
            </h2>

            <div class="tw-mt-4">
                <p>
                    <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_CONTENUS_NON_ACCESSIBLES_INTRO'); ?>
                </p>
                <br/>
                <h3 class="tw-mb-1">
                    <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_CONTENUS_NON_ACCESSIBLES_LIST'); ?>
                </h3>
                <p>
                    <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_CONTENUS_NON_ACCESSIBLES_LIST_INTRO'); ?>
                </p>
                <ul>
                    <?php for($i = 1; $i <= 8; $i++): ?>
                        <li>
                            <?php echo Text::sprintf('COM_EMUNDUS_ACCESSIBILITY_CONTENUS_NON_ACCESSIBLES_LIST_ITEM_' . $i); ?>
                        </li>
                    <?php endfor; ?>
                </ul>
                <br/>
                <h3 class="tw-mb-1">
                    <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_CONTENUS_NON_SOUMIS'); ?>
                </h3>
                <ul>
	                <?php for($i = 1; $i <= 2; $i++): ?>
                        <li>
                            <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_CONTENUS_NON_SOUMIS_ITEM_'.$i); ?>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        </div>

        <!-- Etablissement de la déclaration -->
        <div id="etablissement-declaration">
            <h2 class="tw-mb-3">
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_ETABLISSEMENT_DECLARATION_TITLE'); ?>
                <a href="#etablissement-declaration" class="tw-hidden"><span class="material-symbols-outlined">tag</span></a>
            </h2>
            <p>
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_ETABLISSEMENT_DECLARATION_INTRO'); ?>
            </p>
            <br/>
            <h3 class="tw-mb-1">
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_ETABLISSEMENT_DECLARATION_TECHNOLOGIES_TITLE'); ?>
            </h3>
            <ul>
                <li>HTML5</li>
                <li>CSS</li>
                <li>Javascript</li>
                <li>PHP</li>
            </ul>
            <br/>
            <h3 class="tw-mb-1">
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_ETABLISSEMENT_DECLARATION_TOOLS_TEST_TITLE'); ?>
            </h3>
            <ul>
                <li>Wave</li>
                <li>Axe DevTools - Web Accessibility Testing</li>
            </ul>
            <br/>
            <h3 class="tw-mb-1">
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_ETABLISSEMENT_DECLARATION_PAGES_TITLE'); ?>
            </h3>
            <ul>
	            <?php for($i = 1; $i <= 25; $i++): ?>
                    <li>
                        <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_ETABLISSEMENT_DECLARATION_PAGES_ITEM_'.$i); ?>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>

        <!-- Contact -->
        <div id="contact">
            <h2 class="tw-mb-3">
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_CONTACT_TITLE'); ?>
                <a href="#contact" class="tw-hidden"><span class="material-symbols-outlined">tag</span></a>
            </h2>
            <p>
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_CONTACT_INTRO'); ?>
            </p>
            <br/>
            <ul>
                <li>
                    <?php echo Text::sprintf('COM_EMUNDUS_ACCESSIBILITY_CONTACT_EMAIL', $contactEmail, $contactEmail); ?>
                </li>
            </ul>
        </div>

        <!-- Voie de recours -->
        <div id="voie-de-recours">
            <h2 class="tw-mb-3">
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_VOIE_RECOURS_TITLE'); ?>
                <a href="#voie-de-recours" class="tw-hidden"><span class="material-symbols-outlined">tag</span></a>
            </h2>
            <p>
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_VOIE_RECOURS_INTRO'); ?>
            </p>
            <br/>
            <p>
                <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_VOIE_RECOURS_DETAILS'); ?>
            </p>
            <ul>
	            <?php for($i = 1; $i <= 4; $i++): ?>
                    <li>
                        <?php echo Text::_('COM_EMUNDUS_ACCESSIBILITY_VOIE_RECOURS_ITEM_'.$i); ?>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    </div>
</div>

<script type="application/javascript">
    window.addEventListener('load', function() {
        if (window.location.hash) {
            const header = document.querySelector('#g-navigation'); // Remplacez 'header' par le sélecteur de votre header sticky
            let headerHeight = header ? header.offsetHeight : 0;
            const element = document.querySelector(window.location.hash);

            const banner = document.querySelector('.alerte-message-container');
            if(banner)
            {
                // Si une bannière est présente, on ajoute sa hauteur au décalage
                const bannerHeight = banner.offsetHeight;
                headerHeight += bannerHeight;
            }

            headerHeight += 20;

            if (element) {
                // Décale le scroll de la hauteur du header
                const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
                const offsetPosition = elementPosition - headerHeight;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        }
    });
</script>
