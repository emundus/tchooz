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
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Repositories\Payment\TransactionRepository;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of article records.
 *
 * @since  1.6
 */
class TransactionsModel extends ListModel
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

		//TODO: Add relationships to the query and manage specific fields
		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				[
					$db->quoteName('transaction.id'),
				]
			)
		)
			->from($db->quoteName('#__emundus_payment_transaction', 'transaction'));

		$transaction_status = $this->getState('filter.status');
		try {
			if (!empty($transaction_status) && TransactionStatus::from($transaction_status)) {
				$query->where($db->quoteName('transaction.status') . ' = ' . $db->quote($transaction_status));
			}
		} catch (\ValueError $e) {
			// If the status is not valid, we ignore it.
			// This allows us to not filter by status if the status is not valid.
			// This is useful for API calls where the status is not always known.
		}

		$transaction_payment_method_id = $this->getState('filter.payment_method_id');
		if (!empty($transaction_payment_method_id)) {
			$query->where($db->quoteName('transaction.payment_method_id') . ' = ' . $db->quote($transaction_payment_method_id));
		}

		$transaction_synchronizer_id = $this->getState('filter.synchronizer_id');
		if (!empty($transaction_synchronizer_id)) {
			$query->where($db->quoteName('transaction.synchronizer_id') . ' = ' . $db->quote($transaction_synchronizer_id));
		}

		$transaction_external_reference = $this->getState('filter.external_reference');
		if (!empty($transaction_external_reference)) {
			$query->leftJoin($db->quoteName('#__emundus_external_reference', 'external_reference') . ' ON ' . $db->quoteName('external_reference.intern_id') . ' = ' . $db->quoteName('transaction.id'));
			$query->where($db->quoteName('external_reference.reference') . ' = ' . $db->quote($transaction_external_reference));
		}

		$transaction_fnum = $this->getState('filter.fnum');
		if (!empty($transaction_fnum)) {
			$query->where($db->quoteName('transaction.fnum') . ' = ' . $db->quote($transaction_fnum));
		}

		$transaction_user_id = $this->getState('filter.user_id');
		if (!empty($transaction_user_id)) {
			$query->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $db->quoteName('ecc.fnum') . ' = ' . $db->quoteName('transaction.fnum'));
			$query->where($db->quoteName('ecc.applicant_id') . ' = ' . $db->quote($transaction_user_id));
		}

		$date_from = $this->getState('filter.date_from');
		if (!empty($date_from)) {
			$query->where($db->quoteName('transaction.created_at') . ' >= ' . $db->quote($date_from));
		}

		$date_to = $this->getState('filter.date_to');
		if (!empty($date_to)) {
			$query->where($db->quoteName('transaction.created_at') . ' <= ' . $db->quote($date_to));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'transaction.id');
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

		$repository = new TransactionRepository();
		foreach ($items as $item) {
			$transaction = $repository->getById($item->id);
			$item->typeAlias = 'com_emundus.transaction';

			if (isset($item->metadata)) {
				$registry       = new Registry($item->metadata);
				$item->metadata = $registry->toArray();
			}

			// add properties from the transaction entity apiRender method
			foreach ($repository->apiRender($transaction) as $key => $value) {
				$item->$key = $value;
			}
		}

		return $items;
	}
}
