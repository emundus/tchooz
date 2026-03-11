<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;

defined('JPATH_BASE') or die;

$d = $displayData;

$jinput = Factory::getApplication()->input;
$formid = $jinput->get->get('formid');
$fnum   = $jinput->get->get('fnum');

$attachId = $d->attributes['attachmentId'];
$size     = $d->attributes['size'];
$encrypt  = $d->attributes['encrypted'];

$attachmentTypeRepository = new AttachmentTypeRepository();
$attachment               = $attachmentTypeRepository->loadAttachmentTypeById($attachId);

$sample_filepath = null;
$allowed_types   = str_replace(';', ',', $attachment->getAllowedTypes());
$nb_max          = $attachment->getNbMax();

if (!class_exists('EmundusModelWorkflow'))
{
    require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
}
$m_workflow      = new EmundusModelWorkflow();
$currentWorkflow = $m_workflow->getCurrentWorkflowStepFromFile($fnum);
if (!empty($currentWorkflow))
{
    $db    = Factory::getContainer()->get('DatabaseDriver');
    $query = $db->getQuery(true);

    $query->select('sample_filepath')
        ->from($db->quoteName('#__emundus_setup_attachment_profiles'))
        ->where($db->quoteName('attachment_id') . ' = ' . (int) $attachId)
        ->where($db->quoteName('profile_id') . ' = ' . (int) $currentWorkflow->profile_id);
    $db->setQuery($query);
    $sample_filepath = $db->loadResult();
}
?>

<div id="div_<?php echo $d->attributes['id']; ?>" class="fabrik_element___emundus_file_upload_parent">

    <?php if ($sample_filepath)  : ?>
        <div class="tw-mb-2 tw-flex tw-items-center tw-gap-1 attachment_model">
            <span><?php echo Text::_('PLG_ELEMENT_EMUNDUS_FILEUPLOAD_MODEL'); ?></span>
            <a class="tw-flex tw-items-center" href="<?php echo Uri::base(true) . $sample_filepath ?>" target="_blank">
                <span class="em-text-underline">
                    <?php echo Text::_('PLG_ELEMENT_EMUNDUS_FILEUPLOAD_MODEL_LABEL'); ?>
                </span>
                <span class="material-symbols-outlined tw-ml-2 tw-text-neutral-900">cloud_download</span>
            </a>
        </div>
    <?php endif; ?>

    <span class="fabrik_element___file_upload_formats">
        <?= Text::_('PLG_ELEMENT_FILEUPLOAD_ALLOWED_TYPES') . ' : ' . $allowed_types ?>. <?= Text::_('PLG_ELEMENT_FIELD_MAXSIZE_TIP') . $d->attributes['max_size_txt']; ?>. <?= Text::sprintf('PLG_ELEMENT_FIELD_MAXNB_TIP', $nb_max); ?>.
    </span>

    <?php if ($d->attributes['description_input'] == 1) : ?>
        <div id="<?= $d->attributes['id'] . '_description_block'; ?>" class="tw-mt-2">
            <label for="<?= $d->attributes['id'] . '_description'; ?>"
                   id="<?= $d->attributes['id'] . '_description_label'; ?>"
                   id="label-description-document"><?php echo JText::_('DESCRIPTION_DOCUMENT') ?></label>
            <input class="input-document-description" type="text"
                   id="file_<?= $d->attributes['id'] . '_description'; ?>" />
        </div>
    <?php endif; ?>

    <div class="btn-upload em-pointer">
        <p class="em-flex-row"><?php echo Text::_('PLG_ELEMENT_FILEUPLOAD_DROP') ?>
            <u class="em-ml-4">
                <?php echo Text::_('PLG_ELEMENT_FILEUPLOAD_DROP_CLICK') ?>
            </u>
            <span class="material-symbols-outlined em-ml-12">cloud_upload</span>
        </p>
    </div>
    <input class="fabrikinput" type="file" id="file_<?= $d->attributes['id']; ?>"
           name="file_<?= $d->attributes['name']; ?>"
           <?php if ($d->attributes['description_input'] == 1) : ?>style="top: 80px"<?php endif; ?>
           multiple <?php foreach ($d->attributes as $key => $value)
    {
        echo $key . '="' . $value . '" ';
    } ?>/>
    <input class="fabrikinput" type="hidden" id="<?= $d->attributes['id']; ?>" name="<?= $d->attributes['name']; ?>"
           value="<?= $d->attributes['value']; ?>" />
</div>
