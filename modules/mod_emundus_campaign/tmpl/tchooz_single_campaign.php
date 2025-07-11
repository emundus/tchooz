<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

header('Content-Type: text/html; charset=utf-8');

$app = Factory::getApplication();

$config   = $app->getConfig();
$session  = $app->getSession();
$db       = Factory::getContainer()->get('DatabaseDriver');
$user     = $app->getIdentity();
$document = $app->getDocument();
$wa       = $document->getWebAssetManager();
$lang_tag = $app->getLanguage()->getTag();

if ($lang_tag == "fr-FR")
{
	setlocale(LC_TIME, 'fr', 'fr_FR', 'french', 'fra', 'fra_FRA', 'fr_FR.ISO_8859-1', 'fra_FRA.ISO_8859-1', 'fr_FR.utf8', 'fr_FR.utf-8', 'fra_FRA.utf8', 'fra_FRA.utf-8');
}
else
{
	setlocale(LC_ALL, 'en_GB');
}

$site_offset = $config->get('offset');
$site_name   = $config->get('sitename');

$protocol   = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$CurPageURL = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$currentCampaign = is_array($allCampaign) ? $allCampaign[0] : $allCampaign;
$dteStart        = new DateTime($now);
$dteEnd          = new DateTime($currentCampaign->end_date);
$dteDiff         = $dteStart->diff($dteEnd);
$j               = $dteDiff->format("%a");
$h               = $dteDiff->format("%H");

if (empty($currentCampaign))
{
	$app->enqueueMessage(JText::_('MOD_EM_CAMPAIGN_NOT_ACCESSIBLE'));
	$app->redirect('index.php');
}

$can_apply = 0;
if (strtotime($now) < strtotime($currentCampaign->end_date) && strtotime($now) > strtotime($currentCampaign->start_date))
{
	$can_apply = 1;
}
elseif (strtotime($now) > strtotime($currentCampaign->end_date))
{
	$can_apply = -1;
}

if ($currentCampaign->campaign_is_limited == 1 && $currentCampaign->campaign_limit > 0)
{
	$files_sent = min($currentCampaign->nb_files_in_limit, $currentCampaign->campaign_limit);
	if ($files_sent == 1)
	{
		$files_sent_tag = 'MOD_EM_CAMPAIGN_CAMPAIGN_SENT_NUMBER_SINGULAR';
	}
	else
	{
		$files_sent_tag = 'MOD_EM_CAMPAIGN_CAMPAIGN_SENT_NUMBER_PLURAL';
	}

	$is_limit_obtained = $currentCampaign->nb_files_in_limit >= $currentCampaign->campaign_limit;
}

if ($currentCampaign->apply_online == 0)
{
	$can_apply = 0;
}

$campaigns_url = EmundusHelperMenu::getHomepageLink();
if(!empty($mod_em_campaign_go_back_campaigns_link)) {
	$menu = Factory::getApplication()->getMenu();
	$campaigns_url = $user->guest ? EmundusHelperMenu::getHomepageLink() : $menu->getItems('id', (int) $mod_em_campaign_go_back_campaigns_link, true)->route;
}

$have_registration_steps = false;
foreach ($mod_em_campaign_show_registration_steps as $step)
{
    if($step->mod_em_campaign_show_registration_steps_text != '')
    {
        $have_registration_steps = true;
    }
}
?>

