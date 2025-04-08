<?php
/**
 * @package        Joomla.Site
 * @subpackage    mod_users_latest
 * @copyright    Copyright (C) 2005 - 2024 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Module\Tracking\Site\Helper;

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class TrackingHelper
{
	static function getEventDetailsFromUserId(int $user_id): array
	{
		$details = [];

		if (!empty($user_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('email as email_address, firstname as first_name, lastname as last_name')
				->from('#__emundus_users')
				->where('user_id = ' . $user_id);

			try {
				$db->setQuery($query);
				$details = $db->loadAssoc();
			} catch (\Exception $e) {

			}

			if (!empty($details)) {
				$query->clear()
					->select('epd.zipcode_1 as address_postal_code, efc.name as address_city, epd.city_other, escp.valeur as address_country, epd.mobile_phone as phone_number')
					->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
					->leftJoin($db->quoteName('#__emundus_personal_detail', 'epd') . ' ON ecc.fnum = epd.fnum')
					->leftJoin('#__emundus_french_cities as efc ON efc.id = epd.city_1')
					->leftJoin('#__emundus_sise_code_pays as escp ON escp.id = epd.country_1')
					->where('ecc.applicant_id = ' . $user_id);

				try {
					$db->setQuery($query);
					$personal_details = $db->loadAssoc();

					if (!empty($personal_details)) {
						if (empty($personal_details['address_city'])) {
							$personal_details['address_city'] = $personal_details['city_other'];
						}
						unset($personal_details['city_other']);

						$details = array_merge($details, $personal_details);
					}
				} catch (\Exception $e) {
					Log::add('Error while fetching personal details for user ' . $user_id, Log::ERROR, 'com_emundus.error');
				}
			}
		}

		return $details;
	}

	static function getEventDetailsFromFnum(string $fnum): array
	{
		$details = [];

		if (!empty($fnum)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('esc.label as FormFormation, esp.label as FormProgramme, jea.formation_place as FormCampus, jea.studying_level as FormNiveau')
				->from('#__emundus_campaign_candidature as ecc')
				->leftJoin('#__emundus_setup_campaigns as esc ON esc.id = ecc.campaign_id')
				->leftJoin('#__emundus_setup_programmes as esp ON esp.code = esc.training')
				->leftJoin('#__emundus_academic as jea ON jea.fnum = ecc.fnum')
				->where('ecc.fnum = ' . $db->quote($fnum));

			try {
				$db->setQuery($query);
				$details = $db->loadAssoc();
			} catch (\Exception $e) {}

			if (!empty($details['FormCampus'])) {
				$query->clear()
					->select('campus')
					->from('data_campus')
					->where('id = ' . $details['FormCampus']);

				$db->setQuery($query);
				$details['FormCampus'] = $db->loadResult();
			}

			if (!empty($details['FormNiveau'])) {
				$query->clear()
					->select('niveau_detude')
					->from('data_formations_niveaux')
					->where('id = ' . $details['FormNiveau']);

				$db->setQuery($query);
				$details['FormNiveau'] = $db->loadResult();
			}
		}

		return $details;
	}

	static function getUntrackedPaidFiles(int $user_id): array
	{
		$untracked_files = [];

		if (!empty($user_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('ecc.fnum, eh.order_id')
				->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.id = ecc.campaign_id')
				->leftJoin($db->quoteName('#__emundus_hikashop', 'eh') . ' ON ecc.fnum = eh.fnum')
				->leftJoin($db->quoteName('#__hikashop_order', 'eo') . ' ON eo.order_id = eh.order_id')
				->where('ecc.applicant_id = ' . $user_id)
				->andWhere('eh.order_id IS NOT NULL')
				->andWhere('eo.order_status = ' . $db->quote('confirmed'))
				->andWhere('ecc.tracking = 0')
				->andWhere('ecc.published = 1')
				->andWhere('esc.published = 1')
				->andWhere('esc.end_date > NOW()');

			try {
				$db->setQuery($query);
				$untracked_files = $db->loadObjectList();
			} catch (\Exception $e) {
				Log::add('Error while fetching untracked paid files for user ' . $user_id, Log::ERROR, 'com_emundus.error');
			}
		}

		return $untracked_files;
	}

	static function getOrderPrice(int $order_id): int
	{
		$price = 0;

		if (!empty($order_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('order_full_price')
				->from('#__hikashop_order')
				->where('order_id = ' . $order_id);

			try {
				$db->setQuery($query);
				$price = $db->loadResult();

				// format number to 2 decimal places
				$price = number_format($price, 2, '.', '');
			} catch (\Exception $e) {
				Log::add('Error while fetching order price for order ' . $order_id, Log::ERROR, 'com_emundus.error');
			}
		}

		return $price;
	}

	static function setFileAsTracked(string $fnum): bool
	{
		$updated = false;

		if (!empty($fnum)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->update('#__emundus_campaign_candidature')
				->set('tracking = 1')
				->where('fnum = ' . $db->quote($fnum));

			try {
				$db->setQuery($query);
				$db->execute();
				$updated = true;
			} catch (\Exception $e) {
				Log::add('Error while updating tracking status for fnum ' . $fnum, Log::ERROR, 'com_emundus.error');
			}
		}

		return $updated;
	}
}