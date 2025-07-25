<?php
/**
 * @package     Joomla.Site
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

Text::script('COM_EMUNDUS_APPLICATION_SHARE_CONFIRM_DELETE');


$config      = JFactory::getConfig();
$site_offset = $config->get('offset');

$dateTime = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
$dateTime = $dateTime->setTimezone(new DateTimeZone($site_offset));
$now      = $dateTime->format('Y-m-d H:i:s');

$order_by_session = JFactory::getSession()->get('applications_order_by');

$tmp_applications = $applications;
foreach ($applications as $key => $application)
{
	if (!$show_collaboration_files && $application->applicant_id !== $user->id)
	{
		unset($tmp_applications[$key]);
		continue;
	}

	if ($application->published == '1' || ($show_remove_files == 1 && $application->published == '-1') || ($show_archive_files == 1 && $application->published == '0'))
	{
		continue;
	}
	else
	{
		unset($tmp_applications[$key]);
	}
}

$applications   = [];
$status_group   = [];
$missing_status = [];

if (!empty($groups) && !empty($tmp_applications))
{
	$groups_count = 0;
	foreach ($groups as $key => $group)
	{
		$groups_count++;
		$status_to_check = explode(',', $group->mod_em_application_group_status);
		foreach ($status_to_check as $step)
		{
			$status_group[] = $step;
		}
	}

	foreach ($status as $step)
	{
		if (!in_array($step['step'], $status_group))
		{
			$missing_status[] = $step['step'];
		}
	}
	if (!empty($missing_status))
	{
		$groups->{'mod_em_application_group' . $groups_count}                                      = new stdClass();
		$groups->{'mod_em_application_group' . $groups_count}->{'mod_em_application_group_status'} = implode(',', $missing_status);
		$groups->{'mod_em_application_group' . $groups_count}->{'mod_em_application_group_title'}  = Text::_($title_other_section);
	}

	foreach ($groups as $key => $group)
	{
		$applications[0][$key]['applications'][0] = array_filter($tmp_applications, function ($application) use ($group) {
			$status_to_check = explode(',', $group->mod_em_application_group_status);

			return in_array($application->status, $status_to_check) !== false;
		});
		$applications[0][$key]['label']           = $group->mod_em_application_group_title;
	}
}
elseif (!empty($tmp_applications))
{
	foreach ($tmp_applications as $tmp_application)
	{
		switch ($order_by_session)
		{
			case 'status':
				if (!empty($tmp_application->tab_id))
				{
					$applications[$tmp_application->tab_id]['all']['applications'][$tmp_application->value][] = $tmp_application;
				}
				$applications[0]['all']['applications'][$tmp_application->value][] = $tmp_application;
				break;
			case 'campaigns':
				if (!empty($tmp_application->tab_id))
				{
					$applications[$tmp_application->tab_id]['all']['applications'][$tmp_application->label][] = $tmp_application;
				}
				$applications[0]['all']['applications'][$tmp_application->label][] = $tmp_application;
				break;
			case 'programs':
				if (!empty($tmp_application->tab_id))
				{
					$applications[$tmp_application->tab_id]['all']['applications'][$tmp_application->programme][] = $tmp_application;
				}
				$applications[0]['all']['applications'][$tmp_application->programme][] = $tmp_application;
				break;
			case 'years':
				if (!empty($tmp_application->tab_id))
				{
					$applications[$tmp_application->tab_id]['all']['applications'][$tmp_application->year][] = $tmp_application;
				}
				$applications[0]['all']['applications'][$tmp_application->year][] = $tmp_application;
				break;
			default:
				if (!empty($tmp_application->tab_id))
				{
					$applications[$tmp_application->tab_id]['all']['applications'][0][] = $tmp_application;
				}
				$applications[0]['all']['applications'][0][] = $tmp_application;
				break;
		}
	}
}

array_unshift($tabs, [
	'id'       => 0,
	'name'     => 'MOD_EM_APPLICATION_FILES_ALL',
	'ordering' => 0,
	'no_files' => count($tmp_applications)
]);

ksort($applications);

$current_tab = 0;

if (!empty($applications) && !empty($title_override) && !empty(str_replace(array(' ', "\t", "\n", "\r", "&nbsp;"), '', htmlentities(strip_tags($title_override)))))
{
	if (!isset($m_email))
	{
		if (!class_exists('EmundusModelEmails'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
		}
		$m_email = new EmundusModelEmails();
	}

	foreach ($applications[0]['all']['applications'] as $key => $sub_applications)
	{
		foreach ($sub_applications as $a_key => $application)
		{
			$title_override_display = $title_override;
			$post                   = array(
				'APPLICANT_ID'   => $user->id,
				'DEADLINE'       => strftime("%A %d %B %Y %H:%M", strtotime($application->end_date)),
				'CAMPAIGN_LABEL' => $application->label,
				'CAMPAIGN_YEAR'  => $application->year,
				'CAMPAIGN_START' => $application->start_date,
				'CAMPAIGN_END'   => $application->end_date,
				'CAMPAIGN_CODE'  => $application->training,
				'FNUM'           => $application->fnum
			);

			$tags                   = $m_email->setTags($user->id, $post, $application->fnum, '', $title_override_display, false, true);
			$title_override_display = preg_replace($tags['patterns'], $tags['replacements'], $title_override_display);
			$title_override_display = $m_email->setTagsFabrik($title_override_display, array($application->fnum));

			$applications[0]['all']['applications'][$key][$a_key]->label = $title_override_display;
		}
	}
}
?>
<div class="mod_emundus_applications___header mod_emundus_applications___tmp_tchooz">
	<?php if ($mod_em_applications_show_hello_text == 1 && !$is_anonym_user) : ?>
        <div class="em-flex-row em-flex-space-between em-w-100 em-mb-16">
            <h1><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_HELLO') . $user->firstname ?></h1>
        </div>
	<?php endif; ?>

	<?php if (sizeof($applications) > 0 && $mod_em_applications_show_hello_text != 1) : ?>
        <div class="em-flex-column em-flex-align-start">
			<?php if ($show_add_application && ($position_add_application == 3 || $position_add_application == 4) && $applicant_can_renew) : ?>
                <a id="add-application" class="btn btn-success em-mb-8" style="width: 40%" href="<?= $cc_list_url; ?>">
                    <span> <?= Text::_('MOD_EMUNDUS_APPLICATIONS_ADD_APPLICATION_FILE'); ?></span>
                </a>
			<?php endif; ?>
			<?php if ($show_show_campaigns) : ?>
                <a id="add-application" class="btn btn-success em-mt-8 em-mb-8" style="width: 40%"
                   href="<?= $campaigns_list_url; ?>">
                    <span> <?= Text::_('MOD_EMUNDUS_APPLICATIONS_SHOW_CAMPAIGNS'); ?></span>
                </a>
			<?php endif; ?>
        </div>
	<?php endif; ?>

	<?php if (sizeof($applications) > 0) : ?>
        <?php if(!empty($description)) : ?>
            <span class="em-text-neutral-500">
                <?php
                $tags              = $m_email->setTags($user->id, [], '', '', $description, false, true);
                $description = preg_replace($tags['patterns'], $tags['replacements'], $description);
                echo $description;
                ?>
            </span>
        <?php endif; ?>
        <?php if(!empty(Text::_('MOD_EMUNDUS_APPLICATIONS_HELP_INTRO'))) : ?>
            <p>
                <?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_HELP_INTRO'); ?>
                <?php if(!empty($actions)) : ?>
                    <?php echo '(';
                    foreach ($actions as $key => $action) {
                        if ($key == 0)
                        {
                            echo Text::_('MOD_EMUNDUS_APPLICATIONS_ACTIONS_SHORT_'.strtoupper($action));
                        }
                        else
                        {
                            echo ', ' . Text::_('MOD_EMUNDUS_APPLICATIONS_ACTIONS_SHORT_'.strtoupper($action));
                        }
                    }
                    if($show_tabs == 1) {
                        echo ', ' . Text::_('MOD_EMUNDUS_APPLICATIONS_ACTIONS_SHORT_TABS');
                    }
                    echo ').'; ?>
                <?php else : ?>
                    <?php echo '. '; ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>

		<?php if ($show_add_application && ($position_add_application == 0 || $position_add_application == 2) && $applicant_can_renew) : ?>
            <a id="add-application" class="btn btn-success em-mt-24" style="width: auto" href="<?= $cc_list_url; ?>">
                <span> <?= Text::_('MOD_EMUNDUS_APPLICATIONS_ADD_APPLICATION_FILE'); ?></span>
            </a>
            <hr>
		<?php endif; ?>
	<?php endif; ?>
</div>

<?php if ($show_tabs == 1 && sizeof($applications) > 0) : ?>
    <div class="em-mt-12 em-flex-row em-border-bottom-neutral-400"
         style="height: 50px; overflow:hidden; overflow-x: auto;">
		<?php foreach ($tabs as $tab) : ?>
            <div id="tab_link_<?php echo $tab['id'] ?>" onclick="updateTab(<?php echo $tab['id'] ?>)"
                 class="em-flex-row em-light-tabs em-pointer <?php if ($current_tab == $tab['id']) : ?>em-light-selected-tab<?php endif; ?>">
                <p class="em-font-size-14"
                   style="white-space: nowrap"><?php echo Text::_($tab['name']) ?></p>
				<?php if ($tab['id'] != 0) : ?>
                    <span class="mod_emundus_applications_badge"><?php echo $tab['no_files'] ?></span>
				<?php endif; ?>
            </div>
		<?php endforeach; ?>
        <div id="tab_adding_link" onclick="createTab()"
             class="em-light-tabs em-flex-row em-pointer <?php if (count($tabs) > 1) : ?>em-display-none<?php endif; ?>">
            <a class="em-flex-row em-no-hover-underline em-font-size-14 em-pointer" style="white-space: nowrap"><span
                        class="material-symbols-outlined em-font-size-14 em-mr-4">add</span><?php echo Text::_('MOD_EM_APPLICATION_TABS_ADD_TAB') ?>
            </a>
        </div>
        <div id="tab_manage_links" onclick="manageTabs()"
             class="em-light-tabs em-flex-row em-pointer <?php if (count($tabs) == 1) : ?>em-display-none<?php endif; ?>">
            <a class="em-flex-row em-no-hover-underline em-font-size-14 em-pointer"
               style="white-space: nowrap"><?php echo Text::_('MOD_EM_APPLICATION_TABS_MANAGE_TABS') ?></a>
        </div>
    </div>
<?php endif; ?>

<?php if (sizeof($applications) > 0) : ?>
    <div class="em-flex-row em-flex-space-between em-mt-16" id="applications_header_filter_sort">
        <div class="em-flex-row">
            <!-- BUTTONS -->
			<?php if ($mod_em_applications_show_sort == 1) : ?>
                <div id="mod_emundus_application__header_sort"
                     class="mod_emundus_application__header_filter em-border-neutral-400 em-white-bg em-neutral-800-color em-pointer em-mr-8"
                     onclick="displaySort()">
                    <span class="material-symbols-outlined">swap_vert</span>
                    <span class="em-ml-8"><?php echo Text::_('MOD_EM_APPPLICATION_LIST_SORT') ?></span>
                </div>
			<?php endif; ?>

			<?php if ($mod_em_applications_show_filters == 1) : ?>
                <!--            <div id="mod_emundus_application__header_filter" class="mod_emundus_application__header_filter em-border-neutral-400 em-white-bg em-neutral-800-color em-pointer em-mr-8" onclick="displayFilters()">
                    <span class="material-symbols-outlined">filter_list</span>
                    <span class="em-ml-8"><?php /*echo Text::_('MOD_EM_APPPLICATION_LIST_FILTER') */ ?></span>
                    <span id="mod_emundus_campaign__header_filter_count" class="mod_emundus_campaign__header_filter_count em-mr-8"></span>
                </div>-->
			<?php endif; ?>

            <!-- CURRENT SORT -->
			<?php if (!empty($order_by_session)) : ?>
                <div id="mod_emundus_application__header_sort"
                     class="mod_emundus_application__header_filter em-border-neutral-400 em-bg-neutral-200 em-neutral-800-color em-mr-8 em-flex-space-between"
                     style="height: 38px">
                    <span>
                        <?php if ($order_by_session == 'status') : ?>
	                        <?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_STATUS') ?>
                        <?php elseif ($order_by_session == 'campaigns') : ?>
	                        <?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_CAMPAIGN') ?>
                        <?php elseif ($order_by_session == 'programs') : ?>
	                        <?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_PROGRAMS') ?>
                        <?php elseif ($order_by_session == 'last_update') : ?>
	                        <?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_LAST_UPDATE') ?>
                        <?php elseif ($order_by_session == 'years') : ?>
	                        <?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_YEARS') ?>
                        <?php endif; ?>
                    </span>
                    <span class="material-symbols-outlined em-pointer em-ml-8"
                          onclick="filterApplications('applications_order_by','')">close</span>
                </div>
			<?php endif; ?>
        </div>

        <div class="em-flex-row-justify-end tw-flex-wrap" style="gap: 24px">
			<?php if ($mod_em_applications_show_search): ?>
                <div class="em-searchbar em-flex-row-justify-end">
                    <input name="searchword" type="text" id="searchword" class="form-control"
                           placeholder=" ">
                    <label for="searchword"
                           style="display: inline-block;"><?php echo JText::_('MOD_EM_APPLICATIONS_SEARCH') ?></label>
                </div>
			<?php endif; ?>
			<?php if (sizeof($available_views) > 1) : ?>
                <div class="em-flex-row" style="gap: 8px">
					<?php if (in_array('grid', $available_views)) : ?>
                        <div id="button_switch_card"
                             class="em-pointer mod_emundus_application___buttons_switch_view mod_emundus_application___buttons_enable"
                             onclick="updateView('card')">
                            <span class="material-symbols-outlined mod_emundus_application___buttons_switch_view_enable">grid_view</span>
                        </div>
					<?php endif; ?>
					<?php if (in_array('list', $available_views)) : ?>
                        <div id="button_switch_list" class="em-pointer mod_emundus_application___buttons_switch_view"
                             onclick="updateView('list')">
                            <span class="material-symbols-outlined mod_emundus_application___buttons_switch_view_disabled">menu</span>
                        </div>
					<?php endif; ?>
                </div>
			<?php endif; ?>
        </div>
    </div>

    <!-- SORT BLOCK -->
    <div class="mod_emundus_application__header_sort__values em-border-neutral-400 em-neutral-800-color" id="sort_block"
         style="display: none">
        <a onclick="filterApplications('applications_order_by','status')" class="em-text-neutral-900 em-pointer">
			<?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_STATUS') ?>
        </a>
        <a onclick="filterApplications('applications_order_by','campaigns')" class="em-text-neutral-900 em-pointer">
			<?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_CAMPAIGN') ?>
        </a>
        <a onclick="filterApplications('applications_order_by','programs')" class="em-text-neutral-900 em-pointer">
			<?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_PROGRAMS') ?>
        </a>
        <a onclick="filterApplications('applications_order_by','years')" class="em-text-neutral-900 em-pointer">
			<?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_YEARS') ?>
        </a>
        <a onclick="filterApplications('applications_order_by','last_update')" class="em-text-neutral-900 em-pointer">
			<?php echo Text::_('MOD_EM_APPLICATION_LIST_FILTER_GROUP_BY_LAST_UPDATE') ?>
        </a>
    </div>

