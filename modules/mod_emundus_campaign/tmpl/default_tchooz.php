<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

header('Content-Type: text/html; charset=utf-8');

$app       = Factory::getApplication();
$menu      = $app->getMenu();
$user      = $app->getIdentity();
$config    = $app->getConfig();
$lang      = $app->getLanguage();
$locallang = $lang->getTag();

if(!class_exists('EmundusHelperMenu'))
{
    require_once JPATH_SITE . '/components/com_emundus/helpers/menu.php';
}
$base_url = EmundusHelperMenu::getBaseUriWithLang();

if ($locallang == "fr-FR")
{
	setlocale(LC_TIME, 'fr', 'fr_FR', 'french', 'fra', 'fra_FRA', 'fr_FR.ISO_8859-1', 'fra_FRA.ISO_8859-1', 'fr_FR.utf8', 'fr_FR.utf-8', 'fra_FRA.utf8', 'fra_FRA.utf-8');
}
else
{
	setlocale(LC_ALL, 'en_GB');
}
$site_offset = $config->get('offset');

$tmp_campaigns    = [];
$campaigns        = [];
$campaigns_pinned = [];
$campaigns_labels = [];

if (in_array('current', $mod_em_campaign_list_tab) && !empty($currentCampaign))
{
	$tmp_campaigns = array_merge($tmp_campaigns, $currentCampaign);
}
if (in_array('futur', $mod_em_campaign_list_tab) && !empty($futurCampaign))
{
	$tmp_campaigns = array_merge($tmp_campaigns, $futurCampaign);
}
if (in_array('past', $mod_em_campaign_list_tab) && !empty($pastCampaign))
{
	$tmp_campaigns = array_merge($tmp_campaigns, $pastCampaign);
}

if (sizeof($tmp_campaigns) > 0)
{
	$campaign_label_prefix = $params->get('mod_emundus_campaign_label_prefix', '');

	foreach ($tmp_campaigns as $key => $campaign)
	{
        $item = $menu->getItems('alias', $campaign->alias, true);
        // Change link of campaign only if there is a menu item with the alias and no custom link on the program
        if(!empty($item) && empty($campaign->link) && empty($mod_em_campaign_custom_link))
        {
            $campaign->link = $base_url.'/'.$campaign->alias;
        }
        elseif(!empty($mod_em_campaign_custom_link))
        {
            $campaign->link = $base_url.'/'.str_replace('{campaign_id}',$campaign->id,$mod_em_campaign_custom_link);
        }

		if ($campaign->pinned == 1)
		{
			$campaigns_pinned[] = $campaign;
			unset($tmp_campaigns[$key]);
		}

        $campaign->label = Text::_($campaign_label_prefix) . $campaign->label;
	}

	$tmp_campaigns = array_values($tmp_campaigns);

	if ($group_by == 'program')
	{
		/*usort($tmp_campaigns, function ($a, $b) {
			return strcmp($a->programme, $b->programme);
		});*/

		foreach ($tmp_campaigns as $campaign)
		{
			$campaigns[$campaign->training][]        = $campaign;
			$campaigns[$campaign->training]['label'] = $campaign->programme;
			$campaigns_labels[$campaign->training][]        = $campaign;
		}

	}
    elseif ($group_by == 'category')
	{
		usort($tmp_campaigns, function ($a, $b) {
			return strcmp($a->prog_type, $b->prog_type);
		});

		foreach ($tmp_campaigns as $campaign)
		{
			$campaigns[$campaign->prog_type][]        = $campaign;
			$campaigns[$campaign->prog_type]['label'] = JText::_($campaign->prog_type);
			$campaigns_labels[$campaign->prog_type][] = $campaign;
		}
	}
    elseif ($group_by == 'month')
	{
        if($mod_em_campaign_order_type == 'desc')
        {
	        usort($tmp_campaigns, function ($a, $b) use ($order) {
		        return strtotime($b->{$order}) - strtotime($a->{$order});
	        });
        } elseif ($mod_em_campaign_order_type == 'asc' || empty($mod_em_campaign_order_type)) {
	        usort($tmp_campaigns, function ($a, $b) use ($order) {
		        return strtotime($a->{$order}) - strtotime($b->{$order});
	        });
        }

		foreach ($tmp_campaigns as $campaign)
		{
			$month      = explode('-', $campaign->month_name);
			$month_name = JText::_(strtoupper($month[0]));
			$month_year = $month[1];

			$campaigns[$campaign->month . '_' . $month_year][]        = $campaign;
			$campaigns[$campaign->month . '_' . $month_year]['label'] = $month_name . ' - ' . $month_year;
			$campaigns_labels[$campaign->month.'_'.$month_year][]        = $campaign;
		}
	}
	else
	{
		$campaigns['campaigns'] = $tmp_campaigns;
	}
}

$codes_filters = [];
if (!empty($codes))
{
	$codes_filters = explode(',', $codes);
}
$categories_filters = [];
if (!empty($categories_filt))
{
	$categories_filters = explode(',', $categories_filt);
}
$reseaux_filters = [];
if (!empty($reseaux_filt))
{
	$reseaux_filters = explode(',', $reseaux_filt);
}

$protocol   = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$CurPageURL = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];


$mod_em_campaign_groupby_closed = sizeof($campaigns) > 1 ? $mod_em_campaign_groupby_closed : false;

$campaigns_not_pinned = array_filter($tmp_campaigns, function ($campaign) {
    return $campaign->pinned == 0;
});
?>


<?php if($mod_em_campaign_display_program_label == 1 && !empty($codes)) : ?>
    <h1 class="tw-mb-2"><?php echo $program_label; ?></h1>
<?php endif; ?>

<?php if (in_array('intro', $mod_em_campaign_list_sections)): ?>
    <div class="mod_emundus_campaign__intro">
		<?= $mod_em_campaign_intro; ?>
    </div>
<?php endif; ?>


