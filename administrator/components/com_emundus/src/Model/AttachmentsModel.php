<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_emundus
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Administrator\Model;

use Joomla\CMS\Language\Associations;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of document type (attachment) records.
 *
 * @since  4.0.0
 */
class AttachmentsModel extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array                 $config   An optional associative array of configuration settings.
	 * @param   ?MVCFactoryInterface  $factory  The factory.
	 *
	 * @since   4.0.0
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
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function populateState($ordering = 'esa.ordering', $direction = 'asc')
	{
		parent::populateState($ordering, $direction);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  QueryInterface
	 *
	 * @since   4.0.0
	 */
	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$query->select('esa.id, esa.lbl, esa.value, esa.description, esa.allowed_types, esa.nbmax, esa.ordering, esa.published, esa.category')
			->from($db->quoteName('#__emundus_setup_attachments', 'esa'));

		// Filter by published state
		$published = $this->getState('filter.published');
		if ($published !== null && $published !== '') {
			$query->where($db->quoteName('esa.published') . ' = ' . $db->quote((int) $published));
		}

		// Filter by category
		$category = $this->getState('filter.category');
		if ($category !== null && $category !== '') {
			$query->where($db->quoteName('esa.category') . ' = ' . $db->quote($category));
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'esa.ordering');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}
}
