<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

$app       = Factory::getApplication();
$db        = Factory::getDbo();
$query     = $db->getQuery(true);
$listModel = $this->getModel();
$formModel = $listModel->getFormModel();
$data      = $formModel->formData;

$fnum          = $data['jos_emundus_files_request___fnum'] ?? '';
$email         = $data['jos_emundus_files_request___email'] ?? '';
$firstname     = $data['jos_emundus_files_request___firstname'] ?? '';
$lastname      = $data['jos_emundus_files_request___lastname'] ?? '';
$attachment_id = $data['jos_emundus_files_request___attachment_id'] ?? 0;
$rang          = $data['jos_emundus_files_request_1614_repeat___rang'] ?? 0;
$time_date     = 'NULL';

require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
$m_files = new EmundusModelFiles;

$fnumInfos   = $m_files->getFnumInfos($fnum);
$student_id  = $fnumInfos['applicant_id'];
$key         = md5(date('Y-m-d h:m:i') . '::' . $fnum . '::' . $student_id . '::' . $attachment_id . '::' . rand());
$campaign_id = $fnumInfos['campaign_id'];
$name        = $fnumInfos['name'];

$query->select('id')
	->from($db->quoteName('#__emundus_files_request'))
	->where('email LIKE ' . $db->quote($email));
$db->setQuery($query);
$existingIds = $db->loadColumn();

$query->clear()
	->select('efr.id')
	->from($db->quoteName('#__emundus_files_request', 'efr'))
	->leftJoin($db->quoteName('#__emundus_files_request_1614_repeat', 'efrr') . ' ON efr.id = efrr.parent_id')
	->where($db->quoteName('efrr.fnum_expertise') . ' LIKE ' . $db->quote($fnum))
	->andWhere($db->quoteName('efr.email') . ' LIKE ' . $db->quote($email))
	->orWhere('efrr.fnum_expertise like ' . $db->quote($fnum) . ' AND efrr.rang LIKE ' . $db->quote($rang));
$db->setQuery($query);
$existing = $db->loadResult();

$query->clear()
	->select('fnum')
	->from($db->quoteName('#__emundus_campaign_candidature'))
	->where('fnum LIKE ' . $db->quote($fnum));
$db->setQuery($query);
$existingFiles = $db->loadResult();

if (empty($existing) && !empty($existingFiles))
{
	if (!empty($existingIds))
	{
		// Si un email existe déjà, récupérez son ID
		$parentId = (int) $existingIds[0];
	}
	else
	{
		// Sinon, insérez une nouvelle entrée dans `#__emundus_files_request`
		$columns = ['fnum', 'email', 'firstname', 'lastname', 'attachment_id', 'time_date', 'keyid', 'student_id', 'campaign_id'];
		$values  = [
			$db->quote($fnum),
			$db->quote($email),
			$db->quote($firstname),
			$db->quote($lastname),
			$attachment_id,
			$db->quote($time_date),
			$db->quote($key),
			$student_id,
			$campaign_id
		];

		try
		{
			$query->clear()
				->insert($db->quoteName('#__emundus_files_request'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Failed to insert request for fnum ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		// Récupérez l'ID inséré
		$parentId = $db->insertid();
	}

	try
	{
		$columns = ['parent_id', 'rang', 'fnum_expertise', 'etat', 'nom_candidat_expertise'];
		$values  = [$parentId, $rang, $db->quote($fnum), '0', $db->quote($name)];
		$query->clear()
			->insert($db->quoteName('#__emundus_files_request_1614_repeat'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));
		$db->setQuery($query);
		$db->execute();
	}
	catch (Exception $e)
	{
		Log::add('Failed to insert repeat request for fnum ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
	}
}
else
{
	$already_exists[] = "L'expert " . $lastname . " " . $firstname . " existe déjà dans la liste pour le dossier " . $fnum . " ou il y a déjà un expert associé à ce dossier sur le rang " . $rang . " ou le numéro de dossier " . $fnum . " n'existe pas";
	$conditions       = array(
		$db->quoteName('parent_id') . ' = 0'
	);

	$query->clear()
		->delete($db->quoteName('#__emundus_files_request_1614_repeat'))
		->where($conditions);
	$db->setQuery($query);
	$result = $db->execute();
}

if (!empty($already_exists))
{
	foreach ($already_exists as $already_exist)
	{
		$app->enqueueMessage($already_exist);
	}
}

return false;