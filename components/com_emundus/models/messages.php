<?php
/**
 * Messages model used for the new message dialog.
 *
 * @package    Joomla
 * @subpackage eMundus
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

class EmundusModelMessages extends ListModel
{
	private $user;
	private $db;

	/**
	 * Constructor
	 *
	 * @param   array  $config
	 *
	 * @since 3.8.6
	 *
	 */
	public function __construct($config = array())
	{
		$this->user = Factory::getApplication()->getSession()->get('emundusUser');
		$this->db   = Factory::getContainer()->get('DatabaseDriver');

		Log::addLogger(['text_file' => 'com_emundus.chatroom.error.php'], Log::ERROR, 'com_emundus.chatroom');

		parent::__construct($config);
	}

	/**
	 * Gets all published message templates of a certain type.
	 *
	 * @param   Int  $type  The type of email to get, type 2 is by default (Templates).
	 *
	 * @return Mixed False if the query fails and nothing can be loaded. An array of objects describing the messages. (sender, subject, body, etc..)
	 */
	function getAllMessages($type = 2)
	{
		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_setup_emails'))
			->where($this->db->quoteName('type') . ' IN (' . $this->db->Quote($type) . ')')
			->andWhere($this->db->quoteName('published') . ' = ' . $this->db->quote(1))
			->order($this->db->quoteName('subject'));

		try {

			$this->db->setQuery($query);

			return $this->db->loadObjectList();

		}
		catch (Exception $e) {
			Log::add('Error getting emails in model/messages at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

	}


	/**
	 * Gets all published message categories of a certain type.
	 *
	 * @param   Int  $type  The type of category to get, type 2 is by default (Templates).
	 *
	 * @return Mixed False if the query fails and nothing can be loaded. An array of the categories.
	 */
	function getAllCategories($type = 2)
	{
		$query = $this->db->getQuery(true);

		$query->select('DISTINCT(category)')
			->from($this->db->quoteName('#__emundus_setup_emails'))
			->where($this->db->quoteName('type') . ' IN (' . $this->db->Quote($type) . ')')
			->andWhere($this->db->quoteName('published') . ' = ' . $this->db->quote(1))
			->order($this->db->quoteName('category'));

		try {

			$this->db->setQuery($query);

			return $this->db->loadColumn();

		}
		catch (Exception $e) {
			Log::add('Error getting email categories in model/messages at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

	}


	/**
	 * Gets all published attachments unless a filter is active.
	 *
	 * @return Boolean|array False if the query fails and nothing can be loaded. or An array of objects describing attachments.
	 */
	function getAttachments()
	{
		$session = Factory::getApplication()->getSession();

		$filt_params = $session->get('filt_params');

		$query = $this->db->getQuery(true);

		// Get all info about the attachments in the table.
		$query->select('a.*')
			->from($this->db->quoteName('#__emundus_setup_attachments', 'a'));

		$where = '1 = 1 ';

		// if a filter is added then we need to filter out the attachemnts that dont match.
		if (isset($filt_params['campaign'][0]) && $filt_params['campaign'][0] != '%') {

			// Joins are added in the ifs, even though some are redundant it's better than doing tons of joins when not needed.
			$query->leftJoin($this->db->quoteName('#__emundus_setup_attachment_profiles', 'ap') . ' ON ' . $this->db->QuoteName('ap.attachment_id') . ' = ' . $this->db->QuoteName('a.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_profiles', 'p') . ' ON ' . $this->db->QuoteName('ap.profile_id') . ' = ' . $this->db->QuoteName('p.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'c') . ' ON ' . $this->db->QuoteName('c.profile_id') . ' = ' . $this->db->QuoteName('p.id'));

			$where .= ' AND ' . $this->db->quoteName('c.id') . ' LIKE ' . $filt_params['campaign'][0];

		}
		else if (isset($filt_params['programme'][0]) && $filt_params['programme'][0] != '%') {

			$query->leftJoin($this->db->quoteName('#__emundus_setup_attachment_profiles', 'ap') . ' ON ' . $this->db->QuoteName('ap.attachment_id') . ' = ' . $this->db->QuoteName('a.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_profiles', 'p') . ' ON ' . $this->db->QuoteName('ap.profile_id') . ' = ' . $this->db->QuoteName('p.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'c') . ' ON ' . $this->db->QuoteName('c.profile_id') . ' = ' . $this->db->QuoteName('p.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'pr') . ' ON ' . $this->db->QuoteName('c.training') . ' = ' . $this->db->QuoteName('pr.code'));

			$where .= ' AND ' . $this->db->quoteName('pr.code') . ' LIKE ' . $this->db->Quote($filt_params['programme'][0]);

		}

		$query->where($where . ' AND ' . $this->db->quoteName('a.published') . '=1');

		try {

			$this->db->setQuery($query);

			return $this->db->loadObjectList();

		}
		catch (Exception $e) {
			Log::add('Error getting attachments in model/messages at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

	}


	/**
	 * Gets all published letters unless a filter is active.
	 *
	 * @return Boolean False if the query fails and nothing can be loaded.
	 * @return Array An array of objects describing letters.
	 */
	function getLetters()
	{
		$session = Factory::getApplication()->getSession();

		$filt_params = $session->get('filt_params');

		$query = $this->db->getQuery(true);

		// Get all info about the letters in the table.
		$query->select('l.*')
			->from($this->db->quoteName('#__emundus_setup_letters', 'l'));

		// if a filter is added then we need to filter out the letters that dont match.
		if (isset($filt_params['campaign'][0]) && $filt_params['campaign'][0] != '%') {

			$query->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_training', 'lrt') . ' ON ' . $this->db->quoteName('lrt.parent_id') . ' = ' . $this->db->quoteName('l.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'p') . ' ON ' . $this->db->QuoteName('lrt.training') . ' = ' . $this->db->QuoteName('p.code'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'c') . ' ON ' . $this->db->QuoteName('c.training') . ' = ' . $this->db->QuoteName('p.code'))
				->where($this->db->quoteName('c.id') . ' LIKE ' . $filt_params['campaign'][0]);

		}
		else if (isset($filt_params['programme'][0]) && $filt_params['programme'][0] != '%') {

			$query->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_training', 'lrt') . ' ON ' . $this->db->quoteName('lrt.parent_id') . ' = ' . $this->db->quoteName('l.id'))
				->where($this->db->quoteName('lrt.training') . ' LIKE ' . $this->db->Quote($filt_params['programme'][0]));

		}

		try {

			$this->db->setQuery($query);

			return $this->db->loadObjectList();

		}
		catch (Exception $e) {
			Log::add('Error getting letters in model/messages at query : ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

	}


	/**
	 * Gets a message template.
	 *
	 * @param   Mixed The ID or label of the email.
	 *
	 * @return Object The email we seek, false if none is found.
	 */
	function getEmail($id)
	{
		$query = $this->db->getQuery(true);

		$query->select('e.*, et.*, GROUP_CONCAT(etr.tags) as tags, GROUP_CONCAT(ca.candidate_attachment) AS candidate_attachments, GROUP_CONCAT(la.letter_attachment) AS letter_attachments, GROUP_CONCAT(r.receivers) AS receivers')
			->from($this->db->quoteName('#__emundus_setup_emails', 'e'))
			->leftJoin($this->db->quoteName('#__emundus_email_templates', 'et') . ' ON ' . $this->db->quoteName('e.email_tmpl') . ' = ' . $this->db->quoteName('et.id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_emails_repeat_tags', 'etr') . ' ON ' . $this->db->quoteName('e.id') . ' = ' . $this->db->quoteName('etr.parent_id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_emails_repeat_candidate_attachment', 'ca') . ' ON ' . $this->db->quoteName('e.id') . ' = ' . $this->db->quoteName('ca.parent_id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_emails_repeat_letter_attachment', 'la') . ' ON ' . $this->db->quoteName('e.id') . ' = ' . $this->db->quoteName('la.parent_id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_emails_repeat_receivers', 'r') . ' ON ' . $this->db->quoteName('e.id') . ' = ' . $this->db->quoteName('r.parent_id'));

		// Allow the function to dynamically decide if it is getting by ID or label depending on the value submitted.
		if (is_numeric($id)) {
			$query->where($this->db->quoteName('e.id') . ' = ' . $id);
		}
		else {
			$query->where($this->db->quoteName('e.lbl') . ' LIKE ' . $this->db->quote($id));
		}

		$query->group('e.id');

		try {
			$this->db->setQuery($query);

			return $this->db->loadObject();
		}
		catch (Exception $e) {
			Log::add('Error getting template in model/messages at query :' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return new stdClass;
		}

	}

	/**
	 * Gets the email templates by using the category.
	 *
	 * @param   String  $category  The name of the category.
	 *
	 * @return Object|false The list of templates corresponding.
	 * @since 3.8.6
	 */
	function getEmailsByCategory($category)
	{
		$query = $this->db->getQuery(true);

		$query->select('id, subject')
			->from($this->db->quoteName('#__emundus_setup_emails'))
			->where($this->db->quoteName('type') . ' = 2')
			->andWhere($this->db->quoteName('published') . ' = 1');

		if ($category != 'all')
			$query->andWhere($this->db->quoteName('category') . ' = ' . $this->db->quote($category));

		try {

			$this->db->setQuery($query);

			return $this->db->loadObjectList();

		}
		catch (Exception $e) {
			Log::add('Error getting emails by category in model/messages at query ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}


	}


	/**
	 * Gets the a file from the setup_attachment table linked to an fnum.
	 *
	 * @param   String  $fnum           the fnum used for getting the attachment.
	 * @param   Int     $attachment_id  the ID of the attachment used in setup_attachment
	 *
	 * @return bool|mixed
	 * @since 3.8.6
	 *
	 */
	function get_upload($fnum, $attachment_id)
	{
		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName('filename'))
			->from($this->db->quoteName('#__emundus_uploads'))
			->where($this->db->quoteName('attachment_id') . ' = ' . $attachment_id . ' AND ' . $this->db->quoteName('fnum') . ' = ' . $this->db->Quote($fnum));

		try {

			$this->db->setQuery($query);

			return $this->db->loadResult();

		}
		catch (Exception $e) {
			Log::add('Error getting upload filename in model/messages at query ' . $query, Log::ERROR, 'com_emudus');

			return false;
		}
	}

	/**
	 * Gets the a file type label from the setup_attachment table .
	 *
	 * @param   Int  $attachment_id  the ID of the attachment used in setup_attachment
	 *
	 * @return bool|mixed
	 * @since 3.8.13
	 *
	 */
	function get_filename($attachment_id)
	{
		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName('value'))
			->from($this->db->quoteName('#__emundus_setup_attachments'))
			->where($this->db->quoteName('id') . ' = ' . $attachment_id);

		try {

			$this->db->setQuery($query);

			return $this->db->loadResult();
		}
		catch (Exception $e) {
			Log::add('Error getting upload filename in model/messages at query ' . $query, Log::ERROR, 'com_emudus');

			return false;
		}
	}

	/**
	 * Gets the a file from the setup_letters table linked to an fnum.
	 *
	 * @param   Int  $letter_id  the ID of the letter used in setup_letters
	 *
	 * @return Object|false The letter object as found in the DB, also contains the status and training.
	 * @since 3.8.6
	 */
	function get_letter($letter_id)
	{
		$query = $this->db->getQuery(true);

		$query->select("l.*, GROUP_CONCAT( DISTINCT(lrs.status) SEPARATOR ',' ) as status, CONCAT(GROUP_CONCAT( DISTINCT(lrt.training) SEPARATOR '\",\"' )) as training")
			->from($this->db->quoteName('#__emundus_setup_letters', 'l'))
			->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_status', 'lrs') . ' ON ' . $this->db->quoteName('lrs.parent_id') . ' = ' . $this->db->quoteName('l.id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_letters_repeat_training', 'lrt') . ' ON ' . $this->db->quoteName('lrt.parent_id') . ' = ' . $this->db->quoteName('l.id'))
			->where($this->db->quoteName('l.id') . ' = ' . $letter_id);

		try {

			$this->db->setQuery($query);

			return $this->db->loadObject();
		}
		catch (Exception $e) {
			Log::add('Error getting upload filename in model/messages at query ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emudus');

			return false;
		}

	}

	/**
	 * Gets the names of candidate files.
	 *
	 * @param   String The IDs of the candidate files to get the names of
	 *
	 * @return Array|false A list of objects containing the names and ids of the candidate files.
	 * @since 3.8.6
	 */
	function getCandidateFileNames($ids)
	{
		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName(['id', 'value']))
			->from($this->db->quoteName('#__emundus_setup_attachments'))
			->where($this->db->quoteName('id') . ' IN (' . $ids . ')');

		try {

			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error getting candidate file attachment name in model/messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

	}

	/**
	 * Gets the names of candidate files.
	 *
	 * @param   String The IDs of the candidate files to get the names of
	 *
	 * @return Array|false A list of objects containing the names and ids of the candidate files.
	 * @since 3.8.6
	 */
	function getLetterFileNames($ids)
	{
		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName(['id', 'title']))
			->from($this->db->quoteName('#__emundus_setup_letters'))
			->where($this->db->quoteName('id') . ' IN (' . $ids . ')');

		try {

			$this->db->setQuery($query);

			return $this->db->loadObjectList();

		}
		catch (Exception $e) {
			Log::add('Error getting letter attachment name in model/messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

	}


	/** Generates a DOC file for setup_letters
	 *
	 * @param   Object  $letter  The template for the doc to create.
	 * @param   String  $fnum    The fnum used to generate the tags.
	 *
	 * @return String The path to the saved file.
	 * @throws \PhpOffice\PhpWord\Exception\CopyFileException
	 * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
	 * @throws \PhpOffice\PhpWord\Exception\Exception
	 */
	function generateLetterDoc($letter, $fnum)
	{
		//require_once (JPATH_LIBRARIES.DS.'vendor'.DS.'autoload.php');
		require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'vendor' . DS . 'autoload.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'emails.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'export.php');

		$m_export = new EmundusModelExport;

		$m_emails = new EmundusModelEmails;
		$m_files  = new EmundusModelFiles;

		$fnumsInfos  = $m_files->getFnumTagsInfos($fnum);
		$attachInfos = $m_files->getAttachmentInfos($letter->attachment_id);

		$eMConfig             = ComponentHelper::getParams('com_emundus');
		$gotenberg_activation = $eMConfig->get('gotenberg_activation', 0);

		$user = Factory::getApplication()->getIdentity();

		$const = [
			'user_id'      => $user->id,
			'user_email'   => $user->email,
			'user_name'    => $user->name,
			'current_date' => date('d/m/Y', time())
		];

		try {

			$phpWord    = new \PhpOffice\PhpWord\PhpWord();
			$preprocess = new \PhpOffice\PhpWord\TemplateProcessor(JPATH_SITE.$letter->file);
			$tags       = $preprocess->getVariables();

			$idFabrik  = array();
			$setupTags = array();
			foreach ($tags as $i => $val) {
				$tag = strip_tags($val);
				if (is_numeric($tag))
					$idFabrik[] = $tag;
				else
					$setupTags[] = $tag;
			}

			// Process the Fabrik tags in the document ${tag}
			$fabrikElts   = $m_files->getValueFabrikByIds($idFabrik);
			$fabrikValues = array();
			foreach ($fabrikElts as $elt) {

				$params         = json_decode($elt['params']);
				$groupParams    = json_decode($elt['group_params']);
				$isDate         = (in_array($elt['plugin'], ['date','jdate']));
				$isDatabaseJoin = ($elt['plugin'] === 'databasejoin');

				if ($groupParams->repeat_group_button == 1 || $isDatabaseJoin) {
					$fabrikValues[$elt['id']] = $m_files->getFabrikValueRepeat($elt, $fnum, $params, $groupParams->repeat_group_button == 1);
				}
				else {
					if ($isDate)
						if($elt['plugin'] == 'jdate') {
							$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnum, $elt['db_table_name'], $elt['name'], $params->jdate_form_format);
						} else {
							$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnum, $elt['db_table_name'], $elt['name'], $params->date_form_format);
						}
					else
						$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnum, $elt['db_table_name'], $elt['name']);
				}

				if ($elt['plugin'] == "checkbox" || $elt['plugin'] == "dropdown") {
					foreach ($fabrikValues[$elt['id']] as $fnum => $val) {

						if ($elt['plugin'] == "checkbox") {
							$val = json_decode($val['val']);
						}
						else {
							$val = explode(',', $val['val']);
						}

						if (count($val) > 0) {
							foreach ($val as $k => $v) {
								$index   = array_search(trim($v), $params->sub_options->sub_values);
								$val[$k] = $params->sub_options->sub_labels[$index];
							}
							$fabrikValues[$elt['id']][$fnum]['val'] = implode(", ", $val);
						}
						else {
							$fabrikValues[$elt['id']][$fnum]['val'] = "";
						}
					}

				}
				elseif ($elt['plugin'] == "birthday") {

					foreach ($fabrikValues[$elt['id']] as $fnum => $val) {
						$val = explode(',', $val['val']);
						foreach ($val as $k => $v) {
							$val[$k] = date($params->details_date_format, strtotime($v));
						}
						$fabrikValues[$elt['id']][$fnum]['val'] = implode(",", $val);
					}

				}
				else {
					if (@$groupParams->repeat_group_button == 1 || $isDatabaseJoin) {
						$fabrikValues[$elt['id']] = $m_files->getFabrikValueRepeat($elt, $fnum, $params, $groupParams->repeat_group_button == 1);
					}
					else {
						$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnum, $elt['db_table_name'], $elt['name']);
					}
				}

			}

			$preprocess = new \PhpOffice\PhpWord\TemplateProcessor(JPATH_SITE . $letter->file);
			if (isset($fnumsInfos)) {

				foreach ($setupTags as $tag) {
					$val      = "";
					$lowerTag = strtolower($tag);

					if (array_key_exists($lowerTag, $const)) {
						$preprocess->setValue($tag, $const[$lowerTag]);
					}
					elseif (!empty(@$fnumsInfos[$lowerTag])) {
						$preprocess->setValue($tag, @$fnumsInfos[$lowerTag]);
					}
					else {
						$tags = $m_emails->setTagsWord(@$fnumsInfos['applicant_id'], null, $fnum, '');
						$i    = 0;
						foreach ($tags['patterns'] as $key => $value) {
							if ($value == $tag) {
								$val = $tags['replacements'][$i];
								break;
							}
							$i++;
						}

						$preprocess->setValue($tag, htmlspecialchars($val));
					}
				}

				foreach ($idFabrik as $id) {
					if (isset($fabrikValues[$id][$fnum])) {
						$value = str_replace('\n', ', ', $fabrikValues[$id][$fnum]['val']);
						$preprocess->setValue($id, $value);
					}
					else {
						$preprocess->setValue($id, '');
					}
				}

				$rand = rand(0, 1000000);
				if (!file_exists(EMUNDUS_PATH_ABS . $fnumsInfos['applicant_id'])) {
					mkdir(EMUNDUS_PATH_ABS . $fnumsInfos['applicant_id'], 0775);
				}

				$filename = str_replace(' ', '', $fnumsInfos['applicant_name']) . $attachInfos['lbl'] . "-" . md5($rand . time()) . ".docx";

				$preprocess->saveAs(EMUNDUS_PATH_ABS . $fnumsInfos['applicant_id'] . DS . $filename);

				if ($gotenberg_activation == 1 && $letter->pdf == 1) {
					//convert to PDF
					$src      = EMUNDUS_PATH_ABS . $fnumsInfos['applicant_id'] . DS . $filename;
					$dest     = str_replace('.docx', '', $src);
					$filename = str_replace('.docx', '.pdf', $filename);
					$res      = $m_export->toPdf($src, $dest, null, $fnum);
				}

				$m_files->addAttachment($fnum, $filename, $fnumsInfos['applicant_id'], $fnumsInfos['campaign_id'], $letter->attachment_id, $attachInfos['description']);

				return EMUNDUS_PATH_ABS . $fnumsInfos['applicant_id'] . DS . $filename;

			}
			unset($preprocess);

		}
		catch (Exception $e) {
			Log::add('Error generating DOC file in model/messages', Log::ERROR, 'com_emundus');

			return false;
		}

	}



	///// All functions from here are for the messages view

	/** get all contacts the current user has received or sent a message as well as their latest message.
	 *
	 * @param   null  $user
	 *
	 * @return bool|mixed
	 */
	public function getContacts($user = null)
	{
		if (empty($user)) {
			$user = $this->user->id;
		}

		$query = "SELECT jos_messages.*, sender.name as name_from, sp_sender.label as profile_from, recipient.name as name_to, sp_recipient.label as profile_to, recipientUpload.attachment_id as photo_to, senderUpload.attachment_id as photo_from
                  FROM jos_messages
                  INNER JOIN jos_emundus_users AS sender ON sender.user_id = jos_messages.user_id_from
                  INNER JOIN jos_emundus_users AS recipient ON recipient.user_id = jos_messages.user_id_to
                  LEFT JOIN jos_emundus_setup_profiles sp_recipient ON sp_recipient.id =  recipient.profile
                  LEFT JOIN jos_emundus_setup_profiles sp_sender ON sp_sender.id =  sender.profile
                  LEFT JOIN jos_emundus_uploads recipientUpload ON recipientUpload.user_id = recipient.user_id AND recipientUpload.attachment_id = 10
                  LEFT JOIN jos_emundus_uploads senderUpload ON senderUpload.user_id = sender.user_id AND senderUpload.attachment_id = 10
                  INNER JOIN (
                      SELECT MAX(message_id) AS most_recent_message_id
                      FROM jos_messages
                      WHERE (folder_id = 2 OR (folder_id = 3 AND user_id_to = " . $user . "))
                      GROUP BY CASE WHEN user_id_from > user_id_to
                          THEN user_id_to
                          ELSE user_id_from
                      END,
                      CASE WHEN user_id_from < user_id_to
                          THEN user_id_to
                          ELSE user_id_from
                      END) T ON T.most_recent_message_id = jos_messages.message_id
				  WHERE user_id_from = " . $user . "
                  OR user_id_to = " . $user . "
                  ORDER BY date_time DESC";

		try {
			$this->db->setQuery($query);
			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error getting candidate file attachment name in model/messages at query: ' . $query, Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/** gets all messages received after the message $lastID
	 *
	 * @param         $lastId
	 * @param   null  $user
	 * @param   null  $other_user
	 *
	 * @return bool|mixed
	 */
	public function updateMessages($lastId, $user = null, $other_user = null)
	{

		if (empty($user)) {
			$user = $this->user->id;
		}

		$where = $this->db->quoteName('message_id') . ' > ' . $lastId . ' AND ' . $this->db->quoteName('user_id_to') . ' = ' . $user . ' AND ' . $this->db->quoteName('state') . ' = 1 AND ' . $this->db->quoteName('folder_id') . ' = 2';
		if (!empty($other_user)) {
			$where .= ' AND ' . $this->db->quoteName('user_id_from') . ' = ' . $other_user;
		}

		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->db->quoteName('#__messages'))
			->where($where)
			->order('message_id DESC');

		try {
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error loading messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

	}


	/** Get number of unread messages between two users (messages with folder_id 2)
	 *
	 * @param         $sender
	 * @param   null  $receiver
	 *
	 * @return bool|mixed
	 */
	public function getUnread($sender, $receiver = null)
	{

		if (empty($receiver)) {
			$receiver = $this->user->id;
		}

		$query = $this->db->getQuery(true);

		$query->select('COUNT(state)')
			->from($this->db->quoteName('#__messages'))
			->where($this->db->quoteName('state') . ' = 1 AND ' . $this->db->quoteName('folder_id') . ' = 2 AND ' . $this->db->quoteName('user_id_to') . ' = ' . $receiver . ' AND ' . $this->db->quoteName('user_id_from') . ' = ' . $sender);
		try {
			$this->db->setQuery($query);

			return $this->db->loadResult();
		}
		catch (Exception $e) {
			Log::add('Error loading unread messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	/** load messages between two users ( messages with folder_id 2 )
	 *
	 * @param         $user1
	 * @param   null  $user2
	 *
	 * @return bool|mixed
	 */
	public function loadMessages($user1, $user2 = null)
	{

		if (empty($user2)) {
			$user2 = $this->user->id;
		}

		// update message state to read
		$query = $this->db->getQuery(true);
		$query->update($this->db->quoteName('#__messages'))
			->set([$this->db->quoteName('state') . ' = 0'])
			->where('(' . $this->db->quoteName('user_id_to') . ' = ' . $user2 . ' AND ' . $this->db->quoteName('user_id_from') . ' = ' . $user1 . ') OR (' . $this->db->quoteName('user_id_from') . ' = ' . $user2 . ' AND ' . $this->db->quoteName('user_id_to') . ' = ' . $user1 . ')');

		try {
			$this->db->setQuery($query);
			$this->db->execute();
		}
		catch (Exception $e) {
			Log::add('Error loading messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->db->quoteName('#__messages'))
			->where('(' . $this->db->quoteName('user_id_from') . ' = ' . $user2 . ' AND ' . $this->db->quoteName('user_id_to') . ' = ' . $user1 . ' AND ' . $this->db->quoteName('folder_id') . ' = 2) OR (' . $this->db->quoteName('user_id_from') . ' = ' . $user1 . ' AND ' . $this->db->quoteName('user_id_to') . ' = ' . $user2 . ' AND ' . $this->db->quoteName('folder_id') . ' IN (2,3))')
			->order($this->db->quoteName('date_time') . ' ASC')
			->setLimit('100');

		try {
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error loading messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	/** sends message folder_id=2 from user_from to user_to and sets stats to 1
	 *
	 * @param         $receiver
	 * @param         $message
	 * @param   null  $user
	 * @param   bool  $system_message
	 *
	 * @return bool
	 */
	public function sendMessage($receiver, $message, $user = null, $system_message = false)
	{

		if (empty($user)) {
			$user = $this->user->id;
		}

		$query = $this->db->getQuery(true);

		if ($system_message) {
			$folder = 3;
		}
		else {
			$folder = 2;
		}

		$columns = array('user_id_from', 'user_id_to', 'folder_id', 'date_time', 'state', 'priority', 'message');

		$values = array($user, $receiver, $folder, $this->db->quote(date("Y-m-d H:i:s")), 1, 0, $this->db->quote($message));

		$query->insert($this->db->quoteName('#__messages'))
			->columns($this->db->quoteName($columns))
			->values(implode(',', $values));

		try {

			$this->db->setQuery($query);
			$this->db->execute();

			return true;

		}
		catch (Exception $e) {
			Log::add('Error sending message at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	public function deleteSystemMessages($user1, $user2)
	{
		$query = $this->db->getQuery(true);

		$query->delete($this->db->quoteName('#__messages'))
			->where('((' . $this->db->quoteName('user_id_from') . ' = ' . $user1 . ' AND ' . $this->db->quoteName('user_id_to') . ' = ' . $user2 . ') OR (' . $this->db->quoteName('user_id_from') . ' = ' . $user2 . ' AND ' . $this->db->quoteName('user_id_to') . ' = ' . $user1 . ')) AND ' . $this->db->quoteName('folder_id') . ' = 3 ');

		try {

			$this->db->setQuery($query);
			$this->db->execute();

			return true;

		}
		catch (Exception $e) {
			Log::add('Error deleting messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}


	}



	/*
	Chatroom system

	Messages are put in folder ID 4.
	The PAGE column of the jos_messages table is used to indicate which chatroom the messages are in.
	Chatrooms are joined by adding user in jos_emundus_chatroom_users
	Chatrooms may be linked to an fnum or not, by addding the fnum in jos_emundus_chatroom.
	*/
	/**
	 * @param   null  $fnum
	 * @param   null  $id
	 *
	 * @return bool|mixed
	 *
	 * @since version
	 */
	public function createChatroom($fnum = null, $id = null)
	{
		$query = $this->db->getQuery(true);

		$columns = [$this->db->quoteName('fnum')];
		$values  = [$this->db->quote($fnum)];

		if (!empty($id)) {
			$columns[] = $this->db->quoteName('id');
			$values[]  = $id;
		}

		$query->insert($this->db->quoteName('jos_emundus_chatroom'))
			->columns($columns)
			->values($values);
		$this->db->setQuery($query);

		try {

			$this->db->execute();

			return $this->db->insertid();

		}
		catch (Exception $e) {
			Log::add('Error creating chatroom : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');

			return false;
		}

	}


	/**
	 * @param   int    $chatroom  Chatroom id, if the room doesn't exist, it will be created.
	 * @param   mixed  ...$users  Function is called as such : joinChatroom(4, $user1, $user2, $user3);
	 *
	 * @return bool
	 *
	 * @since version
	 */
	public function joinChatroom($chatroom, ...$users)
	{

		if (!$this->chatRoomExists($chatroom)) {
			$chatroom = $this->createChatroom(null, $chatroom);
		}

		if (!$chatroom) {
			return false;
		}

		$query = $this->db->getQuery(true);

		$query->insert($this->db->quoteName('jos_emundus_chatroom_users'))
			->columns([$this->db->quoteName('chatroom_id'), $this->db->quoteName('user_id')]);
		foreach ($users as $user) {
			$query->values($chatroom . ', ' . $user);
		}

		$this->db->setQuery($query);

		try {
			$this->db->execute();

			return true;
		}
		catch (Exception $e) {
			Log::add('Error joining chatroom : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');

			return false;
		}
	}


	/**
	 * @param   int     $chatroom  PAGE column in jos_messages is used to indicate that it's
	 * @param   String  $message
	 *
	 * @return bool
	 *
	 * @since version
	 */
	public function sendChatroomMessage($chatroom, $message)
	{

		if (!$this->chatRoomExists($chatroom)) {
			Log::add('Sending message to non-existant chatroom.', Log::ERROR, 'com_emundus.chatroom');

			return false;
		}

		$query = $this->db->getQuery(true);

		$query->insert($this->db->quoteName('#__messages'))
			->columns($this->db->quoteName(['user_id_from', 'folder_id', 'date_time', 'state', 'priority', 'message', 'page']))
			->values($this->user->id . ', 4, ' . $this->db->quote(date("Y-m-d H:i:s")) . ', 1, 0, ' . $this->db->quote($message) . ', ' . $chatroom);

		$this->db->setQuery($query);

		try {
			$this->db->execute();

			return true;
		}
		catch (Exception $e) {
			Log::add('Error sending chatroom message : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');

			return false;
		}
	}


	/**
	 * @param   int  $chatroom
	 *
	 * @return array|bool|mixed
	 *
	 * @since version
	 */
	public function getChatroomMessages($chatroom)
	{

		if (!$this->chatRoomExists($chatroom)) {
			Log::add('Getting messages from non-existant chatroom.', Log::ERROR, 'com_emundus.chatroom');

			return false;
		}

		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__messages'))
			->where($this->db->quoteName('folder_id') . ' = 4 AND ' . $this->db->quoteName('page') . ' = ' . $chatroom);
		$this->db->setQuery($query);

		try {
			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error getting chatroom messages : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');

			return false;
		}
	}

	/** gets all messages received after the message $lastID
	 *
	 * @param         $lastId
	 * @param   int   $chatroom
	 *
	 * @return bool|mixed
	 */
	public function updateChatroomMessages($lastId, $chatroom)
	{

		$query = $this->db->getQuery(true);

		$query->select(['m.*', 'u.name as user_from'])
			->from($this->db->quoteName('#__messages', 'm'))
			->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('m.user_id_from'))
			->where($this->db->quoteName('folder_id') . ' = 4 AND ' . $this->db->quoteName('page') . ' = ' . $chatroom . ' AND ' . $this->db->quoteName('user_id_from') . ' <> ' . JFactory::getUser()->id . ' AND ' . $this->db->quoteName('message_id') . ' > ' . $lastId)
			->order('message_id DESC');

		try {
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error loading messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString()), Log::ERROR, 'com_emundus');

			return false;
		}

	}

	/**
	 * @param $id
	 *
	 * @return bool|mixed|null
	 *
	 * @since version
	 */
	public function getChatroom($id)
	{

		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('jos_emundus_chatroom'))
			->where($this->db->quoteName('id') . ' = ' . $id);
		$this->db->setQuery($query);

		try {
			return $this->db->loadObject();
		}
		catch (Exception $e) {
			Log::add('Error getting chatroom : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');

			return false;
		}
	}

	/**
	 * @param   int  $chatroom_id
	 *
	 * @return bool|mixed|null
	 *
	 * @since version
	 */
	public function getChatroomUsersId($chatroom_id)
	{

		$query = $this->db->getQuery(true);

		$query->select('cu.user_id')
			->from($this->db->quoteName('jos_emundus_chatroom', 'c'))
			->leftJoin($this->db->quoteName('jos_emundus_chatroom_users', 'cu') . ' ON ' . $this->db->quoteName('cu.chatroom_id') . ' = ' . $this->db->quoteName('c.id'))
			->where($this->db->quoteName('c.id') . ' = ' . $chatroom_id);
		$this->db->setQuery($query);

		try {
			return $this->db->loadColumn();
		}
		catch (Exception $e) {
			Log::add('Error getting chatroom users : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');

			return false;
		}
	}


	/**
	 * @param   mixed  ...$users
	 *
	 * @return bool|int
	 *
	 * @since version
	 */
	public function getChatroomByUsers(...$users)
	{

		$query = $this->db->getQuery(true);

		// Get all chatrooms containing at least one of our three users and that contain all memebers.
		// We then will check for a chatroom having ONLY these three users using PHP.
		$query->select('c.id, GROUP_CONCAT(cu.user_id) AS users, count(cu.user_id) as nbusers')
			->from($this->db->quoteName('jos_emundus_chatroom', 'c'))
			->leftJoin($this->db->quoteName('jos_emundus_chatroom_users', 'cu') . ' ON ' . $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('cu.chatroom_id'))
			->where($this->db->quoteName('cu.user_id') . ' IN (' . implode(',', $users) . ')')
			->group($this->db->quoteName('c.id'))
			->having($this->db->quoteName('nbusers') . ' = ' . count($users));
		$this->db->setQuery($query);

		try {
			$chatrooms = $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Error getting chatroom by users : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');

			return false;
		}

		if (empty($chatrooms)) {
			return false;
		}

		$return = false;
		foreach ($chatrooms as $chatroom) {
			if (!array_diff($users, explode(',', $chatroom->users))) {
				$return = $chatroom->id;
				break;
			}
		}

		return $return;
	}


	private function chatRoomExists($chatroom)
	{
		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName('id'))
			->from($this->db->quoteName('jos_emundus_chatroom'))
			->where($this->db->quoteName('id') . ' = ' . $chatroom);
		$this->db->setQuery($query);

		try {

			return !empty($this->db->loadResult());

		}
		catch (Exception $e) {
			Log::add('Error getting chatroom : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');

			return false;
		}

	}

	/// get message recap by fnum
	public function getMessageRecapByFnum($fnum)
	{
		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'evaluation.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');

		$_mEval = new EmundusModelEvaluation;
		$_mFile = new EmundusModelFiles;

		if (!empty($fnum)) {
			try {
				/// first --> get attachment ids from fnums
				$attachment_ids = $_mEval->getLettersByFnums($fnum, $attachments = true);

				$attachment_list = array();
				foreach ($attachment_ids as $key => $value) {
					$attachment_list[] = $value['id'];
				}

				$attachment_list = array_unique(array_filter($attachment_list));            /// this line ensures that all attachment ids will appear once

				/// get message template from attachment list
				$query->clear()
//                    ->select('distinct #__emundus_setup_emails.id, #__emundus_setup_emails.lbl, #__emundus_setup_emails.subject, #__emundus_setup_emails.message')
					->select('distinct jos_emundus_setup_emails.*, jos_emundus_email_templates.Template')
					->from($this->db->quoteName('#__emundus_setup_emails'))
					->leftJoin($this->db->quoteName('#__emundus_email_templates') . ' ON ' . $this->db->quoteName('#__emundus_email_templates.id') . ' = ' . $this->db->quoteName('#__emundus_setup_emails.email_tmpl'))
					->leftJoin($this->db->quoteName('#__emundus_setup_emails_repeat_letter_attachment') . ' ON ' . $this->db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.parent_id') . ' = ' . $this->db->quoteName('#__emundus_setup_emails.id'))
					->where($this->db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.letter_attachment') . ' IN (' . implode(',', $attachment_list) . ')');

				$this->db->setQuery($query);
				$_message_Info = $this->db->loadObjectList();

				/// third, for each $attachment ids --> detect the uploaded letters (if any), otherwise, detect the letter (default)
				$uploads = array();

				foreach ($attachment_list as $key => $attach) {
					/* generate letters each time get instant message */
					$letter = $_mEval->generateLetters($fnum, [$attach], 0, 0, 0);

					$upload_id       = current($letter->files)['upload'];
					$upload_filename = current($letter->files)['url'] . current($letter->files)['filename'];

					$attachment_raw = $_mFile->getSetupAttachmentsById([$attach]);

					$attachment_value = current($attachment_raw)['value'];
					$attachment_label = current($attachment_raw)['lbl'];

					$uploads[] = array('is_existed' => true, 'id' => $upload_id, 'value' => $attachment_value, 'label' => $attachment_label, 'dest' => $upload_filename);
				}

				/// get tags by email
				$_tags = $this->getTagsByEmail($_message_Info[0]->id);

				return array('message_recap' => $_message_Info, 'attached_letter' => $uploads, 'tags' => $_tags);
			}
			catch (Exception $e) {
				Log::add('Error get available message by fnum : ' . $e->getMessage(), Log::ERROR, 'com_emundus.message');

				return false;
			}
		}
		else {
			return false;
		}
	}

	// get tags by email
	public function getTagsByEmail($eid)
	{
		if (!empty($eid)) {
			try {
				$query = $this->db->getQuery(true);

				$query->clear()
					->select('#__emundus_setup_action_tag.*')
					->from($this->db->quoteName('#__emundus_setup_action_tag'))
					->leftJoin($this->db->quoteName('#__emundus_setup_emails_repeat_tags') . ' ON ' . $this->db->quoteName('#__emundus_setup_action_tag.id') . ' = ' . $this->db->quoteName('#__emundus_setup_emails_repeat_tags.tags'))
					->leftJoin($this->db->quoteName('#__emundus_setup_emails') . ' ON ' . $this->db->quoteName('#__emundus_setup_emails.id') . ' = ' . $this->db->quoteName('#__emundus_setup_emails_repeat_tags.parent_id'))
					->where($this->db->quoteName('#__emundus_setup_emails.id') . ' = ' . (int) $eid);

				$this->db->setQuery($query);

				return $this->db->loadObjectList();

			}
			catch (Exception $e) {
				Log::add('Error get tags by fnum : ' . $e->getMessage(), Log::ERROR, 'com_emundus.message');

				return false;
			}
		}
		else {
			return false;
		}
	}

	/// add tags by fnum
	public function addTagsByFnum($fnum, $tmpl)
	{
		if (!empty($fnum) and !empty($tmpl)) {
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
			$m_files = new EmundusModelFiles();

			$fnum_info = $m_files->getFnumInfos($fnum);
			$_tags     = $this->getTagsByEmail($tmpl);      // return type :: array

			if (!empty($_tags)) {
				foreach ($_tags as $key => $tag) {
					$assoc_tag = $m_files->getTagsByIdFnumUser($tag->id, $fnum_info['fnum'], $fnum_info['applicant_id']);
					if (!$assoc_tag) {
						$m_files->tagFile([$fnum_info['fnum']], [$tag->id]);
					}
				}

				return true;
			}
		}
		else {
			return false;
		}
	}

	// lock or unlock action for fnum
	public function getActionByFnum($fnum)
	{
		if (!empty($fnum)) {
			/// from fnum --> detect the message
			$query = $this->db->getQuery(true);

			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'evaluation.php');
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');

			$_mEval = new EmundusModelEvaluation;
			$_mFile = new EmundusModelFiles;

			try {
				$attachment_ids = $_mEval->getLettersByFnums($fnum, $attachments = true);

				if (count($attachment_ids) > 0) {

					$attachment_list = array();
					foreach ($attachment_ids as $key => $value) {
						$attachment_list[] = $value['id'];
					}

					$attachment_list = array_unique(array_filter($attachment_list));            /// this line ensures that all attachment ids will appear once

					/// get message template from attachment list
					$query->clear()
						->select('distinct #__emundus_setup_emails.id, #__emundus_setup_emails.lbl, #__emundus_setup_emails.subject, #__emundus_setup_emails.message')
						->from($this->db->quoteName('#__emundus_setup_emails'))
						->leftJoin($this->db->quoteName('#__emundus_setup_emails_repeat_letter_attachment') . ' ON ' . $this->db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.parent_id') . ' = ' . $this->db->quoteName('#__emundus_setup_emails.id'))
						->where($this->db->quoteName('#__emundus_setup_emails_repeat_letter_attachment.letter_attachment') . ' IN (' . implode(',', $attachment_list) . ')');

					$this->db->setQuery($query);
					$_message_Info = $this->db->loadObjectList();
					if (!empty($_message_Info)) {
						return true;
					}
					else {
						return false;
					}
				}
				else {
					return false;
				}
			}
			catch (Exception $e) {
				Log::add('Error get getActionByFnum : ' . $e->getMessage(), Log::ERROR, 'com_emundus.message');

				return false;
			}
		}
		else {
			return false;
		}
	}

	/// get all documents being letter
	public function getAllDocumentsLetters()
	{
		$query = $this->db->getQuery(true);

		try {
			$query->clear()
				->select('#__emundus_setup_attachments.*')
				->from($this->db->quoteName('#__emundus_setup_attachments'))
				->where($this->db->quoteName('#__emundus_setup_attachments.id') . ' IN (SELECT DISTINCT #__emundus_setup_letters.attachment_id FROM #__emundus_setup_letters)');

			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Cannot get all documents being letter : ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return [];      /// return empty array
		}
	}

	/// get attachments by profile (jos_emundus_setup_attachment_profiles)
	public function getAttachmentsByProfiles($fnums = [])
	{
		$query = $this->db->getQuery(true);

		$results = [];

		if (!empty($fnums)) {
			try {
				require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
				require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');

				$m_profiles = new EmundusModelProfile;
				$m_files    = new EmundusModelFiles;

				$profiles    = [];
				$attachments = new stdClass();

				foreach ($fnums as $fnum) {
					$fnumInfos = $m_files->getFnumInfos($fnum);

					$profiles_by_campaign = $m_profiles->getProfilesIDByCampaign([$fnumInfos['id']]);

					if (!empty($profiles_by_campaign)) {
						foreach ($profiles_by_campaign as $profile) {
							$profiles[] = $profile;
						}
						$profiles = array_unique($profiles);
					}
					else {
						$_fnumInfo  = $m_files->getFnumInfos($fnum);
						$profiles[] = $_fnumInfo['profile_id'];
					}
				}

				foreach ($profiles as $profile) {
					$attachments->{$profile} = new stdClass();
					$letters                 = [];

					$query->clear()
						->select('#__emundus_setup_attachments.*, #__emundus_setup_profiles.id AS pr_id, #__emundus_setup_profiles.label as pr_label')
						->from($this->db->quoteName('#__emundus_setup_attachments'))
						->leftJoin($this->db->quoteName('#__emundus_setup_attachment_profiles') . ' ON ' . $this->db->quoteName('#__emundus_setup_attachment_profiles.attachment_id') . ' = ' . $this->db->quoteName('#__emundus_setup_attachments.id'))
						->leftJoin($this->db->quoteName('#__emundus_setup_profiles') . ' ON ' . $this->db->quoteName('#__emundus_setup_attachment_profiles.profile_id') . ' = ' . $this->db->quoteName('#__emundus_setup_profiles.id'))
						->where($this->db->quoteName('#__emundus_setup_attachment_profiles.profile_id') . ' = ' . $profile);
					$this->db->setQuery($query);
					$res = $this->db->loadObjectList();

					foreach ($res as $r) {
						$letters[] = ['letter_id' => $r->id, 'letter_label' => $r->value];
					}

					$attachments->{$profile}->label   = $m_profiles->getProfileById($profile)['label'];
					$attachments->{$profile}->letters = $letters;
				}

				$results = (array) $attachments;
			}
			catch (Exception $e) {
				Log::add('Cannot get attachments by profiles : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $results;
	}

	/// get all attachments
	public function getAllAttachments()
	{
		$query = $this->db->getQuery(true);

		try {
			$query->clear()
				->select('#__emundus_setup_attachments.*')
				->from($this->db->quoteName('#__emundus_setup_attachments'))
				->where($this->db->quoteName('published') . " = 1");

			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('Cannot get all attachments : ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return [];      /// return empty array
		}
	}

	/// add tags by fnums
	public function addTagsByFnums($fnums, $tmpl)
	{
		$set_tag = [];

		$query = $this->db->getQuery(true);

		if (!empty($fnums) and !empty($tmpl)) {
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
			$m_files = new EmundusModelFiles();

			foreach ($fnums as $fnum) {
				$this->addTagsByFnum($fnum, $tmpl);
			}

			return true;
		}
		else {
			return false;       /// no fnum or no email template, cannot add tag
		}
	}

	/**
	 * @param $date   DateTime  Date to delete messages before
	 * @description             Deletes messages before a given date.
	 * @return int
	 */
	public function deleteMessagesBeforeADate($date)
	{
		$deleted_messages = 0;

		if (!empty($date))
		{
			if(version_compare(JVERSION, '4.0', '>=')) {
				$this->db = Factory::getContainer()->get('DatabaseDriver');
			} else {
				$this->db = Factory::getDbo();
			}

			$query = $this->db->getQuery(true);

			$query->delete($this->db->quoteName('#__messages'))
				->where($this->db->quoteName('date_time') . ' < ' . $this->db->quote($date->format('Y-m-d H:i:s')))
				->where($this->db->quoteName('folder_id') . ' <> 2')
				->where($this->db->quoteName('page') . ' IS NULL');

			try
			{
				$this->db->setQuery($query);
				$this->db->execute();
				$deleted_messages = $this->db->getAffectedRows();
			}
			catch (Exception $e)
			{
				Log::add('Could not delete messages from jos_messages table in model messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $deleted_messages;
	}

	/**
	 * @param $date   DateTime  Date to export messages before
	 * @description             Exports messages before a given date.
	 * @return string
	 */
	public function exportMessagesBeforeADate($date)
	{
		$csv_filename = '';

		if (!(empty($date)))
		{
			if(version_compare(JVERSION, '4.0', '>=')) {
				$db = Factory::getContainer()->get('DatabaseDriver');
			} else {
				$db = Factory::getDbo();
			}

			$limit = 10000;
			$offset = 0;
			$header_written = false;

			do {
				$query = $db->getQuery(true);

				$query->clear()
					->select('*')
					->from($db->quoteName('#__messages'))
					->where($db->quoteName('date_time') . ' < ' . $db->quote($date->format('Y-m-d H:i:s')))
					->where($db->quoteName('folder_id') . ' <> 2')
					->where($db->quoteName('page') . ' IS NULL')
					->setLimit($limit, $offset);

				try
				{
					$db->setQuery($query);
					$messages = $db->loadAssocList();
				}
				catch (Exception $e)
				{
					Log::add('Could not fetch messages from jos_messages table in model messages at query: ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
					break;
				}

				if (!empty($messages))
				{
					if (!$header_written) {
						$csv_filename = JPATH_SITE . '/tmp/backup_messages_' . date('Y-m-d_H-i-s') . '.csv';
						$csv_file     = fopen($csv_filename, 'w');
						fputcsv($csv_file, array_keys($messages[0]));
						$header_written = true;
					}

					foreach ($messages as $message)
					{
						fputcsv($csv_file, $message);
					}

					$offset += $limit;
				}

			} while (count($messages) == $limit);

			if ($header_written) {
				fclose($csv_file);
			}
		}
		return $csv_filename;
	}

	/**
	 * Get status of chatroom
	 *
	 * @param $fnum
	 *
	 * @return mixed|null
	 *
	 * @since version 1.40.0
	 */
	public function getStatusChatroom($fnum)
	{
		$status = null;

		$query = $this->db->getQuery(true);

		$query->clear()
			->select( $this->db->quoteName('status'))
			->from( $this->db->quoteName('#__emundus_chatroom'))
			->where( $this->db->quoteName('fnum').' LIKE '.  $this->db->quote($fnum));

		try
		{
			$this->db->setQuery($query);
			$status =  $this->db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Could not retrieve chatroom for fnum: ' . $fnum. preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $status;
	}

	/**
	 * Open chatroom
	 *
	 * @param $fnum
	 *
	 *
	 * @return boolean
	 * @since version 1.40.0
	 */
	public function openChatroom($fnum)
	{
		$opened = false;

		$query = $this->db->getQuery(true);

		$fields = array(
			$this->db->quoteName('status') . ' = 1'
		);

		// Conditions for which records should be updated.
		$conditions = array(
			$this->db->quoteName('fnum') . ' LIKE ' .  $this->db->quote($fnum)
		);

		$query->update($this->db->quoteName('#__emundus_chatroom'))->set($fields)->where($conditions);

		try
		{
			$this->db->setQuery($query);
			$opened = $this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Could not update chatroom for fnum: ' . $fnum. preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $opened;
	}

	/**
	 * Close chatroom
	 *
	 * @param         $fnum
	 * @param   bool  $redirect
	 *
	 * @return boolean|void
	 *
	 * @since version 1.40.0
	 */
	public function closeMessenger($fnum,$redirect = true)
	{
		$closed = false;

		try{
			$query = $this->db->getQuery(true);

			$fields = array(
				$this->db->quoteName('status') . ' = 0'
			);

			// Conditions for which records should be updated.
			$conditions = array(
				$this->db->quoteName('fnum') . ' LIKE ' .  $this->db->quote($fnum)
			);

			$query->update($this->db->quoteName('#__emundus_chatroom'))->set($fields)->where($conditions);

			$this->db->setQuery($query);

			$closed = $this->db->execute();

			if($closed && $redirect)
			{
				$this->app->redirect('index.php');
			}
		}
		catch (Exception $e)
		{
			Log::add('Could not update chatroom for fnum: ' . $fnum. preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $closed;
	}
}
