<?php
namespace Emundus\Plugin\Console\Tchooz\CliCommand;

defined('_JEXEC') or die;

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

class TchoozMigrateCommand extends AbstractCommand
{
    use DatabaseAwareTrait;

    /**
     * The default command name
     *
     * @var    string
     * @since  4.0.0
     */
    protected static $defaultName = 'tchooz:migrate';

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

	private $pattern = [
		// DATABASE
		'JFactory::getDbo()' => 'JFactory::getContainer()->get(\'DatabaseDriver\')',

		// INPUT
		'JFactory::getApplication()->input' => 'JFactory::getApplication()->getInput()',
		'$app->input' => '$app->getInput()',
		'$mainframe->input' => '$mainframe->getInput()',
		'JRequest::getVar' => 'JFactory::getApplication()->getInput()->get',

		// SESSION
		'JFactory::getSession()' => 'JFactory::getApplication()->getSession()',

		// USER
		'JFactory::getUser()' => 'JFactory::getApplication()->getIdentity()',

		// CONFIG
		'getCfg' => 'get',
		'JFactory::getConfig()' => 'JFactory::getApplication()->get(',
	];

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
        $this->ioStyle->title('Migrate to Joomla 4!');

		$types = [
			'fabrik_elements',
			'fabrik_forms',
		];
		foreach ($types as $type) {
			$this->getCode($type);
		}

        $this->ioStyle->success("Migration completed successfully!");

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
        $help = "<info>%command.name%</info> will migrate your Joomla 3.x site to Joomla 4.x.\n
		\nUsage: <info>php %command.full_name%</info>";

        $this->setDescription('Migrate your Joomla 3.x site to Joomla 4.x.');
        $this->setHelp($help);
    }

	protected function getCode($type): array
	{
		$results = [];

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		switch ($type) {
			case 'fabrik_elements':

				$query->clear()
					->select([$db->quoteName('id'),$db->quoteName('default'),$db->quoteName('params')])
					->from($db->quoteName('#__fabrik_elements'));
				$db->setQuery($query);
				$elements = $db->loadAssocList();

				foreach ($elements as $element) {
					$to_update = false;
					$query->clear();

					if(!empty($element['default'])) {
						$to_update = true;
						$element['default'] = $this->replace($element['default']);
						$query->set($db->quoteName('default') . ' = ' . $db->quote($element['default']));
					}

					if(!empty($element['params'])) {
						$params = json_decode($element['params'],true);
						if(!empty($params['calc_calculation'])) {
							$to_update = true;
							$params['calc_calculation'] = $this->replace($params['calc_calculation'],false);
						}
						
						if(!empty($params['validations'])) {
							foreach ($params['validations']['plugin'] as $key => $validation) {
								if($validation == 'php' && (!empty($params['php-code'][$key]) || !empty($params['php-validation_condition'][$key]))) {
									$to_update = true;
									if(!empty($params['php-code'][$key])) {
										$params['php-code'][$key] = $this->replace($params['php-code'][$key], false);
									}
									if(!empty($params['php-validation_condition'][$key])) {
										$params['php-validation_condition'][$key] = $this->replace($params['php-validation_condition'][$key], false);
									}
								}

								if($validation == 'notempty' && !empty($params['notempty-validation_condition'][$key])) {
									$to_update = true;
									$params['notempty-validation_condition'][$key] = $this->replace($params['notempty-validation_condition'][$key],false);
								}
							}
						}

						if(!empty($params['rollover']) && $params['tipseval'] == 1) {
							$to_update = true;
							$params['rollover'] = $this->replace($params['rollover'],false);
						}

						$query->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)));
					}

					if($to_update) {
						$query->update($db->quoteName('#__fabrik_elements'))
							->where($db->quoteName('id') . ' = ' . $db->quote($element['id']));
						$db->setQuery($query);

						$results['fabrik_elements'][$element['id']]['status'] = $db->execute();
					}
				}
				break;

			case 'fabrik_forms':
				$query->clear()
					->select([$db->quoteName('id'),$db->quoteName('params')])
					->from($db->quoteName('#__fabrik_forms'));
				$db->setQuery($query);
				$forms = $db->loadAssocList();

				foreach ($forms as $form) {
					$to_update = false;
					$query->clear();

					if(!empty($form['params'])) {
						$params = json_decode($form['params'], true);

						if(!empty($params['plugins'])) {
							foreach ($params['plugins'] as $key => $plugin) {
								if($plugin == 'php' && !empty($params['curl_code'][$key])) {
									$to_update = true;
									$params['curl_code'][$key] = $this->replace($params['curl_code'][$key], false);
								}
							}
						}

						if($to_update) {
							$query->update($db->quoteName('#__fabrik_forms'))
								->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
								->where($db->quoteName('id') . ' = ' . $db->quote($form['id']));
							$db->setQuery($query);

							$results['fabrik_forms'][$form['id']]['status'] = $db->execute();
						}
					}
				}

				break;
		}

		return $results;
	}

	protected function replace($code,$breakline_interpreter = true): string
	{
		$result = str_ireplace(array_keys($this->pattern), array_values($this->pattern), $code);

		if($breakline_interpreter) {
			return str_replace('\n', "\n", $result);
		} else {
			return $result;
		}
	}
}