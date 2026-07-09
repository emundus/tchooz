<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tchooz\Entities\Fields\PasswordField;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;
use Tchooz\Services\Integrations\IntegrationConfigurationRegistry;

/**
 * Neutralises every interconnection credential on the platform.
 *
 * Intended to be run right after copying a production platform to
 * pre-production, so a pre-prod database holds no usable interconnection
 * credential, while keeping every interconnection business configuration
 * intact.
 *
 * Credentials are never enumerated: they all live in the "authentication"
 * group of each integration configuration (that is where every PasswordField
 * is declared). Resetting that whole group to its default values is therefore
 * enough to drop them, and keeps the "configuration" group untouched.
 */
class TchoozResetSynchronizersCommand extends AbstractCommand
{
	use DatabaseAwareTrait;

	/**
	 * The credential-bearing group name, by convention, in every integration
	 * configuration. Reset entirely to its defaults.
	 */
	private const AUTHENTICATION_GROUP = 'authentication';

	/**
	 * Legacy interconnection credentials / account identifiers stored flat in
	 * the com_emundus component params (jos_extensions). Unlike synchronizers,
	 * these have no declarative field schema, so the list is EXPLICIT and
	 * assumed on purpose. Only credentials are listed here; structural settings
	 * (*_base_url, attribute mappings, ...) are intentionally left untouched.
	 *
	 * Keep this list in sync when a new legacy API integration is added under
	 * components/com_emundus/classes/api.
	 *
	 * @var string[]
	 */
	private const LEGACY_CREDENTIAL_PARAMS = [
		'yousign_api_key',
		'file_maker_api_basic_auth_token',
		'glpi_api_app_token',
		'glpi_api_user_token',
		'ixparapheur_api_app_token',
		'postgrest_api_bearer_token',
		'smart_agenda_api_id',
		'smart_agenda_api_key',
		'smart_agenda_login',
		'smart_agenda_pwd',
		'zoom_jwt',
		'external_storage_ged_alfresco_user',
		'external_storage_ged_alfresco_password',
	];

	/**
	 * The default command name
	 *
	 * @var    string
	 */
	protected static $defaultName = 'tchooz:synchronizers_reset';

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
		$this->ioStyle->title('Reset interconnections credentials');

		if (!$this->confirmExecution())
		{
			$this->ioStyle->warning('Aborted, nothing has been changed.');

			return Command::SUCCESS;
		}

		try
		{
			// Step 1 - synchronizers (new interconnection system, declarative config).
			$syncCount = $this->resetSynchronizers();

			// Step 2 - legacy API credentials stored in com_emundus component params.
			$legacyCount = $this->resetLegacyComponentParams();
		}
		catch (\Throwable $e)
		{
			$this->ioStyle->error('Error while resetting interconnections: ' . $e->getMessage());

			return Command::FAILURE;
		}

		$this->ioStyle->success(sprintf(
			'%d synchronizer(s) reset (authentication credentials dropped, synchronizers disabled, business configuration kept) and %d legacy credential(s) emptied.',
			$syncCount,
			$legacyCount
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

		return $this->ioStyle->confirm(
			'This will reset every interconnection credential and disable all synchronizers on this platform. Continue?',
			false
		);
	}

	/**
	 * Resets the credential group of every synchronizer to its default values
	 * and disables it, keeping the rest of its configuration.
	 *
	 * @return int  Number of synchronizers updated.
	 *
	 * @throws \Exception
	 */
	private function resetSynchronizers(): int
	{
		$repository    = new SynchronizerRepository(false);
		$synchronizers = $repository->getAll([], 10000, 1);
		$registry      = new IntegrationConfigurationRegistry();

		$count = 0;
		foreach ($synchronizers as $synchronizer)
		{
			$configuration = $registry->getConfiguration($synchronizer->getType());
			$defaults      = $configuration?->getDefaultParameters() ?? [];

			$config = $synchronizer->getConfig();
			foreach ($this->getCredentialGroups($configuration) as $groupName)
			{
				// Only touch groups actually present in the stored config.
				if (array_key_exists($groupName, $config))
				{
					$config[$groupName] = $defaults[$groupName] ?? [];
				}
			}

			$synchronizer->setConfig($config);
			$synchronizer->setEnabled(false);
			$synchronizer->setPublished(false);

			if ($repository->flush($synchronizer))
			{
				$count++;
			}
			else
			{
				$this->ioStyle->warning('Could not reset synchronizer: ' . $synchronizer->getName());
			}
		}

		return $count;
	}

	/**
	 * Names of the config groups holding credentials: the "authentication"
	 * group (always) plus any group declaring a password field. Derived from
	 * the integration configuration so no sensitive key is ever hard-coded.
	 *
	 * @param   EmundusIntegrationConfiguration|null  $configuration
	 *
	 * @return string[]
	 */
	private function getCredentialGroups(?EmundusIntegrationConfiguration $configuration): array
	{
		$groups = [self::AUTHENTICATION_GROUP => true];

		if ($configuration !== null)
		{
			foreach ($configuration->getParameters() as $field)
			{
				if ($field instanceof PasswordField)
				{
					$groupName = $field->getGroup()?->getName();
					if ($groupName !== null)
					{
						$groups[$groupName] = true;
					}
				}
			}
		}

		return array_keys($groups);
	}

	/**
	 * Empties the legacy interconnection credentials stored flat in the
	 * com_emundus component params, keeping every non-sensitive setting.
	 *
	 * Separate, explicit step: these legacy integrations (FileMaker, GLPI,
	 * IxParapheur, PostgREST, SmartAgenda, Yousign, Zoom, Alfresco GED) predate
	 * the synchronizer system and have no declarative schema, so the cleared
	 * keys are the assumed list in self::LEGACY_CREDENTIAL_PARAMS.
	 *
	 * @return int  Number of param keys emptied.
	 */
	private function resetLegacyComponentParams(): int
	{
		$db = $this->getDatabase();

		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('element') . ' = ' . $db->quote('com_emundus'))
			->where($db->quoteName('type') . ' = ' . $db->quote('component'));
		$db->setQuery($query);
		$paramsJson = $db->loadResult();

		if (empty($paramsJson))
		{
			return 0;
		}

		$params = json_decode($paramsJson, true);
		if (!is_array($params))
		{
			return 0;
		}

		$count = 0;
		foreach (self::LEGACY_CREDENTIAL_PARAMS as $key)
		{
			if (array_key_exists($key, $params) && $params[$key] !== '')
			{
				$params[$key] = '';
				$count++;
			}
		}

		if ($count > 0)
		{
			$query = $db->getQuery(true)
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
				->where($db->quoteName('element') . ' = ' . $db->quote('com_emundus'))
				->where($db->quoteName('type') . ' = ' . $db->quote('component'));
			$db->setQuery($query);
			$db->execute();
		}

		return $count;
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
		$help = "<info>%command.name%</info> resets every interconnection credential on the platform:
		\n  1. synchronizers: the authentication group of each is reset to its default values (dropping all credentials) and the synchronizer is disabled, while its business configuration is kept;
		\n  2. legacy API credentials stored in the com_emundus component params are emptied.
		\nIntended to be run after copying a production platform to pre-production.
		\nUsage: <info>php %command.full_name%</info>";

		$this->setDescription('Reset interconnection credentials (post prod -> pre-prod copy)');
		$this->setHelp($help);
	}
}
