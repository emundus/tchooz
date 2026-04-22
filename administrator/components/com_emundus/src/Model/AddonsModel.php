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
class AddonsModel extends ListModel
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
	                $db->quoteName('sc.namekey', 'id'),
                    $db->quoteName('sc.activated'),
                    $db->quoteName('sc.displayed', 'subscribed'),
                    $db->quoteName('sc.params'),
                ]
            )
        )
            ->from($db->quoteName('#__emundus_setup_config', 'sc'))
            ->where($db->quoteName('namekey') . ' <> ' . $db->quote('onboarding_lists'));

	    $activated = (string) $this->getState('filter.activated');
	    if (\in_array($activated, ['0','1'])) {
		    $activated = (int) $activated;
		    $query->where($db->quoteName('sc.activated') . ' = :activated')
			    ->bind(':activated', $activated, ParameterType::INTEGER);
	    }

	    $subscribed = (string) $this->getState('filter.subscribed');
	    if (\in_array($subscribed, ['0','1'])) {
		    $subscribed = (int) $subscribed;
		    $query->where($db->quoteName('sc.displayed') . ' = :subscribed')
			    ->bind(':subscribed', $subscribed, ParameterType::INTEGER);
	    }

	    $name = $this->getState('filter.name');
	    if (!empty($name)) {
		    $query->where($db->quoteName('sc.namekey') . ' = :name')
			    ->bind(':name', $name);
	    }
		
        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'sc.namekey');
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

	    foreach ($items as $item) {
			$item->params = json_decode($item->params);
	    }

        return $items;
    }
}