<div class="mod_emundus_campaign__grid em-mt-24 em-mb-64" style="grid-gap: 64px">
    <div>
        <button type="button" class="tw-text-link-regular tw-cursor-pointer tw-font-semibold tw-flex tw-items-center tw-group em-mb-12 em-w-max-content"
		    <?php if($mod_em_campaign_go_back_link == 1) : ?>
                onclick="history.go(-1)"
		    <?php elseif($mod_em_campaign_go_back_link == 2) : ?>
                onclick="window.location.href='<?php echo $campaigns_url; ?>'"
		    <?php elseif($mod_em_campaign_go_back_link == 3) : ?>
                onclick="window.location.href='<?php echo $mod_em_campaign_go_back_external_url; ?>'"
		    <?php endif; ?>
        >
            <span class="material-symbols-outlined tw-mr-1 tw-text-link-regular" aria-hidden="true">navigate_before</span>
            <span class="group-hover:tw-underline"><?php echo JText::_('MOD_EM_CAMPAIGN_BACK'); ?></span>
        </button>
		<?php if ($mod_em_campaign_details_show_programme == 1) : ?>
			<?php
			$color      = '#0A53CC';
			$background = '#C8E1FE';
			if (!empty($currentCampaign->tag_color))
			{
				$color = $currentCampaign->tag_color;
				switch ($currentCampaign->tag_color)
				{
					case '#106949':
						$background = '#DFF5E9';
						break;
					case '#C31924':
						$background = '#FFEEEE';
						break;
					case '#FFC633':
						$background = '#FFFBDB';
						break;
				}
			}
			?>
            <p class="em-programme-tag" title="<?php echo $currentCampaign->programme ?>"
               style="color: <?php echo $color ?>;">
				<?php echo $currentCampaign->programme; ?>
            </p>
		<?php endif; ?>
        <h1 class="mod_emundus_campaign__campaign_title em-mt-16"
            style="max-height: unset"><?php echo $currentCampaign->label; ?></h1>
        <div class="em-grid-small em-mt-8 em-mt-8">
			<?php if ($mod_em_campaign_show_camp_start_date && $currentCampaign->start_date != '0000-00-00 00:00:00') : ?>
                <div class="em-flex-row" style="white-space: nowrap;">
                    <p class="em-text-neutral-600 em-flex-row em-applicant-default-font "><span
                                class="material-symbols-outlined em-mr-8" aria-hidden="true">alarm</span> <?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_START_DATE'); ?>
                    </p>
                    <p class="em-text-neutral-600 em-ml-4 em-camp-start em-applicant-default-font "><?php echo JFactory::getDate(new JDate($currentCampaign->start_date, $site_offset))->format($mod_em_campaign_date_format); ?></p>
                </div>
			<?php endif; ?>

			<?php if ($mod_em_campaign_show_camp_end_date && $currentCampaign->end_date != '0000-00-00 00:00:00') : ?>
                <div class="em-flex-row" style="white-space: nowrap;">
                    <p class="em-text-neutral-600 em-flex-row em-applicant-default-font "><span
                                class="material-symbols-outlined em-mr-8" aria-hidden="true">schedule</span> <?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_END_DATE'); ?>
                    </p>
                    <p class="em-text-neutral-600 em-ml-4 em-camp-end em-applicant-default-font "><?php echo JFactory::getDate(new JDate($currentCampaign->end_date, $site_offset))->format($mod_em_campaign_date_format); ?></p>
                </div>
			<?php endif; ?>

			<?php if ($mod_em_campaign_show_formation_start_date && $currentCampaign->formation_start !== '0000-00-00 00:00:00') : ?>
                <div class="em-flex-row" style="white-space: nowrap;">
                    <p class="em-text-neutral-600 em-flex-row em-applicant-default-font"><span
                                class="material-symbols-outlined em-mr-8" aria-hidden="true">alarm</span> <?php echo JText::_('MOD_EM_CAMPAIGN_FORMATION_START_DATE'); ?>
                        :</p>
                    <p class="em-text-neutral-600 em-ml-4 em-formation-start em-applicant-default-font "><?php echo JFactory::getDate(new JDate($currentCampaign->formation_start, $site_offset))->format($mod_em_campaign_date_format); ?></p>
                </div>
			<?php endif; ?>

			<?php if ($mod_em_campaign_show_formation_end_date && $currentCampaign->formation_end !== '0000-00-00 00:00:00') : ?>
                <div class="em-flex-row" style="white-space: nowrap;">
                    <p class="em-applicant-text-color em-flex-row"><span
                                class="material-symbols-outlined em-mr-8" aria-hidden="true">schedule</span> <?php echo JText::_('MOD_EM_CAMPAIGN_FORMATION_END_DATE'); ?>
                        :</p>
                    <p class="em-ml-4 em-formation-end"><?php echo JFactory::getDate(new JDate($currentCampaign->formation_end, $site_offset))->format($mod_em_campaign_date_format); ?></p>
                </div>
			<?php endif; ?>

			<?php if ($mod_em_campaign_show_admission_start_date && !empty($currentCampaign->admission_start_date) && $currentCampaign->admission_start_date !== '0000-00-00 00:00:00') : ?>
                <div class="em-flex-row" style="white-space: nowrap;">
                    <p class="em-text-neutral-600 em-flex-row"><span
                                class="material-symbols-outlined em-mr-8" aria-hidden="true">alarm</span> <?php echo JText::_('MOD_EM_CAMPAIGN_ADMISSION_START_DATE'); ?>
                        :</p>
                    <p class="em-text-neutral-600 em-ml-4 em-formation-start"><?php echo JFactory::getDate(new JDate($currentCampaign->admission_start_date, $site_offset))->format($mod_em_campaign_date_format); ?></p>
                </div>
			<?php endif; ?>

			<?php if ($mod_em_campaign_show_admission_end_date && !empty($currentCampaign->admission_end_date) && $currentCampaign->admission_end_date !== '0000-00-00 00:00:00') : ?>
                <div class="em-flex-row" style="white-space: nowrap;">
                    <p class="em-text-neutral-600 em-flex-row"><span
                                class="material-symbols-outlined em-mr-8" aria-hidden="true">schedule</span> <?php echo JText::_('MOD_EM_CAMPAIGN_ADMISSION_END_DATE'); ?>
                        :</p>
                    <p class="em-text-neutral-600 em-ml-4 em-formation-end"><?php echo JFactory::getDate(new JDate($currentCampaign->admission_end_date, $site_offset))->format($mod_em_campaign_date_format); ?></p>
                </div>
			<?php endif; ?>
        </div>
		<?php if (!empty($mod_em_campaign_show_timezone)) : ?>
            <div class="em-mt-4 em-text-neutral-600 em-flex-row em-camp-time-zone">
                <p class="em-flex-row"><span class="material-symbols-outlined em-mr-8" aria-hidden="true">public</span></p>
                <p class="em-text-neutral-600 em-applicant-default-font"><?php echo JText::_('MOD_EM_CAMPAIGN_TIMEZONE') . $offset ?></p>
            </div>
		<?php endif; ?>

		<?php if ($mod_em_campaign_show_faq == 1 && !empty($faq_articles)) : ?>
        <div class="mod_emundus_campaign__tabs em-flex-row">
            <a class="em-applicant-text-color current-tab em-mr-24" onclick="displayTab('campaign')" id="campaign_tab">
                <span><?php echo JText::_('MOD_EM_CAMPAIGN_DETAILS') ?></span>
            </a>
	        <?php if ($mod_em_campaign_show_faq == 1 && !empty($faq_articles)) : ?>
            <a class="em-applicant-text-color" onclick="displayTab('faq')" id="faq_tab">
                <span><?php echo JText::_('MOD_EM_CAMPAIGN_FAQ') ?></span>
            </a>
	        <?php endif; ?>
        </div>
		<?php endif; ?>

        <div class="g-block size-100 tchooz-single-campaign">
            <div class="single-campaign" id="campaign">
				<?php if ($showprogramme) : ?>
                    <div class="em-mt-16 em-w-100">
                        <div><?php echo $currentCampaign->notes ?></div>
                    </div>
				<?php endif; ?>
				<?php if ($showcampaign) : ?>
                    <div class="em-mt-16 em-w-100">
                        <div><?php echo $currentCampaign->description ?></div>
                    </div>
				<?php endif; ?>
            </div><!-- Close campaign-content -->

			<?php if ($mod_em_campaign_show_faq == 1 && !empty($faq_articles)) : ?>
                <div id="faq">
					<?php foreach ($faq_articles as $article) : ?>
                        <h2> <?php echo $article->title ?></h2>
                        <p> <?php echo $article->introtext ?></p>
                        <hr>
					<?php endforeach; ?>
                </div>
			<?php endif; ?>
        </div>
    </div>

    <aside id="campaign-sidebar" class="tw-sticky">
        <!-- INFO BLOCK -->
		<?php if ($can_apply != 0 || ($mod_em_campaign_show_registration == 1 && $have_registration_steps)) : ?>
            <div class="mod_emundus_campaign__details_content em-border-neutral-300 em-mb-24">

				<?php if ($mod_em_campaign_display_svg == 1) : ?>
                    <div id="background-shapes" alt="<?= JText::_('MOD_EM_CAMPAIGN_IFRAME') ?>"></div>
				<?php endif; ?>

                <h2 class="em-mb-24"><?php echo JText::_('MOD_EM_CAMPAIGN_DETAILS_APPLY') ?></h2>
	            <?php  if ($can_apply == 1 && $currentCampaign->is_limited == 1 && $currentCampaign->limit > 0 && $mod_em_campaign_show_limit_files == 1) : ?>
                    <div class="flex em-flex-center em-mb-24"><p class="mr-2 h-max em-p-5-12 em-font-weight-600 em-text-neutral-300 em-font-size-14 em-border-radius tw-text-center" style="background:var(--bg-3);"><?= $files_sent.' '.JText::_($files_sent_tag).' '.$currentCampaign->limit ?></p></div>
	            <?php endif; ?>
				<?php if ($mod_em_campaign_show_registration == 1 && !empty($mod_em_campaign_show_registration_steps)) : ?>
                    <div class="em-mt-24">
						<?php $index = 1; ?>
						<?php foreach ($mod_em_campaign_show_registration_steps as $key => $step): ?>
							<?php if ($step->mod_em_campaign_show_registration_steps_text == '') continue; ?>
                            <span class="em-applicant-text-color em-flex-row em-font-size-14 em-mb-16 em-profile-font"><span
                                        class="mod_emundus_campaign__details_step_count"><?php echo $index ?></span><?php echo $step->mod_em_campaign_show_registration_steps_text ?></span>
							<?php $index++; ?>
						<?php endforeach; ?>
                    </div>
				<?php endif; ?>
				<?php if ($can_apply == 1) : ?>
					<?php
					// The register URL does not work  with SEF, this workaround helps counter this.
					if ($sef == 0)
					{
						if (!isset($redirect_url) || empty($redirect_url))
						{
							$redirect_url = "index.php?option=com_users&view=registration";
						}
						$register_url = $redirect_url . "&course=" . $currentCampaign->code . "&cid=" . $currentCampaign->id;
					}
					else
					{
                        // Parse redirect URL to ensure it is correct
						$parsedUrl = parse_url($redirect_url);
						parse_str($parsedUrl['query'] ?? '', $params);
                        $params['course'] = $currentCampaign->code;
                        $params['cid'] = $currentCampaign->id;
						$newQuery = http_build_query($params);

                        if($parsedUrl['scheme'])
                        {
	                        $register_url =
		                        ($parsedUrl['scheme'] ?? 'http') . '://' .
		                        ($parsedUrl['host'] ?? '') .
		                        (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '') .
		                        ($parsedUrl['path'] ?? '') .
		                        '?' . $newQuery;
                        }
                        else {
                            $register_url = $redirect_url . '?' . $newQuery;
                        }
                    }
					if (!empty($mod_em_campaign_itemid))
					{
						$register_url .= "&Itemid=" . $mod_em_campaign_itemid;
					}
					if (!$user->guest && !empty($formUrl))
					{
						$register_url .= "&redirect=" . $formUrl;
					}
					if ($is_limit_obtained) : ?>
                        <button class="em-disabled-button em-w-100" role="button" data-toggle="sc-modal"><?= JText::_('MOD_EM_CAMPAIGN_DETAILS_LIMIT_OBTAINED'); ?></button>
					<?php else : ?>
                    <a class="btn btn-primary em-w-100 em-applicant-default-font"
                       href='<?php echo $register_url; ?>'
                       data-toggle="sc-modal"><?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_APPLY_NOW'); ?></a>
					<?php endif; ?>
				<?php elseif ($can_apply == -1) : ?>
                    <button class="em-disabled-button em-w-100" role="button"
                            data-toggle="sc-modal"><?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_IS_FINISH'); ?></button>
				<?php endif; ?>
            </div>
		<?php endif; ?>

        <!-- ATTACHMENTS BLOCK -->
		<?php if (!empty($files) && $mod_em_campaign_show_documents == 1) : ?>
            <div class="mod_emundus_campaign__details_content em-border-neutral-300 em-mb-24">

				<?php if ($mod_em_campaign_display_svg == 1) : ?>
                    <div id="background-shapes" alt="<?= JText::_('MOD_EM_CAMPAIGN_IFRAME') ?>"></div>
				<?php endif; ?>

                <h2 class="em-mb-24"><?php echo JText::_('MOD_EM_CAMPAIGN_DETAILS_DOWNLOADS') ?></h2>
                <div class="em-mt-24">
					<?php foreach ($files as $file) : ?>
                        <div class="em-flex-row em-mb-16 mod_emundus_campaign__details_file">
                            <span class="material-symbols-outlined mod_emundus_campaign__details_file_icon" aria-hidden="true">insert_drive_file</span>
                            <a href="files/<?php echo $file->catid . "/" . $file->title_category . "/" . $file->id . "/" . $file->title_file . "." . $file->ext; ?>"
                               target="_blank" rel="noopener noreferrer">
								<?php echo $file->title_file . "." . $file->ext; ?>
                            </a>
                        </div>
					<?php endforeach; ?>
                </div>
            </div>
		<?php endif; ?>

		<?php if (!empty($contact) && $mod_em_campaign_show_contact == 1) : ?>
            <div class="mod_emundus_campaign__details_content em-border-neutral-300">
                <h4><?php echo JText::_('MOD_EM_CAMPAIGN_DETAILS_CONTACT') ?></h4>
            </div>
		<?php endif; ?>
    </aside>