<?php endif; ?>


<div class="em-mt-32" id="applications_card_view">
	<?php if (sizeof($applications) == 0) : ?>
        <hr>
		<?php if (!empty($override_default_content)) : ?>
            <div class="mod_emundus_applications__list_content--default tw-mt-2 tw-mb-6">
				<?php echo $override_default_content; ?>
            </div>
		<?php endif; ?>

		<?php if (($show_show_campaigns && $applicant_can_renew) || ($show_add_application && $applicant_can_renew)) : ?>
            <div class="hover-and-tile-container hover-and-tile-container-add" style="width: 50%; height: 300px;">
				<?php if ($mod_em_campaign_display_hover_offset == 1) : ?>
                    <div id="tile-hover-offset-request"></div>
				<?php endif; ?>
                <div class="row em-pointer mod_emundus_applications___content_app tw-flex tw-flex-col tw-justify-center tw-items-center">
                    <?php if ($mod_em_campaign_display_svg == 1) : ?>
                       <div id="background-shapes"
                            alt="<?= Text::_('MOD_EM_APPLICATION_IFRAME') ?>"></div>
                    <?php endif; ?>
                    <span class="material-symbols-outlined tw-w-fit">add_circle</span>
                    <p class="tw-w-fit"><?= Text::_('MOD_EMUNDUS_APPLICATIONS_CREATE_APPLICATION_FILE'); ?></p>
                </div>
            </div>
		<?php elseif (empty($override_default_content)) : ?>
            <div class="mod_emundus_applications__list_content--default tw-mt-2 tw-mb-6">
                <h2 class="em-applicant-title-font em-profile-color"><?php echo Text::_('MOD_EM_APPLICATIONS_NO_FILE') ?></h2>
                <p class="em-text-neutral-900 em-default-font em-font-weight-500 em-mb-4"><?php echo Text::_('MOD_EM_APPLICATIONS_NO_FILE_TEXT') ?></p>
            </div>
		<?php endif; ?>
	<?php else : ?>
        <p id="no_file_tab_message_view" class="em-display-none"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_NO_FILE_TAB') ?></p>
        <div class="em-display-none no_file_search_message_view">
            <img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">
            <p style="width: fit-content; margin: 0 auto;"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_NO_FILE_SEARCH') ?></p>
            <a class="em-font-size-16 em-profile-color em-text-underline tw-w-full tw-block tw-text-center" href="<?php echo $campaigns_list_url; ?>"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_NO_FILE_SEARCH_LINK') ?></a>
        </div>
		<?php foreach ($applications as $key => $group) : ?>
			<?php foreach ($group as $g_key => $sub_group) : ?>
				<?php if ((!empty($order_by_session) && !empty($sub_group['applications'])) || !empty($sub_group['applications'][0])) : ?>
                    <div id="group_application_tab_<?php echo $key ?>"
                         class="em-mb-44 <?php if ($key != $current_tab) : ?>em-display-none<?php endif; ?>">
						<?php if (isset($sub_group['label'])) : ?>
                            <h3 class="em-ml-8"><?php echo $sub_group['label'] ?></h3>
                            <hr/>
						<?php endif; ?>
						<?php foreach ($sub_group['applications'] as $f_key => $files) : ?>
							<?php if ($order_by_session == 'years') : ?>
                                <h3 class="em-ml-8"><?php echo $f_key ?></h3>
                                <hr/>
							<?php endif; ?>
                            <div class="<?= $moduleclass_sfx ?> mod_emundus_applications___content em-mb-32">
								<?php foreach ($files as $application) : ?>

									<?php
									$is_admission = false;
									if (!empty($admission_status))
									{
										$is_admission = in_array($application->status, $admission_status);
									}
									$display_app = true;
									if (!empty($show_status) && !in_array($application->status, $show_status))
									{
										$display_app = false;
									}

									if ($display_app)
									{
										$state          = $application->published;
										$confirm_url    = (($absolute_urls === 1) ? '/' : '') . 'index.php?option=com_emundus&task=openfile&fnum=' . $application->fnum . '&confirm=1';
										$first_page_url = (($absolute_urls === 1) ? '/' : '') . 'index.php?option=com_emundus&task=openfile&fnum=' . $application->fnum;
										if ($state == '1' || $show_remove_files == 1 && $state == '-1' || $show_archive_files == 1 && $state == '0') : ?>
											<?php
											if ($file_tags != '')
											{

												$post = array(
													'APPLICANT_ID'   => $user->id,
													'DEADLINE'       => strftime("%A %d %B %Y %H:%M", strtotime($application->end_date)),
													'CAMPAIGN_LABEL' => $application->label,
													'CAMPAIGN_YEAR'  => $application->year,
													'CAMPAIGN_START' => $application->start_date,
													'CAMPAIGN_END'   => $application->end_date,
													'CAMPAIGN_CODE'  => $application->training,
													'FNUM'           => $application->fnum
												);

												$tags              = $m_email->setTags($user->id, $post, $application->fnum, '', $file_tags, false, true);
												$file_tags_display = preg_replace($tags['patterns'], $tags['replacements'], $file_tags);
												$file_tags_display = $m_email->setTagsFabrik($file_tags_display, array($application->fnum));
											}

											$current_phase = $m_workflow->getCurrentWorkflowStepFromFile($application->fnum);

											?>
                                            <div class="hover-and-tile-container"
                                                 id="application_content<?php echo $application->fnum ?>">
												<?php if ($mod_em_campaign_display_hover_offset == 1) : ?>
                                                    <div id="tile-hover-offset-request"></div>
												<?php endif; ?>
                                                <div class="row em-border-neutral-300 mod_emundus_applications___content_app em-pointer"
                                                     onclick="openFile(event,'<?php echo $first_page_url ?>')">
													<?php if ($mod_em_campaign_display_svg == 1) : ?>
                                                        <div id="background-shapes"
                                                             alt="<?= Text::_('MOD_EM_APPLICATION_IFRAME') ?>"></div>
													<?php endif; ?>
                                                    <div class="em-w-100">
														<?php if ($mod_emundus_applications_show_programme == 1) : ?>
                                                            <div class="tw-flex tw-justify-between tw-items-start tw-h-fit">
																<?php
																$color = '#0A53CC';
																if (!empty($application->tag_color))
																{
																	$color = $application->tag_color;
																}
																?>
                                                                <p class="em-programme-tag"
                                                                   style="color: <?php echo $color ?>;margin-bottom: 0">
																	<?php echo $application->programme; ?>
                                                                </p>
                                                                <div class="mod_emundus_applications__container text-xl!" id="actions_button_<?php echo $application->fnum ?>_container_card_tab<?php echo $key ?>">
                                                                <span class="material-symbols-outlined em-text-neutral-600"
                                                                      style="font-size: 24px;"
                                                                      id="actions_button_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>"
                                                                >more_vert</span>
                                                                </div>
                                                            </div>
														<?php endif; ?>
                                                        <div class="em-flex-row em-flex-space-between em-mb-12">
                                                            <div class="tw-flex tw-flex-row tw-items-center tw-gap-2 tw-justify-center">
																<?php
																if (empty($application->class))
																{
																	$application->class = 'default';
																}
																?>
																<?php if (empty($visible_status)) : ?>
                                                                    <div class="tw-flex tw-items-center mod_emundus_applications___status_<?= $application->class; ?> flex"
                                                                         id="application_status_<?php echo $application->fnum ?>">
                                                                        <span class="mod_emundus_applications___status_label label label-<?= $application->class; ?>"><?= $application->value; ?></span>
																		<?php if ($application->applicant_id !== $user->id) : ?>
                                                                            <span class="material-symbols-outlined tw-ml-3">people</span>
																		<?php endif; ?>
                                                                    </div>
																<?php elseif (in_array($application->status, $visible_status)) : ?>
                                                                    <div class="tw-flex tw-items-center mod_emundus_applications___status_<?= $application->class; ?> flex"
                                                                         id="application_status_<?php echo $application->fnum ?>">
                                                                        <span class="mod_emundus_applications___status_label label label-<?= $application->class; ?>"><?= $application->value; ?></span>
																		<?php if ($application->applicant_id !== $user->id) : ?>
                                                                            <span class="material-symbols-outlined tw-ml-3">people</span>
																		<?php endif; ?>
                                                                    </div>
																<?php endif; ?>
																<?php if (!empty($application->order_status)): ?>
                                                                    <br>
                                                                    <p><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_ORDER_STATUS') ?>
                                                                        <span style="color: <?= $application->order_color; ?>"><?= Text::_(strtoupper($application->order_status)); ?></span>
                                                                    </p>
																<?php endif; ?>

																<?php if ($show_nb_comments)
																{
																	$nb_comments = modemundusApplicationsHelper::getNbComments($application->application_id, $user->id);
																	if ($nb_comments > 0)
																	{
																		?>
                                                                        <a href="<?= !empty($comments_page_alias) ? '/' . $comments_page_alias . '?tab=comments&ccid=' . $application->application_id . '&fnum=' . $application->fnum : '#' ?>"
                                                                           id="actions_button_comment"
                                                                           class="tw-flex tw-flex-row comments-icon-wrapper tw-relative tw-ml-2">
                                                                            <span id="actions_button_comment_icon"
                                                                                  class="material-symbols-outlined tw-text-neutral-300 tw-bg-main-500 tw-p-2 tw-rounded-full">comment</span>
                                                                            <span id="actions_button_comment_nb"
                                                                                  class="nb-comments em-border-main-500 em-font-size-12 em-main-500-color em-white-bg tw-border-2 tw-absolute tw-rounded-full tw-p-1"><?= $nb_comments; ?></span>
                                                                        </a>
																		<?php
																	}
																} ?>

																<?php if ($application->show_shared_users && $application->applicant_id === $user->id): ?>
                                                                    <div id="actions_button_collaborate"
                                                                         class="tw-flex tw-flex-row collaborators-icon-wrapper tw-bg-main-500"
                                                                         onclick="shareApplication('<?php echo $application->fnum ?>','<?php echo $application->application_id ?>')">
                                                                        <span id="actions_button_collaborate_icon"
                                                                              class="material-symbols-outlined tw-text-neutral-300">group</span>
                                                                        <span id="actions_button_collaborate_nb"
                                                                              class="nb-collaborators em-profile-color tw-border-main-500 em-font-size-12 tw-bg-neutral-100"><?= sizeof($application->collaborators) ?></span>
                                                                    </div>
																<?php endif; ?>
                                                            </div>
															<?php if ($mod_emundus_applications_show_programme != 1) : ?>
                                                                <div class="mod_emundus_applications__container" id="actions_button_<?php echo $application->fnum ?>_container_card_tab<?php echo $key ?>">
                                                                <span class="material-symbols-outlined em-text-neutral-600"
                                                                      style="font-size: 24px;"
                                                                      id="actions_button_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>"
                                                                >more_vert</span>
                                                                </div>
															<?php endif; ?>
                                                        </div>
														<?php if (empty($application->name)) : ?>
                                                            <a href="<?= JRoute::_($first_page_url); ?>"
                                                               class="mod_emundus_applications___title"
                                                               id="application_title_<?php echo $application->fnum ?>">
                                                                <h5><?= ($is_admission && $add_admission_prefix) ? Text::_('COM_EMUNDUS_INSCRIPTION') . ' - ' . $application->label : $application->label; ?></h5>
                                                            </a>
														<?php else : ?>
                                                            <a href="<?= JRoute::_($first_page_url); ?>"
                                                               class="mod_emundus_applications___title"
                                                               id="application_title_<?php echo $application->fnum ?>">
                                                                <h5><?= $application->name; ?></h5>
                                                            </a>
														<?php endif; ?>
														<?php if ($show_fnum) : ?>
                                                            <div class="em-mb-8">
                                                                <span class="em-applicant-default-font em-text-neutral-600">N°<?php echo $application->fnum ?></span>
                                                            </div>
														<?php endif; ?>
														<?php if (!empty($file_tags_display)) : ?>
                                                            <div class="em-mt-8">
                                                            <span class="em-tags-display em-text-neutral-900">
                                                                <?= $file_tags_display; ?>
                                                            </span>
                                                            </div>
														<?php endif; ?>
                                                    </div>

                                                    <div class="em-flex-row">
														<?php if ($mod_emundus_applications_show_end_date == 1) : ?>
															<?php
															$closed          = false;
															$displayInterval = false;
															$end_date        = $application->end_date;
															if (!empty($current_phase))
															{
																$end_date = $current_phase->end_date;
															}
															if ($now < $end_date)
															{
																$interval = date_create($now)->diff(date_create($end_date));
																if ($interval->y == 0 && $interval->m == 0 && $interval->d == 0)
																{
																	$displayInterval = true;
																}
															}
															else
															{
																$closed = true;
															}
															?>
                                                            <div class="mod_emundus_applications___date em-mt-8">
																<?php if (!$displayInterval && !$closed) : ?>
                                                                    <span class="material-symbols-outlined  em-text-neutral-600 em-mr-8">schedule</span>
                                                                    <p class="em-text-neutral-600 em-applicant-default-font"> <?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_END_DATE'); ?><?php echo JFactory::getDate(new JDate($end_date, $site_offset))->format($date_format); ?></p>
																<?php elseif ($displayInterval && !$closed) : ?>
                                                                    <span class="material-symbols-outlined em-text-neutral-600 em-red-600-color em-mr-8">schedule</span>
                                                                    <p class="em-red-600-color"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_LAST_DAY'); ?>
																		<?php if ($interval->h > 0)
																		{
																			echo $interval->h . 'h' . $interval->i;
																		}
																		else
																		{
																			echo $interval->i . 'm';
																		} ?>
                                                                    </p>
																<?php elseif ($closed) : ?>
                                                                    <span class="material-symbols-outlined em-mr-8 em-red-600-color">schedule</span>
                                                                    <p class="em-applicant-default-font em-red-600-color"> <?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_CLOSED'); ?></p>
																<?php endif; ?>
                                                            </div>
														<?php endif; ?>
                                                    </div>

                                                    <hr/>

                                                    <div class="mod_emundus_applications___informations">
														<?php if ($show_progress == 1) : ?>
                                                            <div>
                                                                <label class="em-text-neutral-600 em-applicant-default-font em-font-size-14"><?= Text::_('MOD_EMUNDUS_APPLICATIONS_COMPLETED'); ?>
                                                                    :</label>
                                                                <p class="em-applicant-default-font em-text-neutral-900"><?php echo(($progress['forms'][$application->fnum] + $progress['attachments'][$application->fnum]) / 2) ?>
                                                                    %</p>
                                                            </div>
														<?php endif; ?>

														<?php if (!empty($application->updated) || !empty($application->submitted_date)) : ?>
                                                            <div>
                                                                <label class="em-text-neutral-600 em-applicant-default-font em-font-size-14"><?= Text::_('MOD_EMUNDUS_APPLICATIONS_LAST_UPDATE'); ?>
                                                                    :</label>
                                                                <p class="em-applicant-default-font em-text-neutral-900">
																	<?php if (empty($application->updated)) : ?>
																		<?php echo EmundusHelperDate::displayDate($application->submitted_date, 'DATE_FORMAT_EMUNDUS'); ?>
																	<?php else : ?>
																		<?php echo EmundusHelperDate::displayDate($application->updated, 'DATE_FORMAT_EMUNDUS', 0); ?>
																	<?php endif; ?>
                                                                </p>
                                                            </div>
														<?php endif; ?>
                                                    </div>

													<?php if ($show_state_files == 1) : ?>
                                                        <div class="">
                                                            <div class="">
                                                                <strong><?= Text::_('MOD_EMUNDUS_STATE'); ?></strong>
																<?php if ($state == 1) : ?>
                                                                    <span class="label alert-success"
                                                                          role="alert"> <?= Text::_('MOD_EMUNDUS_PUBLISH'); ?></span>
																<?php elseif ($state == 0) : ?>
                                                                    <span class="label alert-secondary"
                                                                          role="alert"> <?= Text::_('MOD_EMUNDUS_ARCHIVE'); ?></span>
																<?php else : ?>
                                                                    <span class="label alert-danger"
                                                                          role="alert"><?= Text::_('MOD_EMUNDUS_DELETE'); ?></span>
																<?php endif; ?>
                                                            </div>
                                                        </div>
													<?php endif; ?>
                                                </div>

                                                <!-- ACTIONS BLOCK -->
                                                <div class="mod_emundus_applications__actions em-border-neutral-400 em-neutral-800-color"
                                                     id="actions_block_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>"
                                                     style="display: none"
                                                     data-mid="<?= $module->id ?>"
                                                >
                                                    <a class="em-text-neutral-900 em-pointer em-flex-row"
                                                       href="<?= JRoute::_($first_page_url); ?>"
                                                       id="actions_block_open_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                        <span class="material-symbols-outlined em-mr-8">open_in_new</span>
														<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_OPEN_APPLICATION') ?>
                                                    </a>

													<?php if (in_array('rename', $actions) && ($application->applicant_id === $user->id)) : ?>
                                                        <a class="em-text-neutral-900 em-pointer em-flex-row"
                                                           onclick="renameApplication('<?php echo $application->fnum ?>','<?php echo $application->name ?>','<?php echo $application->label ?>')"
                                                           id="actions_button_rename_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                            <span class="material-symbols-outlined em-mr-8">drive_file_rename_outline</span>
															<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_RENAME_APPLICATION') ?>
                                                        </a>
													<?php endif; ?>

													<?php if (!empty($available_campaigns) && in_array('copy', $actions) && ($application->applicant_id === $user->id)) : ?>
                                                        <a class="em-text-neutral-900 em-pointer em-flex-row"
                                                           onclick="copyApplication('<?php echo $application->fnum ?>')"
                                                           id="actions_button_copy_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                            <span class="material-symbols-outlined em-mr-8">file_copy</span>
															<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COPY_APPLICATION') ?>
                                                        </a>
													<?php endif; ?>

													<?php if (in_array('collaborate', $actions) && ($application->applicant_id === $user->id)) : ?>
                                                        <a class="tw-text-neutral-900 tw-cursor-pointer tw-flex"
                                                           onclick="shareApplication('<?php echo $application->fnum ?>','<?php echo $application->application_id ?>')"
                                                           id="actions_button_collaborate_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                            <span class="material-symbols-outlined tw-mr-2">people</span>
															<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_ACTIONS_COLLABORATE') ?>
                                                        </a>
													<?php endif; ?>

													<?php if ($show_tabs == 1) : ?>
                                                        <a class="tw-text-neutral-900 tw-cursor-pointer tw-flex"
                                                           onclick="moveToTab('<?php echo $application->fnum ?>','tab<?php echo $key ?>','card')"
                                                           id="actions_button_move_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                            <span class="material-symbols-outlined tw-mr-2">drive_file_move</span>
															<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_MOVE_INTO_TAB') ?>
                                                        </a>
													<?php endif; ?>

                                                    <?php if (in_array('documents', $actions) && ($application->applicant_id === $user->id || $application->show_history == 1)) : ?>
                                                        <a class="tw-text-neutral-900 tw-cursor-pointer tw-flex"
                                                           href="<?= Route::_($history_link->route . '?ccid=' . $application->application_id . '&fnum=' . $application->fnum . '&tab=attachments'); ?>"
                                                           id="actions_button_documents_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                            <span class="material-symbols-outlined tw-mr-2">description</span>
															<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_CONSULT_DOCUMENTS') ?>
                                                        </a>
													<?php endif; ?>

													<?php if (in_array('history', $actions) && ($application->applicant_id === $user->id || $application->show_history == 1)) : ?>
                                                        <a class="tw-text-neutral-900 tw-cursor-pointer tw-flex"
                                                           href="<?= Route::_($history_link->route . '?ccid=' . $application->application_id . '&fnum=' . $application->fnum); ?>"
                                                           id="actions_button_history_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                            <span class="material-symbols-outlined tw-mr-2">history</span>
															<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_VIEW_HISTORY') ?>
                                                        </a>
													<?php endif; ?>

													<?php if (in_array($application->status, $status_for_delete) && ($application->applicant_id === $user->id)) : ?>
                                                        <a class="em-red-600-color em-flex-row em-pointer"
                                                           onclick="deletefile('<?php echo $application->fnum; ?>');"
                                                           id="actions_block_delete_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                            <span class="material-symbols-outlined em-red-600-color em-mr-8">delete</span>
															<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_DELETE_APPLICATION_FILE') ?>
                                                        </a>
													<?php endif; ?>

                                                    <?php if (in_array('transactions', $actions) && ($application->applicant_id === $user->id)) : ?>
                                                        <a class="tw-text-neutral-900 tw-cursor-pointer tw-flex"
                                                           href="<?= Route::_('/index.php?option=com_emundus&view=payment&layout=transactions&fnum=' . $application->fnum); ?>"
                                                           id="actions_button_history_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                            <span class="material-symbols-outlined tw-mr-2">paid</span>
		                                                    <?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_VIEW_TRANSACTIONS') ?>
                                                        </a>
                                                    <?php endif; ?>

													<?php
													modemundusApplicationsHelper::displayCustomActions($application, $custom_actions, $key);
													?>
                                                </div>
                                                <!-- END ACTIONS BLOCK -->
                                            </div>
										<?php endif; ?>
									<?php } ?>
								<?php endforeach; ?>
	                            <?php if (($show_show_campaigns && $applicant_can_renew) || ($show_add_application && $applicant_can_renew)) : ?>
                                    <div class="hover-and-tile-container  hover-and-tile-container-add" style="height: 300px;">
										<?php if ($mod_em_campaign_display_hover_offset == 1) : ?>
                                            <div id="tile-hover-offset-request"></div>
										<?php endif; ?>
                                        <div class="row em-pointer mod_emundus_applications___content_app tw-flex tw-flex-col tw-justify-center tw-items-center">
                                           <?php if ($mod_em_campaign_display_svg == 1) : ?>
                                               <div id="background-shapes"
                                                    alt="<?= Text::_('MOD_EM_APPLICATION_IFRAME') ?>"></div>
                                           <?php endif; ?>
                                            <span class="material-symbols-outlined tw-w-fit">add_circle</span>
                                            <p class="tw-w-fit"><?= Text::_('MOD_EMUNDUS_APPLICATIONS_CREATE_APPLICATION_FILE'); ?></p>
                                        </div>
                                    </div>
								<?php endif; ?>
                            </div>
						<?php endforeach; ?>
                    </div>
				<?php endif; ?>
			<?php endforeach ?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

