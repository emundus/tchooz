<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

class modEmundusAttachmentsHelper
{

	public static function getAttachments($groups, $fnum)
	{
		$attachments = [];

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$user = Factory::getUser();

		if(!$user->guest)
		{
			try
			{
				$query->clear()
					->select('id,status,applicant_id')
					->from($db->quoteName('#__emundus_campaign_candidature'))
					->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum))
					->where($db->quoteName('applicant_id') . ' = ' . $db->quote($user->id));
				$db->setQuery($query);
				$candidature = $db->loadObject();

				if(!empty($candidature->id))
				{
					foreach ($groups as $group)
					{
						if (in_array($candidature->status, $group->mod_emundus_attachments_groups_status))
						{
							$query->clear()
								->select([
									'eu.id',
									'eu.filename',
									'eu.timedate as created',
									'esa.id as attachment_id',
									'esa.value as label'
								])
								->from($db->quoteName('#__emundus_uploads', 'eu'))
								->leftJoin($db->quoteName('#__emundus_setup_attachments', 'esa') . ' ON ' . $db->quoteName('esa.id') . ' = ' . $db->quoteName('eu.attachment_id'))
								->where($db->quoteName('eu.attachment_id') . ' = ' . $db->quote($group->mod_emundus_attachments_groups_attachment));
							$db->setQuery($query);
							$group_attachments = $db->loadObjectList();
							$attachments       = array_merge($attachments, $group_attachments);
						}
					}

					$attachments_to_remove = [];
					foreach ($attachments as $key => &$attachment)
					{
						if (!file_exists(JPATH_ROOT . '/images/emundus/files/' . $candidature->applicant_id . '/' . $attachment->filename))
						{
							$attachments_to_remove[] = $key;
						}
						else
						{
							$attachment->size = self::formatBytes(filesize(JPATH_ROOT . '/images/emundus/files/' . $candidature->applicant_id . '/' . $attachment->filename));
							$attachment->link = Uri::base() . 'images/emundus/files/' . $candidature->applicant_id . '/' . $attachment->filename;
						}
					}

					foreach ($attachments_to_remove as $key)
					{
						unset($attachments[$key]);
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error: ' . $e->getMessage(), Log::ERROR, 'mod_emundus_attachments');
			}
		}

		return $attachments;
	}

	public static function formatBytes($bytes, $precision = 2)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow   = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}
}
