<?php
/**
 * @package     Tchooz\Repositories\Emails
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Emails;

use EmundusHelperAccess;
use EmundusHelperMenu;
use EmundusModelCampaign;
use EmundusModelEvaluation;
use EmundusModelForm;
use EmundusModelProfile;
use EmundusModelProgramme;
use Exception;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_tags')]
final class TagRepository
{
	use TraitTable;

	private const COLUMNS = [
		't.id',
		't.tag',
		't.description',
	];

	private DatabaseInterface $db;

	public function __construct()
	{
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
		Log::addLogger(['text_file' => 'com_emundus.repository.tags.php'], Log::ALL, ['com_emundus.repository.tags']);
	}

	/**
	 * TODO: define a TagEntity class to return
	 * @param   string  $sort
	 * @param   string  $search
	 * @param   int     $lim
	 * @param   int     $page
	 * @param   string  $formtype
	 * @param   int     $campaign_id
	 * @param   int     $step_id
	 * @param   int     $user_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getAllFabrikTags(
		string $sort = 'DESC',
		string $search = '',
		int $lim = 25,
		int $page = 0,
		string $formtype = 'all',
		int $campaign_id = 0,
		int $step_id = 0,
		int $user_id = 0
	): array
	{
		if (empty($user_id))
		{
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		if (!class_exists('EmundusModelForm'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/form.php');
		}
		$m_form = new EmundusModelForm();
		if (!class_exists('EmundusModelEvaluation'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/evaluation.php');
		}
		$m_evaluation = new EmundusModelEvaluation();
		if (!class_exists('EmundusHelperAccess'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/helpers/access.php');
		}
		if (!class_exists('EmundusHelperMenu'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/helpers/menu.php');
		}
		$h_menu = new EmundusHelperMenu();

		$tags = [
			'datas' => [],
			'count' => 0
		];

		if (empty($lim) || $lim == 'all')
		{
			$limit = '';
		}
		else
		{
			$limit = $lim;
		}

		if (empty($page) || empty($limit))
		{
			$offset = 0;
		}
		else
		{
			$offset = ($page - 1) * $limit;
		}

		if (empty($sort))
		{
			$sort = 'DESC';
		}

		$campaign_profiles = [];
		if (!empty($campaign_id))
		{
			if (!class_exists('EmundusModelProfile'))
			{
				require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
			}
			$m_profile         = new EmundusModelProfile();
			$campaign_profiles = $m_profile->getProfilesIDByCampaign([$campaign_id]);
		}

		$profile_filters = [];
		$form_filters    = [];
		if (!empty($step_id))
		{
			if (!class_exists('EmundusModelWorkflow'))
			{
				require_once(JPATH_SITE . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new \EmundusModelWorkflow();
			$step_data  = $m_workflow->getStepData($step_id);

			if (!empty($step_data))
			{
				if (!empty($step_data->profile_id))
				{
					$profile_filters = [$step_data->profile_id];
				}

				if (!empty($step_data->form_id))
				{
					$form_filters = [$step_data->form_id];
				}
			}
		}

		$available_profiles = $m_form->getAllForms('', '', '', 0, 0, $user_id);
		$available_profiles = array_keys(array_column($available_profiles['datas'], null, 'id'));

		$fl       = array();
		$menutype = array();

		foreach ($available_profiles as $profile)
		{
			// If filtering by campaign and this profile is not in the campaign profiles then skip
			if (!empty($campaign_id) && !in_array($profile, $campaign_profiles))
			{
				continue;
			}

			if (!empty($step_id) && !empty($profile_filters) && !in_array($profile, $profile_filters))
			{
				continue;
			}

			$menu_list = $h_menu->buildMenuQuery($profile, null, true, $user_id);
			foreach ($menu_list as $m)
			{
				$fl[]               = $m->table_id;
				$menutype[$profile] = $m->menutype;
			}
		}

		if (empty($fl))
		{
			return array();
		}

		$query = $this->db->createQuery();

		try
		{
			$elts = array();

			$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('output', ['defaultgroup' => 'com_emundus']);

			if ($formtype == 'all' || $formtype == 'applicant')
			{
				$cache_key = 'fabrik_tags_applicant';
				if ($cache->contains($cache_key))
				{
					$cache_elts = $cache->get($cache_key);
					if (!empty($cache_elts))
					{
						// Filter cached elements based on current filters (available profiles, form filters)
						foreach ($cache_elts as $value)
						{
							if (is_array($available_profiles) && count($available_profiles) > 0 && !in_array($value->profil_id, $available_profiles))
							{
								continue;
							}

							if (!empty($step_id) && !empty($form_filters) && !in_array($value->form_id, $form_filters))
							{
								continue;
							}

							$elts[] = $value;
						}
					}
				}

				if (empty($elts))
				{
					$columns = [
						'distinct(concat_ws("___",l.db_table_name,e.name)) as fabrik_element',
						'e.id',
						'e.name AS element_name',
						'e.label AS element_label',
						'e.plugin AS element_plugin',
						'e.default as element_default',
						'g.id AS group_id',
						'g.label AS group_label',
						'e.params AS element_attribs',
						'INSTR(g.params,\'"repeat_group_button":"1"\') AS group_repeated',
						'l.id AS table_id',
						'l.db_table_name AS table_name',
						'f.id AS form_id',
						'f.label AS form_label',
						'l.created_by_alias',
						'fj.table_join',
						'm.id as menu_id',
						'm.title',
						'p.label',
						'p.id as profil_id'
					];
					$query->select($columns)
						->from($this->db->quoteName('#__fabrik_elements', 'e'))
						->innerJoin($this->db->quoteName('#__fabrik_groups', 'g') . ' ON ' . $this->db->quoteName('e.group_id') . ' = ' . $this->db->quoteName('g.id'))
						->innerJoin($this->db->quoteName('#__fabrik_formgroup', 'fg') . ' ON ' . $this->db->quoteName('g.id') . ' = ' . $this->db->quoteName('fg.group_id'))
						->innerJoin($this->db->quoteName('#__fabrik_lists', 'l') . ' ON ' . $this->db->quoteName('l.form_id') . ' = ' . $this->db->quoteName('fg.form_id'))
						->innerJoin($this->db->quoteName('#__fabrik_forms', 'f') . ' ON ' . $this->db->quoteName('l.form_id') . ' = ' . $this->db->quoteName('f.id'))
						->leftJoin($this->db->quoteName('#__fabrik_joins', 'fj') . ' ON (' . $this->db->quoteName('l.id') . ' = ' . $this->db->quoteName('fj.list_id') . ' AND (' . $this->db->quoteName('g.id') . ' = ' . $this->db->quoteName('fj.group_id') . ' OR ' . $this->db->quoteName('e.id') . ' = ' . $this->db->quoteName('fj.element_id') . '))')
						->innerJoin($this->db->quoteName('#__menu', 'm') . ' ON ' . $this->db->quoteName('f.id') . ' = SUBSTRING_INDEX(SUBSTRING(' . $this->db->quoteName('m.link') . ', LOCATE("formid=",' . $this->db->quoteName('m.link') . ')+7, 4), "&", 1)')
						->innerJoin($this->db->quoteName('#__emundus_setup_profiles', 'p') . ' ON ' . $this->db->quoteName('p.menutype') . ' = ' . $this->db->quoteName('m.menutype'));
					$query->where($this->db->quoteName('l.published') . ' = 1')
						->where($this->db->quoteName('g.published') . ' = 1');
					if (is_array($available_profiles) && count($available_profiles) > 0)
					{
						$query->where($this->db->quoteName('p.id') . ' IN (' . implode(',', $available_profiles) . ')');
					}
					$query->where($this->db->quoteName('l.id') . ' IN ( ' . implode(',', $fl) . ' )')
						->where($this->db->quoteName('e.published') . ' = 1')
						->where($this->db->quoteName('e.hidden') . ' = 0')
						->where($this->db->quoteName('e.label') . ' != ' . $this->db->quote(' '))
						->where($this->db->quoteName('e.label') . ' != ' . $this->db->quote(''))
						->where($this->db->quoteName('m.menutype') . ' IN ( "' . implode('","', $menutype) . '" )')
						->where($this->db->quoteName('m.published') . ' = 1')
						->where($this->db->quoteName('e.plugin') . ' != ' . $this->db->quote('display'));
					if (!empty($step_id) && !empty($form_filters))
					{
						$query->where($this->db->quoteName('f.id') . ' IN (' . implode(',', $form_filters) . ')');
					}

					if (is_array($available_profiles) && count($available_profiles) > 0)
					{
						$query->order('FIELD(p.id,' . implode(',', $available_profiles) . '), m.lft, m.id, fg.ordering, e.ordering');
					}
					else
					{
						$query->order('FIELD(p.id, m.lft, m.id, fg.ordering, e.ordering');
					}

					$this->db->setQuery($query);
					$elements = $this->db->loadObjectList('id');

					$allowed_groups = EmundusHelperAccess::getUserFabrikGroups($user_id);
					if (count($elements) > 0)
					{
						foreach ($elements as $key => $value)
						{
							if ($allowed_groups !== true && is_array($allowed_groups) && !in_array($value->group_id, $allowed_groups))
							{
								continue;
							}

							$plugin       = ElementPluginEnum::tryFrom($value->element_plugin);
							$plugin_label = !empty($plugin) ? Text::_($plugin->getLabel()) : $value->element_plugin;
							if ($plugin->value === ElementPluginEnum::PANEL->value)
							{
								// Display 25 first characters of default column
								$default_label = strip_tags($value->element_default);
								$element_label = ' - ' . (strlen($default_label) > 25 ? substr($default_label, 0, 25) . '...' : $default_label);
							}
							else
							{
								$element_label = !empty(Text::_($value->element_label)) ? Text::_($value->element_label) : '[' . $plugin_label . ']';
							}

							$value->id            = $key;
							$value->form_label    = Text::_($value->form_label);
							$value->table_label   = Text::_($value->label);
							$value->group_label   = Text::_($value->group_label);
							$value->element_label = $element_label;
							$value->plugin_label  = $plugin_label;

							$elts[] = $value;
						}
					}

					if (!empty($elts))
					{
						$cache->store($elts, $cache_key);
					}
				}
			}

			if ($formtype == 'all' || $formtype == 'management')
			{
				$programs = [];
				if (empty($campaign_id))
				{
					if (!class_exists('EmundusModelProgramme'))
					{
						require_once(JPATH_SITE . '/components/com_emundus/models/programme.php');
					}
					$m_programme = new EmundusModelProgramme;
					$programs    = $m_programme->getUserPrograms($user_id);
				}
				else
				{
					if (!class_exists('EmundusModelCampaign'))
					{
						require_once(JPATH_SITE . '/components/com_emundus/models/campaign.php');
					}
					$m_campaign         = new EmundusModelCampaign();
					$campaign_programme = $m_campaign->getProgrammeByCampaignID($campaign_id);

					$programs[] = $campaign_programme['code'];
				}

				$management_elts = [];
				$cache_key       = 'fabrik_tags_management';
				if ($cache->contains($cache_key))
				{
					$cache_elts = $cache->get($cache_key);
					if (!empty($cache_elts))
					{
						// Filter cached elements based on current filters (programs, form filters)
						foreach ($cache_elts as $value)
						{
							if (!empty($programs) && !in_array($value->programme_code, $programs))
							{
								continue;
							}

							if (!empty($step_id) && !empty($form_filters) && !in_array($value->form_id, $form_filters))
							{
								continue;
							}

							$management_elts[] = $value;
						}
					}
				}

				if (empty($management_elts))
				{
					$m_evaluation  = new EmundusModelEvaluation();
					$eval_elements = $m_evaluation->getEvaluationElementsName(0, 0, $programs, true, $user_id);

					if (!empty($eval_elements))
					{
						foreach ($eval_elements as $value)
						{
							if (!empty($step_id) && !empty($form_filters) && !in_array($value->form_id, $form_filters))
							{
								continue;
							}

							$plugin       = ElementPluginEnum::tryFrom($value->element_plugin);
							$plugin_label = !empty($plugin) ? Text::_($plugin->getLabel()) : $value->element_plugin;
							if ($plugin->value === ElementPluginEnum::PANEL->value)
							{
								// Display 25 first characters of default column
								$default_label = strip_tags($value->element_default);
								$element_label = ' - ' . (strlen($default_label) > 25 ? substr($default_label, 0, 25) . '...' : $default_label);
							}
							else
							{
								$element_label = !empty(Text::_($value->element_label)) ? Text::_($value->element_label) : '[' . $plugin_label . ']';
							}

							$value->form_label    = Text::_($value->form_label);
							$value->table_label   = '';
							$value->group_label   = Text::_($value->group_label);
							$value->element_label = $element_label;
							$value->plugin_label  = $plugin_label;
							$management_elts[]    = $value;
						}
					}

					if (!empty($management_elts))
					{
						$cache->store($management_elts, $cache_key);
					}
				}

				$elts = array_merge($elts, $management_elts);
			}

			$tags['count'] = count($elts);
			if (!empty($search))
			{
				$search        = $this->db->quote('%' . $this->db->escape($search, true) . '%');
				$elts          = array_filter(
					$elts,
					function ($item) use ($search) {
						return (stripos($item->element_label, trim($search, "'%")) !== false)
							|| (stripos($item->form_label, trim($search, "'%")) !== false)
							|| (stripos($item->table_label, trim($search, "'%")) !== false)
							|| (stripos($item->group_label, trim($search, "'%")) !== false);
					}
				);
				$tags['count'] = count($elts);
			}
			if (!empty($limit))
			{
				$elts = array_slice($elts, $offset, $limit);
			}

			$tags['datas'] = $elts;
		}
		catch (Exception $e)
		{
			Log::add('TagRepository::getAllFabrikTags Exception: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.tags');
			$tags = [];
		}

		return $tags;
	}

	public function getAllOtherTags(
		string $sort = 'DESC',
		string $search = '',
		int $lim = 25,
		int $page = 0
	): array
	{
		$tags = [
			'datas' => [],
			'count' => 0
		];

		if (empty($lim) || $lim == 'all')
		{
			$limit = '';
		}
		else
		{
			$limit = $lim;
		}

		if (empty($page) || empty($limit))
		{
			$offset = 0;
		}
		else
		{
			$offset = ($page - 1) * $limit;
		}

		if (empty($sort))
		{
			$sort = 'DESC';
		}

		try
		{
			$query = $this->db->getQuery(true);

			$query->select(self::COLUMNS)
				->from($this->db->quoteName($this->getTableName(self::class), 't'))
				->where($this->db->quoteName('t.published') . ' = 1');
			if (!empty($search))
			{
				$search = $this->db->quote('%' . $this->db->escape($search, true) . '%');
				$query->where($this->db->quoteName('t.tag') . ' LIKE ' . $search)
					->orWhere($this->db->quoteName('t.description') . ' LIKE ' . $search);
			}

			$query->order($this->db->quoteName('t.id') . ' ' . $sort);
			$this->db->setQuery($query);
			$tags['count'] = sizeof($this->db->loadObjectList());

			$this->db->setQuery($query, $offset, $limit);
			$tags['datas'] = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('TagRepository::getAllOtherTags Exception: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.tags');
			$tags = [];
		}

		return $tags;
	}
}