<form action="<?php echo $CurPageURL ?>" method="post" id="search_program">
	<?php if (sizeof($campaigns) == 0 && empty($codes_filters) && empty($categories_filters) && empty($reseaux_filters) && empty($searchword)) : ?>
        <div class="mod_emundus_campaign__list_content--default">
			<?php if ($mod_em_campaign_display_svg == 1) : ?>
                <div id="background-shapes" alt="<?= JText::_('MOD_EM_CAMPAIGN_IFRAME') ?>"></div>
			<?php endif; ?>
            <h2 class="em-applicant-title-font em-mb-16 em-profile-color"><?php echo JText::_('MOD_EM_CAMPAIGN_NO_CAMPAIGN') ?></h2>
			<?php if ($user->guest) : ?>
				<?php if ($show_registration) : ?>
                    <h3 class="em-font-weight-500 em-mb-4"><?php echo JText::_('MOD_EM_CAMPAIGN_NO_CAMPAIGN_TEXT') ?></h3>
                    <p class="em-applicant-text-color"><?php echo JText::_('MOD_EM_CAMPAIGN_NO_CAMPAIGN_TEXT_2') ?></p>
                    <br/>
				<?php endif; ?>
                <h3 class="em-font-weight-500 em-mb-4"><?php echo JText::_('MOD_EM_CAMPAIGN_NO_CAMPAIGN_TEXT_3') ?></h3>
                <p class="em-applicant-text-color"><?php echo JText::_('MOD_EM_CAMPAIGN_NO_CAMPAIGN_TEXT_4') ?></p>
				<?php if (!empty($links)) : ?>
                    <div class="em-flex-row-justify-end mod_emundus_campaign__buttons em-mt-32">
						<?php if ($show_registration) : ?>
                            <a href="<?php echo $links->link_register ?>">
                                <button class="tw-btn-secondary em-w-auto em-applicant-border-radius"
                                        type="button" style="border: 1px solid var(--em-secondary-color);">
									<?php echo JText::_('MOD_EM_CAMPAIGN_REGISTRATION_URL') ?>
                                </button>
                            </a>
						<?php endif; ?>
                        <a href="<?php echo $links->link_login ?>">
                            <button class="em-applicant-primary-button em-w-auto em-ml-8 em-applicant-border-radius"
                                    type="button" style="border: 1px solid var(--em-primary-color);">
								<?php echo JText::_('MOD_EM_CAMPAIGN_LOGIN_URL') ?>
                            </button>
                        </a>
                    </div>
				<?php endif; ?>
			<?php endif; ?>
        </div>
	<?php else : ?>
    <div class="mod_emundus_campaign__content">

        <!-- PINNED CAMPAIGN -->
		<?php if (!empty($campaigns_pinned) && $mod_em_campaign_show_pinned_campaign == 1) : ?>
        <h3><?php echo JText::_('MOD_EM_CAMPAIGN_PINNED_CAMPAIGN') ?></h3>
        <div class="tw-mt-9 tw-mb-9<?php if (sizeof($campaigns_pinned) > 1) : ?> mod_emundus_campaign__list_items<?php endif; ?>">
			<?php foreach ($campaigns_pinned

			               as $campaign_pinned) : ?>
            <div class="mod_emundus_campaign__pinned_campaign"
			     <?php if (sizeof($campaigns_pinned) == 1) : ?>style="width: 60%"<?php endif; ?>>
                <div class="hover-and-tile-container">

					<?php if ($mod_em_campaign_display_hover_offset == 1) : ?>
                        <div id="tile-hover-offset-procedure"
                             class="tile-hover-offset-procedure--pinned-and-closed"></div>
					<?php endif; ?>

					<?php if (strtotime($now) > strtotime($campaign_pinned->end_date)) : ?>

                    <div class="mod_emundus_campaign__list_content--closed mod_emundus_campaign__list_content em-border-neutral-300 <?= $mod_em_campaign_click_to_details == 1 ? 'tw-cursor-pointer' : '' ?>"
	                    <?php if($mod_em_campaign_click_to_details == 1) : ?>
                         onclick="window.location.href='<?php echo !empty($campaign_pinned->link) ? $campaign_pinned->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $campaign_pinned->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>'"
                        <?php endif; ?>
                    >

						<?php else : ?>
                        <div class="mod_emundus_campaign__list_content <?php echo ($mod_em_campaign_single_campaign_line == 1) ? 'mod_emundus_campaign__list_content--fc' : '' ; ?> em-border-neutral-300 <?= $mod_em_campaign_click_to_details == 1 ? 'tw-cursor-pointer' : '' ?>"
	                        <?php if($mod_em_campaign_click_to_details == 1) : ?>
                             onclick="window.location.href='<?php echo !empty($campaign_pinned->link) ? $campaign_pinned->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $campaign_pinned->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>'"
                            <?php endif; ?>
                        >
							<?php endif; ?>

							<?php if ($mod_em_campaign_display_svg == 1) : ?>
                                <div
									<?php if (sizeof($campaigns_pinned) == 1) : ?>class="single-campaign-pinned"<?php endif; ?>
                                    id="background-shapes" alt="<?= JText::_('MOD_EM_CAMPAIGN_IFRAME') ?>"></div>
							<?php endif; ?>

                            <div class="mod_emundus_campaign__list_content_head <?php echo $mod_em_campaign_class; ?>">
                                <div class="mod_emundus_campaign__list_content_container">
									<?php
									$color      = '#0A53CC';
									$background = '#C8E1FE';
									if (!empty($campaign_pinned->tag_color))
									{
										$color = $campaign_pinned->tag_color;
										switch ($campaign_pinned->tag_color)
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

									<?php if ($mod_em_campaign_list_show_programme == '1' && $mod_em_campaign_show_programme_logo == '1') : ?>
                                        <div class="mod_emundus_campaign__programme_properties tw-min-h-[38px]">
                                            <p class="em-programme-tag"
                                               title="<?php echo $campaign_pinned->programme ?>"
                                               style="color: <?php echo $color ?>;">
												<?php echo $campaign_pinned->programme; ?>
                                            </p>
											<?php if (!empty($campaign_pinned->logo)) : ?>
                                                <img src="<?php echo $campaign_pinned->logo; ?>"
                                                     alt="<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_PROGRAMME_LOGO_ALT'); ?>">
											<?php endif; ?>
                                        </div>

                                        <?php if($mod_em_campaign_click_to_details == 1) : ?>
                                            <a href="<?php echo !empty($campaign_pinned->link) ? $campaign_pinned->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $campaign_pinned->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>">
                                                <h3 class="mod_emundus_campaign__campaign_title"
                                                    title="<?php echo $campaign_pinned->label; ?>"><?php echo $campaign_pinned->label; ?></h3>
                                            </a>
                                        <?php else : ?>
                                            <h3 class="mod_emundus_campaign__campaign_title"
                                                title="<?php echo $campaign_pinned->label; ?>"><?php echo $campaign_pinned->label; ?></h3>
                                        <?php endif; ?>

									<?php elseif ($mod_em_campaign_list_show_programme == '1' && $mod_em_campaign_show_programme_logo == '0') : ?>
                                        <div class="tw-min-h-[38px]">
                                            <p class="em-programme-tag" title="<?php echo $campaign_pinned->programme ?>"
                                               style="color: <?php echo $color ?>;">
                                                <?php echo $campaign_pinned->programme; ?>
                                            </p>
                                        </div>

                                        <?php if($mod_em_campaign_click_to_details == 1) : ?>
                                            <a href="<?php echo !empty($campaign_pinned->link) ? $campaign_pinned->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $campaign_pinned->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>">
                                                <h3 class="mod_emundus_campaign__campaign_title"
                                                    title="<?php echo $campaign_pinned->label; ?>"><?php echo $campaign_pinned->label; ?></h3>
                                            </a>
                                        <?php else : ?>
                                            <h3 class="mod_emundus_campaign__campaign_title"
                                                title="<?php echo $campaign_pinned->label; ?>"><?php echo $campaign_pinned->label; ?></h3>
                                        <?php endif; ?>

									<?php elseif ($mod_em_campaign_list_show_programme == '0' && $mod_em_campaign_show_programme_logo == '1') : ?>
                                        <div class="mod_emundus_campaign__campagne_properties">
	                                        <?php if($mod_em_campaign_click_to_details == 1) : ?>
                                            <a href="<?php echo !empty($campaign_pinned->link) ? $campaign_pinned->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $campaign_pinned->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>">
                                                <h3 class="mod_emundus_campaign__campaign_title"
                                                    title="<?php echo $campaign_pinned->label; ?>"><?php echo $campaign_pinned->label; ?></h3>
                                            </a>
                                            <?php else : ?>
                                                <h3 class="mod_emundus_campaign__campaign_title"
                                                    title="<?php echo $campaign_pinned->label; ?>"><?php echo $campaign_pinned->label; ?></h3>
                                            <?php endif; ?>

											<?php if (!empty($campaign_pinned->logo)) : ?>
                                                <img src="<?php echo $campaign_pinned->logo; ?>"
                                                     alt="<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_PROGRAMME_LOGO_ALT'); ?>">
											<?php endif; ?>
                                        </div>
									<?php else : ?>
                                        <?php if($mod_em_campaign_click_to_details == 1) : ?>
                                            <a href="<?php echo !empty($campaign_pinned->link) ? $campaign_pinned->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $campaign_pinned->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>">
                                                <h3 class="mod_emundus_campaign__campaign_title"
                                                    title="<?php echo $campaign_pinned->label; ?>"><?php echo $campaign_pinned->label; ?></h3>
                                            </a>
                                        <?php else : ?>
                                            <h3 class="mod_emundus_campaign__campaign_title"
                                                title="<?php echo $campaign_pinned->label; ?>"><?php echo $campaign_pinned->label; ?></h3>
                                        <?php endif; ?>
									<?php endif; ?>

	                                <?php if (!empty($mod_em_campaign_tags)):
		                                $post = array(
			                                'DEADLINE'       => strftime("%A %d %B %Y %H:%M", strtotime($campaign_pinned->end_date)),
			                                'CAMPAIGN_LABEL' => $campaign_pinned->label,
			                                'CAMPAIGN_YEAR'  => $campaign_pinned->year,
			                                'CAMPAIGN_START' => $campaign_pinned->start_date,
			                                'CAMPAIGN_END'   => $campaign_pinned->end_date,
			                                'CAMPAIGN_CODE'  => $campaign_pinned->training,
			                                'CAMPAIGN_ID'    => $campaign_pinned->id
		                                );

		                                $tags = $m_email->setTags(null, $post, null, '', $mod_em_campaign_tags);
		                                $campaign_tags_display = preg_replace($tags['patterns'], $tags['replacements'], $mod_em_campaign_tags); ?>
                                        <div class="em-mb-8">
                                            <span class="em-tags-display em-text-neutral-900">
                                                <?= $campaign_tags_display; ?>
                                            </span>
                                        </div>
	                                <?php endif; ?>

                                    <div class="<?php echo $mod_em_campaign_class; ?> em-applicant-text-color">
                                        <div>
											<?php if ($mod_em_campaign_show_camp_end_date && strtotime($now) < strtotime($campaign_pinned->start_date)) : //pas commencé ?>

                                                <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
                                                    <span class="material-symbols-outlined em-text-neutral-600 em-mr-4" aria-hidden="true">schedule</span>
                                                    <p class="em-text-neutral-600 em-mr-4"> <?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_START_DATE'); ?></p>
                                                    <span class="em-camp-start em-text-neutral-600"> <?php echo JFactory::getDate(new JDate($campaign_pinned->start_date, $site_offset))->format($mod_em_campaign_date_format); ?></span>
                                                </div>
											<?php endif; ?>

											<?php if ($mod_em_campaign_show_camp_end_date && strtotime($now) > strtotime($campaign_pinned->end_date)) :    //fini  ?>
                                                <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
                                                    <span class="material-symbols-outlined em-text-neutral-600 em-mr-4" aria-hidden="true">alarm_off</span>
                                                    <p class="em-text-neutral-600"><?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_CLOSED'); ?></p>
                                                </div>
											<?php endif; ?>

											<?php if ($mod_em_campaign_show_camp_end_date && strtotime($now) < strtotime($campaign_pinned->end_date) && strtotime($now) > strtotime($campaign_pinned->start_date)) : //en cours ?>
                                                <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
                                                    <span class="material-symbols-outlined em-text-neutral-600 em-mr-4" aria-hidden="true">schedule</span>
                                                    <p class="em-text-neutral-600 em-mr-4"> <?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_END_DATE'); ?>
                                                    </p>
                                                    <span class="em-camp-end em-text-neutral-600"> <?php echo JFactory::getDate(new JDate($campaign_pinned->end_date, $site_offset))->format($mod_em_campaign_date_format); ?></span>
                                                </div>
											<?php endif; ?>


											<?php if ($mod_em_campaign_show_formation_start_date && $campaign_pinned->formation_start !== '0000-00-00 00:00:00') : ?>
                                                <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
                                                    <p class="em-applicant-text-color"><?php echo JText::_('MOD_EM_CAMPAIGN_FORMATION_START_DATE'); ?>
                                                        :</p>
                                                    <span class="em-formation-start em-applicant-text-color"><?php echo JFactory::getDate(new JDate($campaign_pinned->formation_start, $site_offset))->format($mod_em_campaign_date_format); ?></span>
                                                </div>
											<?php endif; ?>

											<?php if ($mod_em_campaign_show_formation_end_date && $campaign_pinned->formation_end !== '0000-00-00 00:00:00') : ?>
                                                <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
                                                    <p class="em-applicant-text-color"><?php echo JText::_('MOD_EM_CAMPAIGN_FORMATION_END_DATE'); ?>
                                                        :</p>
                                                    <span class="em-formation-end em-applicant-text-color"><?php echo JFactory::getDate(new JDate($campaign_pinned->formation_end, $site_offset))->format($mod_em_campaign_date_format); ?></span>
                                                </div>
											<?php endif; ?>

											<?php if (!empty($mod_em_campaign_show_timezone) && !(strtotime($now) > strtotime($campaign_pinned->end_date))) : ?>
                                                <div class="mod_emundus_campaign__date em-flex-row">
                                                    <span class="material-symbols-outlined em-text-neutral-600 em-mr-4" aria-hidden="true">public</span>
                                                    <p class="em-text-neutral-600"><?php echo JText::_('MOD_EM_CAMPAIGN_TIMEZONE') . $offset; ?></p>
                                                </div>
											<?php endif; ?>
                                        </div>
                                    </div>

                                    <hr>

									<?php
									$text     = '';
									$textprog = '';
									$textcamp = '';
                                    $textcamp = $campaign_pinned->short_description;
									?>

                                    <div title="<?php echo strip_tags($textcamp); ?>"
                                         class="mod_emundus_campaign__list_content_resume em-text-neutral-600">
										<?php echo $textcamp; ?>
                                    </div>
                                </div>

	                            <?php if ($mod_em_campaign_show_info_button == 1 || $mod_em_campaign_show_apply_button == 1): ?>
                                    <div class="mod_emundus_campaign__list_content_buttons mod_emundus_campaign__list_content_buttons--pinned">
                                <?php endif; ?>

		                            <?php if ($mod_em_campaign_show_info_button == 1) : ?>
                                        <div>
				                            <?php
				                            $details_url = !empty($campaign_pinned->link) ? $campaign_pinned->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $campaign_pinned->id . "&Itemid=" . $mod_em_campaign_itemid2);
				                            ?>
                                            <a class="btn btn-secondary em-w-100 em-mt-8 em-applicant-default-font em-flex-column"
                                               role="button" href='<?php echo $details_url; ?>'
                                               data-toggle="sc-modal"><?php echo JText::_('MOD_EM_CAMPAIGN_MORE_INFO'); ?></a>
                                        </div>
		                            <?php endif; ?>

								<?php
                                    if ($mod_em_campaign_show_apply_button == 1 && (strtotime($now) < strtotime($campaign_pinned->end_date)) && (strtotime($now) > strtotime($campaign_pinned->start_date))) : ?>
                                    <div>
										<?php
										$can_apply = 1;
                                        $is_limit_obtained = false;
                                        if($campaign_pinned->campaign_is_limited == 1 && $campaign_pinned->campaign_limit > 0) {
                                            if($campaign_pinned->nb_files_in_limit >= $campaign_pinned->campaign_limit) {
	                                            $is_limit_obtained = true;
                                            }
                                        }

                                        if($campaign_pinned->apply_online == 0) {
	                                        $can_apply = 0;
                                        }

										$register_url = '';
										// The register URL does not work  with SEF, this workaround helps counter this.
										if ($sef == 0)
										{
											if (empty($redirect_url))
											{
												$redirect_url = 'index.php?option=com_users&view=registration';
											}
											$register_url = $redirect_url . '&course=' . $campaign_pinned->code . '&cid=' . $campaign_pinned->id;
										}
										else
										{
											$register_url = Uri::base() . $redirect_url . '?course=' . $campaign_pinned->code . '&cid=' . $campaign_pinned->id;
										}

										if (!empty($mod_em_campaign_itemid))
										{
											$register_url .= "&Itemid=" . $mod_em_campaign_itemid;
										}
										if (!$user->guest && !empty($formUrl))
										{
											$register_url .= "&redirect=" . $formUrl;
										}
										?>
                                        <?php if($can_apply == 1) : ?>
                                            <?php if($is_limit_obtained) : ?>
                                                <a class="em-disabled-button em-w-100 em-mt-8 em-applicant-default-font em-flex-column"
                                                   role="button" href='javascript:void(0);'
                                                   data-toggle="sc-modal"><?php echo JText::_('MOD_EM_CAMPAIGN_DETAILS_LIMIT_OBTAINED'); ?></a>
                                            <?php else : ?>
                                            <a class="btn btn-primary em-w-100 em-mt-8 em-applicant-default-font em-flex-column"
                                               role="button" href='<?php echo $register_url; ?>'
                                               data-toggle="sc-modal"><?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_APPLY_NOW'); ?></a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
								<?php endif; ?>
	                                <?php if ($mod_em_campaign_show_info_button == 1 || $mod_em_campaign_show_apply_button == 1): ?>
                                </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
				<?php endforeach; ?>
            </div>
			<?php endif; ?>
            <!-- END PINNED CAMPAIGN -->


            <!-- HEADER : FILTERS, SORT AND SEARCHBAR -->
            <div class="mod_emundus_campaign__header">
                <div>
                    <div class="em-flex-row">
                        <!-- BUTTONS -->
						<?php if ($mod_em_campaign_show_sort == 1 && !empty($mod_em_campaign_sort_list)) : ?>
                            <button type="button" id="mod_emundus_campaign__header_sort"
                                 class="mod_emundus_campaign__header_filter em-border-neutral-400 em-neutral-800-color tw-cursor-pointer em-mr-8"
                                 onclick="displaySort()">
                                <span class="material-symbols-outlined" aria-hidden="true">swap_vert</span>
                                <span class="em-ml-8"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_SORT') ?></span>
                            </button>
						<?php endif; ?>

						<?php if ($mod_em_campaign_show_filters == 1 && !empty($mod_em_campaign_show_filters_list)) : ?>
                            <button type="button" id="mod_emundus_campaign__header_filter"
                                 class="mod_emundus_campaign__header_filter em-border-neutral-400 em-neutral-800-color tw-cursor-pointer em-mr-8"
                                 onclick="displayFilters()">
                                <span class="material-symbols-outlined" aria-hidden="true">filter_list</span>
                                <span class="em-ml-8"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER') ?></span>
                                <span id="mod_emundus_campaign__header_filter_count"
                                      class="mod_emundus_campaign__header_filter_count em-mr-8"></span>
                            </button>
						<?php endif; ?>

                        <!-- TAGS ENABLED -->
						<?php if ($mod_em_campaign_order == 'start_date' && $order == 'end_date') : ?>
                            <div class="mod_emundus_campaign__header_filter em-mr-8 em-border-neutral-400 em-neutral-800-color em-white-bg">
                                <button type="button" class="em-flex-row em-text-neutral-900 tw-cursor-pointer"
                                        onclick="deleteSort(['order_date','order_time'])">
                                    <span><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_END_DATE_NEAR') ?></span>
                                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                </button>
                            </div>
						<?php endif; ?>
						<?php if ($mod_em_campaign_order == 'end_date' && $order == 'start_date') : ?>
                            <div class="mod_emundus_campaign__header_filter em-mr-8 em-border-neutral-400 em-neutral-800-color em-white-bg">
                                <button type="button" class="em-flex-row em-text-neutral-900 tw-cursor-pointer"
                                   onclick="deleteSort(['order_date','order_time'])">
                                    <span><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_START_DATE_NEAR') ?></span>
                                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                </button>
                            </div>
						<?php endif; ?>
						<?php if ($mod_em_campaign_show_sort == 1 && $group_by == 'program') : ?>
                            <div class="mod_emundus_campaign__header_filter em-mr-8 em-border-neutral-400 em-neutral-800-color em-white-bg">
                                <button type="button" class="em-flex-row em-text-neutral-900 tw-cursor-pointer"
                                   onclick="deleteSort(['group_by'])">
                                    <span><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_GROUP_BY_PROGRAM') ?></span>
                                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                </button>
                            </div>
						<?php endif; ?>
						<?php if ($mod_em_campaign_show_sort == 1 && $group_by == 'category') : ?>
                            <div class="mod_emundus_campaign__header_filter em-mr-8 em-border-neutral-400 em-neutral-800-color em-white-bg">
                                <button type="button" class="em-flex-row em-text-neutral-900 tw-cursor-pointer"
                                   onclick="deleteSort(['group_by'])">
                                    <span><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_GROUP_BY_CATEGORY') ?></span>
                                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                </button>
                            </div>
						<?php endif; ?>
						<?php if ($mod_em_campaign_show_sort == 1 && $group_by == 'month') : ?>
                            <div class="mod_emundus_campaign__header_filter em-mr-8 em-border-neutral-400 em-neutral-800-color em-white-bg">
                                <button type="button" class="em-flex-row em-text-neutral-900 tw-cursor-pointer"
                                   onclick="deleteSort(['month'])">
                                    <span><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_GROUP_BY_MONTH') ?></span>
                                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                </button>
                            </div>
						<?php endif; ?>
                    </div>

                    <!-- SORT BLOCK -->
                    <div class="mod_emundus_campaign__header_sort__values em-border-neutral-400 em-neutral-800-color"
                         id="sort_block" style="display: none">
						<?php if ($mod_em_campaign_order == 'start_date') : ?>
                            <button type="button" onclick="filterCampaigns(['order_date','order_time'],['end_date','asc'])"
                               class="em-text-neutral-900 tw-cursor-pointer">
								<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_END_DATE_NEAR') ?>
                            </button>
						<?php endif; ?>
						<?php if ($mod_em_campaign_order == 'end_date') : ?>
                            <button type="button" onclick="filterCampaigns(['order_date','order_time'],['start_date','asc'])"
                               class="em-text-neutral-900 tw-cursor-pointer">
								<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_START_DATE_NEAR') ?>
                            </button>
						<?php endif; ?>
						<?php if (in_array('programme', $mod_em_campaign_sort_list) && $group_by != 'program') : ?>
                            <button type="button" onclick="filterCampaigns('group_by','program')" class="em-text-neutral-900 tw-cursor-pointer">
								<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_GROUP_BY_PROGRAM') ?>
                            </button>
						<?php endif; ?>
						<?php if (in_array('category', $mod_em_campaign_sort_list) && $group_by != 'category') : ?>
                            <button type="button" onclick="filterCampaigns('group_by','category')" class="em-text-neutral-900 tw-cursor-pointer">
								<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_GROUP_BY_CATEGORY') ?>
                            </button>
						<?php endif; ?>
						<?php if (in_array('month', $mod_em_campaign_sort_list) && $group_by != 'month') : ?>
                            <button type="button" onclick="filterCampaigns('group_by','month')" class="em-text-neutral-900 tw-cursor-pointer">
								<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_GROUP_BY_MONTH') ?>
                            </button>
						<?php endif; ?>
                    </div>

                    <!-- FILTERS BLOCK -->
					<?php if ($mod_em_campaign_show_filters == 1 && !empty($mod_em_campaign_show_filters_list)) : ?>
                        <div class="mod_emundus_campaign__header_filter__values em-border-neutral-400 em-neutral-800-color"
                             id="filters_block" style="display: none">
                            <button type="button" class="add-filter-btn em-mb-8 em-flex-row em-font-size-14 tw-cursor-pointer" onclick="addFilter()"
                               title="<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_ADD_FILTER') ?>">
                                <span class="material-symbols-outlined em-font-size-14" aria-hidden="true">add</span>
								<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_ADD_FILTER') ?>
                            </button>

                            <div id="filters_list">
								<?php $i = 0; ?>
								<?php foreach ($codes_filters as $key => $code) : ?>
                                    <div class="mod_emundus_campaign__header_filter__grid" id="filter_<?php echo $i ?>">
                                        <select onchange="setupFilter('<?php echo $i ?>')"
                                                id="select_filter_<?php echo $i ?>">
                                            <option value="0"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PLEASE_SELECT') ?></option>
                                            <option value="programme"
                                                    selected><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PROGRAMME') ?></option>
                                            <option value="category"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PROGRAMME_CATEGORY') ?></option>
                                            <option value="reseau"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_RESEAU') ?></option>
                                        </select>
                                        <span class="em-text-neutral-800"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_IS') ?></span>
                                        <div id="filters_options_<?php echo $i ?>">
                                            <select id="filter_value_<?php echo $i ?>">
                                                <option value=0></option>
												<?php foreach ($programs as $program) : ?>
                                                    <option value=<?php echo $program['code'] ?> <?php if ($program['code'] == $code) : ?>selected<?php endif; ?>>
														<?php echo $program['label'] ?>
                                                    </option>
												<?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="em-flex-row">
                                            <button type="button" class="material-symbols-outlined em-red-600-color tw-cursor-pointer"
                                                  onclick="deleteFilter('<?php echo $i ?>')"
                                                  title="<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_DELETE') ?>">delete</button>
                                        </div>
                                    </div>
									<?php $i++; ?>
								<?php endforeach; ?>

								<?php foreach ($categories_filters as $key => $category) : ?>
                                    <div class="mod_emundus_campaign__header_filter__grid" id="filter_<?php echo $i ?>">
                                        <select onchange="setupFilter('<?php echo $i ?>')"
                                                id="select_filter_<?php echo $i ?>">
                                            <option value="0"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PLEASE_SELECT') ?></option>
                                            <option value="programme"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PROGRAMME') ?></option>
                                            <option value="category"
                                                    selected><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PROGRAMME_CATEGORY') ?></option>
                                            <option value="reseau"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_RESEAU') ?></option>
                                        </select>
                                        <span class="em-text-neutral-800"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_IS') ?></span>
                                        <div id="filters_options_<?php echo $i ?>">
                                            <select id="filter_value_<?php echo $i ?>">
                                                <option value=0></option>
												<?php foreach ($categories as $item) : ?>
                                                    <option value="<?php echo $item ?>"
													        <?php if ($item == $category) : ?>selected<?php endif; ?>>
														<?php echo $item ?>
                                                    </option>
												<?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="em-flex-row">
                                            <button type="button" class="material-symbols-outlined em-red-600-color tw-cursor-pointer"
                                                  onclick="deleteFilter('<?php echo $i ?>')"
                                                  title="<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_DELETE') ?>">delete</button>
                                        </div>
                                    </div>
									<?php $i++; ?>
								<?php endforeach; ?>

	                            <?php foreach ($reseaux_filters as $key => $reseau) : ?>
                                    <div class="mod_emundus_campaign__header_filter__grid" id="filter_<?php echo $i ?>">
                                        <select onchange="setupFilter('<?php echo $i ?>')"
                                                id="select_filter_<?php echo $i ?>">
                                            <option value="0"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PLEASE_SELECT') ?></option>
                                            <option value="programme"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PROGRAMME') ?></option>
                                            <option value="category"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PROGRAMME_CATEGORY') ?></option>
                                            <option value="reseau"
                                                    selected><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_RESEAU') ?></option>
                                        </select>
                                        <span class="em-text-neutral-800"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_IS') ?></span>
                                        <div id="filters_options_<?php echo $i ?>">
                                            <select id="filter_value_<?php echo $i ?>">
                                                <option value=0></option>
					                            <?php foreach ($reseaux as $rank => $item) : ?>
                                                    <option value="<?php echo $rank ?>"
						                                    <?php if ($rank == $reseau) : ?>selected<?php endif; ?>>
							                            <?php echo $item ?>
                                                    </option>
					                            <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="em-flex-row">
                                            <button type="button" class="material-symbols-outlined em-red-600-color tw-cursor-pointer"
                                                  onclick="deleteFilter('<?php echo $i ?>')"
                                                  title="<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_DELETE') ?>">delete</button>
                                        </div>
                                    </div>
		                            <?php $i++; ?>
	                            <?php endforeach; ?>
                            </div>

                            <div>
                                <button class="btn btn-primary em-float-right" type="button" onclick="filterCampaigns()"
                                        title="<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER') ?>">
									<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER') ?>
                                </button>
                            </div>
                        </div>
					<?php endif; ?>

                </div>

	            <?php if ($mod_em_campaign_show_search): ?>
                    <div class="em-searchbar em-flex-row" role="search">
                        <input name="searchword" type="text" class="form-control" id="searchword"
                               placeholder=" "
				            <?php if (isset($searchword) && !empty($searchword)) : ?>
                                value="<?= htmlspecialchars($searchword); ?>"
				            <?php endif; ?> >
                        <label for="searchword" style="display: inline-block"><?php echo JText::_('MOD_EM_CAMPAIGN_SEARCH') ?></label>
                        <button type="submit"><span class="sr-only"><?php echo JText::_('MOD_EM_CAMPAIGN_SEARCH') ?></span><span class="material-symbols-outlined em-font-size-24">search</span></button>
                    </div>
	            <?php endif; ?>
            </div>
            <!-- END HEADER -->
            
            <!-- LIST OF CAMPAIGNS -->
            <div class="mod_emundus_campaign__list em-mt-32">
				<?php if (empty($campaigns)) : ?>
                    <div class="em-mb-48">
                        <img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">
                        <p style="width: fit-content; margin: 0 auto;"><?php echo Text::_('MOD_EM_CAMPAIGN_NO_CAMPAIGN_FOUND') ?></p>
                        <a class="em-font-size-16 em-profile-color em-text-underline tw-w-full tw-block tw-text-center" href="/index.php"><?php echo Text::_('MOD_EM_CAMPAIGN_NO_CAMPAIGN_FOUND_SEARCH_LINK') ?></a>
                    </div>
				<?php endif; ?>

				<?php foreach ($campaigns

				               as $key => $campaign) : ?>
			<?php if ($key == 'campaigns') : ?>
				<?php if (empty($campaigns_not_pinned)) : ?>
                    <div class="em-mb-48">
                        <img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">
                        <p style="width: fit-content; margin: 0 auto;"><?php echo Text::_('MOD_EM_CAMPAIGN_NO_CAMPAIGN_FOUND') ?></p>
                        <a class="em-font-size-16 em-profile-color em-text-underline tw-w-full tw-block tw-text-center" href="/index.php"><?php echo Text::_('MOD_EM_CAMPAIGN_NO_CAMPAIGN_FOUND_SEARCH_LINK') ?></a>
                    </div>
                <?php else: ?>
                    <div class="em-mb-44 em-mt-44">
                        <h2 class="mod_emundus_campaign__programme_cat_title"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_CAMPAIGNS') ?></h2>
                        <hr style="margin-top: 8px">
                    </div>
				<?php endif; ?>
			<?php elseif($group_by == 'category' || $group_by == 'program'|| $group_by == 'month') : ?>

				<?php if($mod_em_campaign_display_tmpl == 1) : ?>

                    <button id="mod_emundus_campaign__tchoozy_tabs_<?php echo $key ?>" type="button"
                            class="em-mb-32 em-mt-32 tw-flex tw-items-center tw-justify-between <?php if (sizeof($campaigns) > 1) : ?>tw-cursor-pointer <?php endif; ?><?php if ($mod_em_campaign_groupby_closed == 0) : ?>open<?php endif; ?>" <?php if (sizeof($campaigns) > 1) : ?> tabindex="0" aria-expanded="false" onclick="hideTchoozyGroup('<?php echo $key ?>')" <?php endif; ?>>
						<?php if ($mod_em_campaign_display_svg == 1) : ?>
                            <div id="background-shapes-tabs" alt="<?= JText::_('MOD_EM_CAMPAIGN_IFRAME') ?>"></div>
						<?php endif; ?>
                        <div>
                            <h2 class="mod_emundus_campaign__programme_cat_title"><?php echo $campaign['label'] ?: JText::_('MOD_EM_CAMPAIGN_LIST_CAMPAIGNS') ?>
                                (<?= count($campaigns_labels[$key]); ?>)</h2>
	                        <?php if (sizeof($campaigns) > 1) : ?>
                                <p id="mod_emundus_campaign__tchoozy_tab_desc_<?php echo $key ?>"><?= JText::_('MOD_EM_CAMPAIGN_TCHOOZY_TAB_DESC_OPEN') ?></p>
	                        <?php else : ?>
                                <p id="mod_emundus_campaign__tchoozy_tab_desc_<?php echo $key ?>"><?= JText::_('MOD_EM_CAMPAIGN_TCHOOZY_TAB_DESC_ONLY_ONE') ?></p>
	                        <?php endif; ?>
                        </div>

                        <!-- If the number of programme categories is greater than 1-->
						<?php if (sizeof($campaigns) > 1) : ?>
                            <span class="material-symbols-outlined" aria-hidden="true"
                                  id="group_icon_<?php echo $key ?>">
                                    <?php if ($mod_em_campaign_groupby_closed == 1) : ?>
                                        expand_more
                                    <?php else : ?>
                                        expand_less
                                    <?php endif; ?>
                                </span>

						<?php endif; ?>
                    </button>
				<?php else : ?>
                    <div class="em-mb-24 em-mt-24">
                        <div class="tw-flex tw-items-center tw-justify-between <?php if (sizeof($campaigns) > 1) : ?>tw-cursor-pointer<?php endif; ?>" <?php if (sizeof($campaigns) > 1) : ?> onclick="hideGroup('<?php echo $key ?>')" <?php endif; ?>>
                            <h2 class="mod_emundus_campaign__programme_cat_title"><?php echo $campaign['label'] ?: JText::_('MOD_EM_CAMPAIGN_LIST_CAMPAIGNS') ?></h2>

							<?php if (sizeof($campaigns) > 1) : ?>
                                <span class="material-symbols-outlined"
                                      id="group_icon_<?php echo $key ?>">
                                        <?php if ($mod_em_campaign_groupby_closed == 1) : ?>
                                            expand_more
                                        <?php else : ?>
                                            expand_less
                                        <?php endif; ?>
                                    </span>

							<?php endif; ?>
                        </div>
                        <hr style="margin-top: 8px">
                    </div>
				<?php endif; ?>
			<?php else : ?>
                <div class="em-mb-44 em-mt-44">
                    <div class="tw-flex tw-items-center tw-justify-between <?php if (sizeof($campaigns) > 1) : ?>cursor-pointer<?php endif; ?>" <?php if (sizeof($campaigns) > 1) : ?> onclick="hideGroup('<?php echo $key ?>')" <?php endif; ?>>
                        <h2 class="mod_emundus_campaign__programme_cat_title"><?php echo $campaign['label'] ?: JText::_('MOD_EM_CAMPAIGN_LIST_CAMPAIGNS') ?></h2>
						<?php if (sizeof($campaigns) > 1) : ?>
                            <span class="material-symbols-outlined"
                                  id="group_icon_<?php echo $key ?>">
					            <?php if ($mod_em_campaign_groupby_closed == 1) : ?>
                                    expand_less
					            <?php else : ?>
                                    expand_more
					            <?php endif; ?>
                            </span>
						<?php endif; ?>
                    </div>
                    <hr style="margin-top: 8px">
                </div>
			<?php endif; ?>

			<?php if (!empty($campaign)) : ?>
                <div id="current_<?php echo $key ?>" class="<?php echo ($mod_em_campaign_single_campaign_line == 1) ? 'mod_emundus_campaign__list_items--line' : 'mod_emundus_campaign__list_items' ?><?php if($mod_em_campaign_groupby_closed == 1) : ?> em-display-none<?php endif; ?>">
					<?php
					foreach ($campaign

					         as $result)
					{
					if (is_object($result))
					{
					if ($result->pinned == 1)
					{
						continue;
					}
					?>

                    <div class="hover-and-tile-container">
						<?php if (strtotime($now) > strtotime($result->end_date)) : ?>

					<?php if ($mod_em_campaign_display_hover_offset == 1) : ?>
                        <div id="tile-hover-offset-procedure" class="tile-hover-offset-procedure--closed"></div>
					<?php endif; ?>
                        <div class="mod_emundus_campaign__list_content--closed mod_emundus_campaign__list_content em-border-neutral-300 <?= $mod_em_campaign_click_to_details == 1 ? 'tw-cursor-pointer' : '' ?>"
                             <?php if($mod_em_campaign_click_to_details == 1) : ?>
                             onclick="window.location.href='<?php echo !empty($result->link) ? $result->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $result->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>'"
                             <?php endif; ?>
                        >
							<?php if ($mod_em_campaign_display_svg == 1) : ?>
                                <div id="background-shapes" alt="<?= JText::_('MOD_EM_CAMPAIGN_IFRAME') ?>"></div>
							<?php endif; ?>

							<?php else : ?>

							<?php if ($mod_em_campaign_display_hover_offset == 1) : ?>
                                <div id="tile-hover-offset-procedure"></div>
							<?php endif; ?>
                            <div class="mod_emundus_campaign__list_content <?php echo ($mod_em_campaign_single_campaign_line == 1) ? 'mod_emundus_campaign__list_content--fc' : '' ; ?> em-border-neutral-300 <?= $mod_em_campaign_click_to_details == 1 ? 'tw-cursor-pointer' : '' ?>"
	                            <?php if($mod_em_campaign_click_to_details == 1) : ?>
                                 onclick="window.location.href='<?php echo !empty($result->link) ? $result->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $result->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>'"
                                <?php endif; ?>
                            >
								<?php if ($mod_em_campaign_display_svg == 1) : ?>
                                    <div id="background-shapes" alt="<?= JText::_('MOD_EM_CAMPAIGN_IFRAME') ?>"></div>
								<?php endif; ?>

								<?php endif; ?>

                                <div class="mod_emundus_campaign__list_content_head <?php echo ($mod_em_campaign_single_campaign_line == 1) ? 'mod_emundus_campaign__list_content_head--fc' : '' ; ?> <?php echo $mod_em_campaign_class; ?>">
                                    <div class="mod_emundus_campaign__list_content_container">

										<?php
										$color      = '#0A53CC';
										$background = '#C8E1FE';
										if (!empty($result->tag_color))
										{
											$color = $result->tag_color;
											switch ($result->tag_color)
											{
												case '#106949':
													$background = '#DFF5E9';
													break;
												case '#C31924':
													$background = '#FFEEEE';
													break;
												case '#FFC633':
													$background = '#FFF0B5';
													break;
											}
										}
										?>

										<?php if ($mod_em_campaign_list_show_programme == '1' && $mod_em_campaign_show_programme_logo == '1') : ?>
                                            <div class="mod_emundus_campaign__programme_properties tw-min-h-[38px]">
                                                <p class="em-programme-tag" title="<?php echo $result->programme ?>"
                                                   style="color: <?php echo $color ?>;">
													<?php echo $result->programme; ?>
                                                </p>
												<?php if (!empty($result->logo)) : ?>
                                                    <img src="<?php echo $result->logo; ?>"
                                                         alt="<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_PROGRAMME_LOGO_ALT'); ?>">
												<?php endif; ?>
                                            </div>

                                            <?php if($mod_em_campaign_click_to_details == 1) : ?>
                                                <a href="<?php echo !empty($result->link) ? $result->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $result->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>">
                                                    <h3 class="mod_emundus_campaign__campaign_title"><?php echo $result->label; ?></h3>
                                                </a>
                                            <?php else : ?>
                                                <h3 class="mod_emundus_campaign__campaign_title"><?php echo $result->label; ?></h3>
                                            <?php endif; ?>

										<?php elseif ($mod_em_campaign_list_show_programme == '1' && $mod_em_campaign_show_programme_logo == '0') : ?>
                                            <div class="tw-min-h-[38px]">
                                                <p class="em-programme-tag" title="<?php echo $result->programme ?>"
                                                   style="color: <?php echo $color ?>;">
                                                    <?php echo $result->programme; ?>
                                                </p>
                                            </div>

                                            <?php if($mod_em_campaign_click_to_details == 1) : ?>
                                                <a href="<?php echo !empty($result->link) ? $result->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $result->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>">
                                                    <h3 class="mod_emundus_campaign__campaign_title"
                                                        title="<?php echo $result->label; ?>"><?php echo $result->label; ?></h3>
                                                </a>
                                            <?php else : ?>
                                                <h3 class="mod_emundus_campaign__campaign_title"
                                                    title="<?php echo $result->label; ?>"><?php echo $result->label; ?></h3>
                                            <?php endif; ?>

										<?php elseif ($mod_em_campaign_list_show_programme == '0' && $mod_em_campaign_show_programme_logo == '1') : ?>
                                            <div class="mod_emundus_campaign__campagne_properties">
	                                            <?php if ($mod_em_campaign_click_to_details == 1) : ?>
                                                    <a href="<?php echo !empty($result->link) ? $result->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $result->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>">
                                                        <h3 class="mod_emundus_campaign__campaign_title"
                                                            title="<?php echo $result->label; ?>"><?php echo $result->label; ?></h3>
                                                    </a>
	                                            <?php else : ?>
                                                    <h3 class="mod_emundus_campaign__campaign_title"
                                                        title="<?php echo $result->label; ?>"><?php echo $result->label; ?></h3>
	                                            <?php endif; ?>

												<?php if (!empty($result->logo)) : ?>
                                                    <img src="<?php echo $result->logo; ?>"
                                                         alt="<?php echo JText::_('MOD_EM_CAMPAIGN_LIST_PROGRAMME_LOGO_ALT'); ?>">
												<?php endif; ?>
                                            </div>
										<?php else : ?>
                                            <?php if($mod_em_campaign_click_to_details == 1) : ?>
                                                <a href="<?php echo !empty($result->link) ? $result->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $result->id . "&Itemid=" . $mod_em_campaign_itemid2); ?>">
                                                    <h3 class="mod_emundus_campaign__campaign_title"
                                                        title="<?php echo $result->label; ?>"><?php echo $result->label; ?></h3>
                                                </a>
                                            <?php else : ?>
                                                <h3 class="mod_emundus_campaign__campaign_title"
                                                    title="<?php echo $result->label; ?>"><?php echo $result->label; ?></h3>
                                            <?php endif; ?>
										<?php endif; ?>

	                                    <?php if (!empty($mod_em_campaign_tags)):
		                                    $post = array(
			                                    'DEADLINE'       => strftime("%A %d %B %Y %H:%M", strtotime($result->end_date)),
			                                    'CAMPAIGN_LABEL' => $result->label,
			                                    'CAMPAIGN_YEAR'  => $result->year,
			                                    'CAMPAIGN_START' => $result->start_date,
			                                    'CAMPAIGN_END'   => $result->end_date,
			                                    'CAMPAIGN_CODE'  => $result->training,
			                                    'CAMPAIGN_ID'    => $result->id
		                                    );

		                                    $tags = $m_email->setTags(null, $post, null, '', $mod_em_campaign_tags);
		                                    $campaign_tags_display = preg_replace($tags['patterns'], $tags['replacements'], $mod_em_campaign_tags); ?>
                                            <div class="em-mb-8">
                                            <span class="em-tags-display em-text-neutral-900">
                                                <?= $campaign_tags_display; ?>
                                            </span>
                                            </div>
	                                    <?php endif; ?>

                                        <div class="<?php echo $mod_em_campaign_class; ?> em-applicant-text-color">
                                            <div>
												<?php if (strtotime($now) < strtotime($result->start_date)) : //pas commencé ?>

                                                    <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
                                                        <span class="material-symbols-outlined em-text-neutral-600 em-mr-4" aria-hidden="true">schedule</span>
                                                        <p class="em-text-neutral-600 em-mr-4"> <?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_START_DATE'); ?></p>
                                                        <span class="em-camp-start em-text-neutral-600"> <?php echo JFactory::getDate(new JDate($result->start_date, $site_offset))->format($mod_em_campaign_date_format); ?></span>
                                                    </div>
												<?php endif; ?>

												<?php if ($mod_em_campaign_show_camp_end_date && strtotime($now) > strtotime($result->end_date)) :    //fini  ?>
                                                    <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
                                                        <span class="material-symbols-outlined em-text-neutral-600 em-mr-4" aria-hidden="true">alarm_off</span>
                                                        <p class="em-text-neutral-600"><?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_CLOSED'); ?></p>
                                                    </div>
												<?php endif; ?>

												<?php if ($mod_em_campaign_show_camp_end_date && strtotime($now) < strtotime($result->end_date) && strtotime($now) > strtotime($result->start_date)) : //en cours ?>
													<?php
													$displayInterval = false;
													$interval        = date_create($now)->diff(date_create($result->end_date));
													if ($interval->y == 0 && $interval->m == 0 && $interval->d == 0)
													{
														$displayInterval = true;
														if ($interval->h < 10) {
															$interval_h = '0' . $interval->h;
														} else {
															$interval_h = $interval->h;
														}
														if ($interval->i < 10) {
															$interval_i = '0' . $interval->i;
														} else {
															$interval_i = $interval->i;
														}

													}
													?>
                                                    <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
														<?php if (!$displayInterval) : ?>
                                                            <span class="material-symbols-outlined em-text-neutral-600 em-mr-4" aria-hidden="true">schedule</span>
                                                            <p class="em-text-neutral-600  em-mr-4"> <?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_END_DATE'); ?>
                                                            </p>
                                                            <span class="em-camp-end em-text-neutral-600"> <?php echo JFactory::getDate(new JDate($result->end_date, $site_offset))->format($mod_em_campaign_date_format); ?></span>
														<?php else : ?>
                                                            <span class="material-symbols-outlined em-text-neutral-600 em-red-600-color em-mr-4" aria-hidden="true">schedule</span>
                                                            <p class="em-red-600-color"><?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_LAST_DAY'); ?>
																<?php if ($interval->h > 0)
																{
																	echo $interval_h . 'h' . $interval_i;
																}
																else
																{
																	echo $interval_i . 'm';
																} ?>
                                                            </p>
														<?php endif; ?>
                                                    </div>
												<?php endif; ?>


												<?php if ($mod_em_campaign_show_formation_start_date && $result->formation_start !== '0000-00-00 00:00:00') : ?>
                                                    <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
                                                        <p class="em-text-neutral-600"><?php echo JText::_('MOD_EM_CAMPAIGN_FORMATION_START_DATE'); ?>
                                                            :</p>
                                                        <span class="em-formation-start em-text-neutral-600"><?php echo JFactory::getDate(new JDate($result->formation_start, $site_offset))->format($mod_em_campaign_date_format); ?></span>
                                                    </div>
												<?php endif; ?>

												<?php if ($mod_em_campaign_show_formation_end_date && $result->formation_end !== '0000-00-00 00:00:00') : ?>
                                                    <div class="mod_emundus_campaign__date em-flex-row em-mb-4">
                                                        <p class="em-text-neutral-600"><?php echo JText::_('MOD_EM_CAMPAIGN_FORMATION_END_DATE'); ?>
                                                            :</p>
                                                        <span class="em-formation-end em-text-neutral-600"><?php echo JFactory::getDate(new JDate($result->formation_end, $site_offset))->format($mod_em_campaign_date_format); ?></span>
                                                    </div>
												<?php endif; ?>
												<?php
												?>
												<?php if (!empty($mod_em_campaign_show_timezone) && !(strtotime($now) > strtotime($result->end_date))) : ?>
                                                    <div class="mod_emundus_campaign__date em-flex-row">
                                                        <span class="material-symbols-outlined em-text-neutral-600 em-mr-4" aria-hidden="true">public</span>
                                                        <p class="em-text-neutral-600"><?php echo JText::_('MOD_EM_CAMPAIGN_TIMEZONE') . $offset; ?></p>
                                                    </div>
												<?php endif; ?>
                                            </div>
                                        </div>

                                        <hr>
										<?php
										$text     = '';
										$textprog = '';
                                        $textcamp = $result->short_description;
										?>

                                        <div title="<?php echo strip_tags($textcamp); ?>"
                                             class="mod_emundus_campaign__list_content_resume em-text-neutral-600"
											<?php if (empty($mod_em_campaign_show_timezone) || (strtotime($now) > strtotime($result->end_date))) : ?> style="-webkit-line-clamp: 4;" <?php endif; ?>
                                        >
											<?php echo $textcamp; ?>
                                        </div>
                                    </div>

	                                <?php if ($mod_em_campaign_show_info_button == 1 || $mod_em_campaign_show_apply_button == 1): ?>
                                    <div class="mod_emundus_campaign__list_content_buttons">
		                                <?php endif; ?>

		                                <?php if ($mod_em_campaign_show_info_button == 1) : ?>
                                            <div>
				                                <?php
				                                $details_url = !empty($result->link) ? $result->link : JRoute::_("index.php?option=com_emundus&view=programme&cid=" . $result->id . "&Itemid=" . $mod_em_campaign_itemid2);
				                                ?>
                                                <a class="btn btn-secondary em-w-100 em-mt-8 em-applicant-default-font em-flex-column"
                                                   role="button" href='<?php echo $details_url; ?>'
                                                   data-toggle="sc-modal"><?php echo JText::_('MOD_EM_CAMPAIGN_MORE_INFO'); ?></a>
                                            </div>
		                                <?php endif; ?>

									<?php if ($mod_em_campaign_show_apply_button == 1 && (strtotime($now) < strtotime($result->end_date)) && (strtotime($now) > strtotime($result->start_date))) : ?>
                                        <div>
											<?php
											$can_apply = 1;
											$is_limit_obtained = false;
											if($result->campaign_is_limited == 1 && $result->campaign_limit > 0) {
												if($result->nb_files_in_limit >= $result->campaign_limit) {
													$is_limit_obtained = true;
												}
											}

											if($result->apply_online == 0) {
												$can_apply = 0;
											}

											$register_url = '';
											// The register URL does not work  with SEF, this workaround helps counter this.
											if ($sef == 0)
											{
												if (empty($redirect_url))
												{
													$redirect_url = 'index.php?option=com_users&view=registration';
												}
												$register_url = $redirect_url . '&course=' . $result->code . '&cid=' . $result->id . '&Itemid=' . $mod_em_campaign_itemid;
											}
											else
											{
												$register_url = $redirect_url . '?course=' . $result->code . '&cid=' . $result->id . '&Itemid=' . $mod_em_campaign_itemid;
											}

											if (!$user->guest)
											{
												$register_url .= '&redirect=' . $formUrl;
											}
											?>
	                                        <?php if($can_apply == 1) : ?>
		                                        <?php if($is_limit_obtained) : ?>
                                                    <a class="em-disabled-button em-w-100 em-mt-8 em-applicant-default-font em-flex-column"
                                                       role="button" href='javascript:void(0);'
                                                       data-toggle="sc-modal"><?php echo JText::_('MOD_EM_CAMPAIGN_DETAILS_LIMIT_OBTAINED'); ?></a>
		                                        <?php else : ?>
                                                    <a class="btn btn-primary em-w-100 em-mt-8 em-applicant-default-font em-flex-column"
                                                       role="button" href='<?php echo $register_url; ?>'
                                                       data-toggle="sc-modal"><?php echo JText::_('MOD_EM_CAMPAIGN_CAMPAIGN_APPLY_NOW'); ?></a>
		                                        <?php endif; ?>
	                                        <?php endif; ?>
                                        </div>
									<?php endif; ?>
	                                    <?php if ($mod_em_campaign_show_info_button == 1 || $mod_em_campaign_show_apply_button == 1): ?>
                                    </div>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
						<?php }
						} ?>
                    </div>
					<?php endif; ?>
					<?php endforeach; ?>
                    <!-- Close tab-content -->
                </div>
				<?php endif; ?>
