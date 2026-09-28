<?php

namespace Tchooz\Synchronizers;

/**
 * Optional transport capability: search remote objects by attribute filters.
 *
 * Only synchronizers whose remote API supports object search implement this. Mapping objects that
 * resolve existence through a remote lookup (e.g. finding a contact by email) require the transport
 * to implement this capability; the executor checks with `instanceof` before use.
 */
interface ObjectSearchInterface
{
	/**
	 * @param   string  $searchedObject      Remote object type to search (e.g. 'contacts').
	 * @param   array   $filters             Attribute => value equality filters.
	 * @param   int     $limit               Maximum number of results.
	 * @param   array   $returnedProperties  Properties to return for each match.
	 *
	 * @return array Matching remote objects.
	 */
	public function searchObjects(string $searchedObject, array $filters, int $limit = 1, array $returnedProperties = []): array;
}
