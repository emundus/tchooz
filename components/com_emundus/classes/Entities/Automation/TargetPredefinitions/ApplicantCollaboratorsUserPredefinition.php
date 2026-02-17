<?php

namespace Tchooz\Entities\Automation\TargetPredefinitions;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitionEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ApplicantCollaboratorsUserPredefinition extends TargetPredefinitionEntity
{

	public function __construct()
	{
		parent::__construct(
			'collaborators_user',
			Text::_('COM_EMUNDUS_AUTOMATION_TARGET_PREDEFINITION_APPLICANT_COLLABORATORS_USER'),
			TargetTypeEnum::USER,
			[TargetTypeEnum::FILE]
		);
	}

	public function resolve(ActionTargetEntity $context): array
	{
		$targets = [];

		if (!empty($context->getFile()))
		{
			if (!class_exists('EmundusModelApplication'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
			}
			$applicationModel = new \EmundusModelApplication();
			$collaborators = $applicationModel->getSharedFileUsers(null, $context->getFile());

			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			foreach ($collaborators as $collaborator)
			{
				if (!empty($collaborator->email))
				{
					$query->clear()
						->select($db->quoteName('id'))
						->from($db->quoteName('#__users'))
						->where($db->quoteName('email') . ' = ' . $db->quote($collaborator->email));
					$db->setQuery($query);
					$userId = $db->loadResult();

					if (!empty($userId))
					{
						$targets[] = new ActionTargetEntity($context->getTriggeredBy(), null, $userId, $context->getParameters(), $context->getCustom(), $context);
					}
				}
			}
		}

		return $targets;
	}
}