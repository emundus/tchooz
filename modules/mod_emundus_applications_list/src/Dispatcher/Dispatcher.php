<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_category
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Module\ApplicationsList\Site\Dispatcher;

use EmundusHelperFiles;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_emundus_back
 *
 * @since  4.4.0
 */
class Dispatcher extends AbstractModuleDispatcher
{

	/**
	 * Returns the layout data.
	 *
	 * @return  array
	 *
	 * @since   4.4.0
	 */
	protected function getLayoutData(): array
	{
		$data   = parent::getLayoutData();
		$params = $data['params'];

		$statuses = $params->get('statuses', []);
		$columns  = $params->get('content', []);

		$menu    = $this->app->getMenu()->getActive();
		$campaign_id = $menu->getParams()->get('com_emundus_programme_campaign_id', 0);
		$data['applications'] = $this->getApplicationsList($statuses, $columns, $campaign_id);

		return $data;
	}

	private function getApplicationsList(array $statuses, $columns, int $campaign_id = 0): array
	{
		$applications = [];

		try {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('jos_emundus_campaign_candidature.*')
				->from($db->quoteName('#__emundus_campaign_candidature'))
				->where($db->quoteName('jos_emundus_campaign_candidature.published') . ' = 1');

			if (!empty($campaign_id)) {
				$query->where($db->quoteName('jos_emundus_campaign_candidature.campaign_id') . ' = ' . $db->quote($campaign_id));
			}

			if (!empty($statuses)) {
				$query->andWhere($db->quoteName('jos_emundus_campaign_candidature.status') . ' IN (' . implode(',', array_map([$db, 'quote'], $statuses)) . ')');
			}

			$pattern_table = '/\w+\.\w+/';
			$known_tables = [
				'jos_emundus_setup_status',
				'jos_emundus_setup_campaigns',
			];

			$already_joined_tables = [
				'jos_emundus_campaign_candidature' => 'jos_emundus_campaign_candidature',
			];

			$fabrik_elements_by_column = [];

			foreach ($columns as $column) {
				if (preg_match($pattern_table, $column->column)) {
					list($table, $field) = explode('.', $column->column);

					if (!empty($table) && !empty($field)) {
						if (in_array($table, $known_tables))
						{
							$alias = '';
							if (!in_array($table, $already_joined_tables)) {
								switch ($table) {
									case 'jos_emundus_setup_campaigns':
										$query->leftJoin($db->quoteName('#__emundus_setup_campaigns') . ' ON ' . $db->quoteName('jos_emundus_campaign_candidature.campaign_id') . ' = ' . $db->quoteName('jos_emundus_setup_campaigns.id'));
										$alias = 'jos_emundus_setup_status';
										$already_joined_tables['jos_emundus_setup_campaigns'] = 'jos_emundus_setup_campaigns';
										break;
									case 'jos_emundus_setup_status':
										$query->leftJoin($db->quoteName('#__emundus_setup_status') . ' ON ' . $db->quoteName('jos_emundus_campaign_candidature.status') . ' = ' . $db->quoteName('jos_emundus_setup_status.step'));
										$alias = 'jos_emundus_setup_status';
										$already_joined_tables['jos_emundus_setup_status'] = 'jos_emundus_setup_status';
										break;
								}
							}

							if (!empty($alias)) {
								$query->select($db->quoteName($alias . '.' . $field) . ' AS ' . $db->quote($column->column));
							}
						} else {
							$h_files = new EmundusHelperFiles();
							$linked = $h_files->isTableLinkedToCampaignCandidature($table);

							if ($linked) {
								if (!in_array($table, $already_joined_tables)) {
									$query->leftJoin($db->quoteName($table) . ' AS ' . $db->quoteName($table) . ' ON ' . $db->quoteName('jos_emundus_campaign_candidature.fnum') . ' = ' . $db->quoteName($table . '.fnum'));
									$already_joined_tables[$table] = $table;
								}

								$query->select($db->quoteName($table . '.' . $field) . ' AS ' . $db->quote($column->column));
							}
						}

						$sub_query = $db->createQuery();
						$sub_query->clear()
							->select('jfe.*, jfg.params as group_params, jfl.db_table_name')
							->from($db->quoteName('#__fabrik_elements', 'jfe'))
							->leftJoin($db->quoteName('#__fabrik_groups', 'jfg') . ' ON ' . $db->quoteName('jfe.group_id') . ' = ' . $db->quoteName('jfg.id'))
							->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON ' . $db->quoteName('jfe.group_id') . ' = ' . $db->quoteName('jffg.group_id'))
							->leftJoin($db->quoteName('#__fabrik_lists', 'jfl') . ' ON ' . $db->quoteName('jffg.form_id') . ' = ' . $db->quoteName('jfl.form_id'))
							->where($db->quoteName('jfe.name') . ' = ' . $db->quote($field))
							->andWhere($db->quoteName('jfl.db_table_name') . ' = ' . $db->quote($table));

						$db->setQuery($sub_query);
						$fabrik_element = $db->loadAssoc();

						if (!empty($fabrik_element)) {
							$fabrik_elements_by_column[$field] = $fabrik_element;
						} else {
							$fabrik_elements_by_column[$field] = null;
						}
					}
				}
			}

			$db->setQuery($query);
			$applications = $db->loadObjectList();

			if (!empty($applications)) {
				if (!class_exists('EmundusModelFiles')) {
					require_once( JPATH_SITE . '/components/com_emundus/models/files.php');
				}
				$m_files = new \EmundusModelFiles();
				foreach ($applications as $application) {
					foreach ($columns as $column) {
						list($table, $field) = explode('.', $column->column);

						if (!empty($fabrik_elements_by_column[$field])) {

							$value = $m_files->getFabrikElementValue($fabrik_elements_by_column[$field], $application->fnum);
							if (isset($value[$fabrik_elements_by_column[$field]['id']])) {
								$application->{$column->column} = $value[$fabrik_elements_by_column[$field]['id']][$application->fnum]['val'];
							}
						}
					}
				}
			}
		} catch (\Exception $e)
		{
			Log::add('Error fetching applications: ' . $e->getMessage(), Log::ERROR, 'module.emundus_applications_list');
		}

		return $applications;
	}
}
