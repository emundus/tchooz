<?php

namespace Tchooz\Services\Mapping;

use Tchooz\Entities\Automation\ActionTargetEntity;

interface AssociationResolverInterface
{
	public function resolve(
		ActionTargetEntity $context,
		mixed $sourceInternalId
	): mixed;
}