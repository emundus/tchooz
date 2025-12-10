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
class ProgramsModel extends ListModel
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
            $config['filter_fields'] = [
                'id', 'sp.id',
                'label', 'sp.label'
            ];

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
                    $db->quoteName('sp.id'),
                    $db->quoteName('sp.code'),
                    $db->quoteName('sp.label'),
                    $db->quoteName('sp.notes', 'description'),
                    $db->quoteName('sp.published'),
                    $db->quoteName('sp.programmes', 'category')
                ]
            )
        )
            ->from($db->quoteName('#__emundus_setup_programmes', 'sp'));

		// Filter by id
	    $id = $this->getState('filter.id');
	    if (is_int($id)) {
		    $query->where($db->quoteName('sp.id') . ' = :id')
			    ->bind(':id', $id, ParameterType::INTEGER);
	    }

	    $published = $this->getState('filter.published');
	    if (is_int($published)) {
		    $query->where($db->quoteName('sp.published') . ' = :published')
			    ->bind(':published', $published, ParameterType::INTEGER);
	    }

	    $code = $this->getState('filter.code');
	    if (is_string($code)) {
		    $query->where($db->quoteName('sp.code') . ' = :code')
			    ->bind(':code', $code, ParameterType::STRING);
	    }

	    $category = $this->getState('filter.category');
	    if (is_string($category)) {
		    $query->where($db->quoteName('sp.programmes') . ' = :category')
			    ->bind(':category', $category, ParameterType::STRING);
	    }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'sp.id');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        if ($orderCol === 'sp.ordering') {
            $ordering = [
                $db->quoteName('sp.label') . ' ' . $db->escape($orderDirn),
            ];
        } else {
            $ordering = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);
        }

        $query->order($ordering);

        return $query;
    }
}
