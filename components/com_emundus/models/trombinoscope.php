<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 28/03/2017
 * Time: 01:13
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Dompdf\Dompdf;
use Dompdf\Options;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

class EmundusModelTrombinoscope extends BaseDatabaseModel
{
	private DatabaseInterface $db;
	
	public $default_margin = '5';
	public $default_header_height = '330';

	public $pdf_margin_top = 0;
	public $pdf_margin_right = 0;
	public $pdf_margin_left = 0;
	public $pdf_margin_header = 0;
	public $pdf_margin_footer = 0;

	public function __construct()
	{
		parent::__construct();
		
		$this->db = $this->getDatabase();
	}

	public function fnums_json_decode($string_fnums)
	{
		if($string_fnums === 'all') {
			if(!class_exists('EmundusModelFiles')) {
				require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
			}
			$mFiles = new EmundusModelFiles();

			$fnums           = $mFiles->getAllFnums(true);
		}
		else {
			$fnums_obj = (array) json_decode(stripslashes($string_fnums), false, 512, JSON_BIGINT_AS_STRING);

			$fnums = array();
			foreach ($fnums_obj as $key => $value) {
				if ($value->sid > 0) {
					$fnums[] = array('fnum'         => $value->fnum,
					                 'applicant_id' => $value->sid,
					                 'campaign_id'  => $value->cid
					);
				}
			}
		}

		return $fnums;
	}

	public function set_template($programme_code, $format = 'trombi')
	{

		if (!empty($programme_code)) {
			if ($format == 'trombi') {
				try {
					$query = 'SELECT tmpl_trombinoscope FROM #__emundus_setup_programmes WHERE code like ' . $this->db->quote($programme_code);
					$this->db->setQuery($query);
					$this->trombi_tpl = $this->db->loadResult();
				}
				catch (Exception $e) {
					$query = "ALTER TABLE `jos_emundus_setup_programmes` ADD `tmpl_trombinoscope` VARCHAR(2048) NULL DEFAULT " . $this->db->quote($this->trombi_tpl);
					$this->db->setQuery($query);
					$this->db->execute();
					error_log($e->getMessage(), 0);
					echo $e->getMessage();
				}
			}
			else {
				try {
					$query = 'SELECT tmpl_badge FROM #__emundus_setup_programmes WHERE code like ' . $this->db->quote($programme_code);
					$this->db->setQuery($query);
					$this->badge_tpl = $this->db->loadResult();
				}
				catch (Exception $e) {
					$query = "ALTER TABLE `#__emundus_setup_programmes` ADD `tmpl_badge` VARCHAR(2048) NULL DEFAULT " . $this->db->quote($this->badge_tpl);
					$this->db->setQuery($query);
					$this->db->execute();
					error_log($e->getMessage(), 0);
					echo $e->getMessage();
				}
			}
		}
	}

	public function getProgByFnum(string $fnum): array
	{
		try {
			$query = $this->db->getQuery(true);
			$query->select('jesp.id, jesp.code, jesp.label')
				->from($this->db->quoteName('#__emundus_campaign_candidature', 'jecc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'jesc') . ' ON jesc.id = jecc.campaign_id')
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'jesp') . ' ON jesp.code like jesc.training')
				->where($this->db->quoteName('jecc.fnum') . ' = ' . $this->db->quote($fnum));
			$this->db->setQuery($query);

			return $this->db->loadAssoc();
		}
		catch (Exception $e) {
			return [];
		}
	}

	// DOMPDF
	public function generate_pdf($html_value, $format)
	{

		set_time_limit(0);

		//require_once(JPATH_LIBRARIES . DS . 'dompdf' . DS . 'dompdf_config.inc.php');
		$lbl = $this->selectLabelSetupAttachments($format);

		$fileName = $lbl['lbl'] . "_" . time() . ".pdf";
		$tmpName  = JPATH_SITE . DS . 'tmp' . DS . $fileName;

		$options = new Options();
		$options->set('isPhpEnabled', true);
		$dompdf = new Dompdf($options);
		$dompdf->loadHtml($html_value);
		$dompdf->render();

		$output = $dompdf->output();
		file_put_contents($tmpName, $output);

		return JURI::base() . 'tmp' . DS . $fileName;
	}

	public function selectHTMLLetters(): array
	{
		$query = $this->db->getQuery(true);
		
		$query->select([
			$this->db->quoteName('title'),
			$this->db->quoteName('attachment_id'),
			$this->db->quoteName('body'),
			$this->db->quoteName('header'),
			$this->db->quoteName('footer')
		])
			->from($this->db->quoteName('#__emundus_setup_letters'))
			->where($this->db->quoteName('template_type') . ' = 2');

		$this->db->setQuery($query);
		return $this->db->loadAssocList();
	}

	public function selectLabelSetupAttachments($attachment_id)
	{
		$attachment = [];

		
		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName('lbl'))
			->from($this->db->quoteName('#__emundus_setup_attachments', 'esa'))
			->join('INNER', $this->db->quoteName('#__emundus_setup_letters', 'esl') . ' ON (' . $this->db->quoteName('esa.id') . ' = ' . $this->db->quoteName('esl.attachment_id') . ')')
			->where($this->db->quoteName('esl.attachment_id') . ' = ' . $attachment_id);

		$this->db->setQuery($query);

		try {
			$attachment = $this->db->loadAssoc();
		}
		catch (Exception $e) {
			JLog::add('Failed to select attachment attachment label' . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
		}

		return $attachment;
	}
}
