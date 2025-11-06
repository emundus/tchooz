<?php

namespace Tchooz\Entities\Automation\TargetPredefinitions;

use EmundusHelperFiles;
use Joomla\CMS\Factory;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitionEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ApplicantOtherFilesPredefinition extends TargetPredefinitionEntity
{
	public function __construct()
	{
		parent::__construct(
			'applicant_other_files',
			'COM_EMUNDUS_AUTOMATION_TARGET_PREDEFINITION_APPLICANT_OTHER_FILES',
			TargetTypeEnum::FILE,
			[TargetTypeEnum::FILE]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function resolve(ActionTargetEntity $context): array
	{
		$targets = [];

		if (!class_exists('EmundusHelperFiles'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';
		}
		$applicantId = !empty($context->getUserId()) ? $context->getUserId() : EmundusHelperFiles::getApplicantIdFromFileId($context->getFile(), 'fnum');

		if (!empty($applicantId))
		{
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery()
				->select('fnum')
				->from($db->quoteName('#__emundus_campaign_candidature'))
				->where($db->quoteName('applicant_id') . ' = ' . $db->quote($applicantId))
				->where($db->quoteName('published') . ' = 1');

			// Exclude the current file if provided in the context
			// if not provided, all files of the applicant will be returned
			if (!empty($context->getFile()))
			{
				$query->where($db->quoteName('fnum') . ' != ' . $db->quote($context->getFile()));
			}

			$db->setQuery($query);
			$files = $db->loadColumn();

			foreach ($files as $file)
			{
				$targets[] = new ActionTargetEntity($context->getTriggeredBy(), $file, $applicantId, $context->getParameters(), $context->getCustom(), $context);
			}
		}

		return $targets;
	}
}