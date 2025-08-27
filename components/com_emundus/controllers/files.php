<?php
/**
 * @package    Joomla
 * @subpackage eMundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Emundus\Plugin\SampleData\Emundus\Extension\Emundus;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\ParameterType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Joomla\CMS\Factory;
use Tchooz\Traits\TraitDispatcher;
use \Tchooz\Traits\TraitResponse;

use Gotenberg\Gotenberg;
use Gotenberg\Stream;

jimport('joomla.application.component.controller');
jimport('joomla.user.helper');

/**
 * eMundus Component Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 */

/**
 * Class EmundusControllerFiles
 */
class EmundusControllerFiles extends BaseController
{
	protected $app;

	private $_user;
	private $_db;

	use TraitResponse;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'filters.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'list.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'emails.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'export.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'menu.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'admission.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'evaluation.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'application.php');
        require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'programme.php');

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getSession()->get('emundusUser');
		$this->_db = Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *                   @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @return  DisplayController  This object to support chaining.
	 *
	 * @since   1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view')) {
			$default = 'files';
			$this->input->set('view', $default);
		}

		parent::display();
	}

	function data_to_img($match)
	{
		list(, $img, $type, $base64, $end) = $match;

		$bin = base64_decode($base64);
		$md5 = md5($bin);   // generate a new temporary filename
		$fn  = "tmp/$md5.$type";
		file_exists($fn) or file_put_contents($fn, $bin);

		return "$img$fn$end";  // new <img> tag
	}

