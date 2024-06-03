<?php
/**
 * @version     2: emunduscampaign 2019-04-11 Hugo Moracchini
 * @package     Fabrik
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Création de dossier de candidature automatique.
 */

// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.emundusattachment
 * @since       3.0
 */
class PlgFabrik_FormEmundusAttachment extends plgFabrik_Form
{

	public function onBeforeCalculations()
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'checklist.php');

		$baseurl              = Uri::base();
		$eMConfig             = ComponentHelper::getParams('com_emundus');
		$alert_new_attachment = $eMConfig->get('alert_new_attachment');
		$m_files              = new EmundusModelFiles();
		$h_checklist          = new EmundusHelperChecklist();

		$formModel = $this->getModel();
		$aid       = $formModel->formData['attachment_id_raw'];
		if (is_array($aid))
		{
			$aid = $aid[0];
		}
		$fnum                      = $formModel->formData['fnum_raw'];
		$can_be_view               = $formModel->formData['can_be_viewed_raw'];
		$inform_applicant_by_email = $formModel->formData['inform_applicant_by_email_raw'];
		$upload_id                 = $formModel->formData['id'];

		$query = $this->_db->getQuery(true);

		$query->select('id, user_id, filename')
			->from($this->_db->quoteName('#__emundus_uploads'))
			->where($this->_db->quoteName('id') . ' = ' . $upload_id);
		$this->_db->setQuery($query);
		$upload  = $this->_db->loadObject();
		$student = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($upload->user_id);

		$query->clear()
			->select('profile')
			->from($this->_db->quoteName('#__emundus_users'))
			->where($this->_db->quoteName('user_id') . ' = ' . $upload->user_id);
		$this->_db->setQuery($query);
		$profile = $this->_db->loadResult();

		$query->clear()
			->select('ap.displayed, attachment.lbl, attachment.value')
			->from($this->_db->quoteName('#__emundus_setup_attachments', 'attachment'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_attachment_profiles', 'ap') . ' ON attachment.id = ap.attachment_id AND ap.profile_id = ' . $profile)
			->where('attachment.id = ' . $aid);
		$this->_db->setQuery($query);
		$attachment_params = $this->_db->loadObject();

		$fnumInfos = $m_files->getFnumInfos($fnum);
		$nom       = $h_checklist->setAttachmentName($upload->filename, $attachment_params->lbl, $fnumInfos);

		if (!file_exists(EMUNDUS_PATH_ABS . $upload->user_id))
		{
			mkdir(EMUNDUS_PATH_ABS . $upload->user_id, 0777, true);
		}

		if (!rename(JPATH_SITE . $upload->filename, EMUNDUS_PATH_ABS . $upload->user_id . DS . $nom))
		{
			die("ERROR_MOVING_UPLOAD_FILE");
		}

		$update_upload = [
			'id'       => $upload->id,
			'filename' => $nom
		];
		$update_upload = (object) $update_upload;
		$this->_db->updateObject('#__emundus_uploads', $update_upload, 'id');

		if ($attachment_params->lbl === '_photo')
		{
			$pathToThumbs = EMUNDUS_PATH_ABS . $student->id . DS . $nom;
			$file_src     = EMUNDUS_PATH_ABS . $student->id . DS . $nom;
			list($w_src, $h_src, $type) = getimagesize($file_src);

			switch ($type)
			{
				case 1:
					$img = imagecreatefromgif($file_src);
					break;
				case 3:
					$img = imagecreatefrompng($file_src);
					break;
				default:
					$img = imagecreatefromjpeg($file_src);
					break;
			}

			$new_width  = 200;
			$new_height = floor($h_src * ($new_width / $w_src));
			$tmp_img    = imagecreatetruecolor($new_width, $new_height);
			imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $w_src, $h_src);
			imagejpeg($tmp_img, EMUNDUS_PATH_ABS . $student->id . DS . 'tn_' . $nom);
			$student->avatar = $nom;
		}

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
		$user = $this->app->getSession()->get('emundusUser');

		$applicant_id = $fnumInfos['applicant_id'];

		$logsStd          = new stdClass();
		$logsStd->element = '[' . $attachment_params->value . '] ';
		$logsStd->details = str_replace("/tmp/", "", $_FILES['jos_emundus_uploads___filename']['name']);

		$logsParams = array('created' => [$logsStd]);

		EmundusModelLogs::log($upload->user_id, $applicant_id, $fnum, 4, 'c', 'COM_EMUNDUS_ACCESS_ATTACHMENT_CREATE', json_encode($logsParams, JSON_UNESCAPED_UNICODE));


		if ($inform_applicant_by_email == 1)
		{
			$patterns     = array('/\[ID\]/', '/\[NAME\]/', '/\[EMAIL\]/', '/\[FNUM\]/', '/\[CAMPAIGN_LABEL\]/', '/\[SITE_URL\]/', '/\n/');
			$replacements = array($student->id, $student->name, $student->email, $fnum, $fnumInfos["label"], Uri::base(), '<br />');
			$mode         = 1;
			if ($can_be_view == 1)
			{
				$file_url = '<br/>' . $baseurl . EMUNDUS_PATH_REL . $upload->user_id . '/' . $nom;
			}
			$from_id = $upload->user_id;

			try
			{
				require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'controllers' . DS . 'messages.php');
				$c_messages = new EmundusControllerMessages;

				$post = array('FILE_URL' => $file_url);
				$c_messages->sendEmail($fnum, "attachment", $post);
			}
			catch (Exception $e)
			{
				echo $e->getMessage();
				Log::add(Uri::getInstance() . ' :: USER ID : ' . $upload->user_id . ' -> ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}
	}

	public function onAfterProcess()
	{
		echo '<script src="' . Uri::base() . 'media/com_emundus/js/lib/sweetalert/sweetalert.min.js"></script>';

		die("<script>
     document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          position: 'top',
          icon: 'success',
          title: '" . Text::_('COM_EMUNDUS_ATTACHMENT_ADDED') . "',
          showConfirmButton: false,
          timer: 2000,
          customClass: {
            title: 'em-swal-title',
          }
        }).then((result) => {
		  window.parent.postMessage('addFileToFnum', '*');

  		  window.parent.document.querySelector('.em-modal-actions .swal2-close').click();
		});
      });
      </script>");
	}
}
