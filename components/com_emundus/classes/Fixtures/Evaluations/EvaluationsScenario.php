<?php

namespace Tchooz\Fixtures\Evaluations;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Fixtures\Evaluations\Seeders\EvaluationSeeder;
use Tchooz\Fixtures\Evaluations\Seeders\EvaluationStepSeeder;
use Tchooz\Fixtures\Evaluations\Seeders\EvaluatorSeeder;
use Tchooz\Fixtures\SampleData\Data\FakeData;
use Tchooz\Fixtures\SampleData\Seeders\ProgramSeeder;
use Tchooz\Fixtures\SampleData\Seeders\UserSeeder;
use Tchooz\Fixtures\ScenarioInterface;
use Tchooz\Fixtures\ScenarioOption;

\defined('_JEXEC') or die;

/**
 * Evaluations over the files of the sample programs: a single-evaluator and a multiple-evaluator
 * step in each workflow covering them, a group of evaluators with the rights to evaluate, and
 * evaluation rows spread across files and evaluators. Meant to run after the sampledata scenario.
 */
final class EvaluationsScenario implements ScenarioInterface
{
	private const DEFAULT_EVALUATORS   = 5;
	private const DEFAULT_COVERAGE     = 60;
	private const DEFAULT_MAX_PER_FILE = 3;

	private DatabaseInterface $db;

	public function __construct(?DatabaseInterface $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
	}

	public static function getScenarioName(): string
	{
		return 'evaluations';
	}

	public static function getOptions(): array
	{
		return [
			new ScenarioOption('evaluators', 'Number of evaluator users to reach', self::DEFAULT_EVALUATORS),
			new ScenarioOption('coverage', 'Percentage of eligible files evaluated per step', self::DEFAULT_COVERAGE),
			new ScenarioOption('max-per-file', 'Max evaluators per file on the multiple-evaluator step', self::DEFAULT_MAX_PER_FILE),
		];
	}

	public function generate(array $options, ?callable $onProgress = null): array
	{
		$programs = (new ProgramSeeder($this->db))->loadSeeded();
		$owner    = (int) ComponentHelper::getParams('com_emundus')->get('automated_task_user', 62);

		$steps = (new EvaluationStepSeeder($this->db))->seed($programs, $owner);

		$actionIds = array_unique(array_map(fn(StepEntity $step) => $step->getType()->getActionId(), $steps['steps']));
		$userSeeder = new UserSeeder($this->db, new FakeData($this->db));
		$evaluators = (new EvaluatorSeeder($this->db, $userSeeder))->seed(
			max(1, (int) ($options['evaluators'] ?? self::DEFAULT_EVALUATORS)),
			array_column($programs, 'code'),
			$actionIds
		);

		$evaluations = (new EvaluationSeeder($this->db))->seed(
			$steps['steps'],
			$evaluators['ids'],
			min(100, max(0, (int) ($options['coverage'] ?? self::DEFAULT_COVERAGE))),
			max(1, (int) ($options['max-per-file'] ?? self::DEFAULT_MAX_PER_FILE))
		);

		return [
			'steps'              => $steps['created'],
			'criteria'           => $steps['criteria'],
			'evaluators'         => $evaluators['created'],
			'evaluations'        => $evaluations['created'],
			'evaluations filled' => $evaluations['backfilled'],
		];
	}
}
