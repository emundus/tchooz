<?php

namespace Emundus\Plugin\Console\Tchooz\CliCommand;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use GuzzleHttp\Client as GuzzleClient;

class TchoozKeycloakCommand extends AbstractCommand
{
	use DatabaseAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected static $defaultName = 'tchooz:keycloak';

	/**
	 * SymfonyStyle Object
	 * @var   object
	 * @since 4.0.0
	 */
	private $ioStyle;

	/**
	 * Stores the Input Object
	 * @var   object
	 * @since 4.0.0
	 */
	private $cliInput;

	/**
	 * Keycloak URL
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $keycloak_url;

	/**
	 * Keycloak client ID
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $keycloak_client_id;

	/**
	 * Keycloak client secret
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $keycloak_client_secret;

	/**
	 * Client ID
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	private $tchooz_client_id;

	/**
	 * Client Secret
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	private $tchooz_client_secret;

	/**
	 * Keycloak realm
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $realm;

	/**
	 * Keycloak well known url
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $well_known_url;

	/**
	 * Tchooz additional redirect uri
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $tchooz_additional_redirect_uri;

	/**
	 * Scopes
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $scopes;

	/**
	 * Guzzle client
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $guzzleClient;

	/**
	 * Headers
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $headers;

	/**
	 * Access token
	 *
	 * @var
	 * @since version 2.0.0
	 */
	private $access_token;

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
		$this->ioStyle->title('Setup keycloak configuration');

		$this->keycloak_url                   = $this->getStringFromOption('keycloak_url', 'Please enter a keycloack url');
		$this->keycloak_client_id             = $this->getStringFromOption('keycloak_client_id', 'Please enter a keycloack client id');
		$this->keycloak_client_secret         = $this->getStringFromOption('keycloak_client_secret', 'Please enter a keycloack client secret');
		$this->tchooz_client_id               = $this->getStringFromOption('tchooz_client_id', '[Optional] Please enter a client id', false);
		$this->tchooz_client_secret           = $this->getStringFromOption('tchooz_client_secret', '[Optional] Please enter a client secret', false);
		$this->realm                          = $this->getStringFromOption('realm', '[Optional] Please enter a realm where the client is created', false);
		$this->well_known_url                 = $this->getStringFromOption('well_known_url', '[Optional] Please enter a well known url', false);
		$this->tchooz_additional_redirect_uri = $this->getStringFromOption('tchooz_additional_redirect_uri', '[Optional] You can add other redirect uris', false);
		$this->scopes                         = $this->getStringFromOption('scopes', '[Optional] You can override default scopes', false);

		if (!empty($this->keycloak_url) && !empty($this->keycloak_client_id) && !empty($this->keycloak_client_secret))
		{
			$this->setClient();

			if (empty($this->realm))
			{
				$this->realm = 'emundus';
			}

			if(empty($this->tchooz_client_id)) {
				$this->tchooz_client_id = str_replace(['http://','https://'],'',Uri::base());
				// remove last character if it is a slash
				if(substr($this->tchooz_client_id, -1) == '/') {
					$this->tchooz_client_id = substr($this->tchooz_client_id, 0, -1);
				}
			}

			if(empty($this->well_known_url)) {
				$this->well_known_url = $this->keycloak_url . '/realms/' . $this->realm . '/.well-known/openid-configuration';
			}

			if($this->getAccessToken()) {
				$keycloak_client = $this->getKeycloakClient();

				if(empty($keycloak_client))
				{
					if ($this->createKeycloakClient())
					{
						$keycloak_client = $this->getKeycloakClient();
						$roles           = ['tchooz-admin', 'tchooz-coordinator'];
						foreach ($roles as $role)
						{
							$this->createRole($role, $keycloak_client);
						}
					}
				}

				// TODO : Update OAuth config with client created and well known url, if exist override
			}
		}

		$this->ioStyle->success("Keycloack configuration updated successfully");

