<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Tchooz\Repositories\ApplicationFile\StatusRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of files records.
 *
 * @since  1.6
 */
class FilesModel extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array                 $config   An optional associative array of configuration settings.
	 * @param   ?MVCFactoryInterface  $factory  The factory.
	 *
	 * @since   1.6
	 * @see     \Joomla\CMS\MVC\Controller\BaseController
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [];

			if (Associations::isEnabled()) {
				$config['filter_fields'][] = 'association';
			}
		}

		parent::__construct($config, $factory);
	}

	/**
	 * Get the filter form
	 *
	 * @param   array    $data      data
	 * @param   boolean  $loadData  load current data
	 *
	 * @return  \Joomla\CMS\Form\Form|null  The Form object or null if the form can't be found
	 *
	 * @since   3.2
	 */
	public function getFilterForm($data = [], $loadData = true)
	{
		$form = parent::getFilterForm($data, $loadData);

		return $form;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		$app   = Factory::getApplication();
		$input = $app->getInput();

		$forcedLanguage = $input->get('forcedLanguage', '', 'cmd');

		// Adjust the context to support modal layouts.
		if ($layout = $input->get('layout')) {
			$this->context .= '.' . $layout;
		}

		// Adjust the context to support forced languages.
		if ($forcedLanguage) {
			$this->context .= '.' . $forcedLanguage;
		}

		// Required content filters for the administrator menu
		//$this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');

		// List state information.
		parent::populateState($ordering, $direction);

		// Force a language
		if (!empty($forcedLanguage)) {
			$this->setState('filter.language', $forcedLanguage);
			$this->setState('filter.forcedLanguage', $forcedLanguage);
		}
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		//$id .= ':' . $this->getState('filter.search');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  QueryInterface
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);
		$user  = $this->getCurrentUser();

		$params = ComponentHelper::getParams('com_emundus');

		$query->select(
			$this->getState(
				'list.select',
				[
					'cc.id',
					'cc.fnum',
					'cc.status',
					'cc.applicant_id',
					'c.id as campaign_id',
					'p.id as program_id',
					//'GROUP_CONCAT(DISTINCT steps.profile_id) AS profile_ids',
				]
			)
		)
			->from($db->quoteName('#__emundus_campaign_candidature', 'cc'))
			->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'c') . ' ON ' . $db->quoteName('cc.campaign_id') . ' = ' . $db->quoteName('c.id'))
			->leftJoin($db->quoteName('#__emundus_setup_programmes', 'p') . ' ON ' . $db->quoteName('c.training') . ' = ' . $db->quoteName('p.code'));
			//->leftJoin($db->quoteName('#__emundus_setup_workflows_programs', 'wp') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('wp.program_id'))
			//->leftJoin($db->quoteName('#__emundus_setup_workflows_steps', 'steps') . ' ON ' . $db->quoteName('wp.workflow_id') . ' = ' . $db->quoteName('steps.workflow_id') . ' AND steps.profile_id is not null and steps.profile_id > 0');

		$published = (int) $this->getState('filter.published', 1);
		$query->where($db->quoteName('cc.published') . ' = ' . $db->quote($published));

		// Filter by status
		$status = $this->getState('filter.status');
		if ($status !== null && $status !== '') {
			$query->andWhere($db->quoteName('cc.status') . ' = ' . $db->quote($status));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'cc.id');
		$orderDirn = $this->state->get('list.direction', 'DESC');


		$ordering = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);

		$query->order($ordering);

		return $query;
	}

	/**
	 * Method to get a list of articles.
	 * Overridden to add item type alias.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$query = $this->getDatabase()->getQuery(true);

		if (!empty($items)) {
			$statusRepository = new StatusRepository();
			$statuses = $statusRepository->getAll();

			foreach ($items as $item) {
				$item->typeAlias = 'com_emundus.files';

				if (isset($item->metadata)) {
					$registry       = new Registry($item->metadata);
					$item->metadata = $registry->toArray();
				}

				// Add tags
				$query->clear()
					->select('esat.id, esat.label')
					->from($this->getDatabase()->quoteName('#__emundus_setup_action_tag', 'esat'))
					->leftJoin(
						$this->getDatabase()->quoteName('#__emundus_tag_assoc', 'eta')
						. ' ON ' . $this->getDatabase()->quoteName('esat.id') . ' = ' . $this->getDatabase()->quoteName('eta.id_tag')
					)
					->where($this->getDatabase()->quoteName('eta.fnum') . ' = ' . $this->getDatabase()->quote($item->fnum));
				$this->getDatabase()->setQuery($query);
				$item->stickers = $this->getDatabase()->loadObjectList();

				foreach ($statuses as $status) {
					if ($status->getStep() == $item->status) {
						$item->status = $status->__serialize();
						break;
					}
				}
			}
		}

		return $items;
	}
}
