<?php
/**
 * Plugin element to render fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.field
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;

if(!class_exists('FabrikFEModelForm'))
{
	require_once JPATH_SITE . '/components/com_fabrik/models/form.php';
}
if(!class_exists('FabrikFEModelList'))
{
	require_once JPATH_SITE . '/components/com_fabrik/models/list.php';
}

/**
 * Plugin element to render fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.emundus_fileupload
 * @since       3.0
 */
class PlgFabrik_ElementEmundus_fileupload extends PlgFabrik_Element
{

	/**
	 * @return bool
	 */
	public function onAjax_upload()
	{

		jimport('joomla.filesystem.file');

		$db     = Factory::getContainer()->get('DatabaseDriver');
		$jinput = $this->app->input;

		$current_user = $this->app->getSession()->get('emundusUser');
		$user         = (int) $current_user->id;

		if ($this->app->getIdentity()->guest) {
			echo json_encode(['status' => 'false']);

			return false;
		}

		$fnum          = $jinput->post->get('fnum');
		$elid          = $jinput->post->get('element_id');
		$attachId      = $jinput->post->get('attachId');
		$repeatCounter = $jinput->post->getInt('repeatCounter', 0);
		$description   = $jinput->getString('description', '');
		$this->setId($elid);

		$formId   = $this->getFormModel()->id;
		$fullName = $this->getFullName(true, false);

		if (!empty($attachId)) {
			$eMConfig             = ComponentHelper::getParams('com_emundus');
			$can_submit_encrypted = ($jinput->post->get('encrypt') == 2) ? $eMConfig->get('can_submit_encrypted', 1) : $jinput->post->get('encrypt');

			$attachmentResult = $this->getAttachment($attachId);
			$label            = $attachmentResult->lbl;

			$files = $jinput->files->get('file');

			$cid          = $this->getCampaignId($fnum);
			$uploadResult = $this->getUploadsByAttachmentId($attachId, $fnum);
			$nbAttachment = count($uploadResult);
			$lengthFile   = count($files);
			$nbMaxFile    = (int) $attachmentResult->nbmax;

			$acceptedExt = [];

			if (!class_exists('EmundusModelFiles')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$m_files   = new EmundusModelFiles();
			$fnumInfos = $m_files->getFnumInfos($fnum);

			if ($this->checkPath($fnumInfos['applicant_id'])) {
				$session = $this->getFormSession($fnum, $formId);
				$data    = !empty($session->data) ? $session->data : [];
				if (!empty($data) && !empty($data[$fullName])) {
					$nbAttachment += count($data[$fullName]);
				}

				foreach ($files as $key => $file) {
					$fileName = $this->getFileName($user, $attachId, $label, $file['name'], $fnum);

					$tmp_name = $file['tmp_name'];
					$fileSize = $file['size'];

					$target = $this->getPath($fnumInfos['applicant_id'], $fileName);

					$extension           = explode('.', $fileName);
					$extensionAttachment = $attachmentResult->allowed_types;
					$typeExtension       = $extension[1];

					$acceptedExt[] = stristr($extensionAttachment, $typeExtension);

					if (!in_array(false, $acceptedExt)) {
						$ext = true;

						$encrypt = true;
						if ($can_submit_encrypted == 0 && $typeExtension == 'pdf') {
							if ($this->isEncrypted($tmp_name) == 1) {
								$encrypt = false;
							}
							else {
								$encrypt = true;
							}
						}

						// The maximum size is equal to the smallest of the two sizes, either the size configured in the plugin or in the server itself.
						$postSize = $jinput->post->getInt('size', 0);
						$iniSize  = $this->file_upload_max_size();
						$sizeMax  = ($postSize >= $iniSize) ? $iniSize : $postSize;

						$fileLimitObtained = false;

						if (($lengthFile + $nbAttachment) > $nbMaxFile) {
							$fileLimitObtained = true;
						}
						else {
							if ($fileSize < $sizeMax) {
								move_uploaded_file($tmp_name, $target);
								$size = true;
							}
							else {
								$size = false;
							}
						}

						$sizeMax = $this->formatBytes($sizeMax);


						$response                   = array(
							'size'           => $size,
							'ext'            => $ext,
							'nbMax'          => $fileLimitObtained,
							'filename'       => $fileName,
							'local_filename' => $file['name'],
							'target'         => EMUNDUS_PATH_REL . $fnumInfos['applicant_id'] . '/' . $fileName,
							'nbAttachment'   => $nbAttachment,
							'encrypt'        => $encrypt,
							'maxSize'        => $sizeMax,
							'attachment_id'  => $attachId,
							'file_size'      => $fileSize,
							'repeatCounter'  => $repeatCounter,
							'noMoreUploads'  => $lengthFile + $nbAttachment >= $nbMaxFile,
							'description'    => $description
						);
						$data[$fullName][$fileName] = $response;
						$result[$key]               = $response;

						if ($size === false || $fileLimitObtained === true) {
							echo json_encode($result);

							return true;
						}

						// Store in temporary table (fabrik_form_sessions)
						$query = $db->getQuery(true);

						if (empty($session->id)) {
							$columns = [
								'hash',
								'user_id',
								'form_id',
								'row_id',
								'data',
								'time_date',
								'fnum'
							];

							$values = [
								$db->quote(md5($fileName)),
								$db->quote($user),
								$db->quote($formId),
								0,
								$db->quote(json_encode($data)),
								$db->quote(date('Y-m-d H:i:s')),
								$db->quote($fnum)
							];

							$query->clear()
								->insert($db->quoteName('#__fabrik_form_sessions'))
								->columns($db->quoteName($columns))
								->values(implode(',', $values));
							$db->setQuery($query);
							$db->execute();
						}
						else {
							$query->clear()
								->update($db->quoteName('#__fabrik_form_sessions'))
								->set('data = ' . $db->quote(json_encode($data)))
								->where('id = ' . $db->quote($session->id));
							$db->setQuery($query);
							$db->execute();
						}
					}
					else {
						$size         = true;
						$ext          = false;
						$encrypt      = true;
						$result[$key] = array(
							'size'           => $size,
							'ext'            => $ext,
							'filename'       => $fileName,
							'local_filename' => $file['name'],
							'target'         => EMUNDUS_PATH_REL . $fnumInfos['applicant_id'] . '/' . $fileName,
							'nbAttachment'   => $nbAttachment,
							'encrypt'        => $encrypt,
							'attachment_id'  => $attachId,
							'file_size'      => $fileSize,
							'repeatCounter'  => $repeatCounter
						);
						echo json_encode($result);

						return true;
					}
				}


				// track the LOGS (ATTACHMENT_CREATE)
				require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
				$user = $this->app->getSession()->get('emundusUser'); # logged user #

				require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
				$mFile        = new EmundusModelFiles();
				$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

				EmundusModelLogs::log($user->id, $applicant_id, $fnum, 4, 'c', 'COM_EMUNDUS_ACCESS_ATTACHMENT_CREATE');


				echo json_encode($result);

				return true;
			}
			else {
				echo json_encode(['status' => 'false']);

				return false;
			}
		}
		else {
			$result = array('status' => false);
			echo json_encode($result);

			return false;
		}
	}

	private function file_upload_max_size()
	{
		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$post_max_size = $this->parse_size(ini_get('post_max_size'));
			if ($post_max_size > 0) {
				$max_size = $post_max_size;
			}

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->parse_size(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}

		return $max_size;
	}

	private function parse_size($size)
	{
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else {
			return round($size);
		}
	}

	private function formatBytes($bytes, $precision = 2)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow   = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}