<div class="em-mt-32" id="applications_list_view" style="display: none">
	<?php if (sizeof($applications) == 0) : ?>
        <hr>

		<?php if (!empty($override_default_content)) : ?>
            <div class="mod_emundus_applications__list_content--default tw-mt-2 tw-mb-6">
				<?php echo $override_default_content; ?>
            </div>
		<?php endif; ?>

		<?php if (($show_show_campaigns && $applicant_can_renew) || ($show_add_application && $applicant_can_renew)) : ?>
            <div class="hover-and-tile-container hover-and-tile-container-add"  style="height: 300px;">
				<?php if ($mod_em_campaign_display_hover_offset == 1) : ?>
                    <div id="tile-hover-offset-request"></div>
				<?php endif; ?>
                <div class="row em-pointer mod_emundus_applications___content_app tw-flex tw-flex-col tw-justify-center tw-items-center">
                    <?php if ($mod_em_campaign_display_svg == 1) : ?>
                       <div id="background-shapes"
                            alt="<?= Text::_('MOD_EM_APPLICATION_IFRAME') ?>"></div>
                    <?php endif; ?>
                    <span class="material-symbols-outlined tw-w-fit">add_circle</span>
                    <p class="tw-w-fit"><?= Text::_('MOD_EMUNDUS_APPLICATIONS_CREATE_APPLICATION_FILE'); ?></p>
                </div>
            </div>
		<?php elseif (empty($override_default_content)) : ?>
            <div class="mod_emundus_applications__list_content--default tw-mt-2 tw-mb-6">
                <h2 class="em-applicant-title-font em-profile-color"><?php echo Text::_('MOD_EM_APPLICATIONS_NO_FILE') ?></h2>
                <p class="em-text-neutral-900 em-default-font em-font-weight-500 em-mb-4"><?php echo Text::_('MOD_EM_APPLICATIONS_NO_FILE_TEXT') ?></p>
            </div>
		<?php endif; ?>
	<?php else : ?>
        <h4 id="no_file_tab_message_list" class="em-display-none"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_NO_FILE_TAB') ?></h4>
        <div class="em-display-none no_file_search_message_view">
            <img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">
            <p style="width: fit-content; margin: 0 auto;"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_NO_FILE_SEARCH') ?></p>
            <a class="em-font-size-16 em-profile-color em-text-underline tw-w-full tw-block tw-text-center" href="<?php echo $campaigns_list_url; ?>"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_NO_FILE_SEARCH_LINK') ?></a>
        </div>

        <?php foreach ($applications as $key => $group) : ?>
			<?php foreach ($group as $g_key => $sub_group) : ?>
				<?php if ((!empty($order_by_session) && !empty($sub_group['applications'])) || !empty($sub_group['applications'][0])) : ?>
                    <div id="group_application_tab_<?php echo $key ?>"
                         class="em-mb-44 <?php if ($key != $current_tab) : ?>em-display-none<?php endif; ?>">

						<?php if (isset($sub_group['label'])) : ?>
                            <h3 class="em-ml-8"><?php echo $sub_group['label'] ?></h3>
                            <hr/>
						<?php endif; ?>
                        <table class="em-mb-12 applications_list_view--tables">
                            <thead>
                            <tr>
                                <th></th>
                                <th style="width: 23.75%;"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_RENAME_APPLICATION_NAME') ?></th>
                                <th style="width: 23.75%;"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_LAST_UPDATE') ?></th>
								<?php if ($show_progress == 1) : ?>
                                    <th style="width: 23.75%;"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COMPLETED') ?></th>
								<?php endif; ?>
                                <th style="width: 23.75%;"><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_STATUS') ?></th>
                                <th style="width: 5%;"></th>
                            </tr>
                            </thead>
                        </table>
						<?php foreach ($sub_group['applications'] as $f_key => $files) : ?>
							<?php if ($order_by_session == 'years') : ?>
                                <div class="em-mt-12 em-flex-row em-white-bg em-applicant-border-radius em-p-6-12">
                                    <span class="material-symbols-outlined em-mr-8">expand_more</span>
                                    <h2 style="margin-top: 0"><?php echo $f_key ?></h2>
                                </div>
							<?php endif; ?>
                            <table class="em-ml-12" style="border-collapse: separate;border-spacing: 0 6px;">
                                <tbody>
								<?php foreach ($files as $application) : ?>

									<?php
									$is_admission = in_array($application->status, $admission_status);
									$display_app  = true;
									if (!empty($show_status) && !in_array($application->status, $show_status))
									{
										$display_app = false;
									}

									if ($display_app)
									{
										$state          = $application->published;
										$confirm_url    = (($absolute_urls === 1) ? '/' : '') . 'index.php?option=com_emundus&task=openfile&fnum=' . $application->fnum . '&confirm=1';
										$first_page_url = (($absolute_urls === 1) ? '/' : '') . 'index.php?option=com_emundus&task=openfile&fnum=' . $application->fnum;
										if ($state == '1' || $show_remove_files == 1 && $state == '-1' || $show_archive_files == 1 && $state == '0') : ?>
											<?php
											if ($file_tags != '')
											{

												$post = array(
													'APPLICANT_ID'   => $user->id,
													'DEADLINE'       => strftime("%A %d %B %Y %H:%M", strtotime($application->end_date)),
													'CAMPAIGN_LABEL' => $application->label,
													'CAMPAIGN_YEAR'  => $application->year,
													'CAMPAIGN_START' => $application->start_date,
													'CAMPAIGN_END'   => $application->end_date,
													'CAMPAIGN_CODE'  => $application->training,
													'FNUM'           => $application->fnum
												);

												$tags              = $m_email->setTags($user->id, $post, $application->fnum, '', $file_tags, false, true);
												$file_tags_display = preg_replace($tags['patterns'], $tags['replacements'], $file_tags);
												$file_tags_display = $m_email->setTagsFabrik($file_tags_display, array($application->fnum));
											}

											$current_phase = $m_workflow->getCurrentWorkflowStepFromFile($application->fnum);

											?>
                                            <tr class="em-pointer"
                                                id="application_content<?php echo $application->fnum ?>"
                                                onclick="openFile(event,'<?php echo $first_page_url ?>')">
                                                <td style="width: 23.75%;">
													<?php if ($mod_em_campaign_display_svg == 1) : ?>
                                                        <div id="background-shapes"
                                                             alt="<?= Text::_('MOD_EM_APPLICATION_IFRAME') ?>"></div>
													<?php endif; ?>
													<?php if (empty($application->name)) : ?>
                                                        <a href="<?= JRoute::_($first_page_url); ?>"
                                                           class="mod_emundus_applications___title em-font-size-14"
                                                           id="application_title_<?php echo $application->fnum ?>">
                                                            <span><?= ($is_admission && $add_admission_prefix) ? Text::_('COM_EMUNDUS_INSCRIPTION') . ' - ' . $application->label : $application->label; ?></span>
                                                        </a>
													<?php else : ?>
                                                        <a href="<?= JRoute::_($first_page_url); ?>"
                                                           class="mod_emundus_applications___title em-font-size-14"
                                                           id="application_title_<?php echo $application->fnum ?>">
                                                            <span><?= $application->name; ?></span>
                                                        </a>
													<?php endif; ?>
                                                </td>
                                                <td style="width: 23.75%;">
													<?php if (!empty($application->updated) || !empty($application->submitted_date)) : ?>
                                                        <div>
                                                            <p class="em-applicant-default-font em-text-neutral-900 em-font-size-14">
																<?php if (empty($application->updated)) : ?>
																	<?php echo EmundusHelperDate::displayDate($application->submitted_date, 'DATE_FORMAT_EMUNDUS'); ?>
																<?php else : ?>
																	<?php echo EmundusHelperDate::displayDate($application->updated, 'DATE_FORMAT_EMUNDUS', 0); ?>
																<?php endif; ?>
                                                            </p>
                                                        </div>
													<?php endif; ?>
                                                </td>
												<?php if ($show_progress == 1) : ?>
                                                    <td style="width: 23.75%;">
                                                        <p class="em-applicant-default-font em-text-neutral-900 em-font-size-14"><?php echo(($progress['forms'][$application->fnum] + $progress['attachments'][$application->fnum]) / 2) ?>
                                                            %</p>
                                                    </td>
												<?php endif; ?>
                                                <td style="width: 23.75%;">
                                                    <div>
														<?php
														if (empty($application->class))
														{
															$application->class = 'default';
														}
														?>
														<?php if (empty($visible_status)) : ?>
                                                            <div class="mod_emundus_applications___status_<?= $application->class; ?> tw-flex tw-items-center"
                                                                 id="application_status_<?php echo $application->fnum ?>">
                                                                <span class="mod_emundus_applications___status_label label label-<?= $application->class; ?>"><?= $application->value; ?></span>
																<?php if ($application->applicant_id !== $user->id) : ?>
                                                                    <span class="material-symbols-outlined tw-ml-3">people</span>
																<?php endif; ?>
                                                            </div>
														<?php elseif (in_array($application->status, $visible_status)) : ?>
                                                            <div class="mod_emundus_applications___status_<?= $application->class; ?> tw-flex tw-items-center"
                                                                 id="application_status_<?php echo $application->fnum ?>">
                                                                <span class="mod_emundus_applications___status_label label label-<?= $application->class; ?>"><?= $application->value; ?></span>
																<?php if ($application->applicant_id !== $user->id) : ?>
                                                                    <span class="material-symbols-outlined tw-ml-3">people</span>
																<?php endif; ?>
                                                            </div>
														<?php endif; ?>
														<?php if (!empty($application->order_status)): ?>
                                                            <br>
                                                            <p><?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_ORDER_STATUS') ?>
                                                                <span style="color: <?= $application->order_color; ?>"><?= Text::_(strtoupper($application->order_status)); ?></span>
                                                            </p>
														<?php endif; ?>
                                                    </div>
                                                </td>
                                                <td style="width: 5%;">
                                                    <div class="mod_emundus_applications__container" id="actions_button_<?php echo $application->fnum ?>_container_list_tab<?php echo $key ?>">
                                                            <span class="material-symbols-outlined em-text-neutral-600"
                                                                  style="font-size: 24px;"
                                                                  id="actions_button_<?php echo $application->fnum ?>_list_tab<?php echo $key ?>"
                                                            >more_vert</span>

                                                        <!-- ACTIONS BLOCK -->
                                                        <div class="mod_emundus_applications__actions em-border-neutral-400 em-neutral-800-color"
                                                             id="actions_block_<?php echo $application->fnum ?>_list_tab<?php echo $key ?>"
                                                             style="display: none"
                                                             data-mid="<?= $module->id ?>"
                                                        >
                                                            <a class="em-text-neutral-900 em-pointer em-flex-row"
                                                               href="<?= JRoute::_($first_page_url); ?>"
                                                               id="actions_block_open_<?php echo $application->fnum ?>_list_tab<?php echo $key ?>">
                                                                <span class="material-symbols-outlined em-mr-8">open_in_new</span>
																<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_OPEN_APPLICATION') ?>
                                                            </a>

															<?php if (in_array('rename', $actions) && ($application->applicant_id === $user->id)) : ?>
                                                                <a class="em-text-neutral-900 em-pointer em-flex-row"
                                                                   onclick="renameApplication('<?php echo $application->fnum ?>','<?php echo $application->name ?>','<?php echo $application->label ?>')"
                                                                   id="actions_button_rename_<?php echo $application->fnum ?>_list_tab<?php echo $key ?>">
                                                                    <span class="material-symbols-outlined em-mr-8">drive_file_rename_outline</span>
																	<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_RENAME_APPLICATION') ?>
                                                                </a>
															<?php endif; ?>

															<?php if (!empty($available_campaigns) && in_array('copy', $actions) && ($application->applicant_id === $user->id)) : ?>
                                                                <a class="em-text-neutral-900 em-pointer em-flex-row"
                                                                   onclick="copyApplication('<?php echo $application->fnum ?>')"
                                                                   id="actions_button_copy_<?php echo $application->fnum ?>_list_tab<?php echo $key ?>">
                                                                    <span class="material-symbols-outlined em-mr-8">file_copy</span>
																	<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COPY_APPLICATION') ?>
                                                                </a>
															<?php endif; ?>

															<?php if ($show_tabs == 1) : ?>
                                                                <a class="em-text-neutral-900 em-pointer em-flex-row"
                                                                   onclick="moveToTab('<?php echo $application->fnum ?>','tab<?php echo $key ?>','list')"
                                                                   id="actions_button_move_<?php echo $application->fnum ?>_list_tab<?php echo $key ?>">
                                                                    <span class="material-symbols-outlined em-mr-8">drive_file_move</span>
																	<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_MOVE_INTO_TAB') ?>
                                                                </a>
															<?php endif; ?>

															<?php if (in_array('collaborate', $actions) && ($application->applicant_id === $user->id)) : ?>
                                                                <a class="em-text-neutral-900 em-pointer em-flex-row"
                                                                   onclick="shareApplication('<?php echo $application->fnum ?>','<?php echo $application->application_id ?>')"
                                                                   id="actions_button_collaborate_<?php echo $application->fnum ?>_list_tab<?php echo $key ?>">
                                                                    <span class="material-symbols-outlined em-mr-8">people</span>
																	<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_ACTIONS_COLLABORATE') ?>
                                                                </a>
															<?php endif; ?>

                                                            <?php if (in_array('documents', $actions) && ($application->applicant_id === $user->id || $application->show_history == 1)) : ?>
                                                                <a class="tw-text-neutral-900 tw-cursor-pointer tw-flex"
                                                                   href="<?= Route::_($history_link->route . '?ccid=' . $application->application_id . '&fnum=' . $application->fnum . '&tab=attachments'); ?>"
                                                                   id="actions_button_documents_<?php echo $application->fnum ?>_card_tab<?php echo $key ?>">
                                                                    <span class="material-symbols-outlined tw-mr-2">description</span>
                                                                    <?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_CONSULT_DOCUMENTS') ?>
                                                                </a>
                                                            <?php endif; ?>

															<?php if (in_array('history', $actions) && ($application->applicant_id === $user->id || $application->show_history == 1)) : ?>
                                                                <a class="em-text-neutral-900 em-pointer em-flex-row"
                                                                   href="<?= Route::_($history_link->route . '?ccid=' . $application->application_id . '&fnum=' . $application->fnum); ?>"
                                                                   id="actions_button_history_<?php echo $application->fnum ?>_list_tab<?php echo $key ?>">
                                                                    <span class="material-symbols-outlined em-mr-8">history</span>
																	<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_VIEW_HISTORY') ?>
                                                                </a>
															<?php endif; ?>

															<?php if (in_array($application->status, $status_for_delete) && ($application->applicant_id === $user->id)) : ?>
                                                                <a class="em-red-600-color em-flex-row em-pointer"
                                                                   onclick="deletefile('<?php echo $application->fnum; ?>');"
                                                                   id="actions_block_delete_<?php echo $application->fnum ?>_list_tab<?php echo $key ?>">
                                                                    <span class="material-symbols-outlined em-red-600-color em-mr-8">delete</span>
																	<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_DELETE_APPLICATION_FILE') ?>
                                                                </a>
															<?php endif; ?>

															<?php
															modemundusApplicationsHelper::displayCustomActions($application, $custom_actions, $key);
															?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
										<?php endif; ?>
									<?php } ?>
								<?php endforeach; ?>
						        <?php if (($show_show_campaigns && $applicant_can_renew) || ($show_add_application && $applicant_can_renew)) : ?>
                                <tr class="em-pointer list-application-add">
                                    <td>
                                        <?php if ($mod_em_campaign_display_svg == 1) : ?>
                                            <div id="background-shapes" alt="<?= Text::_('MOD_EM_APPLICATION_IFRAME') ?>"></div>
                                        <?php endif; ?>
                                        <p class="tw-w-fit"><?= Text::_('MOD_EMUNDUS_APPLICATIONS_CREATE_APPLICATION_FILE'); ?></p>
                                    </td>
                                    <td></td>
                                    <td>
                                        <span class="material-symbols-outlined tw-w-fit">add_circle</span>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
						<?php endforeach; ?>
                    </div>
				<?php endif; ?>
			<?php endforeach ?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

