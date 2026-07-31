<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

use Joomla\CMS\Log\Log;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\Security\SensitiveDataStrategy;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Services\Security\SensitiveDataAnonymizer;

/**
 * Anonymises user personal data directly in the database.
 *
 * Intended to be run right after copying a production platform to
 * pre-production, so a pre-prod database holds no exploitable user identity.
 *
 * Phase 1 (this command):
 *  - empties the password column of every Joomla account and sets
 *    requireReset=1 so the next login goes through the reset flow; applied
 *    platform-wide (SSO covers the in-house case, applicants reach their
 *    account via the reset link). No hash is stored, so there is no secret
 *    to brute-force in a pre-prod leak;
 *  - replaces identifying data of the applicant accounts with fake,
 *    deterministic values - Joomla #__users (name, username, email) and eMundus
 *    #__emundus_users (firstname, lastname, email_cc). Only users whose eMundus
 *    profile is an applicant profile (#__emundus_setup_profiles.published = 1)
 *    are touched; any user that also holds a manager profile (published = 0)
 *    is preserved as-is, so the platform stays administrable. Super users are
 *    covered by that rule too (they never hold an applicant profile);
 *  - anonymises every entity that declares personal data through the
 *    #[SensitiveData] attribute on its properties. Currently registered pairs
 *    (see SENSITIVE_DATA_ENTITIES) are ContactEntity/#__emundus_contacts,
 *    AddressEntity/#__emundus_addresses, OrganizationEntity/#__emundus_organizations.
 *    Adding a new PII table only requires decorating its entity and adding
 *    the pair - no new UPDATE has to be written here;
 *  - anonymises every applicant answer stored in Fabrik form tables for
 *    elements whose plugin exposes personal data - phone numbers
 *    (emundus_phonenumber plugin) and IBANs (iban plugin). Targets are
 *    discovered dynamically from #__fabrik_elements so newly added forms are
 *    picked up automatically. This is also the mechanism the future
 *    "sensitive data" parameter will lean on;
 *  - optionally wipes historical data that can leak PII (sent emails,
 *    internal messaging, file comments, SMS queue) in an all-or-nothing step
 *    - flushed platform-wide since pre-prod has no business use for that
 *    history and any residual row is a leak vector.
 *
 * Phase 2 (later, once the "sensitive data" parameter is developed): extend the
 * Fabrik pass to every element flagged as sensitive on top of the plugins
 * covered here. A TODO marker is kept below.
 */
class TchoozAnonymizeUsersCommand extends AbstractCommand
{
	use DatabaseAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'tchooz:users_anonymize';

	/**
	 * Entity classes decorated with #[SensitiveData] properties. Each pair binds
	 * an entity to its SQL table so the anonymiser can build the UPDATE using
	 * the columns declared on the entity. Adding a new PII entity is now a
	 * two-step change: decorate its properties, then add the pair here.
	 *
	 * @var array<array{entity: class-string, table: string}>
	 */
	private const SENSITIVE_DATA_ENTITIES = [
		['entity' => ContactEntity::class,      'table' => '#__emundus_contacts'],
		['entity' => AddressEntity::class,      'table' => '#__emundus_addresses'],
		['entity' => OrganizationEntity::class, 'table' => '#__emundus_organizations'],
	];

	/**
	 * PII-leaking history tables wiped as a single all-or-nothing step. Order
	 * matters: child tables (FK dependents) are deleted before their parents.
	 *
	 *  - #__emundus_chatroom_notifications / #__emundus_chatroom : internal messaging;
	 *  - #__emundus_comments : file comments;
	 *  - #__messages : Joomla native table, populated by com_emundus with sent emails;
	 *  - #__emundus_sms_queue : dispatched SMS (recipient numbers, rendered content).
	 *    #__emundus_setup_sms holds the templates only and is intentionally kept.
	 *
	 * @var string[]
	 */
	private const HISTORY_TABLES = [
		'#__emundus_chatroom_notifications',
		'#__emundus_chatroom',
		'#__emundus_comments',
		'#__messages',
		'#__emundus_sms_queue',
	];

