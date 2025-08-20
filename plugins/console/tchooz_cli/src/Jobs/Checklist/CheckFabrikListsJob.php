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
use Gantry\Framework\Exception;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CheckFabrikListsJob extends TchoozChecklistJob
{
	private OutputInterface $output;

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

		$deprecated_tables = [
			'jos_emundus_evaluations',
			'jos_emundus_admission',
			'jos_emundus_final_grade'
		];

		$fabrik_lists = $this->getDeprecatedFabrikLists($deprecated_tables);

		if (!empty($fabrik_lists)) {
			$output->writeln(count($fabrik_lists) . ' Fabrik lists associated to deprecated tables found:');
			foreach ($fabrik_lists as $list) {
				$output->writeln('====================================');
				$output->writeln('List: ' . $list->label . ' (' . $list->id . ')');
				$output->writeln('Table: ' . $list->db_table_name);

				$menus = $this->getMenusUsingList((int)$list->id, (int)$list->form_id);
				if (!empty($menus)) {
					$output->writeln('This list is used in the following menus:');
					foreach ($menus as $menu) {
						$output->writeln('Menu: ' . $menu->title . ' (' . $menu->id . ' - ' . $menu->menutype. ')');
						$output->writeln('Path: ' . $menu->path);
					}
				} else {
					$output->writeln('This list is not used in any menu.');
				}

				// ask if the user wants to unpublish the list
				$helper = new QuestionHelper();
				$question = new ConfirmationQuestion('Unpublish this list? (y/n): ', false);
				if ($helper->ask($input, $output, $question)) {
					$db = $this->databaseService->getDatabase();
					$query = $db->createQuery();
					$query->update($db->quoteName('#__fabrik_lists'))
						->set($db->quoteName('published') . ' = 0')
						->where($db->quoteName('id') . ' = ' . (int)$list->id);

					try {
						$db->setQuery($query);
						$db->execute();
						$output->writeln('List unpublished successfully.');
					} catch (\Exception $e) {
						$this->logger->error('Error while unpublishing list: ' . $e->getMessage());
					}
				}
				$output->writeln('====================================');
			}
		} else {
			$output->writeln('No Fabrik lists associated to deprecated tables found.');
		}
	}

	private function getDeprecatedFabrikLists(array $deprecated_table_names): array
	{
		$lists = [];

		if (!empty($deprecated_table_names)) {
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();

			$query->select('*')
				->from($db->quoteName('#__fabrik_lists'))
				->where($db->quoteName('db_table_name') . ' IN (' . implode(',', array_map([$db, 'quote'], $deprecated_table_names)) . ')')
				->andWhere($db->quoteName('published') . ' = 1');

			try {
				$db->setQuery($query);
				$lists = $db->loadObjectList();
			} catch (\Exception $e) {
				$this->logger->error('Error while fetching fabrik lists: ' . $e->getMessage());
			}
		}

		return $lists;
	}

	private function getMenusUsingList(int $list_id, int $form_id): array
	{
		$menus = [];

		if (!empty($list_id)) {
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();

			$query->select('*')
				->from('#__menu')
				->where('link like ' . $db->quote('index.php?option=com_fabrik&view=list&listid=' . $list_id))
				->orWhere('link like ' . $db->quote('index.php?option=com_fabrik&view=form&formid=' . $form_id));

			try {
				$db->setQuery($query);
				$menus = $db->loadObjectList();
			} catch (\Exception $e) {
				$this->logger->error('Error while fetching menus using list: ' . $e->getMessage());
			}
		}

		return $menus;
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