////// EMAIL APPLICANT WITH CUSTOM MESSAGE///////////////////

	/**
	 *
	 */
	public function applicantemail()
	{
		if (EmundusHelperAccess::asAccessAction(9, 'c')) {
			require_once(JPATH_SITE . '/components/com_emundus/helpers/emails.php');
			$h_emails = new EmundusHelperEmails;
			$h_emails->sendApplicantEmail();
		}
	}

	/**
	 *
	 */
	public function groupmail()
	{
		if (EmundusHelperAccess::asAccessAction(16, 'c'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/helpers/emails.php');
			$h_emails = new EmundusHelperEmails;
			$h_emails->sendGroupEmail();
		}
	}

	/**
	 *
	 */
	public function clear()
	{
		EmundusHelperFiles::clear();
		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function applyfilters()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id)) {
			$filters              = $this->input->getString('filters', '');
			$quick_search_filters = $this->input->getString('search_filters', '');

			if (!empty($filters)) {
				$filters              = json_decode($filters, true);
				$quick_search_filters = json_decode($quick_search_filters, true);
				$session              = $this->app->getSession();
				$session->set('em-applied-filters', $filters);
				$session->set('em-quick-search-filters', $quick_search_filters);
				$session->set('limitstart', 0);

				$filter_fabrik_element_ids = [];
				foreach ($filters as $filter) {
					if (is_numeric($filter['id']) && !in_array($filter['id'], $filter_fabrik_element_ids)) {
						$filter_fabrik_element_ids[] = $filter['id'];
					}
				}
				$session->set('adv_cols', $filter_fabrik_element_ids);
				$session->set('last-filters-use-advanced', true);
				$response = ['status' => true, 'msg' => Text::_('FILTERS_APPLIED')];
			}
			else {
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 *
	 */
	public function setfilters()
	{
		$filterName = $this->input->getString('id', null);
		$elements   = $this->input->getString('elements', null);
		$multi      = $this->input->getString('multi', null);

		EmundusHelperFiles::clearfilter();

		if ($multi == "true") {
			$filterval = $this->input->get('val', array(), 'ARRAY');
		}
		else {
			$filterval = $this->input->getString('val', null);
		}

		$session = $this->app->getSession();
		$params  = $session->get('filt_params');

		if ($elements == 'false') {
			$params[$filterName] = $filterval;
		}
		else {
			$vals = (array) json_decode(stripslashes($filterval));
			if (count($vals) > 0) {
				foreach ($vals as $val) {
					if ($val->adv_fil) {
						$params['elements'][$val->name]['value']  = $val->value;
						$params['elements'][$val->name]['select'] = $val->select;
					}
					else {
						$params[$val->name] = $val->value;
					}
				}

			}
			else {
				$params['elements'][$filterName]['value'] = $filterval;
			}
		}
		$session->set('last-filters-use-advanced', false);
		$session->set('filt_params', $params);

		echo json_encode((object) (array('status' => true)));
		exit();
	}

	/**
	 * @throws Exception
	 */
	public function loadfilters()
	{
		$status = false;

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)){
			try {
				$id = $this->input->getInt('id', null);

				$session = $this->app->getSession();

				$h_files                 = new EmundusHelperFiles;
				$filter                  = $h_files->getEmundusFilters($id);
				$params                  = (array) json_decode($filter->constraints);
				$params['select_filter'] = $id;
				$params                  = json_decode($filter->constraints, true);

				$session->set('select_filter', $id);

				if (isset($params['filter_order'])) {
					$session->set('filter_order', $params['filter_order']);
					$session->set('filter_order_Dir', $params['filter_order_Dir']);
				}

				$session->set('filt_params', $params['filter']);

				if (!empty($params['col']))
					$session->set('adv_cols', $params['col']);

				$status = true;
			}
			catch (Exception $e) {
				throw new Exception;
			}
		}


		echo json_encode((object) (array('status' => $status)));
		exit();
	}

	/**
	 *
	 */
	public function order()
	{
		$order = $this->input->getString('filter_order', null);

		$session      = $this->app->getSession();
		$ancientOrder = $session->get('filter_order');
		$params       = $session->get('filt_params');
		$session->set('filter_order', $order);

		$params['filter_order'] = $order;

		if ($order == $ancientOrder) {

			if ($session->get('filter_order_Dir') == 'desc') {

				$session->set('filter_order_Dir', 'asc');
				$params['filter_order_Dir'] = 'asc';

			}
			else {

				$session->set('filter_order_Dir', 'desc');
				$params['filter_order_Dir'] = 'desc';

			}

		}
		else {

			$session->set('filter_order_Dir', 'asc');
			$params['filter_order_Dir'] = 'asc';

		}

		$session->set('filt_params', $params);
		echo json_encode((object) (array('status' => true)));
		exit;
	}

	/**
	 *
	 */
	public function setlimit()
	{
		$limit = $this->input->getInt('limit', null);

		$session = $this->app->getSession();
		$session->set('limit', $limit);
		$session->set('limitstart', 0);

		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function savefilters()
	{
		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$name   = $this->input->getString('name', null);
			$itemid = $this->input->getInt('Itemid', 0);

			if (!empty($name) && !empty($itemid)) {
				$session     = JFactory::getSession();
				$filt_params = $session->get('filt_params');
				$adv_params  = $session->get('adv_cols');
				$constraints = array('filter' => $filt_params, 'col' => $adv_params);
				$constraints = json_encode($constraints);
				$time_date   = (date('Y-m-d H:i:s'));

				$query = "INSERT INTO #__emundus_filters (time_date,user,name,constraints,item_id) values('" . $time_date . "'," . $this->_user->id . ",'" . $name . "'," . $this->_db->quote($constraints) . "," . $itemid . ")";
				$this->_db->setQuery($query);
				try {
					$this->_db->Query();
					$query = 'select f.id, f.name from #__emundus_filters as f where f.time_date = "' . $time_date . '" and user = ' . $this->_user->id . ' and name="' . $name . '" and item_id="' . $itemid . '"';
					$this->_db->setQuery($query);
					$result = $this->_db->loadObject();

					echo json_encode((object) (array('status' => true, 'filter' => $result)));
					exit;

				}
				catch (Exception $e) {
					Log::add('Error saving filter: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
				}
			}
		}

		echo json_encode((object) (array('status' => false)));
		exit;
	}


	/**
	 *
	 */
	public function newsavefilters()
	{
		$response = ['status' => false, 'msg' => 'MISSING_PARAMS'];

		if(EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$name    = $this->input->getString('name', null);
			$filters = $this->input->getString('filters', null);
			$item_id = $this->input->getInt('item_id', 0);

			if (!empty($name) && !empty($filters))
			{
				$m_files = $this->getModel('Files');
				$saved   = $m_files->saveFilters($this->_user->id, $name, $filters, $item_id);

				if ($saved)
				{
					$response = ['status' => true, 'msg' => 'FILTER_SAVED'];
				}
				else
				{
					$response = ['status' => false, 'msg' => 'FILTER_NOT_SAVED'];
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	public function getsavedfilters()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (!empty($this->_user->id)) {
			$item_id = $this->input->getInt('item_id', 0);

			$m_files = $this->getModel('Files');
			$filters = $m_files->getSavedFilters($this->_user->id, $item_id);

			$response = ['status' => true, 'msg' => 'FILTERS_LOADED', 'data' => $filters];
		}

		echo json_encode($response);
		exit;
	}

	public function updatefilter()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (!empty($this->_user->id)) {
			$item_id   = $this->input->getInt('item_id', 0);
			$filter_id = $this->input->getInt('id', 0);
			$filters   = $this->input->getString('filters', null);

			if (!empty($filters) && !empty($filter_id)) {
				$m_files = $this->getModel('Files');
				$updated = $m_files->updateFilter($this->_user->id, $filter_id, $filters, $item_id);

				$response = ['status' => $updated, 'msg' => 'FILTER_UPDATED'];
			}
			else {
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 *
	 */
	public function deletefilters()
	{
		$deleted   = false;
		$filter_id = $this->input->getInt('id', 0);

		if (!empty($filter_id) && !empty($this->_user->id)) {
			$m_files = $this->getModel('Files');
			$deleted = $m_files->deleteFilter($filter_id,$this->_user->id);
		}

		echo json_encode((object) (array('status' => $deleted)));
		exit;
	}

	/**
	 *
	 */
	public function setlimitstart()
	{
		$session    = $this->app->getSession();

		$limistart  = $this->input->getInt('limitstart', null);

		$limit      = intval($session->get('limit'));
		$limitstart = ($limit != 0 ? ($limistart > 1 ? (($limistart - 1) * $limit) : 0) : 0);

		$session->set('limitstart', $limitstart);

		echo json_encode((object) (array('status' => true)));
		exit;
	}

	/**
	 * @throws Exception
	 */
	public function getadvfilters()
	{
		$result = [
			'status' => false,
			'default' => Text::_('COM_EMUNDUS_PLEASE_SELECT'),
			'defaulttrash' => Text::_('REMOVE_SEARCH_ELEMENT'),
			'options' => []
		];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			if (!$this->_user->guest) {
				$h_files = new EmundusHelperFiles;

				try
				{
					$result['options'] = $h_files->getElements();
					$result['status']  = true;

				}
				catch (Exception $e)
				{
					Log::add(Text::_('COM_EMUNDUS_ERROR') . ' : ' . $e->getMessage(), Log::ERROR, 'emundus');
				}
			}
		}

		echo json_encode((object) $result);
		exit;
	}

	/**
	 * @throws Exception
	 */
	public function getbox()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			try {
				$id    = $this->input->getInt('id', null);
				$index = $this->input->getInt('index', null);

				$session = JFactory::getSession();
				$params  = $session->get('filt_params');

				$h_files = new EmundusHelperFiles;
				$element = $h_files->getElementsName($id);

				$tab_name                 = (isset($element[$id]->table_join) ? $element[$id]->table_join : $element[$id]->tab_name);
				$key                      = $tab_name . '.' . $element[$id]->element_name;
				$params['elements'][$key] = '';

				$advCols = $session->get('adv_cols');

				if (!$session->has('adv_cols') || count($advCols) == 0) {
					$advCols = array($index => $id);
				}
				else {
					$advCols = $session->get('adv_cols');
					if (isset($advCols[$index])) {
						$lastId = @$advCols[$index];
						if (!in_array($id, $advCols)) {
							$advCols[$index] = $id;
						}
						if (array_key_exists($index, $advCols)) {
							$lastElt  = $h_files->getElementsName($lastId);
							$tab_name = (isset($lastElt[$lastId]->table_join) ? $lastElt[$lastId]->table_join : $lastElt[$lastId]->tab_name);
							unset($params['elements'][$tab_name . '.' . $lastElt[$lastId]->element_name]);
						}
					}
					else {
						$advCols[$index] = $id;
					}
				}
				$session->set('filt_params', $params);
				$session->set('adv_cols', $advCols);

				$html = $h_files->setSearchBox($element[$id], '', $tab_name . '.' . $element[$id]->element_name, $index);

				$response = array('status' => true, 'default' => Text::_('COM_EMUNDUS_PLEASE_SELECT'), 'defaulttrash' => Text::_('REMOVE_SEARCH_ELEMENT'), 'html' => $html);
			}
			catch (Exception $e) {
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 *
	 */
	public function deladvfilter()
	{
		$name = $this->input->getString('elem', null);
		$id   = $this->input->getInt('id', null);

		$session = JFactory::getSession();
		$params  = $session->get('filt_params');
		$advCols = $session->get('adv_cols');
		unset($params['elements'][$name]);
		unset($advCols[$id]);
		$session->set('filt_params', $params);
		$session->set('adv_cols', $advCols);

		echo json_encode((object) (array('status' => true)));
		exit;
	}

	public function getFileIdFromFnum()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$fnum = $this->input->getString('fnum', null);
			$ccid = EmundusHelperFiles::getIdFromFnum($fnum);

			if (!empty($ccid)) {
				$response['status'] = true;
				$response['code'] = 200;
				$response['msg'] = Text::_('SUCCESS');
				$response['data'] = $ccid;
			}
		}

		if ($response['code'] === 403) {
			header('HTTP/1.1 403 Forbidden');
			echo $response['msg'];
			exit;
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Add a comment on a file.
	 *
	 * @since version 1.0.0
	 */
	public function addcomment() {
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asAccessAction(10, 'c', $this->_user->id)) {
			$fnums  = $this->input->getString('fnums', null);
			$title  = $this->input->getString('title', '');
			$comment = $this->input->getString('comment', null);

			if (!empty($comment) && !empty($fnums)) {
				$fnums = (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);
				$fnumErrorList = [];

				if (!class_exists('EmundusModelComments')) {
					require_once (JPATH_ROOT . '/components/com_emundus/models/comments.php');
				}
				$m_comments = new EmundusModelComments();

				foreach ($fnums as $fnum) {
					if (EmundusHelperAccess::asAccessAction(10, 'c', $this->_user->id, $fnum)) {
						$aid = intval(substr($fnum, 21, 7));
						$comment_content = array(
							'applicant_id' => $aid,
							'user_id' => $this->_user->id,
							'reason' => $title,
							'comment_body' => $comment,
							'fnum' => $fnum,
							'status_from' => -1,
							'status_to' => -1
						);

						if(!empty($title))
						{
							$comment = $title . ' ' . $comment;
						}

						PluginHelper::importPlugin('emundus', 'custom_event_handler');
						$this->app->triggerEvent('onBeforeCommentAdd', [$comment_content]);
						$this->app->triggerEvent('onCallEventHandler', ['onBeforeCommentAdd', ['comment' => $comment_content]]);

						$ccid = EmundusHelperFiles::getIdFromFnum($fnum);
						$new_comment_id = $m_comments->addComment($ccid, $comment, [], 0, 0, $this->_user->id);
						if (empty($new_comment_id)) {
							$fnumErrorList[] = $fnum;
						}
					} else {
						$fnumErrorList[] = $fnum;
					}
				}

				if (empty($fnumErrorList)) {
					$response['status'] = true;
					$response['code'] = 200;
					$response['msg'] = Text::_('COM_EMUNDUS_COMMENTS_SUCCESS');
					$response['id'] = $new_comment_id;
				} else {
					$response['code'] = 500;
					$response['msg'] = Text::_('COM_EMUNDUS_ERROR') . implode(', ', $fnumErrorList);
				}
			}
		}

		if ($response['code'] === 403) {
			header('HTTP/1.1 403 Forbidden');
			echo $response['msg'];
			exit;
		}

		echo json_encode($response);
		exit;
	}

	/*
     * Gets all tags.
     * @since 6.0
     */
	public function gettags()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED'), 'tags' => null];
		$user     = JFactory::getUser();

		if (EmundusHelperAccess::asAccessAction(14, 'c', $user->id)) {
			$m_files          = $this->getModel('Files');
			$response['tags'] = $m_files->getAllTags();

			if (!empty($response['tags'])) {
				$response['code']       = 200;
				$response['status']     = true;
				$response['msg']        = Text::_('SUCCESS');
				$response['tag']        = Text::_('COM_EMUNDUS_TAGS');
				$response['select_tag'] = Text::_('COM_EMUNDUS_FILES_PLEASE_SELECT_TAG');

				$params                         = JComponentHelper::getParams('com_emundus');
				$response['show_tags_category'] = $params->get('com_emundus_show_tags_category', 0);
			}
			else {
				$response['code'] = 500;
				$response['msg']  = Text::_('FAIL');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Add a tag to an application.
	 * @since 6.0
	 */
	public function tagfile()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('BAD_REQUEST')];

		$fnums = $this->input->getString('fnums', null);
		$tag   = (array) $this->input->get('tag', []);

		if (!empty($fnums) && !empty($tag)) {
			$m_files = $this->getModel('Files');
			$fnums   = ($fnums == 'all') ? $m_files->getAllFnums() : (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);

			if (!empty($fnums)) {
				$validFnums = [];
				foreach ($fnums as $fnum) {
					if ($fnum != 'em-check-all' && EmundusHelperAccess::asAccessAction(14, 'c', $this->_user->id, $fnum)) {
						$validFnums[] = $fnum;
					}
				}
				unset($fnums);
				$response['status'] = $m_files->tagFile($validFnums, $tag);

				if ($response['status']) {
					$response['code']   = 200;
					$response['msg']    = Text::_('COM_EMUNDUS_TAGS_SUCCESS');
					$response['tagged'] = $validFnums;
				}
				else {
					$response['code'] = 500;
					$response['msg']  = Text::_('FAIL');
				}
			}
		}

		echo json_encode((object) ($response));
		exit;
	}

	public function deletetags()
	{

		$fnums = $this->input->getString('fnums', null);
		$tags  = $this->input->getVar('tag', null);

		$fnums = ($fnums == 'all') ? 'all' : (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);

		$m_files       = $this->getModel('Files');
		$m_application = $this->getModel('Application');

		if ($fnums == "all") {
			$fnums = $m_files->getAllFnums();
		}

		JPluginHelper::importPlugin('emundus');


		$this->app->triggerEvent('onCallEventHandler', ['onBeforeTagRemove', ['fnums' => $fnums, 'tags' => $tags]]);

		foreach ($fnums as $fnum) {
			if ($fnum != 'em-check-all') {
				foreach ($tags as $tag) {
					$hastags = $m_files->getTagsByIdFnumUser($tag, $fnum, $this->_user->id);
					if ($hastags) {
						$m_application->deleteTag($tag, $fnum);
					}
					else {
						if (EmundusHelperAccess::asAccessAction(14, 'd', $this->_user->id, $fnum)) {
							$m_application->deleteTag($tag, $fnum);
						}
					}
				}
			}
		}

		$this->app->triggerEvent('onCallEventHandler', ['onAfterTagRemove', ['fnums' => $fnums, 'tags' => $tags]]);

		unset($fnums);
		unset($tags);

		echo json_encode((object) (array('status' => true, 'msg' => Text::_('COM_EMUNDUS_TAGS_DELETE_SUCCESS'))));
		exit;
	}


	/**
	 *
	 */
	public function share()
	{

		$actions = $this->input->getString('actions', null);
		$groups  = $this->input->getString('groups', null);
		$evals   = $this->input->getString('evals', null);
		$notify  = $this->input->getVar('notify', 'false');
		$itemId  = $this->input->getInt('Itemid', 0);

		$actions = (array) json_decode(stripslashes($actions));

		$m_files = $this->getModel('Files');

		$fnums_post = $this->input->getString('fnums', null);
		$fnums      = ($fnums_post) == 'all' ? $m_files->getAllFnums(false, $this->_user->id, $itemId) : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);

		$validFnums = array();
		foreach ($fnums as $fnum) {
			if ($fnum != 'em-check-all' && EmundusHelperAccess::asAccessAction(11, 'c', $this->_user->id, $fnum)) {
				$validFnums[] = $fnum;
			}
		}

		unset($fnums);
		if (count($validFnums) > 0) {
			if (!empty($groups)) {
				$groups = (array) json_decode(stripslashes($groups));
				$res    = $m_files->shareGroups($groups, $actions, $validFnums);
			}

			if (!empty($evals)) {
				$evals = (array) json_decode(stripslashes($evals));
				$res   = $m_files->shareUsers($evals, $actions, $validFnums);
			}

			if ($res !== false) {
				$msg = Text::_('COM_EMUNDUS_ACCESS_SHARE_SUCCESS');
			}
			else {
				$msg = Text::_('COM_EMUNDUS_ACCESS_SHARE_ERROR');
			}
		} else {
			$msg = Text::_('COM_EMUNDUS_ACCESS_SHARE_ERROR');
			echo json_encode((object) (array('status' => '0', 'msg' => $msg)));
			exit;
		}

		if ($notify !== 'false' && $res !== false && !empty($evals)) {

			if (empty($fnums)) {
				$fnums = $validFnums;
			}

			require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'controllers' . DS . 'messages.php');
			require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'users.php');
			require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');

			$c_messages = new EmundusControllerMessages();
			$m_users    = $this->getModel('Users');
			$m_profile  = $this->getModel('Profile');

			$evals = $m_users->getUsersByIds($evals);

			$menu = $this->app->getMenu();

			$fnums = $m_files->getFnumsInfos($fnums);

			foreach ($evals as $eval) {

				$menutype = $m_profile->getProfileByApplicant($eval->id)['menutype'];
				$items    = $menu->getItems('menutype', $menutype);

				if (empty($items)) {
					echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
					exit;
				}

				// We're getting the first link in the user's menu that's from com_emundus, which is PROBABLY a files/evaluation view, but this does not guarantee it.
				/* this methode does not word at all, it get a random link from invited evaluator
                $index = 0;
                foreach ($items as $k => $item) {
                    if ($item->component === 'com_emundus') {
                        $index = $k;
                        break;
                    }
                }

                if (JFactory::getConfig()->get('sef') == 1) {
                    $userLink = $items[$index]->alias;
                } else {
                    $userLink = $items[$index]->link.'&Itemid='.$items[0]->id;
                }
                */
				$fnumList = '<ul>';
				foreach ($fnums as $fnum) {
					//$fnumList .= '<li><a href="'.JURI::base().$userLink.'#'.$fnum['fnum'].'|open">'.$fnum['name'].' ('.$fnum['fnum'].')</a></li>';
					$fnumList            .= '<li><a href="' . JURI::base() . '#' . $fnum['fnum'] . '|open">' . $fnum['name'] . ' (' . $fnum['fnum'] . ')</a></li>';
					$campaign_label      = $fnums[$fnum['fnum']]['label'];
					$campaign_start_date = $fnums[$fnum['fnum']]['start_date'];
					$campaign_end_date   = $fnums[$fnum['fnum']]['end_date'];
					$campaign_year       = $fnums[$fnum['fnum']]['year'];
				}
				$fnumList .= '</ul>';

				$post = [
					'FNUMS'          => $fnumList,
					'NAME'           => $eval->name,
					'SITE_URL'       => JURI::base(),
					'CAMPAIGN_LABEL' => $campaign_label,
					'CAMPAIGN_YEAR'  => $campaign_year,
					'CAMPAIGN_START' => $campaign_start_date,
					'CAMPAIGN_END'   => $campaign_end_date
				];
				$c_messages->sendEmailNoFnum($eval->email, 'share_with_evaluator', $post, $eval->id, null, $fnum['fnum']);
			}
		}

		echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
		exit;
	}

	/**
	 *
	 */
	public function getstate()
	{
		$response = ['status' => false, 'msg'=> Text::_('ACCESS_DENIED')];
		$user = $this->app->getIdentity();

		if (!$user->guest) {

			$m_files = $this->getModel('Files');
			$states  = $m_files->getAllStatus();
			$selected_state = $this->app->getSession()->get('last_status_selected',0);

			if(empty($selected_state)) {
				$fnum = $this->input->getString('fnum', null);
				if (!empty($fnum)) {
					$state          = $m_files->getStatusByFnums([$fnum]);
					$selected_state = $state[$fnum]['status'];
				}
			}

			$response = [
				'status' => true,
				'states' => $states,
				'state' => Text::_('COM_EMUNDUS_STATE'),
				'select_state' => Text::_('PLEASE_SELECT_STATE'),
				'selected_state' => $selected_state
            ];
		}

		echo json_encode((object)$response);
		exit;
	}

	/**
	 *
	 */
	public function getpublish()
	{
		$publish = array(
			0 =>
				array(
					'id'       => '1',
					'step'     => '1',
					'value'    => Text::_('COM_EMUNDUS_APPLICATION_PUBLISHED'),
					'ordering' => '1'
				),
			1 =>
				array(
					'id'       => '0',
					'step'     => '0',
					'value'    => Text::_('COM_EMUNDUS_APPLICATION_ARCHIVED'),
					'ordering' => '2'
				),
			3 =>
				array(
					'id'       => '3',
					'step'     => '-1',
					'value'    => Text::_('COM_EMUNDUS_APPLICATION_TRASHED'),
					'ordering' => '3'
				)
		);

		echo json_encode((object) (array('status'         => true,
		                                 'states'         => $publish,
		                                 'state'          => Text::_('COM_EMUNDUS_APPLICATION_PUBLISH'),
		                                 'select_publish' => Text::_('PLEASE_SELECT_PUBLISH'))));
		exit;
	}

	/**
	 *
	 */
	public function getExistEmailTrigger()
	{
		$trigger_emails = [];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			require_once(JPATH_SITE . '/components/com_emundus/models/emails.php');

			$state        = $this->input->getInt('state', null);
			$fnums        = $this->input->getString('fnums', null);
			$to_applicant = $this->input->getString('to_applicant', '0,1');

			$m_email = $this->getModel('Emails');
			$m_files = $this->getModel('Files');

			if ($fnums == "all") {
				$fnums = $m_files->getAllFnums();
			}

			if (!is_array($fnums)) {
				$fnums = (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);
			}

			if (count($fnums) == 0 || !is_array($fnums)) {
				$res = false;
				$msg = Text::_('STATE_ERROR');

				echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
				exit;
			}

			$validFnums = array();

			foreach ($fnums as $fnum) {
				if (EmundusHelperAccess::asAccessAction(13, 'u', $this->_user->id, $fnum)) {
					$validFnums[] = $fnum;
				}
			}

			$fnumsInfos = $m_files->getFnumsInfos($validFnums);

			$codes = array();
			foreach ($fnumsInfos as $fnum) {
				$codes[] = $fnum['training'];
			}

			$trigger_emails = $m_email->getEmailTrigger($state, $codes, $to_applicant);
			// If the trigger does not have the applicant as recipient for a manager action AND has no other recipients, given the context is a manager action,
			// we therefore remove the trigger from the list.
			foreach ($trigger_emails as $key => $trigger) {
				foreach ($trigger as $code => $data) {
					if ($data['to']['to_applicant'] == 0 && empty($data['to']['recipients'])) {
						unset($trigger_emails[$key][$code]);
					}
				}

				if (empty($trigger_emails[$key])) {
					unset($trigger_emails[$key]);
				}
			}
		}

		echo json_encode((object)(array('status' => !empty($trigger_emails), 'msg' => Text::sprintf('COM_EMUNDUS_APPLICATION_MAIL_CHANGE_STATUT_INFO', sizeof($validFnums)))));
		exit;
	}

	/**
	 *
	 */
	public function updatestate()
	{
		$fnums = $this->input->getString('fnums', null);
		$state = $this->input->getInt('state', null);
		$this->app->getSession()->set('last_status_selected', $state);

		$m_files = $this->getModel('Files');

		if ($fnums == "all") {
			$fnums = $m_files->getAllFnums();
		}

		if (!is_array($fnums)) {
			$fnums = (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);
		}

		if (count($fnums) == 0 || !is_array($fnums)) {
			$res = false;
			$msg = Text::_('STATE_ERROR');

			echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
			exit;
		}

		$validFnums = array();

		foreach ($fnums as $fnum) {
			if (EmundusHelperAccess::asAccessAction(13, 'u', $this->_user->id, $fnum)) {
				$validFnums[] = $fnum;
			}
		}

		$res = $m_files->updateState($validFnums, $state, $this->_user->id);
		$msg = '';

		if (is_array($res)) {
			$msg = isset($res['msg']) ? $res['msg'] : '';
			$res = isset($res['status']) ? $res['status'] : true;
		}

		if ($res !== false) {
			$msg .= Text::_('COM_EMUNDUS_APPLICATION_STATE_SUCCESS');
		}
		else {
			$msg = empty($msg) ? Text::_('STATE_ERROR') : $msg;
		}

		echo json_encode(array('status' => $res, 'msg' => $msg));
		exit;
	}

	public function updatepublish()
	{

		$publish = $this->input->getInt('publish', null);

		$m_files = $this->getModel('Files');

		$fnums_post  = $this->input->getString('fnums', null);
		$fnums_array = ($fnums_post == 'all') ? 'all' : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);

		if ($fnums_array == 'all') {
			$fnums = $m_files->getAllFnums();
		}
		else {
			$fnums = array();
			foreach ($fnums_array as $value) {
				$fnums[] = $value;
			}
		}

		$validFnums = [];

		foreach ($fnums as $fnum) {
			if (is_numeric($fnum) && EmundusHelperAccess::asAccessAction(13, 'u', $this->_user->id, $fnum))
				$validFnums[] = $fnum;
		}
		$res = $m_files->updatePublish($validFnums, $publish);

		if ($res !== false) {
			$msg = Text::_('COM_EMUNDUS_APPLICATION_PUBLISHED_STATE_SUCCESS');
		}
		else $msg = Text::_('STATE_ERROR');

		echo json_encode((object) (array('status' => $res, 'msg' => $msg)));
		exit;
	}

	/**
	 *
	 */
	public function unlinkevaluators()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$fnum  = $this->input->getString('fnum', null);
			$id    = $this->input->getint('id', null);
			$group = $this->input->getString('group', null);

			$m_files = $this->getModel('Files');

			if ($group == "true")
			{
				$res = $m_files->unlinkEvaluators($fnum, $id, true);
			} else
			{
				$res = $m_files->unlinkEvaluators($fnum, $id, false);
			}

			if ($res)
			{
				$msg = Text::_('SUCCESS_SUPPR_EVAL');
			} else
			{
				$msg = Text::_('ERROR_SUPPR_EVAL');
			}

			$response = ['status' => $res, 'msg' => $msg];
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 *
	 */
	public function getfnuminfos() {
		$response = ['status' => false, 'fnumInfos' => '', 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];
		$user_id = $this->_user->id;

		if (!empty($user_id)) {
			$fnum = $this->input->getString('fnum', '');

			if (!empty($fnum) && EmundusHelperAccess::isUserAllowedToAccessFnum($user_id, $fnum)) {
				$m_files = new EmundusModelFiles();
				$response['fnumInfos'] = $m_files->getFnumInfos($fnum);
				$response['code'] = 200;

				if (!empty($response['fnumInfos'])) {
					$response['status'] = true;
					$this->app->getSession()->set('application_fnum', $fnum);
				}
			}
		}

		if ($response['code'] == 403) {
			header('HTTP/1.1 403 Forbidden');
			echo Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS');
			exit;
		}

		echo json_encode((object)($response));
		exit;
	}

	/**
	 *
	 */
	public function deletefile()
	{
		$fnum    = $this->input->getString('fnum', null);
		$m_files = $this->getModel('Files');
		if (EmundusHelperAccess::asAccessAction(1, 'd', $this->_user->id, $fnum))
			$res = $m_files->changePublished($fnum);
		else
			$res = false;

		$result = array('status' => $res);
		echo json_encode((object) $result);
		exit;
	}

	/**
	 *
	 */
	public function removefile()
	{
		$fnum = $this->input->post->getString('fnum', null);

		$m_files = $this->getModel('Files');
		if (EmundusHelperAccess::asAccessAction(1, 'd', $this->_user->id, $fnum)) {
			$res = $m_files->deleteFile($fnum);
		}
		else {
			$res = false;
		}

		$result = array('status' => $res);
		echo json_encode((object) $result);
		exit;
	}

	/**
	 *
	 */
	public function getformelem()
	{
		$res = array('status' => false, 'elts' => [], 'defaults' => []);

		if(EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			//Filters
			$m_files = $this->getModel('Files');
			$h_files = new EmundusHelperFiles;

			$code = $this->input->getString('code', null);
			$camp = $this->input->getString('camp', null);

			$code = explode(",", $code);
			$camp = explode(",", $camp);

			$profile = $this->input->getInt('profile', 0);

			$res['defaults'] = $m_files->getDefaultElements();
			if (!empty($res['defaults']))
			{
				$res['defaults'] = array_map(function ($value) {
					$value->element_label = Text::_($value->element_label);

					return $value;
				}, $res['defaults']);
			}

			$res['elts'] = $h_files->getElements($code, $camp, [], $profile);
			$res['status'] = true;
		}

		echo json_encode((object) $res);
		exit;
	}


	/**
	 *
	 */
	public function zip()
	{
		$response = ['status' => false, 'msg' => Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS')];

		require_once(JPATH_SITE . '/components/com_emundus/helpers/access.php');
		$current_user = JFactory::getUser();

		if (EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) {
			$forms      = $this->input->getInt('forms', 0);
			$attachment = $this->input->getInt('attachment', 0);
			$eval_steps = $this->input->getString('eval_steps', '');
			$eval_steps = !empty($eval_steps) ? json_decode($eval_steps, true) : [];
			$formids    = $this->input->getVar('formids', null);
			$attachids  = $this->input->getVar('attachids', null);
			$options    = $this->input->getVar('options', null);
			$params     = $this->input->getString('params', null);
			$params     = !empty($params) ? json_decode($params, true) : [];

			$m_files = $this->getModel('Files');

			$fnums_post  = $this->input->getVar('fnums', null);
			$fnums_array = ($fnums_post == 'all') ? 'all' : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);

			if ($fnums_array == 'all') {
				$fnums = $m_files->getAllFnums();
			}
			else {
				$fnums = array();
				foreach ($fnums_array as $key => $value) {
					$fnums[] = $value;
				}
			}

			$validFnums = array();
			foreach ($fnums as $fnum) {
				if (EmundusHelperAccess::asAccessAction(6, 'c', $this->_user->id, $fnum)) {
					$validFnums[] = $fnum;
				}
			}


			if (extension_loaded('zip')) {
				$name = $m_files->exportZip($validFnums, $forms, $attachment, $eval_steps, $formids, $attachids, $options, false, $current_user, $params);
			}
			else {
				$name = $this->export_zip_pcl($validFnums);
			}

			$response = ['status' => true, 'name' => $name, 'msg' => ''];
		}

		echo json_encode((object) $response);
		exit();
	}

	/**
	 * @param $val
	 *
	 * @return int|string
	 */
	public function return_bytes($val)
	{
		$val  = trim($val);
		$last = strtolower($val[strlen($val) - 1]);

		switch ($last) {
			// Le modifieur 'G' est disponible depuis PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}

	/**
	 * @param $array
	 * @param $orderArray
	 *
	 * @return array
	 */
	public function sortArrayByArray($array, $orderArray)
	{
		$ordered = array();

		foreach ($orderArray as $key) {
			if (array_key_exists($key, $array)) {
				$ordered[$key] = $array[$key];
				unset($array[$key]);
			}
		}

		return $ordered + $array;
	}

	/**
	 * Create temp CSV file for XLS extraction
	 * @return String json
	 */
	public function create_file_csv() {
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$response['code'] = 500;
			$today  = date("MdYHis");
			$name   = md5($today.rand(0,10));
			$name   = $name.'.csv';
			$chemin = JPATH_SITE.DS.'tmp'.DS.$name;

			if (!$fichier_csv = fopen($chemin, 'w+')) {
				$response['msg'] = Text::_('ERROR_CANNOT_OPEN_FILE').' : '.$chemin;
			} else {
				fprintf($fichier_csv, chr(0xEF).chr(0xBB).chr(0xBF));
				if (!fclose($fichier_csv)) {
					$response['msg'] = Text::_('COM_EMUNDUS_EXPORTS_ERROR_CANNOT_CLOSE_CSV_FILE');
				} else {
					$response['code'] = 200;
					$response = array('status' => true, 'file' => $name);
				}
			}
		}

		if ($response['code'] == 403) {
			header('HTTP/1.1 403 Forbidden');
			echo Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS');
			exit;
		}

		echo json_encode((object) $response);
		exit();
	}

	/**
	 * Create temp PDF file for PDF extraction
	 * @return String json
	 */
	public function create_file_pdf()
	{
		$result = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))  {
			$today = date("MdYHis");
			$name  = md5($today . rand(0, 10));
			$name  = $name . '-applications.pdf';

			$result = array('status' => true, 'file' => $name);
		}

		echo json_encode((object) $result);
		exit();
	}

	public function getfnums_csv()
	{
		$m_files = $this->getModel('Files');

		$fnums_post  = $this->app->getInput()->getString('fnums', null);
		$fnums_array = ($fnums_post == 'all') ? 'all' : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);

		if ($fnums_array == 'all') {
			$fnums = $m_files->getAllFnums();
		}
		else {
			$fnums = array();
			foreach ($fnums_array as $key => $value) {
				$fnums[] = $value;
			}
		}

		$validFnums = array();
		foreach ($fnums as $fnum) {
            if ($fnum != 'em-check-all-all' && $fnum != 'em-check-all' && EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)) {
				$validFnums[] = $fnum;
			}
		}

		if (!empty($validFnums)) {
			EmundusModelLogs::logs($this->_user->id, $validFnums, 6, 'c', 'COM_EMUNDUS_ACCESS_EXPORT_EXCEL');
		}

		$session = $this->app->getSession();
		$session->set('fnums_export', $validFnums);
		$result = array('status' => true, 'totalfile' => count($validFnums), 'valid_fnums' => $validFnums);
		echo json_encode((object) $result);
		exit();
	}

	public function getfnums()
	{
		$ids = $this->input->getVar('ids', null);

		$action_id = $this->input->getVar('action_id', null);
		$crud      = $this->input->getVar('crud', null);

		$m_files = $this->getModel('Files');

		$fnums_post  = $this->input->getVar('fnums', null);
		$fnums_array = ($fnums_post == 'all') ? 'all' : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);

		if ($fnums_array == 'all') {
			$fnums = $m_files->getAllFnums();
		}
		else {
			$fnums = array();
			foreach ($fnums_array as $key => $value) {
				$fnums[] = $value;
			}
		}

		$validFnums = array();
		foreach ($fnums as $fnum) {
			if (EmundusHelperAccess::asAccessAction($action_id, $crud, $this->_user->id, $fnum) && $fnum != 'em-check-all-all' && $fnum != 'em-check-all')
				$validFnums[] = $fnum;
		}
		$totalfile = count($validFnums);

		$session = Factory::getApplication()->getSession();
		$session->set('fnums_export', $validFnums);

		$result = array('status' => true, 'totalfile' => $totalfile, 'ids' => $ids);
		echo json_encode((object) $result);
		exit();
	}

	public function getallfnums()
	{
		$m_files = $this->getModel('Files');
		$fnums   = $m_files->getAllFnums();

		$validFnums = array();
		foreach ($fnums as $fnum) {
			if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum) && $fnum != 'em-check-all-all' && $fnum != 'em-check-all') {
				$validFnums[] = $fnum;
			}
		}

		echo json_encode($validFnums);
		exit();
	}

	public function getcolumn($elts)
	{
		return (array) json_decode(stripcslashes($elts));
	}

	/**
	 * Add lines to temp CSV file
	 * @return String json
	 * @throws Exception
	 */
	public function generate_array()
	{
		$current_user = Factory::getApplication()->getIdentity();

		if (!EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$eMConfig          = JComponentHelper::getParams('com_emundus');
		$eval_can_see_eval = $eMConfig->get('evaluators_can_see_other_eval', 0);

		$m_files       = $this->getModel('Files');
		$m_application = $this->getModel('Application');
		$m_users       = $this->getModel('Users');

		$session        = $this->app->getSession();
		$fnums          = $session->get('fnums_export');
		$anonymize_data = EmundusHelperAccess::isDataAnonymized($current_user->id);

		if (count($fnums) == 0) {
			$fnums = array($session->get('application_fnum'));
		}

		$file                               = $this->input->get('file', null, 'STRING');
		$totalfile                          = $this->input->get('totalfile', null);
		$start                              = $this->input->getInt('start', 0);
		$limit                              = $this->input->getInt('limit', 0);
		$nbcol                              = $this->input->get('nbcol', 0);
		$elts                               = $this->input->getString('elts', null);
		$step_elts                          = $this->input->getString('step_elts', []);
		$objs                               = $this->input->getString('objs', null);
		$opts                               = $this->input->getString('opts', null);
		$methode                            = $this->input->getString('methode', null);
		$objclass                           = $this->input->get('objclass', null);
		$excel_file_name                    = $this->input->get('excelfilename', null);
		$opts                               = $this->getcolumn($opts);

		// TODO: upper-case is mishandled, remove temporarily until fixed
		$opts = array_diff($opts, ['upper-case']);

		$col    = $this->getcolumn($elts);
		$colsup = $this->getcolumn($objs);
		$colOpt = array();

		if (!$csv = fopen(JPATH_SITE . DS . 'tmp' . DS . $file, 'a')) {
			$result = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_OPEN_FILE') . ' : ' . $file);
			echo json_encode((object) $result);
			exit();
		}

		$h_files  = new EmundusHelperFiles;
		$elements = $h_files->getElementsName(implode(',', $col));

		// re-order elements
		$ordered_elements = array();
		foreach ($col as $c) {
			$ordered_elements[$c] = $elements[$c];
		}

		// Order elements to have jos_emundus_setup_campaigns_more and jos_emundus_setup_campaigns first
		$orders = [
			'jos_emundus_campaign_candidature' => 1,
			'jos_emundus_setup_campaigns' => 2,
			'jos_emundus_setup_campaigns_more' => 3,
			'jos_emundus_setup_programmes' => 4,
			'jos_users' => 5,
		];

		usort($ordered_elements, function ($a, $b) use ($orders) {
			$orderA = $orders[$a->tab_name] ?? PHP_INT_MAX; // Default to high value if not found
			$orderB = $orders[$b->tab_name] ?? PHP_INT_MAX;

			return $orderA <=> $orderB;
		});

		$failed_with_old_method = false;
		if ($methode == 2) {
			$fnumsArray = $m_files->getFnumArray($fnums, $ordered_elements, $methode, $start, $limit, 0);
			if ($fnumsArray === false) {
				$failed_with_old_method = true;
			}
		}

		$not_already_handled_fnums = [];
		if ($methode != 2 || $failed_with_old_method) {
			$not_already_handled_fnums = $fnums;
			if ($start > 0) {
				$not_already_handled_fnums = $session->get('not_already_handled_fnums');
			}
			$fnumsArray = $m_files->getFnumArray2($not_already_handled_fnums, $ordered_elements, 0, $limit, $methode);
		}

		if ($fnumsArray !== false) {
			if (!empty($step_elts)) {
				$evaluations_by_fnum_by_step = $m_files->getEvaluationsArray($fnums, $step_elts);

				$step_element_ids = [];
				foreach ($step_elts as $step_id => $step_elements) {
					$step_element_ids = array_merge($step_element_ids, array_values($step_elements));
				}
				$step_elements_name = $h_files->getElementsName(implode(',', $step_element_ids));

				$ordered_elements[] = 'step_id';

				foreach ($step_elements_name as $element_id => $step_element_name) {
					$ordered_elements[$element_id] = $step_element_name;
				}

				$fnumsArray = $m_files->mergeEvaluations($fnumsArray, $evaluations_by_fnum_by_step, $step_elements_name);

			}

			// On met a jour la liste des fnums traités
			$fnums = array();
			foreach ($fnumsArray as $fnum) {
				$fnums[] = $fnum['fnum'];
			}

			$not_already_handled_fnums = array_diff($not_already_handled_fnums, $fnums);
			$session->set('not_already_handled_fnums', $not_already_handled_fnums);

			foreach ($colsup as $colsupkey => $col) {
				$col = explode('.', $col);

				switch ($col[0]) {
					case "photo":
						if (!$anonymize_data) {
							$allowed_attachments = EmundusHelperAccess::getUserAllowedAttachmentIDs(JFactory::getUser()->id);
							if ($allowed_attachments === true || in_array('10', $allowed_attachments)) {
								$photos = $m_files->getPhotos($fnums);
								if (count($photos) > 0) {
									$pictures = array();
									foreach ($photos as $photo) {
										$folder                   = JURI::base() . EMUNDUS_PATH_REL . $photo['user_id'];
										$link                     = '=HYPERLINK("' . $folder . '/tn_' . $photo['filename'] . '","' . $photo['filename'] . '")';
										$pictures[$photo['fnum']] = $link;
									}
									$colOpt['PHOTO'] = $pictures;
								}
								else {
									$colOpt['PHOTO'] = array();
								}
							}
						}
						break;
					case "forms":
						foreach ($fnums as $fnum) {
							$formsProgress[$fnum] = $m_application->getFormsProgress($fnum);
						}
						if (!empty($formsProgress)) {
							$colOpt['forms'] = $formsProgress;
						}
						break;
					case "attachment":
						foreach ($fnums as $fnum) {
							$attachmentProgress[$fnum] = $m_application->getAttachmentsProgress($fnum);
						}
						if (!empty($attachmentProgress)) {
							$colOpt['attachment'] = $attachmentProgress;
						}
						break;
					case "assessment":
						$colOpt['assessment'] = $h_files->getEvaluation('text', $fnums);
						break;
					case "comment":
						$colOpt['comment'] = $m_files->getCommentsByFnum($fnums);
						break;
					case 'evaluators':
						$colOpt['evaluators'] = $h_files->createEvaluatorList($col[1], $m_files);
						break;
					case 'tags':
						$colOpt['tags'] = $m_files->getTagsByFnum($fnums);
						break;
					case 'group-assoc':
						$colOpt['group-assoc'] = $m_files->getAssocByFnums($fnums, true, false);
						break;
					case 'user-assoc':
						$colOpt['user-asoc'] = $m_files->getAssocByFnums($fnums, false, true);
						break;
					case 'overall':
						require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
						$m_evaluations     = $this->getModel('Evaluation');
						$evaluations_average_by_step = $m_evaluations->getEvaluationAverageBySteps($fnums, $current_user->id);

						foreach($evaluations_average_by_step as $step_id => $average_by_fnum) {
							$m_workflow = new EmundusModelWorkflow();
							$step_data = $m_workflow->getStepData($step_id);
							$colsup['overall_' . $step_id] = TEXT::_('COM_EMUNDUS_EVALUATIONS_OVERALL') . ' ' . $step_data->label;

							foreach($average_by_fnum as $fnum => $average) {
								$colOpt['overall_' . $step_id][$fnum] = (float)$average;
							}
						}

						unset($colsup[$colsupkey]);
						break;
				}
			}
			$status      = $m_files->getStatusByFnums($fnums);
			$line        = "";
			$element_csv = array();
			$i           = $start;

			// Here we filter elements which are already present but under a different name or ID, by looking at tablename___element_name.
			$elts_present = [];
			foreach ($ordered_elements as $elt_id => $o_elt)
			{
				$element = !empty($o_elt->table_join) ? $o_elt->table_join . '___' . $o_elt->element_name : $o_elt->tab_name . '___' . $o_elt->element_name;
				if (in_array($element, $elts_present))
				{
					unset($ordered_elements[$elt_id]);
				}
				else
				{
					$elts_present[] = $element;
				}
			}

			// On traite les en-têtes
			if ($start == 0) {

				if ($anonymize_data) {
					$line = Text::_('COM_EMUNDUS_FILE_F_NUM') . "\t" . Text::_('COM_EMUNDUS_STATUS') . "\t" . Text::_('COM_EMUNDUS_PROGRAMME') . "\t";
				}
				else {
					$line = Text::_('COM_EMUNDUS_FILE_F_NUM') . "\t" . Text::_('COM_EMUNDUS_STATUS') . "\t" . Text::_('COM_EMUNDUS_FORM_LAST_NAME') . "\t" . Text::_('COM_EMUNDUS_FORM_FIRST_NAME') . "\t" . Text::_('COM_EMUNDUS_EMAIL') . "\t" . Text::_('COM_EMUNDUS_PROGRAMME') . "\t";
				}

				$nbcol         = 6;
				$date_elements = [];
				$textarea_elements = [];
				$iban_elements = [];
                $calc_elements = [];
				$currency_elements = [];
				foreach ($ordered_elements as $fLine) {
					if ($fLine === 'step_id') {
						$line .= Text::_('COM_EMUNDUS_EVALUATION_EVAL_STEP') . "\t";
						$line .= Text::_('COM_EMUNDUS_EVALUATION_ID') . "\t";
						$line .= Text::_('COM_EMUNDUS_EVALUATION_EVALUATOR') . "\t";
						$nbcol += 3;
						continue;
					}

					if ($fLine->element_name != 'fnum' && $fLine->element_name != 'code' && $fLine->element_label != 'Programme' && $fLine->element_name != 'campaign_id') {
						if (!(count($opts) == 1 && in_array("form-csv-only", $opts)) && count($opts) > 0 && $fLine->element_name != "date_time" && $fLine->element_name != "date_submitted") {
							if (in_array("form-title", $opts) && in_array("form-group", $opts)) {
								$line .= Text::_($fLine->form_label) . " > " . Text::_($fLine->group_label) . " > " . preg_replace('#<[^>]+>|\t#', ' ', Text::_($fLine->element_label)) . "\t";
								$nbcol++;
							}
							elseif (count($opts) == 1) {
								if (in_array("form-title", $opts)) {
									$line .= Text::_($fLine->form_label) . " > " . preg_replace('#<[^>]+>|\t#', ' ', Text::_($fLine->element_label)) . "\t";
									$nbcol++;
								}
								elseif (in_array("form-group", $opts)) {
									$line .= Text::_($fLine->group_label) . " > " . preg_replace('#<[^>]+>|\t#', ' ', Text::_($fLine->element_label)) . "\t";
									$nbcol++;
								}
							}
						}
						else {
							$params                                                         = json_decode($fLine->element_attribs);
							$elt_name = $fLine->tab_name.'___'.$fLine->element_name;
							if(!empty($fLine->table_join) && $fLine->table_join_key == 'parent_id') {
								$elt_name = $fLine->table_join.'___'.$fLine->element_name;
							}

							if (in_array($fLine->element_plugin, ['date','jdate'])) {
								if($fLine->element_plugin === 'jdate') {
									$date_elements[$elt_name] = $params->jdate_form_format;
								} else {
									$date_elements[$elt_name] = $params->date_form_format;
								}
							}

							if ($fLine->element_plugin === 'textarea') {
								$textarea_elements[$elt_name] = $params->use_wysiwyg;
							}

							if ($fLine->element_plugin === 'iban') {
								$iban_elements[$elt_name] = $params->encrypt_datas;
							}
                            if ($fLine->element_plugin === 'calc') {
                                $calc_elements[] = $elt_name;
                            }

							if ($fLine->element_plugin === 'currency')
							{
								$currency_elements[] =  $elt_name;
							}

							$line .= preg_replace('#<[^>]+>|\t#', ' ', Text::_($fLine->element_label)) . "\t";
							$nbcol++;
						}
					}
				}

				foreach ($colsup as $kOpt => $vOpt) {
					if ($vOpt == "forms" || $vOpt == "attachment") {
						$line .= Text::_('COM_EMUNDUS_'.strtoupper($vOpt))." (%)\t";
					}
					elseif ($vOpt == "overall") {
						$line .= Text::_('COM_EMUNDUS_EVALUATIONS_OVERALL') . "\t";
					}
					else {
						switch ($vOpt) {
							case 'comment':
								$line .= Text::_('COM_EMUNDUS_COMMENT') . "\t";
								break;
							case 'tags':
								$line .= Text::_('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_TAGS') . "\t";
								break;
							case 'group-assoc':
								$line .= Text::_('COM_EMUNDUS_ASSOCIATED_GROUPS') . "\t";
								break;
							case 'user-assoc':
								$line .= Text::_('COM_EMUNDUS_ASSOCIATED_USERS') . "\t";
								break;
							case 'ranking':
								// do nothing, handled later
								break;
							default:
								$line .= '"' . preg_replace("/\r|\n|\t/", "", $vOpt) . '"' . "\t";
								break;
						}
					}
					$nbcol++;
				}

				// On met les en-têtes dans le CSV
				$element_csv[] = $line;
				$line          = "";
			} else {
				// On définit les bons formats
				$date_elements = [];
				$textarea_elements = [];
				$iban_elements = [];
                $calc_elements = [];
				$currency_elements = [];
				foreach ($ordered_elements as $fLine) {
					$params                                                         = json_decode($fLine->element_attribs);
					$elt_name = $fLine->tab_name.'___'.$fLine->element_name;
					if(!empty($fLine->table_join) && $fLine->table_join_key == 'parent_id') {
						$elt_name = $fLine->table_join.'___'.$fLine->element_name;
					}

					if (in_array($fLine->element_plugin,['date','jdate'])) {
						if($fLine->element_plugin == 'jdate') {
							$date_elements[$elt_name] = $params->jdate_form_format;
						} else {
							$date_elements[$elt_name] = $params->date_form_format;
						}
					}

					if ($fLine->element_plugin == 'textarea') {
						$textarea_elements[$elt_name] = $params->use_wysiwyg;
					}

					if ($fLine->element_plugin == 'iban') {
						$iban_elements[$elt_name] = $params->encrypt_datas;
					}
                    if ($fLine->element_plugin == 'calc') {
                        $calc_elements[] = $elt_name;
                    }

					if ($fLine->element_plugin === 'currency')
					{
						$currency_elements[] =  $elt_name;
					}
				}
			}
			
			//check if evaluator can see others evaluators evaluations
			if (EmundusHelperAccess::isEvaluator($current_user->id) && !@EmundusHelperAccess::isCoordinator($current_user->id)) {
				$user      = $m_users->getUserById($current_user->id);
				$evaluator = $user[0]->lastname . " " . $user[0]->firstname;
				if ($eval_can_see_eval == 0 && !empty($objclass) && in_array("emundusitem_evaluation otherForm", $objclass)) {
					foreach ($fnumsArray as $idx => $d) {
						foreach ($d as $k => $v) {
							if ($k === 'jos_emundus_evaluations___user' && strcasecmp($v, $evaluator) != 0) {
								foreach ($fnumsArray[$idx] as $key => $value) {
									if (substr($key, 0, 26) === "jos_emundus_evaluations___") {
										$fnumsArray[$idx][$key] = Text::_('COM_EMUNDUS_ACCESS_NO_RIGHT');
									}
								}
							}
						}
					}
				}
			}

			if (in_array('ranking', $colsup)) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/ranking.php');
				$m_ranking = new EmundusModelRanking();

				if ($m_ranking->isActivated()) {
					$hierarchies = $m_ranking->getHierarchies();

					foreach ($hierarchies as $hierarchy) {
						$files_rankings = $m_ranking->getAllRankingsSuperAdmin($hierarchy['id'], 0, 0, [], [], [], '', '', 'ecc.fnum', 'ASC', $fnums);
						// add a header column for each hierarchy and another for the ranker
						$element_csv[0] .= Text::_('COM_EMUNDUS_RANKING_EXPORT_RANKING') . ' ' . $hierarchy['label'] . "\t";
						$element_csv[0] .= Text::_('COM_EMUNDUS_RANKING_EXPORT_RANKER') . ' ' . $hierarchy['label'] . "\t";

						foreach ($files_rankings as $ranking_row) {
							$fnumsArray[$ranking_row['fnum']]['ranking_' . $hierarchy['id']] = $ranking_row['rank'] !== -1 && !empty($ranking_row['rank']) ? $ranking_row['rank'] : Text::_('COM_EMUNDUS_RANKING_NOT_RANKED');
							$fnumsArray[$ranking_row['fnum']]['ranker_' . $hierarchy['id']] = $ranking_row['ranker_name'];
						}

						if (!empty($hierarchy['form_id'])) {
							$hierarchy_form_elements = $m_ranking->getHierarchyFormElements($hierarchy['form_id'], 'array');

							foreach($hierarchy_form_elements as $form_element) {
								$element_csv[0] .= strip_tags(Text::_($form_element['label'])) . "\t";

								foreach ($files_rankings as $ranking_row) {
									$element_id = $form_element['db_table_name'] . '___' . $form_element['element_name'];
									$value = $m_files->getFabrikElementValue($form_element, $ranking_row['fnum']);

									if (isset($value[$form_element['id']][$ranking_row['fnum']]['val'])) {
										$fnumsArray[$ranking_row['fnum']][$element_id] = $value[$form_element['id']][$ranking_row['fnum']]['val'];
									} else {
										$fnumsArray[$ranking_row['fnum']][$element_id] = '';
									}
								}
							}
						}
					}
				}
			}

			if (!empty($fnumsArray)) {
				$encrypted_tables = $h_files->getEncryptedTables();
				if (!empty($encrypted_tables)) {
					$cipher         = 'aes-128-cbc';
					$encryption_key = JFactory::getConfig()->get('secret');
				}

				$emParams = ComponentHelper::getParams('com_emundus');
				$excel_elts_to_escape = $emParams->get('export_elements_to_escape', '');
				if(!empty($excel_elts_to_escape) && is_array($excel_elts_to_escape)) {
					$db = Factory::getContainer()->get('DatabaseDriver');
					$query = $db->getQuery(true);

					$query->select('name')
						->from($db->quoteName('#__fabrik_elements'))
						->where($db->quoteName('id') . ' IN ('.implode(',',$excel_elts_to_escape).')');
					$db->setQuery($query);
					$excel_elts_to_escape = $db->loadColumn();
				} else {
					$excel_elts_to_escape = [];
				}

				// On parcours les fnums
				foreach ($fnumsArray as $fnum) {
					// On traite les données du fnum
					foreach ($fnum as $k => $v) {
						if ($k != 'code' && strpos($k, 'campaign_id') === false) {

							if ($k === 'fnum') {
								$line .= "'" . $v . "\t";
								$line .= $status[$v]['value'] . "\t";
								$uid  = intval(substr($v, 21, 7));
								if (!$anonymize_data) {
									$userProfil = $m_users->getUserById($uid)[0];
									$line       .= $userProfil->lastname . "\t";
									$line       .= $userProfil->firstname . "\t";
								}
							}
							else {
								list($key_table, $key_element) = explode('___', $k);

								if ($v == "") {
									$line .= " " . "\t";
								}
								else {
									if (!empty($encrypted_tables)) {
										if (!empty($key_table) && in_array($key_table, $encrypted_tables)) {
											$decoded_value = json_decode($v, true);

                                            if (!empty($decoded_value)) {
                                                $all_decrypted_data = [];
                                                foreach ($decoded_value as $decoded_sub_value) {
                                                    $all_decrypted_data[] = EmundusHelperFabrik::decryptDatas($decoded_sub_value);
                                                }

                                                $v = '[' . implode(',', $all_decrypted_data) . ']';
                                            } else {
                                                $v = EmundusHelperFabrik::decryptDatas($v);
                                            }
										}
									}

									if ($v[0] == "=" || $v[0] == "-") {
										if (count($opts) > 0 && in_array("upper-case", $opts)) {
											$line .= " " . mb_strtoupper($v) . "\t";
										}
										else {
											$line .= " " . preg_replace("/\t/", "", $v) . "\t";
										}
									}
									else {
										if (!empty($date_elements[$k])) {
											$v = str_replace("\\", '', $v); // if date contains \, remove it

											if ($v === '0000-00-00 00:00:00') {
												$v = '';
											}
											else {
												$v = date($date_elements[$k], strtotime($v));
											}
											$line .= preg_replace("/\r|\n|\t/", "", $v) . "\t";
										}
										elseif (!empty($textarea_elements) && array_key_exists($k, $textarea_elements)) {
											if ($textarea_elements[$k] == 1) {
												$v = strip_tags($v);
											}
                                            $line .= preg_replace("/\t/", "", $v)."\t"; // limit preg_replace to keep linebreaks
										}
										elseif(!empty($iban_elements[$k])){
											if($iban_elements[$k] == 1){
												if(strpos($k,'repeat')) {
													$v = explode(',', $v);

													$repeat_values_decrypted = [];
													foreach ($v as $repeat_value) {
														$repeat_values_decrypted[] = EmundusHelperFabrik::decryptDatas($repeat_value);
													}

													$v = implode(',', $repeat_values_decrypted);
												}
												else {
													$v = EmundusHelperFabrik::decryptDatas($v);
												}
											}
											$line .= preg_replace("/\r|\n|\t/", "", $v)."\t";
										}
                                        elseif(in_array($k,$calc_elements)){
                                            $v = strip_tags($v);
                                            $line .= preg_replace("/\r|\n|\t/", "", $v)."\t";
                                        }
										else if (!empty($currency_elements) && in_array($k, $currency_elements)) {
											$v = EmundusHelperFabrik::extractNumericValue($v);
											$line .= preg_replace("/\r|\n|\t/", "", $v) . "\t";
										}
										elseif (count($opts) > 0 && in_array("upper-case", $opts)) {
											$line .= Text::_(preg_replace("/\r|\n|\t/", "", mb_strtoupper($v))) . "\t";
										}
										else {
											if(!empty($key_element) && in_array($key_element,$excel_elts_to_escape)) {
												$line .= "'". Text::_(preg_replace("/\r|\n|\t/", "", $v))."\t";
											} else {
												$line .= Text::_(preg_replace("/\r|\n|\t/", "", $v))."\t";
											}
										}
									}
								}
							}
						}
					}

					// On ajoute les données supplémentaires
					foreach ($colOpt as $kOpt => $vOpt) {
						switch ($kOpt) {
							case "PHOTO":
							case "forms":
							case "attachment":
							case 'evaluators':
								if (array_key_exists($fnum['fnum'], $vOpt)) {
									$line .= $vOpt[$fnum['fnum']] . "\t";
								}
								else {
									$line .= "\t";
								}
								break;

							case "assessment":
								$eval = '';
								if (array_key_exists($fnum['fnum'], $vOpt)) {
									$evaluations = $vOpt[$fnum['fnum']];
									foreach ($evaluations as $evaluation) {
										$eval .= $evaluation;
										$eval .= chr(10) . '______' . chr(10);
									}
									$line .= $eval . "\t";
								}
								else {
									$line .= "\t";
								}
								break;

							case "comment":
								$comments = "";
								if (!empty($vOpt)) {
									foreach ($colOpt['comment'] as $comment) {
										if ($comment['fnum'] == $fnum['fnum']) {
											$comments .= $comment['reason'] . " | " . $comment['comment_body'] . "\rn";
										}
									}
									$line .= $comments . "\t";
								}
								else {
									$line .= "\t";
								}
								break;

							case "tags":
								$tags = '';

								foreach ($colOpt['tags'] as $tag) {
									if ($tag['fnum'] == $fnum['fnum'] && (EmundusHelperAccess::asAccessAction(14 ,'r', $current_user->id, $fnum['fnum']) || (EmundusHelperAccess::asAccessAction(14 ,'c', $current_user->id, $fnum['fnum']) && $tag['user_id'] === $current_user->id))) {
										if(!empty($tags)) {
											$tags .= ", ";
										}

										$tags .= $tag['label'];
									}
								}
								$line .= $tags . "\t";
								break;

							default:
								$line .= $vOpt[$fnum['fnum']] . "\t";
								break;
						}
					}
					// On met les données du fnum dans le CSV
					$element_csv[] = $line;
					$line          = "";
					$i++;
				}
			}

			// On remplit le fichier CSV
			foreach ($element_csv as $data) {
				$res = fputcsv($csv, explode("\t", $data), "\t");
				if (!$res) {
					$result = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_WRITE_TO_FILE' . ' : ' . $csv));
					echo json_encode((object) $result);
					exit();
				}
			}
			if (!fclose($csv)) {
				$result = array('status' => false, 'msg' => Text::_('COM_EMUNDUS_EXPORTS_ERROR_CANNOT_CLOSE_CSV_FILE'));
				echo json_encode((object) $result);
				exit();
			}

			$start = $i;

			$dataresult = array('start' => $start, 'limit' => $limit, 'totalfile' => $totalfile, 'methode' => $methode, 'elts' => $elts, 'objs' => $objs, 'nbcol' => $nbcol, 'file' => $file, 'excelfilename' => $excel_file_name);
			$result     = array('status' => true, 'json' => $dataresult);
		}
		else {
			$result = array('status' => false, 'msg' => Text::_('COM_EMUNDUS_EXPORTS_FAILED'));
		}

		echo json_encode((object) $result);
		exit();
	}

	public function getformslist()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403);

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$html = '';
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');

			$m_profile  = $this->getModel('Profile');
			$m_campaign = $this->getModel('Campaign');
			$h_menu     = new EmundusHelperMenu();


			$code = $this->input->getVar('code', null);
			$camp = $this->input->getVar('camp', null);


			$code = explode(',', $code);
			$camp = explode(',', $camp);

			$profiles = $m_profile->getProfileIDByCourse($code, $camp);

			foreach ($profiles as $profile) {
				$profile_data = $m_profile->getProfile($profile);

				$html1    = '';
				$html2    = '';
				$pages    = $h_menu->buildMenuQuery((int) $profile);
				$campaign = $camp[0] != 0 ? $m_campaign->getCampaignsByCourseCampaign($code[0], $camp[0]) : $m_campaign->getCampaignsByCourse($code[0]);

				foreach ($pages as $i => $page) {
					$title = Text::_($page->label);
					//$title = !empty($title[1]) ? Text::_(trim($title[1])) : Text::_(trim($title[0]));

					if ($i < count($pages) / 2) {
						$html1 .= '<div class="em-flex-row"><input class="em-ex-check" type="checkbox" value="' . $page->form_id . '|' . $code[0] . '|' . $camp[0] . '" name="' . $page->label . '" id="' . $page->form_id . '|' . $code[0] . '|' . $camp[0] . '|' . $profile . '" /><label class="em-mb-0-important" for="' . $page->form_id . '|' . $code[0] . '|' . $camp[0] . '|' . $profile . '">' . Text::_($title) . '</label></div>';
					}
					else {
						$html2 .= '<div class="em-flex-row"><input class="em-ex-check" type="checkbox" value="' . $page->form_id . '|' . $code[0] . '|' . $camp[0] . '" name="' . $page->label . '" id="' . $page->form_id . '|' . $code[0] . '|' . $camp[0] . '|' . $profile . '" /><label class="em-mb-0-important" for="' . $page->form_id . '|' . $code[0] . '|' . $camp[0] . '|' . $profile . '">' . Text::_($title) . '</label></div>';
					}
				}

				$html .= '<div class="em-mt-12">
                    <div class="em-flex-row em-pointer em-mb-4" onclick="showelts(this, ' . "'felts-" . $code[0] . $camp[0] . "-" . $profile . "'" . ')">
                       <span title="' . Text::_('COM_EMUNDUS_SHOW_ELEMENTS') . '" id="felts-' . $code[0] . $camp[0] . '-' . $profile . '-icon" class="material-symbols-outlined em-mr-4" style="transform: rotate(-90deg)">expand_more</span>
                       <p>' . $campaign['label'] . ' (' . $campaign['year'] . ' | ' . $profile_data->label . ')</p>
                    </div>
                    <div id="felts-' . $code[0] . $camp[0] . '-' . $profile . '" style="display:none;">
                        <table><tr><td>' . $html1 . '</td><td style="padding-left:80px;">' . $html2 . '</td></tr></table>
                    </div>
                </div>';
			}

			$response = array('status' => true, 'html' => $html);
		}

		echo json_encode((object) ($response));
		exit;
	}

	public function getdoctype()
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');


		$code     = $this->input->getVar('code', null);
		$camp     = $this->input->getVar('camp', null);
		$code     = explode(',', $code);
		$camp     = explode(',', $camp);
		$profiles = $this->input->getVar('profiles', null);

		$m_profile  = $this->getModel('Profile');
		$m_campaign = $this->getModel('Campaign');
		$h_files    = new EmundusHelperFiles();

		$profiles = !empty($profiles) ? $profiles : $m_profile->getProfileIDByCourse($code, $camp);
		//$docs = $h_files->getAttachmentsTypesByProfileID((int)$profile[0]);


		$docs = $h_files->getAttachmentsTypesByProfileID($profiles);

		// Sort the docs out that are not allowed to be exported by the user.
		$allowed_attachments = EmundusHelperAccess::getUserAllowedAttachmentIDs($this->_user->id);
		if ($allowed_attachments !== true) {
			foreach ($docs as $key => $doc) {
				if (!in_array($doc->id, $allowed_attachments)) {
					unset($docs[$key]);
				}
			}
		}

		// Reindex array otherwise next for loop will not work properly if we did unset docs before
		$docs = array_values($docs);

		if ($camp[0] != 0) {
			$campaign = $m_campaign->getCampaignsByCourseCampaign($code[0], $camp[0]);
		}
		else {
			$campaign = $m_campaign->getCampaignsByCourse($code[0]);
		}

		$html1 = '';
		$html2 = '';
		for ($i = 0; $i < count($docs); $i++) {
			if ($i < count($docs) / 2) {
				$html1 .= '<div class="em-flex-row"><input class="em-ex-check" type="checkbox" value="' . $docs[$i]->id . "|" . $code[0] . "|" . $camp[0] . '" name="' . $docs[$i]->value . '" id="' . $docs[$i]->id . "|" . $code[0] . "|" . $camp[0] . '" /><label class="em-mb-0-important" for="' . $docs[$i]->id . "|" . $code[0] . "|" . $camp[0] . '">' . Text::_($docs[$i]->value) . '</label></div>';
			}
			else {
				$html2 .= '<div class="em-flex-row"><input class="em-ex-check" type="checkbox" value="' . $docs[$i]->id . "|" . $code[0] . "|" . $camp[0] . '" name="' . $docs[$i]->value . '" id="' . $docs[$i]->id . "|" . $code[0] . "|" . $camp[0] . '" /><label class="em-mb-0-important" for="' . $docs[$i]->id . "|" . $code[0] . "|" . $camp[0] . '">' . Text::_($docs[$i]->value) . '</label></div>';
			}
		}

		$html = '<div class="em-mt-12">
                    <div class="em-flex-row em-pointer em-mb-4" onclick="showelts(this, ' . "'aelts-" . $code[0] . $camp[0] . "'" . ')">
                    <span title="' . Text::_('COM_EMUNDUS_SHOW_ELEMENTS') . '" id="aelts-' . $code[0] . $camp[0] . '-icon" class="material-symbols-outlined em-mr-4" style="transform: rotate(-90deg)">expand_more</span>
                        <p>' . $campaign['label'] . ' (' . $campaign['year'] . ')</p>
                    </div>
                    <div id="aelts-' . $code[0] . $camp[0] . '" style="display:none;">
                        <table><tr><td>' . $html1 . '</td><td>' . $html2 . '</td></tr></table>
                    </div>
                </div>';

		echo json_encode((object) (array('status' => true, 'html' => $html)));
		exit;
	}

	/**
	 * Generate PDF
	 *
	 * @since version 1.0.0
	 */
	public function generate_pdf() {
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$m_files = new EmundusModelFiles();

		$session = $this->app->getSession();
		$fnums_post = $session->get('fnums_export');

		if (count($fnums_post) == 0) {
			$fnums_post = [$session->get('application_fnum')];
		}

		$file       = $this->input->getString('file', null);
		$totalfile  = $this->input->getVar('totalfile', null);
		$start      = $this->input->getInt('start', 0);
		$limit      = $this->input->getInt('limit', 1);
		$forms      = $this->input->getInt('forms', 0);
		$attachment = $this->input->getInt('attachment', 0);
		$assessment = 0;
		$decision   = 0;
		$admission  = 0;
		$ids        = $this->input->getString('ids', null);
		$formid     = $this->input->getString('formids', null);
		$attachids   = $this->input->getString('attachids', null);
		$options     = $this->input->getVar('options', null);

		$profiles = $this->input->getRaw('profiles', null);
		$tables = $this->input->getRaw('tables', null);
		$groups = $this->input->getRaw('groups', null);
		$elements = $this->input->getRaw('elements', null);

		$validFnums = [];
		foreach ($fnums_post as $fnum) {
			if (EmundusHelperAccess::asAccessAction(8, 'c', $this->_user->id, $fnum)) {
				$validFnums[] = $fnum;
			}
		}

		$pdf_data = [];
		foreach($profiles as $id) {
			$pdf_data[$id] = ['fids' => $tables, 'gids' => $groups, 'eids' => $elements];
		}

		$result = $m_files->generatePDF($validFnums, $file, $totalfile, $start, $forms, $attachment, $assessment, $decision, $admission, $ids, $formid, $attachids, $options, $pdf_data);

		$result['json']['limit'] = $limit;

		echo json_encode((object) $result);
		exit();
	}

	/// generate pdf with selected form elements
	public function generate_customized_pdf()
	{
		$current_user = JFactory::getUser();

		if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$m_files = $this->getModel('Files');

		$session    = JFactory::getSession();
		$fnums_post = $session->get('fnums_export');

		if (count($fnums_post) == 0) {
			$fnums_post = array($session->get('application_fnum'));
		}

		$file       = $this->input->getVar('file', null, 'STRING');
		$totalfile  = $this->input->getVar('totalfile', null);
		$start      = $this->input->getInt('start', 0);
		$limit      = $this->input->getInt('limit', 1);
		$forms      = $this->input->getInt('forms', 0);
		$attachment = $this->input->getInt('attachment', 0);
		$assessment = $this->input->getInt('assessment', 0);
		$decision   = $this->input->getInt('decision', 0);
		$admission  = $this->input->getInt('admission', 0);
		$ids        = $this->input->getVar('ids', null);
		$formid     = $this->input->getVar('formids', null);
		$attachid   = $this->input->getVar('attachids', null);
		$option     = $this->input->getVar('options', null);

		$elements = $this->input->getVar('params', null);          // an object need to parsed

		$formids   = explode(',', $formid);
		$attachids = explode(',', $attachid);
		$options   = explode(',', $option);

		$validFnums = array();
		foreach ($fnums_post as $fnum) {
			if (EmundusHelperAccess::asAccessAction(8, 'c', $this->_user->id, $fnum)) {
				$validFnums[] = $fnum;
			}
		}

		$fnumsInfo = $m_files->getFnumsInfos($validFnums);

		/// old code
		if (count($validFnums) == 1) {
			$eMConfig              = JComponentHelper::getParams('com_emundus');
			$application_form_name = $eMConfig->get('application_form_name', "application_form_pdf");

			if ($application_form_name != "application_form_pdf") {

				require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'emails.php');
				$m_emails = $this->getModel('Emails');

				$fnum = $validFnums[0];
				$post = array(
					'FNUM'          => $fnum,
					'CAMPAIGN_YEAR' => $fnumsInfo[$fnum]['year']
				);
				$tags = $m_emails->setTags($fnumsInfo[$fnum]['applicant_id'], $post, $fnum, '', $application_form_name);

				// Format filename
				$application_form_name = preg_replace($tags['patterns'], $tags['replacements'], $application_form_name);
				$application_form_name = $m_emails->setTagsFabrik($application_form_name, array($fnum));
				$application_form_name = $m_emails->stripAccents($application_form_name);
				$application_form_name = preg_replace('/[^A-Za-z0-9 _.-]/', '', $application_form_name);
				$application_form_name = preg_replace('/\s/', '', $application_form_name);
				$application_form_name = strtolower($application_form_name);

				if ($file != $application_form_name . '.pdf' && file_exists(JPATH_SITE . DS . 'tmp' . DS . $application_form_name . '.pdf')) {
					unlink(JPATH_SITE . DS . 'tmp' . DS . $application_form_name . '.pdf');
				}

				$file = $application_form_name . '.pdf';
			}
		}
		////////////////////////////////////////////////////////////
		if (file_exists(JPATH_SITE . DS . 'tmp' . DS . $file)) {
			$files_list = array(JPATH_SITE . DS . 'tmp' . DS . $file);
		}
		else {
			$files_list = array();
		}

		/// get all elements of profile by key --> var_dump($elements['menutype_1002']);die;
		/// $forms = 0 or 1
		for ($i = $start; $i < ($start + $limit) && $i < $totalfile; $i++) {
			$fnum = $validFnums[$i];
			if (is_numeric($fnum) && !empty($fnum)) {
				if (isset($forms)) {
					if ($forms && !empty($elements) && !is_null($elements)) {
						/// for each fnum, call to function buildCustomizedPDF
						$files_list[] = EmundusHelperExport::buildCustomizedPDF($fnumsInfo[$fnum], $forms, $elements, $options);
					}
				}

				if ($attachment || !empty($attachids)) {
					$tmpArray             = array();
					$m_application        = $this->getModel('Application');
					$attachment_to_export = array();
					foreach ($attachids as $aids) {
						$detail = explode("|", $aids);
						if ((!empty($detail[1]) && $detail[1] == $fnumsInfo[$fnum]['training']) && ($detail[2] == $fnumsInfo[$fnum]['campaign_id'] || $detail[2] == "0")) {
							$attachment_to_export[] = $detail[0];
						}
					}
					if ($attachment || !empty($attachment_to_export)) {
						$files = $m_application->getAttachmentsByFnum($fnum, $ids, $attachment_to_export);
						if ($options[0] != "0") {
							$files_list[] = EmundusHelperExport::buildHeaderPDF($fnumsInfo[$fnum], $fnumsInfo[$fnum]['applicant_id'], $fnum, $options);
						}
						$files_export = EmundusHelperExport::getAttachmentPDF($files_list, $tmpArray, $files, $fnumsInfo[$fnum]['applicant_id']);
					}
				}

				if ($assessment)
					$files_list[] = EmundusHelperExport::getEvalPDF($fnum, $options);

				if ($decision)
					$files_list[] = EmundusHelperExport::getDecisionPDF($fnum, $options);

				if ($admission)
					$files_list[] = EmundusHelperExport::getAdmissionPDF($fnum, $options);

				if (($forms != 1) && $formids[0] == "" && ($attachment != 1) && ($attachids[0] == "") && ($assessment != 1) && ($decision != 1) && ($admission != 1) && ($options[0] != "0"))
					$files_list[] = EmundusHelperExport::buildHeaderPDF($fnumsInfo[$fnum], $fnumsInfo[$fnum]['applicant_id'], $fnum, $options);

			}

		}
		$start = $i;

		if (count($files_list) > 0) {

			// all PDF in one file
			require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'fpdi.php');

			$pdf = new ConcatPdf();

			$pdf->setFiles($files_list);

			$pdf->concat();

			if (isset($tmpArray)) {
				foreach ($tmpArray as $fn) {
					unlink($fn);
				}
			}
			$pdf->Output(JPATH_SITE . DS . 'tmp' . DS . $file, 'F');

			$start = $i;

			$dataresult = [
				'start'     => $start, 'limit' => $limit, 'totalfile' => $totalfile, 'forms' => $forms, 'formids' => $formid, 'attachids' => $attachid,
				'options'   => $option, 'attachment' => $attachment, 'assessment' => $assessment, 'decision' => $decision,
				'admission' => $admission, 'file' => $file, 'ids' => $ids, 'path' => JURI::base(), 'msg' => Text::_('COM_EMUNDUS_EXPORTS_FILES_ADDED')//.' : '.$fnum
			];
			$result     = array('status' => true, 'json' => $dataresult);

		}
		else {

			$dataresult = [
				'start'     => $start, 'limit' => $limit, 'totalfile' => $totalfile, 'forms' => $forms, 'formids' => $formid, 'attachids' => $attachid,
				'options'   => $option, 'attachment' => $attachment, 'assessment' => $assessment, 'decision' => $decision,
				'admission' => $admission, 'file' => $file, 'ids' => $ids, 'msg' => Text::_('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND')
			];

			$result = array('status' => false, 'json' => $dataresult);
		}
		echo json_encode((object) $result);
		exit();
	}

	//TODO: Comprendre la méthode
	public function export_letter()
	{
		$result = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403);
		/// the main idea of this function is to use Stream of Buffer to pass data from CSV to Excel
		/// params --> 1st: csv, 2nd: excel

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

			// get source, letter name
			$source = $this->input->getVar('source', null);
			$letter = $this->input->getVar('letter', null);

			/// copy excel to excel
			$_start = JPATH_SITE . DS . "tmp" . DS . $source;
			$_end   = JPATH_SITE . $letter;

			/// copy letter from /images/emundus/letters --> /tmp
			$tmp_route    = JPATH_SITE . DS . "tmp" . DS;
			$randomString = JUserHelper::genRandomPassword(20);

			$array              = explode('/', $letter);
			$letter_file        = end($array);
			$letter_file_random = explode('.xlsx', $letter_file)[0] . '_' . $randomString;

			$_newLetter = JPATH_SITE . DS . "tmp" . DS . $letter_file_random . '.xlsx';
			copy($_end, JPATH_SITE . DS . "tmp" . DS . $letter_file_random . '.xlsx');

			$reader             = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
			$_readerSpreadSheet = $reader->load($_start);

			$_readerData = $_readerSpreadSheet->getActiveSheet()->toArray();

			try {
				$dataTable = new Svrnm\ExcelDataTables\ExcelDataTable();

				$data    = array();
				$columns = array();
				foreach ($_readerData[0] as $column) {
					$columns[] = $column;
				}
				foreach ($_readerData as $key => $reader) {
					if ($key !== 0) {
						$row = new stdClass();
						foreach ($columns as $index => $column) {
							$row->{$column} = $reader[$index];
						}
						$data[] = $row;
					}
				}

				$xlsx = $dataTable->showHeaders()->addRows($data)->attachToFile($_end, $_newLetter);

				$_raw_output_file   = explode('#', $_newLetter)[0] . '.xlsx';
				$_output_file       = explode('.xlsx', $_raw_output_file)[0];
				$_clean_output_file = explode(JPATH_SITE . DS . "tmp" . DS, $_output_file)[1] . '.xlsx';
			}
			catch (Exception $e) {
				$_destination = \PhpOffice\PhpSpreadsheet\IOFactory::load($_newLetter);
				$_destination->setActiveSheetIndex(0);
				$_destination->getActiveSheet()->fromArray($_readerData, null, 'A1');

				$writer = new Xlsx($_destination);

				$_raw_output_file   = explode('#', $_newLetter)[0] . '.xlsx';
				$_output_file       = explode('.xlsx', $_raw_output_file)[0];
				$_clean_output_file = explode(JPATH_SITE . DS . "tmp" . DS, $_output_file)[1] . '.xlsx';

				$writer->save($_raw_output_file);
			}

			copy($_raw_output_file, JPATH_SITE . DS . "tmp" . DS . $_clean_output_file);
			unlink($_raw_output_file);

			$result = array('status' => true, 'link' => $_clean_output_file);
		}

		echo json_encode((object) $result);
		exit();
	}

	public function export_xls_from_csv()
	{
		$result = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403);

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			/** PHPExcel */
			require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

			$csv             = $this->input->get('csv', null);
			$nbcol           = $this->input->get('nbcol', 0);
			$nbrow           = $this->input->get('start', 0);
			$excel_file_name = $this->input->get('excelfilename', null);
			$objReader       = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Csv");
			$objReader->setDelimiter("\t");
			$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

			// Excel colonne
			$colonne_by_id = array();
			for ($i = ord("A"); $i <= ord("Z"); $i++) {
				$colonne_by_id[] = chr($i);
			}

			for ($i = ord("A"); $i <= ord("Z"); $i++) {
				for ($j = ord("A"); $j <= ord("Z"); $j++) {
					$colonne_by_id[] = chr($i) . chr($j);
					if (count($colonne_by_id) == $nbrow) break;
				}
			}

			// Set properties
			$objPHPExcel->getProperties()->setCreator("eMundus SAS : https://www.emundus.fr/");
			$objPHPExcel->getProperties()->setLastModifiedBy("eMundus SAS");
			$objPHPExcel->getProperties()->setTitle("eMmundus Report");
			$objPHPExcel->getProperties()->setSubject("eMmundus Report");
			$objPHPExcel->getProperties()->setDescription("Report from open source eMundus plateform : https://www.emundus.fr/");
			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()->setTitle('Extraction');
			$objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

			$objPHPExcel->getActiveSheet()->freezePane('A2');

			$objReader->loadIntoExisting(JPATH_SITE . DS . "tmp" . DS . $csv, $objPHPExcel);

			$objConditional1 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
			$objConditional1->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS)
				->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL)
				->addCondition('0');
			$objConditional1->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');

			$objConditional2 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
			$objConditional2->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS)
				->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL)
				->addCondition('100');
			$objConditional2->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF00FF00');

			$objConditional3 = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
			$objConditional3->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS)
				->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_EQUAL)
				->addCondition('50');
			$objConditional3->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00');

			$i = 0;
			//FNUM
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
			$objPHPExcel->getActiveSheet()->getStyle('A2:A' . ($nbrow + 1))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			$i++;
			//STATUS
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('20');
			$i++;
			//LASTNAME
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('20');
			$i++;
			//FIRSTNAME
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('20');
			$i++;
			//EMAIL
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
			//$objPHPExcel->getActiveSheet()->getStyle('E2:E'.($nbrow+ 1))->getNumberFormat()->setFormatCode( PHPExcel_Style_Font::UNDERLINE_SINGLE );
			$i++;
			//CAMPAIGN
			$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
			$i++;

			for ($i; $i < $nbcol; $i++) {
				$value = $objPHPExcel->getActiveSheet()->getCell(Coordinate::stringFromColumnIndex($i) . '1')->getValue();

				if (strpos($value,'(%)')) {
					$conditionalStyles = $objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$i] . '1')->getConditionalStyles();
					array_push($conditionalStyles, $objConditional1);
					array_push($conditionalStyles, $objConditional2);
					array_push($conditionalStyles, $objConditional3);
					$objPHPExcel->getActiveSheet()->getStyle($colonne_by_id[$i] . '1')->setConditionalStyles($conditionalStyles);
					$objPHPExcel->getActiveSheet()->duplicateConditionalStyle($conditionalStyles, $colonne_by_id[$i] . '1:' . $colonne_by_id[$i] . ($nbrow + 1));
				}
				$objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
			}

			$randomString = JUserHelper::genRandomPassword(20);
			$objWriter    = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, "Xlsx");
			$objWriter->save(JPATH_SITE . DS . 'tmp' . DS . $excel_file_name . '_' . $nbrow . 'rows_' . $randomString . '.xlsx');
			$objPHPExcel->disconnectWorksheets();
			unset($objPHPExcel);
			$link = $excel_file_name . '_' . $nbrow . 'rows_' . $randomString . '.xlsx';
			if (!unlink(JPATH_SITE . DS . "tmp" . DS . $csv)) {
				$result = array('status' => false, 'msg' => 'ERROR_DELETE_CSV');
				echo json_encode((object) $result);
				exit();
			}

			$session = $this->app->getSession();
			$session->clear('fnums_export');
			$result = array('status' => true, 'link' => $link);
		}

		echo json_encode((object) $result);
		exit();

	}

	/**
	 * @param        $fnums
	 * @param        $objs
	 * @param        $element_id
	 * @param   int  $methode
	 *
	 * @return string
	 *
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
	 * @since version
	 */
	public function export_xls($fnums, $objs, $element_id, $methode = 0)
	{
		$current_user = JFactory::getUser();

		if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		@set_time_limit(10800);
		jimport('joomla.user.user');
		error_reporting(0);
		/** PHPExcel*/
		require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

		$m_files = $this->getModel('Files');
		$h_files = new EmundusHelperFiles;

		$elements   = $h_files->getElementsName(implode(',', $element_id));
		$fnumsArray = $m_files->getFnumArray($fnums, $elements, $methode);
		$status     = $m_files->getStatusByFnums($fnums);

		$menu         = $this->app->getMenu();
		$current_menu = $menu->getActive();
		$menu_params  = $menu->getParams($current_menu->id);

		$columnSupl = explode(',', $menu_params->get('em_actions'));
		$columnSupl = array_merge($columnSupl, $objs);
		$colOpt     = array();

		$m_application = $this->getModel('Application');

		foreach ($columnSupl as $col) {
			$col = explode('.', $col);
			switch ($col[0]) {
				case "photo":
					$colOpt['PHOTO'] = $h_files->getPhotos();
					break;
				case "forms":
					$colOpt['forms'] = $m_application->getFormsProgress($fnums);
					break;
				case "attachment":
					$colOpt['attachment'] = $m_application->getAttachmentsProgress($fnums);
					break;
				case "assessment":
					$colOpt['assessment'] = $h_files->getEvaluation('text', $fnums);
					break;
				case "comment":
					$colOpt['comment'] = $m_files->getCommentsByFnum($fnums);
					break;
				case 'evaluators':
					$colOpt['evaluators'] = $h_files->createEvaluatorList($col[1], $m_files);
					break;
				case 'group-assoc':
					$colOpt['group-assoc'] = $m_files->getAssocByFnums($fnums, true, false);
					break;
				case 'user-assoc':
					$colOpt['user-asoc'] = $m_files->getAssocByFnums($fnums, false, true);
					break;
				case 'overall':
					require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'evaluation.php');
					$m_evaluations     = $this->getModel('Evaluation');
					$colOpt['overall'] = $m_evaluations->getEvaluationAverageByFnum($fnums);
					break;
			}
		}

		// Excel colonne
		$colonne_by_id = array();
		for ($i = ord("A"); $i <= ord("Z"); $i++) {
			$colonne_by_id[] = chr($i);
		}
		for ($i = ord("A"); $i <= ord("Z"); $i++) {
			for ($j = ord("A"); $j <= ord("Z"); $j++) {
				$colonne_by_id[] = chr($i) . chr($j);
				if (count($colonne_by_id) == count($fnums)) {
					break;
				}
			}
		}
		// Create new PHPExcel object
		$objPHPSpreadsheet = new Spreadsheet();
		// Initiate cache

		$cacheMethod   = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
		$cacheSettings = array('memoryCacheSize' => '32MB');
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
		// Set properties
		$objPHPSpreadsheet->getProperties()->setCreator("eMundus SAS : https://www.emundus.fr/");
		$objPHPSpreadsheet->getProperties()->setLastModifiedBy("eMundus SAS");
		$objPHPSpreadsheet->getProperties()->setTitle("eMmundus Report");
		$objPHPSpreadsheet->getProperties()->setSubject("eMmundus Report");
		$objPHPSpreadsheet->getProperties()->setDescription("Report from open source eMundus plateform : https://www.emundus.fr/");


		$objPHPSpreadsheet->setActiveSheetIndex(0);
		$objPHPSpreadsheet->getActiveSheet()->setTitle('Extraction');
		$objPHPSpreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		$objPHPSpreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

		$objPHPSpreadsheet->getActiveSheet()->freezePane('A2');

		$i = 0;
		$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($i, 1, Text::_('COM_EMUNDUS_FILE_F_NUM'));
		$objPHPSpreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
		$i++;
		$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($i, 1, Text::_('COM_EMUNDUS_STATUS'));
		$objPHPSpreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('40');
		$i++;
		$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($i, 1, Text::_('COM_EMUNDUS_FORM_LAST_NAME'));
		$objPHPSpreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
		$i++;
		$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($i, 1, Text::_('COM_EMUNDUS_FORM_FIRST_NAME'));
		$objPHPSpreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
		$i++;
		$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($i, 1, Text::_('COM_EMUNDUS_EMAIL'));
		$objPHPSpreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
		$i++;
		$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($i, 1, Text::_('COM_EMUNDUS_CAMPAIGN'));
		$objPHPSpreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
		$i++;

		foreach ($elements as $fLine) {
			if ($fLine->element_name != 'fnum' && $fLine->element_name != 'code' && $fLine->element_name != 'campaign_id') {
				$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($i, 1, $fLine->element_label);
				$objPHPSpreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
				$i++;
			}
		}

		foreach ($colOpt as $kOpt => $vOpt) {
			$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($i, 1, Text::_(strtoupper($kOpt)));
			$objPHPSpreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setWidth('30');
			$i++;
		}

		$line = 2;
		foreach ($fnumsArray as $fnunLine) {
			$col = 0;
			foreach ($fnunLine as $k => $v) {
				if ($k != 'code' && strpos($k, 'campaign_id') === false) {

					if ($k === 'fnum') {
						$objPHPSpreadsheet->getActiveSheet()->setCellValueExplicitByColumnAndRow($col, $line, (string) $v, PHPExcel_Cell_DataType::TYPE_STRING);
						$col++;
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $status[$v]['value']);
						$col++;
						$uid        = intval(substr($v, 21, 7));
						$userProfil = JUserHelper::getProfile($uid)->emundus_profile;
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, strtoupper($userProfil['lastname']));
						$col++;
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $userProfil['firstname']);
						$col++;
					}
					else {
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $v);
						$col++;
					}
				}
			}

			foreach ($colOpt as $kOpt => $vOpt) {
				switch ($kOpt) {
					case "photo":
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, Text::_('COM_EMUNDUS_PHOTO'));
						break;
					case "attachment":
					case "forms":
						$val = $vOpt[$fnunLine['fnum']];
						$objPHPSpreadsheet->getActiveSheet()->getStyle($colonne_by_id[$col] . ':' . $colonne_by_id[$col])->getAlignment()->setWrapText(true);
						if ($val == 0) {
							$rgb = 'FF6600';
						}
						elseif ($val == 100) {
							$rgb = '66FF66';
						}
						elseif ($val == 50) {
							$rgb = 'FFFF00';
						}
						else {
							$rgb = 'FFFFFF';
						}
						$objPHPSpreadsheet->getActiveSheet()->getStyle($colonne_by_id[$col] . $line)->applyFromArray(
							[
								'fill' => [
									'filltype' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
									'color'    => ['argb' => 'FF' . $rgb]
								],
							]
						);
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $val . '%');
						$objPHPSpreadsheet->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
						break;
					case "assessment":
						$eval        = '';
						$evaluations = $vOpt[$fnunLine['fnum']];
						foreach ($evaluations as $evaluation) {
							$eval .= $evaluation;
							$eval .= chr(10) . '______' . chr(10);
						}
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $eval);
						break;
					case "comment":
						$comments = "";
						foreach ($colOpt['comment'] as $comment) {
							if ($comment['fnum'] == $fnunLine['fnum']) {
								$comments .= $comment['reason'] . " | " . $comment['comment_body'] . "\rn";
							}
						}
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $comments);
						break;
					case 'evaluators':
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, $vOpt[$fnunLine['fnum']]);
						break;
					case 'group-assoc':
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, Text::_('COM_EMUNDUS_ASSOCIATED_GROUPS'));
						break;
					case 'user-assoc':
						$objPHPSpreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $line, Text::_('COM_EMUNDUS_ASSOCIATED_USERS'));
						break;
					case 'overall':
						$objPHPSpreadsheet->getActiveSheet()->setCellValue([$col, $line], Text::_('COM_EMUNDUS_EVALUATIONS_OVERALL'));
						break;
				}
				$col++;
			}
			$line++;
		}

		$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPSpreadsheet);

		$objWriter->save(JPATH_SITE . DS . 'tmp' . DS . JFactory::getUser()->id . '_extraction.xls');

		return $this->_user->id . '_extraction.xls';
	}


	/**
	 * @param           $filename
	 * @param   string  $mimePath
	 *
	 * @return bool
	 */
	function get_mime_type($filename, $mimePath = '../etc')
	{
		$fileext = substr(strrchr($filename, '.'), 1);

		if (empty($fileext)) {
			return false;
		}

		$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
		$lines = file("$mimePath/mime.types");
		foreach ($lines as $line) {
			if (substr($line, 0, 1) == '#') {
				continue;
			}
			$line = rtrim($line) . " ";
			if (!preg_match($regex, $line, $matches)) {
				continue;
			}

			return $matches[1];
		}

		return false;
	}

	/**
	 *
	 */
	public function download()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$name = $this->input->getString('name', null);

		$file = JPATH_SITE . DS . 'tmp' . DS . $name;

		if (file_exists($file)) {
			$mime_type = $this->get_mime_type($file);
			header('Content-type: application/' . $mime_type);
			header('Content-Disposition: inline; filename=' . basename($file));
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Pragma: anytextexeptno-cache', true);
			header('Cache-control: private');
			header('Expires: 0');

			ob_clean();
			ob_end_flush();
			readfile($file);
			exit;
		}
		else {
			echo Text::_('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND') . ' : ' . $file;
		}
	}

	/**
	 *  Create a zip file containing all documents attached to application fil number
	 *
	 * @param   array  $fnums
	 *
	 * @return string
	 */
	function export_zip($fnums, $form_post = 1, $attachment = 1, $eval_steps = [], $form_ids = null, $attachids = null, $options = null, $acl_override = false)
	{
		$view         = $this->input->get('view');
		$current_user = $this->app->getIdentity();

		if ((!EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) && $view != 'renew_application' && !$acl_override) {
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$m_files = $this->getModel('Files');

		return $m_files->exportZip($fnums, $form_post, $attachment, $eval_steps, $form_ids, $attachids, $options, false, $current_user);
	}

	/**
	 * @param $fnums
	 *
	 * @return string
	 */
	function export_zip_pcl($fnums)
	{
		$view         = $this->input->get('view');
		$current_user = JFactory::getUser();

		if ((!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) && $view != 'renew_application')
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
		require_once(JPATH_SITE . DS . 'libraries' . DS . 'emundus' . DS . 'pdf.php');
		require_once(JPATH_SITE . DS . 'libraries' . DS . 'pclzip-2-8-2' . DS . 'pclzip.lib.php');


		$nom  = date("Y-m-d") . '_' . rand(1000, 9999) . '_x' . (count($fnums) - 1) . '.zip';
		$path = JPATH_SITE . DS . 'tmp' . DS . $nom;

		$zip = new PclZip($path);

		$m_files = $this->getModel('Files');
		$files   = $m_files->getFilesByFnums($fnums);

		if (file_exists($path))
			unlink($path);

		$users = array();
		foreach ($fnums as $fnum) {
			$sid          = intval(substr($fnum, -7));
			$users[$fnum] = JFactory::getUser($sid);

			if (!is_numeric($sid) || empty($sid))
				continue;

			$dossier = EMUNDUS_PATH_ABS . $users[$fnum]->id;
			$dir     = $fnum . '_' . $users[$fnum]->name;
			application_form_pdf($users[$fnum]->id, $fnum, false);
			$application_pdf = $fnum . '_application.pdf';

			$zip->add($dossier . DS . $application_pdf, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_ADD_PATH, $dir);

		}


		foreach ($files as $key => $file) {
			$dir     = $file['fnum'] . '_' . $users[$file['fnum']]->name;
			$dossier = EMUNDUS_PATH_ABS . $users[$file['fnum']]->id . DS;
			$zip->add($dossier . $file['filename'], PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_ADD_PATH, $dir);
		}

		return $nom;
	}

	/*
     *   Get evaluation Fabrik formid by fnum
     *
     *
     */
	function getformid()
	{
		$current_user = JFactory::getUser();

		if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));

		$fnum = $this->input->getString('fnum', null);

		$m_files = $this->getModel('Files');
		$res     = $m_files->getFormidByFnum($fnum);

		$formid = ($res > 0) ? $res : 29;

		$result = array('status' => true, 'formid' => $formid);
		echo json_encode((object) $result);
		exit();
	}

	/*
      *   Get evaluation Fabrik formid by fnum
      *
      *
      */
	function getdecisionformid()
	{
		$current_user = JFactory::getUser();

		if (!@EmundusHelperAccess::asPartnerAccessLevel($current_user->id))
			die(Text::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));

		$fnum = $this->input->getString('fnum', null);

		$m_files = $this->getModel('Files');
		$res     = $m_files->getDecisionFormidByFnum($fnum);

		$formid = ($res > 0) ? $res : 29;

		$result = array('status' => true, 'formid' => $formid);
		echo json_encode((object) $result);
		exit();
	}

	//todo: jeremy stopped here

	public function exportzipdoc()
	{
		$idFiles = $this->input->getString('ids', '');

		$files = [];
		if (!empty($idFiles)) {
			$idFiles = explode(',', $idFiles);

			$m_files = $this->getModel('Files');
			$files   = $m_files->getAttachmentsById(array_unique($idFiles));
		}

		if (!empty($files)) {
			$nom  = date("Y-m-d") . '_' . md5(rand(1000, 9999) . time()) . '.zip';
			$path = JPATH_SITE . DS . 'tmp' . DS . $nom;

			if (extension_loaded('zip')) {
				$zip = new ZipArchive();

				if ($zip->open($path, ZipArchive::CREATE) == true) {
					foreach ($files as $key => $file) {
						$filename = EMUNDUS_PATH_ABS . $file['applicant_id'] . DS . $file['filename'];
						if (!$zip->addFile($filename, $file['filename'])) {
							Log::add('Error when trying to add file to zip archive : ' . $filename, Log::ERROR, 'com_emundus');
							continue;
						}
					}
					$zip->close();
				}
				else {
					die("ERROR");
				}

			}
			else {
				require_once(JPATH_SITE . DS . 'libraries' . DS . 'pclzip-2-8-2' . DS . 'pclzip.lib.php');
				$zip = new PclZip($path);

				foreach ($files as $key => $file) {
					$user     = JFactory::getUser($file['applicant_id']);
					$dir      = $file['fnum'] . '_' . $user->name;
					$filename = EMUNDUS_PATH_ABS . $file['applicant_id'] . DS . $file['filename'];

					$zip->add($filename, PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_ADD_PATH, $dir);

					if (!$zip->addFile($filename, $file['filename'])) {
						continue;
					}
				}
			}

			$mime_type = $this->get_mime_type($path);
			header('Content-type: application/' . $mime_type);
			header('Content-Disposition: inline; filename=' . basename($path));
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Pragma: anytextexeptno-cache', true);
			header('Cache-control: private');
			header('Expires: 0');
			ob_clean();
			flush();
			readfile($path);
			exit;
		}
	}

	public function getPDFProgrammes()
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		$html = '';

		$m_files = $this->getModel('Files');

		$fnums_post  = $this->input->getVar('checkInput', null);
		$fnums_array = ($fnums_post == 'all') ? 'all' : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);

		if ($fnums_array == 'all') {
			$fnums = $m_files->getAllFnums();
		}
		else {
			$fnums = array();
			foreach ($fnums_array as $key => $value) {
				$fnums[] = $value;
			}
		}


		$m_campaigns = $this->getModel('Campaign');

		if (!empty($fnums)) {
			foreach ($fnums as $fnum) {
				if ($fnum != "em-check-all") {
					$campaign  = $m_campaigns->getCampaignByFnum($fnum);
					$programme = $m_campaigns->getProgrammeByCampaignID((int) $campaign->id);
					$option    = '<option value="' . $programme['code'] . '">' . $programme['label'] . '</option>';
					if (strpos($html, $option) === false) {
						$html .= $option;
					}
				}
			}
		}

		echo json_encode((object) (array('status' => true, 'html' => $html)));
		exit;
	}

	public function getPDFCampaigns()
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		$html = '';

		$m_files = $this->getModel('Files');

		$code = $this->input->getString('code', null);

		$fnums_post  = $this->input->getVar('checkInput', null);
		$fnums_array = ($fnums_post == 'all') ? 'all' : (array) json_decode(stripslashes($fnums_post), false, 512, JSON_BIGINT_AS_STRING);

		if ($fnums_array == 'all') {
			$fnums = $m_files->getAllFnums();
		}
		else {
			$fnums = array();
			foreach ($fnums_array as $key => $value) {
				$fnums[] = $value;
			}
		}

		$m_campaigns = $this->getModel('Campaign');
		$nbcamp      = 0;
		if (!empty($fnums)) {

			foreach ($fnums as $fnum) {
				$campaign = $m_campaigns->getCampaignByFnum($fnum);
				if ($campaign->training == $code) {
					$nbcamp += 1;
					$option = '<option value="' . $campaign->id . '">' . $campaign->label . ' (' . $campaign->year . ')</option>';
					if (strpos($html, $option) === false) {
						$html .= $option;
					}
				}

			}
		}

		echo json_encode((object) (array('status' => true, 'html' => $html, 'nbcamp' => $nbcamp)));
		exit;
	}


	public function getProgrammes()
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'campaign.php');
		$html        = '';
		$session     = JFactory::getSession();
		$filt_params = $session->get('filt_params');

		$h_files    = new EmundusHelperFiles;
		$programmes = $h_files->getProgrammes($filt_params['programme']);

		$nbprg = count($programmes);
		if (empty($filt_params)) {
			$params['programme'] = $programmes;
			$session->set('filt_params', $params);
		}
		foreach ($programmes as $p) {
			if ($nbprg == 1) {
				$html .= '<option value="' . $p->code . '" selected>' . $p->label . ' - ' . $p->code . '</option>';
			}
			else {
				$html .= '<option value="' . $p->code . '">' . $p->label . ' - ' . $p->code . '</option>';
			}
		}

		echo json_encode((object) (array('status' => true, 'html' => $html, 'nbprg' => $nbprg)));
		exit;
	}

	public function getProgramCampaigns()
	{
		$html = '';

		$h_files = new EmundusHelperFiles;

		$code      = $this->input->getString('code', null);
		$campaigns = $h_files->getProgramCampaigns($code);

		$nbcamp = count($campaigns);
		foreach ($campaigns as $c) {
			if ($nbcamp == 1) {
				$html .= '<option data-year="' . $c->year . '" data-training="' . $c->training . '" value="' . $c->id . '" selected>' . $c->label . ' - ' . $c->training . ' (' . $c->year . ')</option>';
			}
			else {
				$html .= '<option data-year="' . $c->year . '" data-training="' . $c->training . '"  value="' . $c->id . '">' . $c->label . ' - ' . $c->training . ' (' . $c->year . ')</option>';
			}
		}

		echo json_encode((object) (array('status' => true, 'html' => $html, 'nbcamp' => $nbcamp, 'campaigns' => $campaigns)));
		exit;
	}

	public function saveExcelFilter()
	{
		$current_user = JFactory::getUser();

		$name   = $this->input->getString('filt_name', null);
		$itemid = $this->input->get->get('Itemid', null);

		$params      = $this->input->getString('params', null);
		$constraints = json_encode(array('excelfilter' => $params));

		$h_files = new EmundusHelperFiles;
		if (empty($itemid)) {
			$itemid = $this->input->post->get('Itemid', null);
		}

		$time_date = (date('Y-m-d H:i:s'));
		$result    = $h_files->saveExcelFilter($current_user->id, $name, $constraints, $time_date, $itemid);

		echo json_encode((object) (array('status' => true, 'filter' => $result)));
		exit;
	}

	public function savePdfFilter()
	{

		$time_date    = (date('Y-m-d H:i:s'));
		$current_user = JFactory::getUser();
		$name         = $this->input->getRaw('filt_name', null);

		$params      = $this->input->getRaw('params', null);
		$constraints = json_encode(array('pdffilter' => $params));

		$itemid = $this->input->get->get('Itemid', null);
		$mode   = $this->input->getRaw('mode', null);

		$h_files = new EmundusHelperFiles;
		if (empty($itemid)) {
			$itemid = $this->input->post->get('Itemid', null);
		}

		$pdfParams = array('time_date' => $time_date, 'user' => $current_user->id, 'name' => $name, 'constraints' => $constraints, 'item_id' => $itemid, 'mode' => $mode);
		$result    = $h_files->savePdfFilter($pdfParams);

		echo json_encode((object) (array('status' => true, 'filter' => $result)));
		exit;
	}

	public function deletePdfFilter()
	{

		$_fid    = $this->input->getVar('fid');
		$h_files = new EmundusHelperFiles;

		$_result = $h_files->deletePdfFilter($_fid);
		echo json_encode((object) (array('status' => true, 'result' => $_result)));
		exit;
	}

	public function getExportExcelFilter()
	{
		$response = array('status' => false, 'filter' => []);
		$user_id  = JFactory::getUser()->id;

		if (!empty($user_id)) {
			$h_files = new EmundusHelperFiles;
			$filters = $h_files->getExportExcelFilter($user_id);

			if ($filters !== false) {
				$response = array('status' => true, 'filter' => $filters);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getAllExportPdfFilter()
	{
		$user_id = JFactory::getUser()->id;

		$h_files = new EmundusHelperFiles;
		$filters = $h_files->getAllExportPdfFilter($user_id);

		echo json_encode((object) (array('status' => true, 'filter' => $filters)));
		exit;
	}

	public function getExportPdfFilterById()
	{
		$modelId = $this->input->getRaw('id');

		$h_files = new EmundusHelperFiles;
		$filters = $h_files->getExportPdfFilterById($modelId);

		echo json_encode((object) (array('status' => true, 'filter' => $filters)));
		exit;
	}

	public function getExportExcelFilterById()
	{
		$user_id = JFactory::getUser()->id;

		$fid = $this->input->getVar('id', null);

		$h_files = new EmundusHelperFiles;
		$filters = $h_files->getExportExcelFilterById($fid);

		echo json_encode((object) (array('status' => true, 'filter' => $filters)));
		exit;
	}

	public function getAllLetters()
	{
		$h_files = new EmundusHelperFiles;
		$letters = $h_files->getAllLetters();

		echo json_encode((object) (array('status' => true, 'letters' => $letters)));
		exit;
	}

	public function getexcelletter()
	{
		$h_files = new EmundusHelperFiles;

		$lid = $this->input->getVar('letter', null);

		$letter = $h_files->getExcelLetterById($lid);

		echo json_encode((object) (array('status' => true, 'letter' => $letter)));
		exit;
	}

	public function checkforms()
	{
		$user_id = $this->_user->id;
		$code = $this->input->getString('code', null);
		$m_eval = $this->getModel('Evaluation');
		$eval = $m_eval->getGroupsEvalByProgramme($code);

		$hasAccessForm = EmundusHelperAccess::asAccessAction(1, 'r', $user_id);
		$hasAccessAtt  = EmundusHelperAccess::asAccessAction(4, 'r', $user_id);
		$hasAccessTags = EmundusHelperAccess::asAccessAction(14, 'r', $user_id) || EmundusHelperAccess::asAccessAction(14, 'c', $user_id);

		$show_form = 0;
		$show_attachments  = 0;
		$show_tag  = 0;
		$show_eval = 0;

		if ($eval) {
			$show_eval = 1;
		}

		if ($hasAccessForm) {
			$show_form = 1;
		}
		if ($hasAccessAtt) {
			$show_attachments = 1;
		}
		if ($hasAccessTags) {
			$show_tag = 1;
		}

		echo json_encode((object) (array('status' => true, 'att' => $show_attachments, 'eval_steps' => $show_eval, 'tag' => $show_tag, 'form' => $show_form)));
		exit;

	}

	/**
	 * Generates or (if it exists already) loads the PDF for a certain GesCOF product.
	 */
	public function getproductpdf()
	{

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'export.php');

		$h_export = new EmundusHelperExport();

		$product_code = $this->input->post->get('product_code', null);

		$filename = DS . 'images' . DS . 'product_pdf' . DS . 'formation-' . $product_code . '.pdf';

		// PDF is rebuilt every time, this is because the information on the PDF probably changes ofter.
		if (file_exists(JPATH_SITE . $filename)) {
			unlink(JPATH_SITE . $filename);
		}

		// The PDF template is saved in the Joomla backoffice as an article.
		$article = $h_export->getArticle(58);

		if (empty($article)) {
			echo json_encode((object) ['status' => false, 'msg' => 'Article not found.']);
			exit;
		}

		$query = $this->_db->getQuery(true);
		$query
			->select(['DISTINCT(tu.session_code) AS session_code',
				$this->_db->quoteName('p.label', 'name'), $this->_db->quoteName('p.numcpf', 'cpf'), $this->_db->quoteName('p.prerequisite', 'prerec'), $this->_db->quoteName('p.audience', 'audience'), $this->_db->quoteName('p.tagline', 'tagline'), $this->_db->quoteName('p.objectives', 'objectives'), $this->_db->quoteName('p.content', 'content'), $this->_db->quoteName('p.manager_firstname', 'manager_firstname'), $this->_db->quoteName('p.manager_lastname', 'manager_lastname'), $this->_db->quoteName('p.pedagogie', 'pedagogie'), $this->_db->quoteName('p.partner', 'partner'), $this->_db->quoteName('p.evaluation', 'evaluation'), $this->_db->quoteName('p.temoignagesclients', 'temoignagesclients'), $this->_db->quoteName('p.accrochecom', 'accrochecom'),
				$this->_db->quoteName('t.label', 'theme'), $this->_db->quoteName('t.color', 'class'),
				$this->_db->quoteName('tu.price', 'price'), $this->_db->quoteName('tu.date_start', 'date_start'), $this->_db->quoteName('tu.date_end', 'date_end'), $this->_db->quoteName('tu.days', 'days'), $this->_db->quoteName('tu.hours', 'hours'), $this->_db->quoteName('tu.time_in_company', 'time_in_company'), $this->_db->quoteName('tu.min_occupants', 'min_o'), $this->_db->quoteName('tu.max_occupants', 'max_o'), $this->_db->quoteName('tu.occupants', 'occupants'), $this->_db->quoteName('tu.location_city', 'city'), $this->_db->quoteName('tu.location_title'), $this->_db->quoteName('tu.tax_rate', 'tax_rate'), $this->_db->quoteName('tu.intervenant', 'intervenant'), $this->_db->quoteName('tu.label', 'session_label')
			])
			->from($this->_db->quoteName('#__emundus_setup_programmes', 'p'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_thematiques', 't') . ' ON ' . $this->_db->quoteName('t.id') . ' = ' . $this->_db->quoteName('p.programmes'))
			->leftJoin($this->_db->quoteName('#__emundus_setup_teaching_unity', 'tu') . ' ON ' . $this->_db->quoteName('tu.code') . ' = ' . $this->_db->quoteName('p.code'))
			->where($this->_db->quoteName('p.code') . ' LIKE ' . $this->_db->quote($product_code) . ' AND ' . $this->_db->quoteName('tu.published') . ' = 1 AND ' . $this->_db->quoteName('tu.date_start') . ' >= ' . date("Y-m-d"))
			->order($this->_db->quoteName('tu.date_start') . ' ASC');
		$this->_db->setQuery($query);

		try {
			$product = $this->_db->loadAssocList();

			//GET Taux de satisfaction from GESCOF
			$http = new JHttp();

			try {
				$result = $http->get('https://ccirochefort.evaluations.ovh/Facett3?Societe=1&Mode=Evaluations&ExtractionDonnees=TauxSatisfaction&CodeProduit=' . $product_code);

				$res = json_decode($result->body);


				$taux   = number_format((float) $res->Taux * 100, 2, '.', '');
				$nbAvis = $res->NbAvis;

				$indicateursFormation = "<p><b>Taux de satisfaction : </b>$taux%</p><p><b>Nombre d'avis : </b>$nbAvis</p>";
			}
			catch (Exception $e) {
				$indicateursFormation = "";
			}
		}
		catch (Exception $e) {
			echo json_encode((object) ['status' => false, 'msg' => 'Error getting product information.']);
			exit;
		}

		setlocale(LC_ALL, 'fr_FR.utf8');
		$sessions = "<ul>";
		foreach ($product as $session) {
			if (strtotime($session['date_end']) >= strtotime("now")) {

				$start_month = date('m', strtotime($session['date_start']));
				$end_month   = date('m', strtotime($session['date_end']));
				$start_year  = date('y', strtotime($session['date_start']));
				$end_year    = date('y', strtotime($session['date_end']));

				if (intval($session['days']) == 1) {

					$sessions .= '<li>Le ' . strftime('%e', strtotime($session['date_start'])) . " " . strftime('%B', strtotime($session['date_end'])) . " " . date('Y', strtotime($session['date_end']));

				}
				else {

					if ($start_month == $end_month && $start_year == $end_year) {
						$sessions .= '<li>' . strftime('%e', strtotime($session['date_start'])) . " au " . strftime('%e', strtotime($session['date_end'])) . " " . strftime('%B', strtotime($session['date_end'])) . " " . date('Y', strtotime($session['date_end']));
					}
					elseif ($start_month != $end_month && $start_year == $end_year) {
						$sessions .= '<li>' . strftime('%e', strtotime($session['date_start'])) . " " . strftime('%B', strtotime($session['date_start'])) . " au " . strftime('%e', strtotime($session['date_end'])) . " " . strftime('%B', strtotime($session['date_end'])) . " " . date('Y', strtotime($session['date_end']));
					}
					elseif (($start_month != $end_month && $start_year != $end_year) || ($start_month == $end_month && $start_year != $end_year)) {
						$sessions .= '<li>' . strftime('%e', strtotime($session['date_start'])) . " " . strftime('%B', strtotime($session['date_end'])) . " " . date('Y', strtotime($session['date_start'])) . " au " . strftime('%e', strtotime($session['date_end'])) . " " . strftime('%B', strtotime($session['date_end'])) . " " . date('Y', strtotime($session['date_end']));
					}
				}

				$sessionCity = !empty($session['city']) ? ' à ' . ucfirst(str_replace(' cedex', '', mb_strtolower($session['city']))) : ' ' . $session['location_title'];
				$sessions    .= $sessionCity . ' : ' . $session['price'] . ' € ' . (!empty($session['tax_rate']) ? 'HT' : 'net de taxe') . '</li>';
			}
		}
		$sessions .= '</ul>';

		$partner = str_replace(' ', '-', trim(strtolower($product[0]['partner'])));
		if (!empty($partner)) {
			$partner = '<img src="images/custom/ccirs/partenaires/' . $partner . '.png" height="30">';
		}
		else {
			$partner = '';
		}

		if (!empty($product[0]['days']) && !empty($product[0]['hours'])) {
			$days = $product[0]['days'] . ' ' . ((intval($product[0]['days']) > 1) ? 'jours' : 'jour') . " pour un total de : " . $product[0]['hours'] . " heures";
			if (!empty($session['time_in_company'])) {
				$days .= ' ' . $product[0]['time_in_company'];
			}
		}
		else {
			$days = 'Aucune information disponible.';
		}

		// Build the variables found in the article.
		$post = [
			'/{PARTNER_LOGO}/'          => $partner,
			'/{PRODUCT_CODE}/'          => str_replace('FOR', '', $product_code),
			'/{PRODUCT_NAME}/'          => ucfirst(mb_strtolower($product[0]['session_label'])),
			'/{PRODUCT_OBJECTIVES}/'    => str_replace("\n", "", $product[0]['objectives']),
			'/{PRODUCT_PREREQUISITES}/' => str_replace("\n", "", $product[0]['prerec']),
			'/{PRODUCT_AUDIENCE}/'      => str_replace("\n", "", $product[0]['audience']),
			'/{PRODUCT_CONTENT}/'       => str_replace("\n", "", $product[0]['content']),
			'/{PRODUCT_MANAGER}/'       => $product[0]['manager_firstname'] . ' ' . mb_strtoupper($product[0]['manager_lastname']),
			'/{EXPORT_DATE}/'           => date('d F Y'),
			'/{DAYS}/'                  => $days,
			'/{SESSIONS}/'              => $sessions,
			'/{EFFECTIFS}/'             => 'Mini : ' . $product[0]['min_o'] . ' - Maxi : ' . $product[0]['max_o'],
			'/{INTERVENANT}/'           => (!empty($product[0]['intervenant'])) ? $product[0]['intervenant'] : 'Formateur consultant sélectionné par la CCI pour son expertise dans ce domaine',
			'/{PEDAGOGIE}/'             => $product[0]['pedagogie'],
			'/{CPF}/'                   => (!empty($product[0]['cpf'])) ? '<h2 style="padding-left: 30px;">' . Text::_('CODE') . '</h2><p style="padding-left: 30px;">' . $product[0]['cpf'] . ' </p>' : '',
			'/{EVALUATION}/'            => $product[0]['evaluation'],
			'/{TEMOINAGE}/'             => $product[0]['temoignagesclients'],
			'/{ACCROCHECOM}/'           => ucfirst(mb_strtolower(strip_tags($product[0]['accrochecom']))),
			'/{INDICATEURS}/'           => $indicateursFormation
		];

		$export_date = strftime('%e') . " " . strftime('%B') . " " . date('Y');

		$body   = html_entity_decode(preg_replace('~<(\w+)[^>]*>(?>[\p{Z}\p{C}]|<br\b[^>]*>|&(?:(?:nb|thin|zwnb|e[nm])sp|zwnj|#xfeff|#xa0|#160|#65279);)*</\1>~iu', '', preg_replace(array_keys($post), $post, preg_replace("/<br[^>]+\>/i", "<br>", $article))));
		$footer = '<hr style="margin=0; padding=0;"><span>Les CCI de Charente-Maritime se réservent le droit d’adapter les informations de cette fiche.</br>La CCIRS est un organisme de formation enregistré sous le numéro 5417 P00 1017. La CCI La Rochelle est un organisme de formation déclaré sous le n° 54 17 P00 04 17. Les CCI de Charente-Maritime sont référencées Datadock.</span><br/><span>{PRODUCT_MANAGER} - competencesetformation@rochefort.cci.fr - 05 46 84 70 92 - www.competencesetformation.fr</span><br/><span>Consultez les CGV dans la rubrique Infos Pratiques sur le site <a href="https://www.competencesetformation.fr" target="_blank">www.competencesetformation.fr</a></span><br/><span>Fiche pédagogique éditée le ' . $export_date . ' - ';
		$footer = html_entity_decode(preg_replace('~<(\w+)[^>]*>(?>[\p{Z}\p{C}]|<br\b[^>]*>|&(?:(?:nb|thin|zwnb|e[nm])sp|zwnj|#xfeff|#xa0|#160|#65279);)*</\1>~iu', '', preg_replace(array_keys($post), $post, preg_replace("/<br[^>]+\>/i", "<br>", $footer))));

		require_once(JPATH_LIBRARIES . DS . 'emundus' . DS . 'pdf.php');
		$filename = generatePDFfromHTML($body, $filename, $footer);

		if ($filename == false) {
			echo json_encode((object) ['status' => false, 'msg' => 'Error generating PDF.']);
			exit;
		}
		else {
			echo json_encode((object) ['status' => true, 'filename' => $filename . '?' . uniqid()]);
			exit;
		}

	}


	public function getValueByFabrikElts($fabrikElts, $fnumsArray)
	{
		$m_files = $this->getModel('Files');

		$fabrikValues = null;
		foreach ($fabrikElts as $elt) {

			$params         = json_decode($elt['params']);
			$groupParams    = json_decode($elt['group_params']);
			$isDate         = (in_array($elt['plugin'], ['date','jdate']));
			$isDatabaseJoin = ($elt['plugin'] === 'databasejoin');

			if (@$groupParams->repeat_group_button == 1 || $isDatabaseJoin) {
				$fabrikValues[$elt['id']] = $m_files->getFabrikValueRepeat($elt, $fnumsArray, $params, $groupParams->repeat_group_button == 1);
			}
			else {
				if ($isDate) {
					if($elt['plugin'] == 'jdate') {
						$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name'], $params->jdate_form_format);
					} else {
						$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name'], $params->date_form_format);
					}
				}
				else {
					$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name']);
				}
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
					$fabrikValues[$elt['id']] = $m_files->getFabrikValueRepeat($elt, $fnumsArray, $params, $groupParams->repeat_group_button == 1);
				}
				else {
					$fabrikValues[$elt['id']] = $m_files->getFabrikValue($fnumsArray, $elt['db_table_name'], $elt['name']);
				}
			}
		}

		return $fabrikValues;
	}

	public function exportfile()
	{

		$fnums = $this->input->post->getString('fnums');
		$type  = $this->input->post->getString('type');

		if (empty($fnums)) {
			echo json_encode((object) (array('status' => false, 'msg' => Text::_('COM_EMUNDUS_EXPORTS_FILES_EXPORTED_TO_EXTERNAL_ERROR'))));
			exit;
		}

		$fnums = (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);

		JPluginHelper::importPlugin('emundus');


		$status = $this->app->triggerEvent('onExportFiles', array($fnums, $type));
		$this->app->triggerEvent('onCallEventHandler', ['onExportFiles', ['fnums' => $fnums, 'type' => $type]]);

		if (is_array($status) && !in_array(false, $status)) {
			$msg    = Text::_('COM_EMUNDUS_EXPORTS_FILES_EXPORTED_TO_EXTERNAL');
			$result = true;
		}
		else {
			$msg    = Text::_('COM_EMUNDUS_EXPORTS_FILES_EXPORTED_TO_EXTERNAL_ERROR');
			$result = false;
		}

		echo json_encode((object) (array('status' => $result, 'msg' => $msg)));
		exit;
	}

	public function getfabrikdatabyelements()
	{
		$h_files = new EmundusHelperFiles;

		$elts         = $this->input->getVar('elts', null);
		$_fabrik_data = $h_files->getFabrikDataByListElements($elts);

		echo json_encode((object) (array('status' => true, 'fabrik_data' => $_fabrik_data)));
		exit;
	}

	public function getselectedelements()
	{
		$h_files   = new EmundusHelperFiles;
		$_elements = $this->input->getVar('elts', null);

		$_getElements = $h_files->getSelectedElements($_elements);

		echo json_encode((object) (array('status' => true, 'elements' => $_getElements)));
		exit;
	}

	public function generateletter()
	{
		$res = array('status' => false, 'data' => []);

		$fnums     = $this->input->post->getRaw('fnums');
		if(!empty($fnums))
		{
			$fnums_array = explode(',', $fnums);

			if (EmundusHelperAccess::asAccessAction(27, 'c', $this->_user->id) || (sizeof($fnums_array) === 1 && EmundusHelperAccess::isFnumMine($this->_user->id, $fnums)))
			{
				$templates = $this->input->post->getRaw('ids_tmpl');
				$canSee    = $this->input->post->getRaw('cansee', 0);

				$showMode  = $this->input->post->getInt('showMode', 0);
				$mergeMode = $this->input->post->getInt('mergeMode', 0);
				$force_replace_document = $this->input->getInt('force_replace_document', 0) == 1;

				require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'evaluation.php');
				$_mEval = $this->getModel('Evaluation');

				$res['data'] = $_mEval->generateLetters($fnums, $templates, $canSee, $showMode, $mergeMode, $force_replace_document);
				ob_clean();

				if ($res['data'])
				{
					$this->app->triggerEvent('onAfterGenerateLetters', ['letters' => $res['data'], 'fnums' => $fnums]);
					$this->app->triggerEvent('onCallEventHandler', ['onAfterGenerateLetters', ['letters' => $res['data'], 'fnums' => $fnums]]);

					$res['status'] = true;
				}
			}
		}

		echo json_encode((object) $res);
		exit;
	}

	public function getfabrikvaluebyid()
	{
		$res = ['status' => false, 'data' => []];

		if(EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$fabrikIds = $this->input->post->getRaw('elements', null);

			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'email.php');

			$m_emails = $this->getModel('Emails');
			$m_files  = $this->getModel('Files');

			$tag_ids = [];

			foreach ($fabrikIds as $tag)
			{
				$vars      = $m_files->getVariables($tag);
				$tag_ids[] = reset($vars);
			}

			$res['data'] = $m_emails->getEmailsFromFabrikIds($tag_ids);
			$res['status'] = true;
		}

		echo json_encode((object) $res);
		exit;
	}

	public function getactionsonfnum()
	{
		$user   = JFactory::getUser()->id;
		$fnum   = $this->input->post->getString('fnum');
		$offset = $this->input->post->getInt('offset', null);

		// get request data //
		$crud    = $this->input->post->get('crud');                 // crud
		$types   = $this->input->post->get('types');               // log id
		$persons = $this->input->post->get('persons');           // person

		$fnumErrorList = [];

		if (EmundusHelperAccess::asAccessAction(37, 'r', $user, $fnum)) {
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
			$m_logs = $this->getModel('Logs');

			$res     = $m_logs->getActionsOnFnum($fnum, $persons, $types, $crud, $offset);
			$details = [];

			if (empty($res)) {
				$fnumErrorList[] = $fnum;
			}
			else {
				foreach ($res as $log) {
					$details[] = $m_logs->setActionDetails($log->action_id, $log->verb, $log->params);
				}
			}
		}
		else {
			$fnumErrorList[] = $fnum;
		}

		if (empty($fnumErrorList)) {
			echo json_encode((object) (array('status' => true, 'res' => $res, 'details' => $details)));
		}
		else {
			echo json_encode((object) (array('status' => false, 'msg' => Text::_('ERROR') . implode(', ', $fnumErrorList))));
		}
		exit;
	}


	public function getattachmentcategories()
	{
		$m_files    = $this->getModel('Files');
		$categories = $m_files->getAttachmentCategories();

		echo json_encode((array('status' => true, 'categories' => $categories)));
		exit;
	}

	public function getattachmentprogress()
	{
		$fnum = $this->input->get->getString('fnum', '');

		if (!empty($fnum)) {
			$m_files  = $this->getModel('Files');
			$progress = $m_files->getAttachmentProgress(array($fnum));
			echo json_encode((array('status' => true, 'progress' => $progress)));
			exit;
		}

		echo json_encode((array('status' => false, 'msg' => 'missing fnum')));
		exit;
	}

	public function isdataanonymized()
	{
		$user    = JFactory::getSession()->get('emundusUser');
		$status  = false;
		$anonyme = false;
		$msg     = '';

		if (!empty($user)) {
			$anonyme = EmundusHelperAccess::isDataAnonymized($user->id);
			$status  = true;
		}

		echo json_encode((array('status' => $status, 'anonyme' => $anonyme, 'msg' => $msg)));
		exit;
	}

	public function exportLogs()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];
		$user = $this->_user;
		$fnum = $this->input->getString('fnum', '');

		if (!empty($fnum) && EmundusHelperAccess::asAccessAction(37, 'r', $user->id, $fnum))
		{
			// get crud, types, persons
			$crud    = json_decode($this->input->getString('crud', ''));
			$types   = json_decode($this->input->getString('types', ''));
			$persons = json_decode($this->input->getString('persons', ''));

			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
			$m_logs = $this->getModel('Logs');
			$data    = $m_logs->exportLogs($fnum, $persons, $types, $crud);

			$response = [
				'status' => true,
				'code'   => 200,
				'msg'    => Text::_('COM_EMUNDUS_LOGS_EXPORT_SUCCESS'),
				'data'   => $data
			];
		}

		$this->sendJsonResponse($response);
	}

	public function checkIfSomeoneElseIsEditing()
	{
		$format = $this->input->get->getString('format', 'json');
		$data   = [];
		$status = false;

		$config                               = JComponentHelper::getParams('com_emundus');
		$display_other_user_editing_same_file = $config->get('display_other_user_editing_same_file', 0);

		if ($display_other_user_editing_same_file) {
			$fnum = $this->input->get->getString('fnum', '');

			if (!empty($fnum) && EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
				$m_files = $this->getModel('Files');
				$data    = $m_files->checkIfSomeoneElseIsEditing($fnum);
				$status  = !empty($data);
			}
		}

		if ($format == 'json') {
			echo json_encode((array('status' => $status, 'data' => $data)));
			exit;
		}

		return !empty($data) ? $data : false;
	}

	public function getalllogactions()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asAccessAction(37, 'r', $this->_user->id)) {
			require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
			$m_files            = $this->getModel('Files');
			$response['data']   = $m_files->getAllLogActions();
			$response['status'] = true;
			$response['code']   = 200;
			$response['msg']    = Text::_('SUCCESS');
		}

		echo json_encode($response);
		exit;
	}

	public function getuserslogbyfnum()
	{
		$fnum = $this->input->getString('fnum', '');

		if (EmundusHelperAccess::asAccessAction(37, 'r', $this->_user->id, $fnum)) {
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
			$m_logs = $this->getModel('Logs');

			if (!empty($fnum)) {
				$users = $m_logs->getUsersLogsByFnum($fnum);
				if (!empty($users)) {
					echo json_encode((['status' => true, 'data' => $users]));
				}
				else {
					echo json_encode((['status' => false, 'data' => []]));
				}
			}
			else {
				echo json_encode((['status' => false, 'data' => []]));
			}
		}
		else {
			echo json_encode((['status' => false, 'data' => [], 'msg' => Text::_('ACCESS_DENIED')]));
		}
		exit;
	}

	public function checkmenufilterparams()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];
		$user_id  = Factory::getApplication()->getIdentity()->id;

		if (EmundusHelperAccess::asPartnerAccessLevel($user_id)) {
			$itemId      = $this->input->getInt('Itemid', 0);
			$menu        = $this->app->getMenu();
			$menu_params = $menu->getParams($itemId);
			$use_module_filters = false;
			if (isset($menu_params['filter_status'])) {
				$use_module_filters = true;
			}

			$response['use_module_filters'] = $use_module_filters;
			$response['status']             = true;
			$response['code']               = 200;
			$response['msg']                = Text::_('SUCCESS');
		}

		echo json_encode($response);
		exit;
	}

	public function getFiltersAvailable()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
			$response['msg'] = Text::_('MISSING_PARAMS');
			$menu_id = $app->input->getInt('menu_id', 0);

			if (!empty($menu_id)) {
				$response['msg'] = Text::_('NO_CALCULATION_FOR_THIS_MODULE');

				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);

				$query->select('params')
					->from('#__menu')
					->where('id = ' . $db->quote($menu_id));

				$db->setQuery($query);
				$menu_params = $db->loadResult();
				$menu_params = json_decode($menu_params, true);

				try {
					if (!class_exists('EmundusFiltersFiles')) {
						require_once(JPATH_ROOT . '/components/com_emundus/classes/filters/EmundusFiltersFiles.php');
					}
					$m_filters = new EmundusFiltersFiles($menu_params, false, true);

					$response['data'] = $m_filters->getFilters();
					$response['status'] = true;
					$response['code'] = 200;
				} catch (Exception $e) {
					$response['code'] = 500;
					$response['msg'] = $e->getMessage();
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	public function setFiltersValuesAvailability()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
			$response['msg'] = Text::_('MISSING_PARAMS');
			$menu_id = $app->input->getInt('menu_id', 0);

			if (!empty($menu_id)) {
				$response['msg'] = Text::_('NO_CALCULATION_FOR_THIS_MODULE');

				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->getQuery(true);

				$query->select('params')
					->from('#__menu')
					->where('id = ' . $db->quote($menu_id));

				$db->setQuery($query);
				$menu_params = $db->loadResult();
				$menu_params = json_decode($menu_params, true);

				if (!empty($menu_params) && $menu_params['count_filter_values'] == 1) {
					$session = $app->getSession();
					$applied_filters = $session->get('em-applied-filters', []);

					if (!empty($applied_filters)) {
						$all_filters_values = $session->get('em-filters-all-values', []);
						foreach ($applied_filters as $key => $filter) {
							if (!empty($all_filters_values[$filter['id']])) {
								$applied_filters[$key]['values'] = $all_filters_values[$filter['id']];
							}
						}

						$menu = $app->getMenu();
						$menu_item = $menu->getItem($menu_id);

						require_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');
						$h_files = new EmundusHelperFiles();
						$data = $h_files->setFiltersValuesAvailability($applied_filters, $user->id, $menu_item);

						$response = ['status' => true, 'code' => 200, 'msg' => Text::_('SUCCESS'), 'data' => $data];
					}
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	public function getfiltervalues()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];
		$user     = $this->app->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id)) {
			$element_id = $this->input->getInt('id', 0);

			if (!empty($element_id)) {
				require_once(JPATH_SITE . '/components/com_emundus/classes/filters/EmundusFilters.php');
				$filters = new EmundusFilters();

				$response['data']   = $filters->getFabrikElementValuesFromElementId($element_id);
				$session            = $this->app->getSession();
				$response['all']    = $session->get('em-filters-all-values');
				$response['status'] = true;
				$response['code']   = 200;
				$response['msg']    = Text::_('SUCCESS');
			}
			else {
				$response['msg']  = Text::_('MISSING_PARAMS');
				$response['code'] = 400;
			}
		}

		echo json_encode($response);
		exit;
	}

	public function countfilesbeforeaction()
	{
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$app    = Factory::getApplication();
			$fnums  = $this->input->getString('fnums', null);
			$action = $this->input->getInt('action_id', 0);
			$verb   = $this->input->getString('verb', '');
			$menu_id = $this->input->getInt('menu_id', 0);

			if (!empty($fnums))
			{
				if ($fnums === 'all')
				{
					if ($this->isEvaluationMenu($menu_id)) {
						$m_evaluation = new EmundusModelEvaluation();
						$fnums = $m_evaluation->getAllFnums($this->_user->id);
					} else {
						$m_files = new EmundusModelFiles();
						$fnums = $m_files->getAllFnums();
					}
				}
				else if (!is_array($fnums))
				{
					$fnums = (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);
				}

				if(!empty($action) && !empty($verb))
				{
                    $validFnums = EmundusHelperAccess::asAccessActionOnFnums($action, $verb, $this->_user->id, $fnums);
				} else {
					$validFnums = $fnums;
				}

				$response['status'] = true;
				$response['code']   = 200;
				$response['msg']    = Text::_('SUCCESS');
				$response['data']   = sizeof($validFnums);
			}
			else
			{
				$response['msg']  = Text::_('MISSING_PARAMS');
				$response['code'] = 400;
			}
		}

		echo json_encode($response);
		exit;
	}

	public function renderemundustags() {
		$response = ['status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$string = $this->input->getString('string', '');
			$fnum = $this->input->getString('fnum', '');

			if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)) {
				if (!empty($string)) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
					$m_emails = new EmundusModelEmails();
					$tags = $m_emails->setTags($this->_user->id, null, $fnum, '', $string);
					$string = preg_replace($tags['patterns'], $tags['replacements'], $string);

					$response['data'] = $string;
					$response['status'] = true;
					$response['code'] = 200;
				} else {
					$response['msg'] = Text::_('MISSING_PARAMS');
					$response['code'] = 500;
				}
			}
		}

		echo json_encode($response);
		exit;
	}

	public function getProfiles()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];
		$user = $this->app->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$m_files = new EmundusModelFiles();
			$response['data'] = array_values($m_files->getProfiles());

			$response['status'] = true;
			$response['msg'] = Text::_('SUCCESS');
		}

		echo json_encode($response);
		exit;
	}

	private function isEvaluationMenu(int $menu_id): bool
	{
		$is_evaluation_menu = false;

		if (!empty($menu_id)) {
			$menu = Factory::getApplication()->getMenu();
			$menu_item = $menu->getItem($menu_id);

			if ($menu_item->link === 'index.php?option=com_emundus&view=evaluation') {
				$is_evaluation_menu = true;
			}
		}

		return $is_evaluation_menu;
	}

	public function getFileSynthesis()
	{
		$this->checkToken('get');
		$response = ['code' => 403, 'status' => false, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$fnum = $this->input->getString('fnum', null);

			if (EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum)) {
				$response['code'] = 200;
				$response['status'] = true;
				$response['message'] = '';
				$m_files = $this->getModel('Files');

				$response['data'] = $m_files->getFileSynthesis($fnum);
			}
		}

		$this->sendJsonResponse($response);
	}
}
