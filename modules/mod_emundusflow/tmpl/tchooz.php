<?php // no direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;
use Joomla\Plugin\System\EmundusPublicAccess\Extension\EmundusPublicAccess;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\UI\ButtonVariantEnum;
use Tchooz\Enums\UI\ButtonWidthEnum;

defined('_JEXEC') or die('Restricted access');

if (empty($applicationFile))
{
    return;
}

$config      = Factory::getApplication()->getConfig();
$site_offset = $config->get('offset');

$dateTime = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
$dateTime = $dateTime->setTimezone(new DateTimeZone($site_offset));
$now      = $dateTime->format('Y-m-d H:i:s');

$document = Factory::getApplication()->getDocument();
$wa       = $document->getWebAssetManager();

if ($params->get('layout', '') != '_:tchooz')
{
    $wa->registerAndUseStyle("modules/mod_emundusflow/style/emundus.css");
}

assert($applicationFile instanceof ApplicationFileEntity);
?>

<style>
    .mod_emundus_flow___infos {
        flex-wrap: wrap;
        grid-gap: 12px;
        max-width: 75%;
    }

    .mod_emundus_flow___infos *:not(.material-symbols-outlined) {
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
			if (!empty($applicationFile->getCampaign()->getProgram()->getColor())) {
				$color = $applicationFile->getCampaign()->getProgram()->getColor();
				switch ($applicationFile->getCampaign()->getProgram()->getColor()) {
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
			<?php if ($show_back_button == 1) :
				if (EmundusPublicAccess::isPublicAccessSession())
				{
					$home_link = '/index.php?option=com_users&task=user.logout&' . Session::getFormToken() . '=1';
				}
			?>
                <?php
                    echo LayoutHelper::render('emundus.button', [
                        'variant' => ButtonVariantEnum::PRIMARY,
                        'width'   => ButtonWidthEnum::FIT,
                        'text'    => Text::_('MOD_EMUNDUS_FLOW_SAVE_AND_EXIT'),
                        'href'    => $home_link,
                    ]);
                ?>
			<?php endif; ?>

            <?php

                $data = [
                    'fnum' => $applicationFile->getFnum(),
                    'context' => 'single'
                ];
                echo LayoutHelper::render('emundus.application.actions', $data, '', $data);
            ?>
        </div>
    </div>
	<?php if ($show_deadline == 1 || $show_status == 1) : ?>
        <div class="tw-flex tw-flex-col tw-mt-2 mod_emundus_flow___infos">
            <?php if ($isShowToApplicant) : ?>
                <div class="tw-flex tw-items-center em-flex-wrap">
                    <p class="em-text-neutral-600 tw-mr-2"><?= Text::_('MOD_EMUNDUS_FLOW_REFERENCE'); ?> : </p>
                    <div class="tw-flex tw-items-end tw-gap-1"
                         title="<?= (!empty($reference) ? $reference: '') . '#' . (!empty($applicationFile->getShortReference()) ? $applicationFile->getShortReference() : ''); ?>"
                    >
                        <?php if (!empty($reference)) : ?>
                            <label class="tw-mb-0"><?= $reference; ?></label>
                        <?php endif; ?>
                        <?php if (!empty($applicationFile->getShortReference())) : ?>
                            <span class="<?= !empty($reference) ? 'tw-text-sm tw-text-neutral-500' : ''; ?>">#<?= $applicationFile->getShortReference(); ?></span>
                        <?php endif; ?>
                        <span id="copy_reference_<?php echo $applicationFile->getId(); ?>" class="material-symbols-outlined !tw-text-base tw-cursor-pointer" onclick="copyReference('<?= $reference . '#' . $applicationFile->getShortReference(); ?>')">content_copy</span>
                    </div>
                </div>
            <?php endif; ?>

			<?php if ($show_deadline == 1) : ?>
                <div class="tw-flex tw-items-center">
                    <p class="em-text-neutral-600 em-font-size-16"> <?php echo Text::_('MOD_EMUNDUS_FLOW_END_DATE'); ?></p>
                    <span class="tw-ml-1.5" style="white-space: nowrap"><?php echo EmundusHelperDate::displayDate($deadline,'DATE_FORMAT_EMUNDUS') ?></span>
                </div>
			<?php endif; ?>

			<?php if ($params->get('show_programme', 1) == 1) : ?>
                <div class="tw-flex tw-items-center em-flex-wrap">
                    <p class="em-text-neutral-600 tw-mr-2"><?= Text::_('MOD_EMUNDUS_FLOW_PROGRAMME'); ?> : </p>
                    <p class="em-programme-tag" style="color: <?php echo $color ?>;margin: unset;padding: 0">
						<?php echo $applicationFile->getCampaign()->getProgram()->getLabel(); ?>
                    </p>
                </div>
			<?php endif; ?>

			<?php if ($show_status == 1) : ?>
                <div class="tw-flex tw-items-center">
                    <p class="em-text-neutral-600 tw-mr-2"><?= Text::_('MOD_EMUNDUS_FLOW_STATUS'); ?> : </p>
                    <div class="mod_emundus_flow___status_<?= $applicationFile->getStatus()->getColor(); ?> tw-flex">
                        <span class="label label-<?=  $applicationFile->getStatus()->getColor() ?>"><?= $applicationFile->getStatus()->getLabel() ?></span>
                    </div>
                </div>
			<?php endif; ?>

	        <?php if($applicationFile->getUser()->id !== Factory::getApplication()->getIdentity()->id) : ?>
                <div class="tw-flex tw-items-center">
                    <p class="tw-text-neutral-600 tw-mr-2"><?= Text::_('MOD_EMUNDUS_FLOW_APPLICANT'); ?></p>
                    <p><?= $applicationFile->getUser()->name; ?></p>
                </div>
	        <?php endif; ?>
        </div>
	<?php endif; ?>


	<?php

	$file_tags_display = '';
	if (!empty($file_tags)) {
        if (!class_exists('EmundusModelEmails'))
        {
            require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
        }
        $m_emails = new EmundusModelEmails();
	    $emundusUser = Factory::getApplication()->getSession()->get('emundusUser');

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

        $tags              = $m_emails->setTags($user->id, $post, $emundusUser->fnum, '', $file_tags);
        $file_tags_display = preg_replace($tags['patterns'], $tags['replacements'], $file_tags);
        $file_tags_display = $m_emails->setTagsFabrik($file_tags_display, array($emundusUser->fnum));
    }

	?>

    <div class="em-mt-8">
		<?php if (!empty($file_tags_display)) :
			echo $file_tags_display;
		endif; ?>
    </div>

    <?php
        if ($applicationFile->isPublic())
        {
            require ModuleHelper::getLayoutPath($module->module, 'publicsession');
        }
    ?>

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

    function copyReference(reference) {
        // Copy to clipboard
        navigator.clipboard.writeText(reference);
        Swal.fire({
            title: '<?php echo Text::_('MOD_EMUNDUS_FLOW_REFERENCE_CLIPBOARD'); ?>',
            icon: 'success',
            showConfirmButton: false,
            customClass: {
                title: 'em-swal-title',
                actions: 'em-swal-single-action',
            },
            timer: 1500,
        });
    }
</script>
