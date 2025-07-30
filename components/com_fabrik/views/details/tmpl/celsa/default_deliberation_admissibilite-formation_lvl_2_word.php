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

    // For BEL students, use average of imported notes (with coeff taken into account), otherwise use moyenne_l3 column

    $query->clear()
        ->select('jee.fnum, ROUND(SUM(jee.epreuve_note * jee.coeff) / SUM(jee.coeff),2) as moyenne_bel, jee.moyenne_l3, if(jepd.first_name is not null, jepd.first_name, jeu.firstname) as first_name, if(jepd.last_name is not null, jepd.last_name, jeu.lastname) as last_name')
        ->from('#__emundus_evaluations AS jee')
        ->leftJoin('#__emundus_personal_detail as jepd ON jepd.fnum = jee.fnum')
        ->leftJoin('#__emundus_campaign_candidature as jecc ON jecc.fnum = jee.fnum')
        ->leftJoin('#__emundus_users as jeu ON jeu.user_id = jecc.applicant_id')
        ->where('jee.fnum in (' . implode(', ', $this->fnums) .')')
        ->group('jee.student_id')
        ->order('moyenne_bel DESC, jee.moyenne_l3 DESC');
    $db->setQuery($query);
    $sorted_fnums = $db->loadObjectList();

	foreach ($sorted_fnums as $key => $item)
	{
		$return['table']['rows'][] = array(
			($key + 1),
			$this->is_anonym ? $item->fnum : strtoupper($item->last_name) . ' ' . ucfirst(strtolower($item->first_name)),
            (!empty($item->moyenne_bel) ? $item->moyenne_bel : $item->moyenne_l3),
		);
	}
}

echo json_encode($return);