<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app  = Factory::getApplication();
$type = $formModel->getElementData('jos_emundus_setup_letters___template_type_raw');
if(is_array($type)) {
	$type = $type[0];
}

$file = $formModel->getElementData('jos_emundus_setup_letters___file_raw');
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

switch ((int)$type) {
	case 3: // Word
		if (!in_array($ext, ['doc','docx','odt'])) {
			$formModel->updateFormData('jos_emundus_setup_letters___file', '', true);
			$formModel->updateFormData('jos_emundus_setup_letters___attachment_id', -1, true);
			$app->enqueueMessage(Text::_('PLG_FORM_PHP_SETUP_LETTERS_WRONG_WORD_FILE'), 'warning');
			return false;
		}
		break;
	case 4: // Excel	
		if (!in_array($ext, ['xls','xlsx','ods'])) {
			$formModel->updateFormData('jos_emundus_setup_letters___file', '', true);
			$formModel->updateFormData('jos_emundus_setup_letters___attachment_id', -1, true);
			$app->enqueueMessage(Text::_('PLG_FORM_PHP_SETUP_LETTERS_WRONG_EXCEL_FILE'), 'warning');
			return false;
		}
		break;
}

$attachment_id = $formModel->getElementData('jos_emundus_setup_letters___attachment_id_raw');
if(is_array($attachment_id)) {
	$attachment_id = $attachment_id[0];
}
if ($attachment_id == -1) {
	$db = Factory::getContainer()->get('DatabaseDriver');

	$letter_title = $formModel->getElementData('jos_emundus_setup_letters___title');
	$attachment_lbl = "_".str_replace(['"',"'"], "", $letter_title);
	$attachment_lbl = str_replace([" ","-"], "_", $attachment_lbl);

	// Create and populate an object.
	$attachment = new stdClass();
	$attachment->id= 0;
	$attachment->lbl= $attachment_lbl;
	$attachment->value = $letter_title;
	$attachment->allowed_types='pdf;doc;docx;xls;xlsx;jpg;odt';
	$attachment->nbmax=0;
	$attachment->category='GENERATED_LETTER';


	$db->insertObject('jos_emundus_setup_attachments', $attachment, 'id');
	$formModel->updateFormData('jos_emundus_setup_letters___attachment_id', $attachment->id, true);
}