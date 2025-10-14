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
class CampaignsModel extends ListModel
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
                'id', 'sc.id',
                'label', 'sc.label'
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
                    $db->quoteName('sc.id'),
                    $db->quoteName('sc.start_date'),
                    $db->quoteName('sc.end_date'),
                    $db->quoteName('sc.label'),
                    $db->quoteName('sc.short_description'),
                    $db->quoteName('sc.description'),
                    $db->quoteName('sc.training'),
                    $db->quoteName('sc.year'),
                    $db->quoteName('sc.published'),
                    $db->quoteName('sc.pinned'),
                    $db->quoteName('sc.alias'),
                    $db->quoteName('sc.visible'),
                ]
            )
        )
            ->from($db->quoteName('#__emundus_setup_campaigns', 'sc'));

	    $published = (string) $this->getState('filter.published');
	    if (\in_array($published, ['0','1'])) {
		    $published = (int) $published;
		    $query->where($db->quoteName('sc.published') . ' = :published')
			    ->bind(':published', $published, ParameterType::INTEGER);
	    }

		$state = $this->getState('filter.state');
		if(in_array($state, ['current','ongoing','finished'])) {
			$toSql = Factory::getDate()->toSql();
			switch ($state) {
				case 'current':
					$query->where($db->quoteName('sc.start_date') . ' <= :current')
						->where($db->quoteName('sc.end_date') . ' >= :current')
						->bind(':current', $toSql, ParameterType::STRING);
					break;

				case 'ongoing':
					$query->where($db->quoteName('sc.start_date') . ' > :ongoing')
						->bind(':ongoing', $toSql, ParameterType::STRING);
					break;

				case 'finished':
					$query->where($db->quoteName('sc.end_date') . ' < :finished')
						->bind(':finished', $toSql, ParameterType::STRING);
					break;
			}
		}

	    $id = $this->getState('filter.id');
	    if (is_int($id)) {
		    $query->where($db->quoteName('sc.id') . ' = :id')
			    ->bind(':id', $id, ParameterType::INTEGER);
	    }

		$start_date = $this->getState('filter.start_date');
		if (!empty($start_date)) {
			$start_date = Factory::getDate($start_date)->toSql();
			$query->where($db->quoteName('sc.start_date') . ' >= :start_date')
				->bind(':start_date', $start_date, ParameterType::STRING);
		}

		$end_date = $this->getState('filter.end_date');
		if (!empty($end_date)) {
			$end_date = Factory::getDate($end_date)->toSql();
			$query->where($db->quoteName('sc.end_date') . ' <= :end_date')
				->bind(':end_date', $end_date, ParameterType::STRING);
		}
		
        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'sc.id');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        if ($orderCol === 'sc.ordering') {
            $ordering = [
                $db->quoteName('sc.label') . ' ' . $db->escape($orderDirn),
            ];
        } else {
            $ordering = $db->escape($orderCol) . ' ' . $db->escape($orderDirn);
        }

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

	    $db = $this->getDatabase();
	    $query = $db->getQuery(true);

	    $base_url = Factory::getApplication()->get('live_site', Uri::base(true));
	    if (substr($base_url, -1) !== '/') {
			$base_url .= '/';
		}

		$query->select('alias')
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('client_id') . ' = 0')
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('menutype') . ' = ' . $db->quote('topmenu'))
			->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_fabrik&view=form&formid=102'));
		$db->setQuery($query);
	    $apply_route = $db->loadResult();

	    //Factory::$language = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage('fr-FR');

        foreach ($items as $item) {
            $item->typeAlias = 'com_emundus.campaign';
			$item->applyUrl = $base_url.$apply_route.'?course=' . $item->training . '&cid=' . $item->id;
			$item->detailsUrl = $base_url.$item->alias;

	        $query->clear()
		        ->select(
					[
						$db->quoteName('df.id', 'id'),
						$db->quoteName('df.catid', 'catid'),
						$db->quoteName('df.title', 'title_file'),
						$db->quoteName('df.ext', 'ext'),
						$db->quoteName('cat.path', 'title_category')
					]
		        )
		        ->from($db->quoteName('jos_dropfiles_files', 'df'))
		        ->leftJoin($db->quoteName('jos_categories', 'cat') . ' ON ' . $db->quoteName('cat.id') . ' = ' . $db->quoteName('df.catid'))
		        ->where($db->quoteName('df.state') . ' = 1')
		        ->andWhere($db->quoteName('cat.extension') . ' = ' . $db->quote('com_dropfiles'))
		        ->andWhere('json_valid(`cat`.`params`)')
		        ->andWhere('json_extract(`cat`.`params`, "$.idCampaign") LIKE ' . $db->quote('"' . $item->id . '"'))
		        ->order($db->quoteName('df.ordering'));
			$db->setQuery($query);
			$files = $db->loadObjectList();

	        foreach ($files as $file) {
		        $file->href = $base_url.'files/' . $file->catid.'/'.$file->title_category.'/'.$file->id.'/'.$file->title_file.'.'.$file->ext;
	        }

			$item->files = $files;

            if (isset($item->metadata)) {
                $registry       = new Registry($item->metadata);
                $item->metadata = $registry->toArray();
            }

			if(!class_exists('EmundusModelCampaign'))
			{
				require_once JPATH_SITE.'/components/com_emundus/models/campaign.php';
			}
			if(!class_exists('EmundusHelperFabrik'))
			{
				require_once JPATH_SITE.'/components/com_emundus/helpers/fabrik.php';
			}
			$m_campaigns = new \EmundusModelCampaign();
			$more_elements = $m_campaigns->getCampaignMoreForm($item->id);
			
			if(!empty($more_elements) && !empty($more_elements['elements']))
			{
				$query->clear()
					->select('*')
					->from($db->quoteName('#__emundus_setup_campaigns_more'))
					->where($db->quoteName('campaign_id') . ' = :campaign_id')
					->bind(':campaign_id', $item->id, ParameterType::INTEGER);
				$db->setQuery($query);
				$more_data = $db->loadAssoc();
				
				$query->clear()
					->select('element_id, table_join, table_key')
					->from($db->quoteName('#__fabrik_joins'))
					->where($db->quoteName('join_from_table') . ' = ' . $db->quote('jos_emundus_setup_campaigns_more'));
				$db->setQuery($query);
				$join_tables = $db->loadAssocList('element_id');

				$item->custom_fields = [];
				foreach ($more_elements['elements'] as $element) {
					if(!in_array($element['id'], array_keys($join_tables)))
					{
						$item->custom_fields[$element['name']] = \EmundusHelperFabrik::formatElementValue($element['name'], $more_data[$element['name']]);
					}
					else {
						$query->clear()
							->select($db->quoteName($join_tables[$element['id']]['table_key']))
							->from($db->quoteName($join_tables[$element['id']]['table_join']))
							->where($db->quoteName('parent_id') . ' = :id')
							->bind(':id', $more_data['id'], ParameterType::INTEGER);
						$db->setQuery($query);
						$values = $db->loadColumn();

						$item->custom_fields[$element['name']] = [];
						foreach ($values as $value) {
							$item->custom_fields[$element['name']][] = \EmundusHelperFabrik::formatElementValue($element['name'], $value);
						}
					}
				}
			}
        }

	    $orderCol  = $this->state->get('list.ordering', 'sc.id');
		if($orderCol === 'label')
		{
			// Perform a natural sort on the label field
			usort($items, function ($a, $b) {
				return strnatcmp($a->label, $b->label);
			});
		}

        return $items;
    }
}
