<?php // no direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

$config      = JFactory::getConfig();
$site_offset = $config->get('offset');

$dateTime = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
$dateTime = $dateTime->setTimezone(new DateTimeZone($site_offset));
$now      = $dateTime->format('Y-m-d H:i:s');
?>

<style>
    .btn.btn-primary.mod_emundus_flow___print:hover,
    .btn.btn-primary.mod_emundus_flow___print:active,
    .btn.btn-primary.mod_emundus_flow___print:focus {
        color: var(--neutral-0) !important;
        background: var(--em-primary-color) !important;
        border: 1px solid var(--em-primary-color) !important;
    }

    .btn.btn-primary.mod_emundus_flow___print:hover .material-symbols-outlined,
    .btn.btn-primary.mod_emundus_flow___print:active .material-symbols-outlined,
    .btn.btn-primary.mod_emundus_flow___print:focus .material-symbols-outlined {
        color: var(--neutral-0) !important;
    }

    .mod_emundus_flow___print {
        display: flex !important;
        align-items: center;
        gap: 4px;
    }

    .mod_emundus_flow___print p {
        font-family: var(--em-profile-font);
        line-height: 20px;
    }

    .btn-primary.mod_emundus_flow___print {
        background: white;
    }

    .mod_emundus_flow___infos {
        flex-wrap: wrap;
        grid-gap: 12px;
        max-width: 75%;
    }

    .mod_emundus_flow___infos * {
        font-family: var(--em-profile-font);
    }


    .mod_emundus_flow___intro .btn.btn-primary {
        font-size: var(--em-applicant-font-size) !important;
        letter-spacing: normal !important;
        line-height: normal !important;
    }

    .mod_emundus_flow___intro {
        display: grid;
        align-items: flex-start;
        gap: 32px;
        grid-template-columns: 67% 30%;
    }

    .em-programme-tag {
        overflow: visible;
        white-space: initial;
    }

    @media all and (max-width: 479px) {
        .mod_emundus_flow___intro {
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            row-gap: 8px !important;
            display: flex !important;
        }

        .mod_emundus_flow___infos div:first-child {
            margin-bottom: 6px;
        }

        .mod_emundus_flow___buttons {
            flex-direction: column;
            align-items: flex-start;
            row-gap: 8px;
        }

        .view-form #g-utility .g-container,
        .view-details #g-utility .g-container,
        .view-checklist #g-utility .g-container {
            width: 100%;
        }

        .mod_emundus_flow___container {
            padding: 0 20px !important;
        }
    }

    @media all and (min-width: 480px) and (max-width: 767px) {
        .mod_emundus_flow___intro {
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            row-gap: 8px !important;
            display: flex !important;
        }

        .mod_emundus_flow___infos div:first-child {
            margin-bottom: 6px;
        }

        .mod_emundus_flow___buttons {
            flex-direction: column;
            align-items: flex-start;
            row-gap: 8px;
        }

        .view-form #g-utility .g-container,
        .view-details #g-utility .g-container,
        .view-checklist #g-utility .g-container {
            width: 100%;
        }

        .mod_emundus_flow___container {
            padding: 0 40px !important;
        }
    }

    @media all and (min-width: 768px) and (max-width: 1198px) {
        .justify-end.mod_emundus_flow___buttons {
            flex-wrap: wrap;
            gap: 16px;
        }

        .mod_emundus_flow___buttons .btn-primary {
            margin-right: 0;
        }
    }

</style>

