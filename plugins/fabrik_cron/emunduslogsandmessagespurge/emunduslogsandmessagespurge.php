<?php
/**
 * A cron task to purge database logs and messages older than a certain time
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @copyright   Copyright (C) 2024 emundus.fr - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-cron.php';

/**
 * A cron task to purge database logs and messages older than a certain time
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.emunduslogsandmessagespurge
 * @since       3.0
 */

class PlgFabrik_Cronemunduslogsandmessagespurge extends PlgFabrik_Cron{

    /**
	 * Check if the user can use the plugin
	 *
	 * @param   string  $location  To trigger plugin on
	 * @param   string  $event     To trigger plugin on
	 *
	 * @return  bool can use or not
     *
     * @since 6.9.3
	 */
	public function canUse($location = null, $event = null){
		return true;
	}


    /**
     * Do the plugin action
     *
     * @param   array  &$data data
     *
     * @return  int  Number of rows deleted (logs + messages)
     *
     * @since 6.9.3
     * @throws Exception
     */
	public function process(&$data, &$listModel)
	{
		include_once(JPATH_SITE . '/components/com_emundus/models/logs.php');
		include_once(JPATH_SITE . '/components/com_emundus/models/messages.php');
		include_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
		Log::addLogger(
			array(
				'text_file' => 'com_emundus.logsandmessagespurge.php',
			),
			Log::INFO, 'com_emundus_logsandmessagespurge');
		$m_logs     = new EmundusModelLogs();
		$m_messages = new EmundusModelMessages();

		$params      = $this->getParams();
		$amount_time = $params->get('amount_time');
		$unit_time   = $params->get('unit_time');
		$amount_time_tmp = $params->get('amount_time_tmp');
		$unit_time_tmp   = $params->get('unit_time_tmp');
		$export      = $params->get('export_zip');

		$now = $this->getDate($amount_time, $unit_time);

		if ($export)
		{
			$filename_logs     = $m_logs->exportLogsBeforeADate($now);
			$filename_messages = $m_messages->exportMessagesBeforeADate($now);

			if(!empty($filename_messages) || !empty($filename_logs))
			{
				$zip_filename = JPATH_SITE . '/tmp/backup_logs_and_messages_' . date('Y-m-d_H-i-s') . '.zip';
				$zip          = new ZipArchive();
				if ($zip->open($zip_filename, ZipArchive::CREATE) === true)
				{
					if (!empty($filename_logs) && file_exists($filename_logs))
					{
						$zip->addFile($filename_logs, basename($filename_logs));
					}

					if (!empty($filename_messages) && file_exists($filename_messages))
					{
						$zip->addFile($filename_messages, basename($filename_messages));
					}

					$zip->close();

					if (file_exists($filename_logs))
					{
						unlink($filename_logs);
					}
					if (file_exists($filename_messages))
					{
						unlink($filename_messages);
					}
				}
			}
		}

		$logs     = $m_logs->deleteLogsBeforeADate($now);
		$messages = $m_messages->deleteMessagesBeforeADate($now);
		$tmp_files = 0;

		$now = $this->getDate($amount_time_tmp, $unit_time_tmp);

		// Clean tmp documents older than $now
		//TODO: Log deleted files
		foreach (glob(JPATH_SITE . '/tmp/*') as $tmp_file)
		{
			if (!preg_match('/^backup_logs_and_messages_[a-zA-Z0-9_-]+\.zip$/', basename($tmp_file)) && basename($tmp_file) !== '.gitignore' && basename($tmp_file) !== 'index.html')
			{
				$creation_date_time = new DateTime('@' . filectime($tmp_file));
				if ($creation_date_time < $now)
				{
					if (is_file($tmp_file))
					{
						Log::add('tmp/ file ' . basename($tmp_file) . ' deleted.', Log::INFO, 'com_emundus_logsandmessagespurge');
						unlink($tmp_file);
						$tmp_files += 1;
					}
					else if (is_dir($tmp_file))
					{
						$files = glob($tmp_file . '/*');
						foreach ($files as $file)
						{
							Log::add('tmp/ file ' . basename($file) . ' deleted.', Log::INFO, 'com_emundus_logsandmessagespurge');
							unlink($file);
						}
						rmdir($tmp_file);
						$tmp_files += 1;
					}
				}
			}
		}

		return $logs + $messages + $tmp_files;
	}

	private function getDate($amount_time, $unit_time)
	{
		if (version_compare(JVERSION, '4.0', '>='))
		{
			$config = Factory::getApplication()->getConfig();
		}
		else
		{
			$config = Factory::getConfig();
		}
		$offset = $config->get('offset', 'Europe/Paris');
		$now    = DateTime::createFromFormat('Y-m-d H:i:s', EmundusHelperDate::getNow($offset));

		switch ($unit_time)
		{
			case 'hour':
				$now->modify('-' . $amount_time . ' hours');
				break;
			case 'day':
				$now->modify('-' . $amount_time . ' days');
				break;
			case 'week':
				$now->modify('-' . $amount_time . ' weeks');
				break;
			case 'month':
				$now->modify('-' . $amount_time . ' months');
				break;
			case 'year':
				$now->modify('-' . $amount_time . ' years');
				break;
		}

		return $now;
	}
}
