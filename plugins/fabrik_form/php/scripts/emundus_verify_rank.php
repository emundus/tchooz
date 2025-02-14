<?php
use Joomla\CMS\Factory;

$app   = Factory::getApplication();
$rowid = $app->input->get('rowid');

$db    = Factory::getDbo();
$query = $db->getQuery(true);

$diff_rang  = false;
$diff_email = false;

$fnum   = $formModel->getElementData('jos_emundus_files_request_1614_repeat___fnum_expertise', false, '');
$nom    = $formModel->getElementData('jos_emundus_files_request___lastname', false, '');
$prenom = $formModel->getElementData('jos_emundus_files_request___firstname', false, '');
$rang   = $formModel->getElementData('jos_emundus_files_request_1614_repeat___rang', false, '');
$email  = $formModel->getElementData('jos_emundus_files_request___email', false, '');

if (empty($rowid))
{
	foreach ($rang as $key => $r)
	{
		$query->clear()
			->select('efr.id')
			->from($db->quoteName('#__emundus_files_request', 'efr'))
			->leftJoin($db->quoteName('#__emundus_files_request_1614_repeat', 'efrr') . ' ON efr.id = efrr.parent_id')
			->where($db->quoteName('efrr.fnum_expertise') . ' LIKE ' . $db->quote($fnum[$key]))
			->andWhere($db->quoteName('efr.email') . ' LIKE ' . $db->quote($email))
			->orWhere('efrr.fnum_expertise like ' . $db->quote($fnum[$key]) . ' AND efrr.rang LIKE ' . $db->quote($r));
		$db->setQuery($query);

		$existing = $db->loadResult();
		if (!empty($existing))
		{
			$messages[] = "L'expert " . $nom . " " . $prenom . " existe déjà dans la liste pour le dossier " . $fnum[$key] . " ou il y a déjà un expert associé à ce dossier sur le rang " . $r;
		}
	}
}
else
{
	$query->select('efrr.rang')
		->from($db->quoteName('#__emundus_files_request', 'efr'))
		->leftJoin($db->quoteName('#__emundus_files_request_1614_repeat', 'efrr') . ' ON efr.id = efrr.parent_id')
		->where($db->quoteName('efrr.parent_id') . ' LIKE ' . $db->quote($rowid));
	$db->setQuery($query);
	$rows_existing = $db->loadColumn();

	foreach ($rows_existing as $row_existing)
	{
		foreach ($rang as $key => $r)
		{
			if ($row_existing != $r)
			{
				$query->clear()
					->select('efr.id')
					->from($db->quoteName('#__emundus_files_request', 'efr'))
					->leftJoin($db->quoteName('#__emundus_files_request_1614_repeat', 'efrr') . ' ON efr.id = efrr.parent_id')
					->where($db->quoteName('efrr.fnum_expertise') . ' LIKE ' . $db->quote($fnum[$key]))
					->andWhere($db->quoteName('efr.email') . ' LIKE ' . $db->quote($email))
					->orWhere('efrr.fnum_expertise like ' . $db->quote($fnum[$key]) . ' AND efrr.rang LIKE ' . $db->quote($r))
					->andWhere('efrr.parent_id NOT LIKE ' . $rowid);

				$db->setQuery($query);
				$existing = $db->loadResult();
				if (!empty($existing))
				{
					$messages[] = "L'expert " . $nom . " " . $prenom . " existe déjà dans la liste pour le dossier " . $fnum[$key] . " ou il y a déjà un expert associé à ce dossier sur le rang " . $r;
				}
			}
		}
	}
}
if (!empty($messages))
{
	foreach ($messages as $message)
	{
		$app->enqueueMessage($message);
	}

	return false;
}