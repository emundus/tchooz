<?php

/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      Merveille Gbetegan
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * List Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 */
class EmundusControllerList extends BaseController
{

	protected $app;

	private $_user;

	private $m_list;

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

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');

		$this->app    = Factory::getApplication();
		$this->_user  = $this->app->getIdentity();
		$this->m_list = $this->getModel('list');
	}

	public function getList()
	{
		$tab  = array('status' => 0, 'msg' => JText::_("ACCESS_DENIED"));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id) || EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {

			$listId                                = $this->input->getInt('listId');
			$listParticularConditionalColumn       = json_decode($this->input->getString('listParticularConditionalColumn'));
			$listParticularConditionalColumnValues = json_decode($this->input->getString('listParticularConditionalColumnValues'));

			if (!empty($listId)) {
				$listData = $this->m_list->getList($listId, $listParticularConditionalColumn, $listParticularConditionalColumnValues);
				if (!empty($listData)) {
					$tab = array('status' => 1, 'msg' => JText::_('COM_EMUNDUS_LIST_RETRIEVED'), 'data' => $listData);
				}
				else {
					$tab = array('status' => 0, 'msg' => JText::_('COM_EMUNDUS_ERROR_CANNOT_RETRIEVE_LIST'), 'data' => $listData);
				}
			}
			else {
				$tab['msg'] = JText::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function getListActions()
	{
		$tab  = array('status' => 0, 'msg' => JText::_("ACCESS_DENIED"));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id) || EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {

			$listId            = $this->input->getInt('listId');
			$lisActionColumnId = $this->input->getInt('listActionColumnId');
			$listData          = $this->m_list->getListActions($listId, $lisActionColumnId,);

			if (!empty($listData)) {
				$tab = array('status' => 1, 'msg' => JText::_('COM_EMUNDUS_LIST_RETRIEVED'), 'data' => $listData);
			}
			else {
				$tab = array('status' => 0, 'msg' => JText::_('COM_EMUNDUS_ERROR_CANNOT_RETRIEVE_LIST'), 'data' => $listData);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function actionSetColumnValueAs()
	{
		$tab  = array('status' => 0, 'msg' => JText::_("ACCESS_DENIED"));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id) || EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {

			$rowId       = $this->input->getString('row_id');
			$value       = $this->input->getString('value');
			$columnName  = $this->input->getString('column_name');
			$dbTablename = $this->input->getString('db_table_name');
			$updated     = $this->m_list->actionSetColumnValueAs($rowId, $value, $dbTablename, $columnName);

			if ($updated) {
				$tab = array('status' => 1, 'msg' => JText::_('COM_EMUNDUS_LIST_RETRIEVED'), 'data' => $updated);
			}
			else {
				$tab = array('status' => 0, 'msg' => JText::_('COM_EMUNDUS_ERROR_CANNOT_RETRIEVE_LIST'), 'data' => $updated);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function updateActionState()
	{
		$tab  = array('status' => 0, 'msg' => JText::_("ACCESS_DENIED"));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id) || EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {

			$newValue = $this->input->getString('newValue');
			$rows     = json_decode($this->input->getString('rows'), true);

			if (!empty($newValue) && !empty($rows)) {
				$updated = $this->m_list->updateActionState($newValue, $rows);

				$tab['status'] = $updated;
			}
		}

		echo json_encode((object) $tab);
		exit;
	}
}

