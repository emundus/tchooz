<?php
namespace Emundus\Plugin\Console\Tchooz\CliCommand\Commands;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class TchoozConfigCommand extends AbstractCommand
{
    use DatabaseAwareTrait;

    /**
     * The default command name
     *
     * @var    string
     * @since  4.0.0
     */
    protected static $defaultName = 'tchooz:config';

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
     * The key
     *
     * @var    string
     *
     * @since  4.0.0
     */
    private $keys;

    /**
     * The value
     *
     * @var    string
     *
     * @since  4.0.0
     */
    private $values;

	/**
	 * The old value
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	private $old_values;

	/**
	 * The component to update
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	private $component;

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
        $this->ioStyle->title('Update Tchooz configuration');

	    $this->keys       = $this->getStringFromOption('keys', 'Please enter a key (separate multiple keys with a comma)');
		$this->values = $this->getStringFromOption('values', 'Please enter a value (separate multiple values with a comma)');
		$this->old_values = $this->getStringFromOption('old_values', '[Optional] Please enter a old value to check before update',false);
		$this->component = $this->getStringFromOption('component', '[Optional] Please enter the component to update',false);

		if(!empty($this->keys) && !empty($this->values)) {
			if(empty($this->component)) {
				$this->component = 'com_emundus';
			}
			
			$this->keys = explode(',', $this->keys);
			$this->values = explode(',', $this->values);
			if(!empty($this->old_values)) {
				$this->old_values = explode(',', $this->old_values);
			} else {
				$this->old_values = [];
			}

			foreach ($this->keys as $index => $key) {
				$old_value = !empty($this->old_values) ? $this->old_values[$index] : null;

				$this->updateExtensionParam($key, $this->values[$index], $old_value, $this->component);
			}
		}

        $this->ioStyle->success("Configuration updated!");

        return Command::SUCCESS;
    }

	private function updateExtensionParam($param, $value, $old_value_checking = null, $component = 'com_emundus')
	{
		$updated = false;
		$config  = ComponentHelper::getParams($component);

		if (!empty($old_value_checking))
		{
			$old_value = $config->get($param, '');
			if (empty($old_value) || $old_value == $old_value_checking)
			{
				$config->set($param, $value);
			}
		}
		else
		{
			$config->set($param, $value);
		}

		$componentid = ComponentHelper::getComponent($component)->id;
		$db          = Factory::getContainer()->get('DatabaseDriver');
		$query       = $db->getQuery(true);

		try
		{
			$query->update('#__extensions')
				->set($db->quoteName('params') . ' = ' . $db->quote($config->toString()))
				->where($db->quoteName('extension_id') . ' = ' . $db->quote($componentid));
			$db->setQuery($query);
			$updated = $db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Failed to update extension parameter ' . $param . ' with value ' . $value . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $updated;
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

		if($this->cliInput->getOption('no-interaction') === false) {
			if($required) {
				while (!$answer) {
					$answer = (string) $this->ioStyle->ask($question);
				}
			} else {
				if (!$answer) {
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
        $help = "<info>%command.name%</info> will update configuration for Tchooz
		\nUsage: <info>php %command.full_name%</info>";

        $this->addOption('keys', null, InputOption::VALUE_REQUIRED, 'Name of parameter to update (separate multiple values by comma)');
        $this->addOption('values', null, InputOption::VALUE_REQUIRED, 'Value to update (separate multiple values by comma)');
        $this->addOption('old_values', null, InputOption::VALUE_OPTIONAL, 'Fill a old value to check before update (separate multiple values by comma)');
        $this->addOption('component', null, InputOption::VALUE_OPTIONAL, 'The component to update (com_emundus by default)');
        $this->setDescription('Update Tchooz configuration');
        $this->setHelp($help);
    }
}