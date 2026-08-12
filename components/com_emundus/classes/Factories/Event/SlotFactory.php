<?php

namespace Tchooz\Factories\Event;

use Tchooz\Entities\Event\SlotEntity;
use Tchooz\Entities\Poll\PollAnswerEntity;
use Tchooz\Enums\Poll\AnswerTypeEnum;
use Tchooz\Factories\AbstractFactory;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\Poll\PollAnswerRepository;
use Tchooz\Repositories\Poll\PollParticipantsRepository;

class SlotFactory extends AbstractFactory
{
	public function buildEntity(object $dbObject, array $relations): SlotEntity
	{
		$slotEntity = new SlotEntity(
			$dbObject->id,
			DateProvider::convertToDateTime($dbObject->start_date),
			DateProvider::convertToDateTime($dbObject->end_date),
			$dbObject->slot_capacity,
			null,
			null,
			$dbObject->more_infos ?? '',
			$dbObject->link ?? '',
			$dbObject->teams_id
		);

		$slotEntity->setLocationText($dbObject->location_text ?? null);

		$answers = [];
		if(!empty($dbObject->answers))
		{
			$pollAnswerRepository = new PollAnswerRepository();
			$participantRepository = new PollParticipantsRepository();
			$answersIds = explode(',', $dbObject->answers);
			foreach($answersIds as $answerId)
			{
				$answer= $pollAnswerRepository->getItemByField('id', (int) $answerId, true);
				if(!empty($answer))
				{
					$participant = $participantRepository->getItemByField('id', (int) $answer->participant, true);
					$answers[] = new PollAnswerEntity(
						$answer->id,
						AnswerTypeEnum::tryFrom($answer->answer) ?? AnswerTypeEnum::NOT_ANSWERED,
						$slotEntity,
						$answer->comment,
						$participant
					);
				}
			}
		}

		$slotEntity->setAnswers($answers);
		
		return $slotEntity;
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		// TODO: Implement getRelationCacheKey() method.
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		// TODO: Implement loadRelation() method.
	}
}