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
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CheckJumiJob extends TchoozChecklistJob
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
		$this->output = $output;

		// check jumi modules nb
		$db = $this->databaseService->getDatabase();
		$query = $db->getQuery(true);

		$query->select('*')
			->from('#__modules')
			->where('module LIKE ' . $db->quote('mod_jumi'));

		$db->setQuery($query);
		$modules = $db->loadObjectList();

		if (!empty($modules)) {
			$nb_modules = count($modules);
			$nb_published_modules = array_reduce($modules, function ($carry, $module) {
				return $carry + $module->published;
			}, 0);

			$this->output->writeln('You have Jumi ' . $nb_modules . ' modules in your project. Remove them if possible.');
			$this->output->writeln('You have ' . $nb_published_modules . ' published Jumi modules in your project.');

			foreach($modules as $module) {
				$this->output->writeln('====================='); // separator
				$this->output->writeln('Module ' . $module->title);

				if ($module->published) {
					$this->output->writeln('Published : Yes');
				} else {
					$this->output->writeln('Published : No');
				}

				if (!empty($module->content)) {
					$this->output->writeln('Content : Yes');

					// count lines
					$lines = explode("\n", $module->content);
					$nb_lines = count($lines);

					$this->output->writeln('Module ' . $module->title . ' has ' . $nb_lines . ' lines.');

					// check if it contains php
					$contains_php = false;
					foreach($lines as $line) {
						if (str_contains($line, '<?php')) {
							$contains_php = true;
							break;
						}
					}

					if ($contains_php) {
						$this->output->writeln('PHP Content : Yes');
					} else {
						$this->output->writeln('PHP Content : No');
					}

					$contains_js = false;
					foreach($lines as $line) {
						if (str_contains($line, '<script')) {
							$contains_js = true;
							break;
						}
					}

					if ($contains_js) {
						$this->output->writeln('JS Content : Yes');
					} else {
						$this->output->writeln('JS Content : No');
					}
				} else {
					$this->output->writeln('Content : No');
				}

				// check menus association
				$query->clear()
					->select('COUNT(menuid)')
					->from('#__modules_menu')
					->where('moduleid = ' . $module->id)
					->andWhere('menuid > 0');

				$db->setQuery($query);
				$nb_menus = $db->loadResult();

				if ($nb_menus > 0) {
					$this->output->writeln('Module ' . $module->title . ' is associated to ' . $nb_menus . ' menus.');
				} else {
					$this->output->writeln('Module ' . $module->title . ' is not associated to any menu.');
				}

				$helper = new QuestionHelper();
				$question = new ConfirmationQuestion('Delete module ' . $module->title . ' ? [y/n]', false);
				if ($helper->ask($input, $output, $question)) {
					$this->output->writeln('Deleting Jumi module ' . $module->title . '...');
					$query->clear()
						->delete('#__modules')
						->where('id = ' . (int) $module->id);

					try {
						$db->setQuery($query);
						$db->execute();
						$this->output->writeln('Module ' . $module->title . ' deleted successfully.');
					} catch (\Exception $e) {
						$this->output->writeln('Error deleting module: ' . $e->getMessage());
					}
				} else {
					$this->output->writeln('Module ' . $module->title . ' not deleted.');
				}
			}
		} else {
			$this->output->writeln('You do not have Jumi modules in your project. Good job!');
		}

		$this->beforeFinishExecute($input, $output);
	}

	private function beforeFinishExecute(InputInterface $input, OutputInterface $output): void
	{
		Log::add('Jumi check job completed successfully.', Log::INFO, 'tchooz');
	}

	public static function getJobName(): string {
		return 'Jumi';
	}

	public static function getJobDescription(): ?string {
		return 'Helps to remove Jumi plugin from the project.';
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}