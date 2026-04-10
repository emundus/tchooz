<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Addons\AddonValue;
use Tchooz\Enums\Campaigns\AnonymizationPolicyEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Addons\AddonHandlerResolver;
use Joomla\CMS\Component\ComponentHelper;

class Release2_19_0Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'program_id', 'INT', 11)['status'];

			// set program id for existing campaigns
			$query->clear()
				->select('esc.id, esp.id AS program_id')
				->from($this->db->quoteName('jos_emundus_setup_campaigns', 'esc'))
				->leftJoin($this->db->quoteName('jos_emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esc.training') . ' = ' . $this->db->quoteName('esp.code'))
				->where($this->db->quoteName('esc.program_id') . ' IS NULL');

			$campaigns = $this->db->setQuery($query)->loadObjectList();

			foreach ($campaigns as $campaign)
			{
				$query->clear()
					->update($this->db->quoteName('jos_emundus_setup_campaigns'))
					->set($this->db->quoteName('program_id') . ' = ' . (int) $campaign->program_id)
					->where($this->db->quoteName('id') . ' = ' . (int) $campaign->id);
				$this->tasks[] = $this->db->setQuery($query)->execute();
			}

			// add foreign key constraint if not exists
			// check if the foreign key already exists
			$query->clear()
				->select('CONSTRAINT_NAME')
				->from($this->db->quoteName('information_schema.KEY_COLUMN_USAGE'))
				->where($this->db->quoteName('TABLE_NAME') . ' = ' . $this->db->quote('jos_emundus_setup_campaigns'))
				->where($this->db->quoteName('COLUMN_NAME') . ' = ' . $this->db->quote('program_id'))
				->where($this->db->quoteName('CONSTRAINT_NAME') . ' = ' . $this->db->quote('fk_jesc_program_id'));
			$fkExists = $this->db->setQuery($query)->loadResult();

			if (!$fkExists)
			{
				$query = "ALTER TABLE `jos_emundus_setup_campaigns`
				ADD CONSTRAINT `fk_jesc_program_id`
				FOREIGN KEY (`program_id`)
				REFERENCES `jos_emundus_setup_programmes`(`id`)
				ON DELETE SET NULL
				ON UPDATE CASCADE";
				$this->tasks[] = $this->db->setQuery($query)->execute();
			}

			$campaignColumn = \EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'public', 'TINYINT', 1, 0, 0);
			$this->tasks[] = $campaignColumn['status'];
			if(!$campaignColumn['status'])
			{
				$result['message'] .= $campaignColumn['message'];
			}

			$appFilePublicColumn = \EmundusHelperUpdate::addColumn('jos_emundus_campaign_candidature', 'public', 'TINYINT', 1, 0, 0);
			$this->tasks[] = $appFilePublicColumn['status'];
			if(!$appFilePublicColumn['status'])
			{
				$result['message'] .= $appFilePublicColumn['message'];
			}

			$appFileAnonymousColumn = \EmundusHelperUpdate::addColumn('jos_emundus_campaign_candidature', 'anonymous', 'TINYINT', 1, 0, 0);
			$this->tasks[] = $appFileAnonymousColumn['status'];
			if(!$appFileAnonymousColumn['status'])
			{
				$result['message'] .= $appFileAnonymousColumn['message'];
			}

			$tableCreated = \EmundusHelperUpdate::createTable('jos_emundus_file_access', [
				new \EmundusTableColumn('ccid', \EmundusColumnTypeEnum::INT, 11, false, null),
				new \EmundusTableColumn('token', \EmundusColumnTypeEnum::VARCHAR, 100, false, null),
				new \EmundusTableColumn('expiration_date', \EmundusColumnTypeEnum::DATETIME, null, false, null),
			],
				[
					new \EmundusTableForeignKey('jos_emundus_file_access_ccid_fk', 'ccid', 'jos_emundus_campaign_candidature', 'id', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),
				],
				'',
				[
					['name' => 'jos_emundus_file_access_ccid_idx', 'columns' => ['ccid']]
				]
			);
			$this->tasks[] = $tableCreated['status'];
			if(!$tableCreated['status'])
			{
				$result['message'] .= $tableCreated['message'];
			}

			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_system_emunduspublicaccess', 'emunduspublicaccess', null, 'plugin', 0, 'system');
			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_emundus_anonymization', 'anonymization', null, 'plugin', 0, 'emundus');

			$resolver           = new AddonHandlerResolver();
			$addonRepository = new AddonRepository();
			$publicSessionAddon = $addonRepository->getByName('public_session');
			if (empty($publicSessionAddon))
			{
				$addonValue         = new AddonValue(false, true, []);
				$publicSessionAddon = new AddonEntity('public_session', $addonValue);
				$handler            = $resolver->resolve('public_session', $publicSessionAddon);
				$params             = [];
				foreach ($handler->getParameters() as $parameter)
				{
					$params[$parameter->getName()] = null;
				}
				$addonValue->setParams($params);
				$publicSessionAddon->setValue($addonValue);

				$this->tasks[] = $addonRepository->flush($publicSessionAddon);
			}

			$addonRepository = new AddonRepository();
			$anonymAddon = $addonRepository->getByName('anonymous');
			if (!empty($anonymAddon))
			{
				if (!isset($anonymAddon->getValue()->getParams()['policy']))
				{
					$handler            = $resolver->resolve('anonymous', $publicSessionAddon);
					$params             = [];
					foreach ($handler->getParameters() as $parameter)
					{
						$value = null;
						if ($parameter->getName() === 'policy')
						{
							$value = AnonymizationPolicyEnum::OPTIONAL;
						}
						$params[$parameter->getName()] = $value;
					}
					$anonymAddon->getValue()->setParams($params);

					$this->tasks[] = $addonRepository->flush($anonymAddon);
				}
			}

			$addAnonymizationPolicy = EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'anonymization_policy', 'VARCHAR', 20, 1, AnonymizationPolicyEnum::GLOBAL->value);
			$this->tasks[] = $addAnonymizationPolicy['status'];
			if (!$addAnonymizationPolicy['status'])
			{
				$result['message'] .= $addAnonymizationPolicy['message'];
			}

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}
