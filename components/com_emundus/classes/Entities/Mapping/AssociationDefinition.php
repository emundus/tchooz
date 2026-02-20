<?php

namespace Tchooz\Entities\Mapping;

use Tchooz\Services\Mapping\AssociationResolverInterface;

final class AssociationDefinition
{
	public function __construct(
		public string $targetObject,
		public AssociationResolverInterface $resolver
	) {}
}