<div style="display: none">
    <div id="swal_manage" class="em-w-100">
        <ul id="items">
        </ul>
    </div>
</div>


<?php if ($show_add_application && ($position_add_application == 1 || $position_add_application == 2 || $position_add_application == 4) && $applicant_can_renew) : ?>
    <div class="mod_emundus_applications___footer">
        <a class="btn btn-success" href="<?= $cc_list_url; ?>"><span
                    class="icon-plus-sign"> <?= Text::_('MOD_EMUNDUS_APPLICATIONS_ADD_APPLICATION_FILE'); ?></span></a>
    </div>
<?php endif; ?>

<?php if (!empty($filled_poll_id) && !empty($poll_url) && $filled_poll_id == 0 && $poll_url != "") : ?>
    <div class="modal fade" id="em-modal-form" style="z-index:99999" tabindex="-1" role="dialog"
         aria-labelledby="em-modal-form" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <h4 class="modal-title" id="em-modal-form-title"><?= Text::_('LOADING'); ?></h4>
                    <img src="media/com_emundus/images/icones/loader-line.gif">
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var poll_url = "<?= $poll_url; ?>";
        if ($poll_url !== "") {
            jQuery(".modal-body").html('<iframe src="' + poll_url + '" style="width:' + window.getWidth() * 0.8 + 'px; height:' + window.getHeight() * 0.8 + 'px; border:none"></iframe>');
            setTimeout(function () {
                jQuery('#em-modal-form').modal({backdrop: true, keyboard: true}, 'toggle');
            }, 1000);
        }
    </script>