<div class="mod_emundus_flow___container tw-mt-4" style="padding: 0 20px">
    <div class="tw-flex tw-justify-between mod_emundus_flow___intro">
        <div class="tw-flex tw-flex-col tw-justify-center">
            <?php if (!empty($title_override_display)) : ?>
	            <?php echo $campaign_name; ?>
            <?php else : ?>
                <h1 class="em-mb-0-important"><?php echo $campaign_name; ?></h1>
            <?php endif; ?>

			<?php
			$color      = '#0A53CC';
			$background = '#C8E1FE';
			if (!empty($current_application->tag_color)) {
				$color = $current_application->tag_color;
				switch ($current_application->tag_color) {
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
        </div>
        <div class="tw-flex tw-items-center tw-justify-end tw-gap-2 mod_emundus_flow___buttons">
			<?php if ($show_back_button == 1) : ?>
                <a href="<?php echo $home_link ?>"
                   title="<?php echo strip_tags(JText::_('MOD_EMUNDUS_FLOW_SAVE_AND_EXIT')) ?>">
                    <button class="tw-btn-primary"><?php echo JText::_('MOD_EMUNDUS_FLOW_SAVE_AND_EXIT') ?></button>
                </a>
			<?php endif; ?>
            <a href="<?php echo JURI::base() ?>component/emundus/?task=pdf&amp;fnum=<?= $current_application->fnum ?>"
               target="blank" title="<?php echo JText::_('PRINT') ?>">
                <button class="tw-btn-secondary mod_emundus_flow___print">
                    <span class="material-symbols-outlined" style="font-size: 19px">print</span>
                    <p><?php echo JText::_('PRINT') ?></p>
                </button>
            </a>
        </div>
    </div>
	<?php if ($show_deadline == 1 || $show_status == 1) : ?>
        <div class="tw-flex tw-flex-col tw-mt-2 mod_emundus_flow___infos">
			<?php if ($show_deadline == 1) : ?>
                <div class="tw-flex tw-items-center">
                    <p class="em-text-neutral-600 em-font-size-16"> <?php echo JText::_('MOD_EMUNDUS_FLOW_END_DATE'); ?></p>
                    <span class="tw-ml-1.5" style="white-space: nowrap"><?php echo EmundusHelperDate::displayDate($deadline,'DATE_FORMAT_EMUNDUS') ?></span>
                </div>
			<?php endif; ?>

			<?php if ($show_programme == 1) : ?>
                <div class="tw-flex tw-items-center em-flex-wrap">
                    <p class="em-text-neutral-600 tw-mr-2"><?= JText::_('MOD_EMUNDUS_FLOW_PROGRAMME'); ?> : </p>
                    <p class="em-programme-tag" style="color: <?php echo $color ?>;margin: unset;padding: 0">
						<?php echo $current_application->prog_label; ?>
                    </p>
                </div>
			<?php endif; ?>

			<?php if ($show_status == 1) : ?>
                <div class="tw-flex tw-items-center">
                    <p class="em-text-neutral-600 tw-mr-2"><?= JText::_('MOD_EMUNDUS_FLOW_STATUS'); ?> : </p>
                    <div class="mod_emundus_flow___status_<?= $current_application->class; ?> tw-flex">
                        <span class="label label-<?= $current_application->class; ?>"><?= $current_application->value ?></span>
                    </div>
                </div>
			<?php endif; ?>

	        <?php if($fnumInfos['applicant_id'] !== Factory::getApplication()->getIdentity()->id) : ?>
                <div class="tw-flex tw-items-center">
                    <p class="tw-text-neutral-600 tw-mr-2"><?= JText::_('MOD_EMUNDUS_FLOW_APPLICANT'); ?></p>
                    <p><?= $fnumInfos['name']; ?></p>
                </div>
	        <?php endif; ?>
        </div>
	<?php endif; ?>


	<?php

	$file_tags_display = '';
	if (!empty($file_tags)) {
	    $m_email = new EmundusModelEmails();
	    $emundusUser = JFactory::getSession()->get('emundusUser');

	    $post = array(
		    'APPLICANT_ID'   => $user->id,
		    'DEADLINE'       => strftime("%A %d %B %Y %H:%M", strtotime($emundusUser->end_date)),
		    'CAMPAIGN_LABEL' => $emundusUser->label,
		    'CAMPAIGN_YEAR'  => $emundusUser->year,
		    'CAMPAIGN_START' => $emundusUser->start_date,
		    'CAMPAIGN_END'   => $emundusUser->end_date,
		    'CAMPAIGN_CODE'  => $emundusUser->training,
		    'FNUM'           => $emundusUser->fnum
	    );

	    $tags              = $m_email->setTags($user->id, $post, $emundusUser->fnum, '', $file_tags);
	    $file_tags_display = preg_replace($tags['patterns'], $tags['replacements'], $file_tags);
	    $file_tags_display = $m_email->setTagsFabrik($file_tags_display, array($emundusUser->fnum));
    }

	?>

    <div class="em-mt-8">
		<?php if (!empty($file_tags_display)) :
			echo $file_tags_display;
		endif; ?>
    </div>

</div>

<?php
if (!empty($campaign_languages) && !in_array($current_lang_id, $campaign_languages)) {

    $db = JFactory::getContainer()->get('DatabaseDriver');
    $query = $db->getQuery(true);

    $query->select('title_native')
        ->from('#__languages')
        ->where('lang_id IN (' . implode(',', $campaign_languages) . ')');

    $db->setQuery($query);
    $titles = $db->loadColumn();

    ?>
    <div class="tw-mt-8 alert alert-error tw-flex" style="margin-bottom: 32px; margin-inline: 16px;">
        <span class="material-symbols-outlined" style="color: #a60e15">warning</span>
        <div class="tw-w-full">
            <p class="em-text-neutral-600" style="color: #520105"><?= sprintf(Text::_('COM_EMUNDUS_ALLOWED_LANGUAGES_FOR_CAMPAIGN_ARE'), implode(',', $titles)); ?></p>
        </div>
    </div>

    <?php
}
?>

<script>
    function saveAndExit() {
        document.getElementsByClassName('fabrikForm')[0].submit();
    }
</script>
