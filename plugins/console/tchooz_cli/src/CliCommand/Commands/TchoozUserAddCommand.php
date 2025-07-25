<?php
namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;


use Joomla\CMS\User\User;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Filter\InputFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class TchoozUserAddCommand extends AbstractCommand
{
    use DatabaseAwareTrait;

    /**
     * The default command name
     *
     * @var    string
     * @since  4.0.0
     */
    protected static $defaultName = 'tchooz:user:add';

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
     * The username
     *
     * @var    string
     *
     * @since  4.0.0
     */
    private $user;

    /**
     * The password
     *
     * @var    string
     *
     * @since  4.0.0
     */
    private $password;

    /**
     *  The firstname
     *
     * @var    string
     *
     * @since  4.0.0
     */
    private $firstname;

    /**
     *  The lastname
     *
     * @var    string
     *
     * @since  4.0.0
     */
    private $lastname;

    /**
     * The email address
     *
     * @var    string
     *
     * @since  4.0.0
     */
    private $email;

    /**
     * The usergroups
     *
     * @var    array
     *
     * @since  4.0.0
     */
    private $userGroups = [];

    /**
     * The userprofiles
     *
     * @var    array
     *
     * @since  4.0.0
     */
    private $userProfiles = [];

	/**
	 * The useremundusgroups
	 *
	 * @var    array
	 *
	 * @since  4.0.0
	 */
	private $userEmundusGroups = [];

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
        $this->ioStyle->title('Add User');

		$this->user       = $this->getStringFromOption('username', 'Please enter a username');
		$this->firstname = $this->getStringFromOption('firstname', 'Please enter a firstname');
		$this->lastname  = $this->getStringFromOption('lastname', 'Please enter a lastname');
        $this->email      = $this->getStringFromOption('email', 'Please enter an email address');
        $this->password   = $this->getStringFromOption('password', 'Please enter a password');
        $this->userGroups = $this->getUserGroups();
        $this->userProfiles = $this->getUserProfiles();
        $this->userEmundusGroups = $this->getEmundusUserGroups();

        if (\in_array("error", $this->userGroups)) {
            $this->ioStyle->error("'" . $this->userGroups[1] . "' user group doesn't exist!");

            return Command::FAILURE;
        }

        if (\in_array("error", $this->userProfiles)) {
            $this->ioStyle->error("'" . $this->userProfiles[1] . "' user profile doesn't exist!");

            return Command::FAILURE;
        }

        // Get filter to remove invalid characters
        $filter = new InputFilter();

		if(empty($this->firstname)) {
			$name = explode(' ', $this->lastname);
			if(sizeof($name) > 1) {
				$this->lastname = $name[0];
				$this->firstname = $name[1];
			}
		}

        $user = [
            'username' => $filter->clean($this->user, 'USERNAME'),
            'password' => $this->password,
            'name'     => $filter->clean($this->lastname, 'STRING') . ' ' . $filter->clean($this->firstname, 'STRING'),
            'email'    => $this->email,
            'groups'   => $this->userGroups,
        ];

        $userObj = User::getInstance();
        $userObj->bind($user);

        if (!$userObj->save()) {
            switch ($userObj->getError()) {
                case "JLIB_DATABASE_ERROR_USERNAME_INUSE":
                    $this->ioStyle->error("The username already exists!");
                    break;
                case "JLIB_DATABASE_ERROR_EMAIL_INUSE":
                    $this->ioStyle->error("The email address already exists!");
                    break;
                case "JLIB_DATABASE_ERROR_VALID_MAIL":
                    $this->ioStyle->error("The email address is invalid!");
                    break;
            }

            return 1;
        }

        $this->createEmundusUser($userObj,$this->firstname,$this->lastname,$this->userProfiles,$this->userEmundusGroups);

        $this->ioStyle->success("User created!");

        return Command::SUCCESS;
    }

    /**
     * Method to get groupId by groupName
     *
     * @param   string  $groupName  name of group
     *
     * @return  integer
     *
     * @since   4.0.0
     */
    protected function getGroupId($groupName)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = :groupName')
            ->bind(':groupName', $groupName);
        $db->setQuery($query);

        return $db->loadResult();
    }

    /**
     * Method to get profileId by groupName
     *
     * @param   string  $profileName  name of group
     *
     * @return  integer
     *
     * @since   4.0.0
     */
    protected function getProfileId($profileName)
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__emundus_setup_profiles'))
            ->where($db->quoteName('label') . ' = :profileName')
            ->bind(':profileName', $profileName);
        $db->setQuery($query);

        return $db->loadResult();
    }

	/**
	 * Method to get profileId by groupName
	 *
	 * @param   string  $profileName  name of group
	 *
	 * @return  integer
	 *
	 * @since   4.0.0
	 */
	protected function getEmundusGroupId($groupName)
	{
		$db    = $this->getDatabase();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__emundus_setup_groups'))
			->where($db->quoteName('label') . ' = :groupName')
			->bind(':groupName', $groupName);
		$db->setQuery($query);

		return $db->loadResult();
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
    public function getStringFromOption($option, $question): string
    {
        $answer = (string) $this->cliInput->getOption($option);

		if($this->cliInput->getOption('no-interaction') === false) {
			while (!$answer) {
				if ($option === 'password') {
					$answer = (string) $this->ioStyle->askHidden($question);
				}
				else {
					$answer = (string) $this->ioStyle->ask($question);
				}
			}
		}

        return $answer;
    }

    /**
     * Method to get a value from option
     *
     * @return  array
     *
     * @since   4.0.0
     */
    protected function getUserGroups(): array
    {
        $groups = $this->getApplication()->getConsoleInput()->getOption('usergroup');
        $db     = $this->getDatabase();

        $groupList = [];

        // Group names have been supplied as input arguments
        if (!\is_null($groups) && $groups[0]) {
            $groups = explode(',', $groups);

            foreach ($groups as $group) {
                $groupId = $this->getGroupId($group);

                if (empty($groupId)) {
                    $this->ioStyle->error("Invalid group name '" . $group . "'");
                    throw new InvalidOptionException("Invalid group name " . $group);
                }

                $groupList[] = $this->getGroupId($group);
            }

            return $groupList;
        }

        // Generate select list for user
        $query = $db->getQuery(true)
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__usergroups'))
            ->order($db->quoteName('id') . 'ASC');
        $db->setQuery($query);

        $list = $db->loadColumn();

        $choice = new ChoiceQuestion(
            'Please select a usergroup (separate multiple groups with a comma)',
            $list
        );
        $choice->setMultiselect(true);

        $answer = (array) $this->ioStyle->askQuestion($choice);

        foreach ($answer as $group) {
            $groupList[] = $this->getGroupId($group);
        }

        return $groupList;
    }

    /**
     * Method to get a value from option
     *
     * @return  array
     *
     * @since   4.0.0
     */
    protected function getUserProfiles(): array
    {
        $profiles = $this->getApplication()->getConsoleInput()->getOption('userprofiles');
        $db     = $this->getDatabase();

        $profileList = [];

        // Group names have been supplied as input arguments
        if (!\is_null($profiles) && $profiles[0]) {
            $profiles = explode(',', $profiles);

            foreach ($profiles as $profile) {
                $groupId = $this->getProfileId($profile);

                if (empty($groupId)) {
                    $this->ioStyle->error("Invalid profile name '" . $profile . "'");
                    throw new InvalidOptionException("Invalid profile name " . $profile);
                }

                $profileList[] = $this->getProfileId($profile);
            }

            return $profileList;
        }

        // Generate select list for user
        $query = $db->getQuery(true)
            ->select($db->quoteName('label'))
            ->from($db->quoteName('#__emundus_setup_profiles'))
            ->order($db->quoteName('id') . 'ASC');
        $db->setQuery($query);

        $list = $db->loadColumn();

        $choice = new ChoiceQuestion(
            'Please select a profile (separate multiple profiles with a comma)',
            $list
        );
        $choice->setMultiselect(true);

        $answer = (array) $this->ioStyle->askQuestion($choice);

        foreach ($answer as $profile) {
            $profileList[] = $this->getProfileId($profile);
        }

        return $profileList;
    }

	protected function getEmundusUserGroups(): array
	{
		$emundusGroups = $this->getApplication()->getConsoleInput()->getOption('useremundusgroups');
		$db     = $this->getDatabase();

		$groupsList = [];

		// Group names have been supplied as input arguments
		if (!\is_null($emundusGroups) && $emundusGroups[0]) {
			$emundusGroups = explode(',', $emundusGroups);

			foreach ($emundusGroups as $group) {
				$groupId = $this->getEmundusGroupId($group);

				if (empty($groupId)) {
					$this->ioStyle->error("Invalid group name '" . $group . "'");
					throw new InvalidOptionException("Invalid group name " . $group);
				}

				$groupsList[] = $this->getEmundusGroupId($group);
			}

			return $groupsList;
		}

		// Generate select list for user
		$query = $db->getQuery(true)
			->select($db->quoteName('label'))
			->from($db->quoteName('#__emundus_setup_groups'))
			->order($db->quoteName('id') . 'ASC');
		$db->setQuery($query);

		$list = $db->loadColumn();

		$choice = new ChoiceQuestion(
			'Please select an emundus group (separate multiple groups with a comma)',
			$list
		);
		$choice->setMultiselect(true);

		$answer = (array) $this->ioStyle->askQuestion($choice);

		foreach ($answer as $group) {
			$groupsList[] = $this->getEmundusGroupId($group);
		}

		return $groupsList;
	}

    protected function createEmundusUser($user,$firstname,$lastname,$profiles,$emundusGroups)
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true);
        $columns = array('user_id', 'firstname', 'lastname', 'profile', 'registerDate');
        $values = array($user->id, $db->quote(ucfirst($firstname)), $db->quote(strtoupper($lastname)), $profiles[0], $db->quote($user->registerDate));
        $query->insert($db->quoteName('#__emundus_users'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));

        $db->setQuery($query);
        if(!$db->execute())
        {
            $this->ioStyle->error("An error occurred while inserting data in #__emundus_users");
        }

        foreach ($profiles as $profile) {
            $columns = array('user_id', 'profile_id');
            $values = array($user->id, $profile);

            $query->clear()
                ->insert($db->quoteName('#__emundus_users_profiles'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));

            $db->setQuery($query);

            if(!$db->execute())
            {
                $this->ioStyle->error("An error occurred while inserting data in #__emundus_users_profiles");
            }
        }

		foreach ($emundusGroups as $group) {
			$columns = array('user_id', 'group_id');
			$values = array($user->id, $group);

			$query->clear()
				->insert($db->quoteName('#__emundus_groups'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));

			$db->setQuery($query);

			if(!$db->execute())
			{
				$this->ioStyle->error("An error occurred while inserting data in #__emundus_groups");
			}
		}
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
        $help = "<info>%command.name%</info> will add a user in Joomla and in eMundus component
		\nUsage: <info>php %command.full_name%</info>";

        $this->addOption('username', null, InputOption::VALUE_REQUIRED, 'username');
        $this->addOption('firstname', null, InputOption::VALUE_OPTIONAL, 'firstname of user');
        $this->addOption('lastname', null, InputOption::VALUE_OPTIONAL, 'lastname of user');
        $this->addOption('password', null, InputOption::VALUE_OPTIONAL, 'password');
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'email address');
        $this->addOption('usergroup', null, InputOption::VALUE_OPTIONAL, 'usergroup (separate multiple groups with comma ",")');
        $this->addOption('userprofiles', null, InputOption::VALUE_OPTIONAL, 'profiles (separate multiple groups with comma ",")');
        $this->addOption('useremundusgroups', null, InputOption::VALUE_OPTIONAL, 'emundus groups (separate multiple groups with comma ",")');
        $this->setDescription('Add a Tchooz user');
        $this->setHelp($help);
    }
}