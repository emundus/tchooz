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

$app = Factory::getApplication();
$download = $app->input->getString('download', '');

$form = $this->form;
$model = $this->getModel();

if (!class_exists('EmundusHelperDate')) {
	require_once(JPATH_ROOT . '/components/com_emundus/helpers/date.php');
}
$this->today = EmundusHelperDate::displayDate(date('Y-m-d H:i:s'), 'DATE_FORMAT_LC3', 0);


// get form data
$data = $model->getData();

$campaign = $data['jos_emundus_generate_pv___campaign_id_raw'];
$type = $data['jos_emundus_generate_pv___type_raw'];
$file_type = $data['jos_emundus_generate_pv___file_type'];
$voies_d_acces = $data['jos_emundus_generate_pv___voie_d_acces_id'];
$is_anonym = $data['jos_emundus_generate_pv___is_anonym'];

$fnums = [];
$formation_level = '';
$formation = '';
$campaign_year = '';
$word_data = [];

if (!empty($campaign) && !empty($type) && !empty($file_type)) {
	$db = Factory::getContainer()->get('DatabaseDriver');
	$query = $db->createQuery();

	// get campaign year
	$query->select('year')
		->from('#__emundus_setup_campaigns')
		->where('id = ' . $campaign);
	$db->setQuery($query);
	$campaign_year = $db->loadResult();

	// get formation
	$query->clear();
	$query->select('data_formation.formation')
		->from('data_formation')
		->leftJoin('#__emundus_setup_campaigns ON #__emundus_setup_campaigns.formation = data_formation.id')
		->where('#__emundus_setup_campaigns.id = ' . $campaign);

	$db->setQuery($query);
	$formation = $db->loadResult();

	// get formation level
	$query->clear();
	$query->select('data_formation_level.id')
		->from('data_formation_level')
		->leftJoin('data_formation ON data_formation.level = data_formation_level.id')
		->leftJoin('#__emundus_setup_campaigns ON #__emundus_setup_campaigns.formation = data_formation.id')
		->where('#__emundus_setup_campaigns.id = ' . $campaign);

	$db->setQuery($query);
	$formation_level = $db->loadResult();

	// get all fnums
	$query->clear()
		->select('fnum')
		->from($db->quoteName('#__emundus_campaign_candidature'))
		->where($db->quoteName('campaign_id') . ' = ' . $db->quote($campaign))
		->andWhere('published = 1');

	if (!empty($voies_d_acces)) {
		$query->andWhere($db->quoteName('data_voie_d_acces') . ' IN (' . implode(', ', $voies_d_acces) . ')');
	}

	$db->setQuery($query);
	$fnums = $db->loadColumn();
}


switch ($type) {
	case '1':
		$template = 'deliberation_admission-formation_lvl_' . $formation_level;
		break;
	case '2':
		$template = 'deliberation_admissibilite-formation_lvl_' . $formation_level;
		break;
	case '3':
		$template = 'commission_vapp';
		break;
	default:
		$template = '';
		break;
}

$content = '<div class="template">
	<header>
		<table class="right-box borderless">
			<tbody>
				<tr><td>Concours ' . date('Y') . '</td></tr>
				<tr><td>' . $formation . '</td></tr>
				<tr><td>Année universitaire ' . $campaign_year . ' </td></tr>
			</tbody>
		</table>
	</header>
	<main>';


if (!empty($template)) {
	$this->fnums = $fnums;
	$this->formation = $formation;
	$this->is_anonym = $is_anonym;

	try {
		$json = $this->loadTemplate($template . '_word');
	} catch (Exception $e) {
		$json = json_encode(array("title" => "Le template pour le format docx n'existe pas pour ce type de fichier. Téléchargez plutot un pdf."));
	}

	$word_data = json_decode($json, true);
}

$content .= '<table class="right-box borderless bottom-infos" style="margin-top: 30px;position: fixed !important;bottom: 0 !important;" >
			<tbody style="padding-top:30px;">
				<tr><td>Neuilly-sur-Seine, le ' . $this->today . '</td></tr>
				<tr><td>Pascal Froissart</td></tr>
				<tr><td>Professeur des universités</td></tr>
				<tr><td>Directeur du CELSA</td></tr>
			</tbody>
		</table>
	</main>
