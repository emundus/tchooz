<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      Benjamin Rivalland
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Programs\ProgramRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Services\UploadService;

class EmundusControllerProgramme extends EmundusController
{
	private EmundusModelProgramme $m_programme;

	private ProgramRepository $programRepository;

	private ActionEntity $programAction;

	function __construct($config = array())
	{
		parent::__construct($config);

		if (!class_exists('EmundusModelProgramme'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/programme.php';
		}
		$this->m_programme = new EmundusModelProgramme();

		$this->programRepository = new ProgramRepository();

		$actionRepository    = new ActionRepository();
		$this->programAction = $actionRepository->getByName('program');
	}

	function display($cachable = false, $urlparams = false): void
	{
		// Set a default view if none exists
		if (!$this->input->get('view'))
		{
			$default = 'programme';
			$this->input->set('view', $default);
		}

		parent::display();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => ActionEnum::PROGRAM, 'mode' => CrudEnum::READ]])]
	public function getprogrammes(): EmundusResponse
	{
		$programmes = $this->m_programme->getProgrammes();

		return EmundusResponse::ok($programmes);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => ActionEnum::PROGRAM, 'mode' => CrudEnum::CREATE]])]
	public function addprogrammes(): EmundusResponse
	{
		$data = $this->input->get('data', null, 'POST', 'none', 0);

		$result = $this->m_programme->addProgrammes($data);

		if (!$result)
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_ADD_PROGRAMMES'));
		}

		return EmundusResponse::ok($result);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => ActionEnum::PROGRAM, 'mode' => CrudEnum::UPDATE]])]
	public function editprogrammes(): EmundusResponse
	{
		$data = $this->input->get('data', null, 'POST', 'none', 0);

		$result = $this->m_programme->editProgrammes($data);
		if (!$result)
		{
			throw new \RuntimeException(Text::_('ERROR_CANNOT_EDIT_PROGRAMMES'));
		}

		return EmundusResponse::ok($result);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => ActionEnum::PROGRAM, 'mode' => CrudEnum::READ]])]
	public function getallprogramforfilter(): EmundusResponse
	{
		$programRepository     = new ProgramRepository();
		$emundusUserRepository = new EmundusUserRepository();

		$userPrograms = $emundusUserRepository->getUserProgramsCodes($this->user->id);
		$programs     = $programRepository->get(
			filters: [
				'code' => $userPrograms
			],
			order: 'label'
		);

		$values = [];
		$type   = $this->input->getString('type', '');

		foreach ($programs as $program)
		{
			assert($program instanceof ProgramEntity);

			$values[] = [
				'label' => $program->getLabel(),
				'value' => $type === 'id' ? $program->getId() : $program->getCode()
			];
		}

		return EmundusResponse::ok($values, Text::_('PROGRAMS_FILTER_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getallprogram(): EmundusResponse
	{
		$filter    = $this->input->getString('filter', null);
		$sort      = $this->input->getString('sort', 'DESC');
		$recherche = $this->input->getString('recherche', '');
		$category  = $this->input->getString('category', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);
		$order_by  = $this->input->getString('order_by', 'p.id');
		$order_by  = $order_by == 'label' ? 'p.label' : $order_by;

		$actionRepository   = new ActionRepository();
		$campaignAction     = $actionRepository->getByName('campaign');
		$campaignAccess     = EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::READ->value, $this->user->id);
		$campaignEditAccess = EmundusHelperAccess::asAccessAction($campaignAction->getId(), CrudEnum::UPDATE->value, $this->user->id);

		$programs = $this->m_programme->getAllPrograms($lim, $page, $filter, $sort, $recherche, $this->user, $category, $order_by);

		foreach ($programs['datas'] as $key => $program)
		{
			$programs['datas'][$key]->label = ['fr' => Text::_($program->label), 'en' => Text::_($program->label)];

			if ($campaignAccess)
			{
				if (!empty($program->nb_campaigns))
				{
					$campaigns                          = $this->m_programme->getAssociatedCampaigns($program->code);
					$programs['datas'][$key]->campaigns = $campaigns;

					if (!empty($campaigns))
					{
						$translation = 'COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED';
						$title       = 'COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE';
						if ($program->nb_campaigns < 2)
						{
							$title       = 'COM_EMUNDUS_ONBOARD_CAMPAIGN_ASSOCIATED_TITLE';
							$translation = 'COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_SINGLE';
						}

						$tags       = '<div>';
						$short_tags = $tags;
						$tags       .= '<h2 class="tw-mb-2">' . Text::_($title) . '</h2>';
						$tags       .= '<div class="tw-flex tw-flex-wrap">';
						foreach ($campaigns as $campaign)
						{
							$tags .= $campaignEditAccess ? '<a href="' . EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=' . $campaign->id) . '" class="tw-cursor-pointer tw-mr-2 tw-mb-2 tw-h-max tw-px-3 tw-py-1 tw-bg-main-100 tw-text-neutral-900 tw-text-sm tw-rounded-coordinator em-campaign-tag"> ' . $campaign->label . ' (' . $campaign->year . ')</a>' : '<span class="tw-mr-2 tw-mb-2 tw-h-max tw-px-3 tw-py-1 tw-bg-neutral-100 tw-text-neutral-900 tw-text-sm tw-rounded-coordinator em-campaign-tag"> ' . $campaign->label . ' (' . $campaign->year . ')</span>';
						}
						$tags .= '</div>';

						$short_tags .= '<span class="tw-cursor-pointer tw-font-semibold tw-text-profile-full tw-flex tw-items-center tw-text-sm hover:!tw-underline">' . count($campaigns) . Text::_($translation) . '</span>';
						$short_tags .= '</div>';
						$tags       .= '</div>';
					}
					else
					{
						$short_tags = Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_NOT');
					}
				}
				else
				{
					$short_tags = Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_NOT');
				}

				$campaigns_assiocated_column = [
					'key'     => Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE'),
					'value'   => $short_tags,
					'classes' => '',
					'display' => 'all'
				];

				if (isset($tags))
				{
					$campaigns_assiocated_column['long_value'] = $tags;
				}
			}

			$programs['datas'][$key]->additional_columns = [
				[
					'key'      => Text::_('COM_EMUNDUS_ONBOARD_PROGCODE'),
					'value'    => $program->code,
					'classes'  => 'em-font-size-14 em-neutral-700-color',
					'display'  => 'all',
					'order_by' => 'p.code'
				],
				[
					'key'      => Text::_('COM_EMUNDUS_ONBOARD_CATEGORY'),
					'value'    => Text::_($program->programmes),
					'classes'  => 'em-font-size-14 em-neutral-700-color',
					'display'  => 'all',
					'order_by' => 'p.programmes'
				],
				[
					'key'      => Text::_('COM_EMUNDUS_ONBOARD_STATE'),
					'value'    => $program->published ? Text::_('PUBLISHED') : Text::_('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH'),
					'classes'  => 'tw-w-fit tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm ' . ($program->published ? ' em-bg-main-500 tw-text-white' : ' tw-bg-neutral-300 tw-text-neutral-700'),
					'display'  => 'table',
					'order_by' => 'p.published'
				],
				[
					'key'      => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM_APPLY_ONLINE'),
					'value'    => $program->apply_online ? Text::_('JYES') : Text::_('JNO'),
					'classes'  => '',
					'display'  => 'table',
					'order_by' => 'p.apply_online'
				],
				[
					'type'    => 'tags',
					'key'     => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM_TAGS'),
					'values'  => [
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_STATE'),
							'value'   => $program->published ? Text::_('PUBLISHED') : Text::_('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH'),
							'classes' => 'tw-w-fit tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm ' . ($program->published ? ' em-bg-main-500 tw-text-white' : ' tw-bg-neutral-300 tw-text-neutral-700'),
						],
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM_APPLY_ONLINE'),
							'value'   => $program->apply_online ? Text::_('COM_EMUNDUS_ONBOARD_PROGRAM_APPLY_ONLINE') : '',
							'classes' => $program->apply_online ? 'tw-w-fit tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-bg-neutral-300 tw-text-neutral-700' : 'hidden',
						]
					],
					'display' => 'blocs',
					'classes' => 'em-mt-8 em-mb-8'
				]
			];

			if ($campaignAccess)
			{
				$programs['datas'][$key]->additional_columns[] = $campaigns_assiocated_column;
			}
		}

		return EmundusResponse::ok($programs, Text::_('PROGRAMS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::PROGRAM, 'mode' => CrudEnum::CREATE],
		['id' => ActionEnum::PROGRAM, 'mode' => CrudEnum::UPDATE],
	])]
	public function saveprogram(): EmundusResponse
	{
		$this->checkToken();
		$data = $this->input->getRaw('body');
		$id = $this->input->getInt('id', 0);

		$logoPath   = '';
		$logo       = null;
		$handleLogo = false;
		if (empty($data))
		{
			$label       = $this->input->getString('label');
			$code        = $this->input->getString('code');
			$programmes  = $this->input->getString('programmes');
			$description = $this->input->getRaw('notes');
			$description = (is_null($description) || $description === 'null') ? '' : $description;
			$synthesis   = $this->input->getRaw('synthesis');
			$logo        = $this->input->files->get('logo');
			$logoPath    = $this->input->getString('logo');
			$handleLogo  = true;
		}
		else
		{
			// Backward compatibility: legacy JSON callers do not manage the logo
			$data        = json_decode($data, true);
			$label       = $data['label'];
			$code        = $data['code'];
			$programmes  = $data['programmes'] ?? '';
			$description = $data['notes'] ?? '';
			$synthesis   = $data['synthesis'] ?? '';
		}

		if (empty($label) || empty($code))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_PROGRAM_FORM_MISSING_REQUIRED_FIELDS'));
		}

		if ($handleLogo && (!empty($logo) && $logo['error'] === 0 || $logoPath === 'null' || empty($logoPath)))
		{
			// Delete old logo if exists
			if ($id > 0)
			{
				$this->programRepository->deleteLogo($id);
			}

			$upload_dir = 'images/emundus/programs/';
			$uploader   = new UploadService($upload_dir);

			if ((!empty($logo) && $logo['error'] === 0))
			{
				$length   = rand(5, 10);
				$random   = bin2hex(random_bytes($length));
				$random   = substr($random, 0, $length);
				$logoPath = $uploader->upload($logo, $label . '_' . $random, 'program');
			}
			else {
				$logoPath = null;
			}
		}

		// Remove Uri::base from logoPath
		if (!empty($logoPath))
		{
			$logoPath = str_replace(Uri::base(), '', $logoPath);
		}

		if ($id > 0)
		{
			// Update: load the existing program so we don't clobber the fields the form does not send (published, apply_online, ordering, color)
			$programEntity = $this->programRepository->getById($id);
			if (empty($programEntity))
			{
				throw new RuntimeException(Text::_('ERROR_CANNOT_FIND_PROGRAM'));
			}

			$programEntity->setCode($code);
			$programEntity->setLabel($label);
			$programEntity->setNotes($description ?? '');
			$programEntity->setProgrammes($programmes ?? '');
			$programEntity->setSynthesis($synthesis ?? '');

			if ($handleLogo)
			{
				$programEntity->setLogo($logoPath);
			}
		}
		else
		{
			$programEntity = new ProgramEntity(
				code: $code,
				label: $label,
				notes: $description,
				programmes: $programmes,
				synthesis: $synthesis,
				applyOnline: true,
				logo: $logoPath
			);
		}

		if (!$this->programRepository->flush($programEntity))
		{
			throw new RuntimeException(Text::_('COM_EMUNDUS_PROGRAM_FORM_ERROR'));
		}

		$successMessage = !empty($id) ? Text::_('COM_EMUNDUS_PROGRAM_FORM_SUCCESS_SAVED') : Text::_('COM_EMUNDUS_PROGRAM_FORM_SUCCESS_ADD');

		return EmundusResponse::ok([
			'programme_id'   => $programEntity->getId(),
			'programme_code' => $programEntity->getCode(),
		], $successMessage);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => ActionEnum::PROGRAM, 'mode' => CrudEnum::UPDATE]])]
	public function updateprogram(): EmundusResponse
	{
		$data = $this->input->getRaw('body');
		$id   = $this->input->getString('id');
		if (empty($id) || empty($data))
		{
			throw new InvalidArgumentException(Text::_("MISSING_PARAMS"));
		}

		$result = $this->m_programme->updateProgram($id, $data);
		if (!$result)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_EDIT_PROGRAMS'));
		}

		return EmundusResponse::ok($result, Text::_('PROGRAMMES_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => ActionEnum::PROGRAM, 'mode' => CrudEnum::DELETE]])]
	public function deleteprogram(): EmundusResponse
	{
		$data = $this->input->getInt('id');
		if (empty($data))
		{
			throw new InvalidArgumentException(Text::_("MISSING_PARAMS"));
		}

		$result = $this->m_programme->deleteProgram($data);
		if (!$result)
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_DELETE_PROGRAMS'));
		}

		return EmundusResponse::ok($result, Text::_('PROGRAMMES_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getprogramcategories(): EmundusResponse
	{
		$programRepository = new ProgramRepository();
		$categories        = $programRepository->getCategories();

		$values = [];
		foreach ($categories as $category)
		{
			$values[] = [
				'label' => Text::_($category),
				'value' => $category
			];
		}

		return EmundusResponse::ok($values, Text::_('PROGRAM_CATEGORIES_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'campaign', 'mode' => CrudEnum::READ]])]
	public function getcampaignsbyprogram(): EmundusResponse
	{
		$program = $this->input->getInt('pid');
		if (empty($program))
		{
			throw new InvalidArgumentException(Text::_("MISSING_PARAMS"));
		}

		$campaigns = $this->m_programme->getCampaignsByProgram($program);

		return EmundusResponse::ok($campaigns);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => ActionEnum::PROGRAM, 'mode' => CrudEnum::READ]])]
	public function getprogram(): EmundusResponse
	{
		$id = $this->input->getInt('id');
		if (empty($id))
		{
			throw new InvalidArgumentException(Text::_("MISSING_PARAMS"));
		}

		$program = $this->programRepository->getItemByField('id', $id, true);
		if (empty($program))
		{
			throw new RuntimeException(Text::_('ERROR_CANNOT_FIND_PROGRAM'));
		}

		return EmundusResponse::ok($program->__serialize());
	}
}
