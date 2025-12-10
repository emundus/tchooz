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
class ProfilesModel extends ListModel
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
                    $db->quoteName('sp.label'),
                    $db->quoteName('sp.description'),
                    $db->quoteName('sp.menutype'),
                    $db->quoteName('sp.published', 'is_applicant'),
                    $db->quoteName('sp.status', 'published')
                ]
            )
        )
            ->from($db->quoteName('#__emundus_setup_profiles', 'sp'));

		// Filter by id
	    $id = $this->getState('filter.id');
	    if (is_int($id)) {
		    $query->where($db->quoteName('sp.id') . ' = :id')
			    ->bind(':id', $id, ParameterType::INTEGER);
	    }

	    $is_applicant = $this->getState('filter.is_applicant');
	    if (is_int($is_applicant)) {
		    $query->where($db->quoteName('sp.published') . ' = :is_applicant')
			    ->bind(':is_applicant', $is_applicant, ParameterType::INTEGER);
	    }

	    $published = $this->getState('filter.published');
	    if (is_int($published)) {
		    $query->where($db->quoteName('sp.status') . ' = :published')
			    ->bind(':published', $published, ParameterType::INTEGER);
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

	public function getItems()
	{
		$items = parent::getItems();

		$campaign = $this->getState('filter.campaign');
		if (is_int($campaign) && !empty($campaign)) {
			if(!class_exists('EmundusModelProfile')) {
				require_once JPATH_SITE.'/components/com_emundus/models/profile.php';
			}
			$m_profile = new \EmundusModelProfile();
			$pidsRaw = $m_profile->getProfilesIDByCampaign([$campaign]);

			// Filter items to only those related to the campaign
			$items = array_filter($items, function($item) use ($pidsRaw) {
				return in_array($item->id, $pidsRaw);
			});
		}

		return $items;
	}
}
