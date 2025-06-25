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
		'Notes écrit'
	);

	$return['table']['rows'] = array();

	$db = Factory::getContainer()->get('DatabaseDriver');
	$query = $db->createQuery();

	$query
		->clear()
		->select('jee.fnum, jee.moyenne_l3, jepd.first_name, jepd.last_name')
		->from('#__emundus_evaluations AS jee')
		->leftJoin('#__emundus_personal_detail as jepd ON jepd.fnum = jee.fnum')
		->where('jee.fnum in (' . implode(', ', $this->fnums) . ')')
		->order('moyenne_l3 DESC');

	$db->setQuery($query);
	$sorted_fnums = $db->loadObjectList();

	foreach ($sorted_fnums as $key => $item)
	{
		$return['table']['rows'][] = array(
			($key + 1),
			$this->is_anonym ? $item->fnum : strtoupper($item->last_name) . ' ' . ucfirst(strtolower($item->first_name)),
			$item->moyenne_l3
		);
	}
}

echo json_encode($return);