<?php endif; ?>

<!-- jsDelivr :: Sortable :: Latest (https://www.jsdelivr.com/package/npm/sortablejs) -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script type="text/javascript">
    var $ = jQuery.noConflict();

    window.addEventListener('DOMContentLoaded', (event) => {
        let selected_tab_session = sessionStorage.getItem('mod_emundus_applications___selected_tab');
        let selected_view = sessionStorage.getItem('mod_emundus_applications___selected_view');
        if (selected_tab_session !== null) {
            this.updateTab(selected_tab_session);
        }
        if (selected_view !== null) {
            this.updateView(selected_view);
        }

        const emptyTiles = document.querySelectorAll('.hover-and-tile-container-add, .list-application-add');

        emptyTiles.forEach((tile) => {
            tile.addEventListener('click', (event) => {
                <?php if($show_add_application && $applicant_can_renew) : ?>
                window.location.href = "<?php echo $cc_list_url; ?>";
                <?php else : ?>
                window.location.href = "<?php echo $campaigns_list_url; ?>";
                <?php endif; ?>
            });
        });
        /*document.querySelector(".hover-and-tile-container-add").addEventListener('click', (event) => {
            console.log('here')
		    <?php if($show_add_application && $applicant_can_renew) : ?>
            window.location.href = "<?php echo $cc_list_url; ?>";
		    <?php else : ?>
            window.location.href = "<?php echo $campaigns_list_url; ?>";
		    <?php endif; ?>
        });*/
    });

    function deletefile(fnum) {
        Swal.fire({
            title: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_CONFIRM_DELETE_FILE'); ?>",
            text: "",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#dc3545",
            reverseButtons: true,
            confirmButtonText: "<?php echo Text::_('JYES');?>",
            cancelButtonText: "<?php echo Text::_('JNO');?>"
        }).then((confirm) => {
            if (confirm.value) {
                document.location.href = "/index.php?option=com_emundus&task=deletefile&fnum=" + fnum + "&redirect=<?php echo base64_encode(JUri::getInstance()->getPath()); ?>";
            }
        });
    }

    function delay(callback, ms) {
        var timer = 0;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                callback.apply(context, args);
            }, ms || 0);
        };
    }

    jQuery(function () {
        jQuery('[data-toggle="tooltip"]').tooltip()
    })

    document.addEventListener('click', function (e) {
        let target = e.target.id;
        let actions = document.querySelectorAll("[id^='actions_block_']");
        let modal = document.querySelector('.swal2-container');

        if (typeof actions !== 'undefined') {
            actions.forEach((action) => {
                if (action.style.display === 'flex') {
                    action.style.display = 'none';
                }
            });

            if (target.indexOf('actions_button_') !== -1) {
                let url = target.split('_');
                let fnum = url[url.length - 3];
                let tab = url[url.length - 1];
                let view = url[url.length - 2];

                actions = document.getElementById('actions_block_' + fnum + '_' + view + '_' + tab);
                if (actions) {
                    if (modal !== null) {
                        actions.style.display = 'none';
                    } else if (actions.style.display === 'none') {
                        actions.style.display = 'flex';
                    } else {
                        actions.style.display = 'none';
                    }
                }
            }
        }
    });

    function openFile(e, url) {
        let target = e.target.id;

        if (target.indexOf('actions_button_') !== -1 || target.indexOf('actions_block_delete_') !== -1) {
            //do nothing
        } else {
            window.location.href = '/' + url;
        }
    }

    jQuery('#applications_searchbar').keyup(delay(function (e) {
        let search = e.target.value;

        if (search !== '') {
            let campaigns = document.querySelectorAll('.mod_emundus_applications___title span');
            let status = document.querySelectorAll('.mod_emundus_applications___status_label');
            let fnums_to_hide = [];
            let fnums_to_show = [];

            for (let campaign of campaigns) {
                let fnum = campaign.parentElement.id.split('_');
                fnum = fnum[fnum.length - 1];

                if (campaign.textContent.normalize('NFD').replace(/\p{Diacritic}/gu, "").toLowerCase().includes(search.normalize('NFD').replace(/\p{Diacritic}/gu, "").toLowerCase()) === false) {
                    fnums_to_hide.push(fnum);
                } else {
                    fnums_to_show.push(fnum);
                }
            }

            for (let step of status) {
                let fnum = step.parentElement.id.split('_');
                fnum = fnum[fnum.length - 1];

                if (step.textContent.normalize('NFD').replace(/\p{Diacritic}/gu, "").toLowerCase().includes(search.normalize('NFD').replace(/\p{Diacritic}/gu, "").toLowerCase()) === false) {
                    if (fnums_to_show.indexOf(fnum) !== -1) {
                        fnums_to_hide.push(fnum);
                    }
                } else {
                    fnums_to_show.push(fnum);
                    if (fnums_to_hide.indexOf(fnum) !== -1) {
                        fnums_to_hide.splice(fnums_to_hide.indexOf(fnum), 1);
                    }
                }
            }

            fnums_to_hide.forEach((fnum) => {
                document.querySelectorAll('#application_content' + fnum).forEach((block) => {
                    block.style.display = 'none';
                })
            })
            fnums_to_show.forEach((fnum) => {
                document.querySelectorAll('#application_content' + fnum).forEach((block) => {
                    if (block.nodeName === 'TR') {
                        block.style.display = 'flex';
                    } else {
                        block.style.display = 'block';
                    }
                })
            });

            if (fnums_to_show.length === 0) {
                document.getElementsByClassName('no_file_search_message_view').forEach((elt) => {
                    elt.style.display = 'block';
                })
                document.getElementsByClassName('applications_list_view--tables').forEach((elt) => {
                    elt.style.display = 'none';
                })
                document.title = "<?php echo JText::_('MOD_EMUNDUS_APPLICATIONS_NO_FILE_SEARCH'); ?>";
            } else {
                document.getElementsByClassName('no_file_search_message_view').forEach((elt) => {
                    elt.style.display = 'none';
                })
                document.getElementsByClassName('applications_list_view--tables').forEach((elt) => {
                    elt.style.display = 'block';
                })
                document.title = "<?php echo JText::_('MOD_EMUNDUS_APPLICATIONS_FILE_SEARCH_RESET'); ?>";
            }
        } else {
            for (let application of document.querySelectorAll("div[id^='application_content'],tr[id^='application_content']")) {
                if (application.nodeName === 'TR') {
                    application.style.display = 'flex';
                } else {
                    application.style.display = 'block';
                }
            }

            document.getElementsByClassName('no_file_search_message_view').forEach((elt) => {
                elt.style.display = 'none';
            })
            document.getElementsByClassName('applications_list_view--tables').forEach((elt) => {
                elt.style.display = 'block';
            })
        }

    }, 500));


    /** VIEWS **/
    function updateView(view) {
        sessionStorage.setItem("mod_emundus_applications___selected_view", view);
        if (view === 'list') {
            document.querySelector('#applications_card_view').style.display = 'none';
            document.querySelector('#applications_list_view').style.display = 'block';
            if(document.querySelector('#button_switch_card')) {
                document.querySelector('#button_switch_card').classList.remove('mod_emundus_application___buttons_enable');
                document.querySelector('#button_switch_card span').classList.remove('mod_emundus_application___buttons_switch_view_enable');
                document.querySelector('#button_switch_card span').classList.add('mod_emundus_application___buttons_switch_view_disabled');
            }
            if(document.querySelector('#button_switch_list')) {
                document.querySelector('#button_switch_list').classList.add('mod_emundus_application___buttons_enable');
                document.querySelector('#button_switch_list span').classList.remove('mod_emundus_application___buttons_switch_view_disabled');
                document.querySelector('#button_switch_list span').classList.add('mod_emundus_application___buttons_switch_view_enable');
            }
        } else {
            document.querySelector('#applications_list_view').style.display = 'none';
            document.querySelector('#applications_card_view').style.display = 'block';
            if(document.querySelector('#button_switch_list')) {
                document.querySelector('#button_switch_list').classList.remove('mod_emundus_application___buttons_enable');
                document.querySelector('#button_switch_list span').classList.remove('mod_emundus_application___buttons_switch_view_enable');
                document.querySelector('#button_switch_list span').classList.add('mod_emundus_application___buttons_switch_view_disabled');
            }
            if(document.querySelector('#button_switch_card')) {
                document.querySelector('#button_switch_card').classList.add('mod_emundus_application___buttons_enable');
                document.querySelector('#button_switch_card span').classList.remove('mod_emundus_application___buttons_switch_view_disabled');
                document.querySelector('#button_switch_card span').classList.add('mod_emundus_application___buttons_switch_view_enable');
            }
        }

    }

    /** TABS **/
    function updateTab(tab) {
        document.getElementById('applications_header_filter_sort').style.display = 'flex';
        document.querySelectorAll('h4[id*="no_file_tab_message_"]').forEach((elt) => {
            elt.style.display = 'none'
        })

        sessionStorage.setItem("mod_emundus_applications___selected_tab", tab);
        document.querySelectorAll('div[id*="tab_link_"]').forEach((elt) => {
            if (elt.id !== 'tab_link_' + tab) {
                elt.classList.remove('em-light-selected-tab');
            } else {
                elt.classList.add('em-light-selected-tab');
            }
        })

        document.querySelectorAll('div[id*="group_application_tab_"]').forEach((elt) => {
            if (elt.id !== 'group_application_tab_' + tab) {
                elt.classList.add('em-display-none');
            } else {
                elt.classList.remove('em-display-none');
            }
        })

        if (!document.querySelector('#group_application_tab_' + tab)) {
            document.querySelectorAll('h4[id*="no_file_tab_message_"]').forEach((elt) => {
                elt.style.display = 'block'
            })

            document.getElementById('applications_header_filter_sort').style.display = 'none';
        }
    }

    async function createTab() {
        const {value: tabName} = await Swal.fire({
            title: "<?= Text::_('MOD_EM_APPLICATION_TABS_CREATE_TAB_SWAL'); ?>",
            input: 'text',
            inputAttributes: {
                maxlength: 30,
            },
            text: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_NAME'); ?>",
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_CREATE_BUTTON');?>",
            cancelButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_CANCEL_BUTTON');?>",
            customClass: {
                container: 'mod_emundus_application_swal_manage_tabs_container',
                popup: 'mod_emundus_application_swal_manage_tabs_popup',
                header: 'mod_emundus_application_swal_manage_tabs_header',
                htmlContainer: 'mod_emundus_application_swal_manage_tabs_content',
                confirmButton: 'mod_emundus_application_swal_manage_tabs_confirm',
                cancelButton: 'mod_emundus_application_swal_manage_tabs_cancel',
                actions: 'mod_emundus_application_swal_manage_tabs_actions',
            },
            inputValidator: (value) => {
                const regex = /^[\d\s\p{L}'"\-]{3,255}$/u;

                if (!value) {
                    return "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_PLEASE_FILL_NAME');?>";
                }
                if (value.length < 3) {
                    return "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_NAME_TOO_SHORT');?>";
                }
                if (value.length > 30) {
                    return "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_NAME_TOO_LONG');?>";
                }
                if (!regex.exec(value)) {
                    return "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_NAME_INVALID');?>";
                }
            }
        });

        if (tabName) {
            let formData = new FormData();
            formData.append('name', tabName);

            fetch('/index.php?option=com_emundus&controller=application&task=createtab', {
                body: formData,
                method: 'post',
            }).then((response) => {
                if (response.ok) {
                    return response.json();
                }
            }).then((res) => {
                if (res.tab != 0) {
                    let html = '<div id="tab_link_' + res.tab + '" onclick="updateTab(' + res.tab + ')" class="em-flex-row em-light-tabs em-pointer"><p class="em-font-size-14 em-text-neutral-600" style="white-space: nowrap">' + tabName + '</p><span class="mod_emundus_applications_badge">0</span></div>';
                    document.getElementById('tab_adding_link').insertAdjacentHTML('beforebegin', html);
                    document.getElementById('tab_adding_link').style.display = 'none';
                    document.getElementById('tab_manage_links').style.display = 'flex';
                } else {
                    Swal.fire({
                        title: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_AN_ERROR_OCCURED');?>",
                        text: "",
                        type: "error",
                        reverseButtons: true,
                        confirmButtonText: "<?php echo Text::_('JYES');?>",
                        timer: 3000
                    });
                }
            })
        }
    }

    async function manageTabs() {
        document.getElementById('items').innerHTML = '';
        if (document.getElementById('add_link_manage') != null) {
            document.getElementById('add_link_manage').remove();
        }
        fetch('/index.php?option=com_emundus&controller=application&task=gettabs', {
            method: 'get',
        }).then((response) => {
            if (response.ok) {
                return response.json();
            }
        }).then((res) => {
            res.tabs.forEach((tab) => {
                let item = document.createElement('li');
                item.classList.add('em-flex-row', 'em-mb-12', 'em-grab', 'em-flex-space-between');
                item.id = 'tab_li_' + tab.id;
                item.innerHTML = '<div class="em-flex-row"><span class="material-symbols-outlined em-font-size-14 em-mr-4">drag_indicator</span><span contenteditable="true" class="em-cursor-text" id="' + tab.id + '">' + tab.name + '</span></div><span class="material-symbols-outlined em-mr-4 em-pointer em-red-600-color" onclick="deleteTab(' + tab.id + ',\'' + tab.name + '\')">close</span>';
                document.getElementById('items').appendChild(item);
            });
            let link_to_add = document.createElement('a');
            link_to_add.classList.add('em-flex-row', 'em-no-hover-underline', 'em-font-size-14', 'em-pointer');
            link_to_add.setAttribute('onclick', 'createTab()');
            link_to_add.id = 'add_link_manage'
            link_to_add.innerHTML = '<span class="material-symbols-outlined em-font-size-14 em-mr-4">add</span><?php echo Text::_('MOD_EM_APPLICATION_TABS_ADD_TAB') ?>';
            document.getElementById('swal_manage').appendChild(link_to_add);

            let el = document.getElementById('swal_manage').cloneNode(true);
            Sortable.create(el.childNodes[1]);

            Swal.fire({
                title: "<?= Text::_('MOD_EM_APPLICATION_TABS_MANAGE_TABS_SWAL'); ?>",
                html: el,
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_SAVE');?>",
                cancelButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_CANCEL_BUTTON');?>",
                customClass: {
                    container: 'mod_emundus_application_swal_manage_tabs_container',
                    popup: 'mod_emundus_application_swal_manage_tabs_popup',
                    header: 'mod_emundus_application_swal_manage_tabs_header',
                    htmlContainer: 'mod_emundus_application_swal_manage_tabs_content',
                    confirmButton: 'mod_emundus_application_swal_manage_tabs_confirm',
                    cancelButton: 'mod_emundus_application_swal_manage_tabs_cancel',
                    actions: 'mod_emundus_application_swal_manage_tabs_actions',
                }
            }).then((confirm) => {
                if (confirm.value) {
                    let new_tabs = document.querySelectorAll('#items li');
                    let tabs_to_post = []
                    new_tabs.forEach((tab, index) => {
                        const tab_to_post = {
                            name: tab.firstChild.lastChild.innerText,
                            ordering: (index + 1),
                            id: tab.firstChild.lastChild.id
                        }
                        tabs_to_post.push(tab_to_post);
                    });

                    let formData = new FormData();
                    formData.append('tabs', JSON.stringify(tabs_to_post));

                    fetch('/index.php?option=com_emundus&controller=application&task=updatetabs', {
                        body: formData,
                        method: 'post',
                    }).then((updating_response) => {
                        if (updating_response.ok) {
                            return updating_response.json();
                        }
                    }).then((updating_res) => {
                        if (updating_res.updated) {
                            window.location.reload();
                        } else {
                            Swal.fire({
                                title: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_AN_ERROR_OCCURED');?>",
                                text: "",
                                type: "error",
                                reverseButtons: true,
                                confirmButtonText: "<?php echo Text::_('JYES');?>",
                                timer: 3000
                            });
                        }
                    })
                }
            });
        })
    }

    function deleteTab(tab, name) {
        Swal.fire({
            title: "<?= Text::_('MOD_EM_APPLICATION_TABS_MANAGE_TABS_DELETE_SWAL'); ?>",
            text: "<?= Text::_('MOD_EM_APPLICATION_TABS_MANAGE_TABS_CONFIRM_DELETE_SWAL'); ?> " + name + " ?",
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: "<?php echo Text::_('MOD_EM_APPLICATION_TABS_MANAGE_TABS_DELETE_SWAL_BUTTON');?>",
            cancelButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_CANCEL_BUTTON');?>",
            customClass: {
                container: 'mod_emundus_application_swal_manage_tabs_container',
                popup: 'mod_emundus_application_swal_manage_tabs_popup',
                header: 'mod_emundus_application_swal_manage_tabs_header',
                htmlContainer: 'mod_emundus_application_swal_manage_tabs_content',
                confirmButton: 'mod_emundus_application_swal_manage_tabs_confirm',
                cancelButton: 'mod_emundus_application_swal_manage_tabs_cancel',
                actions: 'mod_emundus_application_swal_manage_tabs_actions',
            }
        }).then((confirm) => {
            if (confirm.value) {
                fetch('/index.php?option=com_emundus&controller=application&task=deletetab&tab=' + tab, {
                    method: 'get'
                }).then((response) => {
                    if (response.ok) {
                        return response.json();
                    }
                }).then((res) => {
                    if (res.deleted == true) {
                        document.querySelector('#tab_link_' + tab).remove();
                        let selected_tab_session = sessionStorage.getItem('mod_emundus_applications___selected_tab');
                        if (selected_tab_session !== null && selected_tab_session == tab) {
                            sessionStorage.removeItem('mod_emundus_applications___selected_tab');
                            this.updateTab(0);
                        }

                        let tabs = document.querySelectorAll('div[id^="tab_link_"]');
                        if (tabs.length <= 1) {
                            document.getElementById('tab_manage_links').style.display = 'none';
                            document.getElementById('tab_adding_link').style.display = 'flex';
                        }
                    }
                });
            }
        });
    }

    async function moveToTab(fnum, tab, view) {
        let tabs = {};

        fetch('/index.php?option=com_emundus&controller=application&task=gettabs', {
            method: 'get',
        }).then((response) => {
            if (response.ok) {
                return response.json();
            }
        }).then(async (res) => {
            document.querySelector('#actions_block_' + fnum + '_' + view + '_' + tab).style.display = 'none';
            if (res.tabs.length === 0) {
                await this.createTab();
            } else {
                tabs[0] = "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_PLEASE_SELECT'); ?>"
                res.tabs.forEach((tab) => {
                    tabs[tab.id] = tab.name;
                });

                const {value: tab} = await Swal.fire({
                    title: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_MOVE_TO_TAB_SWAL'); ?>",
                    text: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_SELECT'); ?>",
                    input: 'select',
                    inputOptions: tabs,
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_MOVE');?>",
                    cancelButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_CANCEL_BUTTON');?>",
                    customClass: {
                        container: 'mod_emundus_application_swal_manage_tabs_container',
                        popup: 'mod_emundus_application_swal_manage_tabs_popup',
                        header: 'mod_emundus_application_swal_manage_tabs_header',
                        htmlContainer: 'mod_emundus_application_swal_manage_tabs_content',
                        confirmButton: 'mod_emundus_application_swal_manage_tabs_confirm',
                        cancelButton: 'mod_emundus_application_swal_manage_tabs_cancel',
                        actions: 'mod_emundus_application_swal_manage_tabs_actions',
                    },
                    inputValidator: (value) => {
                        if (value == 0) {
                            return "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_PLEASE_SELECT_A_TAB');?>";
                        }
                    }
                });

                if (tab) {
                    let formData = new FormData();
                    formData.append('fnum', fnum);
                    formData.append('tab', tab);

                    fetch('/index.php?option=com_emundus&controller=application&task=movetotab', {
                        body: formData,
                        method: 'post',
                    }).then((response) => {
                        if (response.ok) {
                            return response.json();
                        }
                    }).then((res) => {
                        if (res.status == true) {
                            window.location.reload();
                        } else {
                            Swal.fire({
                                title: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_AN_ERROR_OCCURED');?>",
                                text: res.msg,
                                type: "error",
                                reverseButtons: true,
                                confirmButtonText: "<?php echo Text::_('JYES');?>",
                                timer: 3000
                            });
                        }
                    });
                }
            }
        });
    }

    /** END **/

    async function copyApplication(fnum) {
        fetch('/index.php?option=com_emundus&controller=application&task=getcampaignsavailableforcopy&' + new URLSearchParams({
            fnum: fnum,
        }), {
            method: 'get',
        }).then((response) => {
            if (response.ok) {
                return response.json();
            }
        }).then(async (res) => {
            document.querySelector('.mod_emundus_applications__actions').style.display = 'none';

            const {value: campaign} = await Swal.fire({
                title: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_COPY_FILE'); ?>",
                text: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_COPY_FILE_CAMPAIGN'); ?>",
                input: 'select',
                inputOptions: res.campaigns,
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COPY_FILE_ACTION');?>",
                cancelButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_CANCEL_BUTTON');?>",
                customClass: {
                    container: 'mod_emundus_application_swal_manage_tabs_container',
                    popup: 'mod_emundus_application_swal_manage_tabs_popup',
                    header: 'mod_emundus_application_swal_manage_tabs_header',
                    htmlContainer: 'mod_emundus_application_swal_manage_tabs_content',
                    confirmButton: 'mod_emundus_application_swal_manage_tabs_confirm',
                    cancelButton: 'mod_emundus_application_swal_manage_tabs_cancel',
                    actions: 'mod_emundus_application_swal_manage_tabs_actions',
                },
                inputValidator: (value) => {
                    if (!value) {
                        return "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_PLEASE_SELECT_A_CAMPAIGN');?>";
                    }
                }
            });

            if (campaign) {
                let formData = new FormData();
                formData.append('fnum', fnum);
                formData.append('campaign', campaign);

                fetch('/index.php?option=com_emundus&controller=application&task=copyfile', {
                    body: formData,
                    method: 'post',
                }).then((response) => {
                    if (response.ok) {
                        return response.json();
                    }
                }).then((res) => {
                    if (res.status == true) {
                        window.location.href = res.first_page;
                    } else {
                        Swal.fire({
                            title: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_AN_ERROR_OCCURED');?>",
                            text: res.msg,
                            type: "error",
                            reverseButtons: true,
                            confirmButtonText: "<?php echo Text::_('JYES');?>",
                            timer: 3000
                        });
                    }
                });
            }
        });
    }

    async function renameApplication(fnum, name, campaign_label) {
        if (name === '') {
            name = campaign_label;
        }
        await Swal.fire({
            title: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_RENAME_APPLICATION'); ?>",
            text: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_RENAME_APPLICATION_NAME'); ?>",
            input: 'text',
            inputValue: name,
            inputAttributes: {
                maxlength: 80,
            },
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_RENAME_FILE_ACTION');?>",
            cancelButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_TAB_CANCEL_BUTTON');?>",
            customClass: {
                container: 'mod_emundus_application_swal_manage_tabs_container',
                popup: 'mod_emundus_application_swal_manage_tabs_popup',
                header: 'mod_emundus_application_swal_manage_tabs_header',
                htmlContainer: 'mod_emundus_application_swal_manage_tabs_content',
                confirmButton: 'mod_emundus_application_swal_manage_tabs_confirm',
                cancelButton: 'mod_emundus_application_swal_manage_tabs_cancel',
                actions: 'mod_emundus_application_swal_manage_tabs_actions',
            }
        }).then((result) => {
            if (result.value) {
                let formData = new FormData();
                formData.append('fnum', fnum);
                formData.append('new_name', result.value);

                fetch('/index.php?option=com_emundus&controller=application&task=renamefile', {
                    body: formData,
                    method: 'post',
                }).then((response) => {
                    if (response.ok) {
                        return response.json();
                    }
                }).then((res) => {
                    if (res.status == true) {
                        window.location.reload();
                    } else {
                        Swal.fire({
                            title: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_AN_ERROR_OCCURED');?>",
                            text: res.msg,
                            type: "error",
                            reverseButtons: true,
                            confirmButtonText: "<?php echo Text::_('JYES');?>",
                            timer: 3000
                        });
                    }
                });
            }
        });
    }

    async function shareApplication(fnum, ccid) {
        document.querySelector('.em-page-loader').style.display = 'block';

        fetch('index.php?option=com_emundus&view=application&layout=collaborate&format=raw&fnum=' + fnum + '&ccid=' + ccid, {
            method: 'get',
        }).then((response) => {
            if (response.ok) {
                return response.text();
            }
        }).then((res) => {
            document.querySelector('.em-page-loader').style.display = 'none';

            let actions = document.querySelectorAll("div[id^='actions_block_']");

            if (typeof actions !== 'undefined') {
                actions.forEach((action) => {
                    if (action.style.display === 'flex') {
                        action.style.display = 'none';
                    }
                });
            }

            Swal.fire({
                title: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_TITLE'); ?>",
                html: res,
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_SEND');?>",
                cancelButtonText: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_BACK');?>",
                customClass: {
                    title: 'em-swal-title',
                    cancelButton: 'em-swal-cancel-button',
                    confirmButton: 'em-swal-confirm-button',
                    popup: '!w-3/6',
                    validationMessage: 'em-swal-validation-message',
                },
                didOpen: (toast) => {
                    var tag = document.createElement("script");
                    tag.src = "media/com_emundus/js/collaborate.js";
                    document.getElementsByTagName("head")[0].appendChild(tag);

                    jQuery("#collab_emails").selectize({
                        plugins: ["remove_button"],
                        delimiter: ",",
                        persist: false,
                        createOnBlur: true,
                        create: true,
                        preload: true,
                        maxItems: null,
                        placeholder: '<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ADD_EMAILPLACEHOLDER'); ?>',
                        render: {
                            create: function (input) {
                                return {
                                    value: input,
                                    text: input,
                                };
                            },
                            item: function (data, escape) {
                                const val = data.value;
                                return '<div>' +
                                    '<span class="title">' +
                                    '<span class="name">' + escape(val.substring(val.indexOf(":") + 1)) + '</span>' +
                                    '</span>' +
                                    '</div>';
                            },
                            option_create: function (data, escape) {
                                const addString = '<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ADD_EMAIL'); ?>';
                                return '<div class="create">' + addString + ' <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                            }
                        },
                        onItemAdd: function (value, $item) {
                            if (document.querySelector('#collab_error')) {
                                document.querySelector('#collab_error').remove();
                            }

                            var email = value.substring(value.indexOf(":") + 1);
                            email = email.trim();

                            const regex = /^\S{1,64}@\S{1,255}\.\S{1,255}$/;
                            if (!regex.test(email) || '<?php echo $user->email?>' === email) {
                                this.removeItem(value);
                                let p = document.createElement('p');
                                p.classList.add('tw-text-red-500');
                                p.id = 'collab_error';
                                if ('<?php echo $user->email?>' === email) {
                                    p.innerText = '<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_NOT_YOUR_OWN'); ?>';
                                }
                                if (!regex.test(email)) {
                                    p.innerText = '<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_INVALID_EMAIL'); ?>';
                                }
                                document.querySelector('#collab_emails_block').append(p);
                            }
                        }
                    });
                },
                preConfirm: () => {
                    if (document.querySelector("#collab_emails").value === '') {
                        Swal.showValidationMessage('<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_FILL_EMAILS'); ?>')
                    }
                }
            }).then((result) => {
                if (result.value) {
                    let formData = new FormData();

                    formData.append('fnum', fnum);
                    formData.append('ccid', ccid);
                    formData.append('emails', document.querySelector('#collab_emails').value);

                    Swal.fire({
                        title: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_SUCCESS'); ?>",
                        text: res.msg,
                        iconHtml: '<img class="em-sending-email-img tw-w-1/3 tw-max-w-none" src="/media/com_emundus/images/tchoozy/complex-illustrations/sending-message.svg"/>',
                        showCancelButton: false,
                        showConfirmButton: false,
                        customClass: {
                            title: 'em-swal-title !tw-text-center',
                            cancelButton: 'em-swal-cancel-button',
                            confirmButton: 'em-swal-confirm-button',
                            icon: 'em-swal-icon',
                        },
                        timer: 3000
                    });

                    fetch('index.php?option=com_emundus&controller=application&task=sharefilewith', {
                        body: formData,
                        method: 'post',
                    }).then((response) => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            return response.text().then((text) => {
                                throw new Error(text);
                            });
                        }
                    }).then((res) => {
                        if (res.status != true) {
                            throw new Error(res.msg);
                        } else {
                            if (res.data.failed_emails.length > 0) {
                                let failed_emails = res.data.failed_emails.join(', ');
                                throw new Error("<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_EMAILS'); ?> " + failed_emails);
                            } else {
                                Swal.fire({
                                    title: "<?= Text::_('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_FINISH_SUCCESS'); ?>",
                                    text: res.msg,
                                    iconHtml: '<img class="em-sending-email-img tw-w-1/3 tw-max-w-none" src="/media/com_emundus/images/tchoozy/complex-illustrations/message-sent.svg"/>',
                                    showCancelButton: false,
                                    showConfirmButton: false,
                                    customClass: {
                                        title: 'em-swal-title !tw-text-center',
                                        cancelButton: 'em-swal-cancel-button',
                                        confirmButton: 'em-swal-confirm-button',
                                        icon: 'em-swal-icon',
                                    },
                                    timer: 3000
                                });
                            }
                        }
                    }).catch((error) => {
                        Swal.fire({
                            title: "Une erreur est survenue",
                            text: error,
                            type: "error",
                            reverseButtons: true,
                            confirmButtonText: "<?php echo Text::_('JYES');?>"
                        });
                    });
                }
            });
        });
    }

    function displaySort() {
        let sort = document.getElementById('sort_block');
        if (sort.style.display === 'none') {
            sort.style.display = 'flex';
        } else {
            sort.style.display = 'none';
        }
    }

    function filterApplications(type, value) {
        let formData = new FormData();
        formData.append('type', type);
        formData.append('value', value);

        fetch('/index.php?option=com_emundus&controller=application&task=filterapplications', {
            body: formData,
            method: 'post',
        }).then((response) => {
            if (response.ok) {
                return response.json();
            }
        }).then((res) => {
            if (res.status == true) {
                window.location.reload();
            } else {
                Swal.fire({
                    title: "<?php echo Text::_('MOD_EMUNDUS_APPLICATIONS_AN_ERROR_OCCURED');?>",
                    text: res.msg,
                    type: "error",
                    reverseButtons: true,
                    confirmButtonText: "<?php echo Text::_('JYES');?>",
                    timer: 3000
                });
            }
        });
    }