</div>';

if ($download) {
	// DOCX
	require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$section = $phpWord->addSection(array(
		'orientation' => 'landscape',
	));

	// add header and footer
	$header = $section->addHeader();

	if (file_exists(JUri::base() . '/images/custom/logo_custom.png')) {
		$header->addImage(JUri::base() . '/images/custom/logo_custom.png', array('width' => 135, 'height' => 75, 'align' => 'left'));
	}

	$footer = $section->addFooter();
	$footer->addText("École des hautes études en sciences de l'information et de la communication – Sorbonne Université", array('size' => 8, 'name' => 'Arial', 'color' => '#000000'));
	$footer->addText("77, rue de Villiers  92200 Neuilly-sur-Seine  I  tél. : +33 (0)1 46 43 76 76  I  fax : +33 (0)1 47 45 66 04  I  celsa.fr", array('size' => 8, 'name' => 'Arial', 'color' => '#000000'));


	$textAlignment = array(
		'align' => \PhpOffice\PhpWord\SimpleType\Jc::END,
		'spaceAfter' => 100
	);
	$section->addText('Concours ' . date('Y'), array('textAlignment' => 'right'), $textAlignment);
	$section->addText($formation, array('textAlignment' => 'right'), $textAlignment);
	$section->addText('Année universitaire ' . $campaign_year, array('textAlignment' => 'right'), $textAlignment);
	$section->addTextBreak(2);

	if (!empty($word_data)) {

		// title blue
		$section->addText($word_data['title'], 1, array(
			'size' => 16,
			'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
		));

		$section->addTextBreak(2);

		if (!empty($word_data['table'])) {
			$table = $section->addTable(array(
				'borderSize' => 6,
				'borderColor' => '000000',
				'cellMargin' => 0,
				'cellSpacing' => 0,
				'cellPadding' => 0,
				'bgColor' => 'FFFFFF',
				'width' => 100 * 50,
				'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
				'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
				'textAlignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
			));

			// add header row from $word_data['table']['columns']
			$table->addRow();
			foreach ($word_data['table']['columns'] as $column) {
				$table->addCell(2000, array('valign' => 'center'))->addText($column, array('name' => 'Arial', 'size' => 10, 'color' => '000000', 'bold' => true), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
			}

			// add data rows from $word_data['table']['rows']
			foreach ($word_data['table']['rows'] as $row) {
				$table->addRow();
				foreach ($row as $cell) {
					$table->addCell(2000, array('valign' => 'center'))->addText($cell, array('name' => 'Arial', 'size' => 10, 'color' => '000000'), array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER));
				}
			}
		}
	}

	$section->addTextBreak(2);

	$section->addText('Neuilly-sur-Seine, le  ' . $this->today, array('textAlignment' => 'right'), $textAlignment);
	$section->addText('Pascal Froissart', array('textAlignment' => 'right'), $textAlignment);
	$section->addText('Professeur des universités', array('textAlignment' => 'right'), $textAlignment);
	$section->addText('Directeur du CELSA', array('textAlignment' => 'right'), $textAlignment);

	$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

	if ($file_type == 2) {
		// download file
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $template . '-' . $campaign_year . '-' . $formation . '.docx"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		ob_clean();
		flush();
		$objWriter->save('php://output');
		readfile('php://output');
		exit;
	} else {
		// convert to PDF
		$file_src = JPATH_ROOT . '/tmp/' . $template . '-' . $campaign_year . '-' . $formation . '.docx';
		$phpWord->save($file_src, 'Word2007');
		$file_dest = JPATH_ROOT . '/tmp/' . $template . '-' . $campaign_year . '-' . $formation;

		if (!class_exists('EmundusModelExport')) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/export.php');
		}
		$m_export = new EmundusModelExport();
		$res = $m_export->toPdf($file_src, $file_dest, 'docx');

		if ($res->status) {
			// download pdf file
			header('Content-Description: File Transfer');
			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="' . $template . '-' . $campaign_year . '-' . $formation . '.pdf"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			ob_clean();
			flush();
			readfile($res->file);
			exit;
		}
	}

} else {
	echo $content;
}

?>