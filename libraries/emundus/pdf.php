<?php

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use Joomla\CMS\HTML\HTMLHelper;
require_once (JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;
use Dompdf\Css;

use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use Joomla\CMS\Log\Log;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;

if(!function_exists('get_mime_type'))
{
	function get_mime_type($filename, $mimePath = '../etc')
	{
		$fileext = substr(strrchr($filename, '.'), 1);

		if (empty($fileext))
		{
			return false;
		}

		$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
		$lines = file("$mimePath/mime.types");
		foreach ($lines as $line)
		{
			if (substr($line, 0, 1) == '#')
			{
				continue;
			} // skip comments
			$line = rtrim($line) . " ";
			if (!preg_match($regex, $line, $matches))
			{
				continue;
			} // no match to the extension

			return $matches[1];
		}

		return false; // no match at all
	}
}

if(!function_exists('is_image_ext'))
{
	function is_image_ext($filename)
	{
		$array = explode('.', $filename);

		return in_array(strtolower(end($array)), ['png', 'jpe', 'jpeg', 'jpg', 'gif', 'bmp', 'ico', 'tiff', 'tif', 'svg', 'svgz']);
	}
}


/** Generate a PDF letter based on the HTML it contains.
 * This is only for letter type 2, letters type 1 are any file uploaded by the user and 3 are DOC templates.
 *
 * @param   Object  $letter    The letter to generate the pdf file from.
 * @param   String  $fnum      The fnum of the file to generate for.
 * @param   Int     $user_id   The ID of the user who's data we want.
 * @param   String  $training  The training code for the fnum.
 *
 * @return Boolean False if queries fail or the letter template is not 2.
 * @throws Exception
 */
function generateLetterFromHtml($letter, $fnum, $user_id, $training) {

    if ($letter->template_type != 2) {
	    return false;
    }

    set_time_limit(0);
    require_once (JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'config'.DS.'lang'.DS.'eng.php');
    require_once (JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'emails.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'campaign.php');
    require_once (JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');

    $user = JFactory::getUser($user_id);
    $current_user = JFactory::getUser();
    $db = JFactory::getDBO();

    $m_application 	= new EmundusModelApplication;
    $m_campaign 	= new EmundusModelCampaign;
    $m_emails 		= new EmundusModelEmails;

    $campaign = $m_campaign->getCampaignsByCourse($training);

    if (class_exists('MYPDF') === false || !class_exists('MYPDF')) {
        // Extend the TCPDF class to create custom Header and Footer
        class MYPDF extends TCPDF {

            var $logo = "";
            var $logo_footer = "";
            var $footer = "";

            //Page header
            public function Header() {
                // Logo
                if (is_file($this->logo)) {
	                $this->Image($this->logo, 0, 0, 200, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                }
                // Set font
                $this->SetFont('helvetica', 'B', 16);
                // Title
                $this->Cell(0, 15, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            }

            // Page footer
            public function Footer() {
                // Position at 15 mm from bottom
                $this->SetY(-15);
                // Set font
                $this->SetFont('helvetica', 'I', 8);
                // Page number
                $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
                // footer
                $this->writeHTMLCell($w=0, $h=0, $x='', $y=250, $this->footer, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
                //logo
                if (is_file($this->logo_footer)) {
	                $this->Image($this->logo_footer, 150, 280, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                }
            }
        }
    }

    $error = 0;

    $attachment = $m_application->getAttachmentByID($letter->attachment_id);

    try {

        // Test if letter type has already been created for that user/campaign/attachment and delete before if true.
        $query = 'SELECT * FROM #__emundus_uploads WHERE user_id='.$user_id.' AND attachment_id='.$letter->attachment_id.' AND campaign_id='.$campaign['id']. ' AND fnum like '.$db->Quote($fnum);
        $db->setQuery($query);
        $file = $db->loadAssoc();

    } catch (Exception $e) {
        Log::add('SQL Error in emundus pdf library at query : '.$query, Log::ERROR, 'com_emundus');
        return false;
    }

    // test if directory exist
    if (!file_exists(EMUNDUS_PATH_ABS.$user_id)) {
        mkdir(EMUNDUS_PATH_ABS.$user_id, 0755, true);
        chmod(EMUNDUS_PATH_ABS.$user_id, 0755);
    }

    if (count($file) > 0 && strpos($file['filename'], 'lock') === false) {

        try {

            $query = 'DELETE FROM #__emundus_uploads WHERE user_id='.$user_id.' AND attachment_id='.$letter->attachment_id.' AND campaign_id='.$campaign['id']. ' AND fnum like '.$db->Quote($fnum).' AND filename NOT LIKE "%lock%"';
            $db->setQuery($query);
            $db->execute();

        } catch (Exception $e) {
            Log::add('SQL error in emundus pdf library at query : '.$query, Log::ERROR, 'com_emundus');
            return false;
        }

        @unlink(EMUNDUS_PATH_ABS.$user_id.DS.$file['filename']);
    }

    // Common tags to use.
    $post = [
        'TRAINING_CODE' 	=> $training,
        'TRAINING_PROGRAMME'=> $campaign['label'],
        'USER_NAME' 		=> $user->name,
        'USER_EMAIL' 		=> $user->email,
        'FNUM' 				=> $fnum
    ];

    $tags = $m_emails->setTags($user_id, $post, $fnum, '', $letter->body);
    $htmldata = "";
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($current_user->name);
    $pdf->SetTitle($letter->title);

    // set margins
    $pdf->SetMargins(5, 40, 5);

    $pdf->footer = $letter->footer;

    //get logo
    preg_match('#src="(.*?)"#i', $letter->header, $tab);
    $pdf->logo = JPATH_BASE.DS.$tab[1];

    preg_match('#src="(.*?)"#i', $letter->footer, $tab);
    $pdf->logo_footer = JPATH_BASE.DS.@$tab[1];

    unset($logo);
    unset($logo_footer);

    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    // set default font subsetting mode
    $pdf->setFontSubsetting(true);
    // set font
    $pdf->SetFont('freeserif', '', 8);

    $letter->body = $m_emails->setTagsFabrik($letter->body, array($fnum));

    $htmldata .= preg_replace($tags['patterns'], $tags['replacements'], preg_replace("/<span[^>]+\>/i", "", preg_replace("/<\/span\>/i", "", preg_replace("/<br[^>]+\>/i", "<br>", $letter->body))));

    $pdf->AddPage();

    $pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $htmldata, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

    chdir('tmp');

    $name = $attachment['lbl'].'_'.date('Y-m-d_H-i-s').'.pdf';

    $pdf->Output(EMUNDUS_PATH_ABS.$user_id.DS.$name, 'F');

    $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;

    if ($error == 0) {

        try {

            $query = 'INSERT INTO #__emundus_uploads (user_id, attachment_id, filename, description, can_be_deleted, can_be_viewed, campaign_id, fnum) VALUES ('.$user_id.', '.$letter->attachment_id.', "'.$name.'","'.$training.' '.date('Y-m-d H:i:s').'", 0, 1, '.$campaign['id'].', '.$db->Quote($fnum).')';
            $db->setQuery($query);
            $db->execute();

        } catch (Exception $e) {
            Log::add('SQL error in emundus pdf library at query : '.$query, Log::ERROR, 'com_emundus');
        }

        return $path;
    }

}


/** Generate the letter result
 *
 * @param   int  $user_id  the user ID
 * @param   bool Eligibility ID of the evaluation
 * @param   String Code of the programme
 * @param   int Campaign id
 * @param   int Evaluation id
 * @param   mixed output format
 * @param   String File number
 *
 * @return Array Files
 * @throws \PhpOffice\PhpWord\Exception\Exception
 */
function letter_pdf ($user_id, $eligibility, $training, $campaign_id, $evaluation_id, $output = true, $fnum = null) {
    set_time_limit(0);
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'emails.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'evaluation.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'campaign.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');

    $current_user 	= JFactory::getUser();
    $db 			= JFactory::getDBO();
    $config 		= JFactory::getConfig();
    $jdate 			= JFactory::getDate();
    $app			= JFactory::getApplication();

    $timezone = new DateTimeZone($config->get('offset'));
    $jdate->setTimezone($timezone);
    $now = $jdate->toSql();

    $files = array();

    $m_application 	= new EmundusModelApplication;
    $m_evaluation 	= new EmundusModelEvaluation;
    $m_campaign 	= new EmundusModelCampaign;
    $m_emails 		= new EmundusModelEmails;

    $letters = $m_evaluation->getLettersTemplate($eligibility, $training);

    if (!empty($letters)) {
        try {

            $query = "SELECT * FROM #__emundus_setup_teaching_unity
					WHERE published=1 AND date_start > '".$now."' AND code IN (".$db->Quote($letters[0]['training']).")
					ORDER BY date_start ASC";
            $db->setQuery($query);
            $courses = $db->loadAssocList();

        } catch (Exception $e) {
            Log::add('SQL Error in Emundus pdf library at query : '.$query, Log::ERROR, 'com_emundus');
        }
    }



    $courses_list = '';
    $courses_fee = ' ';

    if (!empty($courses)) {
        foreach ($courses as $c) {
            $ds = !empty($c['date_start']) ? date(Text::_('DATE_FORMAT_LC3'), strtotime($c['date_start'])) : Text::_('NOT_DEFINED');
            $de = !empty($c['date_end']) ? date(Text::_('DATE_FORMAT_LC3'), strtotime($c['date_end'])) : Text::_('NOT_DEFINED');
            $courses_list .= '<img src="'.JPATH_BASE.DS."media".DS."com_emundus".DS."images".DS."icones".DS."checkbox-unchecked_16x16.png".'" width="8" height="8" align="left" /> ';
            $courses_list .= $ds.' - '.$de.'<br />';
            $courses_fee  .= 'Euro '.$c['price'].',-- ';
        }
    }

    $campaign = $m_campaign->getCampaignByID($campaign_id);

    // Extend the TCPDF class to create custom Header and Footer
	if (!class_exists('MYPDF')) {
		class MYPDF extends TCPDF {

			var $logo = "";
			var $logo_footer = "";
			var $footer = "";

			//Page header
			public function Header() {
				// Logo
				if (is_file($this->logo)) {
					$this->Image($this->logo, 0, 0, 200, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
				}
				// Set font
				$this->SetFont('helvetica', 'B', 16);
				// Title
				$this->Cell(0, 15, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
			}

			// Page footer
			public function Footer() {
				// Position at 15 mm from bottom
				$this->SetY(-15);
				// Set font
				$this->SetFont('helvetica', 'I', 8);
				// Page number
				$this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
				// footer
				$this->writeHTMLCell($w = 0, $h = 0, $x = '', $y = 250, $this->footer, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
				//logo
				if (is_file($this->logo_footer)) {
					$this->Image($this->logo_footer, 150, 280, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
				}
			}
		}
	}

    //
    // Evaluation result
    //
    if ($evaluation_id > 0) {
        $evaluation = $m_evaluation->getEvaluationByID($evaluation_id);
        $reasons = $m_evaluation->getEvaluationReasons($evaluation_id);
        unset($evaluation[0]["id"]);
        unset($evaluation[0]["user"]);
        unset($evaluation[0]["time_date"]);
        unset($evaluation[0]["student_id"]);
        unset($evaluation[0]["parent_id"]);
        unset($evaluation[0]["campaign_id"]);
        unset($evaluation[0]["comment"]);

        if(empty($evaluation[0]["reason"])) {
            unset($evaluation[0]["reason"]);
            unset($evaluation[0]["reason_other"]);
        } elseif(empty($evaluation[0]["reason_other"])) {
            unset($evaluation[0]["reason_other"]);
        }

        include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'list.php');
        $evaluation_details = @EmundusHelperList::getElementsDetailsByName('"'.implode('","', array_keys($evaluation[0])).'"');

        $result = "";
        foreach ($evaluation_details as $ed) {
            if ($ed->hidden==0 && $ed->published==1 && $ed->tab_name=="jos_emundus_evaluations" && $ed->element_name=="reason") {
                $result .= '<ul>';
                foreach ($evaluation as $e) {
                    foreach ($reasons as $reason) {
                        $result .= '<li>' . $reason . '</li>';
                    }
                }
                if (@!empty($evaluation[0]["reason_other"])) {
	                $result .= '<ul><li>'.@$evaluation[0]["reason_other"].'</li></ul>';
                }
                $result .= '</ul>';
            }
        }
    }

    //
    // Replacement
    //
    $post = [
    	'TRAINING_CODE' => $training,
        'TRAINING_PROGRAMME' => $campaign['label'],
        'REASON' => @$result,
        'TRAINING_FEE' => $courses_fee,
        'TRAINING_PERIODE' => $courses_list,
        'USER_NAME' => $current_user->name,
        'USER_EMAIL' => $current_user->email,
        'FNUM' => $fnum
	];

    foreach ($letters as $letter) {
        $error = 0;

        $attachment = $m_application->getAttachmentByID($letter['attachment_id']);

        try {

            // Test if letter type has already been created for that user/campaign/attachment and delete before if true.
            $query = 'SELECT * FROM #__emundus_uploads WHERE user_id='.$user_id.' AND attachment_id='.$letter['attachment_id'].' AND campaign_id='.$campaign_id. ' AND fnum like '.$db->Quote($fnum);
            $db->setQuery($query);
            $file = $db->loadAssoc();

        } catch (Exception $e) {
            Log::add('SQL Error in emundus pdf library at query : '.$query, Log::ERROR, 'com_emundus');
        }
        // test if directory exist
        if (!file_exists(EMUNDUS_PATH_ABS.$user_id)) {
            mkdir(EMUNDUS_PATH_ABS.$user_id, 0755, true);
            chmod(EMUNDUS_PATH_ABS.$user_id, 0755);
        }

        if (!empty($file) && strpos($file['filename'], 'lock') === false && $letter['template_type'] != 4) {

            try {

                $query = 'DELETE FROM #__emundus_uploads WHERE user_id='.$user_id.' AND attachment_id='.$letter['attachment_id'].' AND campaign_id='.$campaign_id. ' AND fnum like '.$db->Quote($fnum).' AND filename NOT LIKE "%lock%"';
                $db->setQuery($query);
                $db->execute();

            } catch (Exception $e) {
                Log::add('SQL error in emundus pdf library at query : '.$query, Log::ERROR, 'com_emundus');
            }

            @unlink(EMUNDUS_PATH_ABS.$user_id.DS.$file['filename']);
        }

        if ($letter['template_type'] == 1) { // Static file

            $file_path = explode(DS, $letter['file']);
            $file_type = explode('.', $file_path[count($file_path)-1]);
            $name = $attachment['lbl'].'_'.date('Y-m-d_H-i-s').'.'.$file_type[1];

            if (file_exists(JPATH_BASE.$letter['file'])) {
                $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;
                $url  = EMUNDUS_PATH_REL.$user_id.'/'.$name;
                copy(JPATH_BASE.$letter['file'], $path);
            } else {
                $app->enqueueMessage($name.' - '.Text::_("TEMPLATE_FILE_MISSING").' : '.JPATH_BASE.$letter['file'], 'error');
                $error++;
            }

        } elseif ($letter['template_type'] == 3) { // Template file .docx

	        require_once (JPATH_LIBRARIES.DS.'emundus'.DS.'vendor'.DS.'autoload.php');
            $tags = $m_emails->setTagsWord($user_id, $post, $fnum);

            $file_path = explode(DS, $letter['file']);
            $file_type = explode('.', $file_path[count($file_path)-1]);
            $name = $attachment['lbl'].'_'.date('Y-m-d_H-i-s').'.'.$file_type[1];

            if (file_exists(JPATH_BASE.$letter['file'])) {

                $PHPWord = new \PhpOffice\PhpWord\PhpWord();
                $document = new \PhpOffice\PhpWord\TemplateProcessor(JPATH_BASE.$letter['file']);

                for ($i = 0; $i < count($tags['patterns']); $i++) {
                    $document->setValue($tags['patterns'][$i], $tags['replacements'][$i]);
                }

                $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;
                $url  = EMUNDUS_PATH_REL.$user_id.'/'.$name;

                $document->save($path);
                unset($document);
            } else {
                $app->enqueueMessage($name.' - '.Text::_("TEMPLATE_FILE_MISSING").' : '.JPATH_BASE.$letter['file'], 'error');
                $error++;
            }

        } elseif ($letter['template_type'] == 4) { // Applicant file
            $upload_file = $m_application->getAttachmentsByFnum($fnum, $letter['attachment_id']);
            $name = $upload_file[0]->filename;
            if (file_exists(JPATH_BASE.$letter['file'])) {
                $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;
                $url  = EMUNDUS_PATH_REL.$user_id.'/'.$name;
            } else {
                $app->enqueueMessage($name.' - '.Text::_("TEMPLATE_FILE_MISSING").' : '.JPATH_BASE.$letter['file'], 'error');
                $error++;
            }

        } else { // From HTML : $letter['template_type'] == 2
            $tags = $m_emails->setTags($user_id, $post, $fnum, '', $letter["body"]);
            $htmldata = "";
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($current_user->name);
            $pdf->SetTitle($letter['title']);

            // set margins
            $pdf->SetMargins(5, 40, 5);

            $pdf->footer = $letter["footer"];

            //get logo
            preg_match('#src="(.*?)"#i', $letter['header'], $tab);
            $pdf->logo = JPATH_BASE.DS.$tab[1];

            preg_match('#src="(.*?)"#i', $letter['footer'], $tab);
            $pdf->logo_footer = JPATH_BASE.DS.@$tab[1];

            unset($logo);
            unset($logo_footer);

            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            // set default font subsetting mode
            $pdf->setFontSubsetting(true);
            // set font
            $pdf->SetFont('freeserif', '', 8);

            $letter["body"] = $m_emails->setTagsFabrik($letter["body"], array($fnum));

            $htmldata .= preg_replace($tags['patterns'], $tags['replacements'], preg_replace("/<span[^>]+\>/i", "", preg_replace("/<\/span\>/i", "", preg_replace("/<br[^>]+\>/i", "<br>", $letter["body"]))));


            $pdf->AddPage();

            // Print text using writeHTMLCell()
            $pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $htmldata, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
			chdir('tmp');

            $name = $attachment['lbl'].'_'.date('Y-m-d_H-i-s').'.pdf';
            if ($output) {
	            $pdf->Output(EMUNDUS_PATH_ABS.$user_id.DS.$name, $output);
            } else {
	            $pdf->Output(EMUNDUS_PATH_ABS.$user_id.DS.$name, 'F');
            }
            $path = EMUNDUS_PATH_ABS.$user_id.DS.$name;
            $url  = EMUNDUS_PATH_REL.$user_id.'/'.$name;
        }

        if ($error == 0) {
            if ($letter['template_type'] == 4) {
                $id = $upload_file[0]->id;
            } else {

                try {

                    $query = 'INSERT INTO #__emundus_uploads (user_id, attachment_id, filename, description, can_be_deleted, can_be_viewed, campaign_id, fnum) VALUES ('.$user_id.', '.$letter['attachment_id'].', "'.$name.'","'.$training.' '.date('Y-m-d H:i:s').'", 0, 1, '.$campaign_id.', '.$db->Quote($fnum).')';
                    $db->setQuery($query);
                    $db->execute();
                    $id = $db->insertid();

                } catch (Exception $e) {
                    Log::add('SQL error in emundus pdf library at query : '.$query, Log::ERROR, 'com_emundus');
                }
            }
            $file_info['id'] = $id;
            $file_info['path'] = $path;
            $file_info['attachment_id'] = $letter['attachment_id'];
            $file_info['name'] = $attachment['value'];
            $file_info['url'] = $url;

            $files[] = $file_info;
        }
    }

    return $files;
}


// @description Generate the letter template result
// @params Applicant user ID
// @params Eligibility ID of the evaluation
// @params Code of the programme
// @params Type of output

function letter_pdf_template ($user_id, $letter_id, $fnum = null) {
    set_time_limit(0);
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'config'.DS.'lang'.DS.'eng.php');
    require_once(JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'emails.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'evaluation.php');
    include_once(JPATH_BASE.DS.'components'.DS.'com_emundus'.DS.'models'.DS.'application.php');

    $current_user 	= JFactory::getUser();
    $db 			= JFactory::getDBO();
    $config 		= JFactory::getConfig();
    $jdate 			= JFactory::getDate();

    $timezone = new DateTimeZone($config->get('offset'));
    $jdate->setTimezone($timezone);
    $now = $jdate->toSql();

    $m_application 	= new EmundusModelApplication;
    $m_evaluation 	= new EmundusModelEvaluation;
    $m_emails 		= new EmundusModelEmails;

    $letters = $m_evaluation->getLettersTemplateByID($letter_id);

    try {

        $query = "SELECT * FROM #__emundus_setup_teaching_unity
					WHERE published=1 AND date_start>'".$now."' AND code IN (".$letters[0]['training'].")
					ORDER BY date_start ASC";
        $db->setQuery($query);
        $courses = $db->loadAssocList();

    } catch (Exception $e) {
        Log::add('SQL error in emundus pdf library at query : '.$query, Log::ERROR, 'com_emundus');
    }

    $courses_list = '';
    $courses_fee = ' ';
    foreach ($courses as $c) {
        $ds = !empty($c['date_start']) ? date(Text::_('DATE_FORMAT_LC3'), strtotime($c['date_start'])) : Text::_('NOT_DEFINED');
        $de = !empty($c['date_end']) ? date(Text::_('DATE_FORMAT_LC3'), strtotime($c['date_end'])) : Text::_('NOT_DEFINED');
        $courses_list .= '<img src="'.JPATH_BASE.DS."media".DS."com_emundus".DS."images".DS."icones".DS."checkbox-unchecked_16x16.png".'" width="8" height="8" align="left" /> ';
        $courses_list .= $ds.' - '.$de.'<br />';
        $courses_fee  .= 'Euro '.$c['price'].'<br>';
        $programme = $c['label'];
    }

    // Extend the TCPDF class to create custom Header and Footer
    class MYPDF extends TCPDF {

        var $logo = "";
        var $logo_footer = "";
        var $footer = "";

        //Page header
        public function Header() {
            // Logo
            if (is_file($this->logo)) {
	            $this->Image($this->logo, 0, 0, 200, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
            // Set font
            $this->SetFont('helvetica', 'B', 16);
            // Title
            $this->Cell(0, 15, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            // Page number
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            // footer
            $this->writeHTMLCell($w=0, $h=0, $x='', $y=250, $this->footer, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
            //logo
            if (is_file($this->logo_footer)) {
	            $this->Image($this->logo_footer, 150, 280, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
        }
    }

    //
    // Replacement
    //
    $post = [
    	'TRAINING_CODE' => @$letters[0]['training'],
        'TRAINING_PROGRAMME' => @$programme,
        'REASON' => Text::_("DEPEND_OF_EVALUATION"),
        'TRAINING_FEE' => @$courses_fee,
        'TRAINING_PERIODE' => @$courses_list
    ];
    $tags = $m_emails->setTags($user_id, $post, $fnum);

    foreach ($letters as $letter) {
        $attachment = $m_application->getAttachmentByID($letter['attachment_id']);

        if ($letter['template_type'] == 1) { // Static file
            $file_path = explode(DS, $letter['file']);
            $file_type = explode('.', $file_path[count($file_path)-1]);
            $name = date('Y-m-d_H-i-s').$attachment['lbl'].'.'.$file_type[1];

            $file = JPATH_BASE.$letter['file'];
            if (file_exists($file)) {
                $mime_type = get_mime_type($file);
                header('Content-type: application/'.$mime_type);
                header('Content-Disposition: inline; filename='.basename($file));
                header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Cache-Control: pre-check=0, post-check=0, max-age=0');
                header('Pragma: anytextexeptno-cache', true);
                header('Cache-control: private');
                header('Expires: 0');

                ob_clean();
                flush();
                readfile($file);
                exit;
            } else {
                JError::raiseWarning( 500, Text::_( 'FILE_NOT_FOUND' ).' '.$file );
            }

        } elseif ($letter['template_type'] == 3) { // Template file .docx
            require_once JPATH_LIBRARIES.DS.'PHPWord.php';

            $file_path = explode(DS, $letter['file']);
            $file_type = explode('.', $file_path[count($file_path)-1]);
            $name = date('Y-m-d_H-i-s').$attachment['lbl'].'.'.$file_type[1];

            $PHPWord = new PHPWord();

            $document = new \PhpOffice\PhpWord\TemplateProcessor(JPATH_BASE.$letter['file']);

            for ($i = 0; $i < count($tags['patterns']); $i++) {
                $document->setValue($tags['patterns'][$i], $tags['replacements'][$i]);
            }

            $document->save(JPATH_BASE.DS.'tmp'.DS.$name);

            $file = JPATH_BASE.DS.'tmp'.DS.$name;
            if (file_exists($file)) {
                $mime_type = get_mime_type($file);
                header('Content-type: application/'.$mime_type);
                header('Content-Disposition: inline; filename='.basename($file));
                header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Cache-Control: pre-check=0, post-check=0, max-age=0');
                header('Pragma: anytextexeptno-cache', true);
                header('Cache-control: private');
                header('Expires: 0');

                ob_clean();
                flush();
                readfile($file);
                exit;
            } else {
                JError::raiseWarning( 500, Text::_( 'FILE_NOT_FOUND' ).' '.$file );
            }

            unset($document);

        } else { // From HTML
            $htmldata = "";

            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($current_user->name);
            $pdf->SetTitle($letter['title']);

            // set margins
            $pdf->SetMargins(5, 40, 5);

            $pdf->footer = $letter["footer"];

            //get logo
            preg_match('#src="(.*?)"#i', $letter['header'], $tab);
            $pdf->logo = JPATH_BASE.DS.$tab[1];

            preg_match('#src="(.*?)"#i', $letter['footer'], $tab);
            $pdf->logo_footer = JPATH_BASE.DS.$tab[1];

            unset($logo);
            unset($logo_footer);

            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            // set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            // set default font subsetting mode
            $pdf->setFontSubsetting(true);
            // set font
            $pdf->SetFont('freeserif', '', 10);

            $htmldata .= preg_replace($tags['patterns'], $tags['replacements'], preg_replace("/<span[^>]+\>/i", "", preg_replace("/<\/span\>/i", "", preg_replace("/<br[^>]+\>/i", "<br>", $letter["body"]))));
            $pdf->AddPage();

            // Print text using writeHTMLCell()
            $pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $htmldata, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

            chdir('tmp');
            $pdf->Output(EMUNDUS_PATH_ABS.$user_id.DS."demo", 'I');
        }
    }
    exit();
}

function data_to_img($match) {
    list(, $img, $type, $base64, $end) = $match;

    $bin = base64_decode($base64);
    $md5 = md5($bin);   // generate a new temporary filename
    $fn = "tmp/$md5.$type";
    file_exists($fn) or file_put_contents($fn, $bin);

    return "$img$fn$end";  // new <img> tag
}

/**
 * @param $user_id
 * @param $fnum
 * @param $output
 * @param $form_post
 * @param $form_ids
 * @param $options
 * @param $application_form_order
 * @param $profile_id
 * @param $file_lbl
 * @param $elements
 * @param $attachments
 * @return false|string|void
 * @throws Exception
 */
function application_form_pdf($user_id, $fnum = null, $output = true, $form_post = 1, $form_ids = null, $options = [], $application_form_order = null, $profile_id = null, $file_lbl = null, $elements = null, $attachments = true) {
	jimport('joomla.html.parameter');
    set_time_limit(0);
    require_once (JPATH_SITE.'/components/com_emundus/helpers/date.php');
    require_once (JPATH_SITE.'/components/com_emundus/helpers/emails.php');
    require_once(JPATH_SITE.'/components/com_emundus/models/application.php');
    require_once(JPATH_SITE.'/components/com_emundus/models/profile.php');
    require_once(JPATH_SITE.'/components/com_emundus/models/files.php');
    require_once(JPATH_SITE.'/components/com_emundus/models/form.php');

	$db = Factory::getContainer()->get('DatabaseDriver');
	$app = Factory::getApplication();

	if (is_null($options)) {
		$options = [];
	}

	$step_types = [1];
	if (in_array('eval_steps', $options)) {
		$step_types[] = 2;
	}

    if (empty($file_lbl)) {
        $file_lbl = "_application";
    }

    $eMConfig = ComponentHelper::getParams('com_emundus');
    $cTitle = $eMConfig->get('export_application_pdf_title_color', '#000000'); //déclaration couleur principale

    $config = $app->getConfig();

    $m_profile = new EmundusModelProfile;
    $m_application = new EmundusModelApplication;
    $m_files = new EmundusModelFiles;
    $m_form = new EmundusModelform;

    $user = $m_profile->getEmundusUser($user_id);
    $fnum = empty($fnum) ? $user->fnum : $fnum;

    $infos = $m_profile->getFnumDetails($fnum);
    $campaign_id = $infos['campaign_id'];

    // Get form HTML

//    if ($form_post == 1 && (empty($form_ids) || is_null($form_ids)) && !empty($elements) && !is_null($elements)) {
    if (isset($form_post)) {
	    try {
		    $anonymize_data = EmundusHelperAccess::isDataAnonymized($app->getIdentity()->id) || $user->is_anonym;

		    $photo_attachment_id = $eMConfig->get('photo_attachment', 10);

		    // Users informations
		    $query = $db->getQuery(true);
			$query->select('u.id as user_id, c.firstname, c.lastname, a.filename AS avatar, p.label AS cb_profile, c.profile, esc.label, esc.year AS cb_schoolyear, esc.training, u.id, u.registerDate, u.email, epd.gender, epd.nationality, epd.birth_date, ed.user, ecc.date_submitted')
				->from('#__emundus_campaign_candidature AS ecc')
				->leftJoin('#__users AS u ON u.id=ecc.applicant_id')
				->leftJoin('#__emundus_users AS c ON u.id = c.user_id')
				->leftJoin('#__emundus_setup_campaigns AS esc ON esc.id = ' . $campaign_id)
				->leftJoin('#__emundus_uploads AS a ON a.user_id=u.id AND a.attachment_id = ' . $db->quote($photo_attachment_id) . ' AND a.fnum like ' . $db->quote($fnum))
				->leftJoin('#__emundus_setup_profiles AS p ON p.id = esc.profile_id')
				->leftJoin('#__emundus_personal_detail AS epd ON epd.user = u.id AND epd.fnum like ' . $db->quote($fnum))
				->leftJoin('#__emundus_declaration AS ed ON ed.user = u.id AND ed.fnum like ' . $db->quote($fnum))
				->where('ecc.fnum like ' . $db->Quote($fnum))
				->order('esc.id DESC');
		    $db->setQuery($query);
		    $item = $db->loadObject();

		    /* GET LOGO */
			$logo = EmundusHelperEmails::getLogo(false,$item->training);

		    $type = pathinfo($logo, PATHINFO_EXTENSION);
		    $data = file_get_contents($logo);
		    $logo_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		    /* END LOGO */

	        $htmldata = '';
	        $forms = '';
		    if (!$anonymize_data) {
				$title = strtoupper(@$item->lastname) . ' ' . @$item->firstname;
		    } else {
				$title = $config->get('sitename');
		    }

			$htmldata .= '<html>
				<head>
				  <title>'.$title.'</title>
				  <meta name="author" content="eMundus">
				</head>
				<body>';
			$htmldata .= '<header><table style="width: 100%"><tr><td><img src="'. $logo_base64 .'" width="auto" height="60"/></td><td style="text-align: right">';

            $allowed_attachments = EmundusHelperAccess::getUserAllowedAttachmentIDs(JFactory::getUser()->id);

            if ($options[0] != "0") {
                $date_submitted = (!empty($item->date_submitted) && strpos($item->date_submitted, '0000') === false) ? EmundusHelperDate::displayDate($item->date_submitted) : Text::_('NOT_SENT');

                // Create an date object
                $date_printed = new Date();
                //Use helper date function to set timezone an format
	            $date_printed = EmundusHelperDate::displayDate($date_printed, 'DATE_FORMAT_LC2', 0);

	            if (!$anonymize_data) {
		            $htmldata .= '<p><b>' . Text::_('PDF_HEADER_INFO_CANDIDAT') . ' :</b> ' . @$item->firstname . ' ' . strtoupper(@$item->lastname) . '</p>';
	            }

	            if (!$anonymize_data && in_array("aemail", $options)) {
		            $htmldata .= '<p><b>' . Text::_('EMAIL') . ' :</b> ' . @$item->email . '</p>';
	            }
	            if (in_array("afnum", $options)) {
		            $htmldata .= '<p><b>' . Text::_('FNUM') . ' :</b> ' . $fnum . '</p>';
	            }
                $htmldata .= '</td></tr></table><hr/></header>';

                $htmldata .= '<table width="100%">';

                //$htmldata .= '<tr><td><h3>' . Text::_('PDF_HEADER_INFO_CANDIDAT') . '</h3></td></tr>';
				if(!empty($item->avatar) && is_image_ext($item->avatar) && ($allowed_attachments === true || in_array('10', $allowed_attachments)))
				{
					if (file_exists(EMUNDUS_PATH_ABS . @$item->user_id . '/tn_' . @$item->avatar))
					{
						$avatar        = EMUNDUS_PATH_ABS . @$item->user_id . '/tn_' . @$item->avatar;
					}
					elseif (file_exists(EMUNDUS_PATH_ABS . @$item->user_id . '/' . @$item->avatar) && !empty($item->avatar) && is_image_ext($item->avatar))
					{
						$avatar        = EMUNDUS_PATH_ABS . @$item->user_id . '/' . @$item->avatar;
					}

					if(!empty($avatar))
					{
						$type          = pathinfo($avatar, PATHINFO_EXTENSION);
						$data          = file_get_contents($avatar);
						$avatar_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

						$htmldata .= '<tr><td><img style="border-radius: 50%" src="'. $avatar_base64 .'" width="auto" height="60" align="right"/></td></tr>';
					}
				}

                if (in_array("aid", $options)) {
                    $htmldata .=
                        '<tr>
                                    <td class="idcandidat"><b>' . Text::_('ID_CANDIDAT') . ' :</b> ' . @$item->user_id . '</td>
                                </tr>';
                }

                $htmldata .= '<tr><td><h3>' . Text::_('PDF_HEADER_INFO_DOSSIER') . '</h3></td></tr><tr><td class="name">' . @$item->label . ' (' . @$item->cb_schoolyear . ')</td></tr>';

                if (in_array("afnum", $options)) {
                    $htmldata .= '<tr class="nationality"><td><b>' . Text::_('FNUM') . ' :</b> ' . $fnum . '</td></tr>';
                }

                if (in_array("aapp-sent", $options)) {
                    $htmldata .= '<tr><td class="statut"><b>' . Text::_('APPLICATION_SENT_ON') . ' :</b> ' . $date_submitted . '</td></tr>';
                }

                if (in_array("adoc-print", $options)) {
                    $htmldata .= '<tr class="sent"><td><b>' . Text::_('DOCUMENT_PRINTED_ON') . ' :</b> ' . $date_printed . '</td></tr>';
                }

                if (in_array("status", $options)) {
                    $status = $m_files->getStatusByFnums(explode(',', $fnum));
                    $htmldata .= '<tr class="sent"><td><b>' . Text::_('COM_EMUNDUS_EXPORTS_PDF_STATUS') . ' :</b> ' . $status[$fnum]['value'] . '</td></tr>';
                }
	            if ($attachments) {
		            $uploads = $m_application->getUserAttachmentsByFnum($fnum, '');
					$files_updated = count($uploads) > 1 ? Text::_('COM_EMUNDUS_ATTACHMENTS_FILES_UPLOADED') : Text::_('COM_EMUNDUS_ATTACHMENTS_FILE_UPLOADED');
		            $htmldata .= '<tr class="sent"><td><b>' . $files_updated . ' :</b>' . ' ' . count($uploads) . '</a></td></tr>';
	            }

				$addonRepository = new AddonRepository();
				$choicesAddon = $addonRepository->getByName('choices');
				if(!empty($choicesAddon) && $choicesAddon->getValue()->isEnabled()){
					$applicationChoicesRepository = new ApplicationChoicesRepository();
					$applicationChoices = $applicationChoicesRepository->getChoicesByFnum($fnum);

					if(!empty($applicationChoices)){
						$htmldata .= '<tr><td><h3>' . Text::_('COM_EMUNDUS_APPLICATION_CHOICES') . '</h3></td></tr>';
						foreach ($applicationChoices as $key => $choice) {
							$htmldata .= '<tr class="sent"><td><b>' . Text::sprintf('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO', ($key + 1)) . ' :</b> ' . $choice->getCampaign()->getLabel() . '</td></tr>';
						}
					}
				}


				$htmldata .= '</table>';

                if (in_array("tags", $options)) {
                    $tags = $m_files->getTagsByFnum(explode(',', $fnum));
                    $htmldata .= '<table style="margin-top: 8px" class="tags-table"><tr><td> ';
                    foreach ($tags as $tag) {
	                    if(EmundusHelperAccess::asAccessAction(14 ,'r', $app->getIdentity()->id, $fnum) || (EmundusHelperAccess::asAccessAction(14 ,'c', $app->getIdentity()->id, $fnum) && $tag['user_id'] === $app->getIdentity()->id))
	                    {
		                    $class = str_replace('label-', '', $tag['class']);
		                    $htmldata .= '<span class="sticker label-' . $class . '">' . $tag['label'] . '</span>&nbsp;';
		                    //$htmldata .= '<div class="sticker label-' . $class . '"><span class="circle"></span><span class="tw-text-white tw-truncate tw-font-semibold tw-w-[150px] tw-text-sm">' . $tag['label'] . '</span></div>';
	                    }
                    }
                    $htmldata .= '</td></tr></table>';
                }
                $htmldata .= '<hr>';
            }
        } catch (Exception $e) {
            Log::add('SQL error in emundus pdf library at query : ' . $query, Log::ERROR, 'com_emundus');
        }

	    if ($form_post == 1 && empty($form_ids) && !empty($elements)) {
            $profile_menu = array_keys($elements);

            // Get form HTML
            $group_list = array_values($elements);

            foreach ($profile_menu as $key => $value) {
                $profile_id = $value;
                $fids = $elements[$profile_id]['fids'];
                $gids = $elements[$profile_id]['gids'];
                $eids = $elements[$profile_id]['eids'];

                if(sizeof($profile_menu) > 1) {
                    if($key != 0) {
                        $forms .= '<br pagebreak="true" class="page-break"/>';
                    }

					$profile_data = $m_form->getProfileLabelByProfileId($profile_id);
					if (!empty($profile_data)) {
						$forms .= '<h1>' . $m_form->getProfileLabelByProfileId($profile_id)->label . '</h1>';
					}
                }


                $forms .= $m_application->getFormsPDF($user_id, $fnum, $fids, $gids, $profile_id, $eids, $attachments, $step_types);
            }
        }
        else {
			$eids = !empty($elements) ? $elements[key($elements)]['eids'] : null;
			$forms = $m_application->getFormsPDF($user_id, $fnum, $form_ids, $application_form_order, $profile_id, $eids, $attachments, $step_types);
		}
        /*** Applicant   ***/
	    $htmldata .= "
			<style>
					@page { 
						margin: 130px 25px; 
					}
					header { position: fixed; top: -120px; left: 0px; right: 0px; }
					header hr {
						border: none;
						height: 1px;
						background-color: #A4A4A4;
					}
					.page-break { page-break-before: always; }
					hr {
						border: solid 1px black;
					}
					h2 {
						font-size: 18px;
						line-height: 16px;
						margin-top: 4px;
						margin-bottom: 0;
					}
					h2.pdf-page-title{
					    background-color: #EAEAEA;
					    padding: 10px 12px;
					    border-radius: 2px;
					    margin-right: 16px;
					    color: ".$cTitle."
					}
					h3 {
					  font-style: normal;
					  font-weight: 600;
					  font-size: 16px;
					  line-height: 14px;
					  margin-bottom: 8px;
                    }
                    h3.group{
                      padding-left: 16px;
                    }
                    td{
                    	font-size: 12px;
                    }
                    .pdf-forms{
                   	   border-spacing: 0;
                    }
                    .pdf-repeat-count{
                       margin-top: 12px;
                       margin-bottom: 6px;
                       padding-left: 16px; 
                    }
                    .pdf-forms th{
                       font-size: 12px;
                       font-weight: 400;
                    }
                    .pdf-forms th.background{
                       background-color: #EDEDED;
                       border-top: solid 1px #A4A4A4;
                       border-left: solid 1px #A4A4A4;
                       border-right: solid 1px #A4A4A4;
                    }
                    table.pdf-forms{
                       width: 100%;
                       page-break-inside:auto;
                       padding: 0 16px;
                    }
                    .pdf-forms tr{
                       page-break-inside:avoid; 
                       page-break-after:auto
                    }
                    .pdf-forms td{
                       border-collapse: collapse;
                       padding: 8px;
                       width: 100%;
                       border-left: solid 1px #A4A4A4;
  					   border-top: solid 1px #A4A4A4;
                    }
                    .pdf-forms tr td:first-child {
  					   width: 30%;
					}
                    .pdf-forms tr td:nth-child(2){
                       width:70%; 
                       border-right: solid 1px #A4A4A4;
                    }
                    .pdf-forms td.background-light{
                       width: auto;
                    }
                    .pdf-forms tr td[colspan='2']{
                       border-right: solid 1px #A4A4A4;
                    }
                    .pdf-forms tr:last-child td{
                       border-bottom: solid 1px #A4A4A4;
                    }
                    .pdf-forms tr:last-child td.background-light{
                       border-right: solid 1px #A4A4A4 !important;
                    }
                    .pdf-attachments{
                       font-size: 14px;
                    }
                    .pdf-attachments li {
                       margin-bottom: 6px;
                    }
                    @media print {
                        .breaker{
                            page-break-before: always;
                        }
                    }
                    .label {color:black;padding: 6px 12px;border-radius: 4px;}
		            .label-default, .label-null, .label- {
				        background-color: #b4b4b4 !important;
				        color: #282828;
				        text-shadow: none;
				    }
				
				    .label-text-default, .label-text-null {
				        color: #b4b4b4 !important;
				    }
				
				    .label-default-500, .label-null-500, .label-500 {
				        background-color: #b4b4b4 !important;
				    }
				
				    .label-red-1 {
				        background-color: #eb0000;
				        text-shadow: none;
				    }
				
				    .label-text-red-1 {
				        color: #eb0000;
				    }
				
				    .label-border-red-1 {
				        border-color: #eb0000;
				    }
				
				    .label-red-2 {
				        background-color: #c00016;
				        text-shadow: none;
				    }
				
				    .label-text-red-2 {
				        color: #c00016;
				    }
				
				    .label-border-red-2 {
				        border-color: #c00016;
				    }
				
				    .label-pink-1 {
				        background-color: #ff78d1;
				        color: var(--neutral-900);
				        text-shadow: none;
				    }
				
				    .label-text-pink-1 {
				        color: #ff78d1;
				    }
				
				    .label-border-pink-1 {
				        border-color: #ff78d1;
				    }
				
				
				    .label-pink-2 {
				        background-color: #e0008a;
				        text-shadow: none;
				    }
				
				    .label-text-pink-2 {
				        color: #e0008a;
				    }
				
				    .label-border-pink-2 {
				        border-color: #e0008a;
				    }
				
				    .label-purple-1 {
				        background-color: #d292ef;
				        color: var(--neutral-900);
				        text-shadow: none;
				    }
				
				    .label-text-purple-1 {
				        color: #d292ef;
				    }
				
				    .label-border-purple-1 {
				        border-color: #d292ef;
				    }
				
				    .label-purple-2 {
				        background-color: #9600c7;
				        text-shadow: none;
				    }
				
				    .label-text-purple-2 {
				        color: #9600c7;
				    }
				
				    .label-border-purple-2 {
				        border-color: #9600c7;
				    }
				
				    .label-light-blue-1 {
				        background-color: #35d7d2;
				        color: var(--neutral-900);
				        text-shadow: none;
				    }
				
				    .label-text-light-blue-1 {
				        color: #35d7d2;
				    }
				
				    .label-border-light-blue-1 {
				        border-color: #35d7d2;
				    }
				
				
				    .label-light-blue-2 {
				        background-color: #308281;
				        text-shadow: none;
				    }
				
				    .label-text-light-blue-2 {
				        color: #308281;
				    }
				
				    .label-border-light-blue-2 {
				        border-color: #308281;
				    }
				
				    .label-blue-1 {
				        background-color: #64d8ff;
				        color: var(--neutral-900);
				        text-shadow: none;
				    }
				
				    .label-text-blue-1 {
				        color: #64d8ff;
				    }
				
				    .label-border-blue-1 {
				        border-color: #64d8ff;
				    }
				
				    .label-blue-2 {
				        background-color: #0073e5;
				        text-shadow: none;
				    }
				
				    .label-text-blue-2 {
				        color: #0073e5;
				    }
				
				    .label-border-blue-2 {
				        border-color: #0073e5;
				    }
				
				    .label-blue-3 {
				        background-color: #0644ae;
				        text-shadow: none;
				    }
				
				    .label-text-blue-3 {
				        color: #0644ae;
				    }
				
				    .label-border-blue-3 {
				        border-color: #0644ae;
				    }
				
				    .label-green-1 {
				        background-color: #98d432;
				        color: var(--neutral-900);
				        text-shadow: none;
				    }
				
				    .label-text-green-1 {
				        color: #98d432;
				    }
				
				    .label-border-green-1 {
				        border-color: #98d432;
				    }
				
				    .label-green-2 {
				        background-color: #008a35;
				        text-shadow: none;
				    }
				
				    .label-text-green-2 {
				        color: #008a35;
				    }
				
				    .label-border-green-2 {
				        border-color: #008a35;
				    }
				
				    .label-yellow-1 {
				        background-color: #ffe014;
				        color: var(--neutral-900);
				        text-shadow: none;
				    }
				
				    .label-text-yellow-1 {
				        color: #ffe014;
				    }
				
				    .label-border-yellow-1 {
				        border-color: #ffe014;
				    }
				
				    .label-yellow-2 {
				        background-color: #ffae00;
				        color: var(--neutral-900);
				        text-shadow: none;
				    }
				
				    .label-text-yellow-2 {
				        color: #ffae00;
				    }
				
				    .label-border-yellow-2 {
				        border-color: #ffae00;
				    }
				
				    .label-orange-1 {
				        background-color: #ff6900;
				        color: var(--neutral-900);
				        text-shadow: none;
				    }
				
				    .label-text-orange-1 {
				        color: #ff6900;
				    }
				
				    .label-border-orange-1 {
				        border-color: #ff6900;
				    }
				
				    .label-orange-2 {
				        background-color: #cc4b00;
				        text-shadow: none;
				    }
				
				    .label-text-orange-2 {
				        color: #cc4b00;
				    }
				
				    .label-border-orange-2 {
				        border-color: #cc4b00;
				    }
				
				    .label-beige {
				        background-color: #ffc285;
				        color: var(--neutral-900);
				        text-shadow: none;
				    }
				
				    .label-text-beige {
				        color: #ffc285;
				    }
				
				    .label-border-beige {
				        border-color: #ffc285;
				    }
				
				    .label-brown {
				        background-color: #8b511f;
				        text-shadow: none;
				    }
				
				    .label-text-brown {
				        color: #8b511f;
				    }
				
				    .label-border-brown {
				        border-color: #8b511f;
				    }
				
				    .label-grey-1 {
				        background-color: #b4b4b4;
				        text-shadow: none;
				        color: var(--neutral-900);
				    }
				
				    .label-text-grey-1 {
				        color: #b4b4b4;
				    }
				
				    .label-border-grey-1 {
				        border-color: #b4b4b4;
				    }
				
				    .label-grey-2 {
				        background-color: #757575;
				        text-shadow: none;
				    }
				
				    .label-text-grey-2 {
				        color: #757575;
				    }
				
				    .label-border-grey-2 {
				        border-color: #757575;
				    }
				
				    .label-black {
				        background-color: #1e1e1e;
				        text-shadow: none;
				        color: var(--neutral-0);
				    }
				
				    .label-text-black {
				        color: #1e1e1e;
				    }
				
				    .label-border-black {
				        border-color: #1e1e1e;
				    }
		            .sticker {
			            border-radius: 20px 8px 8px 20px;
				        padding: 4px 8px;
				        margin: 4px 0;
				        font-size: 12px;
				        max-width: 200px;
				        white-space: break-spaces;
				        color: white;
		            }
			</style>";

//
        /**  END APPLICANT   ****/

        $htmldata .= $forms;

        if (!file_exists(EMUNDUS_PATH_ABS . @$item->user_id)) {
            mkdir(EMUNDUS_PATH_ABS . $item->user_id, 0777, true);
            chmod(EMUNDUS_PATH_ABS . $item->user_id, 0777);
        }

	    $htmldata .= '<script type="text/php">
			        if ( isset($pdf) ) {
			            $x = 570;
			            $y = 760;
			            $text = "{PAGE_NUM} / {PAGE_COUNT}";
			            $font = $fontMetrics->get_font("helvetica", "bold");
			            $size = 8;
			            $color = array(0,0,0);
			            $word_space = 0.0;  //  default
			            $char_space = 0.0;  //  default
			            $angle = 0.0;   //  default
			            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
			        }
    			</script>';
	    $htmldata .= '</body></html>';

	    $filename = EMUNDUS_PATH_ABS . @$item->user_id . DS . $fnum . $file_lbl . '.pdf';

		/** DOMPDF */
	    $options = new Options();
	    $options->set('defaultFont', 'helvetica');
		$options->set('isPhpEnabled', true);
	    $dompdf = new Dompdf($options);
	    $dompdf->addInfo('Producer', '');
	    $dompdf->addInfo('Creator', '');

	    try {
		    $dompdf->loadHtml($htmldata);
		    $dompdf->render();

		    if($output) {
			    $dompdf->stream($filename, array("Attachment" => false));
		    } else {
			    file_put_contents($filename, $dompdf->output());
			    return $filename;
		    }
	    }
	    catch (Exception $e) {
		    Log::add('Error when export following file to PDF : ' . $fnum . ' with error ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		    return false;
	    }
	    /** END */

        chdir('tmp');
    }
}

/**
 * @param         $user_id
 * @param   null  $fnum
 * @param   bool  $output
 * @param   null  $options
 *
 *
 * @throws Exception
 * @since version
 * @deprecated since version 2.0
 */
function application_header_pdf($user_id, $fnum = null, $output = true, $options = null) {
    jimport('joomla.html.parameter');
    set_time_limit(0);

    require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
    require_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
    require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');

	$app = Factory::getApplication();
    $config = $app->getConfig();
    $offset = $config->get('offset');

    $m_profile = new EmundusModelProfile;
    $m_application = new EmundusModelApplication;
    $m_files = new EmundusModelFiles;

    $db = JFactory::getDBO();

    $current_user = $app->getIdentity();
    $user = $m_profile->getEmundusUser($user_id);
    $fnum = empty($fnum) ? $user->fnum : $fnum;

    $infos = $m_profile->getFnumDetails($fnum);
    $campaign_id = $infos['campaign_id'];

    // Get form HTML
    $htmldata = '';

	// replace fpdi with dompdf
	$pdf_options = new Options();
	$pdf_options->set('defaultFont', 'freeserif');
	$pdf_options->set('isPhpEnabled', true);
	$dompdf = new Dompdf($pdf_options);

    try {

        // Users informations
        $query = 'SELECT u.id AS user_id, c.firstname, c.lastname, a.filename AS avatar, p.label AS cb_profile, c.profile, esc.label, esc.year AS cb_schoolyear, esc.training, u.id, u.registerDate, u.email, epd.gender, epd.nationality, epd.birth_date, ed.user, ecc.date_submitted
					FROM #__emundus_campaign_candidature AS ecc
					LEFT JOIN #__users AS u ON u.id=ecc.applicant_id
					LEFT JOIN #__emundus_users AS c ON u.id = c.user_id
					LEFT JOIN #__emundus_setup_campaigns AS esc ON esc.id = ' . $campaign_id . '
					LEFT JOIN #__emundus_uploads AS a ON a.user_id=u.id AND a.attachment_id = ' . EMUNDUS_PHOTO_AID . ' AND a.fnum like ' . $db->Quote($fnum) . '
					LEFT JOIN #__emundus_setup_profiles AS p ON p.id = esc.profile_id
					LEFT JOIN #__emundus_personal_detail AS epd ON epd.user = u.id AND epd.fnum like ' . $db->Quote($fnum) . '
					LEFT JOIN #__emundus_declaration AS ed ON ed.user = u.id AND ed.fnum like ' . $db->Quote($fnum) . '
					WHERE ecc.fnum like ' . $db->Quote($fnum) . '
					ORDER BY esc.id DESC';
        $db->setQuery($query);
        $item = $db->loadObject();

    } catch (Exception $e) {
        Log::add('SQL error in emundus pdf library at query : ' . $query, Log::ERROR, 'com_emundus');
    }

    //get logo
    $template = $app->getTemplate(true);
    $params = $template->params;

    $logo = json_decode(str_replace("'", "\"", $params->get('logo')->custom->image), true);
    $logo = !empty($logo['path']) ? JPATH_ROOT . DS . $logo['path'] : "";

    // manage logo by programme
    $ext = substr($logo, -3);
    $logo_prg = substr($logo, 0, -4) . '-' . $item->training . '.' . $ext;
    if (is_file($logo_prg)) {
        $logo = $logo_prg;
    }

    //get title
    $title = $config->get('sitename');
    if (is_file($logo)) {
        //$pdf->SetHeaderData($logo, 20, $title, PDF_HEADER_STRING);
    }

    unset($logo);
    unset($title);

    /*$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, 'I', PDF_FONT_SIZE_DATA));
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    // set default font subsetting mode
    $pdf->setFontSubsetting(true);
    // set font
    $pdf->SetFont('freeserif', '', 10);
    $pdf->AddPage();
    $dimensions = $pdf->getPageDimensions();*/

    /*** Applicant   ***/
    $htmldata .=
        '<style>
	.card  { border: none; display:block; line-height:80%;}
	.name  { display: block; margin: 0 0 0 20px; padding:0; display:block; line-height:110%;}
	.maidename  { display: block; margin: 0 0 0 20px; padding:0; }
	.nationality { display: block; margin: 0 0 0 20px;  padding:0;}
	.sent { display: block; font-family: monospace; margin: 0 0 0 10px; padding:0; text-align:right;}
	.birthday { display: block; margin: 0 0 0 20px; padding:0;}

    .label		   {white-space:nowrap; color:black; border-radius: 2px; padding:2px 2px 2px 2px;}
	.label-default {background-color:#999999;}
	.label-primary {background-color:#337ab7;}
	.label-success {background-color:#5cb85c;}
	.label-info    {background-color:#033c73;}
	.label-warning {background-color:#dd5600;}
	.label-danger  {background-color:#c71c22;}
	.label-lightpurple { background-color: #DCC6E0 }
	.label-purple { background-color: #947CB0 }
	.label-darkpurple {background-color: #663399 }
	.label-lightblue { background-color: #6bb9F0 }
	.label-blue { background-color: #19B5FE }
	.label-darkblue { background-color: #013243 }
	.label-lightgreen { background-color: #00E640 }
	.label-green { background-color: #3FC380 }
	.label-darkgreen { background-color: #1E824C }
	.label-lightyellow { background-color: #FFFD7E }
	.label-yellow { background-color: #FFFD54 }
	.label-darkyellow { background-color: #F7CA18 }
	.label-lightorange { background-color: #FABE58 }
	.label-orange { background-color: #E87E04 }
	.label-darkorange {background-color: #D35400 }
	.label-lightred { background-color: #EC644B }
	.label-red { background-color: #CF000F }
	.label-darkred { background-color: #96281B }
	.label-lightpink { background-color: #e08283; }
	.label-pink { background-color: #d2527f; }
	.label-darkpink { background-color: #db0a5b; }
	</style>';

    if (!empty($options) && $options[0] != "" && $options[0] != "0") {
        $anonymize_data = EmundusHelperAccess::isDataAnonymized($current_user->id);
        $allowed_attachments = EmundusHelperAccess::getUserAllowedAttachmentIDs($current_user->id);
        if (!$anonymize_data) {
            if ($allowed_attachments === true || in_array('10', $allowed_attachments)) {
                $htmldata .= '<div class="card">
								<table width="100%"><tr>';
                if (file_exists(EMUNDUS_PATH_REL . @$item->user_id . '/tn_' . @$item->avatar) && !empty($item->avatar) && is_image_ext($item->avatar)) {
                    $htmldata .= '<td width="20%"><img src="' . EMUNDUS_PATH_REL . @$item->user_id . '/tn_' . @$item->avatar . '" width="100" align="left" /></td>';
                } elseif (file_exists(EMUNDUS_PATH_REL . @$item->user_id . '/' . @$item->avatar) && !empty($item->avatar) && is_image_ext($item->avatar)) {
                    $htmldata .= '<td width="20%"><img src="' . EMUNDUS_PATH_REL . @$item->user_id . '/' . @$item->avatar . '" width="100" align="left" /></td>';
                }
            }

            $htmldata .= '
			<td width="80%">
	
			<div class="name">' . @$item->firstname . ' ' . strtoupper(@$item->lastname) . ', ' . @$item->label . ' (' . @$item->cb_schoolyear . ')</div>';

            if (isset($item->maiden_name)) {
                $htmldata .= '<div class="maidename">' . Text::_('MAIDEN_NAME') . ' : ' . $item->maiden_name . '</div>';
            }
        }

        $date_submitted = (!empty($item->date_submitted) && !strpos($item->date_submitted, '0000')) ? JHTML::_('date', $item->date_submitted, Text::_('DATE_FORMAT_LC2')) : Text::_('NOT_SENT');

            // Create an date object
            $date_printed = new Date();
            //Use helper date function to set timezone an format
            $date_printed = HtmlHelper::date($date_printed, Text::_('DATE_FORMAT_LC2'));

        if (!$anonymize_data && in_array("aemail", $options)) {
            $htmldata .= '<div class="birthday">' . Text::_('EMAIL') . ' : ' . @$item->email . '</div>';
        }

        if (in_array("aid", $options)) {
            $htmldata .= '<div class="nationality">' . Text::_('ID_CANDIDAT') . ' : ' . @$item->user_id . '</div>';
        }
        if (in_array("afnum", $options)) {
            $htmldata .= '<div class="nationality">' . Text::_('FNUM') . ' : ' . $fnum . '</div>';
        }

        if (in_array("aapp-sent", $options)) {
            $htmldata .= '<div class="sent">' . Text::_('APPLICATION_SENT_ON') . ' : ' . $date_submitted . '</div>';
        }
        if (in_array("adoc-print", $options)) {
            $htmldata .= '<div class="sent">'.Text::_('DOCUMENT_PRINTED_ON').' : '.$date_printed.'</div>';
        }
        if (in_array("status", $options)) {
            $status = $m_files->getStatusByFnums(explode(',', $fnum));
            $htmldata .= '<div class="sent">' . Text::_('COM_EMUNDUS_EXPORTS_PDF_STATUS') . ' : ' . $status[$fnum]['value'] . '</div>';
        }
        if (in_array("tags", $options)) {
            $tags = $m_files->getTagsByFnum(explode(',', $fnum));
            $htmldata .= '<br/><table><tr><td> ';
            foreach ($tags as $tag) {
                $htmldata .= '<span class="label ' . $tag['class'] . '" >' . $tag['label'] . '</span>&nbsp;';
            }
            $htmldata .= '</td></tr></table>';
        }
        $htmldata .= '</td></tr></table></div>';
    } elseif ($options[0] == "0") {
        $htmldata .= '';
    }

    /**  END APPLICANT   ****/

    // Listes des fichiers chargés
    $htmldata = preg_replace_callback('#(<img\s(?>(?!src=)[^>])*?src=")data:image/(gif|png|jpeg);base64,([\w=+/]++)("[^>]*>)#', "data_to_img", $htmldata);
    $htmldata = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $htmldata);

    if (!empty($htmldata)) {
        /*$pdf->startTransaction();
        $start_y = $pdf->GetY();
        $start_page = $pdf->getPage();
        $pdf->writeHTMLCell(0, '', '', $start_y, $htmldata, 'B', 1);*/

	    $dompdf->loadHtml($htmldata);
		$dompdf->render();
	}

    if (!file_exists(EMUNDUS_PATH_ABS . $item->user_id)) {
        mkdir(EMUNDUS_PATH_ABS . $item->user_id, 0777, true);
        chmod(EMUNDUS_PATH_ABS . $item->user_id, 0777);
    }


    chdir('tmp');
    if ($output) {
	    if (!isset($current_user->applicant) || $current_user->applicant != 1) {
            //$output?'FI':'F'
            $name = 'application_header_' . date('Y-m-d_H-i-s') . '.pdf';
            //$pdf->Output(EMUNDUS_PATH_ABS . $item->user_id . DS . $name, 'FI');
		    file_put_contents(EMUNDUS_PATH_ABS . $item->user_id . DS . $name, $dompdf->output());
            $attachment = $m_application->getAttachmentByLbl("_application_form");
            $keys = array('user_id', 'attachment_id', 'filename', 'description', 'can_be_deleted', 'can_be_viewed', 'campaign_id', 'fnum');
            $values = array($item->user_id, $attachment['id'], $name, $item->training . ' ' . date('Y-m-d H:i:s'), 0, 0, $campaign_id, $fnum);
            $data = array('key' => $keys, 'value' => $values);
            $m_application->uploadAttachment($data);

        } else {
            //$pdf->Output(EMUNDUS_PATH_ABS . @$item->user_id . DS . $fnum . '_header.pdf', 'FI');
		    file_put_contents(EMUNDUS_PATH_ABS . $item->user_id . DS . $fnum . '_header.pdf', $dompdf->output());


	    }
    } else {
        //$pdf->Output(EMUNDUS_PATH_ABS . @$item->user_id . DS . $fnum . '_header.pdf', 'F');
	    file_put_contents(EMUNDUS_PATH_ABS . $item->user_id . DS . $fnum . '_header.pdf', $dompdf->output());
    }
}


/** Generate a PDF file from HTML.
 * This is a general function which takes an HTML string and builds a PDF from it.
 *
 * @param   String  $html    The HTML to generate the pdf file from.
 * @param   String  $path    The path to export the file to, if none is supplied a path will be generated.
 * @param   String  $footer  HTML for the footer of the PDF.
 *
 * @return String The path to the generated PDF or false if export fails.
 * @throws Exception
 */
function generatePDFfromHTML($html, $path = null, $footer = '') {

    set_time_limit(0);
    require_once (JPATH_LIBRARIES.DS.'emundus'.DS.'tcpdf'.DS.'tcpdf.php');


    if (class_exists('MYPDF') === false || !class_exists('MYPDF')) {
        // Extend the TCPDF class to create custom Header and Footer
        class MYPDF extends TCPDF {

            var $logo = "";
            var $logo_footer = "";
            var $footer = "";

            //Page header
            public function Header() {
                // Logo
                if (is_file($this->logo)) {
	                $this->Image($this->logo, 0, 0, 200, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                }
                // Set font
                $this->SetFont('helvetica', 'B', 16);
                // Title
                $this->Cell(0, 15, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            }

            // Page footer
            public function Footer() {
                // Position at 15 mm from bottom
                $this->SetY(-15);
                // Set font
                $this->SetFont('helvetica', 'I', 8);
                // footer
                $this->writeHTMLCell($w=0, $h=0, $x='', $y=260, $this->footer.' Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().'</p>', $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);
                //logo
                if (is_file($this->logo_footer)) {
	                $this->Image($this->logo_footer, 150, 280, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                }
            }
        }
    }

    $error = 0;

    // Generate a random file name in case one isn't supplied.
    if (empty($path)) {
	    $path = EMUNDUS_PATH_ABS.'pdf'.substr(md5(microtime()), rand(0, 26), 5).'.pdf';
    }

    if (!file_exists(dirname($path))) {
        mkdir(dirname($path), 0755, true);
        chmod(dirname($path), 0755);
    }

    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(PDF_AUTHOR);
    $pdf->SetTitle(basename(JPATH_BASE.$path));
    $pdf->footer = $footer;

    // set margins
    $pdf->SetMargins(15, 40, 15);

	$pdf->SetAutoPageBreak(true, 50);
	// set default monospaced font
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
	// set default font subsetting mode
	$pdf->setFontSubsetting(true);
	// set font
	$pdf->SetFont('helvetica', '', 8);

    $pdf->AddPage();

    $pdf->writeHTMLCell($w=0, $h=30, $x='', $y=10, $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

    chdir('tmp');

    $pdf->Output(JPATH_BASE.$path, 'F');

    if ($error == 0) {
	    return $path;
    } else {
	    return false;
    }
}
