<?php
namespace Joomla\Plugin\Task\SendSMS\Repository;

defined('_JEXEC') or die;

use Tchooz\Synchronizers\SMS\OvhSMS;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;

require_once(JPATH_ROOT . '/components/com_emundus/models/sms.php');

class SendSMSRepository
{
	private int $limit;

	private int $maximum_attempts;

	private string $used_service;

	private bool $debug;

	private DatabaseDriver $db;

	private \EmundusModelSMS $model;

	private $synchronizer = null;

	public function __construct(int $limit = 500, int $maximum_attempts = 3, string $used_service = 'ovh', bool $debug = true)
	{
		Log::addLogger(['text_file' => 'plugin.task.sms.php'], Log::ALL, array('plugin.task.sms'));

		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->limit = $limit;
		$this->maximum_attempts = $maximum_attempts;
		$this->used_service = $used_service;
		$this->debug = $debug;
		$this->model = new \EmundusModelSMS();

		if($this->model->activated)
		{
			try
			{
				switch ($this->used_service)
				{
					case 'ovh':
					default:
						if (!class_exists('OvhSMS'))
						{
							require_once(JPATH_ROOT . '/components/com_emundus/classes/Synchronizers/SMS/OvhSMS.php');
						}

						$this->synchronizer = new OvhSMS();
						break;
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error initializing SMS synchronizer: ' . $e->getMessage(), Log::ERROR, 'plugin.task.sms');
				throw new \Exception('Error initializing SMS synchronizer: ' . $e->getMessage() . ' - Can not send SMS.');
			}
		}
		else
		{
			Log::add('SMS service is not activated.', Log::ERROR, 'plugin.task.sms');
			throw new \Exception('SMS service is not activated.');
		}
	}

	public function sendPendingSMS(): bool
	{
		return $this->model->sendPendingSMS($this->synchronizer, $this->maximum_attempts, $this->limit, $this->debug);
	}
}