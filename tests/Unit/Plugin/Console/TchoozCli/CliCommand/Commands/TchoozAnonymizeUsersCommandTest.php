<?php

namespace Unit\Plugin\Console\TchoozCli\CliCommand\Commands;

use Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;

/**
 * @package     Unit\Plugin\Console\TchoozCli\CliCommand\Commands
 *
 * @since       version 1.0.0
 * @covers      \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand
 *
 * Pure unit tests: only the deterministic SQL-building helpers are exercised,
 * with a mocked database so no row is ever touched. The two global UPDATE
 * methods are intentionally not run here (they mutate the whole users table and
 * would corrupt the shared test dataset) — they are thin glue over these
 * helpers, the Joomla query builder and UserHelper::hashPassword.
 */
class TchoozAnonymizeUsersCommandTest extends TestCase
{
	private TchoozAnonymizeUsersCommand $command;

	protected function setUp(): void
	{
		parent::setUp();

		// Mock the database so quote()/quoteName() are predictable and no query runs.
		$db = $this->createMock(DatabaseInterface::class);
		$db->method('quote')->willReturnCallback(
			static fn($text): string => "'" . $text . "'"
		);
		$db->method('quoteName')->willReturnCallback(
			static fn($name): string => '`' . $name . '`'
		);

		$this->command = new TchoozAnonymizeUsersCommand($db);
	}

	/**
	 * Invoke a private method of the command under test.
	 *
	 * @param   string  $method  Method name.
	 * @param   array   $args    Positional arguments.
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	private function invokePrivate(string $method, array $args)
	{
		$ref = new \ReflectionMethod($this->command, $method);
		$ref->setAccessible(true);

		return $ref->invokeArgs($this->command, $args);
	}

	// -------------------------------------------------------------------------
	// candidatesOnlyClause — restrict updates to applicant users
	// -------------------------------------------------------------------------

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::candidatesOnlyClause
	 * @return void
	 */
	public function testCandidatesOnlyClauseKeepsApplicantsAndDropsManagerHolders(): void
	{
		$clause = $this->invokePrivate('candidatesOnlyClause', ['id']);

		$this->assertStringContainsString('`id` IN (', $clause, 'The clause must include users referenced as applicants (positive filter).');
		$this->assertStringContainsString('`id` NOT IN (', $clause, 'The clause must exclude any user who ALSO holds a manager profile.');
		$this->assertStringContainsString(
			'`published` = 1',
			$clause,
			'Applicant profiles are identified by emundus_setup_profiles.published = 1.'
		);
		$this->assertStringContainsString(
			'`published` = 0',
			$clause,
			'Manager profiles are identified by emundus_setup_profiles.published = 0.'
		);
	}

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::candidatesOnlyClause
	 * @return void
	 */
	public function testCandidatesOnlyClauseFiltersOnTheProvidedColumn(): void
	{
		$clause = $this->invokePrivate('candidatesOnlyClause', ['user_id']);

		$this->assertStringContainsString(
			'`user_id` IN (',
			$clause,
			'The #__emundus_users table is filtered on its user_id column, not id.'
		);
		$this->assertStringContainsString(
			'`user_id` NOT IN (',
			$clause,
			'The exclusion of manager-profile holders must also target user_id.'
		);
	}

