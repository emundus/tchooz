<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checklist;

use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CheckFabrikFieldsJob extends TchoozChecklistJob
{
	private OutputInterface $output;

	private const IGNORED_TABLES = [
		'jos_emundus_evaluations',
		'jos_emundus_final_grade',
		'jos_emundus_admission'
	];

	public function __construct(
		private readonly object            $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService,
	)
	{
		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->output = $output;

		$this->checkFnumsFields($input);

		$this->checkCalcFields($input);

		$this->checkDatabaseJoinFields($input);

		$this->checkForms($input);
	}

	private function checkFnumsFields(InputInterface $input): void
	{
		$query = $this->databaseService->getDatabase()->createQuery();
		$query->clear()
			->select('COUNT(id)')
			->from($this->databaseService->getDatabase()->quoteName('jos_fabrik_elements'))
			->where('name = ' . $this->databaseService->getDatabase()->quote('fnum'))
			->andWhere('published = 1')
			->andWhere($this->databaseService->getDatabase()->quoteName('default') . ' IS NOT NULL AND ' . $this->databaseService->getDatabase()->quoteName('default') . ' != ""')
			->andWhere('eval = 1');


		$this->databaseService->getDatabase()->setQuery($query);
		$fnumElementsCount = $this->databaseService->getDatabase()->loadResult();

		$this->output->writeln('Fnum elements count: ' . $this->colors['bold'] . $fnumElementsCount . $this->colors['reset']);

		if ($fnumElementsCount > 0) {
			$helper = new QuestionHelper();
			$question = new ConfirmationQuestion('Do you want to check the fnum fields for PHP 8 compatibility? (y/n) ', false);
			if ($helper->ask($input, $this->output, $question)) {
				$this->output->writeln('Checking fnum fields for PHP 8 compatibility...');

				$query->clear()
					->select('id, ' . $this->databaseService->getDatabase()->quoteName('default'))
					->from($this->databaseService->getDatabase()->quoteName('jos_fabrik_elements'))
					->where('name = ' . $this->databaseService->getDatabase()->quote('fnum'))
					->andWhere('published = 1')
					->andWhere($this->databaseService->getDatabase()->quoteName('default') . ' IS NOT NULL AND ' . $this->databaseService->getDatabase()->quoteName('default') . ' != ""')
					->andWhere('eval = 1');

				$this->databaseService->getDatabase()->setQuery($query);
				$fnumElements = $this->databaseService->getDatabase()->loadObjectList();

				foreach ($fnumElements as $fnumElement) {
					$this->output->writeln('====================================');
					$this->output->writeln('Fnum Element ID: ' . $fnumElement->id);

					if (!empty($fnumElement->default)) {
						$defaultValue = $fnumElement->default;
						$defaultValue = str_replace(['\n', '\r', '\t'], '', $defaultValue);
						$defaultValue = trim($defaultValue);

						if (!empty($defaultValue)) {
							$this->verifyCodeCompatibility($defaultValue, $this->output, $input, 'fnum');
						} else {
							$this->output->writeln('No default value found for this fnum element.');
						}
					} else {
						$this->output->writeln('No default value found for this fnum element.');
					}
				}
				$this->output->writeln('Fnum fields check completed.');
			} else {
				$this->output->writeln('Skipping fnum fields check.');
			}
		} else {
			Log::add('No fnum elements found.', Log::WARNING, 'jerror');
			$this->output->writeln('No fnum elements found.');
		}
	}

	/**
	 * @param   InputInterface  $input
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function checkCalcFields(InputInterface $input): void
	{
		$db = $this->databaseService->getDatabase();
		$query = $db->createQuery();
		$query->clear()
			->select('COUNT(jfe.id)')
			->from($db->quoteName('jos_fabrik_elements', 'jfe'))
			->leftJoin($db->quoteName('jos_fabrik_groups', 'jfg') . ' ON jfe.group_id = jfg.id')
			->leftJoin($db->quoteName('jos_fabrik_formgroup', 'jffg') . ' ON jfg.id = jffg.group_id')
			->leftJoin($db->quoteName('jos_fabrik_forms', 'jff') . ' ON jffg.form_id = jff.id')
			->leftJoin($db->quoteName('jos_fabrik_lists', 'jfl') . ' ON jff.id = jfl.form_id')
			->where('jfe.plugin = ' . $db->quote('calc'))
			->where('jfe.published = 1')
			->andWhere('jfg.published = 1')
			->andWhere('jff.published = 1')
			->andWhere('jfl.published = 1')
			->andWhere('jfl.db_table_name NOT IN (' . implode(',', array_map([$db, 'quote'], self::IGNORED_TABLES)) . ')');

		$db->setQuery($query);
		$calcElementsCount = $db->loadResult();

		$this->output->writeln('Calc elements count : '.$this->colors['bold'].$calcElementsCount.$this->colors['reset']);

		$helper = new QuestionHelper();
		$question = new ConfirmationQuestion('Do you want to check the calc fields for PHP 8 compatibility? (y/n) ', false);

		if ($helper->ask($input, $this->output, $question)) {
			$debug = false;
			$question = new ConfirmationQuestion('Do you want to see the PHP code? (y/n) ', false);
			if ($helper->ask($input, $this->output, $question)) {
				$debug = true;
			}

			$this->output->writeln('Checking calc fields for PHP 8 compatibility...');

			$query->clear('select')
				->select('jfe.id, jfe.label, jfe.params, jfl.db_table_name');

			$db->setQuery($query);
			$calcElements = $db->loadObjectList();

			foreach ($calcElements as $calcElement) {
				$this->output->writeln('====================================');
				$this->output->writeln('Calc Element ID: ' . $calcElement->id);
				$this->output->writeln('Label: ' . $calcElement->label);
				$this->output->writeln('Table: ' . $calcElement->db_table_name);

				if (!empty($calcElement->params)) {
					$params = json_decode($calcElement->params, true);
					if (!empty($params['calc_calculation'])) {
						$calculation = $params['calc_calculation'];
						$calculation = str_replace(['\n', '\r', '\t'], '', $calculation);
						$calculation = trim($calculation);

						if ($debug) {
							$this->output->writeln('Calculation: ' . $calculation);
						}

						$this->verifyCodeCompatibility($calculation, $this->output, $input, 'calc');
					} else {
						$this->output->writeln('No calculation found in params.');
					}
				} else {
					$this->output->writeln('No params found for this calc element.');
				}

			}
			$this->output->writeln('Calc fields check completed.');
		} else {
			$this->output->writeln('Skipping calc fields check.');
		}
	}

	public function checkDatabaseJoinFields(InputInterface $input): void
	{
		$db = $this->databaseService->getDatabase();
		$query = $db->createQuery();
		$query->clear()
			->select('COUNT(jfe.id)')
			->from($db->quoteName('jos_fabrik_elements', 'jfe'))
			->leftJoin($db->quoteName('jos_fabrik_groups', 'jfg') . ' ON jfe.group_id = jfg.id')
			->leftJoin($db->quoteName('jos_fabrik_formgroup', 'jffg') . ' ON jfg.id = jffg.group_id')
			->leftJoin($db->quoteName('jos_fabrik_forms', 'jff') . ' ON jffg.form_id = jff.id')
			->leftJoin($db->quoteName('jos_fabrik_lists', 'jfl') . ' ON jff.id = jfl.form_id')
			->where('jfe.plugin = ' . $db->quote('databasejoin'))
			->andWhere('jfe.published = 1')
			->andWhere('jfg.published = 1')
			->andWhere('jff.published = 1')
			->andWhere('jfl.published = 1')
			->andWhere('json_extract(jfe.params, \'$.database_join_where_sql\') LIKE ' . $db->quote('%\_\_\_%'))
			->andWhere('jfl.db_table_name NOT IN (' . implode(',', array_map([$db, 'quote'], self::IGNORED_TABLES)) . ')');

		$db->setQuery($query);
		$databaseJoinElementsCount = $db->loadResult();

		$this->output->writeln('Database Join with ajax elements elements count : '.$this->colors['bold'].$databaseJoinElementsCount.$this->colors['reset']);
		if ($databaseJoinElementsCount > 0) {
			$helper = new QuestionHelper();
			$question = new ConfirmationQuestion('Do you want to check the database join fields for PHP 8 compatibility? (y/n) ', false);

			if ($helper->ask($input, $this->output, $question)) {
				$this->output->writeln('Checking database join fields for PHP 8 compatibility...');

				$query->clear('select')
					->select('jfe.id, jfe.label, jfe.params, jfl.db_table_name');

				$db->setQuery($query);
				$databaseJoinElements = $db->loadObjectList();

				foreach ($databaseJoinElements as $element) {
					$this->output->writeln('====================================');
					$this->output->writeln('Database Join Element ID: ' . $element->id);
					$this->output->writeln('Label: ' . $element->label);
					$this->output->writeln('Table: ' . $element->db_table_name);

					if (!empty($element->params)) {
						$params = json_decode($element->params, true);
						if (!empty($params['database_join_where_sql'])) {
							$whereSql = $params['database_join_where_sql'];
							$whereSql = str_replace(['\n', '\r', '\t'], '', $whereSql);
							$whereSql = trim($whereSql);

							$this->output->writeln('Where SQL: ' . $whereSql);
						} else {
							$this->output->writeln('No where SQL found in params.');
						}
					} else {
						$this->output->writeln('No params found for this database join element.');
					}

				}
				$this->output->writeln('Database join fields check completed.');
			} else
			{
				$this->output->writeln('Skipping database join fields check.');
			}
		} else {
			$this->output->writeln('No database join elements with ajax found.');
		}
	}

	/**
	 * @param   InputInterface  $input
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function checkForms(InputInterface $input): void
	{
		$query = $this->databaseService->getDatabase()->createQuery();
		$query->clear()
			->select('COUNT(jff.id)')
			->from($this->databaseService->getDatabase()->quoteName('jos_fabrik_forms', 'jff'))
			->leftJoin($this->databaseService->getDatabase()->quoteName('jos_fabrik_lists', 'jfl'), 'jfl.form_id = jff.id')
			->where('jff.published = 1')
			->andWhere('jff.params LIKE ' . $this->databaseService->getDatabase()->quote('%"php"%'))
			->andWhere('jfl.published = 1')
			->andWhere('jfl.db_table_name NOT IN (' . implode(',', array_map([$this->databaseService->getDatabase(), 'quote'], self::IGNORED_TABLES)) . ')');

		$this->databaseService->getDatabase()->setQuery($query);
		$formsWithPhpCount = $this->databaseService->getDatabase()->loadResult();

		$this->output->writeln('Forms with PHP code count: ' . $this->colors['bold'] . $formsWithPhpCount . $this->colors['reset']);

		$helper = new QuestionHelper();
		$question = new ConfirmationQuestion('Do you want to check the forms for PHP 8 compatibility? (y/n) ', false);
		if ($helper->ask($input, $this->output, $question)) {
			$this->output->writeln('Checking forms for PHP 8 compatibility...');

			$debug = false;
			// ask if user wants to see the php code
			$question = new ConfirmationQuestion('Do you want to see the PHP code? (y/n) ', false);
			if ($helper->ask($input, $this->output, $question)) {
				$debug = true;
			}

			$query->clear('select')
				->select('jff.id, jff.label, jff.params');

			$this->databaseService->getDatabase()->setQuery($query);
			$forms = $this->databaseService->getDatabase()->loadObjectList();

			foreach ($forms as $form) {
				$this->output->writeln('====================================');
				$this->output->writeln('Form ID: ' . $form->id);
				$this->output->writeln('Label: ' . $form->label);

				if (!empty($form->params)) {
					$params = json_decode($form->params, true);
					$phpPluginIndexes = array_keys($params['plugins'], 'php', true);

					if (!empty($phpPluginIndexes)) {
						$this->output->writeln('PHP plugins found indexes: ' . implode(', ', $phpPluginIndexes));

						foreach ($phpPluginIndexes as $index) {
							if (is_array($params['curl_code'])) {
								if (isset($params['curl_code'][$index])) {
									$phpCode = $params['curl_code'][$index];
									if (!empty($phpCode)) {
										$published = $params['plugin_state'][$index] ?? 1;
										$this->output->writeln('Published: ' . ($published ? 'Yes' : 'No'));

										if ($debug) {
											$this->output->writeln('PHP code: ' . $phpCode);
										}
										$this->verifyCodeCompatibility($phpCode, $this->output, $input, 'form');
									} else {
										$this->output->writeln('No PHP code found for this plugin index.');
									}
								} else {
									$this->output->writeln('No PHP code found for this plugin index.');
								}
							} else if (!empty($params['curl_code'])) {
								$published = $params['plugin_state'][$index] ?? 1;
								$this->output->writeln('Published: ' . ($published ? 'Yes' : 'No'));
								if ($debug) {
									$this->output->writeln('PHP code: ' . $params['curl_code']);
								}
								$this->verifyCodeCompatibility($params['curl_code'], $this->output, $input, 'form');
							} else {
								$this->output->writeln('No PHP code found for this form.');
							}
						}
					} else {
						$this->output->writeln('No PHP plugins found in params.');
					}
				} else {
					$this->output->writeln('No params found for this form.');
				}
			}
			$this->output->writeln('Forms check completed.');
		} else {
			$this->output->writeln('Skipping forms check.');
		}

	}

	public static function getJobName(): string {
		return 'Fabrik Verifications';
	}

	public static function getJobDescription(): ?string {
		return 'Helps you to standardize Fabrik forms (fnum, calc, custom plugins etc.).';
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}