	public function onAjax_attachment()
	{
		$result = array('status' => false);

		$jinput        = $this->app->input;
		$fnum          = $jinput->post->get('fnum');
		$element_id    = $jinput->post->get('element_id');
		$attachment_id = $jinput->post->get('attachment_id');
		$this->setId($element_id);

		$current_user = $this->app->getSession()->get('emundusUser');

		if (EmundusHelperAccess::asAccessAction(4, 'r', $current_user->id, $fnum) || (EmundusHelperAccess::isApplicant($current_user->id) && in_array($fnum, array_keys((array) $current_user->fnums)))) {

			if (!class_exists('EmundusModelFiles')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$m_files = new EmundusModelFiles();

			$fnumInfos = $m_files->getFnumInfos($fnum);

			$uploads       = $jinput->post->getString('uploads', '');
			$repeatCounter = $jinput->post->getString('repeatCounter', 0);
			$uploadResult  = $this->getUploads($uploads, $fnum);

			$session = $this->getFormSession($fnum, $this->getFormModel()->id);
			$data    = !empty($session->data) ? $session->data : [];
			if (!empty($data[$this->getFullName(true, false)])) {

				foreach ($data[$this->getFullName(true, false)] as $key => $value) {
					$value = (object) $value;

					if (isset($value->repeatCounter) && $value->repeatCounter != $repeatCounter) {
						continue;
					}

					if (!empty($value->filename)) {
						$uploadResult[] = $value;
					}
				}
			}

			$attachmentResult = $this->getAttachment($attachment_id);
			$nbMaxFile        = (int) $attachmentResult->nbmax;
			$result           = array('status' => true,'files' => [],'limitObtained' => $nbMaxFile <= sizeof($uploadResult));

			foreach ($uploadResult as $key => $upload) {
				if (is_array($upload)) {
					$upload = (object) $upload;
				}
				$fileName       = '';
				$local_fileName = '';
				if (!empty($upload->filename)) {
					$fileName       = $upload->filename;
					$local_fileName = $upload->filename;
					if (!empty($upload->local_filename)) {
						$local_fileName = $upload->local_filename;
					}

					if (empty($upload->can_be_deleted)) {
						$upload->can_be_deleted = 1;
					}
					if (empty($upload->can_be_viewed)) {
						$upload->can_be_viewed = 1;
					}

					if (!isset($upload->repeatCounter)) {
						$upload->repeatCounter = null;
					}
				}

				$target            = '/images' . DS . 'emundus' . DS . 'files' . DS . $fnumInfos['applicant_id'] . DS . $fileName;
				$result['files'][] = array('filename' => $fileName, 'local_filename' => $local_fileName, 'target' => $target, 'can_be_deleted' => $upload->can_be_deleted, 'can_be_viewed' => $upload->can_be_viewed, 'repeatCounter' => $upload->repeatCounter, 'description' => $upload->description);
				$result['status']  = true;
			}

			echo json_encode($result);

			return true;
		}

	}

	public function onAjax_delete()
	{
		$current_user = $this->app->getSession()->get('emundusUser');

		$jinput    = $this->app->input;
		$fileName  = $jinput->post->get('filename');
		$attachId  = $jinput->post->get('attachment_id');
		$elementId = $jinput->post->get('element_id');
		$fnum      = $jinput->post->get('fnum');

		$this->setId($elementId);

		$result = array('status' => false, 'upload_id' => 0);

		if ((EmundusHelperAccess::isApplicant($current_user->id) && in_array($fnum, array_keys((array) $current_user->fnums))) || EmundusHelperAccess::asAccessAction(4, 'd', $current_user->id, $fnum)) {

			if (!class_exists('EmundusModelFiles')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$m_files = new EmundusModelFiles();

			$fnumInfos = $m_files->getFnumInfos($fnum);

			$cid = $this->getCampaignId($fnum);

			$target = $this->getPath($fnumInfos['applicant_id'], $fileName);

			if (file_exists($target)) {
				unlink($target);
			}

			$result['upload_id'] = $this->deleteFile($fileName, $fnum, $cid, $attachId);
			if (empty($result['upload_id'])) {
				$session = $this->getFormSession($fnum, $this->getFormModel()->id);
				$data    = !empty($session->data) ? $session->data : [];

				if (!empty($data[$this->getFullName(true, false)])) {
					unset($data[$this->getFullName(true, false)][$fileName]);

					$result['status'] = $this->updateFormSession($session->id, $data);
				}
			}

			if (!empty($result['upload_id'])) {
				$result['status'] = true;
			}

			if ($result['status'] && !empty($result['upload_id'])) {
				// track the LOGS (ATTACHMENT_DELETE)
				require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
				$mFile        = new EmundusModelFiles();
				$applicant_id = ($mFile->getFnumInfos($fnum))['applicant_id'];

				if (!empty($applicant_id)) {
					require_once(JPATH_SITE . '/components/com_emundus/models/logs.php');
					EmundusModelLogs::log($current_user->id, $applicant_id, $fnum, 4, 'd', 'COM_EMUNDUS_ACCESS_ATTACHMENT_DELETE');
				}
			}
		}

		echo json_encode($result);

		return true;
	}

	/**
	 * @param $fnum
	 *
	 * @return Int
	 */
	public function getCampaignId($fnum)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);
		$query->select($db->quoteName('campaign_id'))
			->from($db->quoteName('#__emundus_campaign_candidature'))
			->where($db->quoteName('fnum') . " LIKE " . $db->quote($fnum));
		$db->setQuery($query);

		return $db->loadResult();
	}