	/**
	 * Fabrik element plugins whose stored value is personal data by nature. Any
	 * form column backed by one of these plugins is emptied on every row. The
	 * future "sensitive data" parameter will extend this via a per-element flag.
	 *
	 * Typed with the project's ElementPluginEnum so the reference to a plugin
	 * name is checked at load time - a rename in the enum breaks the build here
	 * instead of silently missing tables at runtime.
	 *
	 * @var ElementPluginEnum[]
	 */
	private const FABRIK_SENSITIVE_PLUGINS = [
		ElementPluginEnum::PHONENUMBER,
		ElementPluginEnum::IBAN,
	];

	/**
	 * SymfonyStyle Object
	 * @var   SymfonyStyle
	 */
	private SymfonyStyle $ioStyle;

	/**
	 * Stores the Input Object
	 * @var   InputInterface
	 */
	private InputInterface $cliInput;

	/**
	 * Command constructor.
	 *
	 * @param   DatabaseInterface  $db  The database
	 */
	public function __construct(DatabaseInterface $db)
	{
		parent::__construct();

		$this->setDatabase($db);
	}

	/**
	 * Internal function to execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer  The command exit code
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);
		$this->ioStyle->title('Anonymise user data');

		// Dedicated log file for anonymisation warnings (skipped Fabrik targets, ...).
		Log::addLogger(['text_file' => 'com_emundus.anonymize.php'], Log::ALL, ['com_emundus.anonymize']);

		if (!$this->confirmExecution())
		{
			$this->ioStyle->warning('Aborted, nothing has been changed.');

			return Command::SUCCESS;
		}

		$anonymizer = new SensitiveDataAnonymizer($this->getDatabase());

		try
		{
			// Step 1 - every Joomla account gets a scrambled password and requireReset=1
			// so the pre-prod platform never runs on prod credentials and any login triggers
			// a reset (SSO covers the in-house case).
			$resetCount = $this->enforcePasswordResetForAllUsers();

			// Step 1b - candidate accounts additionally get their identifying columns
			// (name, username, email) replaced with fake deterministic values.
			$joomlaCount = $this->anonymiseJoomlaUsers($anonymizer);

			// Step 2 - eMundus profiles: firstname, lastname, email_cc (candidates only).
			$emundusCount = $this->anonymiseEmundusUsers($anonymizer);

			// Steps 3-5 - PII directories declared via #[SensitiveData] on their entities.
			$attributesCount = $this->anonymiseSensitiveEntities($anonymizer);

			// Step 6 - Fabrik sensitive plugins (phone numbers, IBANs) wiped in every form table.
			$fabrikFieldsCount = $this->anonymiseFabrikSensitiveFields();

			// TODO (phase 2): once the "sensitive data" (sensitive) parameter is developed,
			//  extend the Fabrik pass above to every element flagged as sensitive by re-using
			//  the same discovery/UPDATE mechanism (see anonymiseFabrikSensitiveFields()).

			// Step 7 - all-or-nothing wipe of PII-leaking history tables.
			$historyCount = $this->confirmHistoryWipe() ? $this->wipeHistories() : 0;
		}
		catch (\Throwable $e)
		{
			$this->ioStyle->error('Error while anonymising user data: ' . $e->getMessage());

			return Command::FAILURE;
		}

		$this->ioStyle->success(sprintf(
			'%d password(s) emptied + requireReset=1 platform-wide. %d Joomla account(s) and %d eMundus profile(s) had their identifying columns anonymised (applicants only; users holding a manager profile keep their identity). %d row(s) cleaned through #[SensitiveData] entities, %d Fabrik sensitive field column(s) cleaned. %d historical row(s) wiped.',
			$resetCount,
			$joomlaCount,
			$emundusCount,
			$attributesCount,
			$fabrikFieldsCount,
			$historyCount
		));

		return Command::SUCCESS;
	}

	/**
	 * Ask for confirmation before running this destructive command,
	 * unless --no-interaction is passed.
	 *
	 * @return bool
	 */
	private function confirmExecution(): bool
	{
		if ($this->cliInput->getOption('no-interaction'))
		{
			return true;
		}

		$this->displayDangerBlock();

		return $this->ioStyle->confirm(
			'I have read the DANGER block above and I understand this will irreversibly rewrite personal data on the current database. Continue?',
			false
		);
	}

