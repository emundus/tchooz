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
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use JUri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of files records.
 *
 * @since  1.6
 */
class FileuploadsModel extends ListModel
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
		$query = $db->createQuery();
		$user  = $this->getCurrentUser();
		$params = ComponentHelper::getParams('com_emundus');


		$site_url = rtrim(JUri::root(), '/\\') . '/';
		$attachments_folder = 'images/emundus/files/';

		$query->select('upload.id, upload.fnum, upload.filename, upload.local_filename, upload.user_id, attachment.value as attachment_name, attachment.description, CONCAT(' . $db->quote($site_url . $attachments_folder) . ', candidature.applicant_id, "/", upload.filename) as download_url')
			->from($db->quoteName('#__emundus_uploads', 'upload'))
			->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'candidature') . ' ON ' . $db->quoteName('candidature.fnum') . ' = ' . $db->quoteName('upload.fnum'))
			->leftJoin($db->quoteName('#__emundus_setup_attachments', 'attachment') . ' ON ' . $db->quoteName('attachment.id') . ' = ' . $db->quoteName('upload.attachment_id'))
			->where($db->quoteName('candidature.published') . ' = 1');

		$app = Factory::getApplication();
		$filters = $app->input->get('filter', [], 'array');

		if (\array_key_exists('fnum', $filters))
		{
			$fnum = InputFilter::getInstance()->clean($filters['fnum'], 'STRING');

			if ($fnum > 0)
			{
				$query->andWhere($db->quoteName('upload.fnum') . ' LIKE ' . $db->quote($fnum));
			}
		}

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

		return $items;
	}
}
