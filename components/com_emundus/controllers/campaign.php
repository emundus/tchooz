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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Traits\TraitResponse;

/**
 * Emundus Campaign Controller
 * @package     Emundus
 */
class EmundusControllerCampaign extends BaseController
{
	use TraitResponse;

	/**
	 * User object.
	 *
	 * @var \Joomla\CMS\User\User|JUser|mixed|null
	 * @since version 1.0.0
	 */
	private $_user;

	/**
	 * @var EmundusModelCampaign
	 * @since version 1.0.0
	 */
	private $m_campaign;

	private ActionEntity $applicationChoicesAction;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   version 1.0.0
	 */
	function __construct($config = array())
	{
		parent::__construct($config);

		if (!class_exists('EmundusHelperAccess'))
		{
			require_once(JPATH_BASE . DS . '/components/com_emundus/helpers/access.php');
		}
		if (!class_exists('ActionRepository'))
		{
			require_once JPATH_SITE . '/components/com_emundus/classes/Repositories/Actions/ActionRepository.php';
		}

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getIdentity();

		$this->m_campaign = $this->getModel('Campaign');

		$actionRepository               = new ActionRepository();
		$this->applicationChoicesAction = $actionRepository->getByName('application_choices');
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
	 * @since version 1.0.0
	 */
	public function addcampaigns()
	{
		$tab = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
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
	 * @since version 1.0.0
	 */
	public function getcampaignsbyprogram()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
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
	 * @since version 1.0.0
	 */
	public function getcampaignsbyprogramme()
	{
		$this->getcampaignsbyprogram();
	}

	/**
	 * Get the campaigns's list filtered
	 *
	 * @since version 1.0.0.0
	 */
	public function getallcampaign()
	{
		$tab = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$filter    = $this->input->getString('filter', '');
			$sort      = $this->input->getString('sort', 'DESC');
			$recherche = $this->input->getString('recherche', '');
			$lim       = $this->input->getInt('lim', 0);
			$page      = $this->input->getInt('page', 0);
			$program   = $this->input->getString('program', 'all');
			$parent_id = $this->input->getInt('parent_campaign', 0);
			$order_by  = $this->input->getString('order_by', 'sc.id');
			$order_by  = $order_by == 'label' ? 'sc.label' : $order_by;

			$campaignRepository = new CampaignRepository();
			$addonRepository    = new AddonRepository();
			$choices_addon      = $addonRepository->getByName('choices');

			$campaigns = $this->m_campaign->getAssociatedCampaigns($filter, $sort, $recherche, $lim, $page, $program, 'all', $order_by, $parent_id);

			$eMConfig              = ComponentHelper::getParams('com_emundus');
			$allow_pinned_campaign = $eMConfig->get('allow_pinned_campaign', 0);

			if (count($campaigns) > 0)
			{
				// this data formatted is used in onboarding lists
				foreach ($campaigns['datas'] as $key => $campaign)
				{
					$campaign->label = ['fr' => $campaign->label, 'en' => $campaign->label];

					$config        = $this->app->getConfig();
					$offset        = $config->get('offset');
					$now_date_time = new DateTime('now', new DateTimeZone($offset));
					$now           = $now_date_time->format('Y-m-d H:i:s');
					$start_date    = date('Y-m-d H:i:s', strtotime($campaign->start_date));
					$end_date      = date('Y-m-d H:i:s', strtotime($campaign->end_date));

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
					$start_date = EmundusHelperDate::displayDate($campaign->start_date, 'COM_EMUNDUS_DATE_FORMAT');
					$end_date   = EmundusHelperDate::displayDate($campaign->end_date, 'COM_EMUNDUS_DATE_FORMAT');

					$state_values = [
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_STATE'),
							'value'   => $campaign->published ? Text::_('PUBLISHED') : Text::_('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH'),
							'classes' => 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm' . ($campaign->published ? ' em-bg-main-500 tw-text-white' : ' tw-bg-neutral-300 tw-text-neutral-700'),
						],
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_TIME_STATE'),
							'value'   => $campaign_time_state_label,
							'classes' => $campaign_time_state_class
						]
					];

					$campaign->additional_columns = [
						[
							'key'      => Text::_('COM_EMUNDUS_ONBOARD_START_DATE'),
							'value'    => $start_date,
							'classes'  => '',
							'display'  => 'table',
							'order_by' => 'sc.start_date'
						],
						[
							'key'      => Text::_('COM_EMUNDUS_ONBOARD_END_DATE'),
							'value'    => $end_date,
							'classes'  => '',
							'display'  => 'table',
							'order_by' => 'sc.end_date'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_STATE'),
							'type'    => 'tags',
							'values'  => $state_values,
							'display' => 'table'
						],
						[
							'key'      => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM'),
							'value'    => $campaign->program_label,
							'display'  => 'table',
							'order_by' => 'sp.label'
						],
						[
							'key'      => Text::_('COM_EMUNDUS_ONBOARD_NB_FILES'),
							'value'    => '<a target="_blank" class="tw-cursor-pointer tw-font-semibold tw-text-profile-full tw-flex tw-items-center tw-justify-center hover:tw-underline hover:tw-font-semibold" href="/index.php?option=com_emundus&controller=campaign&task=gotocampaign&campaign_id=' . $campaign->id . '" style="line-height: unset;font-size: unset;">' . $campaign->nb_files . '</a>',
							'classes'  => 'go-to-campaign-link',
							'display'  => 'table',
							'order_by' => 'nb_files'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM'),
							'value'   => $campaign->program_label,
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
									'value'   => '<a class="tw-cursor-pointer go-to-campaign-link tw-font-semibold hover:tw-font-semibold tw-text-profile-full tw-flex tw-items-center tw-justify-center tw-text-sm hover:tw-underline" href="/index.php?option=com_emundus&controller=campaign&task=gotocampaign&campaign_id=' . $campaign->id . '" style="line-height: unset;">' . $campaign->nb_files . ' ' . ($campaign->nb_files > 1 ? Text::_('COM_EMUNDUS_FILES_FILES') : Text::_('COM_EMUNDUS_FILES_FILE')) . '</a>',
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
						if (!empty($campaign->parent_id))
						{
							$campaign_parent       = $campaignRepository->getById($campaign->parent_id);
							$campaign_parent_label = $campaign_parent->getLabel();
						}
						$campaign->additional_columns[] = [
							'key'      => Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_PARENT'),
							'value'    => $campaign_parent_label,
							'display'  => 'table',
							'order_by' => 'sp.label'
						];
					}

					$campaigns['datas'][$key] = $campaign;
				}

				$tab = array('status' => true, 'msg' => Text::_('CAMPAIGNS_RETRIEVED'), 'data' => $campaigns, 'allow_pinned_campaigns' => $allow_pinned_campaign);
			}
			else
			{
				$tab = array('status' => false, 'msg' => Text::_('NO_CAMPAIGNS'), 'data' => $campaigns, 'allow_pinned_campaigns' => $allow_pinned_campaign);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Go to files menu with campaign filter
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function goToCampaign()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$campaign_id = $this->app->input->getInt('campaign_id', 0);
			$session     = $this->app->getSession();

			// new filters
			$campaign_filter = [
				'uid'           => 'campaigns',
				'id'            => 'campaigns',
				'label'         => Text::_('MOD_EMUNDUS_FILTERS_CAMPAIGNS'),
				'type'          => 'select',
				'value'         => !empty($campaign_id) ? [$campaign_id] : [],
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
				'campaign'   => !empty($campaign_id) ? [$campaign_id] : [],
				'schoolyear' => [],
				'status'     => [],
				'tag'        => [],
				'programme'  => ['%'],
				'published'  => 1
			]);

			require_once JPATH_ROOT . '/components/com_emundus/models/profile.php';
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
		}

		$this->app->enqueueMessage($response['msg'], 'error');
		$this->app->redirect('/');
	}

