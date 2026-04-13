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
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Addons\AddonValue;
use Tchooz\Enums\Campaigns\AnonymizationPolicyEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Addons\AddonHandlerResolver;
use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Factories\Language\LanguageFactory;
use scripts\ReleaseInstaller;
use Tchooz\Repositories\Language\LanguageRepository;
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
			$query->select('id')
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

			$query->clear()
				->select('value')
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('custom_reference_format'));
			$this->db->setQuery($query);
			$customReferenceModule = $this->db->loadResult();
			if(empty($customReferenceModule))
			{
				$defaultParameters = [
					'blocks' => [],
					'separator' => '-',
					'triggering_status' => null,
					'show_to_applicant' => false,
					'show_in_files' => false,
					'sequence' => [
						'position' => 'end',
						'reset_type' => 'never',
						'length' => 4
					]
				];
				$customReferenceModule = (object) [
					'namekey' => 'custom_reference_format',
					'value' => json_encode($defaultParameters),
					'default' => '[]'
				];
				$this->tasks[] = $this->db->insertObject('#__emundus_setup_config', $customReferenceModule);
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

			$actionRepository = new ActionRepository(false);
			$anonymousRevealAction = $actionRepository->getByName('anonymous_reveal');
			if (empty($anonymousRevealAction))
			{
				$anonymousRevealAction = new ActionEntity(0, 'anonymous_reveal', Text::_('COM_EMUNDUS_ACL_ANONYMIZATION_REVEAL'), new CrudEntity(0, 1, 0, 0, 0), 30, false, 'COM_EMUNDUS_ACL_ANONYMIZATION_REVEAL_DESC');
				if (!$actionRepository->flush($anonymousRevealAction))
				{
					$this->tasks[] = false;
					$result['message'] .= 'Failed to create new action ' . $anonymousRevealAction->getName() . '. ';
				}
			}

			$eventsAdded = EmundusHelperUpdate::addCustomEvents([
				['label' => 'onAskForAnonymousReveal', 'published' => 1, 'category' => 'File', 'available' => 0]
			]);
			$this->tasks[] = $eventsAdded['status'];
			if (!$eventsAdded['status'])
			{
				$result['message'] .= $eventsAdded['message'];
			}

			$query->clear()
				->select('parent_id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&controller=files&task=getstate'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('actions'));
			$this->db->setQuery($query);
			$parent_id = $this->db->loadResult();

			$datas        = [
				'menutype'     => 'actions',
				'title'        => 'Demander la désanonymisation du dossier',
				'alias'        => 'ask-for-reveal',
				'link'         => 'index.php?option=com_emundus&view=application',
				'type'         => 'url',
				'component_id' => 0,
				'note'         => 'anonymous_reveal|c|1'
			];
			$reveal_menu = \EmundusHelperUpdate::addJoomlaMenu($datas, $parent_id, 0);
			if ($this->tasks[] = $reveal_menu['status'])
			{
				$this->tasks[] = \EmundusHelperUpdate::insertFalangTranslation(1, $reveal_menu['id'], 'menu', 'title', 'Demander la désanonymisation du dossier', true);
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
