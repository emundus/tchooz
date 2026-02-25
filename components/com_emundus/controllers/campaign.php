<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Http\Discovery\Exception\NotFoundException;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\User\EmundusUserRepository;

class EmundusControllerCampaign extends EmundusController
{
	private EmundusModelCampaign $m_campaign;

	private CampaignRepository $campaignRepository;

	function __construct($config = array())
	{
		parent::__construct($config);

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once(JPATH_BASE . '/components/com_emundus/helpers/access.php');
		}
		if (!class_exists('EmundusModelCampaign'))
		{
			require_once(JPATH_BASE . '/components/com_emundus/models/campaign.php');
		}

		$this->m_campaign = new EmundusModelCampaign();
		$this->campaignRepository = new CampaignRepository();
	}

	public function setMCampaign(EmundusModelCampaign $m_campaign): void
	{
		$this->m_campaign = $m_campaign;
	}

	public function setCampaignRepository(CampaignRepository $campaignRepository): void
	{
		$this->campaignRepository = $campaignRepository;
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *
	 * @return  EmundusControllerCampaign  This object to support chaining.
	 *
	 * @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @since      version 1.0.0
	 */
	function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view'))
		{
			$default = 'campaign';
			$this->input->set('view', $default);
		}

		parent::display();

		return $this;
	}

	/**
	 * Clear session and reinit values by default
	 *
	 * @since version 1.0.0.0
	 */
	function clear()
	{
		EmundusHelperFiles::clear();
	}

	/**
	 * Add campaign for Ametys sync
	 *
	 * @depecated Use createcampaign instead
	 */
	public function addcampaigns()
	{
		$tab = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			$data                      = array();
			$data['start_date']        = $this->input->get('start_date', null, 'POST');
			$data['end_date']          = $this->input->get('end_date', null, 'POST');
			$data['profile_id']        = $this->input->get('profile_id', null, 'POST');
			$data['year']              = $this->input->get('year', null, 'POST');
			$data['short_description'] = $this->input->get('short_description', null, 'POST');

			$m_programme = $this->getModel('Programme');
			$programmes  = $m_programme->getProgrammes(1);

			if (count($programmes) > 0)
			{
				$result = $this->m_campaign->addCampaignsForProgrammes($data, $programmes);
			}
			else
			{
				$result = false;
			}

			if ($result === false)
			{
				$tab = array('status' => 0, 'msg' => Text::_('COM_EMUNDUS_AMETYS_ERROR_CANNOT_ADD_CAMPAIGNS'), 'data' => $result);
			}
			else
			{
				$tab = array('status' => 1, 'msg' => Text::_('COM_EMUNDUS_CAMPAIGNS_ADDED'), 'data' => $result);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Gets all campaigns linked to a program code
	 *
	 * @deprecated Not replaced yet
	 */
	public function getcampaignsbyprogram()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id))
		{
			$response['msg']       = Text::_('NO_CAMPAIGNS');
			$response['campaigns'] = [];
			$course                = $this->input->get('course', '');

			if (!empty($course))
			{
				$campaigns = $this->m_campaign->getCampaignsByProgram($course);
				$response  = array('status' => true, 'msg' => 'CAMPAIGNS RETRIEVED', 'campaigns' => $campaigns);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * @deprecated
	 * Get the number of campaigns by program
	 *
	 */
	public function getcampaignsbyprogramme()
	{
		$this->getcampaignsbyprogram();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function getallcampaign(): void
	{
		$filter    = $this->input->getString('filter', '');
		$sort      = $this->input->getString('sort', 'DESC');
		$recherche = $this->input->getString('recherche', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);
		$program   = $this->input->getString('program', 'all');
		$parent_id = $this->input->getInt('parent_campaign', 0);
		$order_by  = $this->input->getString('order_by', 'esc.id');
		$order_by  = $order_by == 'label' ? 'esc.label' : $order_by;

		$actionRepository     = new ActionRepository();
		$campaignAction       = $actionRepository->getByName('campaign');
		$campaignDeleteAccess = EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) || EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::DELETE->value, $this->user->id);
		$campaignEditAccess   = EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) || EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::UPDATE->value, $this->user->id);

		$addonRepository       = new AddonRepository();
		$emundusUserRepository = new EmundusUserRepository();

		$userPrograms = $emundusUserRepository->getUserProgramsCodes($this->user->id);
		if (!empty($program) && $program != 'all')
		{
			$userPrograms = array_intersect($userPrograms, [$program]);
		}

		$choices_addon = $addonRepository->getByName('choices');

		$campaigns = $this->campaignRepository->getAllCampaigns($sort, $recherche, $lim, $page, $order_by, null, $parent_id, null, $filter, null, $userPrograms);

		$eMConfig              = ComponentHelper::getParams('com_emundus');
		$allow_pinned_campaign = $eMConfig->get('allow_pinned_campaign', 0);

		$offset        = $this->app->get('offset', 'Europe/Paris');
		$now_date_time = new DateTime('now', new DateTimeZone($offset));
		$now           = $now_date_time->format('Y-m-d H:i:s');

		$results = ['datas' => [], 'count' => $campaigns->getTotalItems()];
		if ($campaigns->getTotalItems() > 0)
		{
			// this data formatted is used in onboarding lists
			foreach ($campaigns->getItems() as $key => $campaign)
			{
				assert($campaign instanceof CampaignEntity);
				$campaignObject           = (object) $campaign->__serialize();
				$campaignObject->nb_files = $campaign->getFilesCount();
				$campaignObject->label    = ['fr' => $campaign->getLabel(), 'en' => $campaign->getLabel()];

				$start_date = $campaign->getStartDate()->format('Y-m-d H:i:s');
				$end_date   = $campaign->getEndDate()->format('Y-m-d H:i:s');

				if ($now < $start_date)
				{
					$campaign_time_state_label = Text::_('COM_EMUNDUS_CAMPAIGN_YET_TO_COME');
					$campaign_time_state_class = 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-bg-neutral-300 tw-text-neutral-700 tw-text-sm tw-font-medium';
				}
				else
				{
					if ($now > $end_date)
					{
						$campaign_time_state_label = Text::_('COM_EMUNDUS_ONBOARD_FILTER_CLOSE');
						$campaign_time_state_class = 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-bg-neutral-950 tw-text-white tw-text-sm tw-font-medium';
					}
					else
					{
						$campaign_time_state_label = Text::_('COM_EMUNDUS_CAMPAIGN_ONGOING');
						$campaign_time_state_class = 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-bg-neutral-300 tw-text-neutral-700 tw-text-sm tw-font-medium';
					}
				}

				if (!class_exists('EmundusHelperDate'))
				{
					require_once JPATH_ROOT . '/components/com_emundus/helpers/date.php';
				}
				$start_date = EmundusHelperDate::displayDate($start_date, 'COM_EMUNDUS_DATE_FORMAT');
				$end_date   = EmundusHelperDate::displayDate($end_date, 'COM_EMUNDUS_DATE_FORMAT');

				$state_values = [
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_STATE'),
						'value'   => $campaign->isPublished() ? Text::_('PUBLISHED') : Text::_('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH'),
						'classes' => 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm' . ($campaign->isPublished() ? ' em-bg-main-500 tw-text-white' : ' tw-bg-neutral-300 tw-text-neutral-700'),
					],
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_TIME_STATE'),
						'value'   => $campaign_time_state_label,
						'classes' => $campaign_time_state_class
					]
				];

				$campaignObject->additional_columns = [
					[
						'key'      => Text::_('COM_EMUNDUS_ONBOARD_START_DATE'),
						'value'    => $start_date,
						'classes'  => '',
						'display'  => 'table',
						'order_by' => 'esc.start_date'
					],
					[
						'key'      => Text::_('COM_EMUNDUS_ONBOARD_END_DATE'),
						'value'    => $end_date,
						'classes'  => '',
						'display'  => 'table',
						'order_by' => 'esc.end_date'
					],
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_STATE'),
						'type'    => 'tags',
						'values'  => $state_values,
						'display' => 'table'
					],
					[
						'key'      => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM'),
						'value'    => $campaign->getProgram()->getLabel(),
						'display'  => 'table',
						'order_by' => 'esp.label'
					],
					[
						'key'      => Text::_('COM_EMUNDUS_ONBOARD_NB_FILES'),
						'value'    => '<a target="_blank" class="tw-cursor-pointer tw-font-semibold tw-text-profile-full tw-flex tw-items-center tw-justify-center hover:tw-underline hover:tw-font-semibold" href="/index.php?option=com_emundus&controller=campaign&task=gotocampaign&campaign_id=' . $campaign->getId() . '" style="line-height: unset;font-size: unset;">' . $campaign->getFilesCount() . '</a>',
						'classes'  => 'go-to-campaign-link',
						'display'  => 'table',
						'order_by' => 'files_count'
					],
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM'),
						'value'   => $campaign->getProgram()->getLabel(),
						'classes' => 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-bg-neutral-300 tw-text-neutral-700 tw-font-medium tw-text-sm',
						'display' => 'blocs'
					],
					[
						'value'   => Text::_('COM_EMUNDUS_DASHBOARD_CAMPAIGN_FROM') . ' ' . $start_date . ' ' . Text::_('COM_EMUNDUS_DASHBOARD_CAMPAIGN_TO') . ' ' . $end_date,
						'classes' => 'em-font-size-14 em-neutral-700-color',
						'display' => 'blocs'
					],
					[
						'type'    => 'tags',
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_STATE'),
						'values'  => [
							$state_values[0],
							$state_values[1],
							[
								'key'     => Text::_('COM_EMUNDUS_FILES_FILES'),
								'value'   => '<a class="tw-cursor-pointer go-to-campaign-link tw-font-semibold hover:tw-font-semibold tw-text-profile-full tw-flex tw-items-center tw-justify-center tw-text-sm hover:tw-underline" href="/index.php?option=com_emundus&controller=campaign&task=gotocampaign&campaign_id=' . $campaign->getId() . '" style="line-height: unset;">' . $campaign->getFilesCount() . ' ' . ($campaign->getFilesCount() > 1 ? Text::_('COM_EMUNDUS_FILES_FILES') : Text::_('COM_EMUNDUS_FILES_FILE')) . '</a>',
								'classes' => 'py-1',
							]
						],
						'classes' => 'em-mt-8 em-mb-8',
						'display' => 'blocs'
					]
				];

				if ($choices_addon->getValue()->isEnabled())
				{
					$campaign_parent_label = Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_NO_PARENT');
					if (!empty($campaign->getParent()))
					{
						$campaign_parent_label = $campaign->getParent()->getLabel();
					}

					$campaignObject->additional_columns[] = [
						'key'      => Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_PARENT'),
						'value'    => $campaign_parent_label,
						'display'  => 'table',
						'order_by' => 'sp.label'
					];
				}

				// Access
				$campaignObject->can_edit   = $campaignEditAccess || ($campaign->getCreatedBy() == $this->user->id);
				$campaignObject->can_delete = $campaignDeleteAccess || ($campaign->getCreatedBy() == $this->user->id);

				$results['datas'][$key] = $campaignObject;
			}
		}

		$this->sendJsonResponse(['status' => true, 'msg' => Text::_('CAMPAIGNS_RETRIEVED'), 'data' => $results, 'allow_pinned_campaigns' => $allow_pinned_campaign, 'code' => EmundusResponse::HTTP_OK]);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function goToCampaign(): void
	{
		$campaign_id = $this->app->input->getInt('campaign_id', 0);
		if (empty($campaign_id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_CAMPAIGN_NO_CAMPAIGN'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$session = $this->app->getSession();
		// new filters
		$campaign_filter = [
			'uid'           => 'campaigns',
			'id'            => 'campaigns',
			'label'         => Text::_('MOD_EMUNDUS_FILTERS_CAMPAIGNS'),
			'type'          => 'select',
			'value'         => [$campaign_id],
			'default'       => true,
			'available'     => true,
			'operator'      => 'IN',
			'andorOperator' => 'OR',
			'menuFilter'    => true,
		];
		$session->set('em-applied-filters', [$campaign_filter]);

		// old filters
		$session->set('filt_params', [
			's'          => [],
			'campaign'   => [$campaign_id],
			'schoolyear' => [],
			'status'     => [],
			'tag'        => [],
			'programme'  => ['%'],
			'published'  => 1
		]);

		if (!class_exists('EmundusModelProfile'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/profile.php';
		}
		$m_profile       = new EmundusModelProfile();
		$current_profile = $m_profile->getProfileById($session->get('emundusUser')->profile);

		$menu  = $this->app->getMenu();
		$items = $menu->getItems('link', 'index.php?option=com_emundus&view=files', true);

		if (is_array($items))
		{
			$redirect_item = $items[0];
			foreach ($items as $item)
			{
				if ($item->menutype == $current_profile['menutype'])
				{
					$redirect_item = $item;
				}
			}
		}
		else
		{
			$redirect_item = $items;
		}

		if (!empty($redirect_item))
		{
			$this->app->redirect('/' . $redirect_item->route);
		}
		else
		{
			$response['msg'] = Text::_('NO_FILES_VIEW_AVAILABLE');
		}

		$this->app->enqueueMessage($response['msg'], 'error');
		$this->app->redirect('/');
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::DELETE]])]
	public function deletecampaign(): EmundusResponse
	{
		$data = $this->input->getInt('id', 0);
		if (empty($data))
		{
			$data = $this->input->getString('ids');
			$data = explode(',', $data);
			$data = array_filter($data);
		}

		if (empty($data))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!$this->m_campaign->deleteCampaign($data, true))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_DELETE_CAMPAIGN'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(true);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE]
	])]
	public function unpublishcampaign(): EmundusResponse
	{
		$data = $this->input->getInt('id', 0);
		if (empty($data))
		{
			$data = $this->input->getString('ids');
			$data = explode(',', $data);
			$data = array_filter($data);
		}

		if (!is_array($data))
		{
			$data = [$data];
		}

		$actionRepository   = new ActionRepository();
		$campaignAction     = $actionRepository->getByName('campaign');
		$campaignEditAccess = EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) || EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::UPDATE->value, $this->user->id);
		if (!$campaignEditAccess)
		{
			$keysToRemove = [];
			foreach ($data as $key => $cid)
			{
				// I can update if I am creator of the campaign even if I don't have the update right on all campaigns
				$campaign = $this->campaignRepository->getById($cid);
				if ($campaign->getCreatedBy() != $this->user->id)
				{
					// Remove the campaign from the list if the user is not creator and doesn't have edit access
					$keysToRemove[] = $key;
				}
			}

			// Remove the campaigns from the data array
			foreach ($keysToRemove as $key)
			{
				unset($data[$key]);
			}
		}

		if (empty($data))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!$this->m_campaign->unpublishCampaign($data, $this->user->id))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_UNPUBLISH_CAMPAIGN'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(true, Text::_('CAMPAIGN_UNPUBLISHED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE]
	])]
	public function publishcampaign(): EmundusResponse
	{
		$data = $this->input->getInt('id', 0);
		if (empty($data))
		{
			$data = $this->input->getString('ids');
			$data = explode(',', $data);
			$data = array_filter($data);
		}

		if (!is_array($data))
		{
			$data = [$data];
		}

		$actionRepository   = new ActionRepository();
		$campaignAction     = $actionRepository->getByName('campaign');
		$campaignEditAccess = EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) || EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::UPDATE->value, $this->user->id);
		if (!$campaignEditAccess)
		{
			$keysToRemove = [];
			foreach ($data as $key => $cid)
			{
				// I can update if I am creator of the campaign even if I don't have the update right on all campaigns
				$campaign = $this->campaignRepository->getById($cid);
				if ($campaign->getCreatedBy() != $this->user->id)
				{
					// Remove the campaign from the list if the user is not creator and doesn't have edit access
					$keysToRemove[] = $key;
				}
			}

			// Remove the campaigns from the data array
			foreach ($keysToRemove as $key)
			{
				unset($data[$key]);
			}
		}

		if (empty($data))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$result = $this->m_campaign->publishCampaign($data, $this->user->id);
		if ($result['success'])
		{
			return EmundusResponse::ok($result['success'], Text::_($result['message']));
		}
		else
		{
			return EmundusResponse::fail(Text::_($result['message']), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR, $result['success']);
		}
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::CREATE]])]
	public function duplicatecampaign(): EmundusResponse
	{
		$campaign_id = $this->input->getInt('id');
		if (empty($campaign_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!$this->m_campaign->duplicateCampaign($campaign_id))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_DUPLICATE_CAMPAIGN'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(Text::_('CAMPAIGN_DUPLICATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getyears(): EmundusResponse
	{
		$years = $this->m_campaign->getYears();

		return EmundusResponse::ok($years);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::CREATE]])]
	public function createcampaign(): void
	{
		$data         = $this->input->getRaw('body');
		$data         = json_decode($data, true);
		$data['user'] = $this->user->id;

		$result = $this->m_campaign->createCampaign($data);
		if ($result)
		{
			$redirect = 'index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=' . $result . '&index=0';

			PluginHelper::importPlugin('emundus', 'custom_event_handler');
			$redirect_dispatcher = $this->app->triggerEvent('onCallEventHandler', ['onCampaignCreateRedirect', ['campaign' => $result]]);
			if (!empty($redirect_dispatcher[0] && !empty($redirect_dispatcher[0]['onCampaignCreateRedirect'])))
			{
				$redirect = $redirect_dispatcher[0]['onCampaignCreateRedirect'];
			}

			$response = array('status' => 1, 'msg' => Text::_('CAMPAIGN_ADDED'), 'data' => $result, 'redirect' => $redirect, 'code' => EmundusResponse::HTTP_OK);
		}
		else
		{
			$response['msg'] = Text::_('ERROR_CANNOT_ADD_CAMPAIGN');
		}

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE],
	])]
	public function updatecampaign(): EmundusResponse
	{
		$cid = $this->input->getInt('cid');
		if (empty($cid))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$actionRepository   = new ActionRepository();
		$campaignAction     = $actionRepository->getByName('campaign');
		$campaignEditAccess = EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id) || EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::UPDATE->value, $this->user->id);
		if (!$campaignEditAccess)
		{
			// I can update if I am creator of the campaign even if I don't have the update right on all campaigns
			$campaign = $this->campaignRepository->getById($cid);
			if ($campaign->getCreatedBy() != $this->user->id)
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}
		}

		$data = $this->input->getRaw('body');
		$data = json_decode($data, true);

		$result = $this->m_campaign->updateCampaign($data, $cid, $this->user->id);
		if (!$result)
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_UPDATE_CAMPAIGN'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok($result, Text::_('CAMPAIGN_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function getcampaignbyid(): EmundusResponse
	{
		$id = $this->input->getInt('id', 0);
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$campaign = $this->m_campaign->getCampaignDetailsById($id);
		if (empty($campaign))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_CAMPAIGN'), EmundusResponse::HTTP_NOT_FOUND);
		}

		return EmundusResponse::ok($campaign, Text::_('CAMPAIGN_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE]
	])]
	public function updateprofile(): EmundusResponse
	{
		$profile  = $this->input->getInt('profile', 0);
		$campaign = $this->input->getInt('campaign', 0);

		if (empty($profile) || empty($campaign))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!$this->m_campaign->updateProfile($profile, $campaign))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_UPDATE_PROFILE'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(true, Text::_('CAMPAIGN_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'form', 'mode' => CrudEnum::CREATE]])]
	public function createdocument(): EmundusResponse
	{

		$document = $this->input->getString('document');
		$document = json_decode($document, true);

		$types = $this->input->getString('types');
		$types = json_decode($types, true);

		$pid = $this->input->getInt('pid', 0);
		if (empty($pid))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$result = $this->m_campaign->createDocument($document, $types, $pid);
		if (!$result['status'])
		{
			throw new \RuntimeException(Text::_($result['msg']), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok($result, Text::_('DOCUMENT_ADDED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'form', 'mode' => CrudEnum::CREATE],
		['id' => 'form', 'mode' => CrudEnum::UPDATE]
	])]
	public function updatedocument(): EmundusResponse
	{
		$document = $this->input->getString('document');
		$document = json_decode($document, true);

		$types = $this->input->getString('types');
		$types = json_decode($types, true);

		$isModeleAndUpdate = $this->input->get('isModeleAndUpdate');
		$did               = $this->input->getInt('did');
		$pid               = $this->input->getInt('pid');

		if (empty($document) || empty($pid) || empty($did))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$result = $this->m_campaign->updateDocument($document, $types, $did, $pid, $isModeleAndUpdate);
		if (!$result)
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_UPDATE_DOCUMENT'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok($result, Text::_('DOCUMENT_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'form', 'mode' => CrudEnum::CREATE],
		['id' => 'form', 'mode' => CrudEnum::UPDATE]
	])]
	public function updatedocumentmandatory(): EmundusResponse
	{
		$did       = $this->input->getInt('did');
		$pid       = $this->input->getInt('pid');
		$mandatory = $this->input->getInt('mandatory');

		if (empty($did) || empty($pid))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!$this->m_campaign->updatedDocumentMandatory($did, $pid, $mandatory))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_UPDATE_DOCUMENT'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(true, Text::_('DOCUMENT_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE]
	])]
	public function getdocumentsdropfiles(): EmundusResponse
	{
		$cid = $this->input->get('cid', 0);
		if (empty($cid))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$campaign_category = $this->m_campaign->getCampaignCategory($cid);
		$datas             = $this->m_campaign->getCampaignDropfilesDocuments($campaign_category);

		return EmundusResponse::ok($datas);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE]
	])]
	public function deletedocumentdropfile(): EmundusResponse
	{
		$did = $this->input->getInt('did');
		if (empty($did))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!$this->m_campaign->deleteDocumentDropfile($did))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_DELETE_DOCUMENT'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(true, Text::_('DOCUMENT_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE]
	])]
	public function editdocumentdropfile(): EmundusResponse
	{
		$did  = $this->input->getInt('did');
		$name = $this->input->getString('name');

		if (empty($did) || empty($name))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!$this->m_campaign->editDocumentDropfile($did, $name))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_EDIT_DOCUMENT'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(true, Text::_('DOCUMENT_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE]
	])]
	public function updateorderdropfiledocuments(): EmundusResponse
	{
		$documents = $this->input->getString('documents');
		$documents = json_decode($documents, true);

		if (!$this->m_campaign->updateOrderDropfileDocuments($documents))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_ORDERING_DOCUMENTS'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(true, Text::_('DOCUMENT_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function pincampaign(): EmundusResponse
	{
		$cid = $this->input->getInt('id', 0);
		if (empty($cid))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		if (!$this->m_campaign->pinCampaign($cid))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_PINNED_CAMPAIGN'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(true, Text::_('PIN_CAMPAIGN_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function unpincampaign(): EmundusResponse
	{
		$cid = $this->input->getInt('id', 0);
		if (empty($cid))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}
		if (!$this->m_campaign->unpinCampaign($cid))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_UNPIN_CAMPAIGN'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok(true, Text::_('PIN_CAMPAIGN_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function getallitemsalias(): EmundusResponse
	{
		$cid = $this->input->getInt('campaign_id', 0);

		$result = $this->m_campaign->getAllItemsAlias($cid);

		return EmundusResponse::ok($result, Text::_('ALL_ITEMS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function getProgrammeByCampaignID(): EmundusResponse
	{
		$campaign_id = $this->input->getInt('campaign_id', 0);
		if (empty($campaign_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$program = $this->m_campaign->getProgrammeByCampaignID($campaign_id);

		return EmundusResponse::ok($program);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE],
	])]
	public function getcampaignmoreformurl(): EmundusResponse
	{
		$campaign_id = $this->input->getInt('cid', 0);
		if (empty($campaign_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$url = $this->m_campaign->getCampaignMoreFormUrl($campaign_id);
		if (empty($url))
		{
			throw new NotFoundException(Text::_('NO_URL'), EmundusResponse::HTTP_NOT_FOUND);
		}

		$url = !empty($this->getBaseUri()) ? $this->getBaseUri() . $url : '/' . $url;

		return EmundusResponse::ok($url, Text::_('URL_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function getCampaignsByProgramId(): EmundusResponse
	{
		$program_id = $this->input->getInt('program_id', 0);
		if (empty($program_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$campaigns = $this->m_campaign->getCampaignsByProgramId($program_id);

		return EmundusResponse::ok($campaigns, Text::_('CAMPAIGNS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function getcampaignlanguages(): EmundusResponse
	{
		$campaign_id = $this->input->getInt('campaign_id', 0);
		if (empty($campaign_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$languages = $this->m_campaign->getCampaignLanguagesValues($campaign_id);

		return EmundusResponse::ok($languages, Text::_('LANGUAGES_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function getcampaignusercategories(): EmundusResponse
	{
		$campaign_id = $this->input->getInt('campaign_id', 0);
		if (empty($campaign_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$usercategories = $this->m_campaign->getCampaignUserCategoriesValues($campaign_id);

		return EmundusResponse::ok($usercategories, Text::_('USERCATEGORIES_RETRIEVED'));
	}

	public function getmediasize(): EmundusResponse
	{
		if ($this->user->guest)
		{
			throw new AccessException(Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$mediaConfig = ComponentHelper::getParams('com_media');

		return EmundusResponse::ok($mediaConfig->get('upload_maxsize', ini_get("upload_max_filesize")));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::UPDATE]])]
	public function getimportmodel(): EmundusResponse
	{
		$this->checkToken('get');

		$campaign_id   = $this->input->getInt('id', 0);
		$status_option = $this->input->getString('status');
		$forms         = $this->input->getString('forms', 0);
		$evaluations   = $this->input->getString('evaluations', 0);
		$validators    = $this->input->getString('validators', 0);
		$format        = $this->input->getString('format', 'xlsx');

		if (empty($campaign_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$m_campaign = $this->getModel('Campaign');
		$campaign   = $m_campaign->getCampaignByID($campaign_id);
		if (empty($campaign))
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_RETRIEVE_CAMPAIGN'), EmundusResponse::HTTP_NOT_FOUND);
		}

		$options   = [
			'status'      => $status_option === 'true',
			'forms'       => $forms === 'true',
			'evaluations' => $evaluations === 'true',
			'validators'  => $validators === 'true',
		];
		$xlsx_path = $m_campaign->generateModel($campaign, $options, $format);
		if (empty($xlsx_path))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_ONBOARD_ERROR_GENERATING_FILE'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		// Transform $xlsx_path to a relative path
		$path = str_replace(JPATH_ROOT, '', $xlsx_path);

		return EmundusResponse::ok($path, Text::_('COM_EMUNDUS_ONBOARD_SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::UPDATE]])]
	public function scanimportfile(): EmundusResponse
	{
		$this->checkToken();

		$file = $this->input->files->get('file');
		if (empty($file) || !is_array($file) || empty($file['name']) || empty($file['tmp_name']))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mtype = finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);

		$valid_types = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv', 'application/vnd.oasis.opendocument.spreadsheet'];
		if (!in_array($mtype, $valid_types) && !in_array($file['type'], $valid_types))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$m_campaign     = $this->getModel('Campaign');
		$rows_to_import = $m_campaign->scanImportFile($file);

		return EmundusResponse::ok($rows_to_import, Text::_('SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::UPDATE]])]
	public function importfiles(): EmundusResponse
	{
		$this->checkToken();

		$campaign_id = $this->input->getInt('campaign_id', 0);
		if (empty($campaign_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$send_email      = $this->input->getInt('send_email', 0);
		$create_new_fnum = $this->input->getInt('create_new_fnum', 0);
		$file            = $this->input->files->get('file');
		if (empty($file) || !is_array($file) || empty($file['name']) || empty($file['tmp_name']))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mtype = finfo_file($finfo, $file['tmp_name']);
		finfo_close($finfo);

		$valid_types = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv', 'application/vnd.oasis.opendocument.spreadsheet'];
		if (!in_array($mtype, $valid_types) && !in_array($file['type'], $valid_types))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		flush();
		ob_flush();

		$m_campaign = $this->getModel('Campaign');
		$result     = $m_campaign->importFiles($file, $campaign_id, $send_email, $create_new_fnum);
		if (empty($results))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_ONBOARD_ERROR_IMPORTING_FILE'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
		}

		return EmundusResponse::ok($result, Text::_('SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function isimportactivated(): EmundusResponse
	{
		$actionRepository = new ActionRepository();
		$addonRepository  = new AddonRepository();
		$importAction     = $actionRepository->getByName('import');

		$allowed        = EmundusHelperAccess::asAccessAction($importAction->getId(), 'c', $this->user->id);
		$addon          = $addonRepository->getByName('import');
		$addonActivated = $allowed && !empty($addon) && $addon->getValue()->isEnabled();

		return EmundusResponse::ok($addonActivated, Text::_('IMPORT_ACTIVATED'));
	}

	/**
	 * @deprecated Need to move to a user or application controller
	 */
	public function getuseridfromfnum()
	{
		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		$fnum = $this->input->getString('fnum', '');

		if (!empty($fnum))
		{
			if (!EmundusHelperAccess::asAccessAction(1, 'r', $this->user->id, $fnum) && !EmundusHelperAccess::isFnumMine($this->user->id, $fnum))
			{
				header('HTTP/1.1 403 Forbidden');
			}
			else
			{

				$m_campaign = $this->getModel('Campaign');
				$user_id    = $m_campaign->getUserIdFromFnum($fnum);

				if (!empty($user_id))
				{
					$response['data']    = $user_id;
					$response['status']  = true;
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
				}
			}
		}
		else
		{
			header('HTTP/1.1 400 Bad Request');
		}

		echo json_encode($response);
		exit();
	}

	public function getavailablechoices(): void
	{
		$response = ['code' => 400, 'status' => false, 'data' => []];

		if ($this->user->guest)
		{
			$response['code']    = 403;
			$response['message'] = 'Access denied.';
			$this->sendJsonResponse($response);

			return;
		}

		$actionRepository         = new ActionRepository();
		$applicationChoicesAction = $actionRepository->getByName('application_choices');

		$search  = $this->input->getString('search', '');
		$filters = $this->input->getRaw('filters');
		if (!empty($filters))
		{
			$filters = json_decode($filters, true);
		}
		$fnum = $this->input->getString('fnum');
		if (!empty($fnum) && EmundusHelperAccess::asAccessAction($applicationChoicesAction->getId(), 'r', $this->user->id, $fnum))
		{
			$current_fnum = $fnum;
		}
		else
		{
			$e_session    = Factory::getApplication()->getSession()->get('emundusUser');
			$current_fnum = $e_session->fnum;
		}

		if (empty($current_fnum))
		{
			$response['code']    = 400;
			$response['message'] = 'Missing fnum parameter.';
			$this->sendJsonResponse($response);

			return;
		}

		// TODO: refactor this with a Filter object
		$built_filters      = [];
		$more_elements      = $this->campaignRepository->getCampaignMoreElements();
		if (!class_exists('EmundusModelForm'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/form.php';
		}
		$m_form = new EmundusModelForm();
		foreach ($more_elements as $element)
		{
			if ($element['hidden'] || $element['show_in_list_summary'] == 0)
			{
				continue;
			}

			$type   = 'text';
			$params = json_decode($element['params']);

			$filter = [
				'key'           => $element['name'],
				'label'         => $element['label'],
				'type'          => $type,
				'alwaysDisplay' => true,
				'value'         => '',
			];

			if ($element['plugin'] === 'databasejoin')
			{
				$options = [];
				try
				{
					$databasejoin_options = $m_form->getDatabaseJoinOptions($params->join_db_name, $params->join_key_column, $params->join_val_column);
					$options[]            = (object) ['value' => '', 'label' => Text::_('PLEASE_SELECT')];
					foreach ($databasejoin_options as $db_option)
					{
						$option        = new stdClass();
						$option->value = $db_option->primary_key;
						$option->label = $db_option->value;
						$options[]     = $option;
					}
					$filter['type']    = 'select';
					$filter['options'] = $options;
				}
				catch (Exception $e)
				{
					continue;
				}
			}

			if ($element['plugin'] === 'yesno')
			{
				$options           = [
					['value' => '', 'label' => Text::_('PLEASE_SELECT')],
					['value' => 1, 'label' => Text::_('JYES')],
					['value' => 0, 'label' => Text::_('JNO')],
				];
				$filter['type']    = 'select';
				$filter['options'] = $options;
			}

			$built_filters[] = $filter;
		}

		// Set value from input filters
		if (!empty($filters) && is_array($filters))
		{
			foreach ($built_filters as &$b_filter)
			{
				foreach ($filters as $key => $filter)
				{
					if ($b_filter['key'] === $key)
					{
						$b_filter['value'] = $filter;
						break;
					}
				}
			}
		}

		// Remove filters with empty value
		$filters = array_filter($filters, function ($value) {
			return $value !== '' && $value !== null;
		});

		$choices = [];

		$applicationFileRepository = new ApplicationFileRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($current_fnum);
		if (empty($applicationFile) || empty($applicationFile->getCampaignId()))
		{
			$response['code']    = 403;
			$response['message'] = 'No application file found for this user.';
			$this->sendJsonResponse($response);

			return;
		}

		$emundusUserRepository = new EmundusUserRepository();
		$emundusUser           = $emundusUserRepository->getByFnum($current_fnum);
		$categoryUser          = $emundusUser->getUserCategory();

		$campaign_parameters = $this->campaignRepository->getParameters();
		$campaign_choices    = $this->campaignRepository->getAllCampaigns('ASC', $search, 0, 0, 't.label', true, $applicationFile->getCampaignId(), $categoryUser?->getId(), null, null, [], [], $filters);
		if ($campaign_choices->getTotalItems() > 0)
		{
			foreach ($campaign_choices->getItems() as $choice)
			{
				/**
				 * @var CampaignEntity $choice
				 */
				$choices[] = $choice->__serialize();
			}
		}

		$response['code']       = 200;
		$response['data']       = $choices;
		$response['filters']    = $built_filters;
		$response['parameters'] = $campaign_parameters;
		$response['status']     = true;
		$response['message']    = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function needmoreinfo(): EmundusResponse
	{
		$id = $this->input->getInt('id', 0);
		if (empty($id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMETERS'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$emParams             = ComponentHelper::getParams('com_emundus');
		$force_campaigns_more = $emParams->get('force_campaigns_more', 0);
		$campaign_more_row    = null;
		if ($force_campaigns_more == 1)
		{
			$campaign_more_row = $this->m_campaign->getCampaignMoreRowId($id);
		}

		return EmundusResponse::ok(!empty($campaign_more_row), Text::_('SUCCESS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function getparentcampaignsforfilter(): EmundusResponse
	{
		$emundusUserRepository = new EmundusUserRepository();

		$userPrograms = $emundusUserRepository->getUserProgramsCodes($this->user->id);
		if (empty($userPrograms))
		{
			throw new \RuntimeException(Text::_('ERROR_NO_PROGRAMS_ASSIGNED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$campaigns          = $this->campaignRepository->getParentCampaigns($userPrograms);

		$campaignsObjects = [];
		foreach ($campaigns as $campaign)
		{
			$campaignsObjects[] = [
				'value' => $campaign->getId(),
				'label' => $campaign->getLabel()
			];
		}

		return EmundusResponse::ok($campaignsObjects, Text::_('SUCCESS'));
	}
}

?>
