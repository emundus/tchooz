<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

use Emundus\Plugin\Console\Tchooz\CliCommand\TchoozCommand;
use Joomla\CMS\Crypt\Crypt;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'tchooz:api:token', description: 'Set an API Token for Tchooz on a user')]
class TchoozApiTokenCommand extends TchoozCommand
{
	use DatabaseAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected static $defaultName = 'tchooz:api:token';

	private string $token = '';

	private string $username = '';

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
		$this->ioStyle->title('Set an API Token for Tchooz on a user');

		$this->username = $this->getStringFromOption('username', 'Please enter a username, the user need to have API access');

		// Only do something if the api-authentication plugin with the same name is published
		if (!PluginHelper::isEnabled('api-authentication', 'token')) {
			$this->ioStyle->warning('The API Token Authentication plugin is not enabled. Skipping token setup.');
			return Command::SUCCESS;
		}

		if(empty($this->username)) {
			$this->ioStyle->error('Username cannot be empty');
			return Command::FAILURE;
		}
		
		$apiGroups = $this->getApiGroups();
		if(empty($apiGroups)) {
			$this->ioStyle->error('Could not find API groups. Make sure API v2 or API v3 groups exist.');
			return Command::FAILURE;
		}
		
		// Load user
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($this->username);
		if(empty(array_intersect($apiGroups, $user->groups)))
		{
			$this->ioStyle->error('User does not belong to API groups. Make sure the user has API access.');
			return Command::FAILURE;
		}

		// Hash token
		$hashToken = base64_encode(Crypt::genRandomBytes(32));

		// Store token
		$stored = $this->storeToken($user->id, $hashToken);
		if(!$stored) {
			return Command::FAILURE;
		}

		$this->ioStyle->success("Updated API token for Tchooz");
		$displayToken = $this->displayToken($hashToken, $user->id);
		$this->ioStyle->writeln("The new API token is:\n");
		$this->ioStyle->writeln("<info>$displayToken</info>\n");
		$this->ioStyle->writeln("Make sure to copy it now, as you won't be able to see it again!");

		return Command::SUCCESS;
	}

	private function storeToken(int $userId, string $tokenHash): bool
	{
		$query = $this->db->getQuery(true);

		try
		{
			// Delete old token
			$query->delete($this->db->quoteName('#__user_profiles'))
				->where($this->db->quoteName('user_id') . ' = ' . $userId)
				->where($this->db->quoteName('profile_key') . ' = ' . $this->db->quote('joomlatoken.token'));
			$this->db->setQuery($query);

			if($this->db->execute()) {
				// Insert new token
				$insert = (object)[
					'user_id'      => $userId,
					'profile_key'  => 'joomlatoken.token',
					'profile_value'=> $tokenHash,
					'ordering'     => 1
				];

				return $this->db->insertObject('#__user_profiles', $insert);
			}
			else {
				$this->ioStyle->error('Could not delete old token.');
				return false;
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error storing API token: ' . $e->getMessage(), Log::ERROR, 'tchooz_cli');
			$this->ioStyle->error('An error occurred while storing the API token. Check the log for details.');
			return false;
		}
	}

	private function displayToken(string $tokenSeed, int $userId, string $algorithm = 'sha256'): string
	{
		if (empty($tokenSeed)) {
			return '';
		}

		try {
			$siteSecret = $this->getApplication()->get('secret');
		} catch (\Exception) {
			$siteSecret = '';
		}

		// NO site secret? You monster!
		if (empty($siteSecret)) {
			return '';
		}

		$rawToken  = base64_decode($tokenSeed);
		$tokenHash = hash_hmac($algorithm, $rawToken, $siteSecret);

		return base64_encode("$algorithm:$userId:$tokenHash");
	}

	private function getApiGroups(): array
	{
		$query = $this->db->getQuery(true);

		try
		{
			$query->select('id')
				->from($this->db->quoteName('#__usergroups'))
				->where($this->db->quoteName('title') . ' IN (' . implode(',', (array)$this->db->quote(['API v2', 'API v3'])) . ')');
			$this->db->setQuery($query);
			return $this->db->loadColumn();
		}
		catch (\Exception $e)
		{
			Log::add('Error fetching API groups: ' . $e->getMessage(), Log::ERROR, 'tchooz_cli');
			$this->ioStyle->error('An error occurred while fetching API groups. Check the log for details.');
			return [];
		}
	}

	protected function configure(): void
	{
		$help = "<info>%command.name%</info> will update configuration for Tchooz
		\nUsage: <info>php %command.full_name%</info>";

		$this->addOption('token', 'token', InputOption::VALUE_REQUIRED, 'The API token to set');
		$this->addOption('username', 'username', InputOption::VALUE_REQUIRED, 'The username to set the token for');
		$this->setDescription('Set an API Token for Tchooz on a user');
		$this->setHelp($help);
	}
}