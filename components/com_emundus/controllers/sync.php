<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 *
 * @license     GNU/GPL
 * @author      HUBINET Brice
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

class EmundusControllerSync extends BaseController
{
	protected $app;

	private $_user;
	private $m_sync;

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

		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'sync.php');
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');

		$this->app    = Factory::getApplication();
		$this->_user  = $this->app->getSession()->get('emundusUser');
		$this->m_sync = $this->getModel('Sync');
	}

	public function getconfig()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_('ACCESS_DENIED'));
		}
		else {

			$type = $this->input->getString('type', null);

			if (!empty($type)) {
				$config = $this->m_sync->getConfig($type);
				$tab    = array('status' => 1, 'msg' => JText::_('CONFIG_SAVED'), 'data' => json_decode($config));
			}
			else {
				$tab = array('status' => 0, 'msg' => JText::_('MISSING_PARAMS'));
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function saveconfig()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {

			$config = $this->input->getString('config', null);
			$type   = $this->input->getString('type', null);

			$saved = $this->m_sync->saveConfig($config, $type);

			$tab = array('status' => 1, 'msg' => JText::_('CONFIG_SAVED'), 'data' => $saved);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getaspects()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {
			$aspects = $this->m_sync->getAspects();
			$tab     = array('status' => 1, 'msg' => JText::_('ASPECTS_FOUND'), 'data' => $aspects);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function uploadaspectfile()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {
			$file = $_FILES['file'];

			$aspects = $this->m_sync->uploadAspectFile($file);
			$tab     = array('status' => !empty($aspects), 'msg' => JText::_('ASPECTS_UPLOADED'), 'data' => $aspects);
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function updateaspectlistfromfile()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {
			$file = $_FILES['file'];

			$aspects = $this->m_sync->updateAspectListFromFile($file);
			$tab     = array('status' => !empty($aspects), 'msg' => JText::_('ASPECTS_UPDATED'), 'data' => $aspects);
		}

		echo json_encode((object) $tab);
		exit;
	}


	public function getdocuments()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {
			$documents = $this->m_sync->getDocuments();

			$tab = array('status' => 1, 'msg' => JText::_('CONFIG_SAVED'), 'data' => $documents);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getemundustags()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {
			$tags = $this->m_sync->getEmundusTags();

			$tab = array('status' => 1, 'msg' => JText::_('CONFIG_SAVED'), 'data' => $tags);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getsetuptags()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {
			require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'tags.php');
			$helper = new EmundusHelperTags();
			$tags   = $helper->getTags();

			$tab = array('status' => 1, 'msg' => JText::_('TAGS_RETRIEVED'), 'data' => $tags);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function updatedocumentsync()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {

			$did  = $this->input->getString('did', null);
			$sync = $this->input->getString('sync', null);

			$updated = $this->m_sync->updateDocumentSync($did, $sync);

			$tab = array('status' => 1, 'msg' => JText::_('CONFIG_SAVED'), 'data' => $updated);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function updatedocumentsyncmethod()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => JText::_("ACCESS_DENIED"));
		}
		else {

			$did         = $this->input->getString('did', null);
			$sync_method = $this->input->getString('sync_method', null);

			$updated = $this->m_sync->updateDocumentSyncMethod($did, $sync_method);

			$tab = array('status' => 1, 'msg' => JText::_('CONFIG_SAVED'), 'data' => $updated);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function issyncmoduleactive()
	{
		$sync_active = $this->m_sync->isSyncModuleActive();

		$tab = array('status' => 1, 'msg' => JText::_('CONFIG_SAVED'), 'data' => $sync_active);
		echo json_encode((object) $tab);
		exit;
	}

	public function getsynctype(): string
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$response['msg']    = Text::_('MISSING_UPLOAD_ID');
			$upload_id = $this->input->getInt('upload_id', null);

			if (!empty($upload_id)) {
				$response['msg']    = Text::_('SYNC_TYPE_NOT_FOUND');
				$sync_type = $this->m_sync->getSyncType($upload_id);

				if (!empty($sync_type)) {
					$response['status'] = 1;
					$response['data'] = $sync_type;
					$response['msg'] = Text::_('SYNC_TYPE_FOUND');
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getsynchronizestate()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$response['msg'] = Text::_('SYNC_STATE_NOT_FOUND');
			$upload_id = $this->input->getInt('upload_id', null);

			if (!empty($upload_id)) {
				$sync_state = $this->m_sync->getUploadSyncState($upload_id);

				$response['status'] = 1;
				$response['msg']    = Text::_('SYNC_STATE_FOUND');
				$response['data']   = $sync_state;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function synchronizeattachments()
	{
		$response = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$response['msg'] = Text::_('MISSING_UPLOAD_IDS');
			$upload_ids = $this->input->get('upload_ids', array(), 'array');
			$upload_ids = json_decode($upload_ids[0]);

			if (!empty($upload_ids) && is_array($upload_ids)) {
				$updated = $this->m_sync->synchronizeAttachments($upload_ids);
				$response     = array('status' => 1, 'msg' => Text::_('CONFIG_SAVED'), 'data' => $updated);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function deleteattachments()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$response['msg'] = Text::_('MISSING_UPLOAD_IDS');

			$upload_ids = $this->input->get('upload_ids', array(), 'array');
			$upload_ids = json_decode($upload_ids[0]);

			if (!empty($upload_ids) && is_array($upload_ids)) {
				$updated = $this->m_sync->deleteAttachments($upload_ids);
				$response = array('status' => 1, 'msg' => Text::_('ATTACHMENTS_SYNC_DELETED'), 'data' => $updated);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function checkattachmentsexists()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			$upload_ids = $this->input->get('upload_ids', array(), 'array');
			$upload_ids = json_decode($upload_ids[0]);

			if (!empty($upload_ids)) {
				$attachments_exists = $this->m_sync->checkAttachmentsExists($upload_ids);

				$response['status'] = 1;
				$response['msg'] = Text::_('ATTACHMENT_FOUND');
				$response['data'] = $attachments_exists;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getattachmentaspectsconfig()
	{
		$user = JFactory::getUser();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$tab = array('status' => 0, 'msg' => JText::_('ACCESS_DENIED'));
		}
		else {
			$attachmentId = $this->input->getInt('attachmentId', 0);

			if (!empty($attachmentId)) {
				$tab         = array('status' => 1, 'msg' => JText::_('ATTACHMENT_ASPECTS_CONFIG_FOUND'));
				$tab['data'] = $this->m_sync->getAttachmentAspectsConfig($attachmentId);
			}
			else {
				$tab = array('status' => 0, 'msg' => JText::_('MISSING_ATTACHMENT_ID'));
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function saveattachmentaspectsconfig()
	{
		$user = JFactory::getUser();

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$tab = array('status' => 0, 'msg' => JText::_('ACCESS_DENIED'));
		}
		else {
			$attachmentId = $this->input->getInt('attachmentId', 0);
			$config       = $this->input->getString('config', '');

			if (!empty($attachmentId)) {
				$tab         = array('status' => 1, 'msg' => JText::_('ATTACHMENT_ASPECTS_CONFIG_SAVED'));
				$tab['data'] = $this->m_sync->saveAttachmentAspectsConfig($attachmentId, $config);
			}
			else {
				$tab = array('status' => 0, 'msg' => JText::_('MISSING_PARAMS'));
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function getnodeid()
	{
		$response = array('status' => 0, 'msg' => JText::_('ACCESS_DENIED'));
		$user     = JFactory::getUser();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			$upload_id = $this->input->getInt('uploadId', 0);

			if (!empty($upload_id)) {
				$node_id = $this->m_sync->getNodeId($upload_id);

				$eMConfig                               = JComponentHelper::getParams('com_emundus');
				$external_storage_ged_alfresco_base_url = $eMConfig->get('external_storage_ged_alfresco_base_url', '');
				$external_storage_ged_alfresco_site     = $eMConfig->get('external_storage_ged_alfresco_site', '');

				$response['data']   = $external_storage_ged_alfresco_base_url . 'share/page/site/' . $external_storage_ged_alfresco_site . '/document-details?nodeRef=workspace://SpacesStore/' . $node_id;
				$response['status'] = 1;
				$response['msg']    = 'Success';
			}
			else {
				$response['msg'] = JText::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function sendtomicrosoftcrm()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if(EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)){
			$fnums  = $this->input->getString('fnums', null);

			if(!empty($fnums))
			{
				$dispatcher = Factory::getApplication()->getDispatcher();
				PluginHelper::importPlugin('emundus');

				$fnums = (array) json_decode(stripslashes($fnums), false, 512, JSON_BIGINT_AS_STRING);

				$onMicrosftDynamicsSyncEventHandler = new GenericEvent(
					'onCallEventHandler',
					['onMicrosftDynamicsSync',
						// Datas to pass to the event
						['fnums' => $fnums]
					]
				);
				$onMicrosftDynamicsSync             = new GenericEvent(
					'onMicrosftDynamicsSync',
					// Datas to pass to the event
					['fnums' => $fnums]
				);

				try
				{
					$dispatcher->dispatch('onCallEventHandler', $onMicrosftDynamicsSyncEventHandler);
					$dispatcher->dispatch('onMicrosftDynamicsSync', $onMicrosftDynamicsSync);

					$response['status'] = 1;
					$response['msg']    = Text::_('COM_EMUNDUS_SEND_TO_MICROSOFT_DYNAMICS_SUCCESS');
				}
				catch (Exception $e)
				{
					$response['status'] = 0;
					$response['msg']    = $e->getMessage();
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}
}
