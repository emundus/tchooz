<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Addons\AddonRepository;
use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Reference\InternalReferenceEntity;

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

			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'custom_reference', crud: ['multi' => 1, 'c' => 1, 'r' => 0, 'u' => 0, 'd' => 1], published: 1);

			$this->tasks[] = \EmundusHelperUpdate::makeFromEntity(InternalReferenceEntity::class);
			$this->tasks[] = \EmundusHelperUpdate::makeFromEntity(ApplicationFileEntity::class);
			$this->tasks[] = \EmundusHelperUpdate::addColumnIndex('jos_emundus_internal_reference', 'sequence_int')['status'];

			$query = $this->db->createQuery();
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=files&layout=generatereference&format=raw'));
			$this->db->setQuery($query);
			$existingMenu = $this->db->loadResult();

			if (empty($existingMenu))
			{
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('2014-09-25-11-03-52'))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'));
				$this->db->setQuery($query);
				$headingId = $this->db->loadResult();

				$data                  = [
					'menutype'          => 'actions',
					'title'             => 'Générer une référence',
					'alias'             => 'generate-reference',
					'path'              => 'generate-reference',
					'link'              => 'index.php?option=com_emundus&view=files&layout=generatereference&format=raw',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
					'note'              => 'custom_reference|c|1',
				];
				$generateReferenceMenu = \EmundusHelperUpdate::addJoomlaMenu($data, $headingId, 1);
				\EmundusHelperUpdate::insertFalangTranslation(1, $generateReferenceMenu['id'], 'menu', 'title', 'Generate a reference');
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=references&layout=history&format=raw'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('application'));
			$this->db->setQuery($query);
			$existingMenu = $this->db->loadResult();

			if (empty($existingMenu))
			{
				$data                  = [
					'menutype'          => 'application',
					'title'             => 'Historique des références',
					'alias'             => 'references-history',
					'path'              => 'references-history',
					'link'              => 'index.php?option=com_emundus&view=references&layout=history&format=raw',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
					'note'              => 'custom_reference|r',
					'access'            => 6
				];
				$generateReferenceMenu = \EmundusHelperUpdate::addJoomlaMenu($data, 1, 1);
				\EmundusHelperUpdate::insertFalangTranslation(1, $generateReferenceMenu['id'], 'menu', 'title', 'Reference\'s history');
			}

			$this->rebuildConfigTable();

			// Add custom reference addon in setup_config
			$addonRepository = new AddonRepository();
			$customReferenceAddon = $addonRepository->getByName('custom_reference_format');
			if (!$customReferenceAddon)
			{
				$params = [
					'blocks' => [],
					'triggering_status' => null,
					'show_to_applicant' => false,
					'show_in_files' => false,
					'separator' => '-',
					'sequence' => [
						'position' => 'end',
						'reset_type' => 'yearly',
						'length' => 4
					],
				];
				$customReferenceAddon = new AddonEntity('custom_reference_format', false, false, false, $params);
				$this->tasks[] = $addonRepository->flush($customReferenceAddon);
			}

			// Add booking addon in setup_config
			$bookingAddon = $addonRepository->getByName('booking');
			if (!$bookingAddon)
			{
				$params = [];
				$bookingAddon = new AddonEntity('booking', false, false, false, $params);
				$this->tasks[] = $addonRepository->flush($bookingAddon);
			}

			$installed = \EmundusHelperUpdate::installExtension('plg_task_booking_recall', 'bookingrecall', null, 'plugin', 0, 'task');
			$this->tasks[] = $installed;
			if (!$installed)
			{
				$result['message'] .= 'Failed to install booking_recall plugin. ';
			}

			// Update sogecommerce params to have a configuration key
			$query->clear()
				->select('id, config')
				->from($this->db->quoteName('#__emundus_setup_sync'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('sogecommerce'));
			$this->db->setQuery($query);
			$sogecommerce = $this->db->loadObject();

			if (!empty($sogecommerce))
			{
				$configuration = json_decode($sogecommerce->config);
				$configuration->configuration = [
					'endpoint' => $configuration->endpoint ?? '',
					'mode' => $configuration->mode ?? '',
					'return_url' => $configuration->return_url ?? '',
				];

				$sogecommerce->config = json_encode($configuration);
				$this->tasks[] = $this->db->updateObject('#__emundus_setup_sync', $sogecommerce, 'id');
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

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function rebuildConfigTable(): void
	{
		$query = $this->db->getQuery(true);

		$tableUpdated = \EmundusHelperUpdate::makeFromEntity(AddonEntity::class);

		if (!$tableUpdated)
		{
			throw new \Exception("Failed to update AddonEntity #_emundus_setup_config table.");
		}

		// Set activated and displayed columns based on value json property for backward compatibility
		$query->clear()
			->select('namekey, value, params, activated, displayed')
			->from($this->db->quoteName('#__emundus_setup_config'))
			->where($this->db->quoteName('params') . ' IS NULL OR ' . $this->db->quoteName('params') . ' = \'\'');
		$this->db->setQuery($query);
		$configs = $this->db->loadObjectList();

		foreach ($configs as $config)
		{
			if (empty($config->value))
			{
				$config->value = '{}';
			}

			$params = json_decode($config->value);
			if (isset($params->enabled))
			{
				if ($params->enabled || $params->enabled == 1)
				{
					$config->activated = 1;
				}
				else
				{
					$config->activated = 0;
				}
				unset($params->enabled);
			}

			if (isset($params->displayed))
			{
				if ($params->displayed || $params->displayed == 1)
				{
					$config->displayed = 1;
				}
				else
				{
					$config->displayed = 0;
				}
				unset($params->displayed);
			}

			$config->params = $params;
			if (isset($params->params))
			{
				$config->params = $params->params;
			}
			$config->params = json_encode($config->params);

			$this->tasks[] = $this->db->updateObject('#__emundus_setup_config', $config, 'namekey');
		}
	}
}