</form>
<script type="text/javascript">
    jQuery(document).ready(function () {

        if (jQuery(window).width() > 768) {
            jQuery('.position-me').each(function () {
                var h = jQuery(this).parent().parent().height() - 23;
                jQuery(this).width(h);
            });
        } else if (jQuery(window).width() == 768) {
            jQuery('.position-me').each(function () {
                var h = jQuery(this).parent().parent().height() - 38;
                jQuery(this).width(h);
            });
        }

		<?php if ($mod_em_campaign_show_filters == 1 && !empty($mod_em_campaign_show_filters_list)) : ?>
        setTimeout(() => {
            let sort_button = document.getElementById('mod_emundus_campaign__header_sort');
            if (typeof sort_button !== 'undefined') {
                document.getElementById('filters_block').style.marginLeft = (sort_button.offsetWidth + 8) + 'px';
            }
        }, 1000);

        let filter_existing = document.querySelectorAll("div[id^='filter_']");
        document.getElementById('mod_emundus_campaign__header_filter_count').innerHTML = filter_existing.length;
		<?php endif; ?>
    });

    function displaySort() {
        let sort = document.getElementById('sort_block');
        if (sort.style.display === 'none') {
            sort.style.display = 'flex';
        } else {
            sort.style.display = 'none';
        }
    }

    function displayFilters() {
        let filters = document.getElementById('filters_block');
        if (filters.style.display === 'none') {
            filters.style.display = 'flex';
        } else {
            filters.style.display = 'none';
        }
    }

    function setupFilter(index) {
        let type = document.getElementById('select_filter_' + index).value
        let html = '';

        switch (type) {
            case 'programme':
                html = '<select id="filter_value_' + index + '"> ' +
                    '<option value = 0></option>' +
					<?php foreach ($programs as $program) : ?>
                    "<option value=\"<?php echo $program['code'] ?>\"><?php echo urlencode($program['label']) ?></option>" +
					<?php endforeach; ?>
                    '</select>';
                break;
            case 'category':
                html = '<select id="filter_value_' + index + '"> ' +
                    '<option value = 0></option>' +
					<?php foreach ($categories as $category) : ?>
                    "<option value=\"<?php echo $category ?>\"><?php echo $category ?></option>" +
					<?php endforeach; ?>
                    '</select>';
                break;
            case 'reseau':
                html = '<select id="filter_value_' + index + '"> ' +
                    '<option value = 0></option>' +
			        <?php foreach ($reseaux as $key => $reseau) : ?>
                    "<option value=\"<?php echo $key ?>\"><?php echo $reseau ?></option>" +
			        <?php endforeach; ?>
                    '</select>';
                break;
            default:
                break;
        }

        document.getElementById('filters_options_' + index).innerHTML = html;
    }

    function addFilter() {
        let index = 1;
        let filter_existing = document.querySelectorAll("div[id^='filter_']");
        let last_filter = filter_existing[filter_existing.length - 1];
        if (typeof last_filter !== 'undefined') {
            index = last_filter.id.split('_');
            index = parseInt(index[index.length - 1]) + 1;
        }

        let html = '<div class="mod_emundus_campaign__header_filter__grid" id="filter_' + index + '"> ' +
            '<select onchange="setupFilter(' + index + ')" id="select_filter_' + index + '"> ' +
            '<option value="0"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PLEASE_SELECT') ?></option> ';

		<?php if (in_array('programme', $mod_em_campaign_show_filters_list)) : ?>
        html += '<option value="programme"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PROGRAMME') ?></option> ';
		<?php endif; ?>

		<?php if (in_array('category', $mod_em_campaign_show_filters_list)) : ?>
        html += '<option value="category"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_PROGRAMME_CATEGORY') ?></option> ';
		<?php endif; ?>

	    <?php if (is_array($mod_em_campaign_show_filters_list) && in_array('reseau', $mod_em_campaign_show_filters_list)) : ?>
        html += '<option value="reseau"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_RESEAU') ?></option> ';
	    <?php endif; ?>

	    <?php if (is_array($mod_em_campaign_show_filters_list) && in_array('reseau', $mod_em_campaign_show_filters_list)) : ?>
        html += '<option value="reseau"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_RESEAU') ?></option> ';
	    <?php endif; ?>

        html += '</select> ' +
            '<span class="em-text-neutral-800"><?php echo JText::_('MOD_EM_CAMPAIGN_LIST_FILTER_IS') ?></span> ' +
            '<div id="filters_options_' + index + '"></div>' +
            '<div class="em-flex-row">' +
            '<button type="button" class="material-symbols-outlined em-red-600-color tw-cursor-pointer" onclick="deleteFilter(' + index + ')">delete</span>' +
            '</div>' +
            '</div>';
        document.getElementById('filters_list').insertAdjacentHTML('beforeend', html);
    }

    function deleteFilter(index) {
        document.getElementById('filter_' + index).remove();
    }

    function filterCampaigns(type = '', value = '') {
        let filters = document.querySelectorAll("select[id^='select_filter_']");
        let current_url = window.location.href;
        if (current_url.indexOf('?') === -1) {
            current_url += '?';
        }

        let existing_filters = current_url.split('&');
        let codes = [];
        let categories = [];
        let reseaux = [];

        let values_to_remove = [];
        existing_filters.forEach((filter, key) => {
            if (filter.indexOf('code') !== -1) {
                values_to_remove.push(filter);
            }
            if (filter.indexOf('category') !== -1) {
                values_to_remove.push(filter);
            }
            if (filter.indexOf('reseau') !== -1) {
                values_to_remove.push(filter);
            }
            if (type !== '' && !Array.isArray(type)) {
                if (filter.indexOf(type) !== -1) {
                    values_to_remove.push(filter);
                }
            } else if (Array.isArray(type)) {
                type.forEach((elt) => {
                    if (filter.indexOf(elt) !== -1) {
                        values_to_remove.push(filter);
                    }
                })
            }
        })

        values_to_remove.forEach((value) => {
            existing_filters.splice(existing_filters.indexOf(value), 1);
        })

        let program_filter = '';
        let category_filter = '';
        let reseaux_filter = '';
        let type_filter = '';
        filters.forEach((filter) => {
            let type = filter.value;
            let index = filter.id.split('_');
            index = parseInt(index[index.length - 1]);

            let value = document.getElementById('filter_value_' + index).value;
            switch (type) {
                case 'programme':
                    if (value != 0 && value != '') {
                        codes.push(value);
                    }
                    break;
                case 'category':
                    if (value != 0 && value != '') {
                        categories.push(value);
                    }
                    break;
                case 'reseau':
                    if (value != 0 && value != '') {
                        reseaux.push(value);
                    }
                    break;
                default:
                    break;
            }
        })

        let new_url = existing_filters.join('&');
        if(new_url.indexOf('?') === -1) {
            new_url += '?';
        }
        if (codes.length > 0) {
            program_filter = '&code=';
            program_filter += codes.join(',');
            new_url += program_filter;
        }
        if (categories.length > 0) {
            category_filter = '&category=';
            let params = categories.join(',');
            params = encodeURIComponent(params);
            category_filter += params;
            new_url += category_filter;
        }
        if (reseaux.length > 0) {
            reseaux_filter = '&reseau=';
            let params = reseaux.join(',');
            params = encodeURIComponent(params);
            reseaux_filter += params;
            new_url += reseaux_filter;
        }
        if (type !== '' && !Array.isArray(type)) {
            type_filter = '&' + type + '=';
            type_filter += value;
            new_url += type_filter;
        } else if (Array.isArray(type)) {
            type.forEach((elt, index) => {
                type_filter += '&' + elt + '=';
                type_filter += value[index];
            })
            new_url += type_filter;
        }

        window.location.href = new_url;
    }

    function deleteSort(sort) {
        let current_url = window.location.href;
        let existing_filters = current_url.split('&');

        existing_filters.forEach((filter, key) => {
            sort.forEach((elt) => {
                if (filter.indexOf(elt) !== -1) {
                    existing_filters.splice(key, 1);
                }
            });
        });

        window.location.href = existing_filters.join('&');
    }

    function hideGroup(key) {
        let group = document.getElementById('current_' + key);
        let icon = document.getElementById('group_icon_' + key);

        if (group.style.display === 'none' || getComputedStyle(group).display === 'none') {
            group.style.display = 'grid';
            icon.innerHTML = 'expand_less';
        } else {
            group.style.display = 'none';
            icon.innerHTML = 'expand_more';
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        let tabs_container = document.querySelectorAll('[id^="mod_emundus_campaign__tchoozy_tabs_"]');
        let tabs_description = document.querySelectorAll('[id^="mod_emundus_campaign__tchoozy_tab_desc_"]');

        tabs_container.forEach((tab_container, index) => {
            if (tab_container.classList.contains('open')) {
                tabs_description[index].innerHTML = "<?= JText::_('MOD_EM_CAMPAIGN_TCHOOZY_TAB_DESC_OPEN') ?>";
            } else {
                tabs_description[index].innerHTML = "<?= JText::_('MOD_EM_CAMPAIGN_TCHOOZY_TAB_DESC_CLOSE') ?>";
            }
        });
    });

    function hideTchoozyGroup(key) {
        let group = document.getElementById('current_' + key);
        let icon = document.getElementById('group_icon_' + key);
        let tabs_desc = document.getElementById("mod_emundus_campaign__tchoozy_tab_desc_" + key);
        let tabs = document.getElementById("mod_emundus_campaign__tchoozy_tabs_" + key);

        if (group.style.display === 'none' || getComputedStyle(group).display === 'none') {
            group.style.display = 'grid';
            icon.innerHTML = 'expand_less';
            tabs.setAttribute("aria-expanded", 'true');
            tabs_desc.innerHTML = "<?= JText::_('MOD_EM_CAMPAIGN_TCHOOZY_TAB_DESC_OPEN')?>";
            tabs.classList.add("open");
        } else {
            group.style.display = 'none';
            icon.innerHTML = 'expand_more';
            tabs.setAttribute("aria-expanded", 'false');
            tabs_desc.innerHTML = "<?= JText::_('MOD_EM_CAMPAIGN_TCHOOZY_TAB_DESC_CLOSE')?>";
            tabs.classList.remove("open");

        }
    }

    document.addEventListener('click', function (e) {
        let sort = document.getElementById('sort_block');
        let filters = document.getElementById('filters_block');
        let clickInsideModule = false;

        if (sort && sort.style.display === 'flex') {
            e.composedPath().forEach((pathElement) => {
                if (pathElement.id == "sort_block" || pathElement.id == "mod_emundus_campaign__header_sort") {
                    clickInsideModule = true;
                }
            });

            if (!clickInsideModule) {
                sort.style.display = 'none';
            }
        }

        if (typeof filters !== 'undefined') {
            clickInsideModule = false;
            if (filters && filters.style.display === 'flex') {
                e.composedPath().forEach((pathElement) => {
                    if (pathElement.id == "filters_block" || pathElement.id == "mod_emundus_campaign__header_filter") {
                        clickInsideModule = true;
                    }
                });

                if (!clickInsideModule) {
                    filters.style.display = 'none';
                }
            }
        }
    });

    /* Changement de couleur des formes au hover de la card */
    let divsHover = document.querySelectorAll(".hover-and-tile-container");
    let iframeElementHover = document.getElementById('background-shapes');

    divsHover.forEach((divHover) => {

        let iframeElementHover = divHover.querySelector('div#background-shapes');
        if (iframeElementHover !== null) {

            divHover.addEventListener('mouseenter', function () {
                iframeElementHover.style.maskImage = 'url("/modules/mod_emundus_campaign/assets/fond-fonce.svg")';
            });

            divHover.addEventListener('mouseleave', function () {
                iframeElementHover.style.maskImage = 'url("/modules/mod_emundus_campaign/assets/fond-clair.svg")';
            });
        }
    })

    document.addEventListener("DOMContentLoaded", function() {
	    <?php if(empty($campaigns)) : ?>
        document.title = "<?php echo JText::_('EMPTY_CAMPAIGNS'); ?>";
	    <?php endif; ?>
    });


</script>
