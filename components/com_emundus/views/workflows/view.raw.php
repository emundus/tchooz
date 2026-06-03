<?php

/**
 * @package     Joomla
 * @subpackage  com_emunudus_onboard
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

/**
 * eMundus Onboard Campaign View
 *
 * @since  0.0.1
 */
class EmundusViewWorkflows extends JViewLegacy
{

	public $user = null;
	private $model = null;

	public int $ccid = 0;

	public string $fnum = '';

	public int $evaluation_row_id = 0;

	public ?object $applicant = null;

	protected ?ApplicationFileEntity $applicationFile = null;

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->user = $app->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
			$this->model = new EmundusModelWorkflow();
			$m_user     = new EmundusModelUsers();
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$jinput = $app->input;
			$layout = $jinput->getString('layout', null);

			if ($layout === 'evaluatorstep') {
				$step_id = $jinput->getInt('step_id', 0);
				$this->fnum = $jinput->getString('fnum', '');

				$applicationFileRepository = new ApplicationFileRepository(false);
				$this->applicationFile = $applicationFileRepository->getByFnum($this->fnum);

				if (!empty($this->applicationFile))
				{
					$this->ccid = $this->applicationFile->getId();
					$this->fnum = $this->applicationFile->getFnum();
					$this->applicant  = $m_user->getUserById($this->applicationFile->getUser()->id)[0];
					if (!$this->applicationFile->isAnonymous() && !$this->applicant->is_anonym && !isset($this->applicant->profile_picture) || empty($this->applicant->profile_picture)) {
						$this->applicant->profile_picture = $m_user->getIdentityPhoto($this->fnum, $this->applicationFile->getUser()->id);
					}

					if (!empty($step_id)) {
						$this->step = $this->model->getStepData($step_id, $this->applicationFile->getCampaignId());

						try {
							$this->access = EmundusHelperAccess::getUserEvaluationStepAccess($this->ccid, $this->step, $this->user->id);
							$evaluation_rows = $this->model->getStepEvaluationsForFile((int)$this->step->id, $this->ccid);
							if (!empty($evaluation_rows)) {
								if ($this->step->multiple) {
									foreach ($evaluation_rows as $evalaution_row) {
										if ($evalaution_row['evaluator'] == $this->user->id) {
											$this->evaluation_row_id = $evalaution_row['id'];
										}
									}
								} else {
									$this->evaluation_row_id = $evaluation_rows[0]['id'];
								}
							}
						} catch (Exception $e) {
							$app->enqueueMessage($e->getMessage(), 'error');
						}
					} else {
						$this->step = null;
					}
				}
			}

			parent::display($tpl);
		} else {
			$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$app->redirect('/connexion');
		}
	}
}
