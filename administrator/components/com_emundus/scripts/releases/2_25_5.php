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
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Addons\Configurations\ChoicesAddonConfiguration;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Tchooz\Enums\AccessLevelEnum;

class Release2_25_5Installer extends ReleaseInstaller
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
			$this->tasks[] = EmundusHelperUpdate::disableEmundusPlugins('emunduswaitingroom', 'system');

			$this->indexEvaluationTables();
			$this->updateChoicesConfiguration();
			$this->campaignsLoggedArticle();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Throwable $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}



	/**
	 * Evaluation step tables only had their primary key: filtering and counting by file, step or
	 * evaluator scanned them entirely. Same indexes as the ones set when such a table is created.
	 */
	private function indexEvaluationTables(): void
	{
		$existing_tables = $this->db->getTableList();

		$query = $this->db->createQuery()
			->select('DISTINCT ' . $this->db->qn('fl.db_table_name'))
			->from($this->db->qn('#__emundus_setup_workflows_steps', 'esws'))
			->join('INNER', $this->db->qn('#__fabrik_lists', 'fl') . ' ON ' . $this->db->qn('fl.form_id') . ' = ' . $this->db->qn('esws.form_id'));
		$this->db->setQuery($query);

		foreach ($this->db->loadColumn() as $table)
		{
			// A list can outlive its table: skip it rather than fail the whole release.
			if (!in_array($table, $existing_tables, true))
			{
				continue;
			}

			$columns = $this->db->getTableColumns($table);

			foreach (['ccid', 'fnum', 'evaluator'] as $column)
			{
				// Some old tables store these columns as TEXT, which MySQL cannot index without a prefix length.
				if (isset($columns[$column]) && !preg_match('/text|blob/i', $columns[$column]))
				{
					$this->tasks[] = EmundusHelperUpdate::addColumnIndex($table, $column)['status'];
				}
			}
		}
	}

	private function updateChoicesConfiguration(): void
	{
		$addonRepository = new AddonRepository();
		$choicesAddon = $addonRepository->getByName(AddonEnum::CHOICES->value);
		if(!empty($choicesAddon))
		{
			$rejectedVisiblesParameter = $choicesAddon->getParam(ChoicesAddonConfiguration::REJECTED_VISIBLES, 'configuration');
			if(is_null($rejectedVisiblesParameter))
			{
				$choicesAddonParams = $choicesAddon->getParams();
				if(isset($choicesAddonParams['configuration']))
				{
					$choicesAddonParams['configuration'][ChoicesAddonConfiguration::REJECTED_VISIBLES] = 1;
					$choicesAddon->setParams($choicesAddonParams);

					$this->tasks[] = $addonRepository->flush($choicesAddon);
				}
			}
		}
	}

	private function campaignsLoggedArticle(): void
	{
		$query = $this->db->createQuery();
		$registeredAccessLevel      = AccessLevelEnum::REGISTERED->value;
		$deprecatedFormsAccessLevel = AccessLevelEnum::FORMS->value;
		$query->clear()
			->select('id, params, access')
			->from($this->db->quoteName('#__modules'))
			->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_campaign'))
			->where('JSON_EXTRACT('.$this->db->quoteName('params').', '.$this->db->quote('$.mod_em_campaign_layout') . ') = ' . $this->db->quote('default_tchooz'))
			->where($this->db->quoteName('access') . ' IN (:registeredAccessLevel, :deprecatedFormsAccessLevel)')
			->bind(':registeredAccessLevel', $registeredAccessLevel, ParameterType::INTEGER)
			->bind(':deprecatedFormsAccessLevel', $deprecatedFormsAccessLevel, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$campaignLoggedModule = $this->db->loadObject();

		if(!empty($campaignLoggedModule) && !empty($campaignLoggedModule->id))
		{
			// The module could still carry the deprecated FORMS access level: force it back to REGISTERED.
			if((int) $campaignLoggedModule->access !== $registeredAccessLevel)
			{
				$update = (object) [
					'id'     => (int) $campaignLoggedModule->id,
					'access' => $registeredAccessLevel,
				];
				$this->tasks[] = $this->db->updateObject('#__modules', $update, 'id');
			}

			$params = json_decode($campaignLoggedModule->params ?? '');
			if(!is_object($params))
			{
				$params = new \stdClass();
			}

			if(empty($params->mod_em_campaign_article_logged))
			{
				$articleId = $this->createEmptyArticle($query, 'campaigns-logged', 'Campaigns logged', (string) ($params->mod_em_campaign_intro ?? ''));

				if(!empty($articleId))
				{
					$query->clear()
						->select('item_id')
						->from($this->db->quoteName('#__workflow_associations'))
						->where($this->db->quoteName('item_id') . ' = :itemId')
						->where($this->db->quoteName('extension') . ' = ' . $this->db->quote('com_content.article'))
						->bind(':itemId', $articleId, ParameterType::INTEGER);
					$this->db->setQuery($query);
					$associationExist = $this->db->loadResult();
					if(empty($associationExist))
					{
						$association = (object) [
							'item_id' => $articleId,
							'stage_id' => 1,
							'extension' => 'com_content.article'
						];
						$this->tasks[] = $this->db->insertObject('#__workflow_associations', $association);
					}

					$query->clear()
						->select('id, value')
						->from($this->db->quoteName('#__falang_content'))
						->where($this->db->quoteName('reference_table') . ' = ' . $this->db->quote('modules'))
						->where($this->db->quoteName('reference_id') . ' = :referenceId')
						->where($this->db->quoteName('reference_field') . ' = ' . $this->db->quote('params'))
						->bind(':referenceId', $campaignLoggedModule->id, ParameterType::INTEGER);
					$this->db->setQuery($query);
					$falangTranslation = $this->db->loadObject();
					if(!empty($falangTranslation) && !empty($falangTranslation->id))
					{
						$falangParams = json_decode($falangTranslation->value ?? '');
						if(!is_object($falangParams))
						{
							$falangParams = new \stdClass();
						}
						$falangParams->mod_em_campaign_article_logged = $articleId;

						$update = (object) [
							'id' => $falangTranslation->id,
							'value' => json_encode($falangParams),
						];
						$this->tasks[] = $this->db->updateObject('#__falang_content', $update, 'id');
					}

					$params->mod_em_campaign_article_logged = $articleId;
					$update = (object) [
						'id'     => (int) $campaignLoggedModule->id,
						'params' => json_encode($params),
					];
					$this->tasks[] = $this->db->updateObject('#__modules', $update, 'id');
				}
			}
		}
	}

	/**
	 * Create an empty com_content article using Joomla's ArticleModel so the
	 * asset_id is built through the standard asset system. Idempotent by alias.
	 */
	private function createEmptyArticle(QueryInterface $query, string $alias, string $title, string $content = ''): int
	{
		// Reuse existing article if it already exists (script may re-run)
		$query->clear()
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__content'))
			->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias));
		$this->db->setQuery($query);
		$existingId = (int) $this->db->loadResult();

		if(!empty($existingId))
		{
			return $existingId;
		}

		// A category is mandatory for content; pick the first com_content category
		$query->clear()
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__categories'))
			->where($this->db->quoteName('extension') . ' = ' . $this->db->quote('com_content'))
			->where($this->db->quoteName('level') . ' > 0')
			->order($this->db->quoteName('lft') . ' ASC');
		$this->db->setQuery($query, 0, 1);
		$catid = (int) $this->db->loadResult();

		// Use the Table (not the admin Model) so this works from CLI: the Model
		// depends on the CMS application (session, input, user identity) which is
		// absent under the CLI application and causes a fatal error.
		// Table::store() still builds the asset_id through the asset system.
		$articleTable = $this->app->bootComponent('com_content')->getMVCFactory()
			->createTable('Article', 'Administrator');

		$data = [
			'id'        => 0,
			'title'     => $title,
			'alias'     => $alias,
			'introtext' => $content,
			'fulltext'  => '',
			'catid'     => $catid,
			'state'     => 1,
			'access'    => 1,
			'language'  => '*',
		];

		if(!$articleTable->bind($data) || !$articleTable->check() || !$articleTable->store())
		{
			$this->tasks[] = false;

			return 0;
		}

		return (int) $articleTable->id;
	}
}
