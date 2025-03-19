<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\Ammon\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Emundus\Ammon\Repository\AmmonRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
class Ammon extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	private DatabaseDriver $db;

	private int $registration_file_status;

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher
	 * @param   array                $config      An optional associative array of configuration settings
	 *
	 * @since   3.9.0
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		Log::addLogger(['text_file' => 'plugin.emundus.ammon.php'], Log::ALL, array('plugin.emundus.ammon'));

		parent::__construct($dispatcher, $config);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterStatusChange' => 'process',
		];
	}

	public function process(GenericEvent $event): void
	{
		$name = $event->getName();
		$data = $event->getArguments();
		$this->registration_file_status = $this->params->get('status', 0);
		$user = Factory::getApplication()->getIdentity();

		if ($name === 'onAfterStatusChange') {
			$fnum = $data['fnum'];

			if (!empty($fnum)) {
				$session_id = $this->getSessionFromFnum($fnum);
				$valid = $this->checkStatus($fnum) && $this->notAlreadyRegistered($fnum, $session_id);

				if ($valid) {
					$force_new_user_if_not_found = $data['force_new_user_if_not_found'] ?? false;
					if (!empty($session_id)) {
						Log::add('Start registration for fnum ' . $fnum . ' and session ' . $session_id . ' in ammon api', Log::INFO, 'plugin.emundus.ammon');

						try {
							$saved_in_queue = $this->saveInQueue($fnum, $session_id, $force_new_user_if_not_found, $user->id);

							if (!$saved_in_queue) {
								Log::add('Something went wrong when trying to save fnum ' . $fnum . ' in ammon queue', Log::ERROR, 'plugin.emundus.ammon');
							} else {
								Log::add('Fnum ' . $fnum . ' saved in ammon queue', Log::INFO, 'plugin.emundus.ammon');
							}
						} catch (\Exception $e) {
							Log::add('Something went wrong when trying to save fnum ' . $fnum . ' in ammon queue : ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
						}
					} else {
						Log::add('No session found for fnum ' . $fnum, Log::WARNING, 'plugin.emundus.ammon');
					}
				}
			} else {
				Log::add('No given fnum', Log::WARNING, 'plugin.emundus.ammon');
			}
		}
	}

	private function getSessionFromFnum($fnum): int
	{
		$session = 0;

		if (!empty($fnum)) {
			$query = $this->db->createQuery();
			$query->select($this->db->quoteName('esc.ammon_id'))
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'esc'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->where($this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$session = $this->db->loadResult();
			} catch (\Exception $e) {
				Log::add('Error when trying to get session from fnum ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $session;
	}

	private function checkStatus(string $fnum): bool
	{
		$valid = false;

		if (!empty($fnum)) {
			$query = $this->db->getQuery(true);
			$query->select($this->db->quoteName('status'))
				->from($this->db->quoteName('#__emundus_campaign_candidature'))
				->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$status = $this->db->loadResult();

				if ($status == $this->registration_file_status) {
					$valid = true;
				}
			} catch (Exception $e) {
				Log::add('Error when trying to get status from fnum ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $valid;
	}

	private function notAlreadyRegistered($fnum, $session_id): bool
	{
		$notRegistered = true;

		if (!empty($fnum)) {
			$query = $this->db->getQuery(true);
			$query->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__emundus_ammon_queue'))
				->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum))
				->andWhere($this->db->quoteName('session_id') . ' = ' . $this->db->quote($session_id));

			try {
				$this->db->setQuery($query);
				$queue_id = $this->db->loadResult();

				if (!empty($queue_id)) {
					$notRegistered = false;
				}
			} catch (\Exception $e) {
				Log::add('Error when trying to get status from fnum ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $notRegistered;
	}

	/**
	 * @param   string  $fnum
	 * @param   int     $session_id
	 * @param   bool    $force_new_user_if_not_found
	 *
	 * @return bool
	 */
	private function saveInQueue(string $fnum, int $session_id, bool $force_new_user_if_not_found, int $user_id): bool
	{
		$saved = false;

		if (!empty($fnum) && !empty($session_id)) {
			$query = $this->db->getQuery(true);
			$query->select('id')
				->from('#__emundus_ammon_queue')
				->where('fnum = ' . $this->db->quote($fnum))
				->where('session_id = ' . $this->db->quote($session_id));

			try {
				$this->db->setQuery($query);
				$id = $this->db->loadResult();

				$force_new = $force_new_user_if_not_found ? 1 : 0;

				if (empty($id)) {
					$query = $this->db->getQuery(true);
					$query->insert('#__emundus_ammon_queue')
						->columns('fnum, session_id, force_new_user_if_not_found, file_status, status, created_by, created_date')
						->values($this->db->quote($fnum) . ', ' . $this->db->quote($session_id) . ', ' . $this->db->quote($force_new) . ', ' . $this->db->quote($this->registration_file_status) . ', ' . $this->db->quote('pending') . ', ' . $this->db->quote($user_id) . ', ' . $this->db->quote(date('Y-m-d H:i:s')));

					$this->db->setQuery($query);
					$saved = $this->db->execute();
				} else {
					$query->update('#__emundus_ammon_queue')
						->set('force_new_user_if_not_found = ' . $this->db->quote($force_new))
						->set('updated_date = ' . $this->db->quote(date('Y-m-d H:i:s')))
						->where('id = ' . $this->db->quote($id));

					$this->db->setQuery($query);
					$saved = $this->db->execute();

					Log::add('Fnum ' . $fnum . ' already in queue', Log::INFO, 'plugin.emundus.ammon');
				}
			} catch (\Exception $e) {
				Log::add('Error when trying to save fnum ' . $fnum . ' in ammon queue ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			}

		}

		return $saved;
	}
}