<?php

/**
 * @version     1.0.0
 * @package     com_emundus
 * @copyright   Copyright (C) 2015. Tous droits réservés.
 * @license     GNU General Public License version 2 ou version ultérieure ; Voir LICENSE.txt
 * @author      emundus <dev@emundus.fr> - http://www.emundus.fr
 */
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Emundus records.
 */
class EmundusModelJobs extends JModelList
{

	/**
	 * Constructor.
	 *
	 * @param   array    An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'a.id',
				'date_time', 'a.date_time',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'user', 'a.user',
				'etablissement', 'a.etablissement',
				'service', 'a.service',
				'intitule_poste', 'a.intitule_poste',
				'domaine', 'a.domaine',
				'nb_postes', 'a.nb_postes',

			);
		}
		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since    1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		// List state information
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);

		$limitstart = $app->input->getInt('limitstart', 0);
		$this->setState('list.start', $limitstart);

		if ($list = $app->getUserStateFromRequest($this->context . '.list', 'list', array(), 'array')) {
			foreach ($list as $name => $value) {
				// Extra validations
				switch ($name) {
					case 'fullordering':
						$orderingParts = explode(' ', $value);

						if (count($orderingParts) >= 2) {
							// Latest part will be considered the direction
							$fullDirection = end($orderingParts);

							if (in_array(strtoupper($fullDirection), array('ASC', 'DESC', ''))) {
								$this->setState('list.direction', $fullDirection);
							}

							unset($orderingParts[count($orderingParts) - 1]);

							// The rest will be the ordering
							$fullOrdering = implode(' ', $orderingParts);

							if (in_array($fullOrdering, $this->filter_fields)) {
								$this->setState('list.ordering', $fullOrdering);
							}
						}
						else {
							$this->setState('list.ordering', $ordering);
							$this->setState('list.direction', $direction);
						}
						break;

					case 'ordering':
						if (!in_array($value, $this->filter_fields)) {
							$value = $ordering;
						}
						break;

					case 'direction':
						if (!in_array(strtoupper($value), array('ASC', 'DESC', ''))) {
							$value = $direction;
						}
						break;

					case 'limit':
						$limit = $value;
						break;

					// Just to keep the default case
					default:
						break;
				}

				$this->setState('list.' . $name, $value);
			}
		}

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$service = $app->getUserStateFromRequest($this->context . '.filter.service', 'filter_service');
		$this->setState('filter.service', $service);

		$etablissement = $app->getUserStateFromRequest($this->context . '.filter.etablissement', 'filter_etablissement');
		$this->setState('filter.etablissement', $etablissement);

		$domaine = $app->getUserStateFromRequest($this->context . '.filter.domaine', 'filter_domaine');
		$this->setState('filter.domaine', $domaine);

		// Receive & set filters
		if ($filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', array(), 'array')) {
			foreach ($filters as $name => $value) {
				$this->setState('filter.' . $name, $value);
			}
		}

		$ordering = $app->input->get('filter_order');
		if (!empty($ordering)) {
			$list             = $app->getUserState($this->context . '.list');
			$list['ordering'] = $app->input->get('filter_order');
			$app->setUserState($this->context . '.list', $list);
		}

		$orderingDirection = $app->input->get('filter_order_Dir');
		if (!empty($orderingDirection)) {
			$list              = $app->getUserState($this->context . '.list');
			$list['direction'] = $app->input->get('filter_order_Dir');
			$app->setUserState($this->context . '.list', $list);
		}

		$list = $app->getUserState($this->context . '.list');

		if (empty($list['ordering'])) {
			$list['ordering'] = 'ordering';
		}

		if (empty($list['direction'])) {
			$list['direction'] = 'asc';
		}

		$this->setState('list.ordering', $list['ordering']);
		$this->setState('list.direction', $list['direction']);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$user       = JFactory::getUser();
		$config     = JFactory::getConfig();
		$eMConfig   = JComponentHelper::getParams('com_emundus');
		$validation = $eMConfig->get('validation', '0');

		// Get the application date and set it to the timezone defined in settings
		$timezone = new DateTimeZone($config->get('offset'));
		$now      = JFactory::getDate()->setTimezone($timezone);

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query
			->select(
				$this->getState(
					'list.select', 'DISTINCT a.*'
				)
			);

		$query->from('`#__emundus_emploi_etudiant` AS a');

		// Join over the created by field 'user'
		$query->join('LEFT', '#__users AS user ON user.id = a.user');
		// Join over the foreign key 'etablissement'
		$query->select('#__categories_1753001.title AS categories_title_1753001');
		$query->join('LEFT', '#__categories AS #__categories_1753001 ON #__categories_1753001.id = a.etablissement');

		$query->select('esc.start_date, esc.end_date');
		$query->join('LEFT', '#__emundus_setup_campaigns AS esc ON esc.id = a.campaign_id');

		if (!JFactory::getUser()->guest) {
			$query->select('eeec.user as student_id, eeec.fnum, eeec.date_time');
			$query->join('LEFT', '#__emundus_emploi_etudiant_candidat AS eeec ON eeec.fiche_emploi = a.id and eeec.user=' . $user->id);

			$query->select('ess.step, ess.value as application_status,ess.class');
			$query->join('LEFT', '#__emundus_campaign_candidature AS ecc ON ecc.fnum = eeec.fnum');
			$query->join('LEFT', '#__emundus_setup_status AS ess ON ess.step = ecc.status');
		}

		if (!JFactory::getUser()->authorise('core.edit.state', 'com_emundus')) {
			//$query->where('a.valide_comite = 1');
			if ($validation == 1) {
				$query->where('a.valide = 1');
			}
			$query->where('a.published = 1');
			$query->where('a.state = 1');
			$query->where('a.date_limite >= "' . $now . '"');
			$query->where('esc.published = 1');
			$query->where('esc.start_date <= "' . $now . '"');
			$query->where('esc.end_date > "' . $now . '"');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else {
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( a.service LIKE ' . $search . '  OR  a.intitule_poste LIKE ' . $search . '  OR  a.domaine LIKE ' . $search . ' OR #__categories_1753001.title like ' . $search . ' )');
			}
		}

		// Filter by domain
		$domaine = $this->getState('filter.domaine');
		if (!empty($domaine)) {
			$domaine = $db->Quote($db->escape($domaine, true));
			$query->where(' a.domaine LIKE ' . $domaine);
		}

		// Filter by domain
		$service = $this->getState('filter.service');
		if (!empty($service)) {
			$service = $db->Quote('%' . $db->escape($service, true) . '%');
			$query->where(' a.service LIKE ' . $service);
		}

		//Filtering etablissement
		$filter_etablissement = $this->state->get("filter.etablissement");
		if ($filter_etablissement)
			$query->where("a.etablissement = '" . $db->escape($filter_etablissement) . "'");

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');
		if ($orderCol && $orderDirn && ($orderCol != 'step' && $user->guest)) {
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

//echo($query->__toString());

		return $query;
	}

	public function getItems()
	{
		$items = parent::getItems();
		foreach ($items as $item) {

			if (isset($item->etablissement) && $item->etablissement != '') {
				if (is_object($item->etablissement)) {
					$item->etablissement = JArrayHelper::fromObject($item->etablissement);
				}
				$values = (is_array($item->etablissement)) ? $item->etablissement : explode(',', $item->etablissement);

				$textValue = array();
				foreach ($values as $value) {
					$db    = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query
						->select($db->quoteName('title'))
						->from('`#__categories`')
						->where($db->quoteName('id') . ' = ' . $db->quote($db->escape($value)));
					$db->setQuery($query);
					$results = $db->loadObject();
					if ($results) {
						$textValue[] = $results->title;
					}
				}

				$item->etablissement = !empty($textValue) ? implode(', ', $textValue) : $item->etablissement;

			}
		}

		return $items;
	}

	/**
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 */
	protected function loadFormData()
	{
		$app              = JFactory::getApplication();
		$filters          = $app->getUserState($this->context . '.filter', array());
		$error_dateformat = false;
		foreach ($filters as $key => $value) {
			if (strpos($key, '_dateformat') && !empty($value) && !$this->isValidDate($value)) {
				$filters[$key]    = '';
				$error_dateformat = true;
			}
		}
		if ($error_dateformat) {
			$app->enqueueMessage(JText::_("COM_PRUEBA_SEARCH_FILTER_DATE_FORMAT"), "warning");
			$app->setUserState($this->context . '.filter', $filters);
		}

		return parent::loadFormData();
	}

