<?php
require_once(JPATH_ROOT . '/components/com_emundus/classes/filters/EmundusFilters.php');
require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

class EmundusFiltersFiles extends EmundusFilters
{
	private $app;

	private $profiles = [];
	private $user_campaigns = [];
	private $user_programs = [];
	private $user_fnum_assocs = [];
	private $user_groups = [];
    private $config = [];
	private $m_users = null;
	private $menu_params = null;

	private bool $keep_session_filters = false;
	public function __construct($config = array(), $skip = false, $keep_session_filters = false)
	{
		Log::addLogger(['text_file' => 'com_emundus.filters.php'], Log::ALL, 'com_emundus.filters');

		$this->app = Factory::getApplication();
		$this->user = $this->app->getIdentity();

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id) || !EmundusHelperAccess::asAccessAction(1, 'r', $this->user->id)) {
			throw new Exception('Access denied', 403);
		}

		$this->keep_session_filters = $keep_session_filters;
		$this->h_cache = new EmundusHelperCache();
		$this->m_users = new EmundusModelUsers();
		$this->config = $config;
		$this->user_campaigns = $this->m_users->getAllCampaignsAssociatedToUser($this->user->id);
		$this->user_programs = $this->m_users->getUserGroupsProgrammeAssoc($this->user->id, 'jesp.id');
		$this->setUsersFnumsAssoc();

		if (!$skip) {
			$this->setMenuParams();
			$this->setProfiles();
			$this->setDefaultFilters($config);
			$this->setFilters();

			$session_filters = $this->app->getSession()->get('em-applied-filters', null);
			if (!empty($session_filters)) {
				$this->addSessionFilters($session_filters);
				$this->checkFiltersAvailability();
			}
			
			$quick_search_filters = $this->app->getSession()->get('em-quick-search-filters', null);
			if (!empty($quick_search_filters)) {
				$this->setQuickSearchFilters($quick_search_filters);
			}

			$this->saveFiltersAllValues();

			if ($this->config['count_filter_values']) {
				require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';
				$helper_files = new EmundusHelperFiles();
				$this->applied_filters = $helper_files->setFiltersValuesAvailability($this->applied_filters);
			}
		}
	}

	public function getUserProgrammes() {
		return $this->user_programs;
	}

	public function getUserGroups() {
		return $this->user_groups;
	}

	private function setMenuParams() {
		$menu = $this->app->getMenu();
        $active = $menu->getActive();
		if (!empty($active)) {
            $this->menu_params = $active->getParams();
        } else {
            // get default file menu of current user profile
            $profile_id = $this->m_users->getCurrentUserProfile($this->user->id);

            if (!empty($profile_id)) {
                $db = Factory::getContainer()->get('DatabaseDriver');
                $query = $db->getQuery(true);

                $query->select('menu.id')
                    ->from($db->quoteName('#__menu', 'menu'))
                    ->leftJoin($db->quoteName('#__emundus_setup_profiles', 'profile') . ' ON ' . $db->quoteName('profile.menutype') . ' = ' . $db->quoteName('menu.menutype'))
                    ->where('profile.id = '. $db->quote($profile_id))
                    ->andWhere('menu.published = 1')
                    ->andWhere('menu.link LIKE "%index.php?option=com_emundus&view=files%"');

                $db->setQuery($query);
                $menu_id = $db->loadResult();

                // get menu params
                if (!empty($menu_id)) {
                    $menu = $menu->getItem($menu_id);
                    $this->menu_params = $menu->getParams();
                }
            }
        }

        if (empty($this->menu_params)) {
            throw new Exception('Menu params not found', 404);
        }
	}

	private function setUsersFnumsAssoc()
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('DISTINCT eua.fnum')
			->from($db->quoteName('#__emundus_users_assoc', 'eua'))
			->where('eua.user_id = ' . $db->quote($this->user->id))
			->andWhere('eua.action_id = 1')
			->andWhere('eua.r = 1');

		try {
			$db->setQuery($query);
			$this->user_fnum_assocs = $db->loadColumn();
		} catch (Exception $e) {
			Log::add('Failed to get user fnums assoc : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
		}

		if (!empty($this->user_fnum_assocs))
		{
			// get fnums distinct campaigns
			$query->clear()
				->select('DISTINCT campaign_id')
				->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->where('ecc.fnum IN (' . implode(',', $db->quote($this->user_fnum_assocs)) . ')');

			try {
				$db->setQuery($query);
				$campaigns = $db->loadColumn();
				$this->user_campaigns = array_merge($this->user_campaigns, $campaigns);
			} catch (Exception $e) {
				Log::add('Failed to get user campaigns assoc : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
			}
		}

		// assoc can be done on groups too
		// select all files that are linked to users groups
		$query->clear()
			->select('DISTINCT eg.group_id')
			->from($db->quoteName('#__emundus_groups', 'eg'))
			->leftJoin($db->quoteName('#__emundus_acl', 'acl') . ' ON ' . $db->quoteName('acl.group_id') . ' = ' . $db->quoteName('eg.group_id'))
			->where('eg.user_id = ' . $db->quote($this->user->id))
			->andWhere('acl.action_id = 1')
			->andWhere('acl.r = 1');

		$db->setQuery($query);
		$user_groups = $db->loadColumn();

		if (!empty($user_groups)) {
			$query->clear()
				->select('DISTINCT ecc.campaign_id')
				->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($db->quoteName('#__emundus_group_assoc', 'ega') . ' ON ' . $db->quoteName('ega.fnum') . ' = ' . $db->quoteName('ecc.fnum') . ' AND action_id = 1 AND r = 1')
				->where('ega.group_id IN (' . implode(',', $db->quote($user_groups)) . ')');

			$db->setQuery($query);
			$campaigns = $db->loadColumn();

			if (!empty($campaigns)) {
				$this->user_campaigns = array_merge($this->user_campaigns, $campaigns);
			}
		}

		if (!empty($this->user_campaigns)) {
			// get fnums distinct programs
			$query->clear()
				->select('DISTINCT jesp.id')
				->from($db->quoteName('#__emundus_setup_programmes', 'jesp'))
				->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $db->quoteName('esc.training') . ' = ' . $db->quoteName('jesp.code'))
				->where('esc.id IN (' . implode(',', $db->quote($this->user_campaigns)) . ')');

			try {
				$db->setQuery($query);
				$programs = $db->loadColumn();
				$this->user_programs = array_merge($this->user_programs, $programs);
			} catch (Exception $e) {
				JLog::add('Failed to get user programs assoc : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.filters.error');
			}
		}
	}

	private function setProfiles()
	{
		if (!empty($this->user_campaigns)) {
			$this->profiles = $this->getProfilesFromCampaignId($this->user_campaigns);
		}
	}

	private function getProfilesFromCampaignId($campaign_ids)
	{
		$profile_ids = [];

		if (!empty($campaign_ids)) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			// profiles from campaigns
			$query->select('DISTINCT profile_id')
				->from('#__emundus_setup_campaigns')
				->where('id IN (' . implode(',', $db->quote($campaign_ids)) . ')');

			$db->setQuery($query);
			$profiles = $db->loadColumn();
			foreach ($profiles as $profile) {
				if (!in_array($profile, $profile_ids)) {
					$profile_ids[] = $profile;
				}
			}

			$query->clear()
				->select('esp.id')
				->from($db->quoteName('#__emundus_setup_programmes', 'esp'))
				->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.training = esp.code')
				->where('esc.id IN (' . implode(',', $campaign_ids) . ')');

			try {
				$db->setQuery($query);
				$programs = $db->loadColumn();
			} catch (Exception $e) {
				Log::add('Failed to get programmes associated to campaign ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
			}

			if (!empty($programs)) {
				// profiles from workflows
				require_once(JPATH_SITE . '/components/com_emundus/models/workflow.php');
				$m_workflow = new EmundusModelWorkflow();
				$workflows = $m_workflow->getWorkflows([], 0, 0, $programs);

				foreach ($workflows as $workflow) {
					$data = $m_workflow->getWorkflow($workflow->id);

					foreach($data['steps'] as $step) {
						if (!empty($step->profile_id) && !in_array($step->profile_id, $profile_ids)) {
							$profile_ids[] = $step->profile_id;
						}
					}
				}
			}
		}

		return $profile_ids;
	}

	private function getProfiles()
	{
		return $this->profiles;
	}

	protected function setFilters(): void
	{
		$elements      = $this->getAllAssociatedElements();
		$this->filters = $this->createFiltersFromFabrikElements($elements);
	}

	protected function getAllAssociatedElements($element_id = null): array
	{
		$elements = [];
		$profiles = $this->getProfiles();
        $profile_form_ids = [];
        $config_form_ids = [];
		$more_campaign_form_ids = [];

		if (!empty($profiles)) {
			// get all forms associated to the user's profiles
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('menu.link')
				->from($db->quoteName('#__menu', 'menu'))
				->leftJoin($db->quoteName('#__emundus_setup_profiles', 'profile') . ' ON ' . $db->quoteName('profile.menutype') . ' = ' . $db->quoteName('menu.menutype'))
				->where('profile.id IN ('. implode(',', $db->quote($profiles)) .')')
				->andWhere('profile.published = 1')
				->andWhere('menu.link LIKE "index.php?option=com_fabrik&view=form&formid=%"')
				->andWhere('menu.published = 1');

			$db->setQuery($query);
			$form_links = $db->loadColumn();

			if (!empty($form_links)) {
				foreach ($form_links as $link) {
                    $profile_form_ids[] = (int) str_replace('index.php?option=com_fabrik&view=form&formid=', '', $link);
				}
            }
		}

        if (!empty($this->config) && !empty($this->config['more_fabrik_forms'])) {
            $config_form_ids = $this->config['more_fabrik_forms'];
        }

		if(!class_exists('EmundusModelCampaign')) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/campaign.php');
		}
		$m_campaign = new EmundusModelCampaign();
		$more_campaign_form = $m_campaign->getCampaignMoreForm();
		if(!empty($more_campaign_form) && !empty($more_campaign_form['form_id'])) {
			$more_campaign_form_ids[] = $more_campaign_form['form_id'];
			$this->config['more_fabrik_forms'][] = $more_campaign_form['form_id'];
		}

        $form_ids = array_merge($profile_form_ids, $config_form_ids, $more_campaign_form_ids);

		$unsorted_elements = $this->getElementsFromFabrikForms($form_ids);

		foreach ($form_ids as $form_id) {
			foreach ($unsorted_elements as $element) {
				if ($element['element_form_id'] == $form_id) {
					$elements[] = $element;
				}
			}
		}

		return $elements;
	}

    private function getElementsFromFabrikForms($form_ids)
    {
	    require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
	    $h_fabrik = new EmundusHelperFabrik();
	    return $h_fabrik->getElementsFromFabrikForms($form_ids, ['panel', 'display']);
    }

	private function setDefaultFilters($config)
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$filter_menu_values           = $this->menu_params->get('em_filters_values', '');
		$filter_menu_values           = explode(',', $filter_menu_values);
		$filter_menu_values_are_empty = empty($filter_menu_values);
		$filter_names                 = [];

		if (!$filter_menu_values_are_empty)
		{
			$filter_names = $this->menu_params->get('em_filters_names', '');
			$filter_names = explode(',', $filter_names);
		}

		if ($config['filter_status'])
		{
			$query->select('id, step, value, 0 as count')
				->from('#__emundus_setup_status');

			if (!$filter_menu_values_are_empty)
			{
				$position = array_search('status', $filter_names);

				if ($position !== false && isset($filter_menu_values[$position]) && $filter_menu_values[$position] !== '')
				{
					$statuses = explode('|', $filter_menu_values[$position]);

					if (!empty($statuses))
					{
						$query->where('step IN (' . implode(',', $statuses) . ')');
					}
				}
			}

			$query->order('ordering ASC');

			try
			{
				$db->setQuery($query);
				$statuses = $db->loadObjectList();
			}
			catch (Exception $e)
			{
				Log::add('Failed to get statuses : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
				throw new Exception('Failed to get statuses ', 500);
			}

			$values = [];
			foreach ($statuses as $status)
			{
				$values[] = ['value' => $status->step, 'label' => $status->value];
			}

			$this->applied_filters[] = [
				'uid'       => 'status',
				'id'        => 'status',
				'label'     => Text::_('MOD_EMUNDUS_FILTERS_STATUS'),
				'type'      => 'select',
				'values'    => $values,
				'value'     => ['all'],
				'default'   => true,
				'available' => true,
				'order'     => $config['filter_status_order']
			];
		}

		if ($config['filter_programs'])
		{
			$programs       = [];
			$programs_codes = [];

			if (!empty($this->user_programs))
			{
				$label = 'label';

				if ($config['filter_programs_display_category']) {
					$label = 'CONCAT(label, " (" , programmes, ")")';
				}

				$query->clear()
					->select('id as value, ' . $label . ' as label, 0 as count')
					->from('#__emundus_setup_programmes')
					->where('published = 1')
					->andWhere('id IN (' . implode(',', $this->user_programs) . ')');

				if (!$filter_menu_values_are_empty)
				{
					$position = array_search('programme', $filter_names);

					if ($position !== false && !empty($filter_menu_values[$position]))
					{
						$programs_codes = explode('|', $filter_menu_values[$position]);
						if (!empty($programs_codes))
						{
							$query->where('code IN (' . implode(',', $db->quote($programs_codes)) . ')');
						}
					}
				}

				$query->order('ordering ASC');

				try
				{
					$db->setQuery($query);
					$programs = $db->loadAssocList();
				}
				catch (Exception $e)
				{
					Log::add('Failed to get programs : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
					throw new Exception('Failed to get programs ', 500);
				}
			}

			$this->applied_filters[] = [
				'uid'       => 'programs',
				'id'        => 'programs',
				'label'     => Text::_('MOD_EMUNDUS_FILTERS_PROGRAMS'),
				'type'      => 'select',
				'values'    => $programs,
				'value'     => ['all'],
				'default'   => true,
				'available' => true,
				'order'     => $config['filter_programs_order']
			];
		}

		if ($config['filter_campaign'])
		{
			$campaigns = [];

			if (!empty($this->user_campaigns))
			{
				$label = 'esc.label';

				if ($config['filter_campaign_display_program']) {
					$label = 'CONCAT(esc.label, " (", esp.label, ")")';
				}

				$query->clear()
					->select('esc.id as value, ' . $label .' as label, 0 as count')
					->from($db->quoteName('#__emundus_setup_campaigns', 'esc'));

				if ($config['filter_campaign_display_program'])
				{
					$query->leftJoin($db->quoteName('#__emundus_setup_programmes', 'esp') .  ' ON ' . $db->quoteName('esc.training') . ' = ' . $db->quoteName('esp.code'));
				}

				$query->where($db->quoteName('esc.published') . ' = 1')
					->andWhere($db->quoteName('esc.id') . ' IN (' . implode(',', $this->user_campaigns) . ')');

				if (!$filter_menu_values_are_empty)
				{
					$position = array_search('campaign', $filter_names);

					if ($position !== false && !empty($filter_menu_values[$position]))
					{
						$campaigns = explode('|', $filter_menu_values[$position]);
						if (!empty($campaigns))
						{
							$query->andWhere('esc.id IN (' . implode(',', $campaigns) . ')');
						}
					}
				}

				if (!empty($programs_codes))
				{
					$query->andWhere('esc.training IN (' . implode(',', $db->quote($programs_codes)) . ')');
				}

				$query->order('esc.id DESC');

				try
				{
					$db->setQuery($query);
					$campaigns = $db->loadAssocList();
				}
				catch (Exception $e)
				{
					Log::add('Failed to get campaigns : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
					throw new Exception('Failed to get campaigns', 500);
				}
			}

			$this->applied_filters[] = [
				'uid'       => 'campaigns',
				'id'        => 'campaigns',
				'label'     => Text::_('MOD_EMUNDUS_FILTERS_CAMPAIGNS'),
				'type'      => 'select',
				'values'    => $campaigns,
				'value'     => ['all'],
				'default'   => true,
				'available' => true,
				'order'     => $config['filter_campaigns_order']
			];
		}

		if ($config['filter_years'])
		{
			$years = [];

			if (!empty($this->user_campaigns))
			{
				$query->clear()
					->select('DISTINCT year as value, year as label, 0 as count')
					->from('#__emundus_setup_campaigns')
					->where('published = 1')
					->andWhere('id IN (' . implode(',', $this->user_campaigns) . ')');

				$db->setQuery($query);
				$years = $db->loadAssocList();
			}

			$this->applied_filters[] = [
				'uid'       => 'years',
				'id'        => 'years',
				'label'     => Text::_('MOD_EMUNDUS_FILTERS_YEARS'),
				'type'      => 'select',
				'values'    => $years,
				'value'     => ['all'],
				'default'   => true,
				'available' => true,
				'order'     => $config['filter_years_order']
			];
		}

		if ($config['filter_tags'])
		{
			$query->clear()
				->select('id as value, label, 0 as count')
				->from('#__emundus_setup_action_tag');

			$db->setQuery($query);
			$tags = $db->loadAssocList();

			$this->applied_filters[] = [
				'uid'            => 'tags',
				'id'             => 'tags',
				'label'          => Text::_('MOD_EMUNDUS_FILTERS_TAGS'),
				'type'           => 'select',
				'values'         => $tags,
				'value'          => ['all'],
				'default'        => true,
				'available'      => true,
				'order'          => $config['filter_tags_order'],
				'andorOperator'  => 'OR',
				'andorOperators' => ['OR', 'AND']
			];
		}

		if ($config['filter_published'])
		{
			$this->applied_filters[] = [
				'uid'       => 'published',
				'id'        => 'published',
				'label'     => Text::_('MOD_EMUNDUS_FILTERS_PUBLISHED_STATE'),
				'type'      => 'select',
				'values'    => [
					['value' => 1, 'label' => Text::_('MOD_EMUNDUS_FILTERS_VALUE_PUBLISHED'), 'count' => 0],
					['value' => 0, 'label' => Text::_('MOD_EMUNDUS_FILTERS_VALUE_ARCHIVED'), 'count' => 0],
					['value' => -1, 'label' => Text::_('MOD_EMUNDUS_FILTERS_VALUE_DELETED'), 'count' => 0]
				],
				'value'     => [1],
				'default'   => true,
				'available' => true,
				'order'     => $config['filter_published_order'],
				'operator'  => '='
			];
		}

		if ($config['filter_groups'])
		{
			$query->clear()
				->select('DISTINCT id as value, label')
				->from('#__emundus_setup_groups')
				->where('published = 1');

			$db->setQuery($query);
			$groups = $db->loadAssocList();

			$this->applied_filters[] = [
				'uid'            => 'group_assoc',
				'id'             => 'group_assoc',
				'label'          => Text::_('MOD_EMUNDUS_FILTERS_GROUP_ASSOC'),
				'type'           => 'select',
				'values'         => $groups,
				'value'          => ['all'],
				'default'        => true,
				'available'      => true,
				'order'          => $config['filter_groups_order'],
				'andorOperator'  => 'OR',
				'andorOperators' => ['OR', 'AND']
			];
		}

		if ($config['filter_users'])
		{
			$query->clear()
				->select('ju.id as value, CONCAT(ju.name, " ", ju.email ) as label')
				->from('#__users as ju')
				->leftJoin('#__emundus_users_assoc as jeua ON ju.id = jeua.user_id')
				->leftJoin('#__emundus_campaign_candidature AS jecc ON jeua.fnum = jecc.fnum')
				->leftJoin('#__emundus_setup_campaigns AS jesc ON jecc.campaign_id = jesc.id')
				->leftJoin('#__emundus_setup_programmes AS jesp ON jesc.training = jesp.code')
				->where('jeua.action_id = 1')
				->andWhere('jecc.campaign_id IN ' . '(' . implode(',', $this->user_campaigns) . ') OR jesp.id IN ' . '(' . implode(',', $this->user_programs) . ')')
				->andWhere('ju.block = 0')
				->group('ju.id');

			try
			{
				$db->setQuery($query);
				$users = $db->loadAssocList();
			}
			catch (Exception $e)
			{
				Log::add('Failed to get users associated to profiles that current' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
			}

			$this->applied_filters[] = [
				'uid'            => 'users_assoc',
				'id'             => 'users_assoc',
				'label'          => Text::_('MOD_EMUNDUS_FILTERS_USERS_ASSOC'),
				'type'           => 'select',
				'values'         => $users,
				'value'          => ['all'],
				'default'        => true,
				'available'      => true,
				'order'          => $config['filter_users_order'],
				'andorOperator'  => 'OR',
				'andorOperators' => ['OR', 'AND']
			];
		}

		if ($config['filter_attachments'])
		{
			$attachments = [];

			$query->clear()
				->select('esa.id as value, esa.value as label')
				->from($db->quoteName('#__emundus_setup_attachments', 'esa'))
				->leftJoin($db->quoteName('#__emundus_setup_attachment_profiles', 'esap') . ' ON ' . $db->quoteName('esap.attachment_id') . ' = ' . $db->quoteName('esa.id'))
				->leftJoin($db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $db->quoteName('esp.id') . ' = ' . $db->quoteName('esap.profile_id'))
				->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $db->quoteName('esc.profile_id') . ' = ' . $db->quoteName('esp.id'))
				->leftJoin($db->quoteName('#__emundus_setup_programmes', 'espp') . ' ON ' . $db->quoteName('esc.training') . ' = ' . $db->quoteName('espp.code'))
				->where('esc.id IN ' . '(' . implode(',', $this->user_campaigns) . ') OR espp.id IN ' . '(' . implode(',', $this->user_programs) . ')')
				->group('esa.id');

			try
			{
				$db->setQuery($query);
				$attachments = $db->loadAssocList();
			}
			catch (Exception $e)
			{
				Log::add('Failed to get attachments associated to profiles that current' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
			}

			$this->applied_filters[] = [
				'uid'            => 'attachments',
				'id'             => 'attachments',
				'label'          => Text::_('MOD_EMUNDUS_FILTERS_ATTACHMENTS'),
				'type'           => 'select',
				'values'         => $attachments,
				'value'          => ['all'],
				'default'        => true,
				'available'      => true,
				'order'          => $config['filter_attachments_order'],
				'andorOperator'  => 'OR',
				'andorOperators' => ['OR', 'AND']
			];
		}

		if ($config['filter_steps']) {
			if (!class_exists('EmundusModelWorkflow')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new EmundusModelWorkflow();
			$workflows = $m_workflow->getWorkflows([], 0, 0, $this->user_programs);
			$steps = [];
			$values_selected = [];
			$steps_selected = [];

			foreach($workflows as $workflow) {
				$workflow_data = $m_workflow->getWorkflow($workflow->id);

				if (!empty($workflow_data['steps'])) {
					foreach($workflow_data['steps'] as $step) {
						if ($m_workflow->isEvaluationStep($step->type)) {
							$action_id = $m_workflow->getStepAssocActionId($step->id);
							if (EmundusHelperAccess::asAccessAction($action_id, 'r', $this->user->id) || EmundusHelperAccess::asAccessAction($action_id, 'c', $this->user->id)) {
								$steps[] = ['value' => $step->id, 'label' => $workflow->label . ' - ' . $step->label];

								if (EmundusHelperAccess::asAccessAction($action_id, 'c', $this->user->id)) {
									$steps_selected[] = $step->id;
								}
							}
						}
					}
				}
			}

			if (!empty($steps_selected) && sizeof($steps) != $steps_selected) {
				$values_selected = $steps_selected;
			}

			if (!$filter_menu_values_are_empty)
			{
				$position = array_search('workflow_steps', $filter_names);

				if ($position !== false && !empty($filter_menu_values[$position]))
				{
					$steps_selected = explode('|', $filter_menu_values[$position]);

					if (!empty($steps_selected))
					{
						$steps_selected = array_map('intval', $steps_selected);
						$steps = array_filter($steps, function($step) use ($steps_selected) {
							return in_array($step['value'], $steps_selected);
						});
						$steps = array_values($steps);

						$values_selected = array_intersect($steps_selected, array_column($steps, 'value'));
						$values_selected = array_values($values_selected);
					}
				}
			}

			$this->applied_filters[] = [
				'uid'            => 'workflow_steps',
				'id'             => 'workflow_steps',
				'label'          => Text::_('MOD_EMUNDUS_FILTERS_WORKFLOW_STEPS'),
				'type'           => 'select',
				'values'         => $steps,
				'value'          => !empty($steps_selected) && !empty($values_selected) ? $values_selected : [],
				'default'        => true,
				'available'      => true,
				'order'          => $config['filter_steps_order'],
				'andorOperator'  => 'OR',
				'andorOperators' => ['OR'],
				'operator'       => 'IN',
				'operators'      => ['IN']
			];
		}

		if ($config['filter_evaluated']) {
			$evaluated_default_value = null;
			if (!$filter_menu_values_are_empty)
			{
				$position = array_search('evaluated', $filter_names);

				if ($position !== false && isset($filter_menu_values[$position]) && $filter_menu_values[$position] !== '')
				{
					$evaluated = explode('|', $filter_menu_values[$position]);
					if (!empty($evaluated))
					{
						$evaluated = array_map('intval', $evaluated);
						$evaluated = array_filter($evaluated, function($value) {
							return $value === 1 || $value === 0;
						});

						$evaluated_default_value = $evaluated[0];
					}
				}
			}

			$this->applied_filters[] = [
				'uid'            => 'evaluated',
				'id'             => 'evaluated',
				'label'          => Text::_('MOD_EMUNDUS_FILTERS_WORKFLOW_EVALUATION_STATE'),
				'type'           => 'select',
				'values'         => [
					['value' => 1, 'label' => Text::_('MOD_EMUNDUS_FILTERS_VALUE_EVALUATED')],
					['value' => 0, 'label' => Text::_('MOD_EMUNDUS_FILTERS_VALUE_TO_EVALUATE')]
				],
				'value'          => !is_null($evaluated_default_value) ? [$evaluated_default_value] : [],
				'default'        => true,
				'available'      => true,
				'order'          => $config['filter_evaluated_order'],
				'andorOperator'  => 'OR',
				'andorOperators' => [],
				'operator'       => 'IN',
				'operators'      => ['IN', 'NOT IN']
			];
		}

		if ($config['filter_evaluators']) {
			$evaluators = [];
			if (!class_exists('EmundusModelWorkflow')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new EmundusModelWorkflow();
			$steps = $m_workflow->getEvaluatorSteps($this->user->id);

			$at_least_one = false;
			foreach($steps as $step)
			{
				if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) || EmundusHelperAccess::asAccessAction($step->action_id, 'u', $this->user->id))
				{
					$at_least_one = true;

					$query->clear()
						->select('DISTINCT ju.id as value, CONCAT(ju.name, " ", ju.email ) as label')
						->from('#__users as ju')
						->leftJoin($step->table . ' AS eval_table ON eval_table.evaluator = ju.id')
						->where('eval_table.step_id = ' . $step->id);

					try
					{
						$db->setQuery($query);
						$evaluators = array_merge($evaluators, $db->loadAssocList());
					}
					catch (Exception $e)
					{
						Log::add('Failed to get evaluators associated to profiles that current' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
					}
				}
			}

			if ($at_least_one) {
				$this->applied_filters[] = [
					'uid'            => 'evaluators',
					'id'             => 'evaluators',
					'label'          => Text::_('MOD_EMUNDUS_FILTERS_EVALUATORS'),
					'type'           => 'select',
					'values'         => $evaluators,
					'value'          => [],
					'default'        => true,
					'available'      => true,
					'order'          => $config['filter_steps_order'],
					'andorOperator'  => 'OR',
					'andorOperators' => ['OR'],
					'operator'       => 'IN',
					'operators'      => ['IN', 'NOT IN']
				];
			}
		}

		$session = $this->app->getSession();

		if (!empty($config['more_filter_elements']))
		{
			foreach ($config['more_filter_elements'] as $more_filter)
			{
				if (!empty($more_filter['fabrik_element_id']))
				{
					$found              = false;
					$new_default_filter = [];
					foreach ($this->filters as $filter)
					{
						if ($filter['id'] == $more_filter['fabrik_element_id'])
						{
							$new_default_filter            = $filter;
							$new_default_filter['default'] = true;
							if (empty($new_default_filter['value']))
							{
								$new_default_filter['value'] = $new_default_filter['type'] === 'select' ? ['all'] : '';
							}
							$new_default_filter['andorOperator'] = 'OR';
							$new_default_filter['operator']      = '=';

							if ($new_default_filter['type'] === 'select')
							{
								$new_default_filter['operator'] = 'IN';
								$new_default_filter['values']   = $this->getFabrikElementValuesFromElementId($filter['id']);
							}
							$found = true;
							break;
						}
					}

					if (!$found)
					{
						$query->clear()
							->select('jfe.id, jfe.plugin, jfe.label, jfe.params, jffg.form_id as element_form_id, jff.label as element_form_label')
							->from('jos_fabrik_elements as jfe')
							->join('inner', 'jos_fabrik_formgroup as jffg ON jfe.group_id = jffg.group_id')
							->join('inner', 'jos_fabrik_forms as jff ON jffg.form_id = jff.id')
							->where('jfe.id = ' . $more_filter['fabrik_element_id'])
							->andWhere('jfe.published = 1');

						$db->setQuery($query);
						$element = $db->loadAssoc();

						if (!empty($element))
						{
							$element['label']              = Text::_($element['label']);
							$element['element_form_label'] = Text::_($element['element_form_label']);
							$formatted_elements            = $this->createFiltersFromFabrikElements([$element]);

							if (!empty($formatted_elements))
							{
								$new_default_filter            = $formatted_elements[0];
								$new_default_filter['default'] = true;
								if (empty($new_default_filter['value']))
								{
									$new_default_filter['value'] = $new_default_filter['type'] === 'select' ? ['all'] : '';
								}
								$new_default_filter['andorOperator'] = 'OR';
								$new_default_filter['operator']      = '=';

								if ($new_default_filter['type'] === 'select')
								{
									$new_default_filter['operator'] = 'IN';
									$new_default_filter['values']   = $this->getFabrikElementValuesFromElementId($element['id']);
								}
								else
								{
									if ($new_default_filter['type'] === 'text')
									{
										$new_default_filter['operator'] = 'LIKE';
									}
								}
							}
							$new_default_filter['plugin'] = $element['plugin'];
						}
					}

					if (!empty($new_default_filter))
					{
						$this->filters[]             = $new_default_filter;
						$new_default_filter['uid']   = 'default-filter-' . $new_default_filter['id'];
						$new_default_filter['order'] = $more_filter['order'];
						$this->applied_filters[]     = $new_default_filter;

						// add filter to adv cols
						$files_displayed_columns = $session->get('adv_cols');
						if (!empty($files_displayed_columns))
						{
							$files_displayed_columns[] = $new_default_filter['id'];
						}
						else
						{
							$files_displayed_columns = [$new_default_filter['id']];
						}
						$session->set('adv_cols', $files_displayed_columns);
					}
				}
			}
		}

		// sort applied filters array by array entry 'order'
		usort($this->applied_filters, function ($a, $b) {
			return intval($a['order']) <=> intval($b['order']);
		});

		if ($this->h_cache->isEnabled() && !empty($active_menu))
		{
			$this->h_cache->set('em_default_filters_' . $active_menu->id, $this->applied_filters);
		}

		$session_filters = $session->get('em-applied-filters', []);
		if (((isset($config['force_reload_on_refresh']) && $config['force_reload_on_refresh']) && !$this->keep_session_filters) || empty($session_filters)) {
			if (!empty($session_filters)) {
				$filters_to_keep = array_filter($session_filters, function ($session_filter) {
					return isset($session_filter['menuFilter']) && $session_filter['menuFilter'];
				});

				if (!empty($filters_to_keep)) {
					$this->addSessionFilters($filters_to_keep);
				}
			}

			$session->set('em-applied-filters', $this->applied_filters);
		}
	}

	private function addSessionFilters($session_values)
	{
		foreach ($session_values as $session_filter) {
			$found = false;
			foreach ($this->applied_filters as $key => $applied_filter) {
				if ($applied_filter['uid'] == $session_filter['uid']) {
					$this->applied_filters[$key]['value'] = $session_filter['value'];
					$this->applied_filters[$key]['operator'] = $session_filter['operator'];
					$this->applied_filters[$key]['andorOperator'] = $session_filter['andorOperator'];

					if (isset($session_filter['menuFilter'])){
						$this->applied_filters[$key]['menuFilter'] = $session_filter['menuFilter'];
					}

					$found = true;
					break;
				}
			}

			if (!$found) {
				// find filter in filters
				foreach ($this->filters as $i_filter => $filter) {
					if ($filter['id'] == $session_filter['id']) {
						if ($filter['type'] == 'select' && empty($filter['values'])) {
							$filter['values'] = $this->getFabrikElementValuesFromElementId($filter['id']);
							$this->filters[$i_filter] = $filter;
						}
						$new_filter = $filter;

						$new_filter['value'] = $session_filter['value'];
						$new_filter['operator'] = $session_filter['operator'];
						$new_filter['andorOperator'] = $session_filter['andorOperator'];
						$new_filter['uid'] = $session_filter['uid'];

						if (isset($session_filter['menuFilter'])) {
							$new_filter['menuFilter'] = $session_filter['menuFilter'];
						}

						$this->applied_filters[] = $new_filter;
						break;
					}
				}
			}
		}

		$session = $this->app->getSession();
		if(!empty($this->applied_filters))
		{
			$session->set('em-applied-filters', $this->applied_filters);
		}
	}

    private function checkFiltersAvailability()
    {
        $campaign_availables = $this->user_campaigns;

        $campaign_filter = null;
        $program_filter = null;
        foreach($this->applied_filters as $filter) {
            if($filter['uid'] == 'campaigns') {
                $campaign_filter = $filter;
            } else if ($filter['uid'] == 'programs') {
                $program_filter = $filter;
            }
        }

        if (!empty($campaign_filter) && !empty($campaign_filter['value'])) {
			if (is_array($campaign_filter['value']) && in_array('all', $campaign_filter['value'])) {
				// stop here, all campaigns are selected
			} else {
				// if the operator is NOT IN or !=, we need to get fabrik elements associated to campaigns that are not in the filter
				switch($campaign_filter['operator']) {
					case 'NOT IN':
					case '!=':
						$campaign_availables = array_diff($this->user_campaigns, $campaign_filter['value']);
						break;
					default:
						$campaign_availables = array_intersect($this->user_campaigns, $campaign_filter['value']);
						break;
				}

				$campaign_availables = array_values($campaign_availables);
			}
        }

        if (!empty($program_filter) && !empty($program_filter['value'])) {
	        if (is_array($program_filter['value']) && in_array('all', $program_filter['value'])) {
		        // stop here, all campaigns are selected
	        } else {
		        // get campaigns associated to programs
		        $db = Factory::getContainer()->get('DatabaseDriver');
		        $query = $db->getQuery(true);

		        $query->select('DISTINCT esc.id')
			        ->from($db->quoteName('#__emundus_setup_campaigns', 'esc'))
			        ->join('INNER', $db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON (' . $db->quoteName('esc.training') . ' = ' . $db->quoteName('esp.code') . ')')
			        ->where($db->quoteName('esp.id') . ' IN (' . implode(',', $program_filter['value']) . ')')
			        ->where('esc.published = 1')
			        ->andWhere('esc.id IN (' . implode(',', $this->user_campaigns) . ')');

		        $db->setQuery($query);
		        $campaigns_of_program = $db->loadColumn();

		        if (!empty($campaigns_of_program)) {
			        // if the operator is NOT IN or !=, we need to get fabrik elements associated to campaigns that are not in the filter
			        switch($program_filter['operator']) {
				        case 'NOT IN':
				        case '!=':
					        $campaign_availables = array_diff($this->user_campaigns, $campaigns_of_program);
					        break;
				        default:
					        $campaign_availables = array_intersect($this->user_campaigns, $campaigns_of_program);
					        break;
			        }
		        }
	        }
		}

	    if (!empty($campaign_availables)) {
		    $filtered_profiles = $this->getProfilesFromCampaignId($campaign_availables);

		    if (!empty($filtered_profiles)) {
			    $element_ids_available = $this->getElementIdsAssociatedToProfile($filtered_profiles);

					$config_more_fabrik_forms = $this->config['more_fabrik_forms'];
					$config_more_fabrik_forms = empty($config_more_fabrik_forms) ? [] : $config_more_fabrik_forms;

					foreach($this->filters as $key => $filter) {
						if (!in_array($filter['id'], $element_ids_available) && !in_array($filter['group_id'], $config_more_fabrik_forms)  && !in_array($filter['form_id'], $config_more_fabrik_forms)) {
							$this->filters[$key]['available'] = false;
						}
					}
		    }
	    }
    }

	private function getElementIdsAssociatedToProfile($profile_ids)
    {
        $element_ids = [];

        if (!empty($profile_ids)) {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true);

            $menus = [];
            $form_links = [];
            foreach ($profile_ids as $profile) {
                if ($this->h_cache->isEnabled()) {
                    $profile_form_links = $this->h_cache->get('em-filters-profile-' . $profile . '-form');

                    if (!empty($profile_form_links)) {
                        $form_links = array_merge($form_links, $profile_form_links);
                        continue;
                    }
                }

                $menus[] = 'menu-profile' . $profile;
            }

            if (!empty($menus)) {
                if ($this->h_cache->isEnabled()) {
                    $query->select('link, menutype')
                        ->from('#__menu')
                        ->where('menutype IN (' . implode(',', $db->quote($menus)) . ')')
                        ->andWhere('link LIKE "index.php?option=com_fabrik&view=form&formid=%"')
                        ->andWhere('published = 1');

                    $db->setQuery($query);
                    $form_menus = $db->loadAssocList();

                    $form_links_by_profile = [];

                    foreach($form_menus as $form_menu) {
                        $profile_id = (int)str_replace('menu-profile', '', $form_menu['menutype']);
                        $form_links[] = $form_menu['link'];
                        $form_links_by_profile[$profile_id][] = $form_menu['link'];
                    }

                    foreach ($profile_ids as $profile) {
                        if (!empty($form_links_by_profile[$profile])) {
                            $this->h_cache->set('em-filters-profile-' . $profile . '-form', $form_links_by_profile[$profile]);
                        }
                    }
                } else {
                    $query->select('link')
                        ->from('#__menu')
                        ->where('menutype IN (' . implode(',', $db->quote($menus)) . ')')
                        ->andWhere('link LIKE "index.php?option=com_fabrik&view=form&formid=%"')
                        ->andWhere('published = 1');

                    $db->setQuery($query);
                    $form_links = $db->loadColumn();
                }
            }

            if (!empty($form_links)) {
                $form_ids = [];
                foreach ($form_links as $link) {
                    $form_ids[] = (int)str_replace('index.php?option=com_fabrik&view=form&formid=', '', $link);
                }
                $form_ids = array_unique($form_ids);

                if ($this->h_cache->isEnabled()) {
                    $form_ids_cached = [];
                    foreach ($form_ids as $form_id) {
                        $form_element_ids = $this->h_cache->get('em-filters-form-' . $form_id . '-element');

                        if (!empty($form_element_ids)) {
                            $element_ids = array_merge($element_ids, $form_element_ids);
                            $form_ids_cached[] = $form_id;
                        }
                    }

                    $form_ids = array_diff($form_ids, $form_ids_cached);
                    if (!empty($form_ids)) {
                        $query->clear()
                            ->select('jfe.id as element_id, jff.id as form_id')
                            ->from('jos_fabrik_elements as jfe')
                            ->join('inner', 'jos_fabrik_formgroup as jffg ON jfe.group_id = jffg.group_id')
                            ->join('inner', 'jos_fabrik_forms as jff ON jffg.form_id = jff.id')
                            ->where('jffg.form_id IN (' . implode(',', $form_ids) . ')')
                            ->andWhere('jfe.published = 1')
                            ->andWhere('jfe.hidden = 0');

                        try {
                            $db->setQuery($query);
                            $ids = $db->loadAssocList();

                            $element_ids_by_form = [];
                            foreach ($ids as $id) {
                                $element_ids_by_form[$id['form_id']][] = $id['element_id'];
                                $element_ids[] = $id['element_id'];
                            }

                            foreach ($form_ids as $form_id) {
                                if (!empty($element_ids_by_form[$form_id])) {
                                    $this->h_cache->set('em-filters-form-' . $form_id . '-elements', $element_ids_by_form[$form_id]);
                                }
                            }
                        } catch (Exception $e) {
                            Log::add('Failed to get elements associated to profiles that current user can access : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
                        }
                    }
                } else {
                    $query->clear()
                        ->select('jfe.id')
                        ->from('jos_fabrik_elements as jfe')
                        ->join('inner', 'jos_fabrik_formgroup as jffg ON jfe.group_id = jffg.group_id')
                        ->join('inner', 'jos_fabrik_forms as jff ON jffg.form_id = jff.id')
                        ->where('jffg.form_id IN (' . implode(',', $form_ids) . ')')
                        ->andWhere('jfe.published = 1')
                        ->andWhere('jfe.hidden = 0');

                    try {
                        $db->setQuery($query);
                        $element_ids = $db->loadColumn();
                    } catch (Exception $e) {
	                    Log::add('Failed to get elements associated to profiles that current user can access : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
                    }
                }
            }
        }

        return $element_ids;
    }
}