<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Tchooz\Enums\AccessLevelEnum;

class Release2_22_2Installer extends ReleaseInstaller
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
			$longDescriptionProgrammeColumn = \EmundusHelperUpdate::addColumn('jos_emundus_setup_programmes', 'long_description', 'TEXT');
			$this->tasks[] = $longDescriptionProgrammeColumn['status'];
			$mustOpenRightsProgrammeColumn = \EmundusHelperUpdate::addColumn('jos_emundus_setup_programmes', 'must_open_rights', 'INT', 11);
			$this->tasks[] = $mustOpenRightsProgrammeColumn['status'];

			$this->campaignsLoggedArticle($query);

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function campaignsLoggedArticle(QueryInterface $query): void
	{
		$registeredAccessLevel = AccessLevelEnum::REGISTERED->value;
		$query->clear()
			->select('id, params')
			->from($this->db->quoteName('#__modules'))
			->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundus_campaign'))
			->where('JSON_EXTRACT('.$this->db->quoteName('params').', '.$this->db->quote('$.mod_em_campaign_layout') . ') = ' . $this->db->quote('default_tchooz'))
			->where($this->db->quoteName('access') . ' = :registeredAccessLevel')
			->bind(':registeredAccessLevel', $registeredAccessLevel, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$campaignLoggedModule = $this->db->loadObject();
		
		if(!empty($campaignLoggedModule) && !empty($campaignLoggedModule->id))
		{
			$params = json_decode($campaignLoggedModule->params);
			if(empty($params->mod_em_campaign_article_logged))
			{
				$articleId = $this->createEmptyArticle('campaigns-logged', 'Campaigns logged', $params->mod_em_campaign_intro, $query);

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
						$falangParams = json_decode($falangTranslation->value);
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
	private function createEmptyArticle(string $alias, string $title, string $content = '', QueryInterface $query): int
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
