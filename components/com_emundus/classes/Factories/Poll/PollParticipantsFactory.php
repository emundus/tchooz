<?php

namespace Tchooz\Factories\Poll;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Factories\AbstractFactory;
use Tchooz\Repositories\Poll\PollRepository;

class PollParticipantsFactory extends AbstractFactory
{
	public function buildEntity(object $dbObject, array $relations): PollParticipantsEntity
	{
		$pollRepository = new PollRepository(false);
		$poll = $pollRepository->getItemByField('id', (int) $dbObject->poll, true);

		return new PollParticipantsEntity(
			id: (int) $dbObject->id,
			poll: $poll,
			email: $dbObject->email,
			firstname: $dbObject->firstname,
			lastname: $dbObject->lastname,
			user: !empty($dbObject->user) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject->user) : null
		);
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