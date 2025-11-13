<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterSubmitEvaluationDefinition extends EventDefinition
{

	const FORM_KEY = 'form';

	public function __construct()
	{
		parent::__construct(
			'onAfterSubmitEvaluation',
			[
				new ChoiceField(self::FORM_KEY, Text::_('COM_EMUNDUS_AUTOMATION_EVENT_FIELD_FABRIK_FORM'), $this->getEvaluationFormsList(), false, true),
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE, TargetTypeEnum::USER];
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	public function getEvaluationFormsList()
	{
		$options = [];

		if (!class_exists('EmundusModelForm')) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/form.php');
		}
		$m_form = new \EmundusModelForm();
		$data = $m_form->getAllGrilleEval();

		if (!empty($data['datas']))
		{
			foreach ($data['datas'] as $value)
			{
				$options[] = new ChoiceFieldValue($value->id, Text::_($value->label['fr']));
			}
		}

		return $options;
	}
}