	/**
	 * Renders a red DANGER block listing the destructive actions this command
	 * performs. Shown only in interactive mode - --no-interaction runs are
	 * meant to be triggered by an orchestrator that already knows the impact.
	 *
	 * @return void
	 */
	private function displayDangerBlock(): void
	{
		$this->ioStyle->block(
			[
				'This command IRREVERSIBLY rewrites personal data on the CURRENT database:',
				'',
				'  * every #__users password is emptied and requireReset=1 - no one will be able to log in',
				'    with their existing password (SSO and the "forgot password" reset flow are unaffected);',
				'  * every applicant identity (name, username, email; firstname/lastname/email_cc on the',
				'    eMundus profile) is replaced with fake deterministic values;',
				'  * the contacts, addresses and organisations directories, and every Fabrik phone/IBAN',
				'    answer, are anonymised in place;',
				'  * you will then be asked whether to WIPE historical PII (sent emails, internal messaging,',
				'    file comments, SMS queue).',
				'',
				'Run this ONLY on a pre-production copy right after a prod-to-preprod refresh.',
				'NEVER on production. There is no rollback.',
			],
			'DANGER',
			'fg=white;bg=red',
			' ',
			true
		);
	}

	/**
	 * All-or-nothing prompt for the PII history wipe. --no-interaction wipes
	 * everything (cohesive default for the automated post prod -> pre-prod
	 * copy use case).
	 *
	 * @return bool
	 */
	private function confirmHistoryWipe(): bool
	{
		if ($this->cliInput->getOption('no-interaction'))
		{
			return true;
		}

		return $this->ioStyle->confirm(
			'Also wipe PII-leaking history (sent emails, internal messaging, file comments, SMS queue)?',
			true
		);
	}

	/**
	 * Deletes every row of the PII-leaking history tables (sent emails,
	 * internal messaging, file comments, SMS queue). Wiped platform-wide on
	 * purpose: on pre-prod that history has no business value and any
	 * residual row is a leak vector.
	 *
	 * @return int  Total number of rows deleted across every history table.
	 */
	private function wipeHistories(): int
	{
		$db    = $this->getDatabase();
		$total = 0;

		foreach (self::HISTORY_TABLES as $table)
		{
			$query = $db->createQuery()->delete($db->quoteName($table));
			$db->setQuery($query);
			$db->execute();

			$deleted = $db->getAffectedRows();
			$total  += $deleted;
			$this->ioStyle->writeln(sprintf('  %s: %d row(s) wiped.', $table, $deleted));
		}

		return $total;
	}

	/**
	 * Empties the password of every Joomla account and sets requireReset=1 so
	 * the next login goes through the reset flow. Applied platform-wide (SSO
	 * covers in-house managers).
	 *
	 * Emptying the column rather than storing a shared random hash is a
	 * deliberate security choice: with no secret in the database there is no
	 * hash to brute-force. UserHelper::verifyPassword() falls into the chained
	 * handler for a non-prefixed value and returns false for any input, so no
	 * password login can succeed until the user goes through the reset flow.
	 *
	 * @return int  Number of accounts updated.
	 */
	private function enforcePasswordResetForAllUsers(): int
	{
		$db = $this->getDatabase();

		$query = $db->createQuery()
			->update($db->quoteName('#__users'))
			->set($db->quoteName('password') . " = ''")
			->set($db->quoteName('requireReset') . ' = 1');

		$db->setQuery($query);
		$db->execute();

		return $db->getAffectedRows();
	}

