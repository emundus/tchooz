<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checklist;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CheckCustomEventsJob extends TchoozChecklistJob
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

		$db = $this->databaseService->getDatabase();
		$query = $db->getQuery(true);

		$query->select('params')
			->from('#__extensions')
			->where('element = ' . $db->quote('custom_event_handler'))
			->where('type = ' . $db->quote('plugin'))
			->where('folder = ' . $db->quote('emundus'));

		try {
			$db->setQuery($query);
			$params = $db->loadResult();

			if (!empty($params)) {
				$params = json_decode($params, true);

				if (empty($params['event_handlers'])) {
					$this->output->writeln('No custom event handler(s) found.');
				} else {
					$this->output->writeln(sizeof($params['event_handlers']) . ' custom event handlers found:');

					foreach($params['event_handlers'] as $eventHandler) {
						$this->output->writeln('====================================');
						$this->output->writeln('Event handler: ' . $eventHandler['event']);
						$this->output->writeln('Description: ' . $eventHandler['description']);

						if (empty($eventHandler['code'])) {
							$this->output->writeln('Code: No code found, maybe delete this event handler.');


						} else {
							$this->output->writeln('Event compatibility test... ');
							$this->verifyCodeCompatibility($eventHandler['code'], $output, $input, 'event_handler');
						}
					}
				}
			}
		} catch (\Exception $e) {
			throw new \Exception('Error while fetching custom event handler plugin params: ' . $e->getMessage());
		}
	}

	public static function getJobName(): string {
		return 'Custom Events';
	}

	public static function getJobDescription(): ?string {
		return 'Helps to check if custom event handlers are correctly configured or can be replaced by new configured event handlers.';
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}