</div>

<script>
    var current_tab = 'campaign';

    window.onload = function () {
        // Set sidebar sticky depends on height of header
        const headerNav = document.getElementById('g-navigation');
        const sidebar = document.querySelector('aside#campaign-sidebar');
        if (headerNav && sidebar) {
            sidebar.style.top = headerNav.offsetHeight + 8 + 'px';
        }
        if(document.getElementById('campaign_tab')) {
            document.getElementById('campaign_tab').classList.add('current-tab');
        }

		<?php if (is_array($modules_tabs) && in_array('faq', $modules_tabs)) : ?>
        document.getElementById('faq').style.display = 'none';
		<?php endif; ?>

		<?php if (is_array($modules_tabs) && in_array('documents', $modules_tabs)) : ?>
        document.getElementById('documents').style.display = 'none';
        if (typeof document.getElementsByClassName('campaign-documents')[0] != 'undefined') {
            document.getElementsByClassName('campaign-documents')[0].parentElement.style.display = 'none';
        }
		<?php endif; ?>
    }

    function displayTab(tab) {
        switch (tab) {
            case 'campaign':
                if (current_tab === 'faq') {
                    document.getElementById('faq').style.display = 'none';
                    document.getElementById('faq_tab').classList.remove('current-tab');
                } else if (current_tab === 'documents') {
                    document.getElementById('documents').style.display = 'none';
                    document.getElementById('documents_tab').classList.remove('current-tab');
                }
                break;
            case 'faq':
                if (current_tab === 'campaign') {
                    document.getElementById('campaign').style.display = 'none';
                    document.getElementById('campaign_tab').classList.remove('current-tab');
                } else if (current_tab === 'documents') {
                    document.getElementById('documents').style.display = 'none';
                    document.getElementById('documents_tab').classList.remove('current-tab');
                }
                break;
            default:
                break;
        }
        current_tab = tab;
        var section = document.getElementById(tab);
        var tab_div = document.getElementById(tab + '_tab');
        section.style.display === 'none' ? tab_div.classList.add('current-tab') : '';
        section.style.display === 'none' ? section.style.display = 'flex' : '';
    }

    /* Couleur des cards "candidater" des campagnes clôturées */
    let buttonElement = document.querySelector(".mod_emundus_campaign__details_content button");

    if (buttonElement !== null) {

        if (buttonElement.classList.contains("em-disabled-button")) {

            let iframeElement = document.querySelector(".mod_emundus_campaign__details_content #background-shapes");

            if (iframeElement !== null) {
                iframeElement.onload = function () {
                    let iframeDocument = iframeElement.contentDocument || iframeElement.contentWindow.document;
                    let pathElements = iframeDocument.querySelectorAll("path");
                    let neutral600 = getComputedStyle(document.documentElement).getPropertyValue('--neutral-600');

                    /* Coloration de tous les éléments "path" */
                    pathElements.forEach((pathElement) => {
                        let pathStyle = pathElement.getAttribute("style");
                        pathStyle = pathStyle.replace(/fill:#[0-9A-Fa-f]{6};/, "fill :" + neutral600 + ";");
                        pathElement.setAttribute("style", pathStyle);
                    });
                }
            }
        }
    }

</script>


<?php if ($mod_em_campaign_google_schema) : ?>
    <script type="application/ld+json">
        {
			"@context": "https://schema.org",
			"@type": "Event",
			"name": "<?php echo $currentCampaign->label ?>",
        "startDate": "<?php echo $currentCampaign->start_date ?>",
        "endDate": "<?php echo $currentCampaign->end_date ?>",
        "eventStatus": "https://schema.org/EventScheduled",
        "eventAttendanceMode": "https://schema.org/OnlineEventAttendanceMode",
        "location": {
            "@type": "VirtualLocation",
            "url": "<?php echo $CurPageURL ?>"
        },
        "image": [
            "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/images/custom/logo_custom.png' ?>"
        ],
        "description": "<?php echo $currentCampaign->short_description ?>",
        "organizer": {
            "@type": "Organization",
            "name": "<?php echo $site_name ?>",
            "url": "<?php echo $_SERVER['HTTP_HOST'] ?>"
        }
    }
    </script>
<?php endif; ?>

