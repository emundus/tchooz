<?php

/**
 * @package         Joomla.Site
 * @subpackage      mod_articles_category
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Module\CountApplications\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_emundus_btob
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
		$data = parent::getLayoutData();

		$user = Factory::getApplication()->getIdentity();
		$euser = Factory::getApplication()->getSession()->get('emundusUser');

		$params = $data['params'];

		$data['profiles'] = (array) $params->get('mod_emundus_count_applications_profiles');
		if(!empty($data['profiles']) && !in_array($euser->profile, $data['profiles']))
		{
			return $data;
		}

		$data['columns'] = (array) $params->get('mod_emundus_count_applications_columns');
		$data['rows']    = (array) $params->get('mod_emundus_count_applications_rows');

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		foreach ($data['rows'] as $key => $row)
		{
			$data['rows'][$key]->applications = [];

			foreach ($data['columns'] as $index => $column)
			{
				$data['rows'][$key]->applications[$index] = 0;

				// Count files base on mod_emundus_count_applications_columns_status and mod_emundus_count_applications_rows_programs
				$status   = $column->mod_emundus_count_applications_columns_status;
				$calculations = (array)$row->mod_emundus_count_applications_rows_calculation;

				foreach ($calculations as $calculation)
				{
					$programs = $calculation->mod_emundus_count_applications_rows_programs;

					// Get the count of applications
					$query->clear()
						->select('COUNT(DISTINCT cc.applicant_id)')
						->from($db->quoteName('#__emundus_campaign_candidature', 'cc'))
						->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $db->quoteName('sc.id') . ' = ' . $db->quoteName('cc.campaign_id'))
						->where($db->quoteName('cc.status') . ' IN (' . implode(',', $db->quote($status)) . ')')
						->where($db->quoteName('sc.training') . ' IN (' . implode(',', $db->quote($programs)) . ')')
						->where($db->quoteName('cc.published') . ' <> -1')
						->group('cc.applicant_id')
						->having('COUNT(DISTINCT sc.training) = ' . count($programs));
					$db->setQuery($query);
					$data['rows'][$key]->applications[$index] += count($db->loadColumn());
				}
			}
		}

		return $data;
	}
}
