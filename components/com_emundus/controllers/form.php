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
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\EmundusResponse;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;
use Tchooz\Traits\TraitResponse;
use Tchooz\Controller\EmundusController;

class EmundusControllerForm extends EmundusController
{
	private $m_form;

	private FabrikRepository $fabrikRepository;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->m_form = $this->getModel('Form');

		$this->fabrikRepository = new FabrikRepository();
		$fabrikFactory          = new FabrikFactory($this->fabrikRepository);
		$this->fabrikRepository->setFactory($fabrikFactory);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getallform(): EmundusResponse
	{
		$actionRepository = new ActionRepository();
		$campaignAction = $actionRepository->getByName('campaign');
		$campaignAccess = EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::READ->value, $this->user->id);
		$campaignEditAccess = EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::UPDATE->value, $this->user->id);

		$page      = $this->input->getInt('page', 0);
		$lim       = $this->input->getInt('lim', 0);
		$filter    = $this->input->getString('filter', '');
		$sort      = $this->input->getString('sort', '');
		$recherche = $this->input->getString('recherche', '');
		$order_by  = $this->input->getString('order_by', '');

		$data = $this->m_form->getAllForms($filter, $sort, $recherche, $lim, $page, $this->user->id, $order_by);

		foreach ($data['datas'] as $form)
		{
			if(!$campaignAccess) {
				continue;
			}

			// find campaigns associated with form
			$campaigns = $this->m_form->getAssociatedCampaign($form->id, $this->user->id);

			if (!empty($campaigns))
			{
				if (count($campaigns) < 2)
				{
					$short_tags = $campaignEditAccess ? '<a href="' . EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=' . $campaigns[0]->id) . '" class="tw-cursor-pointer tw-mr-2 tw-mb-2 tw-h-max tw-font-semibold hover:tw-font-semibold hover:tw-underline tw-text-neutral-900 tw-text-sm em-campaign-tag"> ' . $campaigns[0]->label . '</a>' : $campaigns[0]->label;
				}
				else
				{
					$tags       = '<div>';
					$short_tags = $tags;
					$tags       .= '<h2 class="tw-mb-8 tw-text-center">' . Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE') . '</h2>';
					$tags       .= '<div class="tw-flex tw-flex-wrap">';
					foreach ($campaigns as $campaign)
					{
						$tags .= $campaignEditAccess ? '<a href="' . EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=' . $campaign->id) . '" class="tw-cursor-pointer tw-mr-2 tw-mb-2 tw-h-max tw-px-3 tw-py-1 tw-font-semibold hover:tw-font-semibold tw-bg-main-100 tw-text-neutral-900 tw-text-sm tw-rounded-coordinator em-campaign-tag"> ' . $campaign->label . '</a>' : '<span class="tw-mr-2 tw-mb-2 tw-h-max tw-px-3 tw-py-1 tw-font-semibold tw-bg-main-100 tw-text-neutral-900 tw-text-sm tw-rounded-coordinator em-campaign-tag"> ' . $campaign->label . '</span>';
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

		return EmundusResponse::ok($data, Text::_('FORM_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getallgrilleEval(): EmundusResponse
	{
		$page      = $this->input->getInt('page', 0);
		$lim       = $this->input->getInt('lim', 0);
		$filter    = $this->input->getString('filter', '');
		$sort      = $this->input->getString('sort', '');
		$recherche = $this->input->getString('recherche', '');
		$order_by  = $this->input->getString('order_by', '');

		$forms = $this->m_form->getAllGrilleEval($filter, $sort, $recherche, $lim, $page, $this->user->id, $order_by);

		// this data formatted is used in onboarding lists
		foreach ($forms['datas'] as $form)
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

		return EmundusResponse::ok($forms, Text::_('FORM_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getallformpublished(): EmundusResponse
	{
		$forms = $this->m_form->getAllFormsPublished();

		return EmundusResponse::ok($forms, Text::_('FORM_RETRIEVED'));
	}


	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::DELETE]])]
	public function deleteform(): EmundusResponse
	{
		$data = $this->input->getInt('id');
		if (empty($data))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		$forms = $this->m_form->deleteForm($data);

		if (!$forms)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_DELETE_FORM'));
		}

		return EmundusResponse::ok($forms, Text::_('FORM_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function unpublishform(): EmundusResponse
	{
		$id = $this->input->getInt('id', 0);
		if (empty($id))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		$result = $this->m_form->unpublishForm([$id]);
		if (!$result['status'])
		{
			throw new RuntimeException(!empty($result['msg']) ? Text::_($result['msg']) : Text::_('ERROR_CANNOT_UNPUBLISH_FORM'));
		}

		return EmundusResponse::ok([], Text::_('FORM_UNPUBLISHED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function unpublishFabrikForm(): EmundusResponse
	{
		$ids = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$ids = explode(',', $ids);
		}
		else
		{
			$id  = $this->input->getInt('id', 0);
			$ids = [$id];
		}

		if (empty($ids))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		$unpublishedAll = [];
		foreach ($ids as $id)
		{
			$unpublishedAll[] = $this->m_form->unpublishFabrikForm((int) $id);
		}
		$unpublished = !in_array(false, $unpublishedAll, true);

		if (!$unpublished)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_UNPUBLISH_FORM'));
		}

		return EmundusResponse::ok([], Text::_('FORM_UNPUBLISHED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function publishform(): EmundusResponse
	{
		$id = $this->input->getInt('id');
		if (empty($id))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		if (!$this->m_form->publishForm([$id]))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_PUBLISH_FORM'));
		}

		return EmundusResponse::ok([], Text::_('FORM_PUBLISHED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function publishFabrikForm(): EmundusResponse
	{
		$ids = $this->input->getString('ids', '');
		if (!empty($ids))
		{
			$ids = explode(',', $ids);
		}
		else
		{
			$id  = $this->input->getInt('id', 0);
			$ids = [$id];
		}

		if (empty($ids))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		$publishedAll = [];
		foreach ($ids as $id)
		{
			$publishedAll[] = $this->m_form->publishFabrikForm((int) $id);
		}
		$published = !in_array(false, $publishedAll, true);

		if (!$published)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_PUBLISH_FORM'));
		}

		return EmundusResponse::ok([], Text::_('FORM_PUBLISHED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::CREATE]])]
	public function duplicateform(): EmundusResponse
	{
		$data = $this->input->getInt('id', 0);
		if (empty($data))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		$form = $this->m_form->duplicateForm($data);
		if (!$form)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_DUPLICATE_FORM'));
		}

		return EmundusResponse::ok($form, Text::_('FORM_DUPLICATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::CREATE]])]
	public function duplicateFabrikForm(): EmundusResponse
	{
		$formId = $this->input->getInt('id', 0);
		if (empty($formId))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		if (!class_exists('EmundusModelFormBuilder'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/formbuilder.php');
		}
		$m_formbuilder    = new EmundusModelFormBuilder();
		$duplicatedFormId = $m_formbuilder->duplicateFabrikForm($formId, $this->user->id, ['keep_structure' => false]);

		if (empty($duplicatedFormId))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_DUPLICATE_FORM'));
		}

		return EmundusResponse::ok($duplicatedFormId, Text::_('FORM_DUPLICATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::CREATE]])]
	public function createform(): void
	{
		$result = $this->m_form->createApplicantProfile();
		if (!$result)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_ADD_FORM'));
		}

		$response = array('status' => true, 'msg' => Text::_('FORM_ADDED'), 'data' => $result, 'redirect' => 'index.php?option=com_emundus&view=form&layout=formbuilder&prid=' . $result, 'code' => EmundusResponse::HTTP_OK);

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::CREATE]])]
	public function createformeval(): void
	{
		$form_id = $this->m_form->createFormEval($this->user);
		if (empty($form_id))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_ADD_FORM'));
		}

		$response = array('status' => true, 'msg' => Text::_('FORM_ADDED'), 'data' => $form_id, 'redirect' => 'index.php?option=com_emundus&view=form&layout=formbuilder&prid=' . $form_id . '&mode=eval', 'code' => EmundusResponse::HTTP_OK);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updateform(): EmundusResponse
	{
		$data = $this->input->getRaw('body');
		$pid  = $this->input->getInt('pid');
		if (empty($pid))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		$result = $this->m_form->updateForm($pid, $data);
		if (!$result)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_UPDATE_FORM'));
		}

		return EmundusResponse::ok($result, Text::_('FORM_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function updateformlabel(): EmundusResponse
	{
		$prid  = $this->input->getInt('prid', 0);
		$label = $this->input->getString('label');
		if (empty($prid))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		$result = $this->m_form->updateFormLabel($prid, $label);
		if (!$result)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_UPDATE_FORM'));
		}

		return EmundusResponse::ok($result, Text::_('FORM_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getformbyid(): EmundusResponse
	{
		$id = $this->input->getInt('id');
		if (empty($id))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_FORM_NOT_FOUND'));
		}

		$form = $this->m_form->getFormById($id);
		if (empty($form))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_FORM'));
		}

		return EmundusResponse::ok($form, Text::_('FORM_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getFormByFabrikId(): EmundusResponse
	{
		$id = $this->input->getInt('form_id');
		if (empty($id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$form = $this->m_form->getFormByFabrikId($id);
		if (empty($form))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_FORM'));
		}

		return EmundusResponse::ok($form, Text::_('FORM_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getalldocuments(): EmundusResponse
	{
		$prid = $this->input->getInt('prid');
		$cid  = $this->input->getInt('cid');
		if (empty($prid) || empty($cid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$form = $this->m_form->getAllDocuments($prid, $cid);
		if ($form === false)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_DOCUMENTS'));
		}

		return EmundusResponse::ok($form, Text::_('DOCUMENTS_FOUND'));
	}


	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getundocuments(): EmundusResponse
	{
		$form = $this->m_form->getUnDocuments();
		if ($form === false)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_DOCUMENTS'));
		}

		return EmundusResponse::ok($form, Text::_('DOCUMENTS_FOUND'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getAttachments(): EmundusResponse
	{
		$attachments = $this->m_form->getAttachments();

		return EmundusResponse::ok($attachments, Text::_('DOCUMENTS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getdocumentsusage(): EmundusResponse
	{
		$document_ids = $this->input->getString('documentIds', '');
		if (empty($document_ids))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$document_ids = explode(',', $document_ids);
		$forms        = $this->m_form->getDocumentsUsage($document_ids);
		if (empty($forms))
		{
			throw new RuntimeException(Text::_('ERROR_GETTING_DOCUMENT_USAGE'));
		}

		return EmundusResponse::ok($forms, Text::_('DOCUMENTS_FOUND'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::CREATE]])]
	public function adddocument(): EmundusResponse
	{
		$did  = $this->input->getInt('did');
		$prid = $this->input->getInt('prid');
		$cid  = $this->input->getInt('cid');

		if (empty($did) || empty($prid) || empty($cid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$documents = $this->m_form->addDocument($did, $prid, $cid);
		if (!$documents)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_UPDATE_DOCUMENTS'));
		}

		return EmundusResponse::ok($documents, Text::_('DOCUMENTS_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getFormsByProfileId(): EmundusResponse
	{
		$profile_id = $this->input->getInt('profile_id');
		if (empty($profile_id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$forms = $this->m_form->getFormsByProfileId($profile_id);
		if (empty($forms))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_FORM'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok($forms);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getDocuments(): EmundusResponse
	{
		$profile_id = $this->input->getInt('pid');
		if (empty($profile_id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$documents = $this->m_form->getDocumentsByProfile($profile_id);

		return EmundusResponse::ok($documents, Text::_('DOCUMENTS_FOUND'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function reorderDocuments(): EmundusResponse
	{
		$documents = $this->input->getString('documents');
		if (empty($documents))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$documents = json_decode($documents, true);
		$documents = $this->m_form->reorderDocuments($documents);

		return EmundusResponse::ok($documents, Text::_('DOCUMENTS_FOUND'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function removeDocumentFromProfile(): EmundusResponse
	{
		$did = $this->input->getInt('did');
		if (empty($did))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$result = $this->m_form->removeDocumentFromProfile($did);
		if (!$result)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_UPDATE_DOCUMENTS'));
		}

		return EmundusResponse::ok($result, Text::_('DOCUMENTS_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getProfileLabelByProfileId(): EmundusResponse
	{
		$profile_id = $this->input->getInt('profile_id');
		if (empty($profile_id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$form = $this->m_form->getProfileLabelByProfileId($profile_id);
		if ($form === false)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_PROFILE_LABEL'));
		}

		return EmundusResponse::ok($form, Text::_('PROFILE_LABEL_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getsubmittionpage(): EmundusResponse
	{
		$prid = $this->input->getInt('prid');
		if (empty($prid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$submittionpage = $this->m_form->getSubmittionPage($prid);
		if ($submittionpage === false)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_SUBMISSION_PAGE'));
		}

		return EmundusResponse::ok($submittionpage, Text::_('SUBMISSION_PAGE_RETRIEVED'));
	}

	/**
	 *
	 *
	 * @deprecated This method is deprecated and should not be used anymore. It is recommended to handle access control using the AccessAttribute on each method instead of calling this method directly.
	 */
	public function getAccess(): void
	{
		if (EmundusHelperAccess::asAdministratorAccessLevel($this->user->id))
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

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function deletemodeldocument(): void
	{
		$did = $this->input->getInt('did');
		if (empty($did))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$result = $this->m_form->deleteModelDocument($did);

		$response = array('allowed' => $result, 'msg' => 'worked', 'status' => $result, 'code' => $result ? EmundusResponse::HTTP_OK : EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getdatabasejoinoptions()
	{
		$response = ['status' => true, 'msg' => '', 'options' => []];

		$table_name   = $this->input->getString('table_name');
		$column_name  = $this->input->getString('column_name');
		$value        = $this->input->getString('value');
		$concat_value = $this->input->getString('concat_value');
		$where_clause = $this->input->getString('where_clause');

		try
		{
			$options  = $this->m_form->getDatabaseJoinOptions($table_name, $column_name, $value, $concat_value, $where_clause);
			$response = ['status' => true, 'msg' => 'worked', 'options' => $options, 'code' => EmundusResponse::HTTP_OK];
		}
		catch (Exception $e)
		{
			$response['status'] = false;
			$response['msg']    = $e->getMessage();
			$response['code']   = EmundusResponse::HTTP_INTERNAL_SERVER_ERROR;
		}

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function checkcandocbedeleted(): EmundusResponse
	{
		$docid = $this->input->getInt('docid');
		$prid  = $this->input->getInt('prid');
		if (empty($prid) || empty($docid))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$canBeDeleted = $this->m_form->checkIfDocCanBeRemovedFromCampaign($docid, $prid);

		return EmundusResponse::ok($canBeDeleted);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getpagegroups(): EmundusResponse
	{
		$formId = $this->input->getInt('form_id');
		if (empty($formId))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$groups = $this->m_form->getGroupsByForm($formId);
		if ($groups === false)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_GROUPS'));
		}

		return EmundusResponse::ok(['groups' => $groups], Text::_('GROUPS_RETRIEVED'));
	}

	public function getjsconditions(): EmundusResponse
	{
		$formId = $this->input->getInt('form_id');
		$format = $this->input->getString('format', 'raw');

		if (empty($formId))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$conditions = $this->m_form->getJSConditionsByForm($formId, $format);

		return EmundusResponse::ok(['conditions' => $conditions]);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function addRule(): EmundusResponse
	{
		$form_id    = $this->input->getInt('form_id');
		$conditions = $this->input->getString('conditions');
		$actions    = $this->input->getString('actions');
		$group      = $this->input->getString('group');
		$label      = $this->input->getString('label');

		if (empty($form_id) || empty($conditions) || empty($actions))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$rule_added = $this->m_form->addRule($form_id, $conditions, $actions, 'js', $group, $label);
		if ($rule_added === false)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_ADD_RULE'));
		}

		return EmundusResponse::ok($rule_added);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function editRule(): EmundusResponse
	{
		$rule_id    = $this->input->getInt('rule_id');
		$conditions = $this->input->getRaw('conditions');
		$actions    = $this->input->getString('actions');
		$group      = $this->input->getString('group');
		$label      = $this->input->getString('label');

		if (empty($rule_id) || empty($conditions) || empty($actions))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$rule_edited = $this->m_form->editRule($rule_id, $conditions, $actions, $group, $label);
		if ($rule_edited === false)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_EDIT_RULE'));
		}

		return EmundusResponse::ok($rule_edited);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function deleteRule(): EmundusResponse
	{
		$rule_id = $this->input->getInt('rule_id');
		if (empty($rule_id))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$rule_deleted = $this->m_form->deleteRule($rule_id);
		if ($rule_deleted === false)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_DELETE_RULE'));
		}

		return EmundusResponse::ok($rule_deleted, Text::_('RULE_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::UPDATE]])]
	public function publishRule(): EmundusResponse
	{
		$rule_id = $this->input->getInt('rule_id');
		$state   = $this->input->getInt('state');
		if (empty($rule_id) || empty($state))
		{
			throw new InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$rule_published = $this->m_form->publishRule($rule_id, $state);
		if ($rule_published === false)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_PUBLISH_RULE'));
		}

		return EmundusResponse::ok($rule_published);
	}

	public function getaddpipestatus(): void
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

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::READ]])]
	public function getuserprofileelements(): EmundusResponse
	{
		$elements = $this->m_form->getUserProfileElements();
		if (empty($elements))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_ELEMENTS'));
		}

		return EmundusResponse::ok($elements, Text::_('ELEMENTS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getFabrikElementOptions(): void
	{
		$response = ['code' => 400, 'status' => false, 'msg' => Text::_('MISSING_REQUIRED_PARAMETER'), 'data' => []];
		$search   = $this->input->getString('search_query', '');

		if (!empty($search))
		{
			$resolver = new FormDataConditionResolver();
			$choices  = $resolver->getAvailableElementsOptions($search);

			$response = [
				'code'   => EmundusResponse::HTTP_OK,
				'status' => true,
				'data'   => array_map(function ($choice) {
					return [
						'value' => $choice->getValue(),
						'label' => $choice->getLabel(),
					];
				}, $choices),
			];
		}
		else
		{
			$response['code'] = EmundusResponse::HTTP_OK;
			$response['data'] = [];
			$response['msg']  = Text::_('NO_OPTIONS_FOUND');
		}

		$this->sendJsonResponse($response);
	}
}

