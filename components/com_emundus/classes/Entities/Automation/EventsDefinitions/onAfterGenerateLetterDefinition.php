<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Services\Field\FieldOptionProvider;

class onAfterGenerateLetterDefinition extends EventDefinition
{
	public function __construct()
	{
		$parameters = [];

		// todo: replace with a provider to get the letters list instead of querying directly in the event definition
		$parameters[] = new ChoiceField('letter_id', Text::_('COM_EMUNDUS_EVENT_PARAMETER_LETTER'), $this->getLettersList(), false, true);

		$attachmentsParameter = (new ChoiceField('attachment_id', Text::_('COM_EMUNDUS_EVENT_PARAMETER_ATTACHMENT'), [], false, true))
			->setOptionsProvider(new FieldOptionProvider('form', 'getAttachments', [], new AttachmentTypeRepository(), 'get', ['limit' => 0], 'getValue'))
			->provideOptions()
			->setOptionsProvider(null); // Clear the provider after using it to avoid keeping unnecessary references

		$parameters[] = $attachmentsParameter;

		parent::__construct('onAfterGenerateLetter', $parameters);
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE];
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getLettersList(): array
	{
		$choices = [];

		if (!class_exists('EmundusModelEvaluation'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
		}
		$evaluationModel = new \EmundusModelEvaluation();
		$letters = $evaluationModel->getLetters();

		if (!empty($letters))
		{
			foreach ($letters as $letter)
			{
				$choices[] = new ChoiceFieldValue($letter->id, $letter->title);
			}
		}

		return $choices;
	}
}