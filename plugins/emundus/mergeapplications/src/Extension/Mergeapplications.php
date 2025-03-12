<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\Mergeapplications\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Emundus\Mergeapplications\Repository\ApplicationRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
final class Mergeapplications extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	private DatabaseInterface $db;

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
		parent::__construct($dispatcher, $config);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterStatusChange' => 'mergeFiles',
		];
	}

	public function mergeFiles(GenericEvent $event): bool
	{
		$args = $event->getArguments();

		$fnum   = $args['fnum'];
		$status = $args['state'];

		$statusToMerge    = (int) $this->params->get('status');
		$statusAfterMerge = (int) $this->params->get('status_after_merge', null);
		$programsToMerge  = $this->params->get('programs_to_merge', []);

		if (!empty($programsToMerge) && !empty($fnum) && $status === $statusToMerge)
		{
			$mainProgram  = $this->params->get('main_program', '');
			$archiveFiles = (bool) $this->params->get('archive_files', true);

			$this->db = $this->getDatabase();

			$applicantId = $this->getApplicantId($fnum);
			if (!empty($applicantId))
			{
				$applicationRepository = new ApplicationRepository($applicantId, $mainProgram, $programsToMerge, $statusToMerge, $this->getDatabase());

				$alreadyMerged = $applicationRepository->checkIfApplicantHasFileOnMainProgram();
				if ($alreadyMerged)
				{
					return true;
				}

				$applicationRepository->setApplicationFiles();

				if (count($applicationRepository->getApplicationFiles()) === count($programsToMerge))
				{
					$allFilesAreInSameStatus = $applicationRepository->checkIfAllFilesAreInSameStatus();
					if ($allFilesAreInSameStatus)
					{
						$applicationRepository->runMerge($archiveFiles, $statusAfterMerge);
					}
				}
			}
		}

		return true;
	}

	private function getApplicantId($fnum): int
	{
		$query = $this->db->getQuery(true)
			->select('applicant_id')
			->from($this->db->quoteName('#__emundus_campaign_candidature'))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
		$this->db->setQuery($query);

		return (int) $this->db->loadResult();
	}
}