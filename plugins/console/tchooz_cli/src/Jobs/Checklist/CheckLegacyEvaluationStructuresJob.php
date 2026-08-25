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

class CheckLegacyEvaluationStructuresJob extends TchoozChecklistJob
{
	private OutputInterface $output;

	private const CODE_COLUMNS = [
		'calc',
		'code',
		'request',
		'eval',
	];

	private const CHECKS = [
		[
			'label' => 'Calc fields based on the old management form structure',
			'query' => <<<'SQL'
			SELECT jfe.id as element_id, jfe.parent_id, jfe.group_id, jffg.form_id, jfe.name, jfl.db_table_name, JSON_EXTRACT(jfe.params, '$.calc_calculation') AS calc
			    FROM jos_fabrik_elements jfe
			    LEFT JOIN jos_fabrik_formgroup jffg ON jffg.group_id = jfe.group_id
			    LEFT JOIN jos_fabrik_groups jfg ON jfg.id = jfe.group_id
			    LEFT JOIN jos_fabrik_forms jff ON jff.id = jffg.form_id
			    LEFT JOIN jos_fabrik_lists jfl ON jfl.form_id = jff.id
			        WHERE jfe.plugin = 'calc'
			        AND JSON_VALID(jfe.params)
			        # Adapter le WHERE NOT LIKE sur emundus_evaluations si vous avez trop de tables et donc qu'elles ne commencent pas par 0
			        AND (
			            (JSON_EXTRACT(jfe.params, '$.calc_calculation') LIKE '%emundus_evaluations%' AND JSON_EXTRACT(jfe.params, '$.calc_calculation') NOT LIKE '%emundus_evaluations_0%' AND JSON_EXTRACT(jfe.params, '$.calc_calculation') NOT LIKE '%$db->quote(\'%emundus_evaluations%\')%') OR
			            (JSON_EXTRACT(jfe.params, '$.calc_calculation') LIKE '%emundus_final_grade%') OR
			            (JSON_EXTRACT(jfe.params, '$.calc_calculation') LIKE '%emundus_admission%')
			            )
			        AND (jfe.published = 1 AND jfg.published = 1 AND jff.published = 1 AND jfl.published = 1)
			            ORDER BY jfe.name, jfe.parent_id, jfe.id
			SQL
		],
		[
			'label' => 'Field elements with a default value based on the old management form structure',
			'query' => <<<'SQL'
			SELECT jfe.id as element_id, jfe.parent_id, jfe.group_id, jffg.form_id, jfe.name, jfl.db_table_name, `default`
			    FROM jos_fabrik_elements jfe
			    LEFT JOIN jos_fabrik_formgroup jffg ON jffg.group_id = jfe.group_id
			    LEFT JOIN jos_fabrik_groups jfg ON jfg.id = jfe.group_id
			    LEFT JOIN jos_fabrik_forms jff ON jff.id = jffg.form_id
			    LEFT JOIN jos_fabrik_lists jfl ON jfl.form_id = jff.id
			        WHERE jfe.plugin = 'field'
			        # Adapter le WHERE NOT LIKE sur emundus_evaluations si vous avez trop de tables et donc qu'elles ne commencent pas par 0
			        AND (
			            (`default` LIKE '%emundus_evaluations%' AND `default` NOT LIKE '%emundus_evaluations_0%' AND `default` NOT LIKE '%$db->quote(\'%emundus_evaluations%\')%') OR
			            (`default` LIKE '%emundus_final_grade%') OR
			            (`default` LIKE '%emundus_admission%')
			            )
			        AND (jfe.published = 1 AND jfg.published = 1 AND jff.published = 1 AND jfl.published = 1)
			            ORDER BY jfe.name, jfe.parent_id, jfe.id
			SQL
		],
		[
			'label' => 'Management form JS actions to fix',
			'query' => <<<'SQL'
			SELECT jfe.id AS element_id, jfe.parent_id, jfe.group_id, jfl.form_id, jfe.name, jfl.db_table_name, jfjs.code
			    FROM jos_fabrik_lists jfl
			    LEFT JOIN jos_fabrik_forms jff ON jff.id = jfl.form_id
			    LEFT JOIN jos_fabrik_formgroup jffg ON jffg.form_id = jfl.form_id
			    LEFT JOIN jos_fabrik_groups jfg ON jfg.id = jffg.group_id
			    LEFT JOIN jos_fabrik_elements jfe ON jfe.group_id = jffg.group_id
			    LEFT JOIN jos_fabrik_jsactions jfjs ON jfjs.element_id = jfe.id
			        WHERE jfl.db_table_name LIKE 'jos_emundus_evaluations_%'
			        # Adapter le WHERE NOT LIKE sur emundus_evaluations si vous avez trop de tables et donc qu'elles ne commencent pas par 0
			        AND (
			            (jfjs.code LIKE '%emundus_evaluations%' AND jfjs.code NOT LIKE '%emundus_evaluations_0%') OR
			            (jfjs.code LIKE '%emundus_final_grade%') OR
			            (jfjs.code LIKE '%emundus_admission%')
			            )
			        AND (jfe.published = 1 AND jfg.published = 1 AND jff.published = 1 AND jfl.published = 1)
			          ORDER BY jfe.name, jfe.parent_id, jfe.id
			SQL
		],
		[
			'label' => 'Form plugins based on the old management form structure',
			'query' => <<<'SQL'
			SELECT jff.id as form_id
			    FROM jos_fabrik_forms jff
			    LEFT JOIN jos_fabrik_lists jfl ON jfl.form_id = jff.id
			        # Adapter le WHERE NOT LIKE sur emundus_evaluations si vous avez trop de tables et donc qu'elles ne commencent pas par 0
			        WHERE (
			            (jff.params LIKE '%emundus_evaluations%' AND jff.params NOT LIKE '%emundus_evaluations_0%' AND jff.params NOT LIKE '%$db->quote(\'%emundus_evaluations%\')%') OR
			            (jff.params LIKE '%emundus_final_grade%') OR
			            (jff.params LIKE '%emundus_admission%')
			            )
			        AND (jff.published = 1 AND jfl.published = 1)
			            ORDER BY form_id
			SQL
		],
		[
			'label' => 'Emundus tags based on the old management form structure',
			'query' => <<<'SQL'
			SELECT id, tag, request
			    FROM jos_emundus_setup_tags
			        WHERE request LIKE 'php|%'
			        # Adapter le WHERE NOT LIKE sur emundus_evaluations si vous avez trop de tables et donc qu'elles ne commencent pas par 0
			        AND (
			            (request LIKE '%emundus_evaluations%' AND request NOT LIKE '%emundus_evaluations_0%' AND request NOT LIKE '%$db->quote(\'%emundus_evaluations%\')%') OR
			            (request LIKE '%emundus_final_grade%') OR
			            (request LIKE '%emundus_admission%')
			            )
			SQL
		],
		[
			'label' => 'Widgets based on the old management form structure',
			'query' => <<<'SQL'
			SELECT id, eval
			    FROM jos_emundus_widgets
			        WHERE eval LIKE 'php|%'
			        # Adapter le WHERE NOT LIKE sur emundus_evaluations si vous avez trop de tables et donc qu'elles ne commencent pas par 0
			        AND (
			            (eval LIKE '%emundus_evaluations%' AND eval NOT LIKE '%emundus_evaluations_0%' AND eval NOT LIKE '%$db->quote(\'%emundus_evaluations%\')%') OR
			            (eval LIKE '%emundus_final_grade%') OR
			            (eval LIKE '%emundus_admission%')
			            )
			SQL
		],
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

		$helper = new QuestionHelper();

		foreach (self::CHECKS as $check) {
			$this->runCheck($check['label'], $check['query']);

			$question = new ConfirmationQuestion('Press enter to continue', true);
			$helper->ask($input, $output, $question);
		}

		Log::add('Legacy evaluation structures check completed.', Log::INFO, 'tchooz');
	}

	/**
	 * Exécute une requête de contrôle et affiche les lignes trouvées.
	 *
	 * @param   string  $label
	 * @param   string  $query
	 *
	 * @return void
	 */
	private function runCheck(string $label, string $query): void
	{
		$db = $this->databaseService->getDatabase();

		$this->output->writeln('====================================');
		$this->output->writeln($label);

		try {
			$db->setQuery($query);
			$rows = $db->loadAssocList();
		} catch (\Exception $e) {
			$this->output->writeln('<error>Query failed: ' . $e->getMessage() . '</error>');

			return;
		}

		if (empty($rows)) {
			$this->output->writeln('<info>Nothing to fix.</info>');

			return;
		}

		$this->output->writeln('<error>' . count($rows) . ' row(s) to fix:</error>');

		foreach ($rows as $row) {
			$identifiers = [];

			foreach ($row as $column => $value) {
				if (in_array($column, self::CODE_COLUMNS)) {
					continue;
				}

				$identifiers[] = $column . ': ' . $value;
			}

			$this->output->writeln(implode(' | ', $identifiers));
		}
	}

	public static function getJobName(): string {
		return 'Legacy Evaluation Structures';
	}

	public static function getJobDescription(): ?string {
		return 'Finds code still referencing the old evaluation tables after migration.';
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}
