<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand;

defined('_JEXEC') or die;

use FabrikWorker;
use Joomla\CMS\Factory;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TchoozResetFabrikConnectionCommand extends AbstractCommand
{
	use DatabaseAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected static $defaultName = 'tchooz:fabrik_connection_reset';

	/**
	 * SymfonyStyle Object
	 * @var   SymfonyStyle
	 * @since 4.0.0
	 */
	private SymfonyStyle $ioStyle;

	/**
	 * Stores the Input Object
	 * @var   InputInterface
	 * @since 4.0.0
	 */
	private InputInterface $cliInput;

	/**
	 * Command constructor.
	 *
	 * @param   DatabaseInterface  $db  The database
	 *
	 * @since   4.2.0
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
	 *
	 * @since   4.0.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$this->configureIO($input, $output);
		$this->ioStyle->title('Reset Fabrik Connection');

		$app = Factory::getApplication();
		$crypt = FabrikWorker::getCrypt();

		$columns = [
			'id',
			'host',
			'user',
			'password',
			'database',
			'description',
			'published',
			'checked_out',
			'default',
			'params',
		];

		$values = [
			1,
			$app->get('host'),
			$app->get('user'),
			$crypt->encrypt($app->get('password')),
			$app->get('db'),
			'site database',
			1,
			0,
			1,
			'{"encryptedPw":true}'
		];

		$db = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->delete('#__fabrik_connections');
		$db->setQuery($query);
		if(!$db->execute())
		{
			$this->ioStyle->error('Error deleting fabrik connections');
			return Command::FAILURE;
		}

		$query->clear();
		$query->insert('#__fabrik_connections');
		$query->columns($db->quoteName($columns));
		$query->values(implode(',', $db->quote($values)));
		$db->setQuery($query);
		if(!$db->execute())
		{
			$this->ioStyle->error('Error inserting fabrik connections');
			return Command::FAILURE;
		}

		$this->ioStyle->success('Fabrik Connection reset successfully');

		return Command::SUCCESS;
	}

	/**
	 * Configure the IO.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	private function configureIO(InputInterface $input, OutputInterface $output)
	{
		$this->cliInput = $input;
		$this->ioStyle  = new SymfonyStyle($input, $output);
	}

	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function configure(): void
	{
		$help = "<info>%command.name%</info> will reset Fabrik Connection with configuration file
		\nUsage: <info>php %command.full_name%</info>";

		$this->setDescription('Reset Fabrik Connection with configuration file');
		$this->setHelp($help);
	}
}