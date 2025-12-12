<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ActionGenerateLetter extends ActionEntity
{
	public CONST LETTER_PARAMETER = 'letter';

	private array $letterChoices = [];

	public static function getIcon(): ?string
	{
		return 'description';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::FILE;
	}

	public static function isAsynchronous(): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public static function getType(): string
	{
		return 'generate_letter';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_GENERATE_LETTER_LABEL');
	}

	/**
	 * @inheritDoc
	 */
	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::FILE];
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$executed = ActionExecutionStatusEnum::FAILED;

		$this->verifyRequiredParameters();

		if (!empty($context->getFile()))
		{
			$letterIds = $this->getParameterValue(self::LETTER_PARAMETER);
			if (!is_array($letterIds))
			{
				$letterIds = [$letterIds];
			}

			try {
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->createQuery();
				$query->select('*')
					->from($db->quoteName('#__emundus_setup_letters'))
					->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $letterIds)) . ')');
				$db->setQuery($query);
				$letters = $db->loadObjectList();

				if (!empty($letters))
				{
					if (!class_exists('EmundusModelEvaluation'))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
					}
					$evaluationModel = new \EmundusModelEvaluation();
					$generatedLetters = $evaluationModel->generateFileLetters($context->getFile(), $letters, $context->getTriggeredBy(), true, 1);

					if (!empty($generatedLetters))
					{
						$this->setResult($generatedLetters);
						$executed = ActionExecutionStatusEnum::COMPLETED;
					}
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error generating letter in ActionGenerateLetter: ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');
				$executed = ActionExecutionStatusEnum::FAILED;
			}
		}

		return $executed;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new ChoiceField(self::LETTER_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_GENERATE_LETTER_PARAMETER_LETTER_LABEL'), $this->getLetterChoices(), true, true)
			];
		}

		return $this->parameters;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getLetterChoices(): array
	{
		$options = [];

		if (!empty($this->letterChoices))
		{
			return $this->letterChoices;
		}
		else
		{
			// reset any existing filter params in the session
			$session = Factory::getApplication()->getSession();
			$session->set('filt_params', []);

			if (!class_exists('EmundusModelMessages'))
			{
				require_once (JPATH_ROOT . '/components/com_emundus/models/messages.php');
			}
			$m_messages = new \EmundusModelMessages();
			$letters = $m_messages->getLetters();

			foreach ($letters as $letter)
			{
				$options[] = new ChoiceFieldValue($letter->id, $letter->title);
			}
		}

		return $options;
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}
}