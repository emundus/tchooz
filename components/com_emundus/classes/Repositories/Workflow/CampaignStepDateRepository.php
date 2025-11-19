<?php

namespace Tchooz\Repositories\Workflow;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Workflow\CampaignStepDateEntity;
use Tchooz\Factories\Workflow\CampaignStepDateFactory;
use Tchooz\Traits\TraitTable;


#[TableAttribute(table: 'jos_emundus_setup_campaigns_step_dates')]
class CampaignStepDateRepository
{
	use TraitTable;

	private DatabaseDriver $db;

	public function __construct(?DatabaseDriver $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
		Log::addLogger(['text_file' => 'com_emundus.repository.campaignstepdate.php'], Log::ALL, ['com_emundus.repository.campaignstepdate']);
	}

	/**
	 * @param   int  $stepId
	 *
	 * @return array<CampaignStepDateEntity>
	 */
	public function getCampaignsDatesByStepId(int $stepId): array
	{
		$dates = [];

		if (!empty($stepId))
		{
			$query = $this->db->createQuery();
			$query->select('*')
				->from($this->db->quoteName($this->getTableName(self::class)))
				->where($this->db->quoteName('step_id') . ' = ' . $stepId);

			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			if (!empty($results))
			{
				$dates = CampaignStepDateFactory::fromDbObjects($results);
			}
		}

		return $dates;
	}

	/**
	 * @param   CampaignStepDateEntity  $date
	 *
	 * @return bool
	 */
	public function save(CampaignStepDateEntity $date): bool
	{
		$saved = false;

		try {
			if (!empty($date->getStepId()) && !empty($date->getCampaignId()))
			{
				$columns = [
					'campaign_id' => $date->getCampaignId(),
					'step_id' => $date->getStepId(),
					'start_date' => $date->getStartDate()?->format('Y-m-d H:i:s'),
					'end_date' => $date->getEndDate()?->format('Y-m-d H:i:s'),
					'infinite' => (int) $date->isInfinite(),
					'relative_date' => (int) $date->isRelativeDate(),
					'relative_to' => $date->getRelativeTo()->value,
					'relative_start_date_value' => $date->getRelativeStartDateValue(),
					'relative_start_date_unit' => $date->getRelativeStartDateUnit()->value,
					'relative_end_date_value' => $date->getRelativeEndDateValue(),
					'relative_end_date_unit' => $date->getRelativeEndDateUnit()->value
				];

				$dateObject = (object) $columns;
				if (empty($date->getId()))
				{
					$saved = $this->db->insertObject($this->getTableName(self::class), $dateObject);
					if ($saved)
					{
						$id = $this->db->insertid();
						$date->setId($id);
					}
				}
				else
				{
					$dateObject->id = $date->getId();
					$saved = $this->db->updateObject($this->getTableName(self::class), $dateObject, 'id', true);
				}
			}
		} catch (\Exception $e) {
			Log::add('Error saving CampaignStepDateEntity: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.campaignstepdate');
		}

		return $saved;
	}

	public function delete(CampaignStepDateEntity $date): bool
	{
		$deleted = false;

		if (!empty($date->getId()))
		{
			$query = $this->db->createQuery()
				->delete($this->db->quoteName($this->getTableName(self::class)))
				->where($this->db->quoteName('id') . ' = ' . $date->getId());

			try {
				$this->db->setQuery($query);
				$this->db->execute();
				$deleted = true;
			} catch (\Exception $e) {
				Log::add('Error deleting CampaignStepDateEntity with ID ' . $date->getId() . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.campaignstepdate');
			}
		}

		return $deleted;
	}
}