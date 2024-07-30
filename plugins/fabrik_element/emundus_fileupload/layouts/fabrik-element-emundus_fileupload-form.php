<?php

    defined('JPATH_BASE') or die;

    // Add span with id so that element fxs work.
    $d = $displayData;

    $jinput = JFactory::getApplication()->input;
    $formid = $jinput->get->get('formid');


    $attachId = $d->attributes['attachmentId'];
    $size = $d->attributes['size'];
    $encrypt = $d->attributes['encrypted'];

    $allowed_types = '';
    $nb_max = 0;

    $db = JFactory::getDBO();
    $query = $db->getQuery(true);

    $query->select('allowed_types,nbmax')
        ->from($db->quoteName('#__emundus_setup_attachments'))
        ->where($db->quoteName('id') . ' = ' . $db->quote($attachId));

    try {
        $db->setQuery($query);
	    $attachment = $db->loadObject();

	    $allowed_types = str_replace(';',',', $attachment->allowed_types);
	    $nb_max = $attachment->nbmax;
    } catch (Exception $e) {
        JLog::add('Error in fileupload plugin at query : '.$query->__toString(), JLog::ERROR, 'com_emundus');
    }
?>

<div id="div_<?php echo $d->attributes['id']; ?>" class="fabrik_element___emundus_file_upload_parent">
    <span class="fabrik_element___file_upload_formats">
        <?= JText::_('PLG_ELEMENT_FILEUPLOAD_ALLOWED_TYPES')  . ' : ' . $allowed_types ?>. <?= JText::_('PLG_ELEMENT_FIELD_MAXSIZE_TIP') . $d->attributes['max_size_txt']; ?>. <?= JText::sprintf('PLG_ELEMENT_FIELD_MAXNB_TIP', $nb_max); ?>.
    </span>
	<?php if ($d->attributes['description_input'] == 1) : ?>
        <div id="<?= $d->attributes['id'] . '_description_block'; ?>" class="tw-mt-2">
            <label for="<?= $d->attributes['id'] . '_description'; ?>"
                   id="<?= $d->attributes['id'] . '_description_label'; ?>" id="label-description-document"><?php echo JText::_('DESCRIPTION_DOCUMENT') ?></label>
            <input class="input-document-description" type="text" id="file_<?= $d->attributes['id'] . '_description'; ?>"/>
        </div>
	<?php endif; ?>
    <div class="btn-upload em-pointer">
        <p class="em-flex-row"><?php echo JText::_('PLG_ELEMENT_FILEUPLOAD_DROP') ?>
			<u class="em-ml-4"><?php echo JText::_('PLG_ELEMENT_FILEUPLOAD_DROP_CLICK') ?></u>
			<span class="material-icons-outlined em-ml-12">cloud_upload</span>
        </p>
    </div>
    <input class="fabrikinput" type="file" id="file_<?= $d->attributes['id']; ?>" name="file_<?= $d->attributes['name']; ?>"
	    <?php if ($d->attributes['description_input'] == 1) : ?>style="top: 80px"<?php endif; ?>
    	multiple <?php foreach ($d->attributes as $key => $value) {
    	echo $key . '="' . $value . '" ';
    } ?>/>
    <input class="fabrikinput" type="hidden" id="<?= $d->attributes['id']; ?>" name="<?= $d->attributes['name']; ?>"
           value="<?= $d->attributes['value']; ?>" />
</div>
