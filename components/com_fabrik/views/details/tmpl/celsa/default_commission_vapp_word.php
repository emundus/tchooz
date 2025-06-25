<?php
/**
 * Bootstrap Details Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */
use Joomla\CMS\Factory;

// No direct access
defined('_JEXEC') or die('Restricted access');
$return = array();

$title           = "COMMISSION DE VALIDATION DES ACQUIS PERSONNELS ET PROFESSIONNELS " . $this->today;
$return['title'] = $title;
if (!empty($this->fnums))
{
	$return['table']['columns'] = array(
		'NOM et PRENOM',
		'AGE',
		'AVIS'
	);

	$return['table']['rows'] = array();

	$db = Factory::getContainer()->get('DatabaseDriver');
	$query = $db->getQuery(true);

	// filter fnums that have jos_emundus_1014_00___e_414_8297 = 1
	$query->clear()
		->select('#__emundus_campaign_candidature.fnum')
		->from($db->quoteName('#__emundus_campaign_candidature'))
		->leftJoin('#__emundus_1014_00 as e1014 ON e1014.fnum = #__emundus_campaign_candidature.fnum')
		->where($db->quoteName('e1014.e_414_8297') . ' = 1')
		->andWhere('#__emundus_campaign_candidature.fnum IN (' . implode(',', $this->fnums) . ')');

	$db->setQuery($query);
	$fnums = $db->loadColumn();

	foreach ($fnums as $key => $fnum)
	{
		$name = '';
		$age  = 0;
		$avis = '';

		$query->clear()
			->select('jee.id, jee.valid_vapp')
			->from($db->quoteName('#__emundus_evaluations', 'jee'))
			->where($db->quoteName('jee.fnum') . ' = ' . $db->quote($fnum));
		$db->setQuery($query);
		$evaluation = $db->loadObject();

		if (!empty($evaluation->id))
		{
			switch ($evaluation->valid_vapp)
			{
				case 0:
					$avis = 'Défavorable';
					break;
				case 1:
					$avis = 'Favorable';
					break;
				default:
					$avis = '';
			}
		}

		$query->clear()
			->select('epd.birth_date, epd.last_name, epd.first_name')
			->from($db->quoteName('#__emundus_personal_detail', 'epd'))
			->where($db->quoteName('epd.fnum') . ' = ' . $db->quote($fnum));
		$db->setQuery($query);
		$infos = $db->loadObject();

		if (!empty($infos))
		{
			if (!empty($infos->birth_date))
			{
				$birth = date("Y-m-d", strtotime($infos->birth_date));
				$today = date("Y-m-d");
				$diff  = date_diff(date_create($birth), date_create($today));
				$age   = $diff->format('%y');
			}

			$return['table']['rows'][] = array(
				$infos->last_name . ' ' . $infos->first_name,
				$age,
				$avis
			);
		}
	}
}

echo json_encode($return);