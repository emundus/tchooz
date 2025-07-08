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

			$query->select('ecc.*')
				->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->where($db->quoteName('ecc.published') . ' = 1');

			if (!empty($campaign_id)) {
				$query->where($db->quoteName('ecc.campaign_id') . ' = ' . $db->quote($campaign_id));
			}

			if (!empty($statuses)) {
				$query->andWhere($db->quoteName('ecc.status') . ' IN (' . implode(',', array_map([$db, 'quote'], $statuses)) . ')');
			}

			$pattern_table = '/\w+\.\w+/';
			$known_tables = [
				'jos_emundus_setup_status',
				'jos_emundus_setup_campaigns',
			];

			$already_joined_tables = [
				'ecc' => 'jos_emundus_campaign_candidature',
			];

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
										$query->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'campaign') . ' ON ' . $db->quoteName('ecc.campaign_id') . ' = ' . $db->quoteName('campaign.id'));
										$alias = 'campaign';
										$already_joined_tables['campaign'] = 'jos_emundus_setup_campaigns';
										break;
									case 'jos_emundus_setup_status':
										$query->leftJoin($db->quoteName('#__emundus_setup_status', 'status') . ' ON ' . $db->quoteName('ecc.status') . ' = ' . $db->quoteName('status.step'));
										$alias = 'status';
										$already_joined_tables['status'] = 'jos_emundus_setup_status';
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
									$query->leftJoin($db->quoteName($table) . ' AS ' . $db->quoteName($table) . ' ON ' . $db->quoteName('ecc.fnum') . ' = ' . $db->quoteName($table . '.fnum'));
									$already_joined_tables[$table] = $table;
								}

								$query->select($db->quoteName($table . '.' . $field) . ' AS ' . $db->quote($column->column));
							}
						}
					}
				}
			}

			$db->setQuery($query);
			$applications = $db->loadObjectList();
		} catch (\Exception $e)
		{
			Log::add('Error fetching applications: ' . $e->getMessage(), Log::ERROR, 'module.emundus_applications_list');
		}

		return $applications;
	}
}
