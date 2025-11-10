<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\AutomationEntity;
use Tchooz\Repositories\Automation\AutomationRepository;
use Tchooz\Repositories\Automation\EventsRepository;
use Tchooz\Services\Automation\ActionRegistry;
use Tchooz\Services\Automation\EventDefinitionRegistry;
use Tchooz\Services\Automation\TargetPredefinitionRegistry;

/**
 * eMundus Onboard Automation View
 *
 * @since  0.0.1
 */
class EmundusViewAutomation extends JViewLegacy
{

	public $hash = '';
	public $user = null;
	public int $automationActionId = 0;
	protected AutomationRepository $automationRepository;

	public ?AutomationEntity $automation = null;

	public array $targetPredefinitions = [];

	public array $eventDefinitions = [];

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->user = $app->getIdentity();
		$this->automationActionId = EmundusHelperAccess::getActionIdFromActionName('automation');

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'r', $this->user->id)) {
			$this->automationRepository = new AutomationRepository();
			$jinput = $app->input;
			$layout = $jinput->getString('layout', null);

			$eventRepository = new EventsRepository();
			$this->events = $eventRepository->getEventsList();

			$actionRegistry = new ActionRegistry();
			$this->actions = $actionRegistry->getAvailableActions();

			if ($layout === 'edit')
			{
				if (!EmundusHelperAccess::asAccessAction($this->automationActionId, 'c', $this->user->id)
					&& !EmundusHelperAccess::asAccessAction($this->automationActionId, 'u', $this->user->id)
				) {
					$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
					$app->redirect('index.php?option=com_emundus&view=automation');
				}

				$predefinitionsRegistry = new TargetPredefinitionRegistry();
				$this->targetPredefinitions = $predefinitionsRegistry->getAvailableTargetPredefinitionsSchema();
				$eventDefinitionRegistry = new EventDefinitionRegistry();
				$this->eventDefinitions = $eventDefinitionRegistry->getAvailableEventDefinitionsSchema();
				$automationId = $jinput->getInt('id', 0);
				if ($automationId > 0) {
					$automation = $this->automationRepository->getById($automationId);
					if ($automation) {
						$this->automation = $automation;
					} else {
						$app->enqueueMessage(Text::_('COM_EMUNDUS_AUTOMATION_NOT_FOUND'), 'error');
						$app->redirect('index.php?option=com_emundus&view=automation');
					}
				} else {
					// New automation
					$this->automation = new AutomationEntity(0, Text::_('COM_EMUNDUS_NEW_AUTOMATION'), '');
				}
			}

			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
			$this->hash = EmundusHelperCache::getCurrentGitHash();

			parent::display($tpl);
		} else {
			$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$app->redirect('/');
		}
	}
}
