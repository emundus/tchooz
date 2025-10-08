<?php

namespace Tchooz\Services\Fabrik;

interface TableFromReferenceCreatorInterface
{
	public function supports(string $tableName): bool;

	public function createTableFromReference(string $tableName, array $args = []): string;
}