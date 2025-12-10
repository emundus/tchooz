<?php

/**
 * @package         Joomla.Administrator
 * @subpackage      com_content
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Emundus\Administrator\Model;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting a list of article records.
 *
 * @since  1.6
 */
class ActionsModel extends ListModel
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
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'sa.id',
				'label', 'sa.label'
			];

			if (Associations::isEnabled())
			{
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

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				[
					$db->quoteName('sa.id'),
					$db->quoteName('sa.name'),
					$db->quoteName('sa.label'),
					$db->quoteName('sa.description'),
					$db->quoteName('sa.c'),
					$db->quoteName('sa.r'),
					$db->quoteName('sa.u'),
					$db->quoteName('sa.d'),
					$db->quoteName('sa.status', 'published'),
				]
			)
		)
			->from($db->quoteName('#__emundus_setup_actions', 'sa'));

		// Filter by id
		$id = $this->getState('filter.id');
		if (is_int($id))
		{
			$query->where($db->quoteName('sa.id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);
		}

		$published = $this->getState('filter.published');
		if (is_int($published))
		{
			$query->where($db->quoteName('sa.status') . ' = :published')
				->bind(':published', $published, ParameterType::INTEGER);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'sa.id');
		$orderDirn = $this->state->get('list.direction', 'DESC');

		if ($orderCol === 'sa.ordering')
		{
			$ordering = [
				$db->quoteName('sa.name') . ' ' . $db->escape($orderDirn),
			];
		}
		else
		{
			$ordering = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);
		}

		$query->order($ordering);

		return $query;
	}

	public function getItems()
	{
		$items = parent::getItems();

		$defaultLang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		$lang        = Factory::getApplication()->getLanguage();
		$lang->load('com_emundus', JPATH_SITE . '/components/com_emundus', $defaultLang);

		foreach ($items as $item)
		{
			$item->label       = Text::_($item->label);
			$item->description = Text::_($item->description);
		}

		return $items;
	}
}