	/**
	 * @param $attachId
	 *
	 * @return mixed
	 */
	public function getAttachment($attachId)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('esa.lbl', 'esa.value', 'esa.allowed_types', 'esa.nbmax')))
			->from($db->quoteName('#__emundus_setup_attachments', 'esa'))
			->where($db->quoteName('id') . ' = ' . $attachId);
		$db->setQuery($query);

		return $db->loadObject();
	}


	/**
	 * @param $attachId
	 * @param $uid
	 * @param $cid
	 *
	 * @return mixed
	 */
	public function getUploads($uids, $fnum)
	{
		$uploads = [];

		if (!empty($uids)) {
			$uids  = explode(',', $uids);
			$query = $this->_db->getQuery(true);

			$query->select(array($this->_db->quoteName('id'), $this->_db->quoteName('filename'), $this->_db->quoteName('local_filename'), $this->_db->quoteName('can_be_deleted'), $this->_db->quoteName('can_be_viewed'), $this->_db->quoteName('description')))
				->from($this->_db->quoteName('#__emundus_uploads'))
				->where($this->_db->quoteName('id') . ' IN (' . implode(',', $this->_db->quote($uids)) . ') AND ' . $this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
			$this->_db->setQuery($query);

			$uploads = $this->_db->loadObjectList();
		}

		return $uploads;
	}

	/**
	 * @param $attachId
	 * @param $uid
	 * @param $cid
	 *
	 * @return mixed
	 */
	public function getUploadsByAttachmentId($aid, $fnum)
	{
		$uploads = [];

		if (!empty($aid) && !empty($fnum)) {
			$query = $this->_db->getQuery(true);

			$query->select(array($this->_db->quoteName('id'), $this->_db->quoteName('filename'), $this->_db->quoteName('local_filename'), $this->_db->quoteName('can_be_deleted'), $this->_db->quoteName('can_be_viewed')))
				->from($this->_db->quoteName('#__emundus_uploads'))
				->where($this->_db->quoteName('attachment_id') . ' = ' . $this->_db->quote($aid) . ' AND ' . $this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
			$this->_db->setQuery($query);

			$uploads = $this->_db->loadObjectList();
		}

		return $uploads;
	}


	/**
	 * @param $uid
	 * @param $fileName
	 *
	 * @return string
	 */
	public function getPath($uid, $fileName)
	{
		return EMUNDUS_PATH_ABS . $uid . DS . $fileName;
	}

	public function checkPath($applicant_id)
	{
		$checked = true;

		if (!file_exists(EMUNDUS_PATH_ABS . $applicant_id)) {
			// An error would occur when the index.html file was missing, the 'Unable to create user file' error appeared yet the folder was created.
			if (!file_exists(EMUNDUS_PATH_ABS . 'index.html')) {
				$checked = touch(EMUNDUS_PATH_ABS . 'index.html');
			}

			if (!mkdir(EMUNDUS_PATH_ABS . $applicant_id) || !copy(EMUNDUS_PATH_ABS . 'index.html', EMUNDUS_PATH_ABS . $applicant_id . DS . 'index.html')) {
				$error = Uri::getInstance() . ' :: USER ID : ' . $applicant_id . ' -> Unable to create user file';
				Log::add($error, Log::ERROR, 'com_emundus');

				$checked = false;
			}
		}

		chmod(EMUNDUS_PATH_ABS . $applicant_id, 0755);

		return $checked;
	}


	/**
	 * @param $user
	 * @param $attachId
	 * @param $label
	 * @param $file
	 *
	 * @return mixed
	 */
	public function getFileName($user, $attachId, $label, $file, $fnum)
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'checklist.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');

		$m_profile   = new EmundusModelProfile();
		$h_checklist = new EmundusHelperChecklist();
		$m_files     = new EmundusModelFiles();

		$fnumInfos = $m_files->getFnumInfos($fnum);
		$fileName  = $h_checklist->setAttachmentName($file, $label, $fnumInfos);

		return JFile::makeSafe($fileName);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string    elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		JHtml::stylesheet('plugins/fabrik_element/emundus_fileupload/css/emundus_fileupload.css');

		$params  = $this->getParams();
		$element = $this->getElement();
		$bits    = $this->inputProperties($repeatCounter);

		if (is_array($this->getFormModel()->data)) {
			$data = $this->getFormModel()->data;
		}

		$value = $this->getValue($data, $repeatCounter);
		if (!$this->getFormModel()->failedValidation()) {
			$value = $this->numberFormat($value);
		}

		if (!$this->isEditable()) {

			$value = $this->getReadOnlyOutput($value, $value);

			return ($element->hidden == '1') ? "<!-- " . $value . " -->" : $value;

		}

		if(is_array($value)) {
			$value = $value['filename'];
		}
		$bits['value'] = htmlspecialchars($value, ENT_COMPAT, 'UTF-8', false);

		$bits['class']             .= ' ' . $params->get('text_format');
		$bits['attachmentId']      = $params->get('attachmentId');
		$bits['size']              = $params->get('size');
		$bits['description_input'] = $params->get('description_input');
		$bits['max_size_txt']      = $this->formatBytes($bits['size']);

		$eMConfig          = ComponentHelper::getParams('com_emundus');
		$bits['encrypted'] = ($params->get('encrypt') == 2) ? $eMConfig->get('can_submit_encrypted', 1) : $params->get('encrypt');

		$layout                 = $this->getLayout('form');
		$layoutData             = new stdClass;
		$layoutData->attributes = $bits;
		$layoutData->sizeClass  = $params->get('bootstrap_class', '');

		return $layout->render($layoutData);
	}


	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           Options, 'raw' = 1/0 use raw value
	 *
	 * @return  string    value
	 */
	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$value = parent::getValue($data, $repeatCounter, $opts);

		if (is_array($value)) {
			return array_pop($value);
		}

		return $value;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$params   = $this->getParams();
		$eMConfig = ComponentHelper::getParams('com_emundus');
		if (is_array($this->getFormModel()->data)) {
			$data = $this->getFormModel()->data;
		}

		$id = $this->getHTMLId($repeatCounter);

		$opts          = $this->getElementJSOptions($repeatCounter);
		$opts->encrypt = ($params->get('can_submit_encrypted') == 2) ? $eMConfig->get('can_submit_encrypted', 1) : $params->get('can_submit_encrypted');;
		$opts->size          = $params->get('size');
		$opts->attachment_id = $params->get('attachmentId');

		$fnumElt = array_filter($data, function ($key) {
			return strpos($key, '___fnum_raw') !== false;
		}, ARRAY_FILTER_USE_KEY);
		$fnum    = reset($fnumElt);
		if (empty($fnum)) {
			$fnum = !empty($data['fnum']) ? $data['fnum'] : $data['rowid'];
		}
		$opts->fnum          = $fnum;
		$opts->elid          = $this->getElement()->id;
		$opts->repeatCounter = $repeatCounter;

		Text::script('PLG_ELEMENT_FIELD_SUCCESS');
		Text::script('PLG_ELEMENT_FIELD_EXTENSION');
		Text::script('PLG_ELEMENT_FIELD_ENCRYPT');
		Text::script('PLG_ELEMENT_FIELD_ERROR');
		Text::script('PLG_ELEMENT_FIELD_ERROR_TEXT');
		Text::script('PLG_ELEMENT_FIELD_SIZE');
		Text::script('PLG_ELEMENT_FIELD_LIMIT');
		Text::script('PLG_ELEMENT_FIELD_SURE');
		Text::script('PLG_ELEMENT_FIELD_SURE_TEXT');
		Text::script('PLG_ELEMENT_FIELD_CONFIRM');
		Text::script('PLG_ELEMENT_FIELD_CANCEL');
		Text::script('PLG_ELEMENT_FIELD_DELETE');
		Text::script('PLG_ELEMENT_FIELD_DELETE_TEXT');
		Text::script('PLG_ELEMENT_FIELD_DELETE_FAILED');
		Text::script('PLG_ELEMENT_FIELD_DELETE_TEXT_FAILED');
		Text::script('PLG_ELEMENT_FIELD_ACCESS');
		Text::script('PLG_ELEMENT_FIELD_UPLOAD');
		Text::script('PLG_ELEMENT_FILEUPLOAD_UPLOADED_FILES');

		return array('FbEmundusFileUpload', $id, $opts);
	}


	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   This elements posted form data
	 * @param   array  $data  Posted form data
	 *
	 * @return  mixed
	 */
	public function storeDatabaseFormat($val, $data)
	{
		$value_to_store = [];

		$input   = Factory::getApplication()->input;
		$fnumElt = array_filter($input->getArray(), function ($key) {
			return strpos($key, '___fnum_raw') !== false;
		}, ARRAY_FILTER_USE_KEY);
		$fnum    = reset($fnumElt);

		$params = $this->getParams();

		if (!empty($fnum)) {
			if (!class_exists('EmundusModelFiles')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$m_files   = new EmundusModelFiles();
			$fnumInfos = $m_files->getFnumInfos($fnum);

			if (is_string($val)) {
				$val = explode(',', $val);
			}

			if (!empty($val)) {
				$cid = $this->getCampaignId($fnum);

				$query = $this->_db->getQuery(true);

				$session      = $this->getFormSession($fnum, $this->getFormModel()->id);
				$session_data = !empty($session->data) ? $session->data : [];
				$files_data   = $session_data[str_replace('[]', '', $this->getFullName())];

				foreach ($val as $file) {
					if (!empty($files_data[$file])) {

						if (!empty($files_data[$file]['filename'])) {
							require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
							$now  = EmundusHelperDate::getNow();
							$user = Factory::getUser();

							$values = [
								$this->_db->quote($now),
								$this->_db->quote($user->id),
								$this->_db->quote($fnum),
								$this->_db->quote($cid),
								$this->_db->quote($files_data[$file]['attachment_id']),
								$this->_db->quote($files_data[$file]['filename']),
								$this->_db->quote(1),
								$this->_db->quote(1),
								$this->_db->quote($now),
								$this->_db->quote($files_data[$file]['local_filename']),
								$this->_db->quote($files_data[$file]['file_size']),
								$this->_db->quote($files_data[$file]['description'])
							];

							$upload_id = $this->insertFile(implode(',', $values));

							if (!empty($upload_id)) {
								$value_to_store[] = $upload_id;

								unset($files_data[$file]);

								$session_data[str_replace('[]', '', $this->getFullName())] = $files_data;

								$query->clear()
									->update($this->_db->quoteName('#__fabrik_form_sessions'))
									->set($this->_db->quoteName('data') . ' = ' . $this->_db->quote(json_encode($session_data)))
									->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($session->id));
								$this->_db->setQuery($query);
								$this->_db->execute();
							}
						}
					}
					else {
						$query->clear()
							->select($this->_db->quoteName('id'))
							->from($this->_db->quoteName('#__emundus_uploads'))
							->where($this->_db->quoteName('filename') . ' LIKE ' . $this->_db->quote($file))
							->andWhere($this->_db->quoteName('campaign_id') . ' = ' . $cid)
							->andWhere($this->_db->quoteName('attachment_id') . ' = ' . $params->get('attachmentId'))
							->andWhere($this->_db->quoteName('fnum') . ' LIKE ' . $this->_db->quote($fnum));
						$this->_db->setQuery($query);
						$upload_id = $this->_db->loadResult();

						if(!empty($upload_id)) {
							$value_to_store[] = $upload_id;
						} else
						{
							$value_to_store[] = $file;
						}
					}
				}
			}
		}

		return implode(',', $value_to_store);
	}

	/**
	 * @param $values
	 *
	 * @return int
	 *
	 * @throws Exception
	 * @since version 2.0.0
	 */
	public function insertFile($values)
	{
		$upload_id = 0;

		if (!empty($values)) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$columns = array('timedate', 'user_id', 'fnum', 'campaign_id', 'attachment_id', 'filename', 'can_be_deleted', 'can_be_viewed', 'modified', 'local_filename', 'size', 'description');

			$query->insert($db->quoteName('#__emundus_uploads'))
				->columns($db->quoteName($columns))
				->values($values);
			$db->setQuery($query);

			try {
				if ($db->execute()) {
					$upload_id = $db->insertid();
				}
			}
			catch (Exception $e) {
				Factory::getApplication()->enqueueMessage('Probrème survenu au téléchargement des fichiers', 'message');
			}
		}

		return $upload_id;
	}


	/**
	 * @param $fileName
	 * @param $fnum
	 * @param $cid
	 * @param $attachId
	 *
	 * @throws Exception
	 */
	public function deleteFile($fileName, $fnum, $cid, $attachId)
	{
		$upload_id = 0;

		if (!empty($fnum) && !empty($fileName) && !empty($attachId) && !empty($cid)) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('id')
				->from($db->quoteName('#__emundus_uploads'))
				->where($db->quoteName('filename') . ' LIKE ' . $db->quote($fileName))
				->andWhere($db->quoteName('campaign_id') . ' = ' . $cid)
				->andWhere($db->quoteName('attachment_id') . ' = ' . $attachId)
				->andWhere($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum));
			$db->setQuery($query);
			$upload_id = $db->loadResult();

			if (!empty($upload_id)) {
				$query->clear()
					->delete($db->quoteName('#__emundus_uploads'))
					->where($db->quoteName('id') . ' = ' . $db->quote($upload_id));

				try {
					$db->setQuery($query);
					if (!$db->execute()) {
						$upload_id = 0;
					}
				}
				catch (Exception $e) {
					Log::add("Failed to delete file for fnum $fnum, filename $fileName, campaign $cid, attachment $attachId : " . $e->getMessage(), Log::ERROR, 'com_emundus.error');
					Factory::getApplication()->enqueueMessage('Problème survenu à la suppression des fichiers', 'message');
				}
			}
		}

		return $upload_id;
	}

	/**
	 * @param $fnum
	 * @param $formid
	 *
	 * @return mixed|null
	 *
	 * @since version 2.0.0
	 */
	public function getFormSession($fnum, $formid)
	{
		$query = $this->_db->getQuery(true);

		$query->select('id, data')
			->from('#__fabrik_form_sessions')
			->where('fnum = ' . $this->_db->quote($fnum))
			->where('form_id = ' . $this->_db->quote($formid));
		$this->_db->setQuery($query);
		$session = $this->_db->loadObject();

		if (!empty($session->id)) {
			$session->data = json_decode($session->data, true);
		}

		return $session;
	}

	/**
	 * @param $sessionId
	 * @param $data
	 *
	 * @return mixed
	 *
	 * @since version 2.0.0
	 */
	public function updateFormSession($sessionId, $data)
	{
		$query = $this->_db->getQuery(true);

		$query->update($this->_db->quoteName('#__fabrik_form_sessions'))
			->set($this->_db->quoteName('data') . ' = ' . $this->_db->quote(json_encode($data)))
			->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($sessionId));
		$this->_db->setQuery($query);

		return $this->_db->execute();
	}

	/**
	 * @param $file
	 *
	 * @return bool|false|int
	 */
	public function isEncrypted($file)
	{
		$f = fopen($file, 'rb');
		if (!$f) {
			return false;
		}

		//Read the last 320KB
		fseek($f, -323840, SEEK_END);
		$s = fread($f, 323840);

		//Extract Info object number
		return preg_match('/Encrypt ([0-9]+) /', $s);
	}
}
