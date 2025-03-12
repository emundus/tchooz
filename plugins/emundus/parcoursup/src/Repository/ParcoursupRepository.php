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

readonly class ParcoursupRepository
{

	private QueryInterface $query;

	private QueryInterface $subQuery;

	public function __construct(
		private DatabaseInterface  $db,
		private UserRepository     $userRepository
	)
	{
		$this->query = $this->db->getQuery(true);
		$this->subQuery = $this->db->getQuery(true);
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
				$datas[$result['id_parcoursup']] = json_decode($result['json'], true);
			}
		}

		return $datas;
	}

	public function flush(ParcoursupEntity $datas): bool
	{
		$flushed = false;

		try
		{
			// User creation
			$this->query->clear()
				->select('id')
				->from($this->db->quoteName('#__users'))
				->where($this->db->quoteName('email') . ' = ' . $this->db->quote($datas->getUser()->email));
			$this->db->setQuery($this->query);
			$userId = $this->db->loadResult();

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

			if (empty($fnum))
			{
				return false;
			}
			
			if ($this->fillFnum($datas, $fnum, $userId))
			{
				$flushed = true;
			}
			//

			if(!$this->updateParcoursupState($fnum, $datas->getParcourSupId()))
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

	public function deleteApplicationFile($idParcoursup, $deleteUser = false)
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
					->delete($this->db->quoteName('#__emundus_campaign_candidature'))
					->where('fnum = ' . $this->db->quote($fnum));
				$this->db->setQuery($this->query);
				$deleted = $this->db->execute();

				if ($deleted)
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
				}
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

	private function updateParcoursupState(string $fnum, string $idParcoursup): bool
	{
		$updateValues = [
			'id_parcoursup' => $idParcoursup,
			'fnum' => $fnum,
			'updated_at' => date('Y-m-d H:i:s'),
			'json' => null
		];
		$updateValues = (object) $updateValues;
		return $this->db->updateObject('#__emundus_campaign_candidature_parcoursup', $updateValues, 'id_parcoursup', true);
	}
}