	/**
	 * Delete one or multiple campaigns
	 *
	 * @since version 1.0.0
	 */
	public function deletecampaign()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$response = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_DELETE_CAMPAIGN'), 'data' => false);

			$data = $this->input->getInt('id', 0);
			if (empty($data))
			{
				$data = $this->input->getString('ids');
				$data = explode(',', $data);
			}

			if (!empty($data))
			{
				$deleted = $this->m_campaign->deleteCampaign($data, true);

				if ($deleted)
				{
					$response = array('status' => 1, 'msg' => Text::_('CAMPAIGN_PUBLISHED'), 'data' => $deleted);
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function unpublishcampaign()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$response = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_UNPUBLISH_CAMPAIGN'), 'data' => false);

			$data = $this->input->getInt('id', 0);
			if (empty($data))
			{
				$data = $this->input->getString('ids');
				$data = explode(',', $data);
			}

			if (!empty($data))
			{
				$unpublished = $this->m_campaign->unpublishCampaign($data, $this->_user->id);

				if ($unpublished)
				{
					$response = array('status' => 1, 'msg' => Text::_('CAMPAIGN_UNPUBLISHED'), 'data' => $unpublished);
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Publish one or multiple campaigns
	 *
	 * @since version 1.0.0
	 */
	public function publishcampaign()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$response = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_PUBLISH_CAMPAIGN'), 'data' => false);

			$data = $this->input->getInt('id', 0);
			if (empty($data))
			{
				$data = $this->input->getString('ids');
				$data = explode(',', $data);
			}

			if (!empty($data))
			{
				$result = $this->m_campaign->publishCampaign($data, $this->_user->id);

				$response = array('status' => $result['success'], 'msg' => Text::_($result['message']), 'data' => $result['success']);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Duplicate one or multiple campaigns
	 *
	 * @since version 1.0.0
	 */
	public function duplicatecampaign()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$response['msg'] = Text::_('COM_EMUNDUS_ERROR_CANNOT_DUPLICATE_CAMPAIGN');
			$campaign_id     = $this->input->getInt('id');

			if (!empty($campaign_id))
			{
				if ($this->m_campaign->duplicateCampaign($campaign_id))
				{
					$this->getallcampaign();

					// the response will be sent by getallcampaign but in case of error, we must send it here
					$response['status'] = 1;
					$response['msg']    = Text::_('CAMPAIGN_DUPLICATED');
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Get teaching_unity available
	 *
	 * @since version 1.0.0
	 * @todo  Throw in the years controller
	 */
	public function getyears()
	{
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$years = $this->m_campaign->getYears();

			if ($years > 0)
			{
				$tab = array('status' => 1, 'msg' => Text::_('CAMPAIGNS_RETRIEVED'), 'data' => $years);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_CAMPAIGNS'), 'data' => $years);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Create a campaign
	 *
	 * @since version 1.0.0
	 */
	public function createcampaign()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'redirect' => 0);

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$data         = $this->input->getRaw('body');
			$data         = json_decode($data, true);
			$data['user'] = $this->_user->id;

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

				$response = array('status' => 1, 'msg' => Text::_('CAMPAIGN_ADDED'), 'data' => $result, 'redirect' => $redirect);
			}
			else
			{
				$response['msg'] = Text::_('ERROR_CANNOT_ADD_CAMPAIGN');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Update a campaign
	 *
	 * @since version 1.0.0
	 */
	public function updatecampaign()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$data = $this->input->getRaw('body');
			$data = json_decode($data, true);
			$cid  = $this->input->getInt('cid');

			$data['user'] = $this->_user->id;

			if (!empty($cid))
			{
				$result = $this->m_campaign->updateCampaign($data, $cid, $this->_user->id);

				if ($result)
				{
					$response = array('status' => true, 'msg' => Text::_('CAMPAIGN_UPDATED'), 'data' => $result);
				}
				else
				{
					$response['msg'] = Text::_('ERROR_CANNOT_UPDATE_CAMPAIGN');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMETERS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Get a campaign by id
	 *
	 * @since version 1.0.0
	 */
	public function getcampaignbyid()
	{
		$response = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$id = $this->input->getInt('id', 0);

			$campaign = $this->m_campaign->getCampaignDetailsById($id);

			if (!empty($campaign))
			{
				$response = array('status' => 1, 'code' => 200, 'msg' => Text::_('CAMPAIGN_RETRIEVED'), 'data' => $campaign);
			}
			else
			{
				$response = array('status' => 0, 'code' => 404, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_CAMPAIGN'), 'data' => $campaign);
			}
		}

		$this->sendJsonResponse($response);
	}

	/**
	 * Affect a profile(form) to a campaign
	 *
	 * @since version 1.0.0
	 */
	public function updateprofile()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$response['msg'] = Text::_('ERROR_CANNOT_UPDATE_CAMPAIGN');
			$profile         = $this->input->getInt('profile', 0);
			$campaign        = $this->input->getInt('campaign', 0);

			if (!empty($profile) && !empty($campaign))
			{
				$updated = $this->m_campaign->updateProfile($profile, $campaign);

				if ($updated)
				{
					$response = ['status' => 1, 'msg' => Text::_('CAMPAIGN_UPDATED'), 'data' => $updated];
				}
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Get campaigns without profile affected and not finished
	 *
	 * @since version 1.0.0
	 */
	public function getcampaignstoaffect()
	{
		$tab = array('status' => false, 'msg' => Text::_("ACCESS_DENIED"));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$campaigns = $this->m_campaign->getCampaignsToAffect();

			if (!empty($campaigns))
			{
				$tab = array('status' => 1, 'msg' => Text::_('USERS_RETRIEVED'), 'data' => $campaigns);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_USERS'), 'data' => $campaigns);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Get campaigns with term filter in name and description
	 *
	 * @since 1.0
	 */
	public function getcampaignstoaffectbyterm()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$term = $this->input->getString('term');

			$campaigns = $this->m_campaign->getCampaignsToAffectByTerm($term);

			if (!empty($campaigns))
			{
				$tab = array('status' => 1, 'msg' => Text::_('USERS_RETRIEVED'), 'data' => $campaigns);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_USERS'), 'data' => $campaigns);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Add a new document to form
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function createdocument()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$document = $this->input->getString('document');
			$document = json_decode($document, true);

			$types = $this->input->getString('types');
			$types = json_decode($types, true);

			$pid = $this->input->getInt('pid', 0);

			if (!empty($pid))
			{
				$result = $this->m_campaign->createDocument($document, $types, $pid);
				if ($result['status'])
				{
					$response = array('status' => 1, 'msg' => Text::_('DOCUMENT_ADDED'), 'data' => $result);
				}
				else
				{
					$response['msg'] = Text::_($result['msg']);
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMETERS');
			}
		}

		echo json_encode($response);
		exit;
	}

	/**
	 * Update form document
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function updatedocument()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$document = $this->input->getString('document');
			$document = json_decode($document, true);

			$types = $this->input->getString('types');
			$types = json_decode($types, true);

			$isModeleAndUpdate = $this->input->get('isModeleAndUpdate');
			$did               = $this->input->getInt('did');
			$pid               = $this->input->getInt('pid');

			$response = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_UPDATE_DOCUMENT'), 'data' => '');

			if (!empty($document) && !empty($pid) && !empty($did))
			{
				$result = $this->m_campaign->updateDocument($document, $types, $did, $pid, $isModeleAndUpdate);

				$response['data'] = $result;
				if ($result)
				{
					$response['status'] = 1;
					$response['msg']    = Text::_('DOCUMENT_UPDATED');
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMETERS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Update document mandatory
	 *
	 * @since version 1.0.0
	 */
	public function updatedocumentmandatory()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$did       = $this->input->getInt('did');
			$pid       = $this->input->getInt('pid');
			$mandatory = $this->input->getInt('mandatory');

			if (!empty($did) && !empty($pid))
			{
				$tab['status'] = $this->m_campaign->updatedDocumentMandatory($did, $pid, $mandatory);
				if ($tab['status'])
				{
					$tab['msg'] = Text::_('DOCUMENT_UPDATED');
				}
			}
		}

		echo json_encode((object) $tab);
		exit;
	}


	/**
	 * Update translations of documents
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function updateDocumentFalang()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => false);

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$response['msg'] = Text::_('ERROR_CANNOT_UPDATE_DOCUMENT');

			$text         = new stdClass;
			$text->fr     = $this->input->getString('text_fr');
			$text->en     = $this->input->getString('text_en');
			$reference_id = $this->input->getInt('did');

			$m_falang = $this->getModel('Falang');
			$result   = $m_falang->updateFalang($text, $reference_id, 'emundus_setup_attachments', 'value');

			if ($result)
			{
				$response = array('status' => 1, 'msg' => Text::_('DOCUMENT_UPDATED'), 'data' => $result);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Get translations of documents
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function getDocumentFalang()
	{
		$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_UPDATE_DOCUMENT'), 'data' => 0);

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$reference_id = $this->input->getInt('docid');

			if (!empty($reference_id))
			{
				$m_falang = $this->getModel('Falang');

				$result = $m_falang->getFalang($reference_id, 'emundus_setup_attachments', 'value');

				if ($result)
				{
					$tab = array('status' => 1, 'msg' => Text::_('DOCUMENT_UPDATE'), 'data' => $result);
				}
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Get Dropfiles documents linked to a campaign
	 *
	 * @throws Exception
	 * @since version
	 */
	public function getdocumentsdropfiles()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$cid = $this->input->get('cid', 0);

			if (!empty($cid))
			{
				$campaign_category = $this->m_campaign->getCampaignCategory($cid);
				$datas             = $this->m_campaign->getCampaignDropfilesDocuments($campaign_category);

				$response = array('status' => '1', 'msg' => Text::_('SUCCESS'), 'documents' => $datas);
			}
			else
			{
				$response['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_DOCUMENTS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Delete Dropfile document
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function deletedocumentdropfile()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$did = $this->input->getInt('did');

			$result = $this->m_campaign->deleteDocumentDropfile($did);

			if ($result)
			{
				$tab = array('status' => 1, 'msg' => Text::_('DOCUMENT_DELETED'), 'data' => $result);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_DELETE_DOCUMENT'), 'data' => $result);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Edit a Dropfile document
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function editdocumentdropfile()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$did  = $this->input->getInt('did');
			$name = $this->input->getString('name');

			$result = $this->m_campaign->editDocumentDropfile($did, $name);

			if ($result)
			{
				$tab = array('status' => 1, 'msg' => Text::_('DOCUMENT_EDITED'), 'data' => $result);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_EDIT_DOCUMENT'), 'data' => $result);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Update the order of Dropfiles documents
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function updateorderdropfiledocuments()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$documents = $this->input->getString('documents');
			$documents = json_decode($documents, true);

			$result = $this->m_campaign->updateOrderDropfileDocuments($documents);

			if ($result)
			{
				$response = array('status' => 1, 'msg' => Text::_('DOCUMENT_ORDERING'), 'data' => $result);
			}
			else
			{
				$response['msg'] = Text::_('ERROR_CANNOT_ORDERING_DOCUMENTS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Get documents link to form by campaign (by the module)
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function getdocumentsform()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result   = 0;
			$response = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$pid = $this->input->get('pid');

			$datas = $this->m_campaign->getFormDocuments($pid);

			$response = array('status' => '1', 'msg' => 'SUCCESS', 'documents' => $datas);
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Update a document available in form view
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function editdocumentform()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{
			$did  = $this->input->getInt('did');
			$pid  = $this->input->getInt('pid');
			$name = $this->input->getString('name');

			$result = $this->m_campaign->editDocumentForm($did, $name, $pid);

			if ($result)
			{
				$tab = array('status' => 1, 'msg' => Text::_('DOCUMENT_EDITED'), 'data' => $result);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_EDIT_DOCUMENT'), 'data' => $result);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Delete a document from form view
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	public function deletedocumentform()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else
		{

			$did = $this->input->getInt('did');
			$pid = $this->input->getInt('pid');

			$result = $this->m_campaign->deleteDocumentForm($did, $pid);

			if ($result)
			{
				$tab = array('status' => 1, 'msg' => Text::_('DOCUMENT_EDITED'), 'data' => $result);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_EDIT_DOCUMENT'), 'data' => $result);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Pin a campaign to homepage
	 *
	 * @since version 1.0.0
	 */
	public function pincampaign()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_('ACCESS_DENIED'));
		}
		else
		{

			$cid = $this->input->getInt('id', 0);

			$result = $this->m_campaign->pinCampaign($cid);

			if ($result)
			{
				$tab = array('status' => 1, 'msg' => Text::_('CAMPAIGN_PINNED'), 'data' => $result);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_PINNED_CAMPAIGN'), 'data' => $result);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Unpin campaign of the homepage
	 *
	 * @since version 1.0.0
	 */
	public function unpincampaign()
	{
		$tab = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$cid = $this->input->getInt('id', 0);

			$result = $this->m_campaign->unpinCampaign($cid);

			if ($result)
			{
				$tab = array('status' => 1, 'msg' => Text::_('CAMPAIGN_UNPINNED'), 'data' => $result);
			}
			else
			{
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_UNPIN_CAMPAIGN'), 'data' => $result);
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	/**
	 * Get alias of a campaign
	 *
	 * @since version 1.0.0
	 */
	public function getallitemsalias()
	{
		$response = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$response['msg'] = Text::_('ERROR_CANNOT_UNPIN_CAMPAIGN');

			$cid = $this->input->getInt('campaign_id', 0);

			$result = $this->m_campaign->getAllItemsAlias($cid);

			if ($result)
			{
				$response = array('status' => 1, 'msg' => Text::_('CAMPAIGN_UNPINNED'), 'data' => $result);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Get programme by campaign id
	 *
	 * @since version 1.0.0
	 */
	public function getProgrammeByCampaignID()
	{
		$response = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$campaign_id = $this->input->getInt('campaign_id', 0);
			$programmes  = $this->m_campaign->getProgrammeByCampaignID($campaign_id);

			if (!empty($programmes))
			{
				$response = array('status' => 1, 'msg' => Text::_('PROGRAMMES_RETRIEVED'), 'data' => $programmes);
			}
			else
			{
				$response = array('status' => 0, 'msg' => Text::_('NO_PROGRAMMES'), 'data' => $programmes);
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	/**
	 * Get url of the form that extend the campaign
	 *
	 * @since version 1.0.0
	 */
	public function getcampaignmoreformurl()
	{
		$response = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$campaign_id = $this->input->getInt('cid', 0);

			$url = $this->m_campaign->getCampaignMoreFormUrl($campaign_id);

			if (!empty($url))
			{
				$url      = !empty(Uri::base()) ? Uri::base() . $url : '/' . $url;
				$response = ['status' => 1, 'msg' => Text::_('URL_RETRIEVED'), 'data' => $url, 'code' => 200];
			}
			else
			{
				$response = ['status' => 0, 'msg' => Text::_('NO_URL'), 'data' => $url, 'code' => 404];
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getCampaignsByProgramId()
	{
		$response = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$response['code'] = 500;
			$program_id       = $this->input->getInt('program_id', 0);

			if (!empty($program_id))
			{
				$campaigns = $this->m_campaign->getCampaignsByProgramId($program_id);

				$response = [
					'status' => 1,
					'msg'    => Text::_('CAMPAIGNS_RETRIEVED'),
					'data'   => $campaigns,
				];
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getcampaignlanguages()
	{
		$response = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$response['code'] = 500;
			$campaign_id      = $this->input->getInt('campaign_id', 0);

			if (!empty($campaign_id))
			{
				$m_campaign = $this->getModel('Campaign');

				$languages = $m_campaign->getCampaignLanguagesValues($campaign_id);
				$response  = ['status' => 1, 'msg' => Text::_('LANGUAGES_RETRIEVED'), 'data' => $languages, 'code' => 200];
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getcampaignusercategories()
	{
		$response = ['status' => 0, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$response['code'] = 500;
			$campaign_id      = $this->input->getInt('campaign_id', 0);

			if (!empty($campaign_id))
			{
				$m_campaign = $this->getModel('Campaign');

				$usercategories = $m_campaign->getCampaignUserCategoriesValues($campaign_id);
				$response       = ['status' => 1, 'msg' => Text::_('USERCATEGORIES_RETRIEVED'), 'data' => $usercategories, 'code' => 200];
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getmediasize()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'size' => 10];

		if (!$this->_user->guest)
		{
			$mediaConfig = ComponentHelper::getParams('com_media');

			$response['status'] = true;
			$response['msg']    = Text::_('SUCCESS');
			$response['size']   = $mediaConfig->get('upload_maxsize', ini_get("upload_max_filesize"));
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getimportmodel(): void
	{
		$this->checkToken('get');

		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => []
		];

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			header('HTTP/1.1 403 Forbidden');
		}
		else
		{
			$campaign_id   = $this->input->getInt('id', 0);
			$status_option = $this->input->getString('status');
			$forms         = $this->input->getString('forms', 0);
			$evaluations   = $this->input->getString('evaluations', 0);
			$validators    = $this->input->getString('validators', 0);
			$format        = $this->input->getString('format', 'xlsx');

			if (!empty($campaign_id))
			{
				$m_campaign = $this->getModel('Campaign');
				$campaign   = $m_campaign->getCampaignByID($campaign_id);

				if (!empty($campaign))
				{
					$options   = [
						'status'      => $status_option === 'true',
						'forms'       => $forms === 'true',
						'evaluations' => $evaluations === 'true',
						'validators'  => $validators === 'true',
					];
					$xlsx_path = $m_campaign->generateModel($campaign, $options, $format);
					if (!empty($xlsx_path))
					{
						// Transform $xlsx_path to a relative path
						$path = str_replace(JPATH_ROOT, '', $xlsx_path);

						$response['data']    = $path;
						$response['status']  = true;
						$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
					}
				}
			}
			else
			{
				header('HTTP/1.1 400 Bad Request');
			}
		}

		echo json_encode($response);
		exit();
	}

	public function scanimportfile()
	{
		$this->checkToken();

		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => [],
			'code'    => 403
		];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$file = $this->input->files->get('file');

			// Check if file is not empty and is a valid file (csv, xlsx, xls, ods)
			if (!empty($file) && is_array($file) && !empty($file['name']) && !empty($file['tmp_name']))
			{
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mtype = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);

				$valid_types = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv', 'application/vnd.oasis.opendocument.spreadsheet'];
				if (in_array($mtype, $valid_types) || in_array($file['type'], $valid_types))
				{
					$m_campaign     = $this->getModel('Campaign');
					$rows_to_import = $m_campaign->scanImportFile($file);

					if (!empty($rows_to_import))
					{
						$response['data']    = $rows_to_import;
						$response['status']  = true;
						$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
						$response['code']    = 200;
					}
					else
					{
						$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_NO_ROWS_TO_IMPORT');
						$response['code']    = 204;
					}
				}
				else
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE');
					$response['code']    = 400;
				}
			}
			else
			{
				$response['code'] = 400;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function importfiles()
	{
		$this->checkToken();

		$response = [
			'status'  => false,
			'message' => Text::_('COM_EMUNDUS_ONBOARD_ACCESS_DENIED'),
			'data'    => [],
			'code'    => 403
		];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id))
		{
			$campaign_id     = $this->input->getInt('campaign_id', 0);
			$send_email      = $this->input->getInt('send_email', 0);
			$create_new_fnum = $this->input->getInt('create_new_fnum', 0);
			$file            = $this->input->files->get('file');

			// Check if file is not empty and is a valid file (csv, xlsx, xls, ods)
			if (!empty($file) && is_array($file) && !empty($file['name']) && !empty($file['tmp_name']))
			{
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mtype = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);

				$valid_types = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv', 'application/vnd.oasis.opendocument.spreadsheet'];

				if (in_array($mtype, $valid_types) || in_array($file['type'], $valid_types))
				{
					flush();
					ob_flush();

					$m_campaign = $this->getModel('Campaign');
					$result     = $m_campaign->importFiles($file, $campaign_id, $send_email, $create_new_fnum);

					if (!empty($result))
					{
						$response['data']    = $result;
						$response['status']  = true;
						$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_SUCCESS');
						$response['code']    = 200;
					}
				}
				else
				{
					$response['message'] = Text::_('COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE');
					$response['code']    = 400;
				}
			}
			else
			{
				$response['code'] = 400;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function isimportactivated()
	{
		$response = ['code' => 403, 'message' => Text::_('IMPORT_NOT_ACTIVATED'), 'status' => false];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$m_campaign = new EmundusModelCampaign();
			$response   = ['code' => 200, 'message' => Text::_('IMPORT_ACTIVATED'), 'status' => true, 'data' => $m_campaign->getImportAddon()->enabled && EmundusHelperAccess::asAccessAction($m_campaign->getImportActionId(), 'c', $this->_user->id)];
		}

		$this->sendJsonResponse($response);
	}

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
			if (!EmundusHelperAccess::asAccessAction(1, 'r', $this->_user->id, $fnum) && !EmundusHelperAccess::isFnumMine($this->_user->id, $fnum))
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

		if ($this->_user->guest)
		{
			$response['code']    = 403;
			$response['message'] = 'Access denied.';
			$this->sendJsonResponse($response);

			return;
		}

		$search  = $this->input->getString('search', '');
		$filters = $this->input->getRaw('filters');
		if (!empty($filters))
		{
			$filters = json_decode($filters, true);
		}
		$fnum = $this->input->getString('fnum');
		if (!empty($fnum) && EmundusHelperAccess::asAccessAction($this->applicationChoicesAction->getId(), 'r', $this->_user->id, $fnum))
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
		$campaignRepository = new CampaignRepository();
		$more_elements      = $campaignRepository->getCampaignMoreElements();
		if (!class_exists('EmundusModelForm'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/form.php';
		}
		$m_form = new EmundusModelForm();
		foreach ($more_elements as $element)
		{
			if ($element['hidden'])
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

		$campaign_parameters = $campaignRepository->getParameters();
		$campaign_choices    = $campaignRepository->getAllCampaigns('ASC', $search, 0, 0, 't.label', true, $applicationFile->getCampaignId(), $categoryUser?->getId(), [], $filters);
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

	public function needmoreinfo()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => false];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$id = $this->input->getInt('id', 0);

			if (!empty($id))
			{
				$emParams             = ComponentHelper::getParams('com_emundus');
				$force_campaigns_more = $emParams->get('force_campaigns_more', 0);
				if ($force_campaigns_more == 1)
				{
					$campaign_more_row = $this->m_campaign->getCampaignMoreRowId($id);

					if (!empty($campaign_more_row))
					{
						$response['data'] = false;
					}
					else
					{
						$response['data'] = true;
					}
				}

				$response['msg'] = Text::_('SUCCESS');
			}
			else
			{
				$response['msg']  = Text::_('MISSING_PARAMETERS');
				$response['code'] = 400;
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getparentcampaignsforfilter(): void
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403, 'data' => []];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->_user->id))
		{
			$campaignRepository = new CampaignRepository();
			$campaigns          = $campaignRepository->getParentCampaigns();

			$campaignsObjects = [];
			foreach ($campaigns as $campaign)
			{
				$campaignsObjects[] = [
					'value' => $campaign->getId(),
					'label' => $campaign->getLabel()
				];
			}

			$response['status'] = true;
			$response['msg']    = Text::_('SUCCESS');
			$response['code']   = 200;
			$response['data']   = $campaignsObjects;
		}

		$this->sendJsonResponse($response);
	}
}

?>
