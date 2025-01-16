<?php

/**
 * @package         Joomla.Site
 * @subpackage      mod_articles_category
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Module\BtoB\Site\Dispatcher;

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

		$params = $data['params'];
		$statuses = $params->get('mod_emundus_btob_status', []);

		if(!empty($statuses))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('ess.step,ess.value')
				->from($db->quoteName('#__emundus_setup_status', 'ess'))
				->where($db->quoteName('ess.step') . ' IN (' . implode(',', $statuses) . ')');
			$db->setQuery($query);
			$data['statuses'] = $db->loadAssocList('step');

			$query->clear()
				->select('esc.id as campaign_id,concat(esp.label,"(",esc.label,")") as label,ecc.status,escm.price')
				->from($db->quoteName('#__emundus_setup_campaigns', 'esc'))
				->leftJoin($db->quoteName('#__emundus_setup_programmes','esp').' ON '.$db->quoteName('esp.code').' = '.$db->quoteName('esc.training'))
				->leftJoin($db->quoteName('#__emundus_setup_campaigns_more','escm').' ON '.$db->quoteName('escm.campaign_id').' = '.$db->quoteName('esc.id'))
				->leftJoin($db->quoteName('#__emundus_campaign_candidature','ecc').' ON '.$db->quoteName('ecc.campaign_id').' = '.$db->quoteName('esc.id'))
				->where($db->quoteName('ecc.applicant_id').' = '.$user->id)
				->where($db->quoteName('ecc.status') . ' IN (' . implode(',', $statuses) . ')')
				->where($db->quoteName('esc.published') . ' = 1');
			$db->setQuery($query);
			$files = $db->loadAssocList();

			foreach ($files as $file) {
				if(empty($data['campaigns'][$file['campaign_id']])) {
					$data['campaigns'][$file['campaign_id']] = [
						'label' => $file['label'],
						'status' => [],
						'price' => 0.00
					];
				}

				foreach ($statuses as $status) {
					if($file['status'] == $status) {
						$data['campaigns'][$file['campaign_id']]['status'][$status] += 1;
						$data['campaigns'][$file['campaign_id']]['price'] += (float)$file['price'];
					}
				}
			}

			foreach ($data['campaigns'] as $key => $campaign) {
				// Format price
				$data['campaigns'][$key]['price'] = number_format($campaign['price'], 0, ',', ' ') . ' €';
			}
		}

		return $data;
	}
}
