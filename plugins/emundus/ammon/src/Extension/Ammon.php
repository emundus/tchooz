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
			//'onAfterSubmitFile' => 'process',
			'onAfterStatusChange' => 'process',
		];
	}

	public function process(GenericEvent $event): void
	{
		$name = $event->getName();
		$data = $event->getArguments();

		$force_new_user_if_not_found = $data['force_new_user_if_not_found'] ?? false;

		if ($name === 'onAfterStatusChange') {
			$fnum = $data['fnum'];

			if (!empty($fnum)) {
				$valid = $this->checkStatus($fnum) && $this->notAlreadyRegistered($fnum);

				if ($valid) {
					$session_id = $this->getSessionFromFnum($fnum);
					Log::add('Start registration for fnum ' . $fnum . ' and session ' . $session_id . ' in ammon api', Log::INFO, 'plugin.emundus.ammon');

					$message = '';
					try {
						$file_status = (int)$this->params->get('status', 0);
						$repository = new AmmonRepository($fnum, $session_id, $file_status);
						$registered = $repository->registerFileToSession($force_new_user_if_not_found);

						if ($registered) {
							$repository->saveAmmonRegistration($fnum);
							Log::add('Registration for fnum ' . $fnum . ' and session ' . $session_id . ' in ammon api was successful', Log::INFO, 'plugin.emundus.ammon');
						} else {
							Log::add('Registration for fnum ' . $fnum . ' and session ' . $session_id . ' in ammon api failed', Log::ERROR, 'plugin.emundus.ammon');
						}
					} catch (Exception $e) {
						$registered = false;
						$message = $e->getMessage();
						Log::add('Something went wrong when trying to register fnum ' . $fnum .  ' in ammon api ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
					}

					$dispatcher = Factory::getApplication()->getDispatcher();
					$onAfterAmmonRegistration = new GenericEvent('onAfterAmmonRegistration', ['fnum' => $fnum, 'session_id' => $session_id, 'status' => $registered ? 'success' : 'error', 'message' => $message]);
					$dispatcher->dispatch('onAfterAmmonRegistration', $onAfterAmmonRegistration);
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
			} catch (Exception $e) {
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

				if ($status == $this->params->get('status', 0)) {
					$valid = true;
				}
			} catch (Exception $e) {
				Log::add('Error when trying to get status from fnum ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $valid;
	}

	private function notAlreadyRegistered($fnum)
	{
		$notRegistered = true;

		if (!empty($fnum)) {
			$query = $this->db->getQuery(true);
			$query->select($this->db->quoteName('registered_in_ammon'))
				->from($this->db->quoteName('#__emundus_campaign_candidature'))
				->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$registered_in_ammon = $this->db->loadResult();

				if ($registered_in_ammon == 1) {
					$notRegistered = false;
				}
			} catch (Exception $e) {
				Log::add('Error when trying to get status from fnum ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $notRegistered;
	}
}