</script>

<script>
    const customActions = document.querySelectorAll('.em-custom-action-launch-action');

    if (customActions.length > 0) {
        customActions.forEach((customAction) => {
            const action = customAction.id.replace('actions_button_custom_', '');

            customAction.addEventListener('click', function () {
                Swal.fire({
                    title: customAction.innerText,
                    text: customAction.dataset.text,
                    type: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#dc3545',
                    reverseButtons: true,
                    confirmButtonText: "<?php echo Text::_('JYES');?>",
                    cancelButtonText: "<?php echo Text::_('JNO');?>"
                }).then((confirm) => {
                    if (confirm.value) {
                        const actions = customAction.closest('.mod_emundus_applications__actions');
                        const module_id = actions.dataset.mid;
                        const fnum = customAction.dataset.fnum;

                        fetch('/index.php?option=com_emundus&controller=application&task=applicantcustomaction&action=' + action + '&fnum=' + fnum + '&module_id=' + module_id)
                            .then((response) => {
                                if (response.ok) {
                                    return response.json();
                                } else {
                                    throw new Error(response.statusText);
                                }
                            })
                            .then((json) => {
                                if (json.status) {
                                    window.location.reload();
                                } else {
                                    console.error(json.msg);
                                }
                            });
                    }
                })
            });
        });
    }

</script>