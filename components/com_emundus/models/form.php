<?php
/**
 * Messages model used for the new message dialog.
 *
 * @package    Joomla
 * @subpackage eMundus
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');
jimport('joomla.database.table');

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseDriver;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Response;
use Tchooz\Traits\TraitResponse;

class EmundusModelForm extends ListModel
{
	use TraitResponse;

	private CMSApplicationInterface $app;

	private DatabaseDriver $db;

	public function __construct(array $config = [])
	{
		parent::__construct($config);

		$this->app = Factory::getApplication();
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		Log::addLogger(['text_file' => 'com_emundus.form.php'], Log::ALL, array('com_emundus.form'));
	}

	/**
	 * @param   String  $filter
	 * @param   String  $sort
	 * @param   String  $recherche
	 * @param   Int     $lim
	 * @param   Int     $page
	 *
	 * @return array|stdClass
	 */
	function getAllForms(string $filter = '', string $sort = '', string $recherche = '', int $lim = 0, int $page = 0, int $user_id = 0, string $order_by = ''): array
	{
		$data = ['datas' => [], 'count' => 0];
		require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');


		$query    = $this->db->getQuery(true);

		// Build filter / limit / pagination part of the query
		if (empty($lim) || $lim == 'all') {
			$limit = '';
		}
		else {
			$limit = $lim;
		}

		if (empty($page) || empty($limit)) {
			$offset = 0;
		}
		else {
			$offset = ($page - 1) * $limit;
		}

		if (empty($sort)) {
			$sort = 'DESC';
		}

		if ($filter == 'Unpublish') {
			$filterDate = $this->db->quoteName('sp.status') . ' = 0';
		}
		else {
			$filterDate = $this->db->quoteName('sp.status') . ' = 1';
		}

		$filterId      = $this->db->quoteName('sp.published') . ' = 1';
		$fullRecherche = empty($recherche) ? 1 : $this->db->quoteName('sp.label') . ' LIKE ' . $this->db->quote('%' . $recherche . '%');

		$m_user           = new EmundusModelUsers();
		$allowed_profiles = $this->getAllFormsPublished($user_id, 'form_label', SORT_ASC, [0,1]);
		$allowed_profile_ids = array_map(function ($profile) {
			return $profile->id;
		}, $allowed_profiles);

		// Now we need to put the query together and get the profiles
		$query->clear()
			->select(['sp.*', 'sp.label AS form_label'])
			->from($this->db->quoteName('#__emundus_setup_profiles', 'sp'))
			->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.profile_id') . ' = ' . $this->db->quoteName('sp.id'))
			->where($filterDate)
			->andWhere($fullRecherche)
			->andWhere($filterId)
			->andWhere($this->db->quoteName('sp.id') . ' IN (' . implode(',', $this->db->quote($allowed_profile_ids)) . ')')
			->andWhere($this->db->quoteName('sp.label') . ' != ' . $this->db->quote('noprofile'))
			->group($this->db->quoteName('sp.id'));

		$valid_columns = ['id', 'label'];
		if(!empty($order_by) && in_array($order_by, $valid_columns))
		{
			// Check that order_by is a valid column
			$query->group($this->db->quoteName('sp.' . $order_by))
				->order($this->db->quoteName('sp.' . $order_by) . ' ' . $sort);
		}
		else {
			$query->order('sp.id ' . $sort);
		}

		try {
			$this->db->setQuery($query);
			$data['count'] = sizeof($this->db->loadObjectList());
			$this->db->setQuery($query, $offset, $limit);
			$data['datas'] = $this->db->loadObjectList();

			if (!empty($data['datas'])) {
				$languages      = LanguageHelper::getLanguages();
				if (!empty($languages)) {
					foreach ($data['datas'] as $key => $form) {
						$label = [];
						foreach ($languages as $language) {
							$label[$language->sef] = LanguageFactory::getTranslation($form->label, $language->lang_code) ?: $form->label;
						}
						$data['datas'][$key]->label = $label;
					}
				}
			}
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Cannot getting the list of forms : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $data;
	}

	/**
	 * TODO: Add filters / recherche etc./.. At the moment, it's not working
	 *
	 * @param   string  $filter
	 * @param   string  $sort
	 * @param   string  $recherche
	 * @param   int     $lim
	 * @param   int     $page
	 * @param   int     $user_id
	 * @param   string  $order_by
	 *
	 * @return array
	 */
	function getAllGrilleEval(string $filter = '', string $sort = '', string $recherche = '', int $lim = 0, int $page = 0, int $user_id = 0, string $order_by = ''): array
	{
		$data     = ['datas' => [], 'count' => 0];

		$query    = $this->db->getQuery(true);

		if (empty($user_id)) {
			$user_id = $this->app->getIdentity()->id;
		}

		try {
			// We need to get the list of fabrik forms that are linked to the jos_emundus_evaluations table
			// we must only keep forms that current user has access to
			$query->clear()
				->select([$this->db->quoteName('ff.id'), $this->db->quoteName('ff.label'), '"grilleEval" AS type, ff.published'])
				->from($this->db->quoteName('#__fabrik_forms', 'ff'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
				->where($this->db->quoteName('fl.db_table_name') . ' LIKE ' . $this->db->quote('jos_emundus_evaluations_%'));

			if ($filter === 'Unpublish') {
				$query->andWhere($this->db->quoteName('ff.published') . ' = 0');
			}
			else {
				$query->andWhere($this->db->quoteName('ff.published') . ' = 1');
			}

			$query->andWhere($this->db->quoteName('fl.published') . ' = 1');

			$this->db->setQuery($query);
			$evaluation_forms = $this->db->loadObjectList();

			if (!empty($evaluation_forms)) {
				if (!class_exists('EmundusModelProgramme')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/programme.php');
				}
				$m_programs = new EmundusModelProgramme();
				$user_programs = $m_programs->getUserProgramIds($user_id);

				if (!empty($user_programs)) {
					$query->clear()
						->select('DISTINCT jesws.form_id')
						->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'jesws'))
						->leftJoin($this->db->quoteName('#__emundus_setup_workflows_programs', 'jeswp') . ' ON ' . $this->db->quoteName('jeswp.workflow_id') . ' = ' . $this->db->quoteName('jesws.workflow_id'))
						->where('jeswp.program_id IN (' . implode(',', $this->db->quote($user_programs)) . ')')
						->andWhere($this->db->quoteName('jesws.form_id') . ' IS NOT NULL');

					$steps_form_ids = $this->db->setQuery($query)->loadColumn();
				} else {
					$steps_form_ids = [];
				}

				$evaluation_form_ids = array_map(function ($form) {
					return $form->id;
				}, $evaluation_forms);

				$query->clear()
					->select('ff.id')
					->from($this->db->quoteName('#__fabrik_forms', 'ff'))
					->where('ff.id IN (' . implode(',', $evaluation_form_ids) . ')');


				// if user as Admin Access, he can see all forms
				if (!EmundusHelperAccess::asAdministratorAccessLevel($user_id))
				{
					$query->andWhere($this->db->quoteName('ff.created_by') . ' = ' . $user_id .
						(!empty($steps_form_ids) ? ' OR ff.id IN (' . implode(',', $steps_form_ids) . ')' : ''));
				}

				$this->db->setQuery($query);
				$evaluation_forms_user_can_access_to = $this->db->loadColumn();

				$evaluation_forms = array_filter($evaluation_forms, function ($form) use ($evaluation_forms_user_can_access_to) {
					return in_array($form->id, $evaluation_forms_user_can_access_to);
				});
				$evaluation_forms = array_values($evaluation_forms);
			}

			if (!empty($evaluation_forms)) {
				$languages      = LanguageHelper::getLanguages();
				$current_language = $this->app->getLanguage()->getTag();
				$current_language = substr($current_language, 0, 2);

				foreach ($evaluation_forms as $evaluation_form) {
					$label = [];
					foreach ($languages as $language) {
						$label[$language->sef] = LanguageFactory::getTranslation($evaluation_form->label, $language->lang_code) ?: $evaluation_form->label;
					}
					$evaluation_form->label = $label;
					$evaluation_form->programs_count = count($this->getProgramsByForm($evaluation_form->id));
				}

				if($order_by == 'label')
				{
					$sort = $sort === 'ASC' ? SORT_ASC : SORT_DESC;
					$sort_labels = array_column($evaluation_forms, 'label', 'id');
					$sort_labels_current_language = array_map(function($labels) use ($current_language) {
						return $labels[$current_language] ?? reset($labels);
					}, $sort_labels);
					array_multisort($sort_labels_current_language, $sort, $evaluation_forms);
				}

				if(!empty($recherche))
				{
					$evaluation_forms = array_filter($evaluation_forms, function($form) use ($recherche, $current_language) {
						$label = $form->label[$current_language] ?? reset($form->label);
						return stripos($label, $recherche) !== false;
					});
					$evaluation_forms = array_values($evaluation_forms);
				}
			}

			$data['datas'] = $evaluation_forms;
			$data['count'] = sizeof($evaluation_forms);
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Cannot getting the list of forms : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $data;
	}

	/**
	 * @param   int     $user_id
	 * @param   string  $sort
	 * @param   int     $sort_order
	 *
	 * @return array
	 */
	function getAllFormsPublished(int $user_id = 0, string $sort = 'form_label', int $sort_order = SORT_ASC, array $status = [1]): array
	{
		$profiles = [];

		if (empty($user_id))
		{
			$user_id = $this->app->getIdentity()->id;
		}

		$query = $this->db->getQuery(true);

		if (!class_exists('EmundusModelUsers'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/users.php');
		}
		$m_user           = new EmundusModelUsers();
		$allowed_programs = $m_user->getUserGroupsProgramme($user_id);

		try {
			$profiles_not_associated = $this->getUnassociatedProfiles($status);
			$profiles_associated = $this->getAssociatedProfiles($allowed_programs, $status);
			$profiles = array_merge($profiles_not_associated, $profiles_associated);

			// Sort profiles by sort argument
			if (!empty($profiles)) {
				$sort = array_column($profiles, $sort);
				array_multisort($sort, $sort_order, $profiles);
			}
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Cannot getting the published forms : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			$profiles = [];
		}

		return $profiles;
	}

	/**
	 * Profiles that are not associated to a campaign neither to a workflow step
	 *
	 * @return array
	 */
	private function getUnassociatedProfiles(array $status = [1]): array
	{
		$profiles = [];

		try {
			$query = $this->db->createQuery();

			$query->select('sp.*, sp.label AS form_label')
				->from($this->db->quoteName('#__emundus_setup_profiles', 'sp'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.profile_id') . ' = ' . $this->db->quoteName('sp.id'))
				->where($this->db->quoteName('esc.profile_id') . ' IS NULL')
				->andWhere($this->db->quoteName('sp.status') . ' IN (' . implode(',', $status) . ')')
				->andWhere($this->db->quoteName('sp.published') . ' = 1')
				->group($this->db->quoteName('sp.id'));

			$potentially_non_associated_profiles = $this->db->setQuery($query)->loadObjectList();
			if (!empty($potentially_non_associated_profiles)) {
				$potentially_non_associated_profile_ids = array_map(function ($profile) {
					return $profile->id;
				}, $potentially_non_associated_profiles);

				$query->clear()
					->select('DISTINCT jesws.profile_id')
					->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'jesws'))
					->leftJoin($this->db->quoteName('#__emundus_setup_workflows', 'jesw') . ' ON ' . $this->db->quoteName('jesw.id') . ' = ' . $this->db->quoteName('jesws.workflow_id'))
					->where($this->db->quoteName('jesws.profile_id') . ' IN (' . implode(',', $potentially_non_associated_profile_ids) . ')')
					->andWhere($this->db->quoteName('jesw.published') . ' = 1');

				$associated_profile_ids = $this->db->setQuery($query)->loadColumn();

				$profiles = array_filter($potentially_non_associated_profiles, function ($profile) use ($associated_profile_ids) {
					return !in_array($profile->id, $associated_profile_ids);
				});
				$profiles = array_values($profiles);
			}
		} catch (Exception $e) {
			Log::add('Cannot get the unassociated profiles : ' . $e->getMessage(), Log::ERROR, 'com_emundus.form');
		}


		return $profiles;
	}

	/**
	 * @param   array  $program_codes
	 *
	 * @return array
	 */
	private function getAssociatedProfiles(array $program_codes = [], array $status = [1]): array
	{
		$profiles = [];

		if (!empty($program_codes)) {
			try {
				$query = $this->db->createQuery();
				$query->select(['sp.*', 'sp.label AS form_label'])
					->from($this->db->quoteName('#__emundus_setup_profiles', 'sp'))
					->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.profile_id') . ' = ' . $this->db->quoteName('sp.id'))
					->where($this->db->quoteName('esc.training') . ' IN (' . implode(',', $this->db->quote($program_codes)) . ')')
					->andWhere($this->db->quoteName('sp.status') . ' IN (' . implode(',', $status) . ')')
					->andWhere($this->db->quoteName('sp.published') . ' = 1')
					->group($this->db->quoteName('sp.id'));
				$this->db->setQuery($query);
				$profiles_associated_through_campaign = $this->db->loadObjectList();

				$profiles_associated_through_workflow = [];

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__emundus_setup_programmes'))
					->where($this->db->quoteName('code') . ' IN (' . implode(',', $this->db->quote($program_codes)) . ')');
				$program_ids = $this->db->setQuery($query)->loadColumn();

				if (!class_exists('EmundusModelWorkflow')) {
					require_once JPATH_ROOT . '/components/com_emundus/models/workflow.php';
				}
				$m_workflow = new EmundusModelWorkflow();
				$workflows = $m_workflow->getWorkflows([], 0, 0, $program_ids);
				$workflow_ids = array_map(function ($workflow) {
					return $workflow->id;
				}, $workflows);

				if(!empty($workflow_ids))
				{
					$query->clear()
						->select(['sp.*', 'sp.label AS form_label'])
						->from($this->db->quoteName('#__emundus_setup_profiles', 'sp'))
						->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps') . ' AS jesws ON jesws.profile_id = sp.id')
						->where($this->db->quoteName('jesws.workflow_id') . ' IN (' . implode(',', $workflow_ids) . ')');
					$profiles_associated_through_workflow = $this->db->setQuery($query)->loadObjectList();
				}

				$profiles = array_merge($profiles_associated_through_campaign, $profiles_associated_through_workflow);

				// Remove duplicate profiles
				$profiles = array_map("unserialize", array_unique(array_map("serialize", $profiles)));
			} catch (Exception $e) {
				Log::add('Cannot get associated profiles from program codes : ' . $e->getMessage(), Log::ERROR, 'com_emundus.form');
			}
		}

		return $profiles;
	}

	public function deleteForm($data)
	{

		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'formbuilder.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'falang.php');

		$formbuilder = new EmundusModelFormbuilder;
		$falang      = new EmundusModelFalang;

		$eMConfig = JComponentHelper::getParams('com_emundus');
		$modules  = $eMConfig->get('form_builder_page_creation_modules', [93, 102, 103, 104, 168, 170]);

		if (count($data) > 0) {
			$sp_conditions = array(
				$this->db->quoteName('sp.id') . ' IN (' . implode(", ", array_values($data)) . ')'
			);

			$query->select([
				'sp.id AS spid',
				'mt.id AS mtid',
				'me.id AS meid'
			])
				->from($this->db->quoteName('#__emundus_setup_profiles', 'sp'))
				->leftJoin($this->db->quoteName('#__menu_types', 'mt') . ' ON ' . $this->db->quoteName('mt.menutype') . ' = ' . $this->db->quoteName('sp.menutype'))
				->leftJoin($this->db->quoteName('#__menu', 'me') . ' ON ' . $this->db->quoteName('me.menutype') . ' = ' . $this->db->quoteName('mt.menutype'))
				->where($sp_conditions);

			try {
				$this->db->setQuery($query);
				$results   = $this->db->loadObjectList();
				$spids_arr = array();
				$mtids_arr = array();
				$meids_arr = array();
				$flids_arr = array();
				foreach (array_values($results) as $result) {
					if (!in_array($result->spid, $spids_arr)) {
						$spids_arr[] = $result->spid;
					}
					if (!in_array($result->mtid, $mtids_arr)) {
						$mtids_arr[] = $result->mtid;
					}
					if (!in_array($result->meid, $meids_arr)) {
						$meids_arr[] = $result->meid;
					}
				}

				$query->clear()
					->select('form_id')
					->from($this->db->quoteName('#__emundus_setup_formlist'))
					->where($this->db->quoteName('profile_id') . ' IN (' . implode(", ", array_values($data)) . ')');
				$this->db->setQuery($query);
				$forms = $this->db->loadObjectList();

				foreach (array_values($forms) as $form) {
					if (!in_array($form->form_id, $flids_arr)) {
						$flids_arr[] = $form->form_id;
					}
				}

				$fl_conditions = array($this->db->quoteName('fl.id') . ' IN (' . implode(", ", array_values($flids_arr)) . ')');

				$query->clear();
				$query->select([
					'ff.intro AS ffintro',
					'ff.id AS ffid',
					'fl.db_table_name AS dbtable',
					'ffg.id AS ffgid',
					'fg.id AS fgid',
					'fe.id AS feid'
				])
					->from($this->db->quoteName('#__fabrik_lists', 'fl'))
					->leftJoin($this->db->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
					->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.form_id') . ' = ' . $this->db->quoteName('ff.id'))
					->leftJoin($this->db->quoteName('#__fabrik_groups', 'fg') . ' ON ' . $this->db->quoteName('fg.id') . ' = ' . $this->db->quoteName('ffg.group_id'))
					->leftJoin($this->db->quoteName('#__fabrik_elements', 'fe') . ' ON ' . $this->db->quoteName('fe.group_id') . ' = ' . $this->db->quoteName('fg.id'))
					->where($fl_conditions);

				$this->db->setQuery($query);
				$results      = $this->db->loadObjectList();
				$ffids_arr    = array();
				$dbtables_arr = array();
				$ffgids_arr   = array();
				$fgids_arr    = array();
				$feids_arr    = array();
				$ffintros_arr = array();
				foreach (array_values($results) as $result) {
					if (!in_array($result->ffid, $ffids_arr)) {
						$ffids_arr[] = $result->ffid;
					}
					if (!in_array($result->dbtable, $dbtables_arr)) {
						$dbtables_arr[] = $result->dbtable;
					}
					if (!in_array($result->ffgid, $ffgids_arr)) {
						$ffgids_arr[] = $result->ffgid;
					}
					if (!in_array($result->fgid, $fgids_arr)) {
						$fgids_arr[] = $result->fgid;
					}
					if (!in_array($result->feid, $feids_arr) && $result->feid != null) {
						$feids_arr[] = $result->feid;
					}
					if (!in_array($result->ffintro, $ffintros_arr)) {
						$ffintros_arr[] = $result->ffintro;
					}
				}

				try {
					// DISSOCIATE CAMPAIGN WITH THIS PROFILE ID
					$conditions = array($this->db->quoteName('profile_id') . ' IN (' . implode(", ", array_values($spids_arr)) . ')');

					$query->clear()
						->update($this->db->quoteName('#__emundus_setup_campaigns'))
						->set($this->db->quoteName('profile_id') . ' = NULL')
						->where($conditions);

					$this->db->setQuery($query);
					$this->db->execute();
					//

					// DELETE SETUP PROFILE
					$conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($spids_arr)) . ')');

					$query->clear()
						->delete($this->db->quoteName('#__emundus_setup_profiles'))
						->where($conditions);

					$this->db->setQuery($query);
					$this->db->execute();

					// DELETE MENU TYPE
					$conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($mtids_arr)) . ')');

					$query->clear()
						->delete($this->db->quoteName('#__menu_types'))
						->where($conditions);

					$this->db->setQuery($query);
					$this->db->execute();

					// DELETE MENUS
					$conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($meids_arr)) . ')');

					$query->clear()
						->select('*')
						->from($this->db->quoteName('#__menu'))
						->where($conditions);
					$this->db->setQuery($query);
					$menus = $this->db->loadObjectList();

					foreach ($menus as $menu) {
						$falang->deleteFalang($menu->id, 'menu', 'title');

						foreach ($modules as $module) {
							$query
								->clear()
								->delete($this->db->quoteName('#__modules_menu'))
								->where($this->db->quoteName('moduleid') . ' = ' . $this->db->quote($module))
								->andWhere($this->db->quoteName('menuid') . ' = ' . $this->db->quote($menu->id));
							$this->db->setQuery($query);
							$this->db->execute();
						}
					}

					$query->clear()
						->delete($this->db->quoteName('#__menu'))
						->where($conditions);

					$this->db->setQuery($query);
					$this->db->execute();

					// DELETE FABRIK FORMS
					$conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($ffids_arr)) . ')');

					$query->clear()
						->select(['label AS label', 'intro AS intro'])
						->from($this->db->quoteName('#__fabrik_forms'))
						->where($conditions);
					$this->db->setQuery($query);
					$forms_texts = $this->db->loadObjectList();

					foreach ($forms_texts as $form_text) {
						$formbuilder->deleteTranslation($form_text->intro);
						$formbuilder->deleteTranslation($form_text->label);
					}

					$query->clear()
						->delete($this->db->quoteName('#__fabrik_forms'))
						->where($conditions);

					$this->db->setQuery($query);
					$this->db->execute();

					// DELETE FABRIK LISTS
					foreach ($dbtables_arr as $dbtablearr) {
						$query = "DROP TABLE " . $dbtablearr;
						$this->db->setQuery($query);
						$this->db->execute();
					}

					$query = $this->db->getQuery(true);

					$conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($flids_arr)) . ')');

					$query->delete($this->db->quoteName('#__fabrik_lists'))
						->where($conditions);

					$this->db->setQuery($query);
					$this->db->execute();

					// DELETE FORMLIST
					$conditions = array($this->db->quoteName('profile_id') . ' IN (' . implode(", ", array_values($data)) . ')');

					$query->clear()
						->delete($this->db->quoteName('#__emundus_setup_formlist'))
						->where($conditions);

					$this->db->setQuery($query);
					$this->db->execute();

					// DELETE FABRIK FORM GROUP
					$conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($ffgids_arr)) . ')');

					$query->clear()
						->delete($this->db->quoteName('#__fabrik_formgroup'))
						->where($conditions);

					$this->db->setQuery($query);
					$this->db->execute();

					// DELETE FABRIK GROUP
					$conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($fgids_arr)) . ')');

					$query->clear()
						->select(['label AS label'])
						->from($this->db->quoteName('#__fabrik_groups'))
						->where($conditions);
					$this->db->setQuery($query);
					$groups_texts = $this->db->loadObjectList();

					foreach ($groups_texts as $group_text) {
						$formbuilder->deleteTranslation($group_text->label);
					}

					$query->clear()
						->delete($this->db->quoteName('#__fabrik_groups'))
						->where($conditions);

					$this->db->setQuery($query);
					$this->db->execute();

					// DELETE FABRIK ELEMENTS
					$conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($feids_arr)) . ')');

					$query->clear()
						->select(['label AS label'])
						->from($this->db->quoteName('#__fabrik_elements'))
						->where($conditions);
					$this->db->setQuery($query);
					$elts_texts = $this->db->loadObjectList();

					foreach ($elts_texts as $elt_text) {
						$formbuilder->deleteTranslation($elt_text->label);
					}

					$query->clear()
						->delete($this->db->quoteName('#__fabrik_elements'))
						->where($conditions);

					$this->db->setQuery($query);

					return $this->db->execute();

				}
				catch (Exception $e) {
					Log::add('component/com_emundus/models/form | Error when try to delete forms : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

					return false;
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error when try to delete forms : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

				return false;
			}
		}
		else {
			return false;
		}
	}


	public function unpublishForm($data)
	{
		$response = [
			'status' => false,
			'msg'    => ''
		];


		$query = $this->db->getQuery(true);

		if (!empty($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = htmlspecialchars($data[$key]);

				$campaigns = $this->getCampaignsByProfile($val);

				if (!empty($campaigns)) {
					$response['msg'] = '<div class="em-flex-column"><p><strong>' . Text::_('COM_EMUNDUS_FORM_UNPUBLISH_BLOCKED_BY_CAMPAIGN_LINK') . '</strong></p>';

					$response['msg'] .= '<ul>';
					foreach ($campaigns as $campaign) {
						$response['msg'] .= '<li><a href="/index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=' . $campaign['id'] . '" target="_blank">' . $campaign['label'] . '</a></li>';
					}
					$response['msg'] .= '</ul>';
					$response['msg'] .= '</div>';

					return $response;
				}
			}

			// we have to verify that form is not linked to any campaign

			try {
				$fields        = array($this->db->quoteName('status') . ' = 0');
				$se_conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($data)) . ')');

				$query->update($this->db->quoteName('#__emundus_setup_profiles'))
					->set($fields)
					->where($se_conditions);

				$this->db->setQuery($query);
				$response['status'] = $this->db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error when unpublish forms : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
				$response['status'] = false;
			}
		}

		return $response;
	}

	/**
	 * @param   int  $formId
	 *
	 * @return bool
	 */
	public function unpublishFabrikForm(int $formId): bool
	{
		$unpublished = false;

		if (!empty($formId))
		{
			$query = $this->db->createQuery();

			$query->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('published') . ' = 0')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($formId));

			try
			{
				$this->db->setQuery($query);
				$unpublished = $this->db->execute();
			} catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error when unpublish fabrik form : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $unpublished;
	}

	public function publishForm($data)
	{

		$query = $this->db->getQuery(true);

		if (!empty($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = htmlspecialchars($data[$key]);
			}

			try {
				$fields        = array($this->db->quoteName('status') . ' = 1');
				$se_conditions = array($this->db->quoteName('id') . ' IN (' . implode(", ", array_values($data)) . ')');

				$query->update($this->db->quoteName('#__emundus_setup_profiles'))
					->set($fields)
					->where($se_conditions);

				$this->db->setQuery($query);

				return $this->db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error when publish forms : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

				return false;
			}
		}
		else {
			return false;
		}
	}

	/**
	 * @param   int  $formId
	 *
	 * @return bool
	 */
	public function publishFabrikForm(int $formId): bool
	{
		$published = false;

		if (!empty($formId))
		{
			$query = $this->db->createQuery();

			$query->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('published') . ' = 1')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($formId));

			try
			{
				$this->db->setQuery($query);
				$published = $this->db->execute();
			} catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error when publish fabrik form : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $published;
	}

	public function duplicateForm($data, $duplicate_condition = true)
	{
		$duplicated = false;
		if (!is_array($data)) {
			$data = array($data);
		}

		if (!empty($data)) {

			$query = $this->db->getQuery(true);

			require_once(JPATH_SITE . '/components/com_emundus/models/formbuilder.php');
			$formbuilder = new EmundusModelFormbuilder();

			try {
				foreach ($data as $pid) {
					// Get profile
					$query->clear()
						->select('*')
						->from($this->db->quoteName('#__emundus_setup_profiles'))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($pid));
					$this->db->setQuery($query);
					$oldprofile = $this->db->loadObject();

					if (!empty($oldprofile)) {
						// Create a new profile
						$new_profile_label = 'Copy - ' . $oldprofile->label;

						$insert = [
							'label' => $new_profile_label,
							'published' => 1,
							'menutype' => $oldprofile->menutype,
							'acl_aro_groups' => $oldprofile->acl_aro_groups,
							'status' => $oldprofile->status
						];
						$insert = (object)$insert;
						$this->db->insertObject('#__emundus_setup_profiles', $insert);
						$newprofile = $this->db->insertid();

						if (!empty($newprofile)) {
							$newmenutype = 'menu-profile' . $newprofile;
							$new_title = 'Copy - ' . $oldprofile->label;
							if (strlen($new_title) > 48) {
								$new_title = substr($new_title, 0, 45) . '...';
							}
							$newmenutype = $this->createMenuType($newmenutype, $new_title);
							if (empty($newmenutype)) {
								Log::add('Failed to create new menu from profile ' . $newprofile, Log::WARNING, 'com_emundus.error');

								return false;
							}

							$update = [
								'id' => $newprofile,
								'menutype' => $newmenutype
							];
							$update = (object)$update;
							$this->db->updateObject('#__emundus_setup_profiles', $update, 'id');
							//

							// Duplicate heading menu
							$query->clear()
								->select('*')
								->from('#__menu')
								->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($oldprofile->menutype))
								->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'))
								->andWhere('published = 1');
							$this->db->setQuery($query);
							$heading_to_duplicate = $this->db->loadObject();

							if (empty($heading_to_duplicate) || empty($heading_to_duplicate->id)) {
								Log::add('Could not find heading menu when copying profile ' . $pid, Log::INFO, 'com_emundus.form');

								$default_heading_menu                    = new stdClass();
								$default_heading_menu->id                = 1;
								$default_heading_menu->menutype          = '';
								$default_heading_menu->title             = "PROFILE $pid - Copy";
								$default_heading_menu->alias             = '';
								$default_heading_menu->note              = '';
								$default_heading_menu->path              = '';
								$default_heading_menu->link              = '';
								$default_heading_menu->type              = 'heading';
								$default_heading_menu->published         = 1;
								$default_heading_menu->parent_id         = 1;
								$default_heading_menu->level             = 1;
								$default_heading_menu->component_id      = 0;
								$default_heading_menu->checked_out       = 0;
								$default_heading_menu->params            = '{"menu-anchor_title":"","menu-anchor_css":"","menu-anchor_rel":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1}';
								$default_heading_menu->home              = 0;
								$default_heading_menu->language          = '*';
								$default_heading_menu->client_id         = 0;
								$default_heading_menu->template_style_id = 22;
								$default_heading_menu->access            = 1;
								$default_heading_menu->browserNav        = 0;
								$heading_to_duplicate                    = $default_heading_menu;
							}

							if (!empty($heading_to_duplicate->id)) {
								$insert = [];
								foreach ($heading_to_duplicate as $key => $val) {
									if ($key != 'id' && $key != 'menutype' && $key != 'alias' && $key != 'path' && $key != 'checked_out' && $key != 'checked_out_time') {
										$insert[$key] = $val;
									}
									elseif ($key == 'menutype') {
										$insert[$key] = $newmenutype;
									}
									elseif ($key == 'path') {
										$insert[$key] = $newmenutype;
									}
									elseif ($key == 'alias') {
										$insert[$key] = str_replace($formbuilder->getSpecialCharacters(), '-', strtolower($new_title)) . '-' . $newprofile;
									}
								}
								$insert = (object)$insert;
								$inserted_heading = $this->db->insertObject('#__menu', $insert);

								if ($inserted_heading) {
									// Get fabrik_lists
									$query->clear()
										->select('link')
										->from('#__menu')
										->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($oldprofile->menutype))
										->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
										->andWhere('published = 1');
									$this->db->setQuery($query);
									$links = $this->db->loadObjectList();

									foreach ($links as $link) {
										if (strpos($link->link, 'formid') !== false) {
											$formsid_arr[] = explode('=', $link->link)[3];
										}
									}

									foreach ($formsid_arr as $formid) {
										$query->clear()
											->select('label, intro')
											->from($this->db->quoteName('#__fabrik_forms'))
											->where($this->db->quoteName('id') . ' = ' . $this->db->quote($formid));
										$this->db->setQuery($query);
										$form = $this->db->loadObject();

										$label = array();
										$intro = array();

										$languages = LanguageHelper::getLanguages();
										foreach ($languages as $language) {
											# Fabrik has a functionnality that adds <p> tags around the intro text, we need to remove them
											$stripped_intro = strip_tags($form->intro);
											if ($form->intro == '<p>' . $stripped_intro . '</p>') {
												$form->intro = $stripped_intro;
											}

											$label[$language->sef] = LanguageFactory::getTranslation($form->label, $language->lang_code);
											$intro[$language->sef] = LanguageFactory::getTranslation($form->intro, $language->lang_code);

											if (empty($label[$language->sef])) {
												$label[$language->sef] = '';
											}
											if (empty($intro[$language->sef])) {
												$intro[$language->sef] = '';
											}
										}

										$new_form = $formbuilder->createMenuFromTemplate($label, $intro, $formid, $newprofile, true);

										if($duplicate_condition) {
											$formbuilder->duplicateConditions((int)$formid, (int)$new_form['id']);
										}
									}

									// Copy attachments
									$copied = $this->copyAttachmentsToNewProfile($pid, $newprofile);

									// Create checklist menu
									$this->addChecklistMenu($newprofile);

									$duplicated = $newprofile;
								}
								else {
									Log::add('Failed to duplicate form, heading has not been created properly', Log::WARNING, 'com_emundus.error');
								}
							}
							else {
								Log::add('Failed to duplicate form, no heading menu found', Log::WARNING, 'com_emundus.error');
							}
							//
						}
						else {
							Log::add('Failed to duplicate form, empty new profile ', Log::WARNING, 'com_emundus.error');
						}
					}
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error when duplicate forms : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		LanguageFactory::cleanCache();

		return $duplicated;
	}

	public function copyAttachmentsToNewProfile($oldprofile, $newprofile)
	{
		$copied = false;
		$db = $this->db;

		if (!empty($oldprofile) && !empty($newprofile)) {

			$query = $this->db->getQuery(true);

			$new_profile_exists = false;
			$query->select('id')
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('id') . ' = ' . $newprofile);

			try {
				$this->db->setQuery($query);
				$new_profile_exists = $this->db->loadResult();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error when get profile : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}

			if (!empty($new_profile_exists)) {
				$query->clear();
				$query->select('*')
					->from($this->db->quoteName('#__emundus_setup_attachment_profiles'))
					->where($this->db->quoteName('profile_id') . ' = ' . $oldprofile);

				try {
					$this->db->setQuery($query);
					$attachments = $this->db->loadAssocList();
				}
				catch (Exception $e) {
					Log::add('component/com_emundus/models/form | Error when get attachments to copy : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

					return false;
				}

				if (!empty($attachments)) {
					$columns = array_keys($attachments[0]);
					$id_key  = array_search('id', $columns);
					unset($columns[$id_key]);

					$values = array();
					foreach ($attachments as $attachment) {
						$attachment['profile_id'] = $newprofile;
						unset($attachment['id']);

						foreach ($attachment as $key => $value) {
							if (empty($value) && $value != 0) {
								$attachment[$key] = null;
							}
						}

						// do not use db->quote() every time, only if the value is not an integer and not null
						$values[] = implode(',', array_map(function ($value) use ($db) {
							return is_null($value) ? 'NULL' : $db->quote($value);
						}, $attachment));
					}

					$query->clear()
						->insert($this->db->quoteName('#__emundus_setup_attachment_profiles'))
						->columns($this->db->quoteName($columns))
						->values($values);

					try {
						$this->db->setQuery($query);
						$copied = $this->db->execute();
					}
					catch (Exception $e) {
						Log::add('component/com_emundus/models/form | Error when copy attachments to new profile : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
					}
				}
			}
		}

		return $copied;
	}

	public function getFormById($id)
	{
		if (empty($id)) {
			return false;
		}


		$query = $this->db->getQuery(true);

		$query->select(['sp.*', 'sp.label AS form_label'])
			->from($this->db->quoteName('#__emundus_setup_profiles', 'sp'))
			->where($this->db->quoteName('sp.id') . ' = ' . $id);

		$this->db->setQuery($query);

		try {
			$this->db->setQuery($query);

			return $this->db->loadObject();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error when get form by id ' . $id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getFormByFabrikId($id)
	{
		$form = [];

		if (!empty($id)) {

			$query = $this->db->getQuery(true);

			$query->select('id, label')
				->from($this->db->quoteName('#__fabrik_forms'))
				->where($this->db->quoteName('id') . ' = ' . $id);


			try {
				$this->db->setQuery($query);
				$form = $this->db->loadObject();

				if (!empty($form->label)) {
					$form->label = Text::_($form->label);
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error when get form by fabrik id ' . $id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $form;
	}

	public function createApplicantProfile($first_page = true)
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'formbuilder.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'settings.php');
		require_once(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'update.php');

		$formbuilder = new EmundusModelFormbuilder();
		$settings    = new EmundusModelSettings();

		$query = $this->db->getQuery(true);

		// Create profile
		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_profiles'))
			->order('id DESC');
		$this->db->setQuery($query);
		$lastprofile = $this->db->loadObjectList()[0];

		$columns = array(
			'label',
			'description',
			'published',
			'schoolyear',
			'menutype',
			'reference_letter',
			'acl_aro_groups',
			'is_evaluator',
			'status',
			'class');

		$values = array(
			'Nouveau formulaire',
			'',
			1,
			null,
			'menu-profile',
			null,
			2,
			0,
			1,
			null
		);

		if ($lastprofile->id == '999' || $lastprofile->id == '1000') {
			array_unshift($columns, 'id');
			array_unshift($values, 1001);
		}
		$query->clear()
			->insert($this->db->quoteName('#__emundus_setup_profiles'))
			->columns($this->db->quoteName($columns))
			->values(implode(',', $this->db->Quote($values)));

		try {
			$this->db->setQuery($query);
			$this->db->execute();
			$newprofile = $this->db->insertid();
			if (empty($newprofile)) {
				return false;
			}

			// Create menutype
			$menutype = $this->createMenuType('menu-profile' . $newprofile, 'Nouveau formulaire');
			if (empty($menutype)) {
				return false;
			}

			$query->clear()
				->update($this->db->quoteName('#__emundus_setup_profiles'))
				->set($this->db->quoteName('menutype') . ' = ' . $this->db->quote($menutype))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($newprofile));
			$this->db->setQuery($query);
			$this->db->execute();


			// Create heading menu
			$datas        = [
				'menutype'     => 'menu-profile' . $newprofile,
				'title'        => 'Nouveau formulaire',
				'link'         => '#',
				'type'         => 'heading',
				'component_id' => 0,
				'params'       => []
			];
			$heading_menu = EmundusHelperUpdate::addJoomlaMenu($datas);
			if ($heading_menu['status'] !== true) {
				return false;
			}
			$header_menu_id = $heading_menu['id'];

			$alias = 'menu-profile' . $newprofile . '-heading-' . $header_menu_id;
			$query->clear()
				->update($this->db->quoteName('#__menu'))
				->set($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias))
				->set($this->db->quoteName('path') . ' = ' . $this->db->quote($alias))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($header_menu_id));
			$this->db->setQuery($query);
			$this->db->execute();

			// Create first page
			if ($first_page) {
				$label = [
					'fr' => 'Ma première page',
					'en' => 'My first page'
				];
				$intro = [
					'fr' => 'Décrivez votre page de formulaire avec une introduction',
					'en' => 'Describe your form page with an introduction'
				];
				$formbuilder->createApplicantMenu($label, $intro, $newprofile, 'false');
			}

			// Create submittion page
			$label               = [
				'fr' => "Confirmation d'envoi de dossier",
				'en' => 'Data & disclaimer confirmation'
			];
			$intro               = [
				'fr' => '',
				'en' => ''
			];
			$submittion_page_res = $formbuilder->createSubmittionPage($label, $intro, $newprofile);
			if ($submittion_page_res['status'] !== true) {
				return false;
			}

			// Create checklist menu
			$this->addChecklistMenu($newprofile);
			//

			LanguageFactory::cleanCache();

			return $newprofile;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error when create a setup_profile : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.error');

			return false;
		}
	}

	/**
	 * @param $user
	 *
	 * @return int
	 * @throws Exception
	 */
	public function createFormEval($user = null, $label = ['fr' => 'Nouvelle Évaluation', 'en' => 'New Evaluation'], $intro =  ['fr' => 'Introduction de l\'évaluation', 'en' => 'Evaluation introduction'])
	{
		$new_form_id = 0;
		require_once(JPATH_ROOT . '/components/com_emundus/models/formbuilder.php');
		$m_formbuilder = new EmundusModelFormbuilder();

		$form_id = $m_formbuilder->createFabrikForm('EVALUATION', $label, $intro, 'eval', $user);
		if (!empty($form_id))
		{
			$new_form_id = $form_id;
			$group = $m_formbuilder->createGroup(array('fr' => 'Hidden group', 'en' => 'Hidden group'), $form_id, -1, 'form', $user);
			if (!empty($group))
			{
				// Create hidden group
				$m_formbuilder->createFormEvalDefaulltElements($group['group_id'], $user);
			}

			$list = $m_formbuilder->createFabrikList('evaluations', $form_id, 6, 'eval', $user);
			if (empty($list)) {
				Log::add('component/com_emundus/models/form | Error when create a list for evaluation form', Log::WARNING, 'com_emundus.error');
				throw new Exception('Error when create a list for evaluation form');
			}
		} else {
			Log::add('component/com_emundus/models/form | Error when create a form for evaluation form', Log::WARNING, 'com_emundus.error');
			throw new Exception('Error when create a form for evaluation form');
		}

		LanguageFactory::cleanCache();

		return $new_form_id;
	}

	public function createMenuType($menutype, $title)
	{
		$menutype_table = JTableNested::getInstance('MenuType');

		try {
			JFactory::$database = null;


			$query = $this->db->getQuery(true);


			$query->clear()
				->select('menutype')
				->from($this->db->quoteName('#__menu_types'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote($menutype));
			$this->db->setQuery($query);
			$is_existing = $this->db->loadResult();

			if (empty($is_existing)) {
				$data = array(
					'menutype'    => $menutype,
					'title'       => $title,
					'description' => '',
					'client_id'   => 0,
				);

				if (!$menutype_table->save($data)) {
					return '';
				}

				return $menutype;
			}
			else {
				return $is_existing;
			}
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Cannot create the menutype ' . $menutype . ' : -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return '';
		}
	}


	public function createMenu($menu, $menutype)
	{

		$query = $this->db->getQuery(true);

		// Insert columns.
		$columns = array(
			'menutype',
			'title',
			'alias',
			'note',
			'path',
			'link',
			'type',
			'published',
			'parent_id',
			'level',
			'component_id',
			'checked_out',
			'checked_out_time',
			'browserNav',
			'access',
			'img',
			'template_style_id',
			'params',
			'lft',
			'rgt',
			'home',
			'language',
			'client_id',
		);

		// Insert values.
		$values = array(
			$menutype,
			$menu['title'],
			$menu['alias'],
			$menu['note'],
			$menu['path'],
			$menu['link'],
			$menu['type'],
			$menu['published'],
			$menu['parent_id'],
			$menu['level'],
			$menu['component_id'],
			$menu['checked_out'],
			$menu['checked_out_time'],
			$menu['browserNav'],
			$menu['access'],
			$menu['img'],
			$menu['template_style_id'],
			$menu['params'],
			$menu['lft'],
			$menu['rgt'],
			$menu['home'],
			$menu['language'],
			$menu['client_id'],
		);

		$query->insert($this->db->quoteName('#__menu'))
			->columns($this->db->quoteName($columns))
			->values(implode(',', $this->db->Quote($values)));

		try {
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Cannot create the menu : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	public function updateForm($id, $data)
	{
		$query_pid = $this->db->getQuery(true);

		if (!empty($data)) {
			$fields = [];

			foreach ($data as $key => $val) {
				$insert   = $this->db->quoteName(htmlspecialchars($key)) . ' = ' . $this->db->quote(htmlspecialchars($val));
				$fields[] = $insert;
			}

			$query_pid->update($this->db->quoteName('#__emundus_setup_profiles'))
				->set($fields)
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));

			try {
				$this->db->setQuery($query_pid);

				return $this->db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Cannot update the form ' . $id . ' : ' . preg_replace("/[\r\n]/", " ", $query_pid . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

				return false;
			}
		}
		else {
			return false;
		}
	}

	public function updateFormLabel($prid, $label)
	{
		$results = [];

		if (!empty($prid)) {
			$query = $this->db->getQuery(true);

			// Truncate label to 150 characters if too long to avoid database errors
			if (strlen($label) > 150) {
				$label = substr($label, 0, 147) . '...';
			}

			$query->update($this->db->quoteName('#__menu_types'))
				->set($this->db->quoteName('title') . ' = ' . $this->db->quote($label))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('menu-profile' . $prid));

			try {
				$this->db->setQuery($query);
				$results[] = $this->db->execute();

				$query->clear()
					->select($this->db->quoteName('id'))
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('menu-profile' . $prid))
					->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'));
				$this->db->setQuery($query);
				$heading_id = $this->db->loadResult();

				$alias = 'menu-profile' . $prid . '-heading-' . $heading_id;
				$query->clear()
					->update($this->db->quoteName('#__menu'))
					->set($this->db->quoteName('title') . ' = ' . $this->db->quote($label))
					->set($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias))
					->set($this->db->quoteName('path') . ' = ' . $this->db->quote($alias))
					->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('menu-profile' . $prid))
					->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'));
				$this->db->setQuery($query);
				$results[] = $this->db->execute();

				$query->clear()
					->update($this->db->quoteName('#__emundus_setup_profiles'))
					->set($this->db->quoteName('label') . ' = ' . $this->db->quote($label))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($prid));
				$this->db->setQuery($query);
				$results[] = $this->db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Cannot update the form ' . $prid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $results;
	}


	public function getAllDocuments($prid, $cid)
	{

		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'falang.php');

		$falang = new EmundusModelFalang;

		try {
			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_attachment_profiles'))
				->where($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($prid))
				->andWhere($this->db->quoteName('campaign_id') . ' IS NULL ');
			$this->db->setQuery($query);
			$old_docs = $this->db->loadObjectList();

			if (!empty($old_docs)) {
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__emundus_setup_campaigns'))
					->where($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($prid));
				$this->db->setQuery($query);
				$campaignstoaffect = $this->db->loadObjectList();

				foreach ($campaignstoaffect as $campaign) {
					foreach ($old_docs as $old_doc) {
						$query->clear()
							->insert($this->db->quoteName('#__emundus_setup_attachment_profiles'));
						foreach ($old_doc as $key => $value) {
							if ($key != 'id' && $key != 'campaign_id') {
								$query->set($key . ' = ' . $this->db->quote($value));
							}
							elseif ($key == 'campaign_id') {
								$query->set($this->db->quoteName('campaign_id') . ' = ' . $this->db->quote($campaign->id));
							}
						}
						$this->db->setQuery($query);
						$this->db->execute();
					}
				}

				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_attachment_profiles'))
					->where($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($prid))
					->andWhere($this->db->quoteName('campaign_id') . ' IS NULL');
				$this->db->setQuery($query);
				$this->db->execute();
			}

			$query->clear()
				->select([
					'sap.attachment_id AS id',
					'sap.ordering',
					'sap.mandatory AS need',
					'sa.value',
					'sa.description',
					'sa.allowed_types',
					'sa.nbmax',
					'sa.lbl'
				])
				->from($this->db->quoteName('#__emundus_setup_attachment_profiles', 'sap'))
				->leftJoin($this->db->quoteName('#__emundus_setup_attachments', 'sa') . ' ON ' . $this->db->quoteName('sa.id') . ' = ' . $this->db->quoteName('sap.attachment_id'))
				->order($this->db->quoteName('sap.ordering'))
				->where($this->db->quoteName('sap.published') . ' = 1')
				->andWhere($this->db->quoteName('sap.campaign_id') . ' = ' . $cid);

			$this->db->setQuery($query);
			$documents = $this->db->loadObjectList();

			foreach ($documents as $document) {
				if (strpos($document->lbl, '_em') === 0) {
					$document->can_be_deleted = true;
				}
				else {
					$document->can_be_deleted = false;
				}

				$f_values           = $falang->getFalang($document->id, 'emundus_setup_attachments', 'value');
				$document->value_en = $f_values->en->value;
				$document->value_fr = $f_values->fr->value;

				$f_descriptions           = $falang->getFalang($document->id, 'emundus_setup_attachments', 'description');
				$document->description_en = $f_descriptions->en->value;
				$document->description_fr = $f_descriptions->fr->value;
			}

			return $documents;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error at getting documents of the campaign ' . $cid . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	public function getUnDocuments()
	{

		$query     = $this->db->getQuery(true);
		$languages = JLanguageHelper::getLanguages();

		require_once(JPATH_SITE . '/components/com_emundus/models/falang.php');

		$falang = new EmundusModelFalang;

		$query->select(array(' DISTINCT a.*', 'b.mandatory'))
			->from($this->db->quoteName('#__emundus_setup_attachments', 'a'))
			->leftJoin($this->db->quoteName('#__emundus_setup_attachment_profiles', 'b') . ' ON ' . $this->db->quoteName('b.attachment_id') . ' = ' . $this->db->quoteName('a.id'))
			->where($this->db->quoteName('a.published') . ' = 1')
			->group($this->db->quoteName('a.id'))
			->order($this->db->quoteName('a.value'));

		try {
			$this->db->setQuery($query);
			$undocuments = $this->db->loadObjectList();


			foreach ($undocuments as $undocument) {
				if (strpos($undocument->lbl, '_em') === 0) {
					$undocument->can_be_deleted = true;
				}
				else {
					$undocument->can_be_deleted = false;
				}

				$f_values                = $falang->getFalang($undocument->id, 'emundus_setup_attachments', 'value', $undocument->value);
				$f_descriptions          = $falang->getFalang($undocument->id, 'emundus_setup_attachments', 'description', $undocument->description);
				$undocument->name        = $f_values;
				$undocument->description = $f_descriptions;
			}

			return $undocuments;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error getting documents not associated : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getAttachments()
	{
		$attachments = [];

		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_setup_attachments'))
			->where($this->db->quoteName('published') . ' = 1')
			->order('value');

		try {
			$this->db->setQuery($query);
			$attachments = $this->db->loadObjectList();

			if (!empty($attachments)) {
				require_once(JPATH_SITE . '/components/com_emundus/models/falang.php');
				$falang = new EmundusModelFalang;

				foreach ($attachments as $attachment) {
					$attachment->can_be_deleted = strpos($attachment->lbl, '_em') === 0;
					$attachment->name           = $falang->getFalang($attachment->id, 'emundus_setup_attachments', 'value', $attachment->value);
					$attachment->description    = $falang->getFalang($attachment->id, 'emundus_setup_attachments', 'description', $attachment->description);
				}
			}
		}
		catch (Exception $e) {
			Log::add('Failed to get attachments ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $attachments;
	}

	/**
	 * @param $documentIds
	 *
	 * @return array
	 */
	public function getDocumentsUsage($documentIds): array
	{
		$forms = [];

        if (!empty($documentIds)) {
            $query = $this->db->getQuery(true);

			$query->select('jesap.attachment_id, jesap.profile_id, jesp.label')
				->from('jos_emundus_setup_attachment_profiles AS jesap')
				->leftJoin('jos_emundus_setup_profiles AS jesp ON jesap.profile_id = jesp.id')
				->where('jesap.attachment_id  IN (' . implode(',', $documentIds) . ')');


			try {
				$this->db->setQuery($query);
				$profile_infos = $this->db->loadObjectList();
			} catch (Exception $e) {
				$msg = 'Error trying to get profile info from attachment_id ' . $e->getMessage();
				Log::add($msg, Log::ERROR, 'com_emundus');
			}

			if (!empty($profile_infos)) {
				foreach ($profile_infos as $profile_info) {
					if (!isset($forms[$profile_info->attachment_id])) {
						$forms[$profile_info->attachment_id] = [
							'profiles' => [],
							'usage'    => 0
						];
					}

					$forms[$profile_info->attachment_id]['profiles'][] = [
						'id'    => $profile_info->profile_id,
						'label' => $profile_info->label
					];
					$forms[$profile_info->attachment_id]['usage']++;
				}
			}
		}

		return $forms;
	}

	public function deleteRemainingDocuments($prid, $allDocumentsIds)
	{


		$values = [];

		foreach ($allDocumentsIds as $document) {
			array_push($values, '(' . $document . ',' . $prid . ',0,0)');
		}

		$query =
			'INSERT INTO jos_emundus_setup_attachment_profiles 
        (attachment_id, profile_id, displayed, published)
        VALUES 
        ' .
			implode(',', $values) .
			'
        ON DUPLICATE KEY UPDATE 
        displayed = VALUES(displayed),
        published = VALUES(published),
        profile_id = VALUES(profile_id)
        ;';

		try {
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error deleting documents : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	public function removeDocument($did, $prid, $cid)
	{

		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName('#__emundus_setup_attachment_profiles'))
			->where($this->db->quoteName('attachment_id') . ' = ' . $this->db->quote($did))
			->andWhere($this->db->quoteName('campaign_id') . ' = ' . $this->db->quote($cid))
			->andWhere($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($prid));
		try {
			$this->db->setQuery($query);
			$this->db->execute();

			$documents_campaign = EmundusModelform::getAllDocuments($prid, $cid);

			if (empty($documents_campaign)) {
				$this->removeChecklistMenu($prid);
			}

			return true;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error remove document ' . $did . ' associated to the campaign ' . $cid . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function updateMandatory($did, $prid, $cid)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('id,mandatory')
				->from($this->db->quoteName('#__emundus_setup_attachment_profiles'))
				->where($this->db->quoteName('attachment_id') . ' = ' . $this->db->quote($did))
				->andWhere($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($prid))
				->andWhere($this->db->quoteName('campaign_id') . ' = ' . $this->db->quote($cid));
			$this->db->setQuery($query);
			$attachment = $this->db->loadObject();
			$mandatory  = intval($attachment->mandatory);

			if ($mandatory == 0) {
				$mandatory = 1;
			}
			else {
				$mandatory = 0;
			}

			$query->clear()
				->update($this->db->quoteName('#__emundus_setup_attachment_profiles'))
				->set($this->db->quoteName('mandatory') . ' = ' . $this->db->quote($mandatory))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($attachment->id));

			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error remove document ' . $did . ' associated to the campaign ' . $cid . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function addDocument($did, $profile, $campaign)
	{

		$query = $this->db->getQuery(true);

		try {
			// Create checklist menu if documents are asked
			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('checklist-' . $profile));
			$this->db->setQuery($query);
			$checklist = $this->db->loadObject();

			if ($checklist == null) {
				$this->addChecklistMenu($profile);
			}
			//

			$query->clear()
				->insert($this->db->quoteName('#__emundus_setup_attachment_profiles'))
				->set($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($profile))
				->set($this->db->quoteName('campaign_id') . ' = ' . $this->db->quote($campaign))
				->set($this->db->quoteName('attachment_id') . ' = ' . $this->db->quote($did))
				->set($this->db->quoteName('displayed') . ' = ' . $this->db->quote(1))
				->set($this->db->quoteName('mandatory') . ' = ' . $this->db->quote(0))
				->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote(0));
			$this->db->setQuery($query);
			$this->db->execute();

			$documents_campaign = EmundusModelform::getAllDocuments($profile, $campaign);

			if (empty($documents_campaign)) {
				$this->removeChecklistMenu($profile);
			}

			return true;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error remove document ' . $did . ' associated to the campaign ' . $campaign . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function deleteDocument($did)
	{

		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'falang.php');

		$falang = new EmundusModelFalang;

		try {
			$falang->deleteFalang($did, 'emundus_setup_attachments', 'value');
			$falang->deleteFalang($did, 'emundus_setup_attachments', 'description');

			$query->clear()
				->delete($this->db->quoteName('#__emundus_setup_attachment_profiles'))
				->where($this->db->quoteName('attachment_id') . ' = ' . $this->db->quote($did));

			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->delete($this->db->quoteName('#__emundus_setup_attachments'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($did));

			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error when delete the document ' . $did . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function addChecklistMenu($prid)
	{

		$query = $this->db->getQuery(true);

		$eMConfig = JComponentHelper::getParams('com_emundus');
		$modules  = $eMConfig->get('form_builder_page_creation_modules', [93, 102, 103, 104, 168, 170]);

		require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'update.php');

		try {
			// Create the menu
			$submittion_page = $this->getSubmittionPage($prid);

			$params = array(
				'custom_title'         => "",
				'show_info_panel'      => "0",
				'show_info_legend'     => "1",
				'show_browse_button'   => "0",
				'show_shortdesc_input' => "0",
				'required_desc'        => "0",
				'show_nb_column'       => "1",
				'is_admission'         => "0",
				'notify_complete_file' => 0,
				'menu-anchor_title'    => "Documents",
				'menu-anchor_css'      => "huge circular inverted blue upload outline icon",
				'menu_image'           => "0",
				'menu_image_css'       => "0",
				'menu_text'            => 1,
				'menu_show'            => 1,
				'page_title'           => "Documents",
				'show_page_heading'    => "",
				'page_heading'         => "",
				'pageclass_sfx'        => "applicant-form",
				'meta_description'     => "",
				'meta_keywords'        => "",
				'robots'               => "",
				'secure'               => 0,
			);

			$datas          = [
				'menutype'     => 'menu-profile' . $prid,
				'title'        => 'Documents',
				'alias'        => 'checklist-' . $prid,
				'path'         => 'checklist-' . $prid,
				'link'         => 'index.php?option=com_emundus&view=checklist',
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'params'       => $params
			];
			$checklist_menu = EmundusHelperUpdate::addJoomlaMenu($datas, $submittion_page->id, 1, 'before', $modules);
			if ($checklist_menu['status'] !== true) {
				return false;
			}

			$newmenuid = $checklist_menu['id'];

			// Affect documents module to each menus of profile
			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('menu-profile' . $prid));
			$this->db->setQuery($query);
			$menus = $this->db->loadObjectList();

			foreach ($menus as $menu) {
				$query->clear()
					->select('moduleid')
					->from($this->db->quoteName('#__modules_menu'))
					->where($this->db->quoteName('moduleid') . ' = 103')
					->andWhere($this->db->quoteName('menuid') . ' = ' . $this->db->quote($menu->id));
				$this->db->setQuery($query);
				$is_existing = $this->db->loadResult();

				if (!$is_existing) {
					$query->clear()
						->insert($this->db->quoteName('#__modules_menu'))
						->set($this->db->quoteName('moduleid') . ' = 103')
						->set($this->db->quoteName('menuid') . ' = ' . $this->db->quote($menu->id));
					$this->db->setQuery($query);
					$this->db->execute();
				}

				$query->clear()
					->select('moduleid')
					->from($this->db->quoteName('#__modules_menu'))
					->where($this->db->quoteName('moduleid') . ' = 104')
					->andWhere($this->db->quoteName('menuid') . ' = ' . $this->db->quote($menu->id));
				$this->db->setQuery($query);
				$is_existing = $this->db->loadResult();

				if (!$is_existing) {
					$query->clear()
						->insert($this->db->quoteName('#__modules_menu'))
						->set($this->db->quoteName('moduleid') . ' = 104')
						->set($this->db->quoteName('menuid') . ' = ' . $this->db->quote($menu->id));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}

			//

			return $newmenuid;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error to add the checklist module to form (' . $prid . ') menus : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function removeChecklistMenu($prid)
	{

		$query = $this->db->getQuery(true);

		$eMConfig = JComponentHelper::getParams('com_emundus');
		$modules  = $eMConfig->get('form_builder_page_creation_modules', [93, 102, 103, 104, 168, 170]);

		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('checklist-' . $prid));
		try {
			$this->db->setQuery($query);
			$checklist = $this->db->loadObject();

			foreach ($modules as $module) {
				$query->clear()
					->delete($this->db->quoteName('#__modules_menu'))
					->where($this->db->quoteName('moduleid') . ' = ' . $this->db->quote($module))
					->andWhere($this->db->quoteName('menuid') . ' = ' . $this->db->quote($checklist->id));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('menu-profile' . $prid));
			$this->db->setQuery($query);
			$menus = $this->db->loadObjectList();

			foreach ($menus as $menu) {
				$query->clear()
					->delete($this->db->quoteName('#__modules_menu'))
					->where($this->db->quoteName('moduleid') . ' IN (103,104)')
					->andWhere($this->db->quoteName('menuid') . ' = ' . $this->db->quote($menu->id));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			$query->clear()
				->delete($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($checklist->id));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error to remove the checklist module to form (' . $prid . ') menus : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	public function getFormsByProfileId($profile_id)
	{
		if (empty($profile_id)) {
			return false;
		}

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'formbuilder.php');
		$formbuilder = new EmundusModelFormbuilder;

		$query = $this->db->getQuery(true);

		$query->select(['menu.link', 'menu.rgt', 'menu.id as menu_id'])
			->from($this->db->quoteName('#__menu', 'menu'))
			->leftJoin($this->db->quoteName('#__menu_types', 'mt') . ' ON ' . $this->db->quoteName('mt.menutype') . ' = ' . $this->db->quoteName('menu.menutype'))
			->leftJoin($this->db->quoteName('#__emundus_setup_profiles', 'sp') . ' ON ' . $this->db->quoteName('sp.menutype') . ' = ' . $this->db->quoteName('mt.menutype'))
			->where($this->db->quoteName('sp.id') . ' = ' . $profile_id)
			->where($this->db->quoteName('menu.parent_id') . ' != 1')
			->where($this->db->quoteName('menu.published') . ' = 1')
			->where($this->db->quoteName('menu.link') . ' LIKE ' . $this->db->quote('%option=com_fabrik%'))
			->group('menu.rgt')
			->order('menu.rgt ASC');


		try {
			$this->db->setQuery($query);
			$forms = $this->db->loadObjectList();

			foreach ($forms as $form) {
				$link     = explode('=', $form->link);
				$form->id = $link[sizeof($link) - 1];

				$query->clear()
					->select('label, intro')
					->from($this->db->quoteName('#__fabrik_forms'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($form->id));
				$this->db->setQuery($query);
				$formObject = $this->db->loadObject();

				$form->label = Text::_($formObject->label);
				$form->intro = Text::_(strip_tags($formObject->intro));
				$form->intro = strip_tags($form->intro);
			}

			return $forms;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error at getting form pages by profile_id ' . $profile_id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getCampaignsByProfile($profile_id)
	{
		$campaigns = [];

		if (!empty($profile_id)) {

			$query = $this->db->getQuery(true);

			$query->select(['sc.id', 'sc.label'])
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'sc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_profiles', 'sp') . ' ON ' . $this->db->quoteName('sp.id') . ' = ' . $this->db->quoteName('sc.profile_id'))
				->where($this->db->quoteName('sp.id') . ' = ' . $profile_id);

			try {
				$campaigns = $this->db->setQuery($query)->loadAssocList();
			}
			catch (Exception $e) {
				Log::add('Failed to get campaigns from form_id ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $campaigns;
	}

	public function getGroupsByForm($form_id)
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'formbuilder.php');

		$formbuilder = new EmundusModelFormbuilder;


		$query = $this->db->getQuery(true);

		$query->select(['g.id', 'g.label', 'g.params', 'g.published'])
			->from($this->db->quoteName('#__fabrik_formgroup', 'fg'))
			->leftJoin($this->db->quoteName('#__fabrik_groups', 'g') . ' ON ' . $this->db->quoteName('g.id') . ' = ' . $this->db->quoteName('fg.group_id'))
			->where($this->db->quoteName('fg.form_id') . ' = ' . $form_id)
			->order('fg.ordering ASC');


		try {
			$this->db->setQuery($query);
			$groups = $this->db->loadObjectList();

			foreach ($groups as $key => $group) {
				$params = json_decode($group->params, true);
				if ($params['repeat_group_show_first'] == -1) {
					array_splice($groups, $key, 1);
				}
				$group->label = Text::_($group->label);
			}

			return $groups;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error at getting groups by form_id ' . $form_id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getSubmittionPage($prid)
	{
		if (empty($prid)) {
			return false;
		}


		$query = $this->db->getQuery(true);

		$query->select(['menu.link', 'menu.rgt', 'menu.id'])
			->from($this->db->quoteName('#__menu', 'menu'))
			->leftJoin($this->db->quoteName('#__menu_types', 'mt') . ' ON ' . $this->db->quoteName('mt.menutype') . ' = ' . $this->db->quoteName('menu.menutype'))
			->leftJoin($this->db->quoteName('#__emundus_setup_profiles', 'sp') . ' ON ' . $this->db->quoteName('sp.menutype') . ' = ' . $this->db->quoteName('mt.menutype'))
			->where($this->db->quoteName('sp.id') . ' = ' . $prid)
			->andWhere($this->db->quoteName('menu.parent_id') . ' = 1')
			->andWhere($this->db->quoteName('menu.type') . ' = ' . $this->db->quote('component'))
			->andWhere($this->db->quoteName('menu.published') . ' = 1');

		try {
			$this->db->setQuery($query);
			$menus    = $this->db->loadObjectList();
			$sub_page = new stdClass();

			foreach ($menus as $menu) {
				$formid = explode('=', $menu->link)[3];
				if ($formid != null) {
					$query->clear()
						->select('count(id)')
						->from($this->db->quoteName('#__fabrik_lists'))
						->where($this->db->quoteName('db_table_name') . ' LIKE ' . $this->db->quote('jos_emundus_declaration'))
						->andWhere($this->db->quoteName('form_id') . ' = ' . $this->db->quote($formid));
					$this->db->setQuery($query);
					$submittion = $this->db->loadResult();
					if ($submittion > 0) {
						$sub_page->link = $menu->link;
						$sub_page->rgt  = $menu->rgt;
						$sub_page->id   = $menu->id;

						break;
					}
				}
			}

			return $sub_page;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error at getting the submittion page of the form ' . $prid . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}

	}


	public function getProfileLabelByProfileId($profile_id)
	{
		if (empty($profile_id)) {
			return false;
		}


		$query = $this->db->getQuery(true);

		$query->select('stpr.label')
			->from($this->db->quoteName('#__emundus_setup_profiles', 'stpr'))
			->where($this->db->quoteName('stpr.id') . ' = ' . $profile_id);
		try {
			$this->db->setQuery($query);

			return $this->db->loadObject();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error at getting name of the form ' . $profile_id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getFilesByProfileId($profile_id)
	{

		$query = $this->db->getQuery(true);

		$user = JFactory::getUser();

		$files = 0;

		$query->select('id')
			->from($this->db->quoteName('#__emundus_setup_campaigns'))
			->where($this->db->quoteName('profile_id') . ' = ' . $profile_id);
		try {
			$this->db->setQuery($query);
			$campaigns = $this->db->loadObjectList();

			foreach ($campaigns as $campaign) {
				$query->clear()
					->select('COUNT(*)')
					->from($this->db->quoteName('#__emundus_campaign_candidature'))
					->where($this->db->quoteName('campaign_id') . ' = ' . $campaign->id)
					->andWhere($this->db->quoteName('published') . ' != -1')
					->andWhere($this->db->quoteName('user_id') . ' != ' . $this->db->quote($user->id));

				$this->db->setQuery($query);
				$files += $this->db->loadResult();
			}

			return $files;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error at getting files by form ' . $profile_id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getAssociatedCampaign($profile_id, $user_id = null)
	{
		$associated_campaigns = [];

		if(empty($user_id)) {
			$user_id = $this->app->getIdentity()->id;
		}

		// Get affected programs
		require_once(JPATH_SITE . '/components/com_emundus/models/programme.php');
		$m_programme = new EmundusModelProgramme;
		$programs = $m_programme->getUserPrograms($user_id);

		if (!empty($programs))
		{
			$query = $this->db->getQuery(true);

			$query->select(['sc.id as id', 'sc.label as label'])
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'sc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'sp') . ' ON ' . $this->db->quoteName('sp.code') . ' LIKE ' . $this->db->quoteName('sc.training'))
				->where($this->db->quoteName('sc.training') . ' IN (' . implode(',', $this->db->quote($programs)) . ')')
				->andWhere('sc.profile_id = ' . $profile_id);

			try {
				$this->db->setQuery($query);
				$associated_campaigns = $this->db->loadObjectList();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error at getting campaigns link to the form ' . $profile_id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}

			/**
			 * campaigns can be associated by workflow step
			 */
			$query->clear()
				->select('DISTINCT esc.id, esc.label')
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'esc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes','esp') . ' ON esp.code = esc.training')
				->leftJoin($this->db->quoteName('#__emundus_setup_workflows_programs','eswp') . ' ON eswp.program_id = esp.id')
				->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps','esws') . ' ON esws.workflow_id = eswp.workflow_id')
				->where($this->db->quoteName('esws.profile_id') . ' = ' . $this->db->quote($profile_id))
				->andWhere($this->db->quoteName('esc.training') . ' IN (' . implode(',', $this->db->quote($programs)) . ')');

			try {
				$this->db->setQuery($query);
				$workflow_campaigns = $this->db->loadObjectList();

				foreach ($workflow_campaigns as $campaign) {
					$associated_campaign_ids = array_column($associated_campaigns, 'id');

					if (!in_array($campaign->id, $associated_campaign_ids)) {
						$associated_campaigns[] = $campaign;
					}
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error at getting campaigns link to the form ' . $profile_id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $associated_campaigns;
	}

	public function getAssociatedProgram($form_id)
	{

		$query = $this->db->getQuery(true);

		$query->select(['group_id as id'])
			->from($this->db->quoteName('#__fabrik_formgroup'))
			->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($form_id));

		try {
			$this->db->setQuery($query);
			$group_id = $this->db->loadRow();
			//var_dump($group_id);


			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_setup_programmes'))
				->where($this->db->quoteName('fabrik_group_id') . ' = ' . $this->db->quote($group_id[0]));

			$this->db->setQuery($query);
			$programme = $this->db->loadObject();

			//var_dump($programme);
			return $programme;

		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error at getting eval form program link to the form ' . $form_id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function affectCampaignsToForm($prid, $campaigns)
	{
		foreach ($campaigns as $campaign) {

			$query = $this->db->getQuery(true);

			$query->select('year')
				->from($this->db->quoteName('#__emundus_setup_campaigns'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($campaign));
			$this->db->setQuery($query);
			$schoolyear = $this->db->loadResult();

			$query->clear()
				->update($this->db->quoteName('#__emundus_setup_campaigns'))
				->set($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($prid))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($campaign));

			try {
				$this->db->setQuery($query);
				$this->db->execute();

				$query->clear()
					->update($this->db->quoteName('#__emundus_setup_teaching_unity'))
					->set($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($prid))
					->where($this->db->quoteName('schoolyear') . ' = ' . $this->db->quote($schoolyear));

				$this->db->setQuery($query);
				$this->db->execute();

			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error when affect campaigns to the form ' . $prid . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

				return false;
			}
		}

		return true;
	}

	function getDocumentsByProfile($prid)
	{
		$attachments_by_profile = [];

		if (!empty($prid)) {

			$query = $this->db->getQuery(true);

			$query->select('sa.id as docid,sa.value as label,sap.*,sa.allowed_types')
				->from($this->db->quoteName('#__emundus_setup_attachment_profiles', 'sap'))
				->leftJoin($this->db->quoteName('#__emundus_setup_attachments', 'sa') . ' ON ' . $this->db->quoteName('sa.id') . ' = ' . $this->db->quoteName('sap.attachment_id'))
				->where($this->db->quoteName('sap.profile_id') . ' = ' . $this->db->quote($prid))
				->order('sap.mandatory DESC, sap.ordering, sa.value ASC');

			try {
				$this->db->setQuery($query);
				$attachments_by_profile = $this->db->loadObjectList();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error cannot get documents by profile_id : ' . $prid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $attachments_by_profile;
	}

	function reorderDocuments($documents)
	{

		$query = $this->db->getQuery(true);

		$results = array();

		try {
			foreach ($documents as $document) {

				$query->update($this->db->quoteName('#__emundus_setup_attachment_profiles'))
					->set($this->db->quoteName('ordering') . ' = ' . (int) $document['ordering'])
					->where($this->db->quoteName('id') . ' = ' . (int) $document['id']);
				$this->db->setQuery($query);

				$results[] = $this->db->execute();
				$query->clear();
			}

			return $results;

		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error cannot reorder documents : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function removeDocumentFromProfile($did)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->delete($this->db->quoteName('#__emundus_setup_attachment_profiles'))
				->where($this->db->quoteName('id') . ' = ' . (int) $did);
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error cannot remove document : ' . $did . ' with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function deleteModelDocument($did)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('count(id)')
				->from($this->db->quoteName('#__emundus_setup_attachment_profiles'))
				->where($this->db->quoteName('attachment_id') . ' = ' . $this->db->quote($did));
			$this->db->setQuery($query);
			$attachment_used = $this->db->loadResult();

			if ($attachment_used == 0) {
				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_attachments'))
					->where($this->db->quoteName('id') . ' = ' . (int) $did);
				$this->db->setQuery($query);

				return $this->db->execute();
			}
			else {
				return false;
			}
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/form | Error cannot delete document template : ' . $did . ' with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/*
	 *
	 * @param $table
	 * @param $column
	 * @param $value
	 * @param $concat_value
	 * @param $where
	 * @param $private_call -> WARNING : if true, the function will not check if the table is allowed, must not be called from controller if true
	 */
	function getDatabaseJoinOptions($table, $column, $value, $concat_value = null, $where = null, $private_call = false)
	{
		$options = [];

		if (!empty($table) && !empty($column) && !empty($value)) {
			$query = $this->db->getQuery(true);

			$query->clear()
				->select('database_name')
				->from('jos_emundus_datas_library');

			$this->db->setQuery($query);
			$allowed_tables = $this->db->loadColumn();

			if (!in_array($table, $allowed_tables) && !$private_call) {
				throw new Exception(Text::_('ACCESS_DENIED'));
			}
			
			$current_shortlang = explode('-', JFactory::getLanguage()->getTag())[0];

			try {
				$value_select = $value . ' as value';
				if (!empty($concat_value)) {
					$concat_value = str_replace('{thistable}', $table, $concat_value);
					$concat_value = str_replace('{shortlang}', $current_shortlang, $concat_value);

					$value_select = 'CONCAT(' . $concat_value . ') as value';
				}
				$query->clear()
					->select(array($this->db->quoteName($column, 'primary_key'), $value_select))
					->from($this->db->quoteName($table));
				if (!empty($where)) {
					$query->where(str_replace('WHERE', '', $where));
				}
				else {
					// Check if table have a published column
					$columns = $this->db->getTableColumns($table);
					if (array_key_exists('published', $columns)) {
						$query->where($this->db->quoteName('published') . ' = 1');
					}
				}
				$this->db->setQuery($query);

				$options = $this->db->loadObjectList();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/form | Error at getDatabaseJoinOptions : ' . preg_replace("/[\r\n]/", " ", $e->getMessage()), Log::ERROR, 'com_emundus');

				return false;
			}	
		}
		
		return $options;
	}

	public function checkIfDocCanBeRemovedFromCampaign($document_id, $profile_id): array
	{
		$data = [
			'can_be_deleted' => false,
			'reason'         => 'No response from sql'
		];


		$query = $this->db->getQuery(true);

		$query->select('COUNT(jeu.id)')
			->from('#__emundus_uploads AS jeu')
			->leftJoin('#__emundus_setup_campaigns AS jesc ON jesc.id = jeu.campaign_id')
			->where('jesc.profile_id = ' . $profile_id)
			->andWhere('jeu.attachment_id = ' . $document_id);

		$this->db->setQuery($query);

		try {
			$nb_uploads = $this->db->loadResult();
		}
		catch (Exception $e) {
			Log::add('Error trying to know if i can remove document from profile ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		if ($nb_uploads < 1) {
			$data['can_be_deleted'] = true;
			$data['reason']         = 'No document found for this attachment_id and campaign_id';
		}
		else {
			$data['reason'] = $nb_uploads;
			$data['sql']    = $query->__toString();
		}

		return $data;
	}

	public function getProgramsByForm($form_id)
	{
		$programs = [];

		if (!empty($form_id)) {
			$query = $this->db->getQuery(true);
			$query->select('DISTINCT eswp.program_id')
				->from($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp'))
				->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws') . ' ON ' . $this->db->quoteName('esws.workflow_id') . ' = ' . $this->db->quoteName('eswp.workflow_id'))
				->where($this->db->quoteName('esws.form_id') . ' = ' . $this->db->quote($form_id));

			$this->db->setQuery($query);
			$program_ids = $this->db->loadColumn();

			if (!empty($program_ids)) {
				$query->clear()
					->select('id, code, label')
					->from($this->db->quoteName('#__emundus_setup_programmes'))
					->where($this->db->quoteName('id') . ' IN (' . implode(',', $program_ids) . ')');
				$this->db->setQuery($query);
				$programs = $this->db->loadAssocList();
			}
		}

		return $programs;
	}

	public function associateFabrikGroupsToProgram($form_id,$programs,$mode = 'eval')
	{
		$associated = false;

		$query = $this->db->getQuery(true);

		if(!empty($form_id) && !empty($programs) && is_array($programs))
		{
			try
			{
				$query->select('group_id')
					->from($this->db->quoteName('#__fabrik_formgroup'))
					->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($form_id));
				$this->db->setQuery($query);
				$fabrik_groups = $this->db->loadColumn();

				if (!empty($fabrik_groups))
				{
					switch ($mode) {
						case 'decision':
							$column = 'fabrik_decision_group_id';
							break;
						default:
							$column = 'fabrik_group_id';
							break;
					}

					$existing_programs = $this->getProgramsByForm($form_id,$mode);

					foreach ($programs as $program) {
						$query->clear()
							->update($this->db->quoteName('#__emundus_setup_programmes'))
							->set($this->db->quoteName($column) . ' = ' . $this->db->quote(implode(',', $fabrik_groups)))
							->set($this->db->quoteName('evaluation_form') . ' = ' . $this->db->quote($form_id))
							->where($this->db->quoteName('code') . ' LIKE ' . $this->db->quote($program));
						$this->db->setQuery($query);
						$associated = $this->db->execute();
					}

					foreach ($existing_programs as $program) {
						if(!in_array($program['code'],$programs)) {
							$query->clear()
								->update($this->db->quoteName('#__emundus_setup_programmes'))
								->set($this->db->quoteName($column) . ' = ' . $this->db->quote(''))
								->where($this->db->quoteName('code') . ' LIKE ' . $this->db->quote($program['code']));
							$this->db->setQuery($query);
							$removed = $this->db->execute();
						}
					}
				}
			} catch (Exception $e)
			{
				Log::add('component/com_emundus/models/form | Error at associateFabrikGroupsToProgram : ' . preg_replace("/[\r\n]/", " ", $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $associated;
	}

	public function getJSConditionsByForm($form_id, $format = 'raw')
	{
		$js_conditions = [];

		$query = $this->db->getQuery(true);

		try
		{
			$query->select('form_id')
				->from($this->db->quoteName('#__emundus_setup_formlist'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('profile'));
			$this->db->setQuery($query);
			$profile_form_id = $this->db->loadResult();

			$query->clear()
				->select($this->db->quoteName(['id','group', 'published', 'label']))
				->from($this->db->quoteName('#__emundus_setup_form_rules'))
				->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($form_id))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('js'));
			if($format == 'raw') {
				$query->where($this->db->quoteName('published') . ' = 1');
			}
			$this->db->setQuery($query);
			$js_conditions = $this->db->loadObjectList();

			foreach ($js_conditions as $js_condition)
			{
				$query->clear()
					->select($this->db->quoteName(['esfrjc.id','esfrjc.parent_id','esfrjc.field','esfrjc.state','esfrjc.values','esfrjc.group','esfrjcg.group_type', 'esfrjc.type']))
					->from($this->db->quoteName('#__emundus_setup_form_rules_js_conditions','esfrjc'))
					->leftJoin($this->db->quoteName('#__emundus_setup_form_rules_js_conditions_group','esfrjcg').' ON '.$this->db->quoteName('esfrjcg.id').' = '.$this->db->quoteName('esfrjc.group'))
					->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($js_condition->id));
				$this->db->setQuery($query);
				$js_condition->conditions = $this->db->loadObjectList();

				if($format == 'view')
				{
					$tmp_conditions = [];
					foreach ($js_condition->conditions as $condition)
					{
						$query->clear()
							->select('jfe.label,jfe.plugin,jfe.params')
							->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
							->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON ' . $this->db->quoteName('jffg.group_id') . ' = ' . $this->db->quoteName('jfe.group_id'))
							->where($this->db->quoteName('jfe.name') . ' = ' . $this->db->quote($condition->field));
						if($condition->type == 'user')
						{
							$query->andWhere($this->db->quoteName('jffg.form_id') . ' = ' . $this->db->quote($profile_form_id));
						}
						else {
							$query->andWhere($this->db->quoteName('jffg.form_id') . ' = ' . $this->db->quote($form_id));
						}

						$this->db->setQuery($query);
						$elt = $this->db->loadObject();
						$condition->elt_label = Text::_($elt->label);

						$choices_plugin = ['checkbox','dropdown','radiobutton'];
						$params = json_decode($elt->params);

						if(in_array($elt->plugin,$choices_plugin)) {
							// Get values
							foreach ($params->sub_options->sub_labels as $key => $sub_label) {
								$params->sub_options->sub_labels[$key] = Text::_($sub_label);
							}

							$condition->options = $params->sub_options;
						}
						elseif ($elt->plugin == 'yesno') {
							$condition->options = new stdClass();
							$condition->options->sub_values = [
								0,
								1
							];
							$condition->options->sub_labels = [
								Text::_('JNO'),
								Text::_('JYES')
							];
						}
						elseif ($elt->plugin == 'databasejoin') {
							$condition->options = new stdClass();
							$condition->options->sub_values = [];
							$condition->options->sub_labels = [];
							$databasejoin_options = $this->getDatabaseJoinOptions($params->join_db_name, $params->join_key_column, $params->join_val_column, $params->join_val_column_concat, null, true);
							foreach ($databasejoin_options as $databasejoin_option) {
								$condition->options->sub_values[] = $databasejoin_option->primary_key;
								$condition->options->sub_labels[] = $databasejoin_option->value;
							}
						}

						if(!empty($condition->group)) {
							$tmp_conditions[$condition->group][] = $condition;
						} else {
							$tmp_conditions[][] = $condition;
						}
					}
					$js_condition->conditions = $tmp_conditions;
				}

				$query->clear()
					->select('esfrr.action,group_concat(esfrr_fields.fields) as fields,group_concat(esfrr_fields.params SEPARATOR "|") as params')
					->from($this->db->quoteName('#__emundus_setup_form_rules_js_actions','esfrr'))
					->leftJoin($this->db->quoteName('#__emundus_setup_form_rules_js_actions_fields','esfrr_fields').' ON '.$this->db->quoteName('esfrr_fields.parent_id').' = '.$this->db->quoteName('esfrr.id'))
					->where($this->db->quoteName('esfrr.parent_id') . ' = ' . $this->db->quote($js_condition->id))
					->group('esfrr.id');
				$this->db->setQuery($query);
				$js_condition->actions = $this->db->loadObjectList();

				if($format == 'view')
				{
					foreach ($js_condition->actions as $action)
					{
						$action->labels = [];
						$action->fields = explode(',',$action->fields);
						$action->params = !empty($action->params) ? explode('|',$action->params) : [];

						$query->clear()
							->select('fe.label')
							->from($this->db->quoteName('#__fabrik_elements','fe'))
							->leftJoin($this->db->quoteName('#__fabrik_formgroup','ffg').' ON '.$this->db->quoteName('ffg.group_id').' = '.$this->db->quoteName('fe.group_id'))
							->where($this->db->quoteName('fe.name') . ' IN (' . implode(',',$this->db->quote($action->fields)) . ')')
							->where($this->db->quoteName('ffg.form_id') . ' = ' . $this->db->quote($form_id));
						$this->db->setQuery($query);
						$labels = $this->db->loadColumn();
						foreach ($labels as $label)
						{
							$action->labels[] = Text::_($label);
						}
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/form | Error at getConditionsByForm : ' . preg_replace("/[\r\n]/"," ",$e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $js_conditions;
	}

	public function addRule($form_id, $grouped_conditions, $actions, $type = 'js', $group = 'OR', $label = '', $user = null)
	{
		$rule_inserted = false;

		if(empty($user)) {
			$user = Factory::getApplication()->getIdentity();
		}

		$grouped_conditions = json_decode($grouped_conditions);
		$actions = json_decode($actions);

		try
		{
			$insert = [
				'date_time' => date('Y-m-d H:i:s'),
				'created_by' => $user->id,
				'form_id' => $form_id,
				'type' => $type,
				'group' => $group,
				'label' => !empty($label) ? $label : ' ',
				'published' => 1
			];
			$insert = (object) $insert;
			$this->db->insertObject('#__emundus_setup_form_rules', $insert);

			$rule_id = $this->db->insertid();

			if(!empty($rule_id))
			{
				foreach ($grouped_conditions as $grouped_condition)
				{
					if(count($grouped_condition) > 1) {
						$group_type = $grouped_condition[0]->group_type;
						$group_id = $this->createConditionGroup($group_type);
						foreach ($grouped_condition as $condition)
						{
							$condition->group = $group_id;
							$this->addCondition($rule_id, $condition);
						}
					} else {
						foreach ($grouped_condition as $condition)
						{
							$this->addCondition($rule_id, $condition);
						}
					}
				}

				foreach ($actions as $action)
				{
					$this->addAction($rule_id, $action);
				}

				$rule_inserted = true;
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/form | Error at addRule : ' . preg_replace("/[\r\n]/"," ",$e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $rule_inserted;
	}

	public function editRule($rule_id, $grouped_conditions, $actions, $group = 'OR', $label = '', $user = null)
	{
		$rule_edited = false;

		if(empty($user)) {
			$user = Factory::getApplication()->getIdentity();
		}

		$grouped_conditions = json_decode($grouped_conditions);
		$actions = json_decode($actions);


		$query = $this->db->getQuery(true);

		try
		{
			if(!empty($rule_id))
			{
				$query->select('DISTINCT '.$this->db->quoteName('group'))
					->from($this->db->quoteName('#__emundus_setup_form_rules_js_conditions'))
					->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($rule_id))
					->where($this->db->quoteName('group') . ' IS NOT NULL');
				$this->db->setQuery($query);
				$condition_groups = $this->db->loadColumn();

				if(!empty($condition_groups))
				{
					$query->clear()
						->delete($this->db->quoteName('#__emundus_setup_form_rules_js_conditions_group'))
						->where($this->db->quoteName('id') . ' IN (' . implode(',', $this->db->quote($condition_groups)) . ')');
					$this->db->setQuery($query);
					$this->db->execute();
				}

				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_form_rules_js_conditions'))
					->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($rule_id));
				$this->db->setQuery($query);
				$this->db->execute();

				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_form_rules_js_actions'))
					->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($rule_id));
				$this->db->setQuery($query);
				$this->db->execute();

				foreach ($grouped_conditions as $grouped_condition)
				{
					if(count($grouped_condition) > 1) {
						$group_type = $grouped_condition[0]->group_type;
						$group_id = $this->createConditionGroup($group_type);
						foreach ($grouped_condition as $condition)
						{
							$condition->group = $group_id;
							$this->addCondition($rule_id, $condition);
						}
					} else {
						foreach ($grouped_condition as $condition)
						{
							$this->addCondition($rule_id, $condition);
						}
					}
				}

				foreach ($actions as $action)
				{
					$this->addAction($rule_id, $action);
				}

				$update = [
					'id' => $rule_id,
					'group' => $group,
					'label' => !empty($label) ? $label : '',
					'updated_by' => $user->id,
					'updated' => date('Y-m-d H:i:s')
				];
				$update = (object) $update;
				$this->db->updateObject('#__emundus_setup_form_rules', $update, 'id');

				$rule_edited = true;
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/form | Error at editRule : ' . preg_replace("/[\r\n]/"," ",$e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $rule_edited;
	}

	public function deleteRule($rule_id)
	{
		$rule_deleted = false;


		$query = $this->db->getQuery(true);

		try
		{
			if(!empty($rule_id))
			{
				$query->delete($this->db->quoteName('#__emundus_setup_form_rules_js_conditions'))
					->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($rule_id));
				$this->db->setQuery($query);
				$this->db->execute();

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__emundus_setup_form_rules_js_actions'))
					->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($rule_id));
				$this->db->setQuery($query);
				$action_ids = $this->db->loadColumn();

				foreach ($action_ids as $actionId) {
					$query->clear()
						->delete($this->db->quoteName('#__emundus_setup_form_rules_js_actions_fields'))
						->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($actionId));
					$this->db->setQuery($query);
					$this->db->execute();
				}

				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_form_rules_js_actions'))
					->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($rule_id));
				$this->db->setQuery($query);
				$this->db->execute();

				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_form_rules'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($rule_id));
				$this->db->setQuery($query);
				$this->db->execute();

				$rule_deleted = true;
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/form | Error at deleteRule : ' . preg_replace("/[\r\n]/"," ",$e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $rule_deleted;
	}

	public function publishRule($rule_id, $state, $user = null)
	{
		$rule_published = false;

		if(empty($user)) {
			$user = Factory::getApplication()->getIdentity();
		}



		try
		{
			if(!empty($rule_id))
			{
				$update = [
					'id' => $rule_id,
					'published' => $state,
					'updated_by' => $user->id,
					'updated' => date('Y-m-d H:i:s')
				];
				$update = (object) $update;
				$this->db->updateObject('#__emundus_setup_form_rules', $update, 'id');

				$rule_published = true;
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/form | Error at publishRule : ' . preg_replace("/[\r\n]/"," ",$e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $rule_published;
	}

	private function addCondition($rule_id, $condition)
	{
		$operators = ['=', '!=', '<', '>', '<=', '>=', 'empty', '!empty'];

		try
		{
			$insert = [
				'parent_id' => $rule_id,
				'field'     => $condition->field,
				'state'     => in_array($condition->state, $operators) ? $condition->state : '=',
				'values'    => $condition->values,
				'group'     => !empty($condition->group) ? $condition->group : null,
				'type'      => $condition->type ?? 'form'
			];
			$insert = (object) $insert;
			$this->db->insertObject('#__emundus_setup_form_rules_js_conditions', $insert);
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/form | Error at addCondition : ' . preg_replace("/[\r\n]/"," ",$e->getMessage()), Log::ERROR, 'com_emundus');
		}
	}

	private function createConditionGroup($group_type)
	{
		$group_id = 0;


		try
		{
			$insert = [
				'group_type' => $group_type,
			];
			$insert = (object) $insert;
			$this->db->insertObject('#__emundus_setup_form_rules_js_conditions_group', $insert);

			$group_id = $this->db->insertid();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/form | Error at addCondition : ' . preg_replace("/[\r\n]/"," ",$e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $group_id;
	}

	private function addAction($rule_id, $action)
	{


		try
		{
			$insert = [
				'parent_id' => $rule_id,
				'action'    => $action->action
			];
			$insert = (object) $insert;
			$this->db->insertObject('#__emundus_setup_form_rules_js_actions', $insert);

			$action_id = $this->db->insertid();

			foreach ($action->fields as $field)
			{
				$insert = [
					'parent_id' => $action_id,
					'fields'    => $field,
					'params'    => !empty($action->params) ? json_encode($action->params) : null
				];
				$insert = (object) $insert;
				$this->db->insertObject('#__emundus_setup_form_rules_js_actions_fields', $insert);
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/form | Error at addAction : ' . preg_replace("/[\r\n]/"," ",$e->getMessage()), Log::ERROR, 'com_emundus');
		}
	}

	public function getUserProfileElements($only_names = false)
	{
		$elements = [];

		try
		{
			// Get profile form id
			$query = $this->db->getQuery(true);

			$query->select('form_id')
				->from($this->db->quoteName('#__emundus_setup_formlist'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('profile'));
			$this->db->setQuery($query);
			$form_id = $this->db->loadResult();

			$select = 'fe.id,fe.name,fe.label,fe.plugin,fe.eval,fe.group_id,fe.hidden,fe.params,fe.default,fe.published as publish,fe.show_in_list_summary';
			if($only_names) {
				$select = 'fe.name';
			}
			$query->clear()
				->select($select)
				->from($this->db->quoteName('#__fabrik_elements', 'fe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.group_id') . ' = ' . $this->db->quoteName('fe.group_id'))
				->where($this->db->quoteName('ffg.form_id') . ' = ' . $this->db->quote($form_id))
				->where($this->db->quoteName('fe.published') . ' = 1')
				->order('fe.ordering ASC');
			$this->db->setQuery($query);

			if(!$only_names)
			{
				$elements = $this->db->loadObjectList();
				foreach ($elements as $element)
				{
					$params = json_decode($element->params, true);

					$element->FRequire = false;
					if (!empty($params['validations']) && in_array('notempty', $params['validations']['plugin']))
					{
						$element->FRequire = true;
					}
					$element->label_tag = $element->label;
					$element->label     = Text::_($element->label);
					$element->params = $params;
				}
			}
			else {
				$elements = $this->db->loadColumn();
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/form | Error at getUserProfileElements : ' . preg_replace("/[\r\n]/"," ",$e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $elements;
	}
}
