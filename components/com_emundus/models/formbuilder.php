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
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

class EmundusModelFormbuilder extends JModelList
{
	private $app;
	private $m_translations;
	private $h_fabrik;

	private $db;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->app = Factory::getApplication();
		$this->db = Factory::getContainer()->get('DatabaseDriver');


		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'translations.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'fabrik.php');
		require_once(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'update.php');
		$this->m_translations = new EmundusModelTranslations;
		$this->h_fabrik       = new EmundusHelperFabrik;

		Log::addLogger(['text_file' => 'com_emundus.formbuilder.php'], Log::ALL, array('com_emundus.formbuilder'));
	}

	public function replaceAccents($value)
	{
		$unwanted_array = array('Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
		                        'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
		                        'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
		                        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
		                        'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', '!' => '', '?' => '', '*' => '', '%' => 'y', '^' => '', '€' => '', '+' => '', '=' => '',
		                        ';' => '', ',' => '', '&' => '', '@' => '', '#' => '', '`' => '', '¨' => '', '§' => '', '"' => '', '\'' => '', '\\' => '', '/' => '', '(' => '', ')' => '', '[' => '', ']' => '', ' ' => '_');

		return strtr($value, $unwanted_array);
	}

	/** TRANSLATION SYSTEM */
	public function translate($key, $values, $reference_table = '', $id = '', $reference_field = '', $user_id = 0)
	{
		$languages = JLanguageHelper::getLanguages();
		foreach ($languages as $language) {
			if (!empty($values) && isset($values[$language->sef])) {
				$this->m_translations->insertTranslation($key, $values[$language->sef], $language->lang_code, '', 'override', $reference_table, $id, $reference_field, $user_id);
			}
		}

		return $key;
	}

	public function updateTranslation($key, $values, $reference_table = '', $reference_id = 0, $reference_field = '', $user_id = null)
	{
		if(empty($user_id)) {
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		$languages = JLanguageHelper::getLanguages();

		foreach ($languages as $language) {
			if (isset($values[$language->sef])) {
				$key = $this->m_translations->updateTranslation($key, $values[$language->sef], $language->lang_code, 'override', $reference_table, $reference_id, $reference_field, $user_id);
			}
		}

		return $key;
	}

	function deleteTranslation($text)
	{
		$this->m_translations->deleteTranslation($text);
	}

	/**
	 * Copy languages file to administration to get elements translations in backoffice
	 *
	 * @param $langtag
	 *
	 * @return bool
	 */
	function copyFileToAdministration($langtag)
	{
		$origin_file = basename(__FILE__) . '/../language/overrides/' . $langtag . '.override.ini';
		$newfile     = basename(__FILE__) . '/../administrator/language/overrides/' . $langtag . '.override.ini';

		if (file_exists($newfile)) {
			unlink($newfile);
		}

		if (!copy($origin_file, $newfile)) {
			return false;
		}

		return true;
	}

    /**
     * Ge translation of an element in all languages
     * @param $text
     * @param $code_lang
     * @return string|string[]
     */
	function getTranslation($text, $code_lang){
		$translation = '';

		if(!empty($text) && !empty($code_lang)) {
			$translation = $text;

			$fileName = constant('JPATH_SITE') . '/language/overrides/' . $code_lang . '.override.ini';
			$strings  = LanguageHelper::parseIniFile($fileName);

			if(isset($strings[$text])) {
				$translation = $strings[$text];
			}
		}

		return $translation;
	}

	/**
	 * Get translation of an array
	 *
	 * @param $toJTEXT
	 *
	 * @return array
	 */
	function getJTEXTA($toJTEXT)
	{
		$translations = [];

		if (!empty($toJTEXT) && is_array($toJTEXT)) {
			foreach ($toJTEXT as $text) {
				$translations[] = JText::_($text);
			}
		}

		return $translations;
	}

	/**
	 * Get translation of a text
	 *
	 * @param $toJTEXT
	 *
	 * @return mixed
	 */
	function getJTEXT($toJTEXT)
	{
		$toJTEXT = JText::_($toJTEXT);

		return JText::_($toJTEXT);
	}

	/**
	 * Update translations
	 *
	 * @param $labelTofind
	 * @param $locallang
	 * @param $NewSubLabel
	 */
	function formsTrad($labelTofind, $NewSubLabel, $element = null, $group = null, $page = null, $user_id = null)
	{
		$new_key = '';

		if(empty($user_id)) {
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		try {
			$query = $this->db->getQuery(true);

			if (!empty($element)) {
				$new_key = $this->updateTranslation($labelTofind, $NewSubLabel, 'fabrik_elements', $element, '', $user_id);

				if (!empty($new_key) && !is_bool($new_key)) {
					$query->update($this->db->quoteName('#__fabrik_elements'))
						->set($this->db->quoteName('label') . ' = ' . $this->db->quote($new_key))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($element));

					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
			elseif (!empty($group)) {
				$new_key = $this->updateTranslation($labelTofind, $NewSubLabel, 'fabrik_groups', $group, '', $user_id);

				if (!empty($new_key) && !is_bool($new_key)) {
					$translation = Text::_($new_key);
					$new_name    = !empty($translation) && $translation != "Nouvelle section" ? $translation : $new_key;

					$query->update($this->db->quoteName('#__fabrik_groups'))
						->set($this->db->quoteName('name') . ' = ' . $this->db->quote($new_name))
						->set($this->db->quoteName('label') . ' = ' . $this->db->quote($new_key))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($group));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
			elseif (!empty($page)) {
				$new_key = $this->updateTranslation($labelTofind, $NewSubLabel, 'fabrik_forms', $page, '', $user_id);
				if (!empty($new_key) && !is_bool($new_key)) {
					$query->update($this->db->quoteName('#__fabrik_forms'))
						->set($this->db->quoteName('label') . ' = ' . $this->db->quote($new_key))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($page));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
			else {
				$new_key = $this->updateTranslation($labelTofind, $NewSubLabel, '', 0, '', $user_id);
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
			Log::add('component/com_emundus/models/formbuilder | Error when update the translation of ' . $labelTofind . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			$new_key = false;
		}

		return $new_key;
	}

	/** END TRANSLATION SYSTEM */

	function getSpecialCharacters()
	{
		return array('=', '&', ',', '#', '_', '*', ';', '!', '?', ':', '+', '$', '\'', ' ', '£', ')', '(', '@', '%');
	}

	function htmlspecial_array(&$variable)
	{
		foreach ($variable as &$value) {
			if (!is_array($value)) {
				$value = htmlspecialchars($value);
			}
			else {
				$this->htmlspecial_array($value);
			}
		}
	}

	function updateElementWithoutTranslation($eid, $label)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->update($this->db->quoteName('#__fabrik_elements'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($label))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($eid));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error update label of the element ' . $eid . ' without translation : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function updateGroupWithoutTranslation($gid, $label)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->update($this->db->quoteName('#__fabrik_groups'))
				->set($this->db->quoteName('name') . ' = ' . $this->db->quote($label))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($label))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($gid));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error update label of the group ' . $gid . ' without translation : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function updatePageWithoutTranslation($pid, $label)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($label))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($pid));
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->update($this->db->quoteName('#__fabrik_lists'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($label))
				->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($pid));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error update label of the page ' . $pid . ' without translation : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function updatePageIntroWithoutTranslation($pid, $intro)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('intro') . ' = ' . $this->db->quote($intro))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($pid));
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->update($this->db->quoteName('#__fabrik_lists'))
				->set($this->db->quoteName('introduction') . ' = ' . $this->db->quote($intro))
				->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($pid));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error update label of the page intro ' . $pid . ' without translation : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function createApplicantMenu($label, $intro, $prid, $template)
	{
		if (empty($prid)) {
			Log::add('component/com_emundus/models/formbuilder | Error when create a new page in form, missing prid', Log::ERROR, 'com_emundus');

			return [
				'status' => false,
				'msg'    => 'MISSING_PRID'
			];
		}

		$query = $this->db->getQuery(true);

		$eMConfig = JComponentHelper::getParams('com_emundus');
		$modules  = $eMConfig->get('form_builder_page_creation_modules', [123, 124]);

		if (!is_array($label)) {
			$label = json_decode($label, true);
		}
		if (!is_array($intro)) {
			$intro = json_decode($intro, true);
		}

		$lang           = JFactory::getLanguage();
		$actualLanguage = substr($lang->getTag(), 0, 2);

		try {
			$formid = $this->createFabrikForm($prid, $label, $intro);
			if (empty($formid)) {
				return array(
					'status' => false,
					'msg'    => 'UNABLE_TO_CREATE_FARBIK_FORM'
				);
			}

			$list = $this->createFabrikList($prid, $formid);
			if (empty($list)) {
				return array(
					'status' => false,
					'msg'    => 'UNABLE_TO_CREATE_FARBIK_FORM'
				);
			}

			$joined = $this->joinFabrikListToProfile($list['id'], $prid);
			if (!$joined) {
				return array(
					'status' => false,
					'msg'    => 'UNABLE_TO_JOIN_LIST_TO_PRID'
				);
			}

			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($prid));
			$this->db->setQuery($query);
			$profile = $this->db->loadObject();
			if (empty($profile)) {
				return array(
					'status' => false,
					'msg'    => 'UNABLE_TO_FIND_PROFILE_DATA_FROM_PRID'
				);
			}
			$menutype = $profile->menutype;

			// INSERT MENU
			$query->clear()
				->select('*')
				->from('#__menu')
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($menutype))
				->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'));
			$this->db->setQuery($query);
			$menu_parent = $this->db->loadObject();

			/**
			 * Case of old platforms. deprecated
			 */
			if (empty($menu_parent) || empty($menu_parent->id)) {
				$query->clear()
					->select('*')
					->from('#__menu')
					->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($menutype))
					->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('url'));
				$this->db->setQuery($query);
				$menu_parent = $this->db->loadObject();
			}

			$query->clear()
				->select('rgt')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($menutype))
				->andWhere($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($menu_parent->id))
				->order('rgt');
			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();
			$rgts    = [];
			foreach (array_values($results) as $result) {
				if (!in_array($result->rgt, $rgts)) {
					$rgts[] = intval($result->rgt);
				}
			}

			$params = EmundusHelperFabrik::prepareFabrikMenuParams();
			$datas  = [
				'menutype'     => $profile->menutype,
				'title'        => 'FORM_' . $prid . '_' . $formid,
				'link'         => 'index.php?option=com_fabrik&view=form&formid=' . $formid,
				'path'         => $menu_parent->path . '/' . preg_replace('/\s+/', '-', strtolower($this->replaceAccents($label['fr']))) . '-form-' . $formid,
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_fabrik')->id,
				'params'       => $params
			];
			$result = EmundusHelperUpdate::addJoomlaMenu($datas, $menu_parent->id, 1, 'last-child', $modules);
			if ($result['status'] !== true) {
				return array(
					'status' => false,
					'msg'    => 'UNABLE_TO_INSERT_NEW_MENU'
				);
			}
			$newmenuid = $result['id'];

			$alias = 'menu-profile' . $prid . '-form-' . $newmenuid;
			$query->clear()
				->update($this->db->quoteName('#__menu'))
				->set($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias))
				->set($this->db->quoteName('path') . ' = ' . $this->db->quote($menu_parent->path . '/' . $alias))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($newmenuid));
			$this->db->setQuery($query);
			$this->db->execute();


			// Create hidden group
			$group = $this->createGroup(array('fr' => 'Hidden group', 'en' => 'Hidden group',), $formid, -1);
			$this->createElement('id', $group['group_id'], 'internalid', 'id', '', 1, 0, 0);
			$this->createElement('time_date', $group['group_id'], 'jdate', 'time date', '', 1, 0);
			$this->createElement('user', $group['group_id'], 'user', 'user', '', 1, 0);
			$default_fnum = 'use Joomla\CMS\Factory;
			$app = Factory::getApplication();
			$fnum = Factory::getApplication()->getInput()->getString(\'rowid\', \'\');
			if (empty($fnum)) { 
			$fnum = Factory::getApplication()->getSession()->get(\'emundusUser\')->fnum;
			}
			return $fnum;';

			$this->createElement('fnum', $group['group_id'], 'field', 'fnum', $default_fnum, 1, 0, 1, 1, 0, 44);
			//

			// Create the first group
			$group_label = array(
				'fr' => 'Nouvelle section',
				'en' => 'New section'
			);
			$this->createGroup($group_label, $formid);
			//

			// Save as template
			if ($template == 'true') {
				$query->clear()
					->insert($this->db->quoteName('#__emundus_template_form'))
					->set($this->db->quoteName('form_id') . ' = ' . $this->db->quote($formid))
					->set($this->db->quoteName('label') . ' = ' . $this->db->quote('FORM_' . $prid . '_' . $formid))
					->set($this->db->quoteName('created') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			//

			return array(
				'status'        => true,
				'msg'           => 'SUCCESS',
				'id'            => $formid,
				'db_table_name' => $list['db_table_name'],
				'label'         => $label[$actualLanguage],
				'link'          => 'index.php?option=com_fabrik&view=form&formid=' . $formid,
				'new_menu_id'   => $newmenuid,
				'rgt'           => array_values($rgts)[strval(sizeof($rgts) - 1)] + 2,
			);
		}
		catch (Exception $e) {
			$query_str = !is_string($query) ? $query->__toString() : $query;
			Log::add('component/com_emundus/models/formbuilder | Error when create a new page in form ' . $prid . ' : ' . preg_replace("/[\r\n]/", " ", $query_str . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return array();
		}
	}

	/**
	 * @param $prid  int profile id
	 * @param $label array labels by language
	 * @param $intro array intros by language
	 * @param $type  string (form || eval)
	 *
	 * @return false|int|mixed
	 */
	function createFabrikForm($prid, $label, $intro, $type = '', $user = null)
	{
		$form_id = 0;

		if (!empty($prid) && !empty($label) && is_array($label)) {
			if (empty($user)) {
				$user = Factory::getApplication()->getIdentity();
			}

			$query = $this->db->getQuery(true);

			try {
				$params = $this->h_fabrik->prepareFormParams(true, $type);
				$data   = array(
					'label'               => 'FORM_' . $prid,
					'record_in_database'  => 1,
					'error'               => 'FORM_ERROR',
					'intro'               => '<p>' . 'FORM_' . $prid . '_INTRO</p>',
					'created'             => gmdate('Y-m-d h:i:s'),
					'created_by'          => $user->id,
					'created_by_alias'    => $user->username,
					'modified'            => gmdate('Y-m-d h:i:s'),
					'modified_by'         => $user->id,
					'checked_out'         => $user->id,
					'checked_out_time'    => gmdate('Y-m-d h:i:s'),
					'publish_up'          => gmdate('Y-m-d h:i:s'),
					'reset_button_label'  => 'RESET',
					'submit_button_label' => 'SAVE_CONTINUE',
					'form_template'       => 'emundus',
					'view_only_template'  => 'emundus',
					'published'           => 1,
					'params'              => json_encode($params),
				);

				$query->insert($this->db->quoteName('#__fabrik_forms'))
					->columns($this->db->quoteName(array_keys($data)))
					->values(implode(',', $this->db->quote(array_values($data))));
				$this->db->setQuery($query);
				$this->db->execute();
				$form_id = $this->db->insertid();

				if (!empty($form_id)) {
					$query->clear()
						->update($this->db->quoteName('#__fabrik_forms'))
						->set($this->db->quoteName('label') . ' = ' . $this->db->quote('FORM_' . $prid . '_' . $form_id))
						->set($this->db->quoteName('intro') . ' = ' . $this->db->quote('<p>' . 'FORM_' . $prid . '_INTRO_' . $form_id . '</p>'));
					$query->where($this->db->quoteName('id') . ' = ' . $this->db->quote($form_id));
					$this->db->setQuery($query);
					$this->db->execute();

					// Add translation to translation files
					$this->translate('FORM_' . $prid . '_' . $form_id, $label, 'fabrik_forms', $form_id, 'label');

					if (!empty($intro) && is_array($intro)) {
						$this->translate('FORM_' . $prid . '_INTRO_' . $form_id, $intro, 'fabrik_forms', $form_id, 'intro');
					}
				}
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/formbuilder | Error when create a form ' . $prid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $form_id;
	}

	function createFabrikList($prid, $formid, $access = null, $type = 'default', $user = null)
	{
		$response = [];

		if (empty($access)) {
			$access = $prid;
		}

		if (empty($user)) {
			$user =  $this->app->getIdentity();
		}

		$query = $this->db->getQuery(true);

		try {
			// Create core table
			$query->select('COUNT(*)')
				->from($this->db->quoteName('information_schema.tables'))
				->where($this->db->quoteName('table_name') . ' LIKE ' . $this->db->quote('%jos_emundus_' . $prid . '%'));
			$this->db->setQuery($query);
			$result    = $this->db->loadResult();
			$increment = str_pad(strval($result), 2, '0', STR_PAD_LEFT);


			$collation = 'utf8mb4_0900_ai_ci';
			$sql_engine = $this->db->setQuery("SHOW VARIABLES LIKE 'version_comment'")->loadAssoc();
			if(!empty($sql_engine)) {
				$sql_engine = $sql_engine['Value'];
				if(strpos($sql_engine, 'MySQL') === false) {
					$collation = 'utf8mb4_unicode_ci';
				}
			}

			if ($type === 'eval') {
				$query = "CREATE TABLE IF NOT EXISTS jos_emundus_" . $prid . "_" . $increment . " (
		            id int(11) NOT NULL AUTO_INCREMENT,
		            time_date datetime NULL DEFAULT current_timestamp(),
		            ccid int(11) NOT NULL,
		            fnum VARCHAR(28) NOT NULL,
		            evaluator int(11) NOT NULL,
		            updated_by int(11) NOT NULL,
		            step_id int(11) NOT NULL,
		            PRIMARY KEY (id)
		            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE " . $collation;
			} else {
				$query = "CREATE TABLE IF NOT EXISTS jos_emundus_" . $prid . "_" . $increment . " (
		            id int(11) NOT NULL AUTO_INCREMENT,
		            time_date datetime NULL DEFAULT current_timestamp(),
		            fnum varchar(28) NOT NULL,
		            user int(11) NULL,
		            PRIMARY KEY (id),
		            UNIQUE KEY fnum (fnum)
		            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE ".$collation;
			}
			$this->db->setQuery($query);
			$table_created = $this->db->execute();

			if ($table_created) {
				// Add constraints

				if ($type === 'default') {
					$query = "ALTER TABLE jos_emundus_" . $prid . "_" . $increment . "
		            ADD CONSTRAINT jos_emundus_" . $prid . "_" . $increment . "_ibfk_1
		            FOREIGN KEY (user) REFERENCES jos_emundus_users (user_id) ON DELETE CASCADE ON UPDATE CASCADE;";
					$this->db->setQuery($query);
					$this->db->execute();

					$query = "ALTER TABLE jos_emundus_" . $prid . "_" . $increment . "
		            ADD CONSTRAINT jos_emundus_" . $prid . "_" . $increment . "_ibfk_2
		            FOREIGN KEY (fnum) REFERENCES jos_emundus_campaign_candidature (fnum) ON DELETE CASCADE ON UPDATE CASCADE;";
					$this->db->setQuery($query);
					$this->db->execute();

					$query = "CREATE INDEX user
            ON jos_emundus_" . $prid . "_" . $increment . " (user);";
					$this->db->setQuery($query);
					$this->db->execute();
					//
				}

				// INSERT FABRIK LIST
				$params = $this->h_fabrik->prepareListParams();

				$data = array(
					'label'            => 'FORM_' . $prid,
					'introduction'     => '',
					'form_id'          => $formid,
					'db_table_name'    => 'jos_emundus_' . $prid . '_' . $increment,
					'db_primary_key'   => 'jos_emundus_' . $prid . '_' . $increment . '.id',
					'auto_inc'         => 1,
					'connection_id'    => 1,
					'created'          => date('Y-m-d h:i:s'),
					'created_by'       => $user->id,
					'created_by_alias' => $user->username,
					'modified'         => date('Y-m-d h:i:s'),
					'modified_by'      => $user->id,
					'checked_out'      => $user->id,
					'checked_out_time' => date('Y-m-d h:i:s'),
					'published'        => 1,
					'publish_up'       => date('Y-m-d h:i:s'),
					'access'           => 7,
					'hits'             => 0,
					'rows_per_page'    => 10,
					'template'         => 'bootstrap',
					'order_by'         => '[""]',
					'order_dir'        => '["ASC"]',
					'filter_action'    => 'onchange',
					'group_by'         => '',
					'params'           => json_encode($params),
				);

				$query = $this->db->getQuery(true);
				$query->insert($this->db->quoteName('#__fabrik_lists'))
					->columns($this->db->quoteName(array_keys($data)))
					->values(implode(',', $this->db->quote(array_values($data))));
				$this->db->setQuery($query);
				$list_inserted = $this->db->execute();

				if ($list_inserted) {
					$list_id = $this->db->insertid();

					$query->clear();
					$query->update($this->db->quoteName('#__fabrik_lists'))
						->set('label = ' . $this->db->quote('FORM_' . strtoupper($prid) . '_' . $formid))
						->set('access = ' . $this->db->quote($access));
					$query->where($this->db->quoteName('id') . ' = ' . $this->db->quote($list_id));
					$this->db->setQuery($query);
					$this->db->execute();
					//

					$response = array(
						'id'            => $list_id,
						'db_table_name' => 'jos_emundus_' . $prid . '_' . $increment
					);
				}
			}
		}
		catch (Exception $e) {
			$query_str = is_string($query) ? $query : $query->__toString();

			error_log($e->getMessage());
			error_log($query_str);

			Log::add('component/com_emundus/models/formbuilder | Error when create a list ' . $prid . ' : ' . preg_replace("/[\r\n]/", " ", $query_str . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $response;
	}

	function joinFabrikListToProfile($listid, $prid)
	{

		$query = $this->db->getQuery(true);

		try {
			$columns = array(
				'form_id',
				'profile_id',
				'created'
			);

			$values = array(
				$listid,
				$prid,
				date('Y-m-d H:i:s')
			);

			$query->insert($this->db->quoteName('#__emundus_setup_formlist'))
				->columns($this->db->quoteName($columns))
				->values(implode(',', $this->db->quote($values)));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error when join list ' . $listid . ' to profile ' . $prid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function createSubmittionPage($label, $intro, $prid)
	{
		if (empty($prid)) {
			Log::add('component/com_emundus/models/formbuilder | Error when create a new page in form, missing prid', Log::ERROR, 'com_emundus');

			return [
				'status' => false,
				'msg'    => 'MISSING_PRID'
			];
		}

		$user = JFactory::getUser();

		$query = $this->db->getQuery(true);

		$eMConfig = JComponentHelper::getParams('com_emundus');
		$modules  = $eMConfig->get('form_builder_page_creation_modules', [93, 102, 103, 104, 168, 170]);

		if (!is_array($label)) {
			$label = json_decode($label, true);
		}
		if (!is_array($intro)) {
			$intro = json_decode($intro, true);
		}

		try {
			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($prid));
			$this->db->setQuery($query);
			$profile = $this->db->loadObject();
			if (empty($profile)) {
				return array(
					'status' => false,
					'msg'    => 'UNABLE_TO_FIND_PROFILE_DATA_FROM_PRID'
				);
			}

			$params = $this->h_fabrik->prepareFormParams();
			$params = $this->h_fabrik->prepareSubmittionPlugin($params);

			$now  = gmdate('Y-m-d H:i:s');
			$data = array(
				'label'               => 'FORM_' . $prid,
				'record_in_database'  => 1,
				'error'               => 'FORM_ERROR',
				'intro'               => '<p>' . 'FORM_' . $prid . '_INTRO</p>',
				'created'             => $now,
				'created_by'          => $user->id,
				'created_by_alias'    => $user->username,
				'modified'            => $now,
				'modified_by'         => $user->id,
				'checked_out'         => $user->id,
				'checked_out_time'    => $now,
				'publish_up'          => $now,
				'reset_button_label'  => 'RESET',
				'submit_button_label' => 'SUBMIT',
				'form_template'       => 'emundus',
				'view_only_template'  => 'bootstrap',
				'published'           => 1,
				'params'              => json_encode($params),
			);

			$query->clear()
				->insert($this->db->quoteName('#__fabrik_forms'))
				->columns($this->db->quoteName(array_keys($data)))
				->values(implode(',', $this->db->quote(array_values($data))));
			$this->db->setQuery($query);
			$this->db->execute();
			$formid = $this->db->insertid();
			if (empty($formid)) {
				return array(
					'status' => false,
					'msg'    => 'UNABLE_TO_CREATE_FARBIK_FORM'
				);
			}

			$query->clear()
				->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote('FORM_' . $prid . '_' . $formid . '_SUBMITTING_APPLICATION'))
				->set($this->db->quoteName('intro') . ' = ' . $this->db->quote('<p>' . 'FORM_' . $prid . '_INTRO_' . $formid . '</p>'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($formid));
			$this->db->setQuery($query);
			$this->db->execute();

			// Add translation to translation files
			$this->translate('FORM_' . $prid . '_' . $formid . '_SUBMITTING_APPLICATION', $label, 'fabrik_forms', $formid, 'label');
			$this->translate('FORM_' . $prid . '_INTRO_' . $formid, $intro, 'fabrik_forms', $formid, 'intro');
			//

			// INSERT FABRIK LIST
			$params = $this->h_fabrik->prepareListParams();

			$data = array(
				'label'            => 'FORM_' . $prid . '_' . $formid . '_SUBMITTING_APPLICATION',
				'introduction'     => '',
				'form_id'          => $formid,
				'db_table_name'    => 'jos_emundus_declaration',
				'db_primary_key'   => 'jos_emundus_declaration.id',
				'auto_inc'         => 1,
				'connection_id'    => 1,
				'created'          => date('Y-m-d h:i:s'),
				'created_by'       => $user->id,
				'created_by_alias' => $user->username,
				'modified'         => date('Y-m-d h:i:s'),
				'modified_by'      => $user->id,
				'checked_out'      => $user->id,
				'checked_out_time' => date('Y-m-d h:i:s'),
				'published'        => 1,
				'publish_up'       => date('Y-m-d h:i:s'),
				'access'           => 7,
				'hits'             => 0,
				'rows_per_page'    => 10,
				'template'         => 'bootstrap',
				'order_by'         => '[""]',
				'order_dir'        => '["ASC"]',
				'filter_action'    => 'onchange',
				'group_by'         => '',
				'params'           => json_encode($params),
			);


			$query->clear()
				->insert($this->db->quoteName('#__fabrik_lists'))
				->columns($this->db->quoteName(array_keys($data)))
				->values(implode(',', $this->db->quote(array_values($data))));
			$this->db->setQuery($query);
			$this->db->execute();
			$listid = $this->db->insertid();
			if (empty($listid)) {
				return array(
					'status' => false,
					'msg'    => 'UNABLE_TO_CREATE_FARBIK_FORM'
				);
			}
			//

			// Insert menu
			$params = EmundusHelperFabrik::prepareFabrikMenuParams();
			$datas  = [
				'menutype'     => $profile->menutype,
				'title'        => 'FORM_' . $prid . '_' . $formid,
				'link'         => 'index.php?option=com_fabrik&view=form&formid=' . $formid,
				'path'         => preg_replace('/\s+/', '-', strtolower($this->replaceAccents($label['fr']))) . '-form-' . $formid,
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_fabrik')->id,
				'params'       => $params
			];
			$result = EmundusHelperUpdate::addJoomlaMenu($datas, 1, 1, 'last-child', $modules);
			if ($result['status'] !== true) {
				return array(
					'status' => false,
					'msg'    => 'UNABLE_TO_INSERT_NEW_MENU'
				);
			}
			$submittion_menu_id = $result['id'];

			$alias = 'menu-profile' . $prid . '-submission-' . $submittion_menu_id;
			$query->clear()
				->update($this->db->quoteName('#__menu'))
				->set($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias))
				->set($this->db->quoteName('path') . ' = ' . $this->db->quote($alias))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($submittion_menu_id));
			$this->db->setQuery($query);
			$this->db->execute();

			//

			// Create hidden group
			$label        = array(
				'fr' => 'Hidden group',
				'en' => 'Hidden group',
			);
			$hidden_group = $this->createGroup($label, $formid, -1);
			$this->createElement('id', $hidden_group['group_id'], 'internalid', 'id', '', 1, 0, 0);
			$this->createElement('time_date', $hidden_group['group_id'], 'jdate', 'SENT_ON', '', 1, 0);
			$this->createElement('user', $hidden_group['group_id'], 'user', 'user', '', 1, 0);
			$default_fnum = 'use Joomla\CMS\Factory; $app = Factory::getApplication(); $fnum = $app->getInput()->getString(\'rowid\');if (empty($fnum)) { $fnum = $app->getSession()->get(\'emundusUser\')->fnum;}return $fnum;';
			$this->createElement('fnum', $hidden_group['group_id'], 'field', 'fnum', $default_fnum, 1, 0, 1, 1, 0, 44);
			//

			$group_label = array(
				'fr' => "Confirmation d'envoi de dossier",
				'en' => 'Submitting application'
			);
			$group       = $this->createGroup($group_label, $formid);

			$eid = $this->createElement('declare', $group['group_id'], 'checkbox', 'Confirmation', '', 0, 0, 0);
			EmundusHelperFabrik::addOption($eid, 'CONFIRM_POST', 'JYES');
			EmundusHelperFabrik::addNotEmptyValidation($eid);

			//

			return array(
				'status' => true,
				'id'     => $formid,
				'link'   => 'index.php?option=com_fabrik&view=form&formid=' . $formid,
				'rgt'    => 111,
			);
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error when create the submittion page of the form ' . $prid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return array();
		}
	}

	function deleteMenu($menu)
	{
		$query = $this->db->getQuery(true);

		try {
			$query->clear()
				->update($this->db->quoteName('#__menu'))
				->set($this->db->quoteName('published') . ' = -2')
				->where($this->db->quoteName('id') . ' = ' . (int)$menu);
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at move to trash the menu with the fabrik_form ' . $menu . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function saveAsTemplate($menu, $template)
	{

		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_template_form'))
			->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($menu['id']));
		try {
			$this->db->setQuery($query);
			$existing_template = $this->db->loadObject();

			if ($template != 'false') {
				if ($existing_template == null) {
					$query->clear()
						->insert('#__emundus_template_form')
						->set($this->db->quoteName('created') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
						->set($this->db->quoteName('form_id') . ' = ' . $this->db->quote($menu['id']))
						->set($this->db->quoteName('label') . ' = ' . $this->db->quote($menu['show_title']['titleraw']))
						->set($this->db->quoteName('intro') . ' = ' . $this->db->quote($menu['intro_raw']));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
			else {
				if ($existing_template != null) {
					$query->clear()
						->delete('#__emundus_template_form')
						->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($menu['id']));
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}

			return true;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error when save a page as a model : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function createGroup($label, $fid, $repeat_group_show_first = 1, $mode = 'form', $user = null)
	{
		$group = [];

		if(empty($user))
		{
			$user = Factory::getApplication()->getIdentity();
		}

		if (!empty($fid)) {

			$query = $this->db->getQuery(true);

			$label = !is_array($label) ? json_decode($label, true) : $label;

			// Prepare languages
			$path_to_file   = basename(__FILE__) . '/../language/overrides/';
			$path_to_files  = array();
			$Content_Folder = array();

			$languages = LanguageHelper::getLanguages();
			foreach ($languages as $language) {
				$path_to_files[$language->sef] = $path_to_file . $language->lang_code . '.override.ini';

				if (file_exists($path_to_files[$language->sef])) {
					$Content_Folder[$language->sef] = file_get_contents($path_to_files[$language->sef]);
				}
				else {
					$Content_Folder[$language->sef] = '';
				}
			}

			try {
				$params = $this->h_fabrik->prepareGroupParams();
				$params = $this->h_fabrik->updateParam($params, 'repeat_group_show_first', $repeat_group_show_first);

				$columns = array(
					'name',
					'css',
					'label',
					'published',
					'created',
					'created_by',
					'created_by_alias',
					'modified',
					'modified_by',
					'checked_out',
					'checked_out_time',
					'is_join',
					'private',
					'params');

				// Insert values.
				$values = array(
					'GROUP_' . $fid,
					'',
					'GROUP_' . $fid,
					1,
					date('Y-m-d H:i:s'),
					$user->id,
					$user->username,
					date('Y-m-d H:i:s'),
					$user->id,
					0,
					date('Y-m-d H:i:s'),
					0,
					0,
					json_encode($params)
				);

				$query->clear()
					->insert($this->db->quoteName('#__fabrik_groups'))
					->columns($this->db->quoteName($columns))
					->values(implode(',', $this->db->Quote($values)));
				$this->db->setQuery($query);
				$this->db->execute();
				$groupid = $this->db->insertid();

				if (!empty($groupid)) {
					$tag = 'GROUP_' . $fid . '_' . $groupid;
					$this->translate($tag, $label, 'fabrik_groups', $groupid, 'label', $user->id);

					$query->clear()
						->update($this->db->quoteName('#__fabrik_groups'))
						->set($this->db->quoteName('name') . ' = ' . $this->db->quote($tag))
						->set($this->db->quoteName('label') . ' = ' . $this->db->quote($tag))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($groupid));
					$this->db->setQuery($query);
					$this->db->execute();

					// INSERT FORMGROUP
					$query->clear()
						->select('*')
						->from($this->db->quoteName('#__fabrik_formgroup'))
						->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($fid))
						->order('ordering');

					$this->db->setQuery($query);
					$results = $this->db->loadObjectList();

					if (!empty($results)) {
						$orderings = [];
						foreach (array_values($results) as $result) {
							if (!in_array($result->ordering, $orderings)) {
								$orderings[] = intval($result->ordering);
							}
						}

						$order = array_values($orderings)[strval(sizeof($orderings) - 1)] + 1;
					}
					else {
						$order = 1;
					}

					$columns = array('form_id', 'group_id', 'ordering',);
					$values  = array($fid, $groupid, $order);

					$query->clear()
						->insert($this->db->quoteName('#__fabrik_formgroup'))
						->columns($this->db->quoteName($columns))
						->values(implode(',', $this->db->Quote($values)));

					$this->db->setQuery($query);
					$this->db->execute();

					$label_fr = $this->getTranslation($tag, 'fr-FR');
					$label_en = $this->getTranslation($tag, 'en-GB');

					$group = array(
						'elements'         => array(),
						'group_id'         => $groupid,
						'group_tag'        => $tag,
						'group_showLegend' => $this->getJTEXT("GROUP_" . $fid . "_" . $groupid),
						'label'            => array(
							'fr' => $label_fr,
							'en' => $label_en,
						),
						'ordering'         => $order,
						'formid'           => $fid
					);

					if($mode === 'eval') {
						require_once (JPATH_SITE . '/components/com_emundus/models/form.php');
						$m_form = new EmundusModelForm();

						$programs = $m_form->getProgramsByForm($fid, $mode);
						$codes = array_map(function($program) {
							return $program['code'];
						}, $programs);

						$m_form->associateFabrikGroupsToProgram($fid,$codes,$mode);
					}
				}
			}
			catch (Exception $e) {
				error_log($e->getMessage());
				Log::add('component/com_emundus/models/formbuilder | Error at creating a group for fabrik_form ' . $fid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $group;
	}

	function deleteGroup($group)
	{

		$query = $this->db->getQuery(true);
		try {
			$query->update($this->db->quoteName('#__fabrik_groups'))
				->set($this->db->quoteName('published') . ' = ' . 0)
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($group));
			$this->db->setQuery($query);
			$this->db->execute();

			return true;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error when move to trash the group ' . $group . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function createSectionSimpleElements($gid, $plugins, $mode = 'forms')
	{
		$created_elements = [];
		$user             = JFactory::getUser()->id;

		$evaluation = $mode === 'eval' ? 1 : 0;

		foreach ($plugins as $plugin) {
			switch ($plugin) {
				case 'birthday':

					$label = array(
						'fr' => 'Date de naissance',
						'en' => 'Birthday',
					);

					$created_elements[] = $this->createSimpleElement($gid, $plugin, null, $evaluation, $label);
					break;
				case 'date_debut':
					$label = array(
						'fr' => 'Date de début du contrat',
						'en' => 'Contract start date',
					);

					$created_elements[] = $this->createSimpleElement($gid, 'birthday', null, $evaluation, $label);
					break;
				case 'date_fin':
					$label = array(
						'fr' => 'Date de fin du contrat',
						'en' => 'Contract end date',
					);

					$created_elements[] = $this->createSimpleElement($gid, 'birthday', null, $evaluation, $label);
					break;

				case 'telephone':

					$label = array(
						'fr' => 'Téléphone',
						'en' => 'Phone',
					);

					$created_elements[] = $this->createSimpleElement($gid, 'field', null, $evaluation, $label);
					break;
				case 'fonction':

					$label = array(
						'fr' => 'Fonction',
						'en' => 'Function',
					);


					$created_elements[] = $this->createSimpleElement($gid, 'field', null, $evaluation, $label);
					break;

				case 'employeur':
					$label = array(
						'fr' => 'Employeur',
						'en' => 'Employer',
					);

					$created_elements[] = $this->createSimpleElement($gid, 'field', null, $evaluation, $label);
					break;

				case 'ville_employeur':

					$label = array(
						'fr' => "Ville de l'employeur",
						'en' => 'Employer city',
					);


					$created_elements[] = $this->createSimpleElement($gid, 'field', null, $evaluation, $label);
					break;
				case 'missions':

					$label = array(
						'fr' => 'Missions réalisées',
						'en' => 'Missions',
					);


					$created_elements[] = $this->createSimpleElement($gid, 'textarea', null, $evaluation, $label);
					break;
				case 'adresse':
					$label = array(
						'fr' => 'Adresse',
						'en' => 'Address',
					);


					$created_elements[] = $this->createSimpleElement($gid, 'field', null, $evaluation, $label);
					break;
				case 'code postal':
					$label              = array(
						'fr' => 'Code postal',
						'en' => 'postal code',
					);
					$created_elements[] = $this->createSimpleElement($gid, 'field', null, $evaluation, $label);
					break;
				case 'ville':
					$label              = array(
						'fr' => 'Ville',
						'en' => 'City',
					);
					$created_elements[] = $this->createSimpleElement($gid, 'field', null, $evaluation, $label);
					break;
				case 'adresseComplementaire':
					$label = array(
						'fr' => 'Adresse complémentaire',
						'en' => 'Additional addresd',
					);


					$created_elements[] = $this->createSimpleElement($gid, 'field', null, $evaluation, $label);
					break;

				case 'email':

					$label = array(
						'fr' => 'Adresse e-mail',
						'en' => 'E-mail address',
					);

					$created_elements[] = $this->createSimpleElement($gid, $plugin, null, $evaluation, $label);

					break;
				case 'nationalite':

					$label = array(
						'fr' => 'Nationalité',
						'en' => 'Nationality',
					);

					$el_id = $this->createSimpleElement($gid, 'databasejoin', null, $evaluation, $label);

					$created_elements[] = $el_id;
					$element            = json_decode(json_encode($this->getElement($el_id, $gid)), true);

					$element['params']["join_db_name"]            = "data_nationality";
					$element['params']["join_key_column"]         = "id";
					$element['params']["join_val_column"]         = "label_fr";
					$element['params']["database_join_where_sql"] = "order by id";

					$this->UpdateParams($element, $user);
					break;
				case 'pays':
					$label = array(
						'fr' => 'Pays',
						'en' => 'Country',
					);

					$el_id = $this->createSimpleElement($gid, 'databasejoin', null, $evaluation, $label);

					$created_elements[] = $el_id;
					$element            = json_decode(json_encode($this->getElement($el_id, $gid)), true);

					$element['params']["join_db_name"]            = "data_country";
					$element['params']["join_key_column"]         = "id";
					$element['params']["join_val_column"]         = "label_fr";
					$element['params']["database_join_where_sql"] = "order by id";

					$this->UpdateParams($element, $user);
					break;

				default:
					$created_elements[] = $this->createSimpleElement($gid, $plugin, null, $evaluation);
					break;
			}

		}


		return $created_elements;
	}

	function createElement($name, $group_id, $plugin, $label, $default = '', $hidden = 0, $create_column = 1, $show_in_list_summary = 1, $published = 1, $parent_id = 0, $width = 20, $user = null)
	{
		$query = $this->db->getQuery(true);

		if(empty($user)) {
			$user = Factory::getApplication()->getIdentity();
		}

		try {
			//Create element in fabrik_elements
			$params = $this->h_fabrik->prepareElementParameters($plugin, false);

			$data = array(
				'name'                 => $name,
				'group_id'             => $group_id,
				'plugin'               => $plugin,
				'label'                => $label,
				'checked_out_time'     => date('Y-m-d H:i:s'),
				'created'              => date('Y-m-d H:i:s'),
				'created_by'           => $user->id,
				'created_by_alias'     => $user->username,
				'modified'             => date('Y-m-d H:i:s'),
				'modified_by'          => $user->id,
				'width'                => $width,
				'default'              => $default,
				'hidden'               => $hidden,
				'eval'                 => $default === '' ? 0 : 1,
				'ordering'             => 1,
				'show_in_list_summary' => $show_in_list_summary,
				'filter_type'          => '',
				'filter_exact_match'   => 0,
				'published'            => $published,
				'access'               => 1,
				'parent_id'            => $parent_id,
				'params'               => json_encode($params),
			);

			$query->insert($this->db->quoteName('#__fabrik_elements'))
				->columns($this->db->quoteName(array_keys($data)))
				->values(implode(',', $this->db->quote(array_values($data))));
			$this->db->setQuery($query);
			$this->db->execute();
			$eid = $this->db->insertid();
			//

			$this->h_fabrik->checkFabrikJoins($eid, $name, $plugin, $group_id);

			// Create columns in database
			if ($create_column) {
				$db_type = $this->h_fabrik->getDBType($plugin);

				$query->clear()
					->select('*')
					->from($this->db->quoteName('#__fabrik_groups'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($group_id));
				$this->db->setQuery($query);
				$fabrik_group = $this->db->loadObject();
				$group_params = json_decode($fabrik_group->params);

				$query->clear()
					->select([
						'fl.db_table_name AS dbtable',
						'fl.form_id AS formid',
					])
					->from($this->db->quoteName('#__fabrik_formgroup', 'fg'))
					->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('fg.form_id'))
					->where($this->db->quoteName('fg.group_id') . ' = ' . $this->db->quote($group_id));
				$this->db->setQuery($query);
				$result = $this->db->loadObject();

				$query = "ALTER TABLE " . $result->dbtable . " ADD " . $name . " " . $db_type . " NULL";
				$this->db->setQuery($query);
				$this->db->execute();
				if ($group_params->repeat_group_button == 1 || $fabrik_group->is_join == 1) {
					$repeat_table_name = $result->dbtable . "_" . $group_id . "_repeat";
					$query             = "ALTER TABLE " . $repeat_table_name . " ADD " . $name . " " . $db_type . " NULL";
					$this->db->setQuery($query);
					$this->db->execute();
				}
			}

			return $eid;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error when create an element in group ' . $group_id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return 0;
		}
	}

	/**
	 * Returns the element id of the created element
	 * false if error
	 *
	 * @param $gid
	 * @param $plugin
	 * @param $attachementId
	 * @param $evaluation
	 * @param $labels
	 *
	 * @return false|mixed
	 */
	function createSimpleElement($gid, $plugin, $attachementId = null, $evaluation = 0, $labels = null, $user = null)
	{
		$elementId = false;

		if (!empty($gid) && !empty($plugin)) {
			if(empty($user)) {
				$user = Factory::getApplication()->getIdentity();
			}

			$query = $this->db->getQuery(true);

			try {
				$dbtype  = $this->h_fabrik->getDBType($plugin);
				$dbnull  = 'NULL';
				$default = '';
				$eval = 1;

				if ($plugin === 'display' || $plugin === 'panel') {
					$eval = 0;
					$default = 'Ajoutez du texte personnalisé pour vos candidats';
				}

				// Prepare parameters
				$params = $this->h_fabrik->prepareElementParameters($plugin);
				//

				// Prepare ordering
				$query->clear()
					->select('ordering')
					->from($this->db->quoteName('#__fabrik_elements'))
					->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($gid))
					->order('ordering');

				$this->db->setQuery($query);
				$results   = $this->db->loadColumn();
				$orderings = [];
				foreach ($results as $result) {
					if (!in_array($result, $orderings)) {
						$orderings[] = intval($result);
					}
				}
				//

				// Create our element
				$query->clear()
					->insert($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('name') . ' = ' . $this->db->quote('element'))
					->set($this->db->quoteName('group_id') . ' = ' . $this->db->quote($gid))
					->set($this->db->quoteName('plugin') . ' = ' . $this->db->quote($plugin == 'nom' || $plugin == 'prenom' || $plugin == 'email' ? 'field' : $plugin))
					->set($this->db->quoteName('label') . ' = ' . $this->db->quote(strtoupper('element_' . $gid)))
					->set($this->db->quoteName('checked_out') . ' = 0')
					->set($this->db->quoteName('checked_out_time') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
					->set($this->db->quoteName('created') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
					->set($this->db->quoteName('created_by') . ' = ' . $user->id)
					->set($this->db->quoteName('created_by_alias') . ' = ' . $this->db->quote($user->username))
					->set($this->db->quoteName('modified') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
					->set($this->db->quoteName('modified_by') . ' = ' . $user->id)
					->set($this->db->quoteName('width') . ' = 0')
					->set($this->db->quoteName('default') . ' = ' . $this->db->quote($default))
					->set($this->db->quoteName('hidden') . ' = 0')
					->set($this->db->quoteName('eval') . ' = ' . $eval)
					->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote(array_values($orderings)[strval(sizeof($orderings) - 1)] + 1))
					->set($this->db->quoteName('parent_id') . ' = 0')
					->set($this->db->quoteName('published') . ' = 1')
					->set($this->db->quoteName('access') . ' = 1')
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)));
				$this->db->setQuery($query);
				$element_inserted = $this->db->execute();

				if ($element_inserted) {
					$elementId = $this->db->insertid();

					if (!empty($elementId)) {
						$query->clear()
							->select(['fg.is_join, fg.params, fl.db_table_name AS dbtable'])
							->from($this->db->quoteName('#__fabrik_formgroup', 'ffg'))
							->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ffg.form_id'))
							->leftJoin($this->db->quoteName('#__fabrik_groups', 'fg') . ' ON ' . $this->db->quoteName('fg.id') . ' = ' . $this->db->quoteName('ffg.group_id'))
							->where($this->db->quoteName('fg.id') . ' = ' . $this->db->quote($gid));
						$this->db->setQuery($query);
						$formlist     = $this->db->loadObject();
						$group_params = json_decode($formlist->params);

						// Prepare label
						if ($labels != null) {
							$label = $labels;
						}
						else {
							$label = $this->h_fabrik->initLabel($plugin);
						}
						$this->translate('ELEMENT_' . $gid . '_' . $elementId, $label, 'fabrik_elements', $elementId, 'label', $user->id);
						if ($evaluation) {
							$name = 'criteria_' . $gid . '_' . $elementId;
						}
						else {
							$name = 'e_' . $gid . '_' . $elementId;
						}
						//

						$params['alias'] = !empty($label['fr']) ? $label['fr'] : "";
						$params['alias'] = str_replace(' ', '_', $params['alias']);
						$params['alias'] = htmlentities($params['alias'], ENT_COMPAT, "UTF-8");
						$params['alias'] = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil);/', '$1', $params['alias']);
						$params['alias'] = html_entity_decode($params['alias']);
						$params['alias'] = preg_replace('/[^a-zA-Z0-9_]/', '', $params['alias']);
						$params['alias'] = strtolower($params['alias']);

						$params['alias'] = $params['alias'] === "" ? strtolower($name) . "_alias" : $params['alias'];

						// Init a default subvalue for checkboxes
						if ($plugin === 'checkbox' || $plugin === 'radiobutton' || $plugin === 'dropdown') {
							$sub_values = [];
							$sub_labels = [];

							$sub_labels[] = strtoupper('sublabel_' . $gid . '_' . $elementId . '_0');
							if ($plugin === 'dropdown') {
								$sub_values[] = 0;
								$labels       = array(
									'fr' => 'Veuillez sélectionner',
									'en' => 'Please select'
								);
							} else {
								$sub_values[] = 1;
								$labels       = array(
									'fr' => 'Option 1',
									'en' => 'Option 1'
								);
							}
							$this->translate(strtoupper('sublabel_' . $gid . '_' . $elementId . '_0'), $labels, 'fabrik_elements', $elementId, 'sub_labels');

							$params['sub_options'] = array(
								'sub_values' => $sub_values,
								'sub_labels' => $sub_labels
							);
						}
						//

						$query->clear()
							->update($this->db->quoteName('#__fabrik_elements'))
							->set($this->db->quoteName('label') . ' = ' . $this->db->quote(strtoupper('element_' . $gid . '_' . $elementId)))
							->set($this->db->quoteName('name') . ' = ' . $this->db->quote($name))
							->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
							->where($this->db->quoteName('id') . '= ' . $this->db->quote($elementId));
						$this->db->setQuery($query);
						$this->db->execute();

						// Add element to table
						if ($evaluation) {
							$query = "ALTER TABLE " . $formlist->dbtable . " ADD criteria_" . $gid . "_" . $elementId . " " . $dbtype . " " . $dbnull;
							$this->db->setQuery($query);
							$this->db->execute();

							if ($group_params->repeat_group_button == 1 || $formlist->is_join == 1) {
								$query = $this->db->getQuery(true);
								$query->select('table_join')
									->from($this->db->quoteName('#__fabrik_joins'))
									->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($gid))
									->andWhere($this->db->quoteName('table_join_key') . '=' . $this->db->quote('parent_id'));
								$this->db->setQuery($query);
								$table_join_name = $this->db->loadObject();

								$query = "ALTER TABLE " . $table_join_name->table_join . " ADD criteria_" . $gid . "_" . $elementId . " " . $dbtype . " " . $dbnull;
								$this->db->setQuery($query);
								try {
									$this->db->execute();
								}
								catch (Exception $e) {
									Log::add('component/com_emundus/models/formbuilder | Cannot not create new colum in the repeat table case: new element form group to an target group witc at group   because column already exist ' . $gid . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
								}
							}

						}
						else {
							$query = "ALTER TABLE " . $formlist->dbtable . " ADD e_" . $gid . "_" . $elementId . " " . $dbtype . " " . $dbnull;

							try {
								$this->db->setQuery($query);
								$column_added = $this->db->execute();
							}
							catch (Exception $e) {
								Log::add('Failed to add column for element ' . $elementId . ' in group ' . $gid . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
								$column_added = false;
							}

							if (!$column_added) {
								$elementId = false;
							}
							else {
								if ($group_params->repeat_group_button == 1 || $formlist->is_join == 1) {
									$query = $this->db->getQuery(true);
									$query->select('table_join')
										->from('#__fabrik_joins')
										->where('join_from_table = ' . $this->db->quote($formlist->dbtable))
										->andWhere('group_id = ' . $gid);
									$this->db->setQuery($query);

									$repeat_table_name = $this->db->loadResult();

									if (empty($repeat_table_name)) {
										$repeat_table_name = $formlist->dbtable . "_" . $gid . "_repeat";
									}

									$query = "ALTER TABLE " . $repeat_table_name . " ADD e_" . $gid . "_" . $elementId . " " . $dbtype . " " . $dbnull;
									try {
										$this->db->setQuery($query);
										$this->db->execute();
									}
									catch (Exception $e) {
										Log::add('component/com_emundus/models/formbuilder | Failed to alter table for ' . $repeat_table_name . $gid . '_' . $elementId . ' ' . $dbtype . ' ' . $dbnull . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus');
									}
								}

								$this->h_fabrik->addJsAction($elementId, $plugin);

								Log::add($user->id . ' Element ' . $elementId . ' created in group ' . $gid, Log::INFO, 'com_emundus.formbuilder');
							}
						}
					}
					else {
						Log::add($user->id . ' could not find new element id, for type ' . $plugin . ' group ' . $gid, Log::WARNING, 'com_emundus.formbuilder');
					}
				}
				else {
					Log::add($user->id . ' element insertion failed, type ' . $plugin . ' group ' . $gid, Log::WARNING, 'com_emundus.formbuilder');
				}
			}
			catch (Exception $e) {
				$query_str = is_string($query) ? $query : $query->__toString();
				Log::add('Problem when create a simple element in the group ' . $gid . ' : ' . preg_replace("/[\r\n]/", " ", $query_str . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.formbuilder');
				$elementId = false;
			}
		}

		return $elementId;
	}

	function updateGroupElementsOrder($elements, $group_id)
	{
		$updated = false;

		if (!empty($elements) && !empty($group_id)) {

			$query = $this->db->getQuery(true);

			$elements_ids = [];
			$case         = [];
			foreach ($elements as $element) {
				$case[]         = 'when id = ' . $element['id'] . ' then ' . $element['order'];
				$elements_ids[] = $element['id'];
			}

			$query->update('jos_fabrik_elements')
				->set('ordering = (case ' . join(' ', $case) . ' end)')
				->set('modified = ' . $this->db->quote(date('Y-m-d H:i:s')))
				->set('modified_by = ' . $this->db->quote(JFactory::getUser()->id))
				->set('group_id = ' . $group_id)
				->where('id IN (' . join(',', $elements_ids) . ')');

			try {
				$this->db->setQuery($query);
				$updated = $this->db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/formbuilder | Cannot reorder elements : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $updated;
	}

	/**
	 * Update orders of a group's elements
	 *
	 * @param $elements
	 * @param $group_id
	 * @param $user
	 *
	 * @return array|string
	 */
	function updateOrder($elements, $group_id, $user, $moved_el = null)
	{
		$updated = false;

		if ($moved_el != null) {
			if ($moved_el['group_id'] == $group_id) {
				$updated = $this->updateGroupElementsOrder($elements, $group_id);
			}
			else {

				// groupe cible different du groupe de provenance
				// on vérifie si le groupe cible est un groupe repeat


				$query = $this->db->getQuery(true);

				$query->select('params')
					->from($this->db->quoteName('#__fabrik_groups'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($group_id));
				$this->db->setQuery($query);
				$group_cible_params = json_decode(($this->db->loadObject())->params);

				if ($group_cible_params->repeat_group_button == 1) {
					//le groupe cible est un groupe répétable
					//alors on crée la colone correspondante à l'element dans la table repetable;
					$query->clear();
					$query->select('table_join')
						->from($this->db->quoteName('#__fabrik_joins'))
						->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($group_id))
						->andWhere($this->db->quoteName('table_join_key') . '=' . $this->db->quote('parent_id'));
					$this->db->setQuery($query);
					$table_join_name = $this->db->loadObject();


					// on recupere la form_id
					$query->clear()
						->select('fl.form_id as formid')
						->from($this->db->quoteName('#__fabrik_formgroup', 'fg'))
						->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('fg.form_id'))
						->where($this->db->quoteName('fg.group_id') . ' = ' . $this->db->quote($group_id));
					$this->db->setQuery($query);
					$object = $this->db->loadObject();

					$form_id = $object->formid;

					if ($moved_el['plugin'] === 'birthday') {
						$dbtype = 'DATE';
					}
					elseif ($moved_el['plugin'] === 'textarea') {
						$dbtype = 'TEXT';
					}
					else {
						$dbtype = 'TEXT';
					}

					// on crée maintenant la colonne donc;
					$this->db->setQuery("ALTER TABLE " . $table_join_name->table_join . " ADD " . $moved_el['name'] . " " . $dbtype . " NULL");

					try {
						$this->db->execute();
					}
					catch (Exception $e) {
						Log::add('component/com_emundus/models/formbuilder | Cannot not create new colum in the repeat table case: moving element form group to an target group witch is repeat group because column already exist ' . $group_id . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
					}
				}

				// Maintenant j'update enfin les ordres
				$updated = $this->updateGroupElementsOrder($elements, $group_id);
			}
		}
		else {
			$updated = $this->updateGroupElementsOrder($elements, $group_id);
		}

		return $updated;
	}

	function updateElementOrder($group_id, $element_id, $new_index)
	{
		// get elements from group_id
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId($group_id);
		$elements       = $groupModel->getMyElements();
		$elements_order = array();

		foreach ($elements as $key => $element) {
			if ($element->element->id == $element_id) {
				$elements_order[] = [
					'id'    => $element->element->id,
					'order' => intval($new_index),
				];
			}
			else {
				$elements_order[] = [
					'id'    => $element->element->id,
					'order' => $element->element->ordering,
				];
			}
		}

		// sort elements by order
		usort($elements_order, function ($a, $b) {
			return $a['order'] - $b['order'];
		});

		$after_element_id = false;
		foreach ($elements_order as $key => $element) {
			if ($element_id == $element['id']) {
				$after_element_id = true;
			}

			if ($after_element_id && $element['order'] == $elements_order[$key - 1]['order']) {
				$elements_order[$key]['order'] = $elements_order[$key - 1]['order'] + 1;
			}
		}

		return $this->updateGroupElementsOrder($elements_order, $group_id);
	}

	function ChangeRequire($element, $user)
	{
		if (empty($user)) {
			$user = JFactory::getUser()->id;
		}


		$query = $this->db->getQuery(true);

		$date = new Date();
		$eval = 0;

		$query->select([
			'el.name AS name',
			'fl.db_table_name AS dbtable',
			'el.params AS params'
		])
			->from($this->db->quoteName('#__fabrik_elements', 'el'))
			->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'fg') . ' ON ' . $this->db->quoteName('fg.group_id') . ' = ' . $this->db->quoteName('el.group_id'))
			->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('fg.form_id'))
			->where($this->db->quoteName('el.id') . ' = ' . $this->db->quote($element['id']));
		$this->db->setQuery($query);
		$db_element = $this->db->loadObject();
		$old_params = json_decode($db_element->params, true);

		if ($element['FRequire'] === 'true') {
			$old_params['validations']['plugin'][]           = "notempty";
			$old_params['validations']['plugin_published'][] = "1";
			$old_params['validations']['validate_in'][]      = "both";
			$old_params['validations']['validation_on'][]    = "both";
			$old_params['validations']['validate_hidden'][]  = "0";
			$old_params['validations']['must_validate'][]    = "0";
			$old_params['validations']['show_icon'][]        = "1";
			$old_params['notempty-message']                  = array("");
			$old_params['notempty-validation_condition']     = array("");
			$eval                                            = 1;
		}
		else {
			$key = false;
			if(is_array($old_params['validations']['plugin'])) {
            	$key = array_search("notempty",$old_params['validations']['plugin']);
			}
			unset($old_params['validations']['plugin'][$key]);
			unset($old_params['validations']['plugin_published'][$key]);
			unset($old_params['validations']['validate_in'][$key]);
			unset($old_params['validations']['validation_on'][$key]);
			unset($old_params['validations']['validate_hidden'][$key]);
			unset($old_params['validations']['must_validate'][$key]);
			unset($old_params['validations']['show_icon'][$key]);
			unset($old_params['notempty-message']);
			unset($old_params['notempty-validation_condition']);
			$old_params['validations']['plugin']           = array_values($old_params['validations']['plugin']);
			$old_params['validations']['plugin_published'] = array_values($old_params['validations']['plugin_published']);
			$old_params['validations']['validate_in']      = array_values($old_params['validations']['validate_in']);
			$old_params['validations']['validation_on']    = array_values($old_params['validations']['validation_on']);
			$old_params['validations']['validate_hidden']  = array_values($old_params['validations']['validate_hidden']);
			$old_params['validations']['must_validate']    = array_values($old_params['validations']['must_validate']);
			$old_params['validations']['show_icon']        = array_values($old_params['validations']['show_icon']);
		}

		$fields = array(
			$this->db->quoteName('eval') . ' = ' . $this->db->quote($eval),
			$this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($old_params)),
			$this->db->quoteName('modified_by') . ' = ' . $this->db->quote($user),
			$this->db->quoteName('modified') . ' = ' . $this->db->quote($date),
		);
		$query->clear()
			->update($this->db->quoteName('#__fabrik_elements'))
			->set($fields)
			->where($this->db->quoteName('id') . '  =' . $element['id']);

		try {
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Problem when change require of the element ' . $element['id'] . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}


	function UpdateParams($element, $user)
	{
		if (empty($user)) {
			$user = JFactory::getUser()->id;
		}
		$date = new Date();


		$query = $this->db->getQuery(true);

		// Get old element
		$query->select([
			'el.name AS name',
			'el.plugin AS plugin',
			'el.default as default_text',
			'fl.db_table_name AS dbtable',
			'el.params AS params'
		])
			->from($this->db->quoteName('#__fabrik_elements', 'el'))
			->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'fg') . ' ON ' . $this->db->quoteName('fg.group_id') . ' = ' . $this->db->quoteName('el.group_id'))
			->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('fg.form_id'))
			->where($this->db->quoteName('el.id') . ' = ' . $this->db->quote($element['id']));

		try {
			$this->db->setQuery($query);
			$db_element = $this->db->loadObject();

			$key = false;
			if(is_array($element['params']['validations']['plugin'])) {
            	$key = array_search("notempty", $element['params']['validations']['plugin']);
			}
			if ($element['FRequire'] != "true") {
				if ($key !== false && $key !== null) {
					unset($element['params']['validations']['plugin'][$key]);
					unset($element['params']['validations']['plugin_published'][$key]);
					unset($element['params']['validations']['validate_in'][$key]);
					unset($element['params']['validations']['validation_on'][$key]);
					unset($element['params']['validations']['validate_hidden'][$key]);
					unset($element['params']['validations']['must_validate'][$key]);
					unset($element['params']['validations']['show_icon'][$key]);

					$isemail_key = array_search("isemail", $element['params']['validations']['plugin']);
					if($isemail_key !== false && $isemail_key !== null) {
						unset($element['params']['isemail-message'][$key]);
						unset($element['params']['isemail-validation_condition'][$key]);
						unset($element['params']['isemail-allow_empty'][$key]);
						unset($element['params']['isemail-check_mx'][$key]);
						$element['params']['isemail-message'] = array_values($element['params']['isemail-message']);
						$element['params']['isemail-validation_condition'] = array_values($element['params']['isemail-validation_condition']);
						$element['params']['isemail-allow_empty'] = array_values($element['params']['isemail-allow_empty']);
						$element['params']['isemail-check_mx'] = array_values($element['params']['isemail-check_mx']);
					}

					// Reindex validations
					$element['params']['validations']['plugin'] = array_values($element['params']['validations']['plugin']);
					$element['params']['validations']['plugin_published'] = array_values($element['params']['validations']['plugin_published']);
					$element['params']['validations']['validate_in'] = array_values($element['params']['validations']['validate_in']);
					$element['params']['validations']['validation_on'] = array_values($element['params']['validations']['validation_on']);
					$element['params']['validations']['validate_hidden'] = array_values($element['params']['validations']['validate_hidden']);
					$element['params']['validations']['must_validate'] = array_values($element['params']['validations']['must_validate']);
					$element['params']['validations']['show_icon'] = array_values($element['params']['validations']['show_icon']);
				}
			}
			else {
				if ($key === false || $key === null) {
					$element['params']['validations']['plugin'][]           = "notempty";
					$element['params']['validations']['plugin_published'][] = "1";
					$element['params']['validations']['validate_in'][]      = "both";
					$element['params']['validations']['validation_on'][]    = "both";
					$element['params']['validations']['validate_hidden'][]  = "0";
					$element['params']['validations']['must_validate'][]    = "0";
					$element['params']['validations']['show_icon'][]        = "1";

					$isemail_key = array_search("isemail", $element['params']['validations']['plugin']);
					if($isemail_key !== false && $isemail_key !== null) {
						$element['params']['isemail-message'][] = "";
						$element['params']['isemail-validation_condition'][] = "";
						$element['params']['isemail-allow_empty'][] = "";
						$element['params']['isemail-check_mx'][] = "";
					}
				}
			}


			// Filter by plugin
			if ($element['plugin'] === 'checkbox' || $element['plugin'] === 'radiobutton' || $element['plugin'] === 'dropdown' || $element['plugin'] === 'databasejoin') {
				$old_params = json_decode($db_element->params, true);

				if (isset($element['params']['join_db_name'])) {
					$query->clear()
						->select('*')
						->from($this->db->quoteName('#__fabrik_joins'))
						->where($this->db->quoteName('element_id') . ' = ' . $element['id']);
					$this->db->setQuery($query);
					$fabrik_join = $this->db->loadObject();

					if (!empty($fabrik_join)) {
						$join_params                 = json_decode($fabrik_join->params);
						$join_params->{'join-label'} = $element['params']['join_val_column'];
						$join_params->pk             = $this->db->quoteName($element['params']['join_db_name']) . '.' . $this->db->quoteName($element['params']['join_key_column']);

						$fields = array(
							$this->db->quoteName('table_join_key') . ' = ' . $this->db->quote($element['params']['join_key_column']),
							$this->db->quoteName('table_join') . ' = ' . $this->db->quote($element['params']['join_db_name']),
							$this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($join_params)),
						);
						$query->clear()
							->update($this->db->quoteName('#__fabrik_joins'))
							->set($fields)
							->where($this->db->quoteName('id') . ' = ' . $this->db->quote($fabrik_join->id));
						$this->db->setQuery($query);
						$this->db->execute();
					}

					if ($element['params']['database_join_show_please_select'] == 1) {
						$element['params']['database_join_noselectionlabel'] = 'PLEASE_SELECT';
					}

					if(!empty($element['params']['database_join_exclude'])) {
                        preg_match_all("/\bORDER BY\b(.*)/i", $element['params']['database_join_where_sql'], $order_by, PREG_SET_ORDER, 0);
						if(!empty($order_by)) {
							$order_by = $order_by[0][0];
						}
						$element['params']['database_join_where_sql'] = str_replace($order_by, '', $element['params']['database_join_where_sql']);

						$ids_to_exclude = [];
						if(is_array($element['params']['database_join_exclude'])) {
							foreach ($element['params']['database_join_exclude'] as $exclude) {
								$ids_to_exclude[] = $exclude['value'];
							}
						} else {
							$ids_to_exclude = explode(',', $element['params']['database_join_exclude']);
							// Remove quotes
							foreach ($ids_to_exclude as $key => $id) {
								$ids_to_exclude[$key] = str_replace("'", '', $id);
							}
						}

                        if(stripos($element['params']['database_join_where_sql'], 'WHERE') !== false)
						{
                            $element['params']['database_join_where_sql'] = preg_replace(
                                [
                                    // Case 1: WHERE {thistable}.id NOT IN (...) AND ...
                                    '/\bWHERE\s+{thistable}\.id\s+NOT\s+IN\s*\([^)]*\)\s+AND\s+/i',

                                    // Case 2: WHERE {thistable}.id NOT IN (...) only (no other conditions)
                                    '/\bWHERE\s+{thistable}\.id\s+NOT\s+IN\s*\([^)]*\)\s*(?=ORDER BY|\Z)/i',

                                    // Case 3: AND {thistable}.id NOT IN (...) somewhere later in the WHERE clause
                                    '/\s+AND\s+{thistable}\.id\s+NOT\s+IN\s*\([^)]*\)/i'
                                ],
                                [
                                    'WHERE ', // Case 1: remove the condition but keep WHERE
                                    '',       // Case 2: remove entirely
                                    ''        // Case 3: remove entirely
                                ],
                                $element['params']['database_join_where_sql']
                            );
							if(empty($element['params']['database_join_where_sql'])) {
								$element['params']['database_join_where_sql'] = 'WHERE {thistable}.' . $element['params']['join_key_column'] . ' NOT IN (' . implode(',',$this->db->quote($ids_to_exclude)) . ')';
							} else {
								$element['params']['database_join_where_sql'] .= 'AND {thistable}.' . $element['params']['join_key_column'] . ' NOT IN (' . implode(',',$this->db->quote($ids_to_exclude)) . ')';
							}
						} else {
							$element['params']['database_join_where_sql'] .= 'WHERE {thistable}.' . $element['params']['join_key_column'] . ' NOT IN (' . implode(',',$this->db->quote($ids_to_exclude)) . ')';
						}
					} else {
                        $element['params']['database_join_where_sql'] = preg_replace(
                            [
                                // Case 1: WHERE {thistable}.id NOT IN (...) AND ...
                                '/\bWHERE\s+{thistable}\.id\s+NOT\s+IN\s*\([^)]*\)\s+AND\s+/i',

                                // Case 2: WHERE {thistable}.id NOT IN (...) only (no other conditions)
                                '/\bWHERE\s+{thistable}\.id\s+NOT\s+IN\s*\([^)]*\)\s*(?=ORDER BY|\Z)/i',

                                // Case 3: AND {thistable}.id NOT IN (...) somewhere later in the WHERE clause
                                '/\s+AND\s+{thistable}\.id\s+NOT\s+IN\s*\([^)]*\)/i'
                            ],
                            [
                                'WHERE ', // Case 1: remove the condition but keep WHERE
                                '',       // Case 2: remove entirely
                                ''        // Case 3: remove entirely
                            ],
                            $element['params']['database_join_where_sql']
                        );
					}

					// If table have a published column add it to where
					$query->clear()
						->select('COLUMN_NAME')
						->from('INFORMATION_SCHEMA.COLUMNS')
						->where('TABLE_NAME = ' . $this->db->quote($element['params']['join_db_name']))
						->where('COLUMN_NAME = ' . $this->db->quote('published'));
					$this->db->setQuery($query);
					$published_column = $this->db->loadResult();

					if(!empty($published_column)) {
						// Check if the column is already in the where clause
						if(stripos($element['params']['database_join_where_sql'], 'published') !== false) {
							$element['params']['database_join_where_sql'] = preg_replace('/\bWHERE\b(.*)\b{thistable}\.published\b(.*)/i', '', $element['params']['database_join_where_sql']);
						}
						else
						{
							// If we have a order by clause, we need to remove it and add it at the end
							$order_by = '';
							if (stripos($element['params']['database_join_where_sql'], 'ORDER BY') !== false)
							{
								preg_match_all("/\bORDER BY\b(.*)/i", $element['params']['database_join_where_sql'], $order_by, PREG_SET_ORDER, 0);
								if (!empty($order_by))
								{
									$order_by = $order_by[0][0];
								}
								$element['params']['database_join_where_sql'] = str_replace($order_by, '', $element['params']['database_join_where_sql']);
							}
							// Now we can add the published column to the where clause
							if (stripos($element['params']['database_join_where_sql'], 'WHERE') !== false)
							{
								$element['params']['database_join_where_sql'] .= ' AND {thistable}.published = 1';
							}
							else
							{
								$element['params']['database_join_where_sql'] .= 'WHERE {thistable}.published = 1';
							}
						}
					} else {
						// If table have a published column remove it from where
						$element['params']['database_join_where_sql'] = preg_replace('/WHERE\s+\{thistable\}\.published\s*=\s*1\s*/i', '', $element['params']['database_join_where_sql']);
					}

					// If $element['params']['database_join_where_sql'] start by AND or OR, remove it
					$element['params']['database_join_where_sql'] = preg_replace('/^AND\s+/i', 'WHERE ', $element['params']['database_join_where_sql']);

					$order_by_column = !empty($element['params']['join_val_column']) ? '{thistable}.'.$element['params']['join_val_column'] : '{thistable}.'.$element['params']['join_key_column'];
                    $order_by_column = !empty($element['params']['join_val_column_concat']) ? $element['params']['join_val_column_concat'] : $order_by_column;
					if(stripos($element['params']['database_join_where_sql'], 'ORDER BY') !== false)
					{
						preg_replace('/\bORDER BY\b(.*)/i', 'ORDER BY ' . $order_by_column, $element['params']['database_join_where_sql']);
					} else {
						if(!empty($element['params']['database_join_where_sql']))
						{
							$element['params']['database_join_where_sql'] .= ' ORDER BY ' . $order_by_column;
						} else {
							$element['params']['database_join_where_sql'] .= 'ORDER BY ' . $order_by_column;
						}
					}
				}
				else {
					$sub_values            = $old_params['sub_options']['sub_values'];
					$sub_labels            = $old_params['sub_options']['sub_labels'];
					$sub_initial_selection = [];

					if ($element['params']['default_value'] == 1) {
						if (!array_search('PLEASE_SELECT', $old_params['sub_options']['sub_labels'])) {
							$sub_labels[]            = 'PLEASE_SELECT';
							$sub_values[]            = '';
							$sub_initial_selection[] = '';
						}
						else {
							$sub_initial_selection[0] = '';
						}
					}

					$element['params']['sub_options'] = array(
						'sub_values'            => $sub_values,
						'sub_labels'            => $sub_labels,
						'sub_initial_selection' => $sub_initial_selection,
					);
				}
			}

			if ($element['plugin'] === 'field') {
				$key = false;
				if(is_array($element['params']['validations']['plugin'])) {
                	$key = array_search("isemail", $element['params']['validations']['plugin']);
				}

				if ($element['params']['password'] == 3) {
					if ($key === false || $key === null) {
						$element['params']['isemail-message']                   = array("");
						$element['params']['isemail-validation_condition']      = array("");
						$element['params']['isemail-allow_empty']               = array("1");
						$element['params']['isemail-check_mx']                  = array("0");
						$element['params']['validations']['plugin'][]           = "isemail";
						$element['params']['validations']['plugin_published'][] = "1";
						$element['params']['validations']['validate_in'][]      = "both";
						$element['params']['validations']['validation_on'][]    = "both";
						$element['params']['validations']['validate_hidden'][]  = "0";
						$element['params']['validations']['must_validate'][]    = "0";
						$element['params']['validations']['show_icon'][]        = "0";
					}
				}
				else {
					$key = false;
					if(is_array($element['params']['validations']['plugin'])) {
                    	$key = array_search("isemail", $element['params']['validations']['plugin']);
					}
					if ($key !== false && $key !== null) {
						unset($element['params']['validations']['plugin'][$key]);
						unset($element['params']['validations']['plugin_published'][$key]);
						unset($element['params']['validations']['validate_in'][$key]);
						unset($element['params']['validations']['validation_on'][$key]);
						unset($element['params']['validations']['validate_hidden'][$key]);
						unset($element['params']['validations']['must_validate'][$key]);
						unset($element['params']['validations']['show_icon'][$key]);
						unset($element['params']['isemail-message']);
						unset($element['params']['isemail-validation_condition']);
						unset($element['params']['isemail-allow_empty']);
						unset($element['params']['isemail-check_mx']);

						// Reindex validations
						$element['params']['validations']['plugin'] = array_values($element['params']['validations']['plugin']);
						$element['params']['validations']['plugin_published'] = array_values($element['params']['validations']['plugin_published']);
						$element['params']['validations']['validate_in'] = array_values($element['params']['validations']['validate_in']);
						$element['params']['validations']['validation_on'] = array_values($element['params']['validations']['validation_on']);
						$element['params']['validations']['validate_hidden'] = array_values($element['params']['validations']['validate_hidden']);
						$element['params']['validations']['must_validate'] = array_values($element['params']['validations']['must_validate']);
						$element['params']['validations']['show_icon'] = array_values($element['params']['validations']['show_icon']);
					}
				}
			}

			if ($element['plugin'] === 'average') {
				$element['params']['average_multiple_elements'] = json_encode($element['params']['average_multiple_elements']);
			}

			if(($element['params']['alias'] === "" || $element['params']['alias'] === "element_sans_titre") && isset($element['label']['fr'])){
				$element['params']['alias'] =  $element['label']['fr'];
			}
			$element['params']['alias'] = str_replace(' ', '_', $element['params']['alias']);
			$element['params']['alias'] = htmlentities($element['params']['alias'], ENT_COMPAT, "UTF-8");
			$element['params']['alias'] = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil);/', '$1', $element['params']['alias']);
			$element['params']['alias'] = html_entity_decode($element['params']['alias']);
			$element['params']['alias'] = preg_replace('/[^\x20-\x7E]/','', $element['params']['alias']);
			$element['params']['alias'] = preg_replace('/[^a-zA-Z0-9_]/', '', $element['params']['alias']);
			$element['params']['alias'] = strtolower($element['params']['alias']);

			$element['params']['alias'] = $element['params']['alias'] === "" ? (!empty($element['name']) ? strtolower($element['name']) . "_alias" : "alias") :  $element['params']['alias'];

			// Update the element
			$fields = array(
				$this->db->quoteName('plugin') . ' = ' . $this->db->quote($element['plugin']),
				$this->db->quoteName('default') . ' = ' . $this->db->quote($element['default']),
				$this->db->quoteName('eval') . ' = ' . $this->db->quote($element['eval']),
				$this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($element['params'])),
				$this->db->quoteName('modified_by') . ' = ' . $this->db->quote($user),
				$this->db->quoteName('modified') . ' = ' . $this->db->quote($date),
			);
			$query->clear()
				->update($this->db->quoteName('#__fabrik_elements'))
				->set($fields)
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($element['id']));
			//
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			if (gettype($query) == 'string') {
				Log::add('component/com_emundus/models/formbuilder | Error at updating the element ' . $element['id'] . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
			else {
				Log::add('component/com_emundus/models/formbuilder | Error at updating the element ' . $element['id'] . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}

			return false;
		}
	}

	function updateGroupParams($group_id, $params, $lang = null)
	{
		$updated = false;

		$query   = $this->db->getQuery(true);

		// Get old params
		$query->select('params')
			->from($this->db->quoteName('#__fabrik_groups'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($group_id));
		$this->db->setQuery($query);

		try {
			$group_params = json_decode($this->db->loadResult(), true);
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at getting group params : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}

		if ($group_params['repeat_group_button'] == 1 && (isset($params['repeat_group_button']) && $params['repeat_group_button'] == 0)) {
			$this->disableRepeatGroup($group_id);
		}
		if ($group_params['repeat_group_button'] == 0 && (isset($params['repeat_group_button']) && $params['repeat_group_button'] == 1)) {
			$this->enableRepeatGroup($group_id);
		}

		if (!empty($params['intro'])) {
			$stripped_intro = strip_tags($group_params['intro']);
			if ($stripped_intro != Text::_($stripped_intro)) {
				$new_intro = $params['intro'];
				$intro_tag = trim($stripped_intro);

				$new_key = $this->updateTranslation($intro_tag, [$lang => $new_intro], 'fabrik_groups', $group_id, 'intro');
				if ($new_key) {
					$updated = true;
				}
				unset($params['intro']);
			}
			elseif (empty(trim($stripped_intro))) {
				$form_id         = $this->getFormId($group_id);
				$new_tag         = 'FORM_' . $form_id . '_GROUP_' . $group_id . '_INTRO';
				$new_key         = $this->translate($new_tag, [$lang => $params['intro']], 'fabrik_groups', $group_id, 'intro');
				$params['intro'] = $new_key;
			}
		}

		if (!empty($params['outro'])) {
			$stripped_outro = strip_tags($group_params['outro']);

			if ($stripped_outro != Text::_($stripped_outro)) {
				$new_outro = $params['outro'];
				$outro_tag = trim($stripped_outro);

				$new_key = $this->updateTranslation($outro_tag, [$lang => $new_outro], 'fabrik_groups', $group_id, 'intro');
				if ($new_key) {
					$updated = true;
				}
				unset($params['outro']);
			}
			elseif (empty(trim($stripped_outro))) {
				$form_id         = $this->getFormId($group_id);
				$new_tag         = 'FORM_' . $form_id . '_GROUP_' . $group_id . '_OUTRO';
				$new_key         = $this->translate($new_tag, [$lang => $params['outro']], 'fabrik_groups', $group_id, 'outro');
				$params['outro'] = $new_key;
			}
		}

		if (!empty($params)) {
			if (!empty($group_params)) {
				foreach ($params as $param => $value) {
					$group_params[$param] = $value;
				}
			}

			$query->clear()
				->update('#__fabrik_groups')
				->set('params = ' . $this->db->quote(json_encode($group_params)))
				->where('id = ' . $this->db->quote($group_id));

			try {
				$this->db->setQuery($query);
				$updated = $this->db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/formbuilder | Error at updating group params : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
				$updated = false;
			}
		}

		return $updated;
	}

	function duplicateElement($eid, $group, $old_group, $form_id)
	{

		$query = $this->db->getQuery(true);

		// Prepare Fabrik API
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');
		$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$groupModel->setId(intval($old_group));
		$elements = $groupModel->getMyElements();
		//

		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__fabrik_groups'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($group));
		$this->db->setQuery($query);
		$new_group = $this->db->loadObject();

		$new_group_params = json_decode($new_group->params);

		// Prepare languages
		$path_to_file   = basename(__FILE__) . '/../language/overrides/';
		$path_to_files  = array();
		$Content_Folder = array();
		$languages      = JLanguageHelper::getLanguages();
		foreach ($languages as $language) {
			$path_to_files[$language->sef]  = $path_to_file . $language->lang_code . '.override.ini';
			$Content_Folder[$language->sef] = file_get_contents($path_to_files[$language->sef]);
		}

		try {
			foreach ($elements as $element) {
				if ($element->element->id == $eid) {
					$dbtype = 'TEXT';

					$newelement   = $element->copyRow($element->element->id, 'Copy of %s', intval($group), 'e_' . $form_id . '_tmp');
					$newelementid = $newelement->id;

					$el_params = json_decode($element->element->params);

					// Update translation files
					if (($element->element->plugin === 'checkbox' || $element->element->plugin === 'radiobutton' || $element->element->plugin === 'dropdown') && $el_params->sub_options) {
						$sub_labels = [];
						foreach ($el_params->sub_options->sub_labels as $index => $sub_label) {
							$labels_to_duplicate = array(
								'fr' => $this->getTranslation($sub_label, 'fr-FR'),
								'en' => $this->getTranslation($sub_label, 'en-GB')
							);
							if ($labels_to_duplicate['fr'] == false && $labels_to_duplicate['en'] == false) {
								$labels_to_duplicate = array(
									'fr' => $sub_label,
									'en' => $sub_label
								);
							}
							$this->translate('SUBLABEL_' . $group . '_' . $newelementid . '_' . $index, $labels_to_duplicate, 'fabrik_elements', $newelementid, 'sub_labels');
							$sub_labels[] = 'SUBLABEL_' . $group . '_' . $newelementid . '_' . $index;
						}
						$el_params->sub_options->sub_labels = $sub_labels;
					}
					$query->clear();
					$query->update($this->db->quoteName('#__fabrik_elements'));

					$labels_to_duplicate = array(
						'fr' => $this->getTranslation($element->element->label, 'fr-FR'),
						'en' => $this->getTranslation($element->element->label, 'en-GB')
					);
					if ($labels_to_duplicate['fr'] == false && $labels_to_duplicate['en'] == false) {
						$labels_to_duplicate = array(
							'fr' => $element->element->label,
							'en' => $element->element->label
						);
					}
					$this->translate('ELEMENT_' . $group . '_' . $newelementid, $labels_to_duplicate, 'fabrik_elements', $newelementid, 'label');
					//

					$query->set('label = ' . $this->db->quote('ELEMENT_' . $group . '_' . $newelementid));
					$query->set('name = ' . $this->db->quote('e_' . $form_id . '_' . $newelementid));
					$query->set('published = 1');
					$query->set('params = ' . $this->db->quote(json_encode($el_params)));
					$query->where('id =' . $newelementid);
					$this->db->setQuery($query);
					$this->db->execute();

					$query
						->clear()
						->select([
							'fl.db_table_name AS dbtable',
						])
						->from($this->db->quoteName('#__fabrik_lists', 'fl'))
						->where($this->db->quoteName('fl.form_id') . ' = ' . $this->db->quote($form_id));
					$this->db->setQuery($query);
					$dbtable = $this->db->loadObject()->dbtable;

					if ($element->element->plugin === 'birthday') {
						$dbtype = 'DATE';
					}
					elseif ($element->element->plugin === 'textarea') {
						$dbtype = 'TEXT';
					}

					$query = "ALTER TABLE " . $dbtable . " ADD e_" . $form_id . "_" . $newelementid . " " . $dbtype . " NULL";
					$this->db->setQuery($query);
					$this->db->execute();

					if ($new_group_params->repeat_group_button == 1) {
						$repeat_table_name = $dbtable . "_" . $group . "_repeat";
						$query             = "ALTER TABLE " . $repeat_table_name . " ADD e_" . $form_id . "_" . $newelementid . " " . $dbtype . " NULL";
						$this->db->setQuery($query);
						$this->db->execute();
					}

					return $newelementid;
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/formbuilder | Cannot duplicate the element ' . $eid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

	    return false;
    }

	/**
	 * Return an element with fabrik parameters
	 *
	 * @param $element
	 * @param $gid
	 *
	 * @return mixed
	 */
	function getElement($element, $gid)
	{
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');
		$group = JModelLegacy::getInstance('Group', 'FabrikFEModel');
		$group->setId(intval($gid));
		$elements = $group->getMyElements();

		// Prepare languages
		$lang           = JFactory::getLanguage();
		$actualLanguage = substr($lang->getTag(), 0, 2);

		$path_to_file   = basename(__FILE__) . '/../language/overrides/';
		$path_to_files  = array();
		$Content_Folder = array();
		$languages      = JLanguageHelper::getLanguages();
		foreach ($languages as $language) {
			$path_to_files[$language->sef]  = $path_to_file . $language->lang_code . '.override.ini';
			$Content_Folder[$language->sef] = file_get_contents($path_to_files[$language->sef]);
		}

		${"element" . $element} = new stdClass();

		foreach ($elements as $group_elt) {
			if ($group_elt->element->id == $element) {
				$o_element       = $group_elt->element;
				$el_params       = json_decode($o_element->params);
				$content_element = $group_elt->preRender('0', '1', 'bootstrap');

				$labelsAbove = $content_element->labels;

				${"element" . $o_element->id}->id       = $o_element->id;
				${"element" . $o_element->id}->name     = $o_element->name;
				${"element" . $o_element->id}->group_id = $gid;

				${"element" . $o_element->id}->hidden      = $content_element->hidden;
				${"element" . $o_element->id}->default     = $o_element->default;
				${"element" . $o_element->id}->labelsAbove = $labelsAbove;
				${"element" . $o_element->id}->plugin      = $o_element->plugin;
				if (empty($el_params->validations)) {
					$FRequire = false;
				}
				else {
					if (isset($el_params->validations->plugin)) {
						if (empty($el_params->validations->plugin) || !in_array('notempty', $el_params->validations->plugin)) {
							$FRequire = false;
						}
						else {
							$FRequire = true;
						}
					}
				}

				if ($el_params->sub_options) {
					foreach ($el_params->sub_options->sub_labels as $key => $sub_label) {
						$el_params->sub_options->sub_labels[$key] = $this->getTranslation($sub_label, 'fr-FR');
					}
				}

				${"element" . $o_element->id}->FRequire  = $FRequire;
				${"element" . $o_element->id}->params    = $el_params;
				${"element" . $o_element->id}->label_tag = $o_element->label;
				${"element" . $o_element->id}->label     = new stdClass;
				${"element" . $o_element->id}->label->fr = $this->getTranslation(${"element" . $o_element->id}->label_tag, 'fr-FR');
				${"element" . $o_element->id}->label->en = $this->getTranslation(${"element" . $o_element->id}->label_tag, 'en-GB');
				if (${"element" . $o_element->id}->label->fr === false) {
					${"element" . $o_element->id}->label->fr = $o_element->label;
				}
				if (${"element" . $o_element->id}->label->en === false) {
					${"element" . $o_element->id}->label->en = $o_element->label;
				}
				${"element" . $o_element->id}->labelToFind = $group_elt->label;
				${"element" . $o_element->id}->publish     = $group_elt->isPublished();


				if ($labelsAbove == 2) {
					if ($el_params->tipLocation == 'above') :
						${"element" . $o_element->id}->tipAbove = $content_element->tipAbove;
					endif;
					///// ici
					if ($content_element->element) :
						if (in_array($o_element->plugin,['date','jdate'])) {
							${"element" . $o_element->id}->element = '<input data-v-8d3bb2fa="" class="form-control" type="date">';
						}
						else {
							${"element" . $o_element->id}->element = $content_element->element;
						}
					endif;
					//// ici
					if ($content_element->error) :
						${"element" . $o_element->id}->error      = $content_element->error;
						${"element" . $o_element->id}->errorClass = $el_params->class;
					endif;
					if ($el_params->tipLocation == 'side') :
						${"element" . $o_element->id}->tipSide = $content_element->tipSide;
					endif;
					if ($el_params->tipLocation == 'below') :
						${"element" . $o_element->id}->tipBelow = $content_element->tipBelow;
					endif;
				}
				else {
					${"element" . $o_element->id}->label_value = $content_element->label;

					if ($el_params->tipLocation == 'above') :
						${"element" . $o_element->id}->tipAbove = $content_element->tipAbove;
					endif;
					if ($content_element->element) :
						if (in_array($o_element->plugin,['date','jdate'])) {
							${"element" . $o_element->id}->element = '<input data-v-8d3bb2fa="" class="form-control" type="date">';
						}
						else {
							${"element" . $o_element->id}->element = $content_element->element;
						}
					endif;
					if ($content_element->error) :
						${"element" . $o_element->id}->error      = $content_element->error;
						${"element" . $o_element->id}->errorClass = $el_params->class;
					endif;
					if ($el_params->tipLocation == 'side') :
						${"element" . $o_element->id}->tipSide = $content_element->tipSide;
					endif;
					if ($el_params->tipLocation == 'below') :
						${"element" . $o_element->id}->tipBelow = $content_element->tipBelow;
					endif;
				}
			}
		}

		return ${"element" . $element};
	}

	function getSimpleElement($eid) {
		$query = $this->db->getQuery(true);

		$element = [];

		try {
			$query->select('*')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($eid));
			$this->db->setQuery($query);
			$element = $this->db->loadAssoc();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot get simple element : ' . preg_replace("/[\r\n]/"," ",$query->__toString().' -> '.$e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $element;
	}

	function deleteElement($elt)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->update('#__fabrik_elements')
				->set($this->db->quoteName('published') . ' = -2')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($elt));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot move the element to trash ' . $elt . ' : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}
	}

	function reorderMenu($menus, $profile)
	{
		$updated = false;

		if (!empty($profile)) {
			
			$query = $this->db->getQuery(true);

			try {
				$rgt = 2;
				foreach ($menus as $key => $menu) {
					$rgt = $menu->rgt + $key + 3;
					$lft = $menu->rgt + $key + 2;

					if (!empty($menu->link)) {
						$query->clear()
							->update($this->db->quoteName('#__menu'))
							->set('rgt = ' . $this->db->quote($rgt))
							->set('lft = ' . $this->db->quote($lft))
							->where('link = ' . $this->db->quote($menu->link));
						$this->db->setQuery($query);
						$this->db->execute();
					}
				}

				$query->clear()
					->update($this->db->quoteName('#__menu'))
					->set('lft = ' . $this->db->quote(1))
					->set('rgt = ' . $this->db->quote($rgt - 1))
					->where('menutype = ' . $this->db->quote('menu-profile' . $profile))
					->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'));
				$this->db->setQuery($query);

				$updated = $this->db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/formbuilder | Error at reorder the menu with link : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $updated;
	}

	function getGroupOrdering($gid, $fid)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('ordering')
				->from($this->db->quoteName('#__fabrik_formgroup'))
				->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($gid))
				->andWhere($this->db->quoteName('form_id') . ' = ' . $this->db->quote($fid));

			$this->db->setQuery($query);

			return $this->db->loadResult();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot get ordering of group ' . $gid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function reorderGroup($gid, $fid, $order)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->clear()
				->update($this->db->quoteName('#__fabrik_formgroup'))
				->set('ordering = ' . $this->db->quote($order))
				->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($gid))
				->andWhere($this->db->quoteName('form_id') . ' = ' . $this->db->quote($fid));

			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot reorder group ' . $gid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	/**
	 * Get menus templates
	 *
	 * @return array|mixed|void
	 */
	function getPagesModel($form_ids = [], $model_ids = [])
	{
		$models = [];

		$query  = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_template_form'));

		if (!empty($form_ids)) {
			$query->where('form_id IN (' . implode(',', $form_ids) . ')');
		}
		else if (!empty($model_ids)) {
			$query->where('id IN (' . implode(',', $model_ids) . ')');
		}

		$query->order('form_id');

		try {
			$this->db->setQuery($query);
			$models = $this->db->loadObjectList();

			foreach ($models as $model) {
				$model->label = array(
					'fr' => $this->getTranslation($model->label, 'fr-FR'),
					'en' => $this->getTranslation($model->label, 'en-GB')
				);

				$query->clear()
					->select('intro')
					->from($this->db->quoteName('#__fabrik_forms'))
					->where('id = ' . $this->db->quote($model->form_id));

				$this->db->setQuery($query);
				$model_data = $this->db->loadObject();

				$model->intro = array(
					'fr' => $this->getTranslation(strip_tags($model_data->intro), 'fr-FR'),
					'en' => $this->getTranslation(strip_tags($model_data->intro), 'en-GB')
				);
			}
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at getting pages models : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $models;
	}

	/**
	 * Create a menu from a choosen template
	 *
	 * @param         $label
	 * @param         $intro
	 * @param         $formid
	 * @param         $prid
	 * @param   bool  $keep_structure  keep structure true means that the new form id will store data in same table as the template
	 *
	 * @return array
	 */
	function createMenuFromTemplate($label, $intro, $formid, $prid, bool $keep_structure = false)
	{
		$response = array('status' => false, 'msg' => 'Failed to create menu from form template');

		if (!empty($formid) && !empty($prid)) {
			$user = JFactory::getUser();

			if ($keep_structure) {
				$used = $this->checkIfModelTableIsUsedInForm($formid, $prid);

				if ($used) {
					$keep_structure  = false;
					$response['msg'] = 'The table is already used in another form of same workflow, the structure will be changed';
					Log::add($user->id . ' The table of form ' . $formid . ' is already used in another form of same profile ' . $prid . ', the data structure will be duplicated in new table', Log::INFO, 'com_emundus.formbuilder');
				}
			}

			// Prepare Fabrik API
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');
			$form = JModelLegacy::getInstance('Form', 'FabrikFEModel');
			$form->setId(intval($formid));
			$groups = $form->getGroups();

			// Prepare languages
			$model_prefix   = 'Model - ';
			$path_to_file   = basename(__FILE__) . '/../language/overrides/';
			$path_to_files  = array();
			$Content_Folder = array();
			$languages      = JLanguageHelper::getLanguages();
			foreach ($languages as $language) {
				$path_to_files[$language->sef]  = $path_to_file . $language->lang_code . '.override.ini';
				$Content_Folder[$language->sef] = file_get_contents($path_to_files[$language->sef]);
			}

			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'falang.php');
			$falang = new EmundusModelFalang();

			$eMConfig = JComponentHelper::getParams('com_emundus');
			$modules  = $eMConfig->get('form_builder_page_creation_modules', [93, 102, 103, 104, 168, 170]);

			
			$query = $this->db->getQuery(true);

			// Get the profile
			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($prid));
			$this->db->setQuery($query);
			$profile = $this->db->loadObject();

			if (!empty($profile)) {
				// Get the header menu
				$query->clear()
					->select('*')
					->from('#__menu')
					->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($profile->menutype))
					->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'));
				$this->db->setQuery($query);
				$menu_parent = $this->db->loadObject();

				// Duplicate the form
				$query->clear()
					->select('*')
					->from('#__fabrik_forms')
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($formid));
				$this->db->setQuery($query);
				$form_model = $this->db->loadObject();

				$insert = [];
				foreach ($form_model as $key => $val) {
					if ($key != 'id' && $key != 'modified' && $key != 'checked_out_time' && $key != 'publish_down') {
						$insert[$key] = $val;
					}
				}
				$insert['created'] = date('Y-m-d H:i:s');
				$insert['publish_up'] = date('Y-m-d H:i:s');
				$insert = (object) $insert;

				try {
					$form_inserted = $this->db->insertObject('#__fabrik_forms', $insert);
					$newformid     = $this->db->insertid();

					if ($form_inserted && !empty($newformid)) {
						// Update translation files
						$this->translate('FORM_' . $prid . '_' . $newformid, $label, 'fabrik_forms', $newformid, 'label');
						$this->translate('FORM_' . $prid . '_INTRO_' . $newformid, $intro, 'fabrik_forms', $newformid, 'intro');

						$update = [
							'id' => $newformid,
							'label' => 'FORM_' . $prid . '_' . $newformid,
							'intro' => 'FORM_' . $prid . '_INTRO_' . $newformid
						];
						$update = (object) $update;
						$updated = $this->db->updateObject('#__fabrik_forms', $update, 'id');

						// Duplicate fabrik list
						$query->clear()
							->select('*')
							->from('#__fabrik_lists')
							->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($formid));
						$this->db->setQuery($query);
						$list_model = $this->db->loadObject();

						if (!empty($list_model)) {
							if (!$keep_structure) {
								$list_model->db_table_name  = $this->createDatabaseTableFromTemplate($list_model->db_table_name, $prid);
								$list_model->db_primary_key = $list_model->db_table_name . '.id';
							}
							$db_table_name = $list_model->db_table_name;

							if (!empty($db_table_name)) {
								$insert = [];
								foreach ($list_model as $key => $val) {
									if ($key != 'id' && $key != 'form_id' && $key != 'access' && $key != 'modified' && $key != 'checked_out_time' && $key != 'publish_down') {
										$insert[$key] = $val;
									}
									elseif ($key == 'form_id') {
										$insert[$key] = $newformid;
									}
									elseif ($key == 'access') {
										$insert[$key] = $prid;
									}
								}
								$insert['created'] = date('Y-m-d H:i:s');
								$insert['publish_up'] = date('Y-m-d H:i:s');
								$insert = (object) $insert;
								$this->db->insertObject('#__fabrik_lists', $insert);
								$newlistid = $this->db->insertid();

								if (!empty($newlistid)) {
									$update = [
										'id' => $newlistid,
										'label' => 'FORM_' . $prid . '_' . $newformid,
										'introduction' => '<p>FORM_' . $prid . '_INTRO_' . $newformid . '</p'
									];
									$update = (object) $update;
									$updated = $this->db->updateObject('#__fabrik_lists', $update, 'id');

									// JOIN LIST AND PROFILE_ID
									$insert = [
										'form_id' => $newlistid,
										'profile_id' => $prid,
										'created' => date('Y-m-d H:i:s')
									];
									$insert = (object) $insert;
									$this->db->insertObject('#__emundus_setup_formlist', $insert);

									// Duplicate group
									$ordering = 0;
									foreach ($groups as $group) {
										$ordering++;
										$properties = $group->getGroupProperties($group->getFormModel());
										$elements   = $group->getMyElements();

										$query->clear()
											->select('*')
											->from('#__fabrik_groups')
											->where($this->db->quoteName('id') . ' = ' . $this->db->quote($properties->id));
										$this->db->setQuery($query);
										$group_model = $this->db->loadObject();

										$insert = [];
										foreach ($group_model as $key => $val) {
											if ($key != 'id' && $key != 'form_id' && $key != 'created_by' && $key != 'created' && $key != 'modified' && $key != 'checked_out_time') {
												$insert[$key] = $val;
											}
											elseif ($key == 'form_id') {
												$insert[$key] = $newformid;
											}
											elseif ($key == 'created_by') {
												$insert[$key] = $user->id;
											}
											elseif ($key == 'created') {
												$insert[$key] = date('Y-m-d H:i:s');
											}
										}
										$insert = (object) $insert;
										$this->db->insertObject('#__fabrik_groups', $insert);

										$newgroupid = $this->db->insertid();

										if ($group_model->is_join == 1) {
											$query->clear()
												->select('table_join')
												->from($this->db->quoteName('#__fabrik_joins'))
												->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($properties->id))
												->andWhere($this->db->quoteName('table_join_key') . ' = ' . $this->db->quote('parent_id'));
											$this->db->setQuery($query);
											$repeat_table_to_copy = $this->db->loadResult();

											if (!$keep_structure) {
												$repeat_table_to_copy = $this->createDatabaseTableFromTemplate($repeat_table_to_copy, $prid, $db_table_name, $newgroupid);
											}

											$joins_params = '{"type":"group","pk":"`' . $repeat_table_to_copy . '`.`id`"}';

											$insert = [
												'list_id' => $newlistid,
												'element_id' => 0,
												'join_from_table' => $db_table_name,
												'table_join' => $repeat_table_to_copy,
												'table_key' => 'id',
												'table_join_key' => 'parent_id',
												'join_type' => 'left',
												'group_id' => $newgroupid,
												'params' => $joins_params
											];
											$insert = (object) $insert;
											$this->db->insertObject('#__fabrik_joins', $insert);
										}

										// Update translation files
										if ($formid == 258) {
											$labels = array(
												'fr' => "Confirmation d'envoi de dossier",
												'en' => 'Confirmation of file sending',
											);
											$this->translate('GROUP_' . $newformid . '_' . $newgroupid, $labels, 'fabrik_groups', $newgroupid, 'label');
										}
										else {
											$labels_to_duplicate = array();
											foreach ($languages as $language) {
												$labels_to_duplicate[$language->sef] = str_replace($model_prefix, '', $this->getTranslation($group_model->label, $language->lang_code));
												if ($label[$language->sef] == '') {
													$label[$language->sef] = $group_model->label;
												}
											}
											$this->translate('GROUP_' . $newformid . '_' . $newgroupid, $labels_to_duplicate, 'fabrik_groups', $newgroupid, 'label');
										}

										$update = [
											'id' => $newgroupid,
											'label' => 'GROUP_' . $newformid . '_' . $newgroupid,
											'name' => 'GROUP_' . $newformid . '_' . $newgroupid
										];
										$update = (object) $update;
										$updated = $this->db->updateObject('#__fabrik_groups', $update, 'id');

										$insert = [
											'form_id' => $newformid,
											'group_id' => $newgroupid,
											'ordering' => $ordering
										];
										$insert = (object) $insert;
										$this->db->insertObject('#__fabrik_formgroup', $insert);

										foreach ($elements as $element) {
											try {
												$newelement   = $element->copyRow($element->element->id, 'Copy of %s', $newgroupid);
												$newelementid = $newelement->id;

												$el_params = json_decode($element->element->params);

												// Update translation files
												if (($element->element->plugin === 'checkbox' || $element->element->plugin === 'radiobutton' || $element->element->plugin === 'dropdown') && $el_params->sub_options) {
													$sub_labels = [];
													foreach ($el_params->sub_options->sub_labels as $index => $sub_label) {
														$labels_to_duplicate = array();
														foreach ($languages as $language) {
															$labels_to_duplicate[$language->sef] = str_replace($model_prefix, '', $this->getTranslation($sub_label, $language->lang_code));
															if ($label[$language->sef] == '') {
																$label[$language->sef] = $sub_label;
															}
														}
														$this->translate('SUBLABEL_' . $newgroupid . '_' . $newelementid . '_' . $index, $labels_to_duplicate, 'fabrik_elements', $newelementid, 'sub_labels');
														$sub_labels[] = 'SUBLABEL_' . $newgroupid . '_' . $newelementid . '_' . $index;
													}
													$el_params->sub_options->sub_labels = $sub_labels;
												}

												$labels_to_duplicate = array();
												foreach ($languages as $language) {
													$labels_to_duplicate[$language->sef] = str_replace($model_prefix, '', $this->getTranslation($element->element->label, $language->lang_code));
													if ($label[$language->sef] == '') {
														$label[$language->sef] = $element->element->label;
													}
												}
												$this->translate('ELEMENT_' . $newgroupid . '_' . $newelementid, $labels_to_duplicate, 'fabrik_elements', $newelementid, 'label');

												$update = [
													'id' => $newelementid,
													'label' => 'ELEMENT_' . $newgroupid . '_' . $newelementid,
													'published' => $element->element->published,
													'params' => json_encode($el_params)
												];
												$update = (object) $update;
												$updated = $this->db->updateObject('#__fabrik_elements', $update, 'id');
											}
											catch (Exception $e) {
												Log::add('component/com_emundus/models/formbuilder | Error at create a page from the model ' . $formid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
											}
										}
									}

									// Duplicate the form-menu
									$query
										->clear()
										->select('rgt')
										->from($this->db->quoteName('#__menu'))
										->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($profile->menutype))
										->andWhere($this->db->quoteName('path') . ' LIKE ' . $this->db->quote($profile->menutype . '%'))
										->andWhere($this->db->quoteName('published') . ' = 1')
										->order('rgt');
									$this->db->setQuery($query);
									$menus = $this->db->loadObjectList();
									$rgts  = [];
									foreach (array_values($menus) as $menu) {
										if (!in_array($menu->rgt, $rgts)) {
											$rgts[] = intval($menu->rgt);
										}
									}

									$params    = EmundusHelperFabrik::prepareFabrikMenuParams();
									$datas     = [
										'menutype'     => $profile->menutype,
										'title'        => 'FORM_' . $profile->id . '_' . $newformid,
										'link'         => 'index.php?option=com_fabrik&view=form&formid=' . $newformid,
										'path'         => $menu_parent->path . '/' . str_replace($this->getSpecialCharacters(), '-', strtolower($label['fr'])) . '-' . $newformid,
										'alias'        => 'form-' . $newformid . '-' . str_replace($this->getSpecialCharacters(), '-', strtolower($label['fr'])),
										'type'         => 'component',
										'component_id' => ComponentHelper::getComponent('com_fabrik')->id,
										'params'       => $params
									];
									$parent_id = 1;
									if ($list_model->db_table_name != 'jos_emundus_declaration' && $menu_parent->id != 0) {
										$parent_id = $menu_parent->id;
									}
									$result = EmundusHelperUpdate::addJoomlaMenu($datas, $parent_id, 1, 'last-child', $modules);
									if ($result['status'] !== true) {
										return array(
											'status' => false,
											'msg'    => 'UNABLE_TO_INSERT_NEW_MENU'
										);
									}
									$newmenuid = $result['id'];

									if (!empty($newmenuid)) {
										$update = [
											'id' => $newmenuid,
											'alias' => 'menu-profile' . $profile->id . '-form-' . $newmenuid,
											'published' => 1
										];
										$update = (object) $update;
										$updated = $this->db->updateObject('#__menu', $update, 'id');

										require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'falang.php');
										$falang = new EmundusModelFalang;
										$falang->insertFalang($label, $newmenuid, 'menu', 'title');

										$response['id']     = $newformid;
										$response['link']   = 'index.php?option=com_fabrik&view=form&formid=' . $newformid;
										$response['rgt']    = array_values($rgts)[strval(sizeof($rgts) - 1)] + 2;
										$response['status'] = true;
									}
									else {
										$response['msg'] = 'Failed to insert new menu';
									}
								}
								else {
									$response['msg'] = 'Failed to insert new list';
								}
							}
							else {
								$response['msg'] = 'Empty db table name';
							}
						}
						else {
							$response['msg'] = 'Failed to retrieve list from model form_id ' . $formid;
						}
					}
					else {
						$response['msg'] = 'Failed to create new form';
					}
				}
				catch (Exception $e) {
					Log::add('component/com_emundus/models/formbuilder | Error at create a page from the model ' . $formid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
					$response = array(
						'status' => false,
						'msg'    => $e->getMessage(),
						'query'  => $query->__toString()
					);
				}
			}
			else {
				$response['msg'] = 'Failed to get profile infos from ' . $prid;
			}
		}

		return $response;
	}


	function checkIfModelTableIsUsedInForm($model_id, $profile_id)
	{
		$used = false;

		if (!empty($model_id) && !empty($profile_id)) {
			
			$query = $this->db->getQuery(true);

			require_once(JPATH_SITE . '/components/com_emundus/models/form.php');
			$m_form = new EmundusModelForm();
			$forms  = $m_form->getFormsByProfileId($profile_id);

			if (!empty($forms)) {
				$query->select('db_table_name')
					->from($this->db->quoteName('#__fabrik_lists'))
					->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($model_id));

				$this->db->setQuery($query);
				$model_table = $this->db->loadResult();

				$form_ids = array();
				foreach ($forms as $form) {
					$form_ids[] = $form->id;
				}

				$query->clear()
					->select('db_table_name')
					->from($this->db->quoteName('#__fabrik_lists'))
					->where($this->db->quoteName('form_id') . ' IN (' . implode(',', $form_ids) . ')');

				$this->db->setQuery($query);
				$form_tables = $this->db->loadColumn();

				if (!empty($form_tables)) {
					if (in_array($model_table, $form_tables)) {
						$used = true;
					}
				}
			}
		}

		return $used;
	}

	/**
	 * @param   string  $template_table_name
	 * @param   int     $profile_id
	 *
	 * @return string
	 */
	public function createDatabaseTableFromTemplate(string $template_table_name, int $profile_id, string $parent_table_name = '', $group_id = 0)
	{
		$new_table = '';

		if (!empty($template_table_name)) {
			
			$query = 'SHOW CREATE TABLE ' . $this->db->quoteName($template_table_name);

			try {
				$this->db->setQuery($query);
				$result = $this->db->loadAssoc();
			}
			catch (Exception $e) {
				// table doesn't exist
				$result = array();
			}

			if (!empty($result['Create Table'])) {
				$new_create_table = $result['Create Table'];

				// Replace table name
				if (empty($parent_table_name)) {
					$increment      = 0;
					$new_table_name = 'jos_emundus_' . $profile_id . '_' . str_pad($increment, 2, '0', STR_PAD_LEFT);
					// while table exists increment

					require_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');
					$h_files = new EmundusHelperFiles();
					while ($h_files->tableExists($new_table_name)) {
						$increment++;
						$new_table_name = 'jos_emundus_' . $profile_id . '_' . str_pad($increment, 2, '0', STR_PAD_LEFT);
					}
				}
				else {
					$new_table_name = $parent_table_name . '_' . $group_id . '_repeat';
				}

				$new_create_table = str_replace($template_table_name, $new_table_name, $new_create_table);

				try {
					$this->db->setQuery($new_create_table);
					$created = $this->db->execute();

					if ($created) {
						$new_table = $new_table_name;
					}
				}
				catch (Exception $e) {
					Log::add('component/com_emundus/models/formbuilder | Error at create a table from the template ' . $template_table_name . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
				}
			}
		}

		return $new_table;
	}

	function checkConstraintGroup($cid)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('sg.id')
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'c'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'gc') . ' ON ' . $this->db->quoteName('c.training') . ' LIKE ' . $this->db->quoteName('gc.course'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups', 'sg') . ' ON ' . $this->db->quoteName('gc.parent_id') . ' = ' . $this->db->quoteName('sg.id'))
				->where($this->db->quoteName('c.id') . ' = ' . $this->db->quote($cid))
				->andWhere($this->db->quoteName('sg.description') . ' LIKE ' . $this->db->quote('constraint_group'));
			$this->db->setQuery($query);

			return $this->db->loadResult();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at check constraints groups of the campaign ' . $cid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function checkVisibility($group, $cid)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('COUNT(gf.id)')
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'c'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_course', 'gc') . ' ON ' . $this->db->quoteName('c.training') . ' LIKE ' . $this->db->quoteName('gc.course'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups', 'sg') . ' ON ' . $this->db->quoteName('gc.parent_id') . ' = ' . $this->db->quoteName('sg.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_groups_repeat_fabrik_group_link', 'gf') . ' ON ' . $this->db->quoteName('gf.parent_id') . ' = ' . $this->db->quoteName('sg.id'))
				->where($this->db->quoteName('c.id') . ' = ' . $this->db->quote($cid))
				->andWhere($this->db->quoteName('sg.description') . ' LIKE ' . $this->db->quote('constraint_group'))
				->andWhere($this->db->quoteName('gf.fabrik_group_link') . ' = ' . $this->db->quote($group));
			$this->db->setQuery($query);

			return $this->db->loadResult();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at check visibility of the group ' . $group . ' in campaign ' . $cid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function publishUnpublishElement($element)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('published')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($element));
			$this->db->setQuery($query);
			$old_publish = $this->db->loadResult();

			$publish = 1;
			if ($old_publish == 1) {
				$publish = 0;
			}

			$query->clear()
				->update($this->db->quoteName('#__fabrik_elements'))
				->set($this->db->quoteName('published') . ' = ' . $this->db->quote($publish))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($element));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at publish/unpublish element ' . $element . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function hiddenUnhiddenElement($element)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('hidden')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($element));
			$this->db->setQuery($query);
			$old_hidden = $this->db->loadResult();

			$hidden = 1;
			if ($old_hidden == 1) {
				$hidden = 0;
			}

			$query->clear()
				->update($this->db->quoteName('#__fabrik_elements'))
				->set($this->db->quoteName('hidden') . ' = ' . $this->db->quote($hidden))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($element));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at publish/unpublish element ' . $element . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function getDatabasesJoin()
	{

		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_datas_library'))
			->order('label');
		$this->db->setQuery($query);
		try {
			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at getting databases references : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function getDatabaseJoinOrderColumns($database_name)
	{


		$query = "SELECT DISTINCT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N'$database_name'";

		try {
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at getting databases references columns : ' . preg_replace("/[\r\n]/", " ", $query . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function enableRepeatGroup($gid)
	{
		$saved = false;

		$query = $this->db->getQuery(true);
		$user  = $this->app->getIdentity()->id;

		$group = $this->getFabrikGroup($gid);

		if (!empty($group)) {
			// Prepare Fabrik API
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');
			$groupModel = JModelLegacy::getInstance('Group', 'FabrikFEModel');
			$groupModel->setId(intval($gid));
			$elements  = $groupModel->getMyElements();
			$listModel = $groupModel->getListModel();
			$list      = $listModel->getTable();
			$db_table  = $list->db_table_name;
			$list_id   = $list->id;
			$form_id   = $list->form_id;

			$group_params                      = json_decode($group->params);
			$group_params->repeat_group_button = 1;

			$query->clear()
				->update($this->db->quoteName('#__fabrik_groups'))
				->set($this->db->quoteName('is_join') . ' = ' . $this->db->quote(1))
				->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($group_params)))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($gid));
			$this->db->setQuery($query);
			$this->db->execute();

			// Create the new table
			$newtablename = $db_table . "_" . $gid . "_repeat";
			$joins_params = '{"type":"group","pk":"`' . $newtablename . '`.`id`"}';

			$query = "CREATE TABLE IF NOT EXISTS " . $newtablename . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            PRIMARY KEY (id)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4";
			$this->db->setQuery($query);
			$this->db->execute();

			// Create parent_id element
			$query  = $this->db->getQuery(true);
			$params = $this->h_fabrik->prepareElementParameters('field', false);

			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($gid))
				->order('ordering');
			$this->db->setQuery($query);
			$results   = $this->db->loadObjectList();
			$orderings = [];
			foreach (array_values($results) as $result) {
				if (!in_array($result->ordering, $orderings)) {
					$orderings[] = intval($result->ordering);
				}
			}

			// Check if the ID and parent_id already exists in the group
			$ignore_elms = [];
			foreach ($elements as $element => $value) {
				if ($value->element->name == 'parent_id' || $value->element->name == 'id') {
					$ignore_elms[] = $value->element->name;
				}
			}
			// Insert parent_id in elements

			if (!in_array('parent_id', $ignore_elms)) {
				$query
					->clear()
					->insert($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('name') . ' = ' . $this->db->quote('parent_id'))
					->set($this->db->quoteName('group_id') . ' = ' . $this->db->quote($gid))
					->set($this->db->quoteName('plugin') . ' = ' . $this->db->quote('field'))
					->set($this->db->quoteName('label') . ' = ' . $this->db->quote('parent_id'))
					->set($this->db->quoteName('checked_out') . ' = 0')
					->set($this->db->quoteName('checked_out_time') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
					->set($this->db->quoteName('created') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
					->set($this->db->quoteName('created_by') . ' = ' . $this->db->quote($user))
					->set($this->db->quoteName('created_by_alias') . ' = ' . $this->db->quote('coordinator'))
					->set($this->db->quoteName('modified') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
					->set($this->db->quoteName('modified_by') . ' = ' . $this->db->quote($user))
					->set($this->db->quoteName('width') . ' = 0')
					->set($this->db->quoteName('default') . ' = ' . $this->db->quote(''))
					->set($this->db->quoteName('hidden') . ' = 1')
					->set($this->db->quoteName('eval') . ' = 0')
					->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote(array_values($orderings)[strval(sizeof($orderings) - 1)] + 1))
					->set($this->db->quoteName('parent_id') . ' = 0')
					->set($this->db->quoteName('published') . ' = 1')
					->set($this->db->quoteName('access') . ' = 1')
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)));
				$this->db->setQuery($query);
				$this->db->execute();
			}
			else {
				$query->clear()
					->update('#__fabrik_elements')
					->set('published = 1')
					->where('group_id = ' . $this->db->quote($gid))
					->andWhere('name = ' . $this->db->quote('parent_id'));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			if (!in_array('id', $ignore_elms)) {
				// Insert id in elements
				$query
					->clear()
					->insert($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('name') . ' = ' . $this->db->quote('id'))
					->set($this->db->quoteName('group_id') . ' = ' . $this->db->quote($gid))
					->set($this->db->quoteName('plugin') . ' = ' . $this->db->quote('internalid'))
					->set($this->db->quoteName('label') . ' = ' . $this->db->quote('id'))
					->set($this->db->quoteName('checked_out') . ' = 0')
					->set($this->db->quoteName('checked_out_time') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
					->set($this->db->quoteName('created') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
					->set($this->db->quoteName('created_by') . ' = ' . $this->db->quote($user))
					->set($this->db->quoteName('created_by_alias') . ' = ' . $this->db->quote('coordinator'))
					->set($this->db->quoteName('modified') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
					->set($this->db->quoteName('modified_by') . ' = ' . $this->db->quote($user))
					->set($this->db->quoteName('width') . ' = 0')
					->set($this->db->quoteName('default') . ' = ' . $this->db->quote(''))
					->set($this->db->quoteName('hidden') . ' = 1')
					->set($this->db->quoteName('eval') . ' = 0')
					->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote(array_values($orderings)[strval(sizeof($orderings) - 1)] + 1))
					->set($this->db->quoteName('parent_id') . ' = 0')
					->set($this->db->quoteName('published') . ' = 1')
					->set($this->db->quoteName('access') . ' = 1')
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			try {
				$query = "ALTER TABLE " . $newtablename . " ADD COLUMN parent_id int(11) NULL AFTER id";
				$this->db->setQuery($query);
				$this->db->execute();

				$query = "CREATE INDEX fb_parent_fk_parent_id_INDEX ON " . $newtablename . " (parent_id);";
				$this->db->setQuery($query);
				$this->db->execute();
			}
			catch (Exception $e) {
				// This means that the parent_id already exists in the table.
			}

			//verify if left join doesn't already exist;
			$query = $this->db->getQuery(true);
			$query->select('id')
				->from($this->db->quoteName('#__fabrik_joins'))
				->where($this->db->quoteName('table_join') . ' = ' . $this->db->quote($newtablename))
				->andWhere($this->db->quoteName('table_join_key') . ' = ' . $this->db->quote('parent_id'));
			$this->db->setQuery($query);
			$left_join_exist = $this->db->loadObject();

			if ($left_join_exist == null) {
				$query->clear();
				$query->insert($this->db->quoteName('#__fabrik_joins'));
				$query->set($this->db->quoteName('list_id') . ' = ' . $this->db->quote($list_id))
					->set($this->db->quoteName('element_id') . ' = ' . $this->db->quote(0))
					->set($this->db->quoteName('join_from_table') . ' = ' . $this->db->quote($db_table))
					->set($this->db->quoteName('table_join') . ' = ' . $this->db->quote($newtablename))
					->set($this->db->quoteName('table_key') . ' = ' . $this->db->quote('id'))
					->set($this->db->quoteName('table_join_key') . ' = ' . $this->db->quote('parent_id'))
					->set($this->db->quoteName('join_type') . ' = ' . $this->db->quote('left'))
					->set($this->db->quoteName('group_id') . ' = ' . $this->db->quote($gid))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote($joins_params));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			// Insert element present in the group
			foreach ($elements as $element) {
				if ($element->element->plugin === 'birthday') {
					$dbtype = 'DATE';
				}
				elseif ($element->element->plugin === 'textarea') {
					$dbtype = 'TEXT';
				}
				else {
					$dbtype = 'TEXT';
				}


				if (!empty($element->element->name)) {
					$query = "ALTER TABLE " . $newtablename . " ADD " . $element->element->name . " " . $dbtype . " NULL";
				}
				else {
					$query = "ALTER TABLE " . $newtablename . " ADD e_" . $form_id . "_" . $element->element->id . " " . $dbtype . " NULL";
				}

				$this->db->setQuery($query);
				try {
					$this->db->execute();
				}
				catch (Exception $e) {
					continue;
				}
			}

			$saved = true;
		}

		return $saved;
	}

	private function getFabrikGroup($gid)
	{
		$group = null;


		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__fabrik_groups'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($gid));
		$this->db->setQuery($query);
		try {
			$group = $this->db->loadObject();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot get group ' . $gid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $group;
	}

	function disableRepeatGroup($gid)
	{
		$saved = false;


		$query = $this->db->getQuery(true);

		$query->select('jfg.*, jff.form_id AS form_id')
			->from('#__fabrik_groups AS jfg')
			->leftJoin('#__fabrik_formgroup AS jff ON jff.group_id = jfg.id')
			->where('jfg.id = ' . $this->db->quote($gid));
		$this->db->setQuery($query);

		try {
			$group = $this->db->loadAssoc();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at enabling repeat group ' . $gid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		if (!empty($group)) {
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');
			require_once(JPATH_ADMINISTRATOR . '/components/com_fabrik/models/group.php');
			$groupModel                    = new FabrikAdminModelGroup;
			$params                        = json_decode($group['params'], true);
			$params['repeat_group_button'] = 0;

			$data = array(
				'id'        => $gid,
				'label'     => $group['label'],
				'form'      => $group['form_id'],
				'name'      => $group['name'],
				'published' => $group['published'],
				'is_join'   => $group['is_join'],
				'params'    => $params,
				'tags'      => $group['tags']
			);

			$saved = $groupModel->save($data);

			if ($saved) {
				$query->clear()
					->update('#__fabrik_groups')
					->set('is_join = 0')
					->where('id = ' . $this->db->quote($gid));

				$this->db->setQuery($query);
				$this->db->execute();

				$query->clear()
					->update('#__fabrik_elements')
					->set('published = 0')
					->where('group_id = ' . $this->db->quote($gid))
					->andWhere('name = ' . $this->db->quote('parent_id'));

				$this->db->setQuery($query);
				$this->db->execute();
			}
		}

		return $saved;
	}

	function displayHideGroup($gid)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('params')
				->from($this->db->quoteName('#__fabrik_groups'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($gid));
			$this->db->setQuery($query);
			$group_params = json_decode($this->db->loadResult());
			if ((int) $group_params->repeat_group_show_first == -1) {
				$group_params->repeat_group_show_first = 1;
			}
			else {
				$group_params->repeat_group_show_first = -1;
			}

			$query->clear()
				->update($this->db->quoteName('#__fabrik_groups'))
				->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($group_params)))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($gid));
			$this->db->setQuery($query);
			$this->db->execute();

			return $group_params->repeat_group_show_first;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot disable repeat group ' . $gid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function updateMenuLabel($label, $pid)
	{

		$query = $this->db->getQuery(true);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'falang.php');

		$falang = new EmundusModelFalang;

		$link = 'index.php?option=com_fabrik&view=form&formid=' . $pid;

		$query->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote($link));
		$this->db->setQuery($query);

		try {
			$menuid = $this->db->loadObject();

			return $falang->updateFalang($label, $menuid->id, 'menu', 'title');
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot update the menu label of the fabrik_form ' . $pid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function getFormTesting($prid, $uid)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('id,label')
				->from($this->db->quoteName('#__emundus_setup_campaigns'))
				->where($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($prid));
			$this->db->setQuery($query);
			$campaigns = $this->db->loadObjectList();
			if (sizeof($campaigns) > 0) {
				foreach ($campaigns as $campaign) {
					$query->clear()
						->select('id,fnum')
						->from($this->db->quoteName('#__emundus_campaign_candidature'))
						->where($this->db->quoteName('campaign_id') . ' = ' . $this->db->quote($campaign->id))
						->andWhere($this->db->quoteName('user_id') . ' = ' . $this->db->quote($uid))
						->andWhere($this->db->quoteName('published') . ' != ' . $this->db->quote(-1));
					$this->db->setQuery($query);
					$campaign->files = $this->db->loadObjectList();
				}
			}

			return $campaigns;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at getting files and campaigns of the form ' . $prid . ' and of the user ' . $uid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function createTestingFile($cid, $uid, $return_ccid = false)
	{

		$query = $this->db->getQuery(true);

		include_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');

		$fnum = EmundusHelperFiles::createFnum($cid, $uid, false);

		try {
			$query->insert($this->db->quoteName('#__emundus_campaign_candidature'));
			$query->set($this->db->quoteName('applicant_id') . ' = ' . $this->db->quote($uid))
				->set($this->db->quoteName('user_id') . ' = ' . $this->db->quote($uid))
				->set($this->db->quoteName('campaign_id') . ' = ' . $this->db->quote($cid))
				->set($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$this->db->execute();
			$ccid = $this->db->insertid();

			return $return_ccid ? [$fnum, $ccid] : $fnum;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at creating a testing file in the campaign ' . $cid . ' of the user ' . $uid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function deleteFormTesting($fnum, $uid)
	{

		$query = $this->db->getQuery(true);
		try {
			$query->delete()
				->from($this->db->quoteName('#__emundus_campaign_candidature'))
				->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum))
				->andWhere($this->db->quoteName('user_id') . ' = ' . $this->db->quote($uid));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot delete testing file ' . $fnum . ' of the user ' . $uid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function retriveElementFormAssociatedDoc($gid, $docid)
	{

		$query = $this->db->getQuery(true);

		try {

			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_attachments'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($docid));

			$this->db->setQuery($query);

			return $this->db->loadObject();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot get ordering of group ' . $gid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function updateDefaultValue($eid, $value)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->update($this->db->quoteName('#__fabrik_elements'))
				->set($this->db->quoteName('default') . ' = ' . $this->db->quote($value))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($eid));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot update default value of element ' . $eid . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function getSection($section)
	{

		$query = $this->db->getQuery(true);

		try {
			$query->select('id,label,params')
				->from($this->db->quoteName('#__fabrik_groups'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($section));
			$this->db->setQuery($query);
			$group = $this->db->loadObject();

			$group->label  = Text::_($group->label);
			$group->params = json_decode($group->params);

			$group->params->intro = Text::_(trim(strip_tags($group->params->intro)));
			$group->params->outro = Text::_(trim(strip_tags($group->params->outro)));

			return $group;
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Cannot get group ' . $section . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function updateElementOption($element, $oldOptions, $index, $newTranslation, $lang = 'fr')
	{
		if (empty($oldOptions['sub_labels'][$index])) {
			$group = $this->getGroupId($element);
			if (empty($group)) {
				return false;
			}

			$oldOptions['sub_labels'][$index] = 'SUBLABEL_' . $group . '_' . $element . '_' . $index;
		}

		$this->deleteTranslation($oldOptions['sub_labels'][$index]);
		$translated = $this->translate($oldOptions['sub_labels'][$index], [$lang => $newTranslation], 'fabrik_elements', $element, 'sub_labels');

		return !empty($translated);
	}

	private function getGroupId($element)
	{
		$group = 0;

		$query = $this->db->getQuery(true);

		$query->select('group_id')
			->from('#__fabrik_elements')
			->where('id = ' . $element);

		$this->db->setQuery($query);
		try {
			$group = $this->db->loadResult();
		}
		catch (Exception $e) {
			Log::add('formbuilder | Error when  trying to find group from element: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $group;
	}

	private function getFabrikElementParams($element)
	{
		$params = [];
		$query  = $this->db->getQuery(true);

		$query->select('params')
			->from('#__fabrik_elements')
			->where('id = ' . $element);

		$this->db->setQuery($query);

		try {
			$params = $this->db->loadResult();

			if (!empty($params)) {
				$params = json_decode($params, true);
			}
		}
		catch (Exception $e) {
			Log::add('formbuilder | Error when  trying to find params from element: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $params;
	}

	private function updateFabrikElementParams($element, $params)
	{
		$updated = false;
		if (!empty($element) && !empty($params)) {

			$query = $this->db->getQuery(true);

			$query->clear()
				->update('#__fabrik_elements')
				->set('params = ' . $this->db->quote(json_encode($params)))
				->where('id = ' . $element);

			$this->db->setQuery($query);

			try {
				$updated = $this->db->execute();
			}
			catch (Exception $e) {
				Log::add("formbuilder | Error when  trying to update params of element $element : " . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $updated;
	}

	public function getElementSubOption($element)
	{
		$options = [];

		$params = $this->getFabrikElementParams($element);

		if (!empty($params)) {
			$options = $params['sub_options'];
		}

		return $options;
	}

	public function addElementSubOption($element, $newOption, $lang)
	{
		$return = false;

		if (!empty($element) && !empty(trim($newOption))) {
			$sub_options = $this->getElementSubOption($element);
			$group       = $this->getGroupId($element);

			$index = sizeof($sub_options['sub_values']) + 1;
			while (in_array($index, $sub_options['sub_values'])) {
				$index++;
			}

			$newLabel                    = 'SUBLABEL_' . $group . '_' . $element . '_' . $index;
			$sub_options['sub_values'][] = $index;
			$sub_options['sub_labels'][] = $newLabel;

			$params = $this->getFabrikElementParams($element);

			if (!empty($params)) {
				$params['sub_options'] = $sub_options;

				$updated = $this->updateFabrikElementParams($element, $params);

				if ($updated) {
					$this->deleteTranslation($newLabel);
					$translated = $this->translate($newLabel, [$lang => $newOption], 'fabrik_elements', $element, 'sub_labels');

					if ($translated) {
						$return = $sub_options;
					}
				}
			}
		}

		return $return;
	}

	public function deleteElementSubOption($element, $index): bool
	{
		$deleted     = false;
		$sub_options = $this->getElementSubOption($element);

		$trad_to_delete = $sub_options['sub_labels'][$index];
		array_splice($sub_options['sub_labels'], $index, 1);
		array_splice($sub_options['sub_values'], $index, 1);

		$params = $this->getFabrikElementParams($element);

		if (!empty($params)) {
			$params['sub_options'] = $sub_options;
			$updated               = $this->updateFabrikElementParams($element, $params);

			if ($updated) {
				$this->deleteTranslation($trad_to_delete);
				$deleted = true;
			}
		}

		return $deleted;
	}

	public function updateElementSubOptionsOrder($element, $old_order, $new_order): bool
	{
		$updated = false;

		if (!empty($element) && !empty($new_order) && is_array($new_order) && !empty($old_order) && is_array($old_order)) {
			if (sizeof($new_order['sub_values']) > 1 && sizeof($new_order['sub_values']) == sizeof($old_order['sub_values'])) {
				$params = $this->getFabrikElementParams($element);

				if (!empty($params)) {
					$params['sub_options'] = $new_order;

					$updated = $this->updateFabrikElementParams($element, $params);
				}
			}
		}

		return $updated;
	}

	private function getFormId($group_id)
	{
		$form_id = 0;

		$query   = $this->db->getQuery(true);

		$query->select('form_id')
			->from('#__fabrik_formgroup')
			->where('group_id = ' . $group_id);

		$this->db->setQuery($query);

		try {
			$form_id = $this->db->loadResult();
		}
		catch (Exception $e) {
			Log::add('formBuilder model: Error trying to find fotm id from group id', Log::ERROR, 'com_emundus.formbuilder');
		}

		return $form_id;
	}

	public function addFormModel($form_id_to_copy, $label)
	{
		$inserted = false;

		if (!empty($form_id_to_copy)) {
			// copy list, form, groups and elements from origin form_id
			$new_form_id = $this->copyForm($form_id_to_copy, 'Model - ');

			// then add form created to list of form models
			if (!empty($new_form_id)) {
				$list_to_copy = $this->getList($form_id_to_copy);

				if (!empty($list_to_copy) && !empty($list_to_copy->id)) {
					$new_list_id = $this->copyList($list_to_copy, $new_form_id);

					if (!empty($new_list_id)) {
						$copied = $this->copyGroups($form_id_to_copy, $new_form_id, $new_list_id, $list_to_copy->db_table_name);

						if ($copied) {
							$label = !empty($label) ? $label : 'Model - ' . $form_id_to_copy . ' - ' . $new_form_id;

							// insert form into models list

							$query = $this->db->getQuery(true);

							$insert = [
								'form_id' => $new_form_id,
								'label'   => $label,
								'created' => date('Y-m-d H:i:s')
							];
							$insert = (object) $insert;

							try {
								$inserted = $this->db->insertObject('#__emundus_template_form', $insert);
							}
							catch (Exception $e) {
								$inserted = false;
								Log::add('Failed to create new form model ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
							}
						}
						else {
							Log::add('Failed to copy groups in new form model  (new form : ' . $new_form_id . ', form model : ' . $form_id_to_copy . ')', Log::WARNING, 'com_emundus.formbuilder');
						}
					}
					else {
						Log::add('Failed to copy List for new form model (new form : ' . $new_form_id . ', list to copy : ' . $list_to_copy . ')', Log::WARNING, 'com_emundus.formbuilder');
					}
				}
				else {
					Log::add('Failed to get List from form model (form model : ' . $form_id_to_copy . ')', Log::WARNING, 'com_emundus.formbuilder');
				}
			}
			else {
				Log::add('Failed to copy Form for new form model (new form : ' . $new_form_id . ', form model : ' . $form_id_to_copy . ')', Log::WARNING, 'com_emundus.formbuilder');
			}
		}

		return $inserted;
	}

	public function deleteFormModel($form_id)
	{
		$deleted = false;

		if (!empty($form_id)) {
			$model = $this->getPagesModel([$form_id]);

			if (!empty($model)) {

				$query = $this->db->getQuery(true);

				$query->delete('#__emundus_template_form')
					->where('form_id = ' . $form_id);

				$this->db->setQuery($query);

				try {
					$deleted = $this->db->execute();
				}
				catch (Exception $e) {
					Log::add('Failed to delete form ' . $form_id . ' model ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
				}
			}
			else {
				$deleted = true;
			}
		}

		return $deleted;
	}

	public function deleteFormModelFromIds($model_ids)
	{
		$deleted = false;

		if (!empty($model_ids)) {
			$model_ids = !is_array($model_ids) ? [$model_ids] : $model_ids;

			// get only models who truly exists
			$models           = $this->getPagesModel([], $model_ids);
			$models_to_delete = [];

			foreach ($models as $model) {
				$models_to_delete[] = $model->id;
			}

			if (!empty($models_to_delete)) {

				$query = $this->db->getQuery(true);

				$query->delete('#__emundus_template_form')
					->where('id IN (' . implode(',', $models_to_delete) . ')');

				$this->db->setQuery($query);

				try {
					$deleted = $this->db->execute();
				}
				catch (Exception $e) {
					Log::add('Failed to delete ' . implode(',', $models_to_delete) . ' model ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
				}
			}
			else {
				$deleted = true;
			}
		}

		return $deleted;
	}

	/**
	 * Duplicate fabrik form, return the id of the form copy, 0 if failed
	 *
	 * @param $form_id_to_copy
	 *
	 * @return int
	 */
	public function copyForm($form_id_to_copy, $label_prefix = ''): int
	{
		$new_form_id = 0;

		if (!empty($form_id_to_copy)) {

			$query = $this->db->getQuery(true);

			$query->clear()
				->select('*')
				->from('#__fabrik_forms')
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($form_id_to_copy));
			$this->db->setQuery($query);
			$form_model = $this->db->loadObject();

			if (!empty($form_model)) {
				$query->clear();
				$query->insert($this->db->quoteName('#__fabrik_forms'));
				foreach ($form_model as $key => $val) {
					if ($key != 'id' && !is_null($val)) {
						$query->set($key . ' = ' . $this->db->quote($val));
					}
				}

				try {
					$this->db->setQuery($query);
					$form_inserted = $this->db->execute();

					if ($form_inserted) {
						$new_form_id = $this->db->insertid();

						$query->clear()
							->update('#__fabrik_forms')
							->set('label = ' . $this->db->quote('FORM_MODEL_' . $new_form_id))
							->where('id = ' . $new_form_id);

						$this->db->setQuery($query);
						$this->db->execute();

						$languages           = JLanguageHelper::getLanguages();
						$labels_to_duplicate = [];
						foreach ($languages as $language) {
							$labels_to_duplicate[$language->sef] = $label_prefix . $this->getTranslation($form_model->label, $language->lang_code);
						}
						$this->translate('FORM_MODEL_' . $new_form_id, $labels_to_duplicate, 'fabrik_forms', $new_form_id, 'label');
					}
				}
				catch (Exception $e) {
					Log::add('Failed to copy form from id : ' . $form_id_to_copy . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
				}
			}
		}

		return $new_form_id;
	}

	public function copyList($list_model, $form_id)
	{
		$new_list_id = 0;

		if (!empty($list_model)) {

			$query = $this->db->getQuery(true);

			$query->clear();
			$query->insert($this->db->quoteName('#__fabrik_lists'));
			foreach ($list_model as $key => $val) {
				if ($key == 'form_id') {
					$query->set($key . ' = ' . $this->db->quote($form_id));
				}
				elseif (!in_array($key, ['id', 'checked_out_time', 'publish_up', 'publish_down', 'modified'])) {
					$query->set($key . ' = ' . $this->db->quote($val));
				}
			}

			if (empty($list_model->created)) {
				$query->set('created = ' . $this->db->quote(date('Y-m-d H:i:s')));
			}

			try {
				$this->db->setQuery($query);
				$inserted = $this->db->execute();
				if ($inserted) {
					$new_list_id = $this->db->insertid();
				}
			}
			catch (Exception $e) {
				Log::add('Failed to copy Fabrik list ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
			}

			if (!empty($new_list_id)) {
				$query->clear();
				$query->update($this->db->quoteName('#__fabrik_lists'));
				$query->set('label = ' . $this->db->quote('FORM_MODEL_' . $form_id));
				$query->set('introduction = ' . $this->db->quote('<p>' . 'FORM_MODEL_INTRO_' . $form_id . '</p>'));
				$query->where('id = ' . $this->db->quote($new_list_id));

				try {
					$this->db->setQuery($query);
					$this->db->execute();
				}
				catch (Exception $e) {
					Log::add('Failed to update Fabrik list label and intro ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
				}
			}
		}

		return $new_list_id;
	}

	public function getList($form_id, $columns = '*')
	{
		$list = new stdClass();

		if (!empty($form_id)) {

			$query = $this->db->getQuery(true);

			$query->select($columns)
				->from('#__fabrik_lists')
				->where('form_id = ' . $form_id);

			try {
				$this->db->setQuery($query);
				$list = $this->db->loadObject();
			}
			catch (Exception $e) {
				Log::add('Failed to find list id from form id ' . $form_id . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
			}
		}

		return $list;
	}

	public function copyGroups($form_id_to_copy, $new_form_id, $new_list_id, $db_table_name, $label_prefix = '', $user = null)
	{
		$copied = false;

		if(empty($user)) {
			$user = Factory::getApplication()->getIdentity();
		}

		if (!empty($form_id_to_copy) && !empty($new_form_id) && !empty($new_list_id)) {
			$label = [];

			$query = $this->db->getQuery(true);

			$languages = LanguageHelper::getLanguages();
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_fabrik/models');
			$form = JModelLegacy::getInstance('Form', 'FabrikFEModel');
			$form->setId(intval($form_id_to_copy));
			$ordering = 0;

			$groups        = $form->getGroups();
			$groups_copied = [];
			foreach ($groups as $g_index => $group) {
				$groups_copied[$g_index] = false;
				$ordering++;
				$properties = $group->getGroupProperties($group->getFormModel());

				$query->clear()
					->select('*')
					->from('#__fabrik_groups')
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($properties->id));
				$this->db->setQuery($query);
				$group_model = $this->db->loadObject();

				if (!empty($group_model)) {
					$query->clear();
					$query->insert($this->db->quoteName('#__fabrik_groups'));
					foreach ($group_model as $key => $val) {
						if(in_array($key, ['created', 'modified', 'checked_out_time'])) {
							$query->set($key . ' = ' . $this->db->quote(date('Y-m-d H:i:s')));
						}
						else if(in_array($key, ['created_by', 'modified_by', 'checked_out'])) {
							$query->set($key . ' = ' . $this->db->quote($user->id));
						}
						else if ($key != 'id') {
							$query->set($key . ' = ' . $this->db->quote($val));
						}
					}
					$this->db->setQuery($query);
					$this->db->execute();
					$new_group_id = $this->db->insertid();

					if (!empty($new_group_id)) {
						if ($group_model->is_join == 1) {
							$query->clear()
								->select('table_join')
								->from($this->db->quoteName('#__fabrik_joins'))
								->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($properties->id))
								->andWhere($this->db->quoteName('table_join_key') . ' = ' . $this->db->quote('parent_id'));
							$this->db->setQuery($query);
							$repeat_table_to_copy = $this->db->loadResult();

							$joins_params = '{"type":"group","pk":"`' . $repeat_table_to_copy . '`.`id`"}';

							$query->clear()
								->insert($this->db->quoteName('#__fabrik_joins'));
							$query->set($this->db->quoteName('list_id') . ' = ' . $this->db->quote($new_list_id))
								->set($this->db->quoteName('element_id') . ' = ' . $this->db->quote(0))
								->set($this->db->quoteName('join_from_table') . ' = ' . $this->db->quote($db_table_name))
								->set($this->db->quoteName('table_join') . ' = ' . $this->db->quote($repeat_table_to_copy))
								->set($this->db->quoteName('table_key') . ' = ' . $this->db->quote('id'))
								->set($this->db->quoteName('table_join_key') . ' = ' . $this->db->quote('parent_id'))
								->set($this->db->quoteName('join_type') . ' = ' . $this->db->quote('left'))
								->set($this->db->quoteName('group_id') . ' = ' . $this->db->quote($new_group_id))
								->set($this->db->quoteName('params') . ' = ' . $this->db->quote($joins_params));
							$this->db->setQuery($query);
							$this->db->execute();
						}

						// Update translation files
						$query->clear();
						$query->update($this->db->quoteName('#__fabrik_groups'));

						if ($form_id_to_copy == 258) {
							$labels = array(
								'fr' => $label_prefix . 'Confirmation d\'envoi de dossier',
								'en' => $label_prefix . 'Confirmation of file sending',
							);
							$this->translate('GROUP_MODEL_' . $new_form_id . '_' . $new_group_id, $labels, 'fabrik_groups', $new_group_id, 'label');
						}
						else {
							$labels_to_duplicate = array();
							foreach ($languages as $language) {
								$labels_to_duplicate[$language->sef] = $label_prefix . $this->getTranslation($group_model->label, $language->lang_code);
							}
							$this->translate('GROUP_MODEL_' . $new_form_id . '_' . $new_group_id, $labels_to_duplicate, 'fabrik_groups', $new_group_id, 'label');
						}

						$query->set('label = ' . $this->db->quote('GROUP_MODEL_' . $new_form_id . '_' . $new_group_id));
						$query->set('name = ' . $this->db->quote('GROUP_MODEL_' . $new_form_id . '_' . $new_group_id));
						$query->where('id =' . $new_group_id);
						$this->db->setQuery($query);
						$this->db->execute();

						$query->clear()
							->insert($this->db->quoteName('#__fabrik_formgroup'))
							->set('form_id = ' . $this->db->quote($new_form_id))
							->set('group_id = ' . $this->db->quote($new_group_id))
							->set('ordering = ' . $this->db->quote($ordering));
						$this->db->setQuery($query);
						$this->db->execute();

						$elements = $group->getMyElements();
						foreach ($elements as $element) {
							try {
								$new_element    = $element->copyRow($element->element->id, 'Copy of %s', $new_group_id);
								$new_element_id = $new_element->id;

								$el_params = json_decode($element->element->params);

								// Update translation files
								if (($element->element->plugin === 'checkbox' || $element->element->plugin === 'radiobutton' || $element->element->plugin === 'dropdown') && $el_params->sub_options) {
									$sub_labels = [];
									foreach ($el_params->sub_options->sub_labels as $index => $sub_label) {
										$labels_to_duplicate = array();
										foreach ($languages as $language) {
											$labels_to_duplicate[$language->sef] = $label_prefix . $this->getTranslation($sub_label, $language->lang_code);
										}
										$this->translate('SUBLABEL_MODEL_' . $new_group_id . '_' . $new_element_id . '_' . $index, $labels_to_duplicate, 'fabrik_elements', $new_element_id, 'sub_labels');
										$sub_labels[] = 'SUBLABEL_MODEL_' . $new_group_id . '_' . $new_element_id . '_' . $index;
									}
									$el_params->sub_options->sub_labels = $sub_labels;
								}
								$query->clear();
								$query->update($this->db->quoteName('#__fabrik_elements'));

								$labels_to_duplicate = array();
								foreach ($languages as $language) {
									$labels_to_duplicate[$language->sef] = $label_prefix . $this->getTranslation($element->element->label, $language->lang_code);
								}
								$this->translate('ELEMENT_MODEL_' . $new_group_id . '_' . $new_element_id, $labels_to_duplicate, 'fabrik_elements', $new_element_id, 'label');

								$query->set('label = ' . $this->db->quote('ELEMENT_MODEL_' . $new_group_id . '_' . $new_element_id));
								$query->set('published = ' . $element->element->published);
								$query->set('params = ' . $this->db->quote(json_encode($el_params)));
								$query->where('id =' . $new_element_id);
								$this->db->setQuery($query);
								$this->db->execute();
							}
							catch (Exception $e) {
								Log::add('component/com_emundus/models/formbuilder | Error at create a page from the model ' . $form_id_to_copy . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
							}
						}

						$groups_copied[$g_index] = true;
					}
				}
			}

			$copied = !in_array(false, $groups_copied);
		}

		return $copied;
	}

	public function getDocumentSample($attachment_id, $profile_id)
	{
		$document = [];

		if (!empty($attachment_id) && !empty($profile_id)) {
			
			$query = $this->db->getQuery(true);

			$query->select('has_sample, sample_filepath')
				->from('#__emundus_setup_attachment_profiles')
				->where($this->db->quoteName('attachment_id') . ' = ' . $attachment_id)
				->andWhere($this->db->quoteName('profile_id') . ' = ' . $profile_id);

			try {
				$this->db->setQuery($query);
				$document = $this->db->loadAssoc();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/formbuilder | Error at getting document sample : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		if (!is_array($document)) $document = array();

		return $document;
	}

	public function getSqlDropdownOptions($table,$key,$value,$translate)
	{
		$datas = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$tables_allowed = [];
		$query->select('database_name')
			->from($db->quoteName('#__emundus_datas_library'));
		$db->setQuery($query);
		$tables_allowed = $db->loadColumn();

		if(!in_array($table, $tables_allowed)) {
			return $datas;
		}

		try {
			if($translate) {
				$query->clear()
					->select('sef')
					->from($db->quoteName('#__languages'))
					->where($db->quoteName('lang_code') . ' = ' . $db->quote(Factory::getApplication()->getLanguage()->getTag()));
				$db->setQuery($query);
				$language = $db->loadResult();

				$query->clear()
					->select($key . ' as value, ' . $value . '_' . $language . ' as label');
			} else {
				$query->clear()
					->select($key . ' as value, ' . $value . ' as label');
			}

			$query->from($db->quoteName($table));
			$db->setQuery($query);
			$datas = $db->loadAssocList();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at getting sql dropdown options : ' . preg_replace("/[\r\n]/"," ",$query->__toString().' -> '.$e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $datas;
	}

	public function updateElementParam($element_id, $param, $value)
	{
		$updated = false;

		if (!empty($element_id) && !empty($param) && isset($value)) {
			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__fabrik_elements'))
				->set($this->db->quoteName($param) . ' = ' . $this->db->quote($value))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($element_id));

			try {
				$this->db->setQuery($query);
				$updated = $this->db->execute();
			}
			catch (Exception $e) {
				Log::add('component/com_emundus/models/formbuilder | Error at updating element param : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $updated;
	}

	public function getCurrencies(int $published = 1): array
	{
		$currencies = [];
		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->db->quoteName('data_currency'))
			->where('published = ' . $this->db->quote($published))
			->order('name ASC');

		try {
			$this->db->setQuery($query);
			$currencies = $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at getting currencies : ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
		}

		return $currencies;
	}


	public function getCurrencyListOptions(): array
	{
		$currencies = [];

		$query = $this->db->createQuery();

		$query->select('iso3 as value, CONCAT(name, " (", symbol,")") as label')
			->from($this->db->quoteName('data_currency'))
			->where('published = 1')
			->order('name ASC');

		try {
			$this->db->setQuery($query);
			$currencies = $this->db->loadObjectList();
		}
		catch (Exception $e) {
			Log::add('component/com_emundus/models/formbuilder | Error at getting currency list options : ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
		}

		return $currencies;
	}
}
