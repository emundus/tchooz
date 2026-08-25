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
use Tchooz\Entities\Automation\EventsDefinitions\onAfterApplicationChoiceUpdateDefinition;
use Tchooz\Entities\Emails\Providers\ChoiceCommentTagProvider;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Enums\Automation\EventCategoryEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\QueryInterface;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class Release2_24_0Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		try
		{
			$query = $this->db->createQuery();

			$importTable = \EmundusHelperUpdate::createTable(
				'jos_emundus_import',
				[
					new \EmundusTableColumn('created_at', \EmundusColumnTypeEnum::DATETIME, null, false, null),
					new \EmundusTableColumn('created_by', \EmundusColumnTypeEnum::INT, null, false, null),
					new \EmundusTableColumn('type', \EmundusColumnTypeEnum::VARCHAR, 50, false, ''),
					new \EmundusTableColumn('filename', \EmundusColumnTypeEnum::VARCHAR, 255, true, null),
					new \EmundusTableColumn('original_filename', \EmundusColumnTypeEnum::VARCHAR, 255, true, null),
					new \EmundusTableColumn('format', \EmundusColumnTypeEnum::VARCHAR, 10, false, ''),
					new \EmundusTableColumn('conflict_mode', \EmundusColumnTypeEnum::VARCHAR, 20, false, 'skip'),
					new \EmundusTableColumn('progress', \EmundusColumnTypeEnum::FLOAT, null, false, '0'),
					new \EmundusTableColumn('total_rows', \EmundusColumnTypeEnum::INT, null, false, '0'),
					new \EmundusTableColumn('last_processed_row', \EmundusColumnTypeEnum::INT, null, false, '0'),
					new \EmundusTableColumn('report', \EmundusColumnTypeEnum::JSON, null, true, null),
					new \EmundusTableColumn('task_id', \EmundusColumnTypeEnum::INT, null, true, null),
					new \EmundusTableColumn('cancelled', \EmundusColumnTypeEnum::TINYINT, 1, false, '0'),
					new \EmundusTableColumn('failed', \EmundusColumnTypeEnum::TINYINT, 1, false, '0'),
				]
			);
			$this->tasks[] = $importTable['status'];
			if (!$importTable['status'])
			{
				$result['message'] .= "\n" . $importTable['message'];
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=imports&layout=imports'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('topmenu'));
			$this->db->setQuery($query);
			$exists = $this->db->loadResult();

			if (!$exists)
			{
				$datas        = [
					'menutype'     => 'topmenu',
					'title'        => 'Imports',
					'alias'        => 'my-imports',
					'link'         => 'index.php?option=com_emundus&view=imports&layout=imports',
					'type'         => 'component',
					'component_id' => ComponentHelper::getComponent('com_emundus')->id,
					'params'       => [
						'menu_image_css' => 'archive',
						'menu_show'      => 0
					]
				];
				$imports_menu = \EmundusHelperUpdate::addJoomlaMenu($datas);
				if($this->tasks[] = $imports_menu['status'])
				{
					$this->tasks[] = \EmundusHelperUpdate::insertFalangTranslation(1, $imports_menu['id'], 'menu', 'title', 'Mes imports');
				}
			}

			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_task_purgeimports', 'purgeimports', null, 'plugin', 1, 'task');

			$execution_rules = [
				'rule-type'     => 'interval-days',
				'interval-days' => '1',
				'exec-day'      => date('d'),
				'exec-time'     => '03:30',
			];
			$cron_rules = [
				'type' => 'interval',
				'exp'  => 'P1D',
			];
			$this->tasks[] = \EmundusHelperUpdate::createSchedulerTask('Purge finished imports', 'plg_task_purgeimports_task_get', $execution_rules, $cron_rules);

			$addonRepository = new AddonRepository();
			$choiceAddon = $addonRepository->getByName(AddonEnum::CHOICES->value);
			$added = \EmundusHelperUpdate::addCustomEvents([
				['label' => onAfterApplicationChoiceUpdateDefinition::NAME, 'description' => '', 'category' => EventCategoryEnum::CHOICES->value, 'published' => 1, 'available' => !empty($choiceAddon) ? (int) $choiceAddon->isActivated() : 0],
			]);
			$this->tasks[] = $added['status'];

			if (!$added['status'])
			{
				$result['message'] .= $added['message'] . "\n";
			}

			// The tag itself is resolved by ChoiceCommentTagProvider. The row only makes it selectable in
			// the email editor, hence the self referencing request, as LAST_COMMENT and VOEU already do.
			$this->tasks[] = $this->addSelectableProviderTag(
				ChoiceCommentTagProvider::TAG,
				'Voeux du dossier et motif de leur dernier changement d\'état. Accepte les modificateurs STATUS et INDEX.'
			);

			$this->initLanguagesFeature($query);

			$this->createFavoriteFilesTable();
			$this->registerFavoriteAddon();




			// Worldline
			$repository = new SynchronizerRepository();
			$worldline  = $repository->getByType('worldline');

			if (empty($worldline))
			{
				$config = [
					'authentication' => [
						'mode'           => 0,
						'merchant_id'    => '',
						'api_key_id'     => '',
						'api_secret'     => '',
						'webhook_key_id' => '',
						'webhook_secret' => '',
					],
				];

				$worldline = new SynchronizerEntity(
					0,
					'worldline',
					'Worldline',
					'Paiement via le service Worldline Connect',
					[],
					$config,
					false,
					false,
					'worldline.png'
				);

				$this->tasks[] = $repository->flush($worldline);
			}

			if (!empty($worldline) && !empty($worldline->getId()))
			{
				$this->tasks[] = $this->associatePaymentMethod('CB', $worldline->getId());
			}







			$result['status'] = !in_array(false, $this->tasks, true);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function associatePaymentMethod(string $methodName, int $serviceId): bool
	{
		$query = $this->db->getQuery(true);
		$query->select('id')
			->from($this->db->quoteName('#__emundus_setup_payment_method'))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote($methodName));
		$this->db->setQuery($query);
		$methodId = $this->db->loadResult();

		if (empty($methodId))
		{
			return true;
		}

		$query->clear()
			->select('payment_method_id')
			->from($this->db->quoteName('#__emundus_setup_payment_method_sync'))
			->where($this->db->quoteName('payment_method_id') . ' = ' . $this->db->quote($methodId))
			->andWhere($this->db->quoteName('service_id') . ' = ' . $this->db->quote($serviceId));
		$this->db->setQuery($query);

		if (!empty($this->db->loadResult()))
		{
			return true;
		}

		$association                    = new \stdClass();
		$association->payment_method_id = $methodId;
		$association->service_id        = $serviceId;

		return $this->db->insertObject('#__emundus_setup_payment_method_sync', $association);
	}

	/**
	 * Registers a tag whose value comes from a provider, so it shows up in the email editor picker.
	 * Idempotent: an already known tag is left untouched.
	 */
	private function addSelectableProviderTag(string $tag, string $description): bool
	{
		$query = $this->db->createQuery();

		$query->select('COUNT(id)')
			->from($this->db->quoteName('#__emundus_setup_tags'))
			->where($this->db->quoteName('tag') . ' = ' . $this->db->quote($tag));

		if ($this->db->setQuery($query)->loadResult() > 0)
		{
			return true;
		}

		$query->clear()
			->insert($this->db->quoteName('#__emundus_setup_tags'))
			->columns($this->db->quoteName(['tag', 'request', 'description', 'published']))
			->values(
				$this->db->quote($tag) . ', ' .
				$this->db->quote('[' . $tag . ']') . ', ' .
				$this->db->quote($description) . ', 1'
			);

		return (bool) $this->db->setQuery($query)->execute();
	}

	private function initLanguagesFeature(QueryInterface $query): void
	{
		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
			->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=languages'));
		$this->db->setQuery($query);
		$languagesMenuId = $this->db->loadResult();

		if (empty($languagesMenuId))
		{
			$data          = [
				'menutype'          => 'onboardingmenu',
				'title'             => 'Traductions système',
				'alias'             => 'languages',
				'path'              => 'languages',
				'link'              => 'index.php?option=com_emundus&view=languages',
				'type'              => 'component',
				'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
				'access'            => AccessLevelEnum::ADMINISTRATOR->value,
				'template_style_id' => 0,
				'params'            => [
					'menu_image_css' => 'translate'
				],
			];
			$languagesMenu = \EmundusHelperUpdate::addJoomlaMenu($data, 1, 1);

			if ($this->tasks[] = $languagesMenu['status'])
			{
				$languagesMenuId = $languagesMenu['id'];
				\EmundusHelperUpdate::insertFalangTranslation(1, $languagesMenuId, 'menu', 'title', 'System translations');
			}
		}
	}

	/**
	 * Personal favorites on application files: one row per (user, fnum).
	 */
	private function createFavoriteFilesTable(): void
	{
		$columns = [
			[
				'name'   => 'fnum',
				'type'   => 'VARCHAR',
				'length' => 28,
				'null'   => 0,
			],
			[
				'name'   => 'user_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
			],
			[
				'name' => 'created',
				'type' => 'DATETIME',
				'null' => 0,
			]
		];

		$foreign_keys = [
			[
				'name'           => 'emundus_favorite_files_fnum_fk',
				'from_column'    => 'fnum',
				'ref_table'      => '#__emundus_campaign_candidature',
				'ref_column'     => 'fnum',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'emundus_favorite_files_user_fk',
				'from_column'    => 'user_id',
				'ref_table'      => '#__emundus_users',
				'ref_column'     => 'user_id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];

		// user_id first: the dominant query is "every favorite of user X" (the filter subquery),
		// which this index prefix serves directly.
		$unique_keys = [
			[
				'name'    => 'emundus_favorite_files_user_fnum',
				'columns' => ['user_id', 'fnum']
			]
		];

		$created       = EmundusHelperUpdate::createTable('jos_emundus_favorite_files', $columns, $foreign_keys, '', $unique_keys);
		$this->tasks[] = $created['status'];

		if ($created['status'])
		{
			$indexed       = EmundusHelperUpdate::addColumnIndex('jos_emundus_favorite_files', 'fnum');
			$this->tasks[] = $indexed['status'];
		}
	}

	/**
	 * Registers favorites as a suggested, deactivated addon: the feature must never appear on an
	 * instance that did not explicitly ask for it.
	 */
	private function registerFavoriteAddon(): void
	{
		$addonRepository = new AddonRepository();

		if (!empty($addonRepository->getByName(AddonEnum::FAVORITE->value)))
		{
			return;
		}

		$addon         = new AddonEntity(AddonEnum::FAVORITE->value, false, false, true);
		$this->tasks[] = $addonRepository->flush($addon);
	}
}
