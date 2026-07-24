<?php

namespace Tchooz\Factories\Poll;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\DateField;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Fields\TextAreaField;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\ColorEnum;
use Tchooz\Factories\AbstractFactory;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\Event\SlotRepository;
use Tchooz\Repositories\Poll\PollParticipantsRepository;
use Tchooz\Services\Field\FieldOptionProvider;

class PollFactory extends AbstractFactory
{
	public function buildEntity(object $dbObject, array $relations): PollEntity
	{
		$color = ColorEnum::tryFrom($dbObject->color);
		if (empty($color))
		{
			$color = ColorEnum::BLUE;
		}

		$status = StatusEnum::tryFrom($dbObject->status);
		if (empty($status))
		{
			$status = StatusEnum::OPEN;
		}
		
		$slotsRepository = new SlotRepository();
		$slotsEntites = [];
		if(!empty($dbObject->slots) && is_string($dbObject->slots))
		{
			$slots = explode(',', $dbObject->slots);
			$slotsEntites = $slotsRepository->getItemsByFields(['id' => $slots], true);

			// Slots are stored in UTC; convert them back to the platform timezone for display.
			$utcTimezone      = new \DateTimeZone('UTC');
			$platformTimezone = new \DateTimeZone(Factory::getApplication()->get('offset', 'Europe/Paris'));
			foreach ($slotsEntites as $slotEntity)
			{
				$slotStart = \DateTime::createFromFormat('Y-m-d H:i:s', $slotEntity->getStart()->format('Y-m-d H:i:s'), $utcTimezone);
				$slotEnd   = \DateTime::createFromFormat('Y-m-d H:i:s', $slotEntity->getEnd()->format('Y-m-d H:i:s'), $utcTimezone);

				$slotStart->setTimezone($platformTimezone);
				$slotEnd->setTimezone($platformTimezone);

				$slotEntity->setStart($slotStart)->setEnd($slotEnd);
			}
		}

		$participantsEntities = [];
		if(!empty($dbObject->participants) && is_string($dbObject->participants))
		{
			$participantsRepository = new PollParticipantsRepository();
			$participants = explode(',', $dbObject->participants);
			foreach ($participants as $participant)
			{
				$participantEntity = $participantsRepository->getItemByField('id', $participant);
				if (!empty($participantEntity))
				{
					$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($participantEntity->user);
					$participantsEntities[] = new PollParticipantsEntity($participantEntity->id, null, $participantEntity->email, $participantEntity->firstname, $participantEntity->lastname, $user);
				}
			}
		}

		$programs = [];
		if (!empty($dbObject->programs) && is_string($dbObject->programs))
		{
			$programs = array_map('intval', array_filter(explode(',', $dbObject->programs)));
		}

		return new PollEntity(
			$dbObject->id,
			$dbObject->name,
			$dbObject->description,
			$color,
			$status,
			DateProvider::convertToDateTime($dbObject->start_date),
			DateProvider::convertToDateTime($dbObject->end_date),
			$participantsEntities,
			$slotsEntites,
			!empty($dbObject->can_edit_answers),
			!empty($dbObject->created_by) ? (int) $dbObject->created_by : null,
			$programs
		);
	}

	public function getFormFields(): array
	{
		$fields = [];

		$generalGroup = new FieldGroup('general', '');

		$statusChoices = [];
		foreach (StatusEnum::cases() as $status)
		{
			$statusChoices[] = new ChoiceFieldValue($status->value, $status->getLabel());
		}

		$optionsProvider = new FieldOptionProvider(
			'poll',
			'getavailableparticipants'
		);

		$programsOptionsProvider = new FieldOptionProvider(
			'poll',
			'getavailableprograms'
		);

		$fields[] = (new StringField('name', Text::_('COM_EMUNDUS_POLL_FIELD_NAME_LABEL'), true, $generalGroup));
		$fields[] = (new TextAreaField('description', Text::_('COM_EMUNDUS_POLL_FIELD_DESCRIPTION_LABEL'), false, $generalGroup))->setRows(4);
		$fields[] = (new DateField('start_date', Text::_('COM_EMUNDUS_POLL_FIELD_START_DATE_LABEL'), false, $generalGroup))->setHelpText(Text::_('COM_EMUNDUS_POLL_FIELD_START_DATE_HELP_TEXT'));
		$fields[] = (new DateField('end_date', Text::_('COM_EMUNDUS_POLL_FIELD_END_DATE_LABEL'), false, $generalGroup))->setHelpText(Text::_('COM_EMUNDUS_POLL_FIELD_END_DATE_HELP_TEXT'));
		$fields[] = (new ChoiceField('participants', Text::_('COM_EMUNDUS_POLL_FIELD_PARTICIPANTS_LABEL'), [], false, true, $generalGroup))->setOptionsProvider($optionsProvider);
		$fields[] = (new ChoiceField('programs', Text::_('COM_EMUNDUS_POLL_FIELD_PROGRAMS_LABEL'), [], false, true, $generalGroup))->setOptionsProvider($programsOptionsProvider)->setHelpText(Text::_('COM_EMUNDUS_POLL_FIELD_PROGRAMS_HELP_TEXT'));
		$fields[] = (new BooleanField('can_edit_answers', Text::_('COM_EMUNDUS_POLL_FIELD_CAN_EDIT_ANSWERS_LABEL'), false, $generalGroup))->setDefaultValue(0)->setHelpText(Text::_('COM_EMUNDUS_POLL_FIELD_CAN_EDIT_ANSWERS_HELP_TEXT'));

		return $fields;
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		// TODO: Implement loadRelation() method.
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		// TODO: Implement getRelationCacheKey() method.
	}
}