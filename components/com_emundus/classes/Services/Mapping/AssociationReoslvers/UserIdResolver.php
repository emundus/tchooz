<?php

namespace Tchooz\Services\Mapping\AssociationReoslvers;

use Tchooz\Services\Mapping\AssociationResolverInterface;
use Tchooz\Entities\Automation\ActionTargetEntity;

final class UserIdResolver implements AssociationResolverInterface
{
	public function resolve(ActionTargetEntity $context, mixed $sourceInternalId): int
	{
		return $context->getUserId();
	}
}