	/**
	 * Replaces the identifying columns (name, username, email) of every
	 * applicant account with fake deterministic values. #__users is not modelled
	 * by a Tchooz entity so the SET list is spelled out here; the fake
	 * generators come from the anonymiser to keep a single source for the name
	 * pools and email domain. Password reset is handled globally in step 1.
	 *
	 * @param   SensitiveDataAnonymizer  $anonymizer
	 *
	 * @return int  Number of accounts updated.
	 */
	private function anonymiseJoomlaUsers(SensitiveDataAnonymizer $anonymizer): int
	{
		$db = $this->getDatabase();

		$idColumn = $db->quoteName('id');
		$firstName = $anonymizer->expressionFor(SensitiveDataStrategy::FAKE_FIRSTNAME, $idColumn);
		$lastName  = $anonymizer->expressionFor(SensitiveDataStrategy::FAKE_LASTNAME, $idColumn);
		$email     = $anonymizer->expressionFor(SensitiveDataStrategy::FAKE_EMAIL, $idColumn);

		$query = $db->createQuery()
			->update($db->quoteName('#__users'))
			->set($db->quoteName('name') . ' = CONCAT(' . $firstName . ", ' ', " . $lastName . ')')
			->set($db->quoteName('username') . " = CONCAT('user_', " . $idColumn . ')')
			->set($db->quoteName('email') . ' = ' . $email)
			->where($this->candidatesOnlyClause('id'));

		$db->setQuery($query);
		$db->execute();

		return $db->getAffectedRows();
	}

	/**
	 * Replaces firstname/lastname and clears email_cc of every non super-user
	 * eMundus profile. Fake names are derived from the same user id as the Joomla
	 * account, so #__users.name stays consistent with firstname + lastname.
	 *
	 * @param   SensitiveDataAnonymizer  $anonymizer
	 *
	 * @return int  Number of profiles updated.
	 */
	private function anonymiseEmundusUsers(SensitiveDataAnonymizer $anonymizer): int
	{
		$db = $this->getDatabase();

		$userIdColumn = $db->quoteName('user_id');
		$firstName    = $anonymizer->expressionFor(SensitiveDataStrategy::FAKE_FIRSTNAME, $userIdColumn);
		$lastName     = $anonymizer->expressionFor(SensitiveDataStrategy::FAKE_LASTNAME, $userIdColumn);

		$query = $db->createQuery()
			->update($db->quoteName('#__emundus_users'))
			->set($db->quoteName('firstname') . ' = ' . $firstName)
			->set($db->quoteName('lastname') . ' = ' . $lastName)
			->set($db->quoteName('email_cc') . " = ''")
			->where($this->candidatesOnlyClause('user_id'));

		$db->setQuery($query);
		$db->execute();

		return $db->getAffectedRows();
	}

	/**
	 * Runs the anonymiser against every entity/table pair declared in
	 * SENSITIVE_DATA_ENTITIES. Adding a new PII table only requires decorating
	 * the entity properties with #[SensitiveData] and registering the pair -
	 * the SQL is built from the entity's declaration.
	 *
	 * @param   SensitiveDataAnonymizer  $anonymizer
	 *
	 * @return int  Total number of rows updated across all decorated entities.
	 */
	private function anonymiseSensitiveEntities(SensitiveDataAnonymizer $anonymizer): int
	{
		$total = 0;

		foreach (self::SENSITIVE_DATA_ENTITIES as $pair)
		{
			$updated = $anonymizer->anonymise($pair['entity'], $pair['table']);
			$total  += $updated;
			$this->ioStyle->writeln(sprintf('  %s: %d row(s) anonymised via #[SensitiveData].', $pair['table'], $updated));
		}

		return $total;
	}