		return Command::SUCCESS;
	}

	private function getAccessToken()
	{
		$authenticated = false;

		$url     = '/realms/' . $this->realm . '/protocol/openid-connect/token';
		$headers = [
			'Content-Type' => 'application/x-www-form-urlencoded'
		];
		$body    = [
			'client_id'     => $this->keycloak_client_id,
			'grant_type'    => 'client_credentials',
			'client_secret' => $this->keycloak_client_secret
		];

		$request = $this->guzzleClient->request('POST',$this->keycloak_url . '/' . $url, ['form_params' => $body, 'headers' => $headers]);

		$status = $request->getStatusCode();

		if($status == 200) {
			$authenticated = true;
			$data = json_decode($request->getBody());
			$this->headers = [
				'Authorization' => 'Bearer ' . $data->access_token
			];
		} else {
			$this->ioStyle->error("Error while getting access token");
		}

		return $authenticated;
	}

	private function createKeycloakClient()
	{
		$created = false;

		$url     = '/admin/realms/' . $this->realm . '/clients';
		$this->headers['Content-Type'] = 'application/json';

		$body    = [
			'clientId'     => $this->tchooz_client_id,
			'name' => Factory::getApplication()->get('sitename'),
			'redirectUris'    => [
				'https://'.$this->tchooz_client_id,
				'https://'.$this->tchooz_client_id . '/*'
			],
			'standardFlowEnabled' => true,
			'directAccessGrantsEnabled' => true,
			'serviceAccountsEnabled' => true,
			'authorizationServicesEnabled' => true,
		];
		$body = json_encode($body);
		
		$request = $this->guzzleClient->request('POST',$this->keycloak_url . '/' . $url, ['body' => $body, 'headers' => $this->headers]);

		$status = $request->getStatusCode();

		if($status == 201) {
			$created = true;
		} else {
			$this->ioStyle->error("Error while creating keycloak client");
		}

		return $created;
	}

	private function getKeycloakClient()
	{
		$keycloak_client = null;

		$url     = '/admin/realms/' . $this->realm . '/clients';

		$request = $this->guzzleClient->request('GET',$this->keycloak_url . '/' . $url . '?clientId='.$this->tchooz_client_id, ['headers' => $this->headers]);

		$status = $request->getStatusCode();

		if($status == 200) {
			$data = json_decode($request->getBody());
			if(!empty($data))
			{
				$keycloak_client = $data[0];
			}
		} else {
			$this->ioStyle->error("Error while getting keycloak client");
		}

		return $keycloak_client;
	}

	private function createRole($role,$keycloak_client)
	{
		$created = false;

		if(!empty($keycloak_client) && !empty($role))
		{
			$url                           = '/admin/realms/' . $this->realm . '/clients/' . $keycloak_client->id . '/roles';
			$this->headers['Content-Type'] = 'application/json';

			$body = [
				'name'                         => $role
			];
			$body = json_encode($body);

			$request = $this->guzzleClient->request('POST', $this->keycloak_url . '/' . $url, ['body' => $body, 'headers' => $this->headers]);

			$status = $request->getStatusCode();

			if ($status == 201)
			{
				$created = true;
			}
			else
			{
				$this->ioStyle->error("Error while creating role");
			}
		}

		return $created;
	}

	private function setClient(): void
	{
		$this->guzzleClient = new GuzzleClient([
			'base_uri' => $this->keycloak_url,
			'verify'   => false
		]);
	}

	/**
	 * Method to get a value from option
	 *
	 * @param   string  $option    set the option name
	 * @param   string  $question  set the question if user enters no value to option
	 *
	 * @return  string
	 *
	 * @since   4.0.0
	 */
	public function getStringFromOption($option, $question, $required = true): string
	{
		$answer = (string) $this->cliInput->getOption($option);

		if ($this->cliInput->getOption('no-interaction') === false)
		{
			if ($required)
			{
				while (!$answer)
				{
					$answer = (string) $this->ioStyle->ask($question);
				}
			}
			else
			{
				if (!$answer)
				{
					$answer = (string) $this->ioStyle->ask($question);
				}
			}
		}

		return $answer;
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
		// docker exec -it joomla5 php cli/joomla.php tchooz:keycloak -n --keycloak_url="https://staging-login.emundus.fr" --keycloak_client_id="tchooz-deployment" --keycloak_client_secret="ZCmi091LBpiQSZdkidjxqPoLQBntGlWs"

		$help = "<info>%command.name%</info> will update configuration for Tchooz
		\nUsage: <info>php %command.full_name%</info>";

		$this->addOption('keycloak_url', null, InputOption::VALUE_REQUIRED, 'Keycloak URL');
		$this->addOption('keycloak_client_id', null, InputOption::VALUE_REQUIRED, 'Keycloak Client ID');
		$this->addOption('keycloak_client_secret', null, InputOption::VALUE_REQUIRED, 'Keycloak Client ID');
		$this->addOption('tchooz_client_id', null, InputOption::VALUE_OPTIONAL, 'Tchooz Client ID');
		$this->addOption('tchooz_client_secret', null, InputOption::VALUE_OPTIONAL, 'Tchooz Client secret');
		$this->addOption('realm', null, InputOption::VALUE_OPTIONAL, 'Keycloak Realm');
		$this->addOption('well_known_url', null, InputOption::VALUE_OPTIONAL, 'Keycloak Well known url');
		$this->addOption('tchooz_additional_redirect_uri', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Tchooz redirect uris');
		$this->addOption('scopes', null, InputOption::VALUE_OPTIONAL, 'Tchooz redirect uris');
		$this->setDescription('Setup keycloak configuration');
		$this->setHelp($help);
	}
}