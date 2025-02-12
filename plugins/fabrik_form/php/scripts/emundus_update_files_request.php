<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

$app = Factory::getApplication();

require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
$m_files = new EmundusModelFiles;

$email = $formModel->getElementData('jos_emundus_files_request___email', false);
$fnum  = $formModel->getElementData('jos_emundus_files_request___fnum', false);
$rang  = $formModel->getElementData('jos_emundus_files_request_1614_repeat_rang', false);

$db = Factory::getDbo();
$query = $db->getQuery(true);

$query->select('id')
	->from($db->quoteName('#__emundus_files_request'))
	->where('email LIKE ' . $db->quote($email));
$db->setQuery($query);
$existingIds = $db->loadColumn();

$lastIds = $formModel->getElementData('jos_emundus_files_request___id', false);
if (!empty($existingIds))
{
	if (count($existingIds) > 1)
	{
		try
		{
			$query->clear()
				->delete($db->quoteName('#__emundus_files_request'))
				->where('id = ' . $existingIds[array_key_last($existingIds)]);
			$db->setQuery($query);
			$db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Failed to delete request for id ' . $existingIds[array_key_last($existingIds)] . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

	}
	try
	{
		$query->clear()
			->update($db->quoteName('#__emundus_files_request_1614_repeat'))
			->set($db->quoteName('parent_id') . ' = ' . $db->quote($existingIds[0]))
			->where('parent_id = ' . $existingIds[array_key_last($existingIds)]);
		$db->setQuery($query);
		$db->execute();
	}
	catch (Exception $e)
	{
		Log::add('Failed to update repeat request for id ' . $existingIds[0] . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
	}

}
else
{
	try
	{
		$query->clear()
			->update($db->quoteName('#__emundus_files_request_1614_repeat'))
			->set($db->quoteName('parent_id') . ' = ' . $db->quote($lastIds))
			->where('parent_id = 0');
		$db->setQuery($query);
		$db->execute();
	}
	catch (Exception $e)
	{
		Log::add('Failed to update repeat request for id ' . $lastIds . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
	}
}