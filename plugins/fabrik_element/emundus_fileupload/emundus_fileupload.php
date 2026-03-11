<?php
/**
 * Plugin element to render fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.emundus_filupload
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
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Joomla\Filesystem\File;

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
	private UploadRepository $uploadRepository;
	
	private AttachmentTypeRepository $attachmentTypeRepository;

	private object $currentUser;

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		if (!class_exists('EmundusModelFiles')) {
			require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
		}
		if (!class_exists('EmundusModelLogs'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/logs.php');
		}
		$this->uploadRepository = new UploadRepository();
		$this->attachmentTypeRepository = new AttachmentTypeRepository();

		$this->currentUser = $this->app->getSession()->get('emundusUser');
	}

	public function onAjax_upload(): bool
	{
		$m_files   = new EmundusModelFiles();

		$db     = Factory::getContainer()->get('DatabaseDriver');

		$user         = (int) $this->currentUser->id;
		if ($this->app->getIdentity()->guest) {
			echo json_encode(['status' => 'false']);

			return false;
		}

		$fnum          = $this->app->input->post->get('fnum');
		$elid          = $this->app->input->post->get('element_id');
		$attachId      = $this->app->input->post->get('attachId');
		$repeatCounter = $this->app->input->post->getInt('repeatCounter', 0);
		$description   = $this->app->input->getString('description', '');
		$this->setId($elid);

		$formId   = $this->getFormModel()->id;
		$fullName = $this->getFullName(true, false);

		if (!empty($attachId)) {
			$eMConfig             = ComponentHelper::getParams('com_emundus');
			$can_submit_encrypted = ($this->app->input->post->get('encrypt') == 2) ? $eMConfig->get('can_submit_encrypted', 1) : $this->app->input->post->get('encrypt');

			$attachmentResult = $this->attachmentTypeRepository->getItemByField('id', $attachId);

			$files = $this->app->input->files->get('file');

			$uploadResult = $this->uploadRepository->getItemsByFields(['attachment_id' => $attachId, 'fnum' => $fnum]);
			$nbAttachment = count($uploadResult);
			$lengthFile   = count($files);
			$nbMaxFile    = (int) $attachmentResult->nbmax;

			$acceptedExt = [];

			$fnumInfos = $m_files->getFnumInfos($fnum);
			if ($this->checkPath($fnumInfos['applicant_id'])) {
				$session = $this->getFormSession($fnum, $formId);
				$data    = !empty($session->data) ? $session->data : [];
				if (!empty($data) && !empty($data[$fullName])) {
					foreach ($data[$fullName] as $value) {
						if (isset($value['need_to_delete'])) {
							$nbAttachment--;
						}
						else {
							$nbAttachment++;
						}
					}
				}

				foreach ($files as $key => $file) {
					$fileName = $this->getFileName($attachmentResult->lbl, $file['name'], $fnum);

					$tmp_name = $file['tmp_name'];
					$fileSize = $file['size'];

					$target = self::getPath($fnumInfos['applicant_id'], $fileName);

					$extension           = explode('.', $fileName);
					$extensionAttachment = $attachmentResult->allowed_types;
					$typeExtension       = $extension[1];

					$acceptedExt[] = stristr($extensionAttachment, $typeExtension);
					if (!in_array(false, $acceptedExt)) {
						$size = true;

						$encrypt = true;
						if ($can_submit_encrypted == 0 && $typeExtension == 'pdf') {
							if (self::isEncrypted($tmp_name) == 1) {
								$encrypt = false;
							}
						}

						// The maximum size is equal to the smallest of the two sizes, either the size configured in the plugin or in the server itself.
						$postSize = $this->app->input->post->getInt('size', 0);
						$iniSize  = $this->file_upload_max_size();
						$sizeMax  = ($postSize >= $iniSize) ? $iniSize : $postSize;

						$fileLimitObtained = false;

						if (($lengthFile + $nbAttachment) > $nbMaxFile) {
							$fileLimitObtained = true;
						}
						else {
							if ($fileSize < $sizeMax) {
								move_uploaded_file($tmp_name, $target);
							}
							else {
								$size = false;
							}
						}

						$sizeMax = $this->formatBytes($sizeMax);


						$response                   = array(
							'size'           => $size,
							'ext'            => true,
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
						$result[$key] = array(
							'size'           => true,
							'ext'            => false,
							'filename'       => $fileName,
							'local_filename' => $file['name'],
							'target'         => EMUNDUS_PATH_REL . $fnumInfos['applicant_id'] . '/' . $fileName,
							'nbAttachment'   => $nbAttachment,
							'encrypt'        => true,
							'attachment_id'  => $attachId,
							'file_size'      => $fileSize,
							'repeatCounter'  => $repeatCounter
						);
						echo json_encode($result);

						return true;
					}
				}

				$applicant_id = ($m_files->getFnumInfos($fnum))['applicant_id'];
				EmundusModelLogs::log($this->currentUser->id, $applicant_id, $fnum, 4, 'c', 'COM_EMUNDUS_ACCESS_ATTACHMENT_CREATE');

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

	public function onAjax_attachment(): bool
	{
		$result = array('status' => false);

		$fnum          = $this->app->input->post->get('fnum');
		$element_id    = $this->app->input->post->get('element_id');
		$attachment_id = $this->app->input->post->get('attachment_id');
		$this->setId($element_id);
		
		if (EmundusHelperAccess::asAccessAction(4, 'r', $this->currentUser->id, $fnum) || (EmundusHelperAccess::isApplicant($this->currentUser->id) && in_array($fnum, array_keys((array) $this->currentUser->fnums)))) {
			$m_files = new EmundusModelFiles();
			$fnumInfos = $m_files->getFnumInfos($fnum);

			$uploads       = $this->app->input->post->getString('uploads', '');
			$repeatCounter = $this->app->input->post->getString('repeatCounter', 0);
			$uploads = !empty($uploads) ? explode(',', $uploads) : [];

			$uploadResult = $this->uploadRepository->getItemsByFields(['fnum' => $fnum, 'id' => $uploads]);

			$session = $this->getFormSession($fnum, $this->getFormModel()->id);
			$data    = !empty($session->data) ? $session->data : [];
			if (!empty($data[$this->getFullName(true, false)])) {
				foreach ($data[$this->getFullName(true, false)] as $value) {
					$value = (object) $value;

					if (isset($value->repeatCounter) && $value->repeatCounter != $repeatCounter) {
						continue;
					}

					if($value->need_to_delete)
					{
						// Remove from form session
						unset($data[$this->getFullName(true, false)][$value->filename]);
					}

					if (!empty($value->filename) && !isset($value->need_to_delete)) {
						$uploadResult[] = $value;
					}
				}

				if(empty($data[$this->getFullName(true, false)]))
				{
					$data = null;
				}

				$this->updateFormSession($session->id, $data);
			}
			
			if(empty($uploadResult)) {
				// Check if attachment was previously uploaded and saved to database
				$uploadResult = $this->uploadRepository->getItemsByFields(['attachment_id' => $attachment_id, 'fnum' => $fnum]);
			}

			$attachmentResult = $this->attachmentTypeRepository->getItemByField('id', $attachment_id);
			$result           = array('status' => true,'files' => [],'limitObtained' => (int) $attachmentResult->nbmax <= sizeof($uploadResult));

			foreach ($uploadResult as $upload) {
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
					$upload->description = $upload->description ?? '';

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

				$target            = '/images/emundus/files/' . $fnumInfos['applicant_id'] . '/' . $fileName;
				$result['files'][] = array('filename' => $fileName, 'local_filename' => $local_fileName, 'target' => $target, 'can_be_deleted' => $upload->can_be_deleted, 'can_be_viewed' => $upload->can_be_viewed, 'repeatCounter' => $upload->repeatCounter, 'description' => $upload->description);
				$result['status']  = true;
			}
		}

		echo json_encode($result);
		return true;
	}

	public function onAjax_delete()
	{
		$fileName  = $this->app->input->post->get('filename');
		$attachId  = $this->app->input->post->get('attachment_id');
		$elementId = $this->app->input->post->get('element_id');
		$fnum      = $this->app->input->post->get('fnum');

		$this->setId($elementId);

		$result = array('status' => false, 'upload_id' => 0);

		if ((EmundusHelperAccess::isApplicant($this->currentUser->id) && in_array($fnum, array_keys((array) $this->currentUser->fnums))) || EmundusHelperAccess::asAccessAction(4, 'd', $this->currentUser->id, $fnum)) {
			$cid = $this->getCampaignId($fnum);
			
			$session = $this->getFormSession($fnum, $this->getFormModel()->id);
			$data    = !empty($session->data) ? $session->data : [];

			$fileInfos = $this->uploadRepository->getItemsByFields([
				'filename' => $fileName,
				'fnum' => $fnum,
				'campaign_id' => $cid,
				'attachment_id' => $attachId
			]);

			if(empty($data[$this->getFullName(true, false)]))
			{
				$data[$this->getFullName(true, false)] = [];
			}

			if(isset($data[$this->getFullName(true, false)][$fileName]) && !isset($data[$this->getFullName(true, false)][$fileName]['id']))
			{
				unset($data[$this->getFullName(true, false)][$fileName]);
			}
			elseif(!empty($fileInfos))
			{
				// Add it in the session to be deleted at submission
				$data[$this->getFullName(true, false)][$fileName] = [
					'id'             => $fileInfos[0]->id,
					'filename' => $fileInfos[0]->filename,
					'need_to_delete' => true
				];
				$result['upload_id'] = $fileInfos[0]->id;
			}

			$result['status'] = $this->updateFormSession($session->id, $data);
		}

		echo json_encode($result);
		return true;
	}

	public function getCampaignId(string $fnum): int
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);
		$query->select($db->quoteName('campaign_id'))
			->from($db->quoteName('#__emundus_campaign_candidature'))
			->where($db->quoteName('fnum') . " LIKE " . $db->quote($fnum));
		$db->setQuery($query);

		return $db->loadResult();
	}

	public function checkPath($applicant_id): bool
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

	public function getFileName(string $label, string $file, string $fnum): string
	{
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'checklist.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');

		$h_checklist = new EmundusHelperChecklist();
		$m_files     = new EmundusModelFiles();

		$fnumInfos = $m_files->getFnumInfos($fnum);
		$fileName  = $h_checklist->setAttachmentName($file, $label, $fnumInfos);

		return File::makeSafe($fileName);
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

		if(empty($value) && !empty($this->getFormModel()->data['fnum']) && !empty($params->get('attachmentId')))
		{
			// Check if attachments are already uploaded in application file but not via the element
			$uploads = $this->uploadRepository->getItemsByFields(['fnum' => $this->getFormModel()->data['fnum'], 'attachment_id' => $params->get('attachmentId')]);

			$value = [];
			foreach($uploads as $upload) {
				$value[] = $upload->id;
			}
			$value = !empty($value) ? implode(',', $value) : '';
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

		$task = $this->app->input->get('task', '');
		if($task === 'form.process' || $task == 'process')
		{
			$fnumElt = array_filter($this->app->input->getArray(), function ($key) {
				return strpos($key, '___fnum_raw') !== false;
			}, ARRAY_FILTER_USE_KEY);
			$fnum    = reset($fnumElt);

			$params = $this->getParams();
			if (!empty($fnum))
			{
				$session      = $this->getFormSession($fnum, $this->getFormModel()->id);
				$session_data = !empty($session->data) ? $session->data : [];
				$files_data   = $session_data[str_replace('[]', '', $this->getFullName())];

				$m_files   = new EmundusModelFiles();
				$fnumInfos = $m_files->getFnumInfos($fnum);

				if (is_string($val))
				{
					$val = explode(',', $val);
				}

				$val = array_filter($val, function ($file) {
					return !empty($file);
				});
				
				if (!empty($val))
				{
					$cid = $this->getCampaignId($fnum);

					$query = $this->_db->getQuery(true);

					foreach ($val as $file)
					{
						if (!empty($files_data[$file]))
						{
							if (!empty($files_data[$file]['filename']) && !isset($files_data[$file]['need_to_delete']))
							{
								require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
								$now  = EmundusHelperDate::getNow();
								$user = $this->app->getIdentity();

								$uploadEntity = new UploadEntity(
									0,
									$user->id,
									$fnum,
									$files_data[$file]['attachment_id'],
									$files_data[$file]['filename'],
									$files_data[$file]['description'],
									$files_data[$file]['local_filename'],
									$cid,
									$files_data[$file]['file_size']
								);
								$this->uploadRepository->flush($uploadEntity);

								if (!empty($uploadEntity->getId()))
								{
									$value_to_store[] = $uploadEntity->getId();

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
						else
						{
							$upload = $this->uploadRepository->getItemsByFields([
								'filename' => $file,
								'fnum' => $fnum,
								'campaign_id' => $cid,
								'attachment_id' => $params->get('attachmentId')
							]);
							$upload_id = !empty($upload) ? $upload[0]->id : null;

							if (!empty($upload_id))
							{
								$value_to_store[] = $upload_id;
							}
							else
							{
								$value_to_store[] = $file;
							}
						}
					}
				}

				// Check if some files are marked for deletion
				$m_files        = new EmundusModelFiles();
				foreach ($files_data as $fileData)
				{
					if($fileData['need_to_delete'] && !empty($fileData['id']))
					{
						$target = self::getPath($fnumInfos['applicant_id'], $fileData['filename']);

						if (file_exists($target)) {
							unlink($target);
						}

						if($this->uploadRepository->delete($fileData['id']))
						{
							$applicant_id = ($m_files->getFnumInfos($fnum))['applicant_id'];
							if (!empty($applicant_id)) {
								EmundusModelLogs::log($this->currentUser->id, $applicant_id, $fnum, 4, 'd', 'COM_EMUNDUS_ACCESS_ATTACHMENT_DELETE');
							}
						}
					}
				}
			}
		}

		return implode(',', $value_to_store);
	}

	public function getFormSession(string $fnum, int $formid): ?object
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

	public function updateFormSession(int $sessionId, $data): bool
	{
		$query = $this->_db->getQuery(true);

		$query->update($this->_db->quoteName('#__fabrik_form_sessions'));
		if(is_array($data))
		{
			$query->set($this->_db->quoteName('data') . ' = ' . $this->_db->quote(json_encode($data)));
		}
		elseif (is_null($data))
		{
			$query->set($this->_db->quoteName('data') . ' = NULL');
		}
		$query->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($sessionId));
		$this->_db->setQuery($query);

		return $this->_db->execute();
	}

	private function file_upload_max_size(): float|int
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

	private function parse_size($size): float
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

	private function formatBytes($bytes, $precision = 2): string
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow   = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	public static function getPath($uid, $fileName): string
	{
		return EMUNDUS_PATH_ABS . $uid . DS . $fileName;
	}
	
	public static function isEncrypted($file): bool|int
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
