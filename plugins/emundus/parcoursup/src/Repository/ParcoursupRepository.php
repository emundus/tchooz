<?php
/**
 * @package     Joomla\Plugin\Emundus\Parcoursup\Repository
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\Parcoursup\Repository;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Plugin\Emundus\Parcoursup\Entity\ParcoursupEntity;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\User\UserCategoryRepository;

class ParcoursupRepository
{

	private QueryInterface $query;

	private QueryInterface $subQuery;
	
	private array $config = [];

	private int $parcoursupUserCategory = 0;

	public function __construct(
		private DatabaseInterface  $db,
		private UserRepository     $userRepository,
		private CampaignRepository $campaignRepository,
		private ApplicationChoicesRepository $applicationChoicesRepository
	)
	{
		$this->query = $this->db->getQuery(true);
		$this->subQuery = $this->db->getQuery(true);
		
		$this->config = $this->getParcoursupConfig();

		$userCategoryRepository = new UserCategoryRepository();
		$userCategories = $userCategoryRepository->getAllCategories();
		foreach ($userCategories as $userCategory) {
			if($userCategory->label === 'Parcoursup')
			{
				$this->parcoursupUserCategory = $userCategory->id;
				break;
			}
		}
	}

	public function getDatas(): array
	{
		$datas = [];

		// Get datas from database
		$this->query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_campaign_candidature_parcoursup'))
			->where('json IS NOT NULL');
		$this->db->setQuery($this->query);
		$results = $this->db->loadAssocList();

		if (!empty($results))
		{
			foreach ($results as $result)
			{
				$datas[] = json_decode($result['json'], true);
			}
		}

		return $datas;
	}

	public function flush(ParcoursupEntity $datas): bool|string
	{
		$flushed = false;

		$fnum = '';
		$userId = 0;

		try
		{
			if(!empty($datas->getParcoursupId()) && !empty($this->config['lookupKeys'])) {
				$element = $this->config['lookupKeys'][0]['elementId'];
				if(!empty($element))
				{
					$table = explode('___', $element)[0];
					$field = explode('___', $element)[1];

					$this->query->clear()
						->select('cc.fnum, cc.applicant_id')
						->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
						->leftJoin($this->db->quoteName($table, 'd') . ' ON d.fnum = cc.fnum')
						->where('d.' . $field . ' = ' . $this->db->quote($datas->getParcoursupId()));
					$this->db->setQuery($this->query);
					$application = $this->db->loadObject();

					if(!empty($application->fnum))
					{
						$fnum = $application->fnum;
						$userId = $application->applicant_id;
					}
				}
			}

			if(empty($userId))
			{
				// User creation
				$this->query->clear()
					->select('id')
					->from($this->db->quoteName('#__users'))
					->where($this->db->quoteName('email') . ' = ' . $this->db->quote($datas->getUser()->email));
				$this->db->setQuery($this->query);
				$userId = $this->db->loadResult();

				// Associate Parcoursup category if exist
				if (empty($userId))
				{
					$userId = $this->userRepository->flushUser($datas->getUser());
				}

				if (empty($userId))
				{
					return false;
				}
				//

				// Application creation
				$this->query->clear()
					->select('fnum')
					->from($this->db->quoteName('#__emundus_campaign_candidature'))
					->where('campaign_id = ' . $datas->getCampaignId())
					->where('applicant_id = ' . $userId);
				$this->db->setQuery($this->query);
				$fnum = $this->db->loadResult();

				if (empty($fnum))
				{
					$fnum = $this->createFile($datas->getCampaignId(), $userId);
				}
			}
			$datas->setUserId($userId);
			$datas->getUser()->id = $userId;

			// Check if we have a Parcoursup user category
			if(!empty($this->parcoursupUserCategory))
			{
				$this->query->clear()
					->update($this->db->quoteName('#__emundus_users'))
					->set($this->db->quoteName('user_category') . ' = ' . $this->db->quote($this->parcoursupUserCategory))
					->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($userId));
				$this->db->setQuery($this->query);
				$this->db->execute();
			}
			//

			if (empty($fnum))
			{
				return false;
			}

			if ($this->fillFnum($datas, $fnum, $userId))
			{
				$flushed = $fnum;
			}

			// Check if we have choice_cid in applicationFile, if yes add application choices to application file via ApplicationChoicesRepository
			if(!empty($datas->getApplicationFileKey('choice_cid')))
			{
				// Check if not already have a choice for this campaign
				$applicationChoiceEntity = null;
				$applicationChoices = $this->applicationChoicesRepository->getChoicesByFnum($fnum);
				foreach ($applicationChoices as $choice)
				{
					if($choice->getCampaignId() === $datas->getApplicationFileKey('choice_cid'))
					{
						$applicationChoiceEntity = $choice;
					}
				}

				if(empty($applicationChoiceEntity)) {
					$campaignEntity = $this->campaignRepository->getById($datas->getApplicationFileKey('choice_cid'));

					if(!empty($campaignEntity))
					{
						$applicationChoiceEntity      = new ApplicationChoicesEntity(
							$fnum,
							$datas->getUser(),
							$campaignEntity,
							$datas->getApplicationFileKey('choice_cid'),
							0,
							ChoicesStateEnum::WAITING
						);

						$this->applicationChoicesRepository->flush($applicationChoiceEntity);
					}
				}

				if(!empty($applicationChoiceEntity->getId()))
				{
					$this->query->clear()
						->select('*')
						->from($this->db->quoteName('#__emundus_campaign_candidature_choices_more'))
						->where('parent_id = ' . $applicationChoiceEntity->getId());
					$this->db->setQuery($this->query);
					$moreApplicationChoice = $this->db->loadObject();

					if(empty($moreApplicationChoice))
					{
						$moreApplicationChoice = (object)[
							'parent_id' => $applicationChoiceEntity->getId(),
						];
						$this->db->insertObject('#__emundus_campaign_candidature_choices_more', $moreApplicationChoice);
						$moreApplicationChoice->id = $this->db->insertid();
					}
					
					// If we have datas on jos_emundus_campaign_candidature_choices_more table, we can update them with the application file datas
					$attributes = array_keys($datas->getApplicationFile());
					// Get attributes that start by jos_emundus_campaign_candidature_choices_more
					$moreAttributes = array_filter($attributes, function($attribute) {
						return str_starts_with($attribute, 'jos_emundus_campaign_candidature_choices_more');
					});

					foreach ($moreAttributes as $attribute)
					{
						$value = $datas->getApplicationFileKey($attribute);

						if(!empty($value))
						{
							if(!is_array($value))
							{
								$value = [$value];
							}

							$attributeArray = explode('___', $attribute);

							$this->query->clear()
								->delete($this->db->quoteName($attributeArray[0]))
								->where('parent_id = ' . $moreApplicationChoice->id);
							$this->db->setQuery($this->query);
							$this->db->execute();


							foreach ($value as $v)
							{
								$moreData = (object) [
									'parent_id'        => $moreApplicationChoice->id,
									$attributeArray[1] => $v
								];

								$this->db->insertObject($attributeArray[0], $moreData);
							}
						}
					}
				}
			}
			//

			if(!$this->updateParcoursupState($fnum, $datas->getParcourSupId(), $datas->getCampaignId()))
			{
				$flushed = false;
			}
			//
		}
		catch (\Exception $e)
		{
			return false;
		}

		return $flushed;
	}

	public function deleteApplicationFile($idParcoursup, $deleteUser = false): bool
	{
		$deleted = true;

		$this->query->clear()
			->select('fnum')
			->from($this->db->quoteName('#__emundus_campaign_candidature_parcoursup'))
			->where('id_parcoursup = ' . $this->db->quote($idParcoursup));
		$this->db->setQuery($this->query);
		$fnum = $this->db->loadResult();

		if(!empty($fnum))
		{
			$this->query->clear()
				->select('applicant_id, status')
				->from($this->db->quoteName('#__emundus_campaign_candidature'))
				->where('fnum LIKE ' . $this->db->quote($fnum));
			$this->db->setQuery($this->query);
			$fnumInfos = $this->db->loadAssoc();

			if(in_array($fnumInfos['status'],[0,1]))
			{
				$this->query->clear()
					->update($this->db->quoteName('#__emundus_campaign_candidature'))
					->set('published = -1')
					->where('fnum = ' . $this->db->quote($fnum));
				$this->db->setQuery($this->query);
				$deleted = $this->db->execute();

				/*if ($deleted)
				{
					if($deleteUser)
					{
						$this->query->clear()
							->select('count(id)')
							->from($this->db->quoteName('#__emundus_campaign_candidature'))
							->where('applicant_id = ' . $fnumInfos['applicant_id']);
						$this->db->setQuery($this->query);
						$count = $this->db->loadResult();

						if ($count === 0)
						{
							$this->query->clear()
								->delete($this->db->quoteName('#__users'))
								->where('id = ' . $fnumInfos['applicant_id']);
							$this->db->setQuery($this->query);
							$this->db->execute();
						}
					}

					$this->query->clear()
						->update($this->db->quoteName('#__emundus_campaign_candidature_parcoursup'))
						->set('deleted_at = ' . $this->db->quote(date('Y-m-d H:i:s')))
						->set('fnum = NULL')
						->where('id_parcoursup = ' . $this->db->quote($idParcoursup));
					$this->db->setQuery($this->query);
					$this->db->execute();
				}*/
			}
		}

		return $deleted;
	}

	private function createFile(int $campaignId, int $userId)
	{
		$fnum = '';

		if (!empty($campaignId))
		{
			$fnum = date('YmdHis') . str_pad($campaignId, 7, '0', STR_PAD_LEFT) . str_pad($userId, 7, '0', STR_PAD_LEFT);

			if (!empty($fnum))
			{
				$insert = [
					'date_time'    => date('Y-m-d H:i:s'),
					'applicant_id' => $userId,
					'user_id'      => $userId,
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
					Log::add("Failed to create file $fnum - $userId" . $e->getMessage(), Log::ERROR, 'com_emundus.parcoursup');
				}

				if (!$inserted)
				{
					$fnum = '';
				}
			}
		}

		return $fnum;
	}

	private function fillFnum(ParcoursupEntity $datas, string $fnum, int $userId): bool
	{
		$filled = true;

		try
		{
			$queries = [];

			foreach ($datas->getApplicationFile() as $field => $data)
			{
				if(str_contains($field, 'jos_emundus_campaign_candidature_choices_more'))
				{
					continue;
				}

				if (str_contains($field, '___'))
				{
					$table = explode('___', $field)[0];
					$field = explode('___', $field)[1];

					if (is_array($data) && !str_contains($table, 'repeat'))
					{
						$data = implode(',', $data);
					}
					
					$queries[$table][$field] = $data;
				}
			}

			foreach ($queries as $table => $insert)
			{
				$values = array_map(
					function ($value) {
						return $value;
					},
					$insert
				);
				
				if (str_contains($table, 'repeat'))
				{
					$length = null;
					$firstElement = array_key_first($values);
					if (is_array($values[$firstElement]))
					{
						$length = count($values[$firstElement]);
					}
				}
				
				$values = (object) $values;

				$this->query->clear()
					->select('id')
					->from($this->db->quoteName($table));

				if ($table === 'jos_emundus_users')
				{
					$this->query->where('user_id = ' . $userId);
				}
				elseif (str_contains($table, 'repeat'))
				{
					// Get parent id from table parent
					$this->subQuery->clear()
						->select('join_from_table')
						->from($this->db->quoteName('jos_fabrik_joins'))
						->where('table_join = ' . $this->db->quote($table));
					$this->db->setQuery($this->subQuery);
					$joinFromTable = $this->db->loadResult();

					if (!empty($joinFromTable))
					{
						$this->subQuery->clear()
							->select('id')
							->from($this->db->quoteName($joinFromTable))
							->where('fnum = ' . $this->db->quote($fnum));
						$this->db->setQuery($this->subQuery);
						$parentId = $this->db->loadResult();

						if (empty($parentId))
						{
							// Create row in parent table
							$parentValues            = new \stdClass();
							$parentValues->fnum      = $fnum;
							$parentValues->user      = $userId;
							$parentValues->time_date = date('Y-m-d H:i:s');

							if ($this->db->insertObject($joinFromTable, $parentValues))
							{
								$parentId = $this->db->insertid();
							}
						}

						if (!empty($parentId))
						{
							$values->parent_id = [];
							for ($i = 0; $i < $length; $i++)
							{
								$values->parent_id[$i] = $parentId;
							}
						}
					}
				}
				else
				{
					$values->fnum = $fnum;
					$values->user = $userId;
					$this->query->where('fnum = ' . $this->db->quote($fnum));
				}

				if(!str_contains($table, 'repeat'))
				{
					$this->db->setQuery($this->query);
					$id = $this->db->loadResult();

					if (empty($id))
					{
						$this->db->insertObject($table, $values);
					}
					else
					{
						$values->id = $id;
						$this->db->updateObject($table, $values, 'id');
					}
				}
				elseif(!empty($parentId) && $length > 0)
				{
					// Delete each repeatable row
					$this->query->clear()
						->delete($this->db->quoteName($table))
						->where('parent_id = ' . $this->db->quote($parentId));
					$this->db->setQuery($this->query);
					$this->db->execute();
					
					// Insert each repeatable row
					for($i = 0; $i < $length; $i++)
					{
						$values = (object) array_map(
							function ($value) use ($i) {
								return $value[$i];
							},
							$insert
						);
						$values->parent_id = $parentId;
						$this->db->insertObject($table, $values);
					}
				}

				// Log query
				Log::add($this->db->getQuery(), Log::DEBUG, 'com_emundus.parcoursup');
			}
		}
		catch (\Exception $e)
		{
			$filled = false;
			Log::add(Text::_('PLG_EMUNDUS_PARCOURSUP_ERROR_FILLING_FNUM') . ' ' . $e->getMessage(), Log::ERROR, 'emundus');
		}

		return $filled;
	}

	private function updateParcoursupState(string $fnum, string $idParcoursup, int $campaignId): bool
	{
		$query = $this->db->getQuery(true);

		$query->update($this->db->quoteName('#__emundus_campaign_candidature_parcoursup'))
			->set('fnum = ' . $this->db->quote($fnum))
			->set('updated_at = ' . $this->db->quote(date('Y-m-d H:i:s')))
			->set('json = null')
			->where('id_parcoursup = ' . $this->db->quote($idParcoursup))
			->where('campaign_id = ' . $this->db->quote($campaignId));
		$this->db->setQuery($query);
		return $this->db->execute();
	}

	private function getParcoursupConfig(): array
	{
		$config = [];
		$query  = $this->db->getQuery(true);

		$query->select('params')
			->from('#__emundus_setup_sync')
			->where('type = ' . $this->db->quote('parcoursup'));
		$this->db->setQuery($query);
		$jsonConfig = $this->db->loadResult();

		if (!empty($jsonConfig))
		{
			$config = json_decode($jsonConfig, true);
		}

		return $config;
	}
}