	// -------------------------------------------------------------------------
	// userIdsForProfilePublishedSubquery — covers both profile storages
	// -------------------------------------------------------------------------

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::userIdsForProfilePublishedSubquery
	 * @return void
	 */
	public function testUserIdsSubqueryUnionsSingleColumnAndManyToManyStorages(): void
	{
		$subquery = $this->invokePrivate('userIdsForProfilePublishedSubquery', [1]);

		$this->assertStringContainsString(
			'#__emundus_users',
			$subquery,
			'The single-profile column on #__emundus_users must be one source.'
		);
		$this->assertStringContainsString(
			'#__emundus_users_profiles',
			$subquery,
			'The many-to-many #__emundus_users_profiles table must be the other source.'
		);
		$this->assertStringContainsString(
			' UNION ',
			$subquery,
			'The two sources must be UNION-ed so a user matches if either association exists.'
		);
	}

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::userIdsForProfilePublishedSubquery
	 * @return void
	 */
	public function testUserIdsSubqueryPropagatesPublishedFlagToProfileLookup(): void
	{
		$forApplicants = $this->invokePrivate('userIdsForProfilePublishedSubquery', [1]);
		$forManagers   = $this->invokePrivate('userIdsForProfilePublishedSubquery', [0]);

		$this->assertStringContainsString(
			'`published` = 1',
			$forApplicants,
			'Requesting applicants (published=1) must inject that value in the profile lookup.'
		);
		$this->assertStringContainsString(
			'`published` = 0',
			$forManagers,
			'Requesting managers (published=0) must inject that value in the profile lookup.'
		);
	}

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::candidatesOnlyClause
	 * @return void
	 */
	public function testCandidatesOnlyClauseWrapsSubqueriesInDerivedTablesForMysqlSelfReference(): void
	{
		// MySQL rejects an UPDATE whose WHERE sub-query reads from the same
		// table being updated, unless that sub-query is wrapped in a derived
		// table. Regression guard: both sub-queries must be wrapped, with
		// distinct aliases so the two derived tables can coexist in one WHERE.
		$clause = $this->invokePrivate('candidatesOnlyClause', ['user_id']);

		$this->assertStringContainsString(
			'`applicants_wrap`',
			$clause,
			'The applicants sub-query must be wrapped in a derived table to avoid MySQL "You can\'t specify target table" errors on the UPDATE #__emundus_users step.'
		);
		$this->assertStringContainsString(
			'`managers_wrap`',
			$clause,
			'The managers sub-query must also be wrapped, with a distinct alias so both derived tables can coexist.'
		);
	}

	// -------------------------------------------------------------------------
	// History tables — coverage and FK-safe ordering
	// -------------------------------------------------------------------------

	/**
	 * Reads the private HISTORY_TABLES constant on the command under test.
	 *
	 * @return string[]
	 * @throws \ReflectionException
	 */
	private function historyTables(): array
	{
		$ref = new \ReflectionClass($this->command);

		return $ref->getConstant('HISTORY_TABLES');
	}

	/**
	 * @return void
	 */
	public function testHistoryTablesCoverEveryPiiLeakingHistoryCategory(): void
	{
		$tables = $this->historyTables();

		$this->assertContains('#__messages', $tables, 'Sent emails history (Joomla #__messages) must be wiped.');
		$this->assertContains('#__emundus_chatroom', $tables, 'Internal messaging (#__emundus_chatroom) must be wiped.');
		$this->assertContains('#__emundus_chatroom_notifications', $tables, 'Chat notifications (#__emundus_chatroom_notifications) must be wiped.');
		$this->assertContains('#__emundus_comments', $tables, 'File comments (#__emundus_comments) must be wiped.');
		$this->assertContains('#__emundus_sms_queue', $tables, 'SMS queue (#__emundus_sms_queue) must be wiped: it holds recipient numbers and rendered content.');
		$this->assertNotContains('#__emundus_setup_sms', $tables, 'SMS templates (#__emundus_setup_sms) are configuration, not PII history, and must be preserved.');
	}

	/**
	 * @return void
	 */
	public function testHistoryTablesDeleteChatNotificationsBeforeChatroom(): void
	{
		$tables = $this->historyTables();

		$notificationsPos = array_search('#__emundus_chatroom_notifications', $tables, true);
		$chatroomPos      = array_search('#__emundus_chatroom', $tables, true);

		$this->assertIsInt($notificationsPos, 'Chat notifications table must be part of the wipe list.');
		$this->assertIsInt($chatroomPos, 'Chatroom table must be part of the wipe list.');
		$this->assertLessThan(
			$chatroomPos,
			$notificationsPos,
			'chatroom_notifications depends on chatroom via chatroom_id: it must be deleted first to avoid FK issues.'
		);
	}

	// -------------------------------------------------------------------------
	// Fabrik sensitive plugins — coverage and target-table resolution
	// -------------------------------------------------------------------------

	/**
	 * Reads the private FABRIK_SENSITIVE_PLUGINS constant on the command.
	 *
	 * @return string[]
	 * @throws \ReflectionException
	 */
	private function fabrikSensitivePlugins(): array
	{
		$ref = new \ReflectionClass($this->command);

		return $ref->getConstant('FABRIK_SENSITIVE_PLUGINS');
	}

	/**
	 * @return void
	 */
	public function testFabrikSensitivePluginsCoverPhoneAndIban(): void
	{
		$plugins = $this->fabrikSensitivePlugins();

		$this->assertContains(
			ElementPluginEnum::PHONENUMBER,
			$plugins,
			'The project-specific phone plugin (ElementPluginEnum::PHONENUMBER) must be listed so every phone answer is emptied.'
		);
		$this->assertContains(
			ElementPluginEnum::IBAN,
			$plugins,
			'The IBAN plugin (ElementPluginEnum::IBAN) must be listed so every IBAN answer is emptied.'
		);
	}

