<?php
/**
 * @package     Joomla\Plugin\Emundus\Mergeapplications\Repository
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\Mergeapplications\Repository;

use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

class ApplicationRepository
{
	private array $applicationFiles = [];

	public function __construct(
		private readonly int               $applicantId,
		private readonly string            $mainProgram,
		private readonly array              $programsToMerge,
		private readonly int               $statusToMerge,
		private readonly DatabaseInterface $db
	)
	{}

	public function checkIfApplicantHasFileOnMainProgram(): bool
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(cc.id)')
			->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
			->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $this->db->quoteName('sc.id') . ' = ' . $this->db->quoteName('cc.campaign_id'))
			->where($this->db->quoteName('cc.applicant_id') . ' = ' . $this->db->quote($this->applicantId))
			->where($this->db->quoteName('sc.training') . ' = ' . $this->db->quote($this->mainProgram));
		$this->db->setQuery($query);

		return (bool) $this->db->loadResult();
	}

	public function setApplicationFiles(): void
	{
		$query = $this->db->getQuery(true)
			->select('cc.id, cc.fnum, cc.status')
			->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
			->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $this->db->quoteName('sc.id') . ' = ' . $this->db->quoteName('cc.campaign_id'))
			->where($this->db->quoteName('cc.applicant_id') . ' = ' . $this->db->quote($this->applicantId))
			->where($this->db->quoteName('cc.published') . ' = 1')
			->where($this->db->quoteName('sc.training') . ' IN (' . implode(',', $this->db->quote($this->programsToMerge)) . ')');
		$this->db->setQuery($query);
		$this->applicationFiles = $this->db->loadAssocList();
	}

	public function getApplicationFiles(): array
	{
		return $this->applicationFiles;
	}

	public function checkIfAllFilesAreInSameStatus(): bool
	{
		foreach ($this->applicationFiles as $file)
		{
			if ($file['status'] !== $this->statusToMerge)
			{
				return false;
			}
		}

		return true;
	}

	public function runMerge($archiveFiles, $statusAfterMerge = null): bool
	{
		$copied = false;

		require_once JPATH_ROOT . '/components/com_emundus/models/application.php';
		require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
		$mApplication = new \EmundusModelApplication();
		$mFiles = new \EmundusModelFiles();

		// We need to create a file for the main program
		$newFnum      = $this->createFile();
		$fnumToCopied = $this->applicationFiles[0]['fnum'];

		if (!empty($newFnum))
		{
			$copied = $mApplication->copyApplication($fnumToCopied, $newFnum);
			if ($copied)
			{
				if(empty($statusAfterMerge)) {
					$statusAfterMerge = $this->applicationFiles[0]['status'];
				}
				$mFiles->updateState([$newFnum], $statusAfterMerge, $this->applicantId);
			}

			if ($copied && $archiveFiles)
			{
				// We need to archive the files
				$copied = $this->archiveFiles();
			}
		}

		return $copied;
	}

	private function createFile(): string
	{
		$fnum = '';

		if (!empty($this->mainProgram))
		{
			$query = $this->db->getQuery(true)
				->select('id')
				->from('#__emundus_setup_campaigns')
				->where('training = ' . $this->db->quote($this->mainProgram))
				->where('published = 1')
				->where('start_date <= NOW()')
				->where('end_date >= NOW()');
			$this->db->setQuery($query);
			$campaignId = (int) $this->db->loadResult();

			if (!empty($campaignId))
			{
				$fnum = date('YmdHis') . str_pad($campaignId, 7, '0', STR_PAD_LEFT) . str_pad($this->applicantId, 7, '0', STR_PAD_LEFT);

				if (!empty($fnum))
				{
					$insert = [
						'date_time'    => date('Y-m-d H:i:s'),
						'applicant_id' => $this->applicantId,
						'user_id'      => $this->applicantId,
						'campaign_id'  => $campaignId,
						'fnum'         => $fnum
					];
					$insert = (object) $insert;

					try
					{
						$inserted = $this->db->insertObject('#__emundus_campaign_candidature', $insert);
					}
					catch (\Exception $e)
					{
						$fnum     = '';
						$inserted = false;
						Log::add("Failed to create file $fnum - $this->applicantId" . $e->getMessage(), Log::ERROR, 'com_emundus.mergeapplications');
					}

					if (!$inserted)
					{
						$fnum = '';
					}
				}
			}
		}

		return $fnum;
	}

	private function archiveFiles(): bool
	{
		$deleted                  = false;
		$applicationFilesArchived = [];

		foreach ($this->applicationFiles as $file)
		{
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__emundus_campaign_candidature'))
				->set('published = 0')
				->where('id = ' . $this->db->quote($file['id']));
			$this->db->setQuery($query);
			$applicationFilesArchived[] = $this->db->execute();
		}

		if (count($this->applicationFiles) === count($applicationFilesArchived))
		{
			$deleted = true;
		}

		return $deleted;
	}
}