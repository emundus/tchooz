<?php

namespace Joomla\Plugin\Extension\Emundus\Extension;

use Joomla\CMS\Event\Model\AfterSaveEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;

class Emundus extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return array
	 *
	 * @since   5.2.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onExtensionAfterSave' => 'onExtensionAfterSave'
		];
	}

	public function onExtensionAfterSave(AfterSaveEvent $event): void
	{
		$context = $event->getContext();
		$table   = $event->getItem();

		// Check that we're modifying the correct component.
		if ($context !== 'com_config.component' || $table->element !== 'com_emundus')
		{
			return;
		}

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		// New component params.
		$params = new Registry($table->params);

		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
		$payment_activated = $params->get('application_fee');
		if ($payment_activated)
		{
			$removed = \EmundusHelperUpdate::removeFromFile(JPATH_ROOT . '/.htaccess', ['php_value session.cookie_samesite Strict' . PHP_EOL]);
			if ($removed)
			{
				Factory::getApplication()->enqueueMessage(Text::_('PLG_EXTENSION_EMUNDUS_SAMESITE_REMOVED'));
			}
		}
		else
		{
			$inserted = \EmundusHelperUpdate::insertIntoFile(JPATH_ROOT . '/.htaccess', "php_value session.cookie_samesite Strict" . PHP_EOL);
			if ($inserted)
			{
				Factory::getApplication()->enqueueMessage(Text::_('PLG_EXTENSION_EMUNDUS_SAMESITE_INSERTED'));
			}
		}

		// Sync exception
		$id_applicant = $params->get('id_applicants', '');

		$ids = explode(',', $id_applicant);

		$query->select('user')
			->from($db->quoteName('#__emundus_setup_exceptions'));
		$db->setQuery($query);
		$ids_db = $db->loadColumn();

		$ids_to_add = array_diff($ids, $ids_db);

		foreach ($ids_to_add as $id)
		{
			if (!empty($id))
			{
				$columns = [
					'date_time',
					'user'
				];
				$values  = [
					$db->quote(date('Y-m-d H:i:s')),
					$id
				];

				$query->clear()
					->insert($db->quoteName('#__emundus_setup_exceptions'))
					->columns($db->quoteName($columns))
					->values(implode(',', $values));
				$db->setQuery($query);
				$db->execute();
			}
		}

		$ids_to_delete = array_diff($ids_db, $ids);
		foreach ($ids_to_delete as $id)
		{
			$query->clear()
				->delete($db->quoteName('#__emundus_setup_exceptions'))
				->where($db->quoteName('user') . ' = ' . $id);
			$db->setQuery($query);
			$db->execute();
		}

		if (!empty($ids_to_add) || !empty($ids_to_delete))
		{
			Factory::getApplication()->enqueueMessage(Text::_('PLG_EXTENSION_EMUNDUS_EXCEPTIONS_SYNCED'));
		}

		// AddPipe
		$add_pipe = $params->get('addpipe_activation', 0);
		if ($add_pipe == 1)
		{
			$query->clear()
				->select('id')
				->from($db->quoteName('#__emundus_setup_attachments'))
				->where($db->quoteName('lbl') . ' LIKE ' . $db->quote('_video'));
			$db->setQuery($query);
			$attachment_id = $db->loadResult();

			if (empty($attachment_id))
			{
				$insert   = [
					'lbl'              => '_video',
					'value'            => 'Video',
					'allowed_types'    => 'video',
					'nbmax'            => 1,
					'ordering'         => 0,
					'published'        => 1,
					'video_max_length' => 60
				];
				$insert   = (object) $insert;
				$inserted = $db->insertObject('#__emundus_setup_attachments', $insert);
				if ($inserted)
				{
					Factory::getApplication()->enqueueMessage(Text::_('PLG_EXTENSION_EMUNDUS_ADDPIPE_CREATED'));
				}
			}

			// Convert all mp4 attachments to video
			$query->clear()
				->select('id,allowed_types')
				->from($db->quoteName('#__emundus_setup_attachments'))
				->where($db->quoteName('allowed_types') . ' LIKE ' . $db->quote('%mp4%'));
			$db->setQuery($query);
			$attachments = $db->loadObjectList();

			foreach ($attachments as $attachment)
			{
				$allowed_types = explode(';', $attachment->allowed_types);
				$allowed_types = array_map(
					function ($type) {
						if ($type == 'mp4')
						{
							return 'video';
						}
						return $type;
					},
					$allowed_types
				);
				$allowed_types = implode(';', $allowed_types);
				$attachment->allowed_types = $allowed_types;
				$db->updateObject('#__emundus_setup_attachments', $attachment, 'id');
			}

			if(!empty($attachments))
			{
				Factory::getApplication()->enqueueMessage(Text::_('PLG_EXTENSION_EMUNDUS_ADDPIPE_CONVERTED_TO_VIDEO'));
			}
		}
		else {
			// Convert all video attachments to mp4
			$query->clear()
				->select('id,allowed_types')
				->from($db->quoteName('#__emundus_setup_attachments'))
				->where($db->quoteName('allowed_types') . ' LIKE ' . $db->quote('%video%'));
			$db->setQuery($query);
			$attachments = $db->loadObjectList();

			foreach ($attachments as $attachment)
			{
				$allowed_types = explode(';', $attachment->allowed_types);
				$allowed_types = array_map(
					function ($type) {
						if ($type == 'video')
						{
							return 'mp4';
						}
						return $type;
					},
					$allowed_types
				);
				$allowed_types = implode(';', $allowed_types);
				$attachment->allowed_types = $allowed_types;
				$db->updateObject('#__emundus_setup_attachments', $attachment, 'id');
			}

			if(!empty($attachments))
			{
				Factory::getApplication()->enqueueMessage(Text::_('PLG_EXTENSION_EMUNDUS_ADDPIPE_CONVERTED_TO_MP4'));
			}
		}
	}
}