	/**
	 * Builds a FabrikElementEntity mock with the two getters resolveFabrikTargetTable relies on.
	 *
	 * @param   string  $joinTable
	 * @param   string  $listTable
	 *
	 * @return FabrikElementEntity
	 */
	private function fabrikElementMock(string $joinTable, string $listTable): FabrikElementEntity
	{
		$element = $this->createMock(FabrikElementEntity::class);
		$element->method('getTableJoin')->willReturn($joinTable);
		$element->method('getDbTableName')->willReturn($listTable);

		return $element;
	}

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::resolveFabrikTargetTable
	 * @return void
	 */
	public function testResolveFabrikTargetTablePicksJoinTableForRepeatGroups(): void
	{
		$element = $this->fabrikElementMock('jos_emundus_form_alpha_repeat_42', 'jos_emundus_form_alpha');

		$this->assertSame(
			'jos_emundus_form_alpha_repeat_42',
			$this->invokePrivate('resolveFabrikTargetTable', [$element]),
			'For a repeat group (getTableJoin() set), the actual storage is the join table, not the main list db_table_name.'
		);
	}

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::resolveFabrikTargetTable
	 * @return void
	 */
	public function testResolveFabrikTargetTableFallsBackToListTableForNonRepeatGroups(): void
	{
		$element = $this->fabrikElementMock('', 'jos_emundus_form_beta');

		$this->assertSame(
			'jos_emundus_form_beta',
			$this->invokePrivate('resolveFabrikTargetTable', [$element]),
			'For a non-repeat group (empty getTableJoin()) the storage is the list db_table_name.'
		);
	}

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::resolveFabrikTargetTable
	 * @return void
	 */
	public function testResolveFabrikTargetTableReturnsNullWhenNothingIsResolvable(): void
	{
		$element = $this->fabrikElementMock('', '');

		$this->assertNull(
			$this->invokePrivate('resolveFabrikTargetTable', [$element]),
			'A malformed Fabrik element (neither join nor list table) must be skipped rather than producing an empty-quoted UPDATE.'
		);
	}

	// -------------------------------------------------------------------------
	// displayDangerBlock — red block rendered in interactive mode
	// -------------------------------------------------------------------------

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::displayDangerBlock
	 * @return void
	 */
	public function testDisplayDangerBlockRendersAsRedBlockWithTheDangerType(): void
	{
		// Captures how the danger block is drawn so a refactor cannot silently
		// downgrade the visual weight (yellow warning, plain writeln) that the
		// operator needs to see BEFORE they hit Enter on the confirmation prompt.
		$capturedType    = null;
		$capturedStyle   = null;
		$capturedMessages = null;

		$style = $this->createMock(SymfonyStyle::class);
		$style->expects($this->once())
			->method('block')
			->willReturnCallback(function ($messages, $type, $blockStyle) use (&$capturedType, &$capturedStyle, &$capturedMessages) {
				$capturedMessages = $messages;
				$capturedType     = $type;
				$capturedStyle    = $blockStyle;
			});

		$command = new TchoozAnonymizeUsersCommand($this->createMock(DatabaseInterface::class));
		$styleProp = new \ReflectionProperty($command, 'ioStyle');
		$styleProp->setAccessible(true);
		$styleProp->setValue($command, $style);

		$ref = new \ReflectionMethod($command, 'displayDangerBlock');
		$ref->setAccessible(true);
		$ref->invoke($command);

		$this->assertSame('DANGER', $capturedType, 'The block prefix must read "DANGER" so an operator scanning the terminal cannot miss it.');
		$this->assertSame('fg=white;bg=red', $capturedStyle, 'The block must be rendered with a white-on-red style to be unmistakable.');
		$this->assertIsArray($capturedMessages, 'The block body must be an array of lines so Symfony can render a multi-line block with a border.');
	}