	/**
	 * Checks if a given date is valid and in an specified format (YYYY-MM-DD)
	 *
	 * @param   string Contains the date to be checked
	 *
	 */
	private function isValidDate($date)
	{
		return preg_match("/^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/", $date) && date_create($date);
	}

	/**
	 * Get the filter form
	 *
	 * @return  JForm/false  the JForm object or false
	 *
	 */
	public function getFilterForm($data = array(), $loadData = true)
	{
		$form = null;

		// Try to locate the filter form automatically. Example: ContentModelArticles => "filter_articles"
		/*	if (empty($this->filterFormName))
			{
				$classNameParts = explode('Model', get_called_class());

				if (count($classNameParts) == 2)
				{
					$this->filterFormName = 'filter_' . strtolower($classNameParts[1]);
				}
			}
	*/
		if (!empty($this->filterFormName)) {
			// Get the form.
			$form = new JForm($this->filterFormName);
			$form->loadFile(dirname(__FILE__) . DS . 'forms' . DS . $this->filterFormName . '.xml');
			$filter_data = JFactory::getApplication()->getUserState($this->context, new stdClass);
			$form->bind($filter_data);
		}

		return $form;
	}

	/**
	 * Function to get the active filters
	 */
	public function getActiveFilters()
	{
		$activeFilters = false;

		if (!empty($this->filter_fields)) {
			for ($i = 0; $i < count($this->filter_fields); $i++) {
				$filterName = 'filter.' . $this->filter_fields[$i];

				if (property_exists($this->state, $filterName) && (!empty($this->state->{$filterName}) || is_numeric($this->state->{$filterName}))) {
					$activeFilters = true;
				}
			}
		}

		return $activeFilters;
	}

	private function getParameterFromRequest($paramName, $default = null, $type = 'string')
	{
		$variables = explode('.', $paramName);
		$input     = JFactory::getApplication()->input;

		$nullFound = false;
		if (count($variables) > 1) {
			$data = $input->get($variables[0], null, 'ARRAY');
		}
		else {
			$data = $input->get($variables[0], null, $type);
		}
		for ($i = 1; $i < count($variables) && !$nullFound; $i++) {
			if (isset($data[$variables[$i]])) {
				$data = $data[$variables[$i]];
			}
			else {
				$nullFound = true;
			}
		}

		return ($nullFound) ? $default : JFilterInput::getInstance()->clean($data, $type);

	}


}