	/**
	 * Fetches every published Fabrik element whose plugin exposes personal data
	 * (see FABRIK_SENSITIVE_PLUGINS) and empties the corresponding form column
	 * on every row. Discovery goes through FabrikRepository so the joins and
	 * filters stay consistent with the rest of the platform (repeat groups,
	 * published state, ...); the storage table is picked from the entity's own
	 * getTableJoin() / getDbTableName() getters.
	 *
	 * Runs each UPDATE inside its own try/catch: a stale element pointing at a
	 * dropped table or column must not abort the whole anonymisation.
	 *
	 * @return int  Number of (table, column) pairs successfully cleaned.
	 */
	private function anonymiseFabrikSensitiveFields(): int
	{
		$db = $this->getDatabase();

		$repository = new FabrikRepository();
		$filters    = [
			'plugin'    => array_map(static fn(ElementPluginEnum $p): string => $p->value, self::FABRIK_SENSITIVE_PLUGINS),
			'published' => 1,
		];

		$cleaned  = 0;
		$page     = 1;
		$pageSize = 100;

		do
		{
			$elements = $repository->getElements($filters, $pageSize, $page);

			foreach ($elements as $element)
			{
				$table  = $this->resolveFabrikTargetTable($element);
				$column = $element->getName();

				if ($table === null || $column === '')
				{
					continue;
				}

				try
				{
					$update = $db->createQuery()
						->update($db->quoteName($table))
						->set($db->quoteName($column) . " = ''");
					$db->setQuery($update);
					$db->execute();

					$cleaned++;
					$this->ioStyle->writeln(sprintf('  Fabrik %s.%s (%s, %s): cleaned.', $table, $column, $element->getId(), $element->getPlugin()->value));
				}
				catch (\Throwable $e)
				{
					$this->ioStyle->writeln(sprintf('  Fabrik %s.%s (%s, %s): skipped (%s).', $table, $column, $element->getId(), $element->getPlugin()->value, $e->getMessage()));

					// CLI stdout scrolls away; a persistent log entry lets ops post-mortem
					// which Fabrik elements were left unchanged (stale table/column, permissions...).
					Log::add(
						sprintf(
							'Fabrik anonymisation skipped: table=%s column=%s element_id=%d plugin=%s exception=%s message=%s',
							$table,
							$column,
							$element->getId(),
							$element->getPlugin()->value,
							$e::class,
							$e->getMessage()
						),
						Log::WARNING,
						'com_emundus.anonymize'
					);
				}
			}

			$page++;
		}
		while (count($elements) === $pageSize);

		return $cleaned;
	}

	/**
	 * Chooses which SQL table backs a Fabrik element: the join table when the
	 * element sits in a repeat group (FabrikElementEntity::getTableJoin() is
	 * set), otherwise the list's main db_table_name. Returns null if neither
	 * getter yields anything usable.
	 *
	 * Extracted so the selection can be unit-tested without a live database.
	 *
	 * @param   FabrikElementEntity  $element
	 *
	 * @return string|null
	 */
	private function resolveFabrikTargetTable(FabrikElementEntity $element): ?string
	{
		$joinTable = $element->getTableJoin();
		if ($joinTable !== '')
		{
			return $joinTable;
		}

		$listTable = $element->getDbTableName();
		if ($listTable !== '')
		{
			return $listTable;
		}

		return null;
	}

	/**
	 * SQL condition restricting the update to applicant users only: any user
	 * having an applicant profile (jos_emundus_setup_profiles.published = 1),
	 * whether it is set as #__emundus_users.profile or referenced via the
	 * many-to-many #__emundus_users_profiles table, and NOT also holding a
	 * manager profile (published = 0). A user with both an applicant and a
	 * manager profile is preserved (safety: we never touch anyone who can
	 * still act as a manager). Super users are covered by this rule too, as
	 * they never hold an applicant profile.
	 *
	 * @param   string  $column  User id column of the updated table (id or user_id).
	 *
	 * @return string
	 */
	private function candidatesOnlyClause(string $column): string
	{
		$db         = $this->getDatabase();
		$columnName = $db->quoteName($column);

		// The IN / NOT IN sub-queries read from #__emundus_users, which is one
		// of the tables this command updates. MySQL rejects such a self-
		// reference in an UPDATE ("You can't specify target table 'X' for
		// update in FROM clause") unless the sub-query is wrapped in a derived
		// table, which forces materialisation. Aliases must be unique within
		// the same WHERE, hence two distinct wrapper names.
		$applicants = 'SELECT ' . $db->quoteName('user_id') . ' FROM ('
			. $this->userIdsForProfilePublishedSubquery(1)
			. ') AS ' . $db->quoteName('applicants_wrap');

		$managers = 'SELECT ' . $db->quoteName('user_id') . ' FROM ('
			. $this->userIdsForProfilePublishedSubquery(0)
			. ') AS ' . $db->quoteName('managers_wrap');

		return $columnName . ' IN (' . $applicants . ') AND '
			. $columnName . ' NOT IN (' . $managers . ')';
	}