	// -------------------------------------------------------------------------
	// enforcePasswordResetForAllUsers — platform-wide, no candidate filter
	// -------------------------------------------------------------------------

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::enforcePasswordResetForAllUsers
	 * @return void
	 */
	public function testEnforcePasswordResetEmptiesPasswordAndSetsRequireResetWithoutCandidateFilter(): void
	{
		// Password reset is a security control, not an anonymisation step: it
		// must reach every #__users row (managers, super users, applicants).
		// The password column is emptied rather than filled with a shared random
		// hash so there is no secret to brute-force in the pre-prod database.
		$capturedSetFragments = [];
		$capturedWhereCalled  = false;

		$queryMock = $this->createMock(QueryInterface::class);
		$queryMock->method('update')->willReturnSelf();
		$queryMock->method('set')->willReturnCallback(function ($fragment) use (&$capturedSetFragments, $queryMock) {
			$capturedSetFragments[] = (string) $fragment;

			return $queryMock;
		});
		$queryMock->method('where')->willReturnCallback(function () use (&$capturedWhereCalled, $queryMock) {
			$capturedWhereCalled = true;

			return $queryMock;
		});

		$db = $this->createMock(DatabaseDriver::class);
		$db->method('quote')->willReturnCallback(static fn($v): string => "'" . $v . "'");
		$db->method('quoteName')->willReturnCallback(static fn(string $n): string => '`' . $n . '`');
		$db->method('createQuery')->willReturn($queryMock);
		$db->method('getAffectedRows')->willReturn(0);

		$command = new TchoozAnonymizeUsersCommand($db);

		$ref = new \ReflectionMethod($command, 'enforcePasswordResetForAllUsers');
		$ref->setAccessible(true);
		$ref->invoke($command);

		$joined = implode(' | ', $capturedSetFragments);

		$this->assertStringContainsString(
			"`password` = ''",
			$joined,
			'The password column must be emptied - no shared random hash to brute-force in a pre-prod leak.'
		);
		$this->assertStringContainsString(
			'`requireReset` = 1',
			$joined,
			'requireReset=1 must be set so Joomla forces a reset at next login.'
		);
		$this->assertFalse(
			$capturedWhereCalled,
			'The password reset must be platform-wide (no WHERE clause) - filtering to candidates would leave manager credentials from prod alive on pre-prod.'
		);
	}

	// -------------------------------------------------------------------------
	// wipeHistories — runtime iteration order matches HISTORY_TABLES
	// -------------------------------------------------------------------------

	/**
	 * @covers \Emundus\Plugin\Console\Tchooz\CliCommand\Commands\TchoozAnonymizeUsersCommand::wipeHistories
	 * @return void
	 */
	public function testWipeHistoriesDeletesTablesInFkSafeOrderAtRuntime(): void
	{
		// The HISTORY_TABLES constant test guarantees the *declared* order, but a
		// reversed foreach (or a switch to array_reverse) would still leave that
		// test green while breaking FK integrity at runtime. This test asserts
		// on the actual sequence of DELETE calls the command issues.
		$capturedTables = [];

		$queryMock = $this->createMock(QueryInterface::class);
		$queryMock->method('delete')->willReturnCallback(function ($tableExpr) use (&$capturedTables, $queryMock) {
			// quoteName wraps the name in backticks - strip them to compare with HISTORY_TABLES.
			$capturedTables[] = trim((string) $tableExpr, '`');

			return $queryMock;
		});

		// Use DatabaseDriver (abstract) rather than DatabaseInterface: the interface
		// does not declare createQuery(), which the command relies on.
		$db = $this->createMock(DatabaseDriver::class);
		$db->method('quoteName')->willReturnCallback(static fn(string $n): string => '`' . $n . '`');
		$db->method('createQuery')->willReturn($queryMock);
		$db->method('getAffectedRows')->willReturn(0);

		$command = new TchoozAnonymizeUsersCommand($db);

		// wipeHistories writes progress lines via ioStyle; stub it so nothing hits stdout.
		$styleProp = new \ReflectionProperty($command, 'ioStyle');
		$styleProp->setAccessible(true);
		$styleProp->setValue($command, $this->createMock(SymfonyStyle::class));

		$ref = new \ReflectionMethod($command, 'wipeHistories');
		$ref->setAccessible(true);
		$ref->invoke($command);

		$this->assertSame(
			[
				'#__emundus_chatroom_notifications',
				'#__emundus_chatroom',
				'#__emundus_comments',
				'#__messages',
				'#__emundus_sms_queue',
			],
			$capturedTables,
			'wipeHistories() must issue the DELETE calls in the exact order declared in HISTORY_TABLES so FK dependents (chat notifications) are wiped before their parents (chatroom).'
		);
	}
}
