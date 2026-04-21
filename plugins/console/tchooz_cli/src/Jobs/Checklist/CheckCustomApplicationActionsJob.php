<?php

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checklist;

use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCustomApplicationActionsJob extends TchoozChecklistJob
{
	private OutputInterface $output;

	public function __construct(
		private readonly object          $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService
	)
	{
		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->output = $output;
		$this->output->writeln('Verifying module application actions...');

		$db = $this->databaseService->getDatabase();
		$query = $db->createQuery();

		$query->select('params')
			->from('#__modules')
			->where('module = ' . $db->quote('mod_emundus_applications'))
			->andWhere('published = 1');

		$db->setQuery($query);
		$params = $db->loadResult();

		if (!empty($params))
		{
			$params = json_decode($params, true);

			if (!empty($params['mod_em_application_custom_actions']))
			{
				$this->output->writeln('Found ' . sizeof($params['mod_em_application_custom_actions']) . ' application action(s) to check.');

				foreach($params['mod_em_application_custom_actions'] as $action)
				{
					$this->output->writeln('====================================');
					$this->output->writeln('Action: ' . $action['mod_em_application_custom_action_label']);

					if (empty($action['display_condition']))
					{
						$this->output->writeln('Code: No code found, replace this action in global settings.');
					}
					else
					{
						$this->output->writeln('Code: Code found, check if global settings can replace this condition, inform Tech Team otherwise.');
					}
				}
			}
			else
			{
				$this->output->writeln('No application action(s) found. Perfect !');
			}
		}
	}

	public static function getJobName(): string {
		return 'Module Application Actions';
	}

	public static function getJobDescription(): ?string {
		return 'Checkhealth job to check if you have custom application actions defined in your project. If you have, check if they can be replaced by global settings, inform Tech Team otherwise.';
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}