	/**
	 * SQL sub-query listing user ids that hold at least one profile whose
	 * jos_emundus_setup_profiles.published column equals $published (1 for
	 * applicant profiles, 0 for manager profiles). Covers both storages: the
	 * legacy single #__emundus_users.profile column and the many-to-many
	 * #__emundus_users_profiles table.
	 *
	 * @param   int  $published  1 for applicant profiles, 0 for manager profiles.
	 *
	 * @return string
	 */
	private function userIdsForProfilePublishedSubquery(int $published): string
	{
		$db = $this->getDatabase();

		$profileIds = 'SELECT ' . $db->quoteName('id')
			. ' FROM ' . $db->quoteName('#__emundus_setup_profiles')
			. ' WHERE ' . $db->quoteName('published') . ' = ' . (int) $published;

		$fromEmundusUsers = 'SELECT ' . $db->quoteName('user_id')
			. ' FROM ' . $db->quoteName('#__emundus_users')
			. ' WHERE ' . $db->quoteName('profile') . ' IN (' . $profileIds . ')';

		$fromUsersProfiles = 'SELECT ' . $db->quoteName('user_id')
			. ' FROM ' . $db->quoteName('#__emundus_users_profiles')
			. ' WHERE ' . $db->quoteName('profile_id') . ' IN (' . $profileIds . ')';

		return $fromEmundusUsers . ' UNION ' . $fromUsersProfiles;
	}

	/**
	 * Configure the IO.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  void
	 */
	private function configureIO(InputInterface $input, OutputInterface $output): void
	{
		$this->cliInput = $input;
		$this->ioStyle  = new SymfonyStyle($input, $output);
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 */
	protected function configure(): void
	{
		$help = "<info>%command.name%</info> anonymises applicant personal data directly in the database:
		\n  1. Joomla accounts (#__users): password column emptied and requireReset=1 for every account (no hash stored - no secret to brute-force); identifying columns (name, username, email) replaced with fake deterministic values on applicant accounts only;
		\n  2. eMundus profiles (#__emundus_users): firstname, lastname replaced and email_cc cleared;
		\n  3. #[SensitiveData]-decorated entities (contacts, addresses, organisations): every property flagged with the attribute is neutralised on every row, structure kept. New PII tables are added by decorating their entity + registering the pair in SENSITIVE_DATA_ENTITIES;
		\n  4. Fabrik sensitive answers: form columns backed by the emundus_phonenumber and iban plugins are emptied on every row (discovered dynamically from #__fabrik_elements);
		\n  5. optional all-or-nothing history wipe (yes/no, default yes; --no-interaction wipes): sent emails (#__messages), internal messaging (#__emundus_chatroom + notifications), file comments (#__emundus_comments), dispatched SMS (#__emundus_sms_queue - templates in #__emundus_setup_sms are kept).
		\nOnly users holding an applicant profile (jos_emundus_setup_profiles.published = 1) and NOT holding a manager profile are anonymised in steps 1 & 2. Intended to be run after copying a production platform to pre-production.
		\nUsage: <info>php %command.full_name%</info>";

		$this->setDescription('Anonymise user data with fake values (post prod -> pre-prod copy)');
		$this->setHelp($help);
	}
}
