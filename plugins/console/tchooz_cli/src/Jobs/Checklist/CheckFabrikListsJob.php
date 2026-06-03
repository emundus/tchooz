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
use Gantry\Framework\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckFabrikListsJob extends TchoozChecklistJob
{

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
		$this->checkListsUsingInlineEdit($output);
	}


	/**
	 * Checks for Fabrik lists using inline edit and disables it if found. Inline edit is a deprecated plugin.
	 *
	 * @param OutputInterface $output
	 * @return void
	 */
	private function checkListsUsingInlineEdit(OutputInterface $output): void
	{
		$db = $this->databaseService->getDatabase();
		$query = $db->createQuery();

		$query->select('id, params')
			->from($db->quoteName('#__fabrik_lists', 'jfl'))
			->where($db->quoteName('params') . ' LIKE ' . $db->quote('%inlineedit%'))
			->andWhere($db->quoteName('published') . ' = 1');

		try {
			$db->setQuery($query);
			$lists = $db->loadAssocList();

			if (!empty($lists)) {
				$output->writeln('There are ' . sizeof($lists) . ' Fabrik lists using inline edit.');

				foreach ($lists as $list) {
					$params = json_decode($list['params'], true);

					$output->writeln('List ID: ' . $list['id']);

					if (is_array($params['plugins'])) {
						$index = array_search('inlineedit', $params['plugins']);

						if ($params['plugin_state'][$index] == 1) {
							$output->writeln('Inline edit is enabled for this list.');

							$params['plugin_state'][$index] = 0;
							$query->clear()
								->update($db->quoteName('#__fabrik_lists'))
								->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
								->where($db->quoteName('id') . ' = ' . (int)$list['id']);

							$db->setQuery($query);
							$updated = $db->execute();

							if ($updated) {
								$output->writeln('Inline edit has been disabled for this list.');
							} else {
								$output->writeln('Failed to disable inline edit for this list.');
							}
						} else {
							$output->writeln('Inline edit is not enabled for this list.');
						}
					}
				}
			} else {
				$output->writeln('No Fabrik lists using inline edit found.');
			}

		} catch (Exception $e) {
			$this->logger->error('Error while checking fabrik lists using inline edit: ' . $e->getMessage());
			return;
		}
	}

	public static function getJobName(): string {
		return 'Fabrik Lists';
	}

	public static function getJobDescription(): ?string {
		return 'Helps you to retrieve fabrik lists that are linked to deprecated tables.';
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}
