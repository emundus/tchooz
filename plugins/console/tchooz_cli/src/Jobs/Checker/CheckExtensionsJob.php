<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checker;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckExtensionsJob extends TchoozJob
{
	private $extensions = [
		'miniorange_saml' => [
			'label' => 'MiniOrange SAML',
			'name'  => 'com_miniorange_saml',
		],
		'dpcalendar'      => [
			'label' => 'DP Calendar',
			'name'  => 'com_dpcalendar',
		],
		'eventbooking'    => [
			'label' => 'Event Booking',
			'name'  => 'com_eventbooking',
		],
		'loginguard'      => [
			'label' => 'Login Guard',
			'name'  => 'com_loginguard',
		],
		'yootheme' => [
			'label' => 'Yootheme',
			'name'  => 'yootheme',
		],
		'evaluations' => [
			'label' => 'Evaluations',
			'name'  => 'mod_emundus_evaluations',
		],
	];

	public function __construct(
		private readonly object $logger,
		private readonly DatabaseService $databaseService
	)
	{
		$this->allowFailure = true;

		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		// Get creation date of the project
		$output->writeln('🔄'.$this->colors['yellow'].'Following extensions are deprecated, if some enabled please be sure of these usages before migrate platform : '.$this->colors['reset']);
		$this->checkExtensions($output);
		$output->writeln('');
	}

	private function checkExtensions(OutputInterface $output): void
	{
		$query = $this->databaseService->getDatabase()->getQuery(true);

		$stateIcon = [
			1 => $this->colors['green'] . '✔' . $this->colors['reset'],
			0 => $this->colors['red'] . '🚫' . $this->colors['reset']
		];

		$stateText = [
			1 => 'Activated',
			0 => 'Deactivated'
		];

		foreach ($this->extensions as $extension) {
			$query->clear()
				->select('enabled')
				->from( $this->databaseService->getDatabase()->quoteName('jos_extensions'))
				->where('element = ' . $this->databaseService->getDatabase()->quote($extension['name']));
			$this->databaseService->getDatabase()->setQuery($query);
			$enabled = $this->databaseService->getDatabase()->loadResult();

			$output->writeln($stateIcon[$enabled].' '.$extension['label'].' : '.$stateText[$enabled]);
		}
	}

	public static function getJobName(): string {
		return 'Extensions';
	}

	public static function getJobDescription(): ?string {
		return 'Check if some extensions installed are deprecated in Tchooz v2';
	}
}