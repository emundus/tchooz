<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;


use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Addons\AddonValue;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;

class Release2_11_3Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			// verify that step types have action ids associated to them
			$stepTypeRepository = new StepTypeRepository();
			$applicantStep = $stepTypeRepository->getStepTypeById(1);
			$evaluatorStep = $stepTypeRepository->getStepTypeById(2);

			if ($applicantStep)
			{
				$applicantStep->setActionId(1);
				$applicantStep->setCode('applicant');
				$this->tasks[] = $stepTypeRepository->flush($applicantStep);
			}

			if ($evaluatorStep)
			{
				$evaluatorStep->setActionId(5);
				$evaluatorStep->setCode('evaluator');
				$this->tasks[] = $stepTypeRepository->flush($evaluatorStep);
			}

			// Add messenger in setup_config
			$addonRepository = new AddonRepository();
			$messenger_addon = $addonRepository->getByName('messenger');
			if (!$messenger_addon)
			{
				$messenger_addon = new AddonEntity('messenger', new AddonValue(false, true, []));
				$this->tasks[] = $addonRepository->flush($messenger_addon);
			}

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}