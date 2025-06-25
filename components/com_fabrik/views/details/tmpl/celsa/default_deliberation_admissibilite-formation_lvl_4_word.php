<?php

use Joomla\CMS\Factory;

// No direct access
defined('_JEXEC') or die('Restricted access');
$return = array();

$return['title'] = 'Délibération d\'admissibilité ' . $this->formation . ' du ' . $this->today;
if (!empty($this->fnums))
{
	$return['table']['columns'] = array(
		'N°',
		$this->is_anonym ? 'Numéro Anonymat' : 'Candidat',
		'Résultat d\'admissibilité',
		'Décision'
	);

	$return['table']['rows'] = array();

	$db    = Factory::getContainer()->get('DatabaseDriver');
	$query = $db->createQuery();

	$query
		->clear()
		->select('jee.fnum, jee.is_admissible, jepd.first_name, jepd.last_name')
		->from('#__emundus_evaluations AS jee')
		->leftJoin('#__emundus_personal_detail as jepd ON jepd.fnum = jee.fnum')
		->where('jee.fnum in (' . implode(', ', $this->fnums) . ')')
		->order('is_admissible DESC');


	$db->setQuery($query);
	$sorted_fnums = $db->loadObjectList();

	$index = 1;
	foreach ($sorted_fnums as $key => $item)
	{
		$decision      = '';
		$admissibilite = 'Admissible';

		if ($item->is_admissible == null)
		{
			continue;
		}

		if ($item->is_admissible == 0)
		{
			$admissibilite = 'Refusé';
		}

		$return['table']['rows'][] = array(
			$index,
			$this->is_anonym ? $item->fnum : strtoupper($item->last_name) . ' ' . ucfirst(strtolower($item->first_name)),
			$admissibilite,
			$decision
		);

		$index++;
	}
}

echo json_encode($return);