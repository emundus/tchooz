<?php

use Joomla\CMS\Factory;

// No direct access
defined('_JEXEC') or die('Restricted access');
$return = array();

$return['title'] = 'Délibération d\'admission ' . $this->formation . ' du ' . $this->today;

if (!empty($this->fnums))
{
	$return['table']['columns'] = array(
		'Numéro',
		$this->is_anonym ? 'Numéro Anonymat' : 'Candidat',
		'Entretien avec un jury',
		'Avis'
	);

	$return['table']['rows'] = array();

	$db    = Factory::getContainer()->get('DatabaseDriver');
    $query = $db->createQuery();

    $query->clear()
        ->select('fg.fnum, fg.moyenne_note_jures, if(jepd.first_name is not null, jepd.first_name, jeu.firstname) as first_name, if(jepd.last_name is not null, jepd.last_name, jeu.lastname) as last_name')
        ->from('#__emundus_final_grade as fg')
        ->leftJoin('#__emundus_personal_detail as jepd ON jepd.fnum = fg.fnum')
        ->leftJoin('#__emundus_campaign_candidature as jecc ON jecc.fnum = fg.fnum')
        ->leftJoin('#__emundus_users as jeu ON jeu.user_id = jecc.applicant_id')
        ->where('fg.fnum in (' . implode(', ', $this->fnums) .')')
        ->order('fg.moyenne_note_jures DESC');
    $db->setQuery($query);
    $sorted_fnums = $db->loadObjectList();

	foreach ($sorted_fnums as $key => $item)
	{
		$return['table']['rows'][] = array(
			($key + 1),
			$this->is_anonym ? $item->fnum : strtoupper($item->last_name) . ' ' . ucfirst(strtolower($item->first_name)),
			$item->moyenne_note_jures,
			''
		);
	}
}

echo json_encode($return);