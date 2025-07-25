<?php

/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      James Dean
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Form Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 * @since      5.0.0
 */
class EmundusControllerForm extends BaseController
{
	protected $app;

	private $_user;
	private $m_form;

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

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getIdentity();

		$this->m_form = $this->getModel('Form');
	}

	public function getallform()
	{
		$tab = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$page      = $this->input->getInt('page', 0);
			$lim       = $this->input->getInt('lim', 0);
			$filter    = $this->input->getString('filter', '');
			$sort      = $this->input->getString('sort', '');
			$recherche = $this->input->getString('recherche', '');

			$data = $this->m_form->getAllForms($filter, $sort, $recherche, $lim, $page, $this->_user->id);

			foreach ($data['datas'] as $key => $form)
			{
				// find campaigns associated with form
				$campaigns = $this->m_form->getAssociatedCampaign($form->id, $this->_user->id);

				if (!empty($campaigns))
				{
					if (count($campaigns) < 2)
					{
						$short_tags = '<a href="'.EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid='.$campaigns[0]->id).'" class="tw-cursor-pointer tw-mr-2 tw-mb-2 tw-h-max tw-font-semibold hover:tw-font-semibold hover:tw-underline tw-text-neutral-900 tw-text-sm em-campaign-tag"> ' . $campaigns[0]->label . '</a>';
					}
					else
					{
						$tags       = '<div>';
						$short_tags = $tags;
						$tags       .= '<h2 class="tw-mb-8 tw-text-center">' . Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE') . '</h2>';
						$tags       .= '<div class="tw-flex tw-flex-wrap">';
						foreach ($campaigns as $campaign)
						{
							$tags .= '<a href="'.EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid='.$campaign->id).'" class="tw-cursor-pointer tw-mr-2 tw-mb-2 tw-h-max tw-px-3 tw-py-1 tw-font-semibold hover:tw-font-semibold tw-bg-main-100 tw-text-neutral-900 tw-text-sm tw-rounded-coordinator em-campaign-tag"> ' . $campaign->label . '</a>';
						}
						$tags .= '</div>';

						$short_tags .= '<span class="tw-w-fit tw-cursor-pointer tw-text-profile-full tw-flex tw-items-center tw-justify-center tw-text-sm hover:!tw-underline tw-font-semibold">' . count($campaigns) . Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED') . '</span>';
						$short_tags .= '</div>';
						$tags       .= '</div>';
					}
				}
				else
				{
					$short_tags = Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_NOT');
				}

				$new_column = [
					'key'     => Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE'),
					'value'   => $short_tags,
					'classes' => '',
					'display' => 'all'
				];

				if (isset($tags))
				{
					$new_column['long_value'] = $tags;
				}

				$form->additional_columns = [
					$new_column
				];
			}

			if (!empty($data))
			{
				$tab = array('status' => true, 'msg' => Text::_('FORM_RETRIEVED'), 'data' => $data);
			}
			else
			{
				$tab['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_FORM');
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function getallgrilleEval()
	{

		$tab = array('status' => false, 'msg' => Text::_("ACCESS_DENIED"));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{

			$page      = $this->input->getInt('page', 0);
			$lim       = $this->input->getInt('lim', 0);
			$filter    = $this->input->getString('filter', '');
			$sort      = $this->input->getString('sort', '');
			$recherche = $this->input->getString('recherche', '');

			$forms = $this->m_form->getAllGrilleEval($filter, $sort, $recherche, $lim, $page, $this->_user->id);

			if (count($forms) > 0)
			{
				// this data formatted is used in onboarding lists
				foreach ($forms['datas'] as $key => $form)
				{
					$form->additional_columns = [
						[
							'key'     => Text::_('COM_EMUNDUS_FORM_ASSOCIATED_PROGRAMS'),
							'value'   => $form->programs_count . ' ' . Text::_('COM_EMUNDUS_FORM_ASSOCIATED_PROGRAMS'),
							'classes' => 'em-p-5-12 em-font-weight-600 em-bg-neutral-200 em-text-neutral-900 em-font-size-14 label',
							'display' => 'blocs'
						],
					];
				}
				$tab = array('status' => true, 'msg' => Text::_('FORM_RETRIEVED'), 'data' => $forms);
			}
			else
			{
				$tab['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_FORM');
			}
		}
		echo json_encode((object) $tab);
		exit;
	}


	public function getallformpublished()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$forms = $this->m_form->getAllFormsPublished();

			if (!empty($forms))
			{
				$response = array('status' => 1, 'msg' => Text::_('FORM_RETRIEVED'), 'data' => $forms);
			}
			else
			{
				$response['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_FORM');
			}
		}

		echo json_encode((object) $response);
		exit;
	}


	public function deleteform()
	{
		$response = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$data  = $this->input->getInt('id');
			$forms = $this->m_form->deleteForm($data);

			if ($forms)
			{
				$response = array('status' => 1, 'msg' => Text::_('FORM_DELETED'), 'data' => $forms);
			}
			else
			{
				$response = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_DELETE_FORM'), 'data' => $forms);
			}
		}
		echo json_encode((object) $response);
		exit;
	}


	public function unpublishform()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{

			$id = $this->input->getInt('id', 0);

			$result = $this->m_form->unpublishForm([$id]);

			if ($result['status'])
			{
				$response = array('status' => 1, 'msg' => Text::_('FORM_UNPUBLISHED'));
			}
			else
			{
				$response = array('status' => 0, 'msg' => !empty($result['msg']) ? Text::_($result['msg']) : Text::_('ERROR_CANNOT_UNPUBLISH_FORM'));
			}
		}

		echo json_encode((object) $response);
		exit;
	}


	public function publishform()
	{
		$response = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$id    = $this->input->getInt('id');
			$forms = $this->m_form->publishForm([$id]);

			if ($forms)
			{
				$response = array('status' => 1, 'msg' => Text::_('FORM_PUBLISHED'), 'data' => $forms);
			}
			else
			{
				$response['msg'] = Text::_('ERROR_CANNOT_PUBLISH_FORM');
			}
		}
		echo json_encode((object) $response);
		exit;
	}


	public function duplicateform()
	{
		$tab = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{

			$data = $this->input->getInt('id', 0);

			if (!empty($data))
			{
				$form = $this->m_form->duplicateForm($data);
				if ($form)
				{
					$tab = array('status' => true, 'msg' => Text::_('FORM_DUPLICATED'), 'data' => $form);
				}
				else
				{
					$tab['msg'] = Text::_('ERROR_CANNOT_DUPLICATE_FORM');
				}
			}
			else
			{
				$tab['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $tab);
		exit;
	}


	public function createform()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = $this->m_form->createApplicantProfile();

			if ($result)
			{
				$response = array('status' => true, 'msg' => Text::_('FORM_ADDED'), 'data' => $result, 'redirect' => 'forms/formbuilder?prid=' . $result);
			}
			else
			{
				$response['msg'] = Text::_('ERROR_CANNOT_ADD_FORM');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function createformeval()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			try
			{
				$form_id = $this->m_form->createFormEval($this->_user);

				if ($form_id > 0)
				{
					$response = array('status' => true, 'msg' => Text::_('FORM_ADDED'), 'data' => $form_id, 'redirect' => 'index.php?option=com_emundus&view=form&layout=formbuilder&prid=' . $form_id . '&mode=eval');
				}
				else
				{
					$response['msg'] = Text::_('ERROR_CANNOT_ADD_FORM');
				}
			}
			catch (Exception $e)
			{
				$response['msg'] = $e->getMessage();
			}
		}

		echo json_encode((object) $response);
		exit;
	}


	public function updateform()
	{

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$data = $this->input->getRaw('body');
			$pid  = $this->input->getInt('pid');

			$result = $this->m_form->updateForm($pid, $data);

			if ($result)
			{
				$tab = array('status' => 1, 'msg' => Text::_('FORM_ADDED'), 'data' => $result);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('FORM'), 'data' => $result);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function updateformlabel()
	{

		$tab = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{

			$prid  = $this->input->getInt('prid', 0);
			$label = $this->input->getString('label');

			$result = $this->m_form->updateFormLabel($prid, $label);

			if ($result)
			{
				$tab = array('status' => 1, 'msg' => Text::_('FORM_UPDATED'), 'data' => $result);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('FORM_NOT_UPDATED'), 'data' => $result);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}


	public function getformbyid()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$id = $this->input->getInt('id');

			$form = $this->m_form->getFormById($id);
			if (!empty($form))
			{
				$tab = array('status' => 1, 'msg' => Text::_('FORM_RETRIEVED'), 'data' => $form);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_FORM'), 'data' => $form);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getFormByFabrikId()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{

			$id = $this->input->getInt('form_id');

			if (!empty($id))
			{
				$form = $this->m_form->getFormByFabrikId($id);
				if (!empty($form))
				{
					$response = array('status' => true, 'msg' => Text::_('FORM_RETRIEVED'), 'data' => $form);
				}
				else
				{
					$response['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_FORM');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getalldocuments()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$prid = $this->input->getInt('prid');
			$cid  = $this->input->getInt('cid');

			$form = $this->m_form->getAllDocuments($prid, $cid);

			if (!empty($form))
			{
				$tab = array('status' => 1, 'msg' => Text::_('DOCUMENTS_RETRIEVED'), 'data' => $form);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_DOCUMENTS'), 'data' => $form);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}


	public function getundocuments()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$form = $this->m_form->getUnDocuments();

			if (!empty($form))
			{
				$tab = array('status' => 1, 'msg' => Text::_('DOCUMENTS_RETRIEVED'), 'data' => $form);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_DOCUMENTS'), 'data' => $form);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getAttachments()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$attachments = $this->m_form->getAttachments();
			if (!empty($attachments))
			{
				$response['status'] = true;
				$response['msg']    = Text::_('DOCUMENTS_RETRIEVED');
				$response['data']   = $attachments;
			}
			else
			{
				$response['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_DOCUMENTS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getdocumentsusage()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$document_ids = $this->input->getString('documentIds', '');

			if (!empty($document_ids))
			{
				$document_ids = explode(',', $document_ids);
				$forms        = $this->m_form->getDocumentsUsage($document_ids);

				if (!empty($forms))
				{
					$response['status'] = 1;
					$response['msg']    = 'SUCCESS';
					$response['data']   = $forms;
				}
				else
				{
					$response['msg'] = Text::_('ERROR_GETTING_DOCUMENT_USAGE');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function updatemandatory()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$did  = $this->input->getInt('did');
			$prid = $this->input->getInt('prid');
			$cid  = $this->input->getInt('cid');

			$documents = $this->m_form->updateMandatory($did, $prid, $cid);

			if ($documents)
			{
				$tab = array('status' => 1, 'msg' => Text::_('DOCUMENTS_UPDATED'), 'data' => $documents);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_UPDATE_DOCUMENTS'), 'data' => $documents);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function adddocument()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$did  = $this->input->getInt('did');
			$prid = $this->input->getInt('prid');
			$cid  = $this->input->getInt('cid');

			$documents = $this->m_form->addDocument($did, $prid, $cid);

			if ($documents)
			{
				$tab = array('status' => 1, 'msg' => Text::_('DOCUMENTS_UPDATED'), 'data' => $documents);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_UPDATE_DOCUMENTS'), 'data' => $documents);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}


	public function removedocument()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$did  = $this->input->getInt('did');
			$prid = $this->input->getInt('prid');
			$cid  = $this->input->getInt('cid');

			$documents = $this->m_form->removeDocument($did, $prid, $cid);

			if ($documents)
			{
				$tab = array('status' => 1, 'msg' => Text::_('DOCUMENTS_UPDATED'), 'data' => $documents);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_UPDATE_DOCUMENTS'), 'data' => $documents);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}


	public function deletedocument()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$did = $this->input->getInt('did');

			$state = $this->m_form->deleteDocument($did);

			$tab = array('status' => $state, 'msg' => Text::_('DOCUMENT_DELETED'));

		}

		echo json_encode((object) $tab);
		exit;
	}


	public function getFormsByProfileId()
	{

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$profile_id = $this->input->getInt('profile_id');

			$form = $this->m_form->getFormsByProfileId($profile_id);

			if (!empty($form))
			{
				$tab = array('status' => 1, 'msg' => 'worked', 'data' => $form);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => 'Doesn t worked', 'data' => $form);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function getDocuments()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$profile_id = $this->input->getInt('pid');

			if (!empty($profile_id))
			{
				$documents = $this->m_form->getDocumentsByProfile($profile_id);

				if (!empty($documents))
				{
					$response = array('status' => true, 'msg' => 'worked', 'data' => $documents);
				}
				else
				{
					$response = array('status' => true, 'msg' => 'No documents attached to profile found', 'data' => $documents);
				}
			}
			else
			{
				$response = array('status' => false, 'msg' => 'Missing parameters');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function reorderDocuments()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$documents = $this->input->getString('documents');
			$documents = json_decode($documents, true);
			$documents = $this->m_form->reorderDocuments($documents);

			if (!empty($documents))
			{
				$tab = array('status' => 1, 'msg' => 'worked', 'data' => $documents);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => 'Doesn t worked', 'data' => $documents);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function removeDocumentFromProfile()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$did = $this->input->getInt('did');

			$result = $this->m_form->removeDocumentFromProfile($did);

			if (!empty($result))
			{
				$tab = array('status' => 1, 'msg' => 'worked', 'data' => $result);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => 'Doesn t worked', 'data' => $result);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getgroupsbyform()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$form_id = $this->input->getInt('form_id');

			$form = $this->m_form->getGroupsByForm($form_id);

			if (!empty($form))
			{
				$tab = array('status' => 1, 'msg' => 'worked', 'data' => $form);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => 'Doesn t worked', 'data' => $form);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}


	public function getProfileLabelByProfileId()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$profile_id = $this->input->getInt('profile_id');

			$form = $this->m_form->getProfileLabelByProfileId($profile_id);

			if (!empty($form))
			{
				$tab = array('status' => 1, 'msg' => 'worked', 'data' => $form);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => 'Doesn t worked', 'data' => $form);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}


	public function getfilesbyform()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$profile_id = $this->input->getInt('pid');

			$files = $this->m_form->getFilesByProfileId($profile_id);

			$tab = array('status' => 1, 'msg' => 'worked', 'data' => $files);
		}
		echo json_encode((object) $tab);
		exit;
	}


	public function getassociatedcampaign()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$profile_id = $this->input->getInt('pid');

			$campaigns = $this->m_form->getAssociatedCampaign($profile_id, $this->_user->id);

			$tab = array('status' => 1, 'msg' => 'worked', 'data' => $campaigns);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getassociatedprogram()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$form_id = $this->input->getInt('fid');

			$campaigns = $this->m_form->getAssociatedProgram($form_id);

			$tab = array('status' => 1, 'msg' => 'worked', 'data' => $campaigns);
		}
		echo json_encode((object) $tab);
		exit;
	}


	public function affectcampaignstoform()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$prid      = $this->input->getInt('prid');
			$campaigns = $this->input->getRaw('campaigns');

			$changeresponse = $this->m_form->affectCampaignsToForm($prid, $campaigns);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function getsubmittionpage()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$prid = $this->input->getInt('prid');

			$submittionpage = $this->m_form->getSubmittionPage($prid);
		}
		echo json_encode((object) $submittionpage);
		exit;
	}

	public function getAccess()
	{


		if (EmundusHelperAccess::asAdministratorAccessLevel($this->_user->id))
		{
			$response = array('status' => 1, 'msg' => Text::_("ACCESS_SYSADMIN"), 'access' => true);
		}
		else
		{
			$response = array('status' => 0, 'msg' => Text::_("ACCESS_REFUSED"), 'access' => false);
		}
		echo json_encode((object) $response);
		exit;
	}

	public function getActualLanguage()
	{
		$lang = JFactory::getLanguage();

		if ($lang)
		{
			$response = array('status' => 1, 'msg' => substr($lang->getTag(), 0, 2));
		}
		else
		{
			$response = array('status' => 0, 'msg' => Text::_("ACCESS_REFUSED"));
		}

		echo json_encode((object) $response);
		exit;
	}

	public function deletemodeldocument()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{


			$did = $this->input->getInt('did');

			$result = $this->m_form->deleteModelDocument($did);

			$changeresponse = array('allowed' => $result, 'msg' => 'worked');
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function getdatabasejoinoptions()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$table_name   = $this->input->getString('table_name');
			$column_name  = $this->input->getString('column_name');
			$value        = $this->input->getString('value');
			$concat_value = $this->input->getString('concat_value');
			$where_clause = $this->input->getString('where_clause');

			try
			{
				$options  = $this->m_form->getDatabaseJoinOptions($table_name, $column_name, $value, $concat_value, $where_clause);
				$response = ['status' => true, 'msg' => 'worked', 'options' => $options];
			}
			catch (Exception $e)
			{
				$response['status'] = false;
				$response['msg']    = $e->getMessage();
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function checkcandocbedeleted()
	{
		$response = array('status' => 0, 'msg' => Text::_("ACCESS_DENIED"));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{

			$docid = $this->input->getInt('docid');
			$prid  = $this->input->getInt('prid');

			if (!empty($prid) && !empty($docid))
			{
				$canBeDeleted = $this->m_form->checkIfDocCanBeRemovedFromCampaign($docid, $prid);

				$response['status'] = 1;
				$response['msg']    = Text::_("SUCCESS");
				$response['data']   = $canBeDeleted;
			}
			else
			{
				$response['msg'] = Text::_("MISSING_PARAMS");
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getpagegroups()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{

			$formId = $this->input->getInt('form_id');

			if (!empty($formId))
			{
				$groups = $this->m_form->getGroupsByForm($formId);

				if ($groups !== false)
				{
					$response['msg']    = Text::_('SUCCESS');
					$response['status'] = true;
					$response['data']   = ['groups' => $groups];
				}
				else
				{
					$response['msg'] = Text::_('FAILED');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getjsconditions()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []);

		$formId = $this->input->getInt('form_id');
		$format = $this->input->getString('format', 'raw');

		if (!empty($formId))
		{
			$conditions = $this->m_form->getJSConditionsByForm($formId, $format);

			$response['msg']    = Text::_('SUCCESS');
			$response['status'] = true;
			$response['data']   = ['conditions' => $conditions];
		}
		else
		{
			$response['msg'] = Text::_('MISSING_PARAMS');
		}

		echo json_encode((object) $response);
		exit;

	}

	public function addRule()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$form_id    = $this->input->getInt('form_id');
			$conditions = $this->input->getString('conditions');
			$actions    = $this->input->getString('actions');
			$group      = $this->input->getString('group');
			$label      = $this->input->getString('label');

			if (!empty($form_id) && !empty($conditions) && !empty($actions))
			{
				$rule_added = $this->m_form->addRule($form_id, $conditions, $actions, 'js', $group, $label);

				if ($rule_added !== false)
				{
					$response['msg']    = Text::_('SUCCESS');
					$response['status'] = true;
				}
				else
				{
					$response['msg'] = Text::_('FAILED');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function editRule()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$rule_id    = $this->input->getInt('rule_id');
			$conditions = $this->input->getString('conditions');
			$actions    = $this->input->getString('actions');
			$group      = $this->input->getString('group');
			$label      = $this->input->getString('label');

			if (!empty($rule_id) && !empty($conditions) && !empty($actions))
			{
				$rule_edited = $this->m_form->editRule($rule_id, $conditions, $actions, $group, $label);

				if ($rule_edited !== false)
				{
					$response['msg']    = Text::_('SUCCESS');
					$response['status'] = true;
				}
				else
				{
					$response['msg'] = Text::_('FAILED');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function deleteRule()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$rule_id = $this->input->getInt('rule_id');

			if (!empty($rule_id))
			{
				$rule_deleted = $this->m_form->deleteRule($rule_id);

				if ($rule_deleted !== false)
				{
					$response['msg']    = Text::_('SUCCESS');
					$response['status'] = true;
				}
				else
				{
					$response['msg'] = Text::_('FAILED');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function publishRule()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$rule_id = $this->input->getInt('rule_id');
			$state   = $this->input->getInt('state');

			if (!empty($rule_id))
			{
				$rule_published = $this->m_form->publishRule($rule_id, $state);

				if ($rule_published !== false)
				{
					$response['msg']    = Text::_('SUCCESS');
					$response['status'] = true;
				}
				else
				{
					$response['msg'] = Text::_('FAILED');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getaddpipestatus()
	{
		$response = array('status' => false);

		$addPipeStatus = ComponentHelper::getParams('com_emundus')->get('addpipe_activation', 0);

		if ($addPipeStatus == 1)
		{
			$response['status'] = true;
			$response['msg']    = Text::_('SUCCESS');
		}
		else
		{
			$response['msg'] = Text::_('ADDPIPE_NOT_ACTIVATED');
		}

		echo json_encode((object) $response);
		exit;
	}
}

