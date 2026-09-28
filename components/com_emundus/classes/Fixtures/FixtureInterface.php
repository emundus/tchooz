<?php

namespace Tchooz\Fixtures;

/**
 * A fixture builds and persists ready-to-use rows for one entity, so a test scenario can be set up
 * without going through the interface that normally creates it.
 */
interface FixtureInterface
{
	/**
	 * The name the CLI addresses this fixture by (tchooz:fixtures --entity=<name>).
	 */
	public static function getEntityName(): string;

	/**
	 * Build one entity, persist it, and return it with its database id set.
	 */
	public function createOne(): object;

	/**
	 * @return array<object>
	 */
	public function createMany(int $nb): array;
}