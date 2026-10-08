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
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CheckRegistrationFormJob extends TchoozChecklistJob
{
	private OutputInterface $output;

	const DB_TABLE_NAME_REGISTRATION = 'jos_emundus_users';

	const FORM_REGISTRATION_ID = 307;

	public function __construct(
		private readonly object          $logger,
		private readonly DatabaseService $databaseServiceSource,
		private readonly DatabaseService $databaseService,
	)
	{
		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->output = $output;

		$helper = new QuestionHelper();

		if (!$this->is307RegistrationForm())
		{
			$question = new ConfirmationQuestion('Registration form seems to not to be 307, do you want migrate it? (y/n) ', false);
			if ($helper->ask($input, $this->output, $question))
			{
				$formsList = $this->findRegistrationForm();
				if (empty($formsList))
				{
					$this->output->writeln('No form link to ' . self::DB_TABLE_NAME_REGISTRATION . ' table was found.');
				}
				else
				{
					$choices = [];
					foreach ($formsList as $form)
					{
						$choices[$form->id] = $form->label . ' (id: ' . $form->id . ')';
					}

					$choiceQuestion = new ChoiceQuestion(
						'Several forms are linked to the ' . self::DB_TABLE_NAME_REGISTRATION . ' table. Which one is the registration form?',
						$choices
					);
					$choiceQuestion->setErrorMessage('Choice %s is invalid.');

					$selectedLabel  = $helper->ask($input, $this->output, $choiceQuestion);
					$selectedFormId = array_search($selectedLabel, $choices, true);

					$this->output->writeln('Selected registration form: ' . $selectedLabel);

					$formToMigrate = $this->findCurrent307Form();
					$id307Free = empty($formToMigrate);
					if (!$id307Free)
					{
						$question = new ConfirmationQuestion('Form ' . $formToMigrate->label . '(id: ' . $formToMigrate->id . ') link to table ' . $formToMigrate->db_table_name . ' will be migrate into a new id. Do you want to continue? (y/n) ', false);
						if ($helper->ask($input, $this->output, $question))
						{
							$report = $this->migrateFormToNewId($formToMigrate);
							$this->output->writeln('<info>Migration report:</info>');
							$this->output->writeln(json_encode($report, JSON_PRETTY_PRINT));
							$id307Free = true;
						}
					}

					if ($id307Free)
					{
						$moveReport = $this->moveFormToId((int) $selectedFormId, self::FORM_REGISTRATION_ID);
						$this->output->writeln('<info>Move report:</info>');
						$this->output->writeln(json_encode($moveReport, JSON_PRETTY_PRINT));
					}


				}
			}
		}

		Log::add('Check registration form check completed.', Log::INFO, 'tchooz');
	}

	private function is307RegistrationForm(): string
	{
		$db    = $this->databaseService->getDatabase();
		$query = $db->getQuery(true);

		$query->select('db_table_name')
			->from($db->qn('#__fabrik_lists'))
			->where($db->qn('form_id') . ' = ' . self::FORM_REGISTRATION_ID);
		$db->setQuery($query);

		return $db->loadResult() === self::DB_TABLE_NAME_REGISTRATION;
	}

	private function findRegistrationForm(): array
	{
		$db    = $this->databaseService->getDatabase();
		$query = $db->getQuery(true);

		$query->select('ff.id, ff.label')
			->from($db->qn('#__fabrik_forms', 'ff'))
			->leftJoin($db->qn('#__fabrik_lists', 'fl') . ' ON ' . $db->qn('fl.form_id') . ' = ' . $db->qn('ff.id'))
			->where($db->qn('fl.db_table_name') . ' = ' . $db->q(self::DB_TABLE_NAME_REGISTRATION));
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	private function findCurrent307Form(): ?object
	{
		$db    = $this->databaseService->getDatabase();
		$query = $db->getQuery(true);

		$query->select('ff.id, ff.label, fl.db_table_name')
			->from($db->qn('#__fabrik_forms', 'ff'))
			->leftJoin($db->qn('#__fabrik_lists', 'fl') . ' ON ' . $db->qn('fl.form_id') . ' = ' . $db->qn('ff.id'))
			->where($db->qn('ff.id') . ' = ' . self::FORM_REGISTRATION_ID);
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Copies the fabrik_forms row of $formToMigrate onto a new auto-incremented id, then repoints every
	 * reference (fabrik_lists, fabrik_formgroup, menu links, emundus_setup_formlist, emundus_setup_form_rules)
	 * from the old id to the new one. Wrapped in a transaction: any failure rolls everything back.
	 *
	 * @return array Report of what happened (old id, new id, affected rows per table).
	 * @throws \Exception
	 */
	private function migrateFormToNewId(object $formToMigrate): array
	{
		$db    = $this->databaseService->getDatabase();
		$oldId = (int) $formToMigrate->id;

		$report = [
			'old_form_id' => $oldId,
			'new_form_id' => null,
			'updates'     => [],
		];

		try
		{
			$db->transactionStart();

			// 1. Copy the fabrik_forms row onto a new auto-incremented id (keep every other column).
			$query = $db->getQuery(true);
			$query->select('*')
				->from($db->qn('#__fabrik_forms'))
				->where($db->qn('id') . ' = ' . $oldId);
			$db->setQuery($query);
			$formRow = $db->loadObject();

			if (empty($formRow))
			{
				throw new \RuntimeException('Fabrik form ' . $oldId . ' not found, nothing to migrate.');
			}

			unset($formRow->id);
			$db->insertObject('#__fabrik_forms', $formRow, 'id');
			$newId                 = (int) $formRow->id;
			$report['new_form_id'] = $newId;
			$this->logStep('Copied fabrik_forms ' . $oldId . ' into new form ' . $newId);

			// 2. Repoint every reference from the old id to the new id.
			$report['updates']['jos_fabrik_lists']             = $this->updateFormId('#__fabrik_lists', $oldId, $newId);
			$report['updates']['jos_fabrik_formgroup']         = $this->updateFormId('#__fabrik_formgroup', $oldId, $newId);
			$report['updates']['jos_menu']                     = $this->updateMenuLink($oldId, $newId);
			$report['updates']['jos_emundus_setup_formlist']   = $this->updateFormId('#__emundus_setup_formlist', $oldId, $newId);
			$report['updates']['jos_emundus_setup_form_rules'] = $this->updateFormId('#__emundus_setup_form_rules', $oldId, $newId);

			// 3. Delete the old fabrik_forms row so id $oldId is free (all references now point to $newId).
			$query = $db->getQuery(true);
			$query->delete($db->qn('#__fabrik_forms'))
				->where($db->qn('id') . ' = ' . $oldId);
			$db->setQuery($query);
			$db->execute();
			$report['deleted_old_form'] = $db->getAffectedRows();
			$this->logStep('Deleted old fabrik_forms ' . $oldId . ', id is now free.');

			$db->transactionCommit();
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();
			$this->logStep('Migration failed and rolled back: ' . $e->getMessage(), 'error');

			throw $e;
		}

		$this->logStep('Migration of form ' . $oldId . ' to ' . $report['new_form_id'] . ' completed.');

		return $report;
	}

	/**
	 * Moves the fabrik_forms row $fromId onto the now-free id $toId (changing its primary key) and repoints
	 * every reference (fabrik_lists, fabrik_formgroup, menu links, emundus_setup_formlist,
	 * emundus_setup_form_rules) accordingly. Wrapped in a transaction: any failure rolls everything back.
	 *
	 * @return array Report of what happened (from id, to id, affected rows per table).
	 * @throws \Exception
	 */
	private function moveFormToId(int $fromId, int $toId): array
	{
		$db     = $this->databaseService->getDatabase();
		$report = [
			'from_form_id' => $fromId,
			'to_form_id'   => $toId,
			'updates'      => [],
		];

		try
		{
			$db->transactionStart();

			// 1. Move the fabrik_forms row onto the free id $toId.
			$query = $db->getQuery(true);
			$query->update($db->qn('#__fabrik_forms'))
				->set($db->qn('id') . ' = ' . $toId)
				->where($db->qn('id') . ' = ' . $fromId);
			$db->setQuery($query);
			$db->execute();
			$this->logStep('Moved fabrik_forms ' . $fromId . ' to ' . $toId);

			// 2. Repoint every reference from $fromId to $toId.
			$report['updates']['jos_fabrik_lists']             = $this->updateFormId('#__fabrik_lists', $fromId, $toId);
			$report['updates']['jos_fabrik_formgroup']         = $this->updateFormId('#__fabrik_formgroup', $fromId, $toId);
			$report['updates']['jos_menu']                     = $this->updateMenuLink($fromId, $toId);
			$report['updates']['jos_emundus_setup_formlist']   = $this->updateFormId('#__emundus_setup_formlist', $fromId, $toId);
			$report['updates']['jos_emundus_setup_form_rules'] = $this->updateFormId('#__emundus_setup_form_rules', $fromId, $toId);

			$db->transactionCommit();
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();
			$this->logStep('Move failed and rolled back: ' . $e->getMessage(), 'error');

			throw $e;
		}

		$this->logStep('Move of form ' . $fromId . ' to ' . $toId . ' completed.');

		return $report;
	}

	/**
	 * Updates the form_id column of $table from $oldId to $newId and returns the number of affected rows.
	 */
	private function updateFormId(string $table, int $oldId, int $newId): int
	{
		$db    = $this->databaseService->getDatabase();
		$query = $db->getQuery(true);

		$query->update($db->qn($table))
			->set($db->qn('form_id') . ' = ' . $newId)
			->where($db->qn('form_id') . ' = ' . $oldId);
		$db->setQuery($query);
		$db->execute();

		$affected = $db->getAffectedRows();
		$this->logStep($table . ': ' . $affected . ' row(s) form_id ' . $oldId . ' -> ' . $newId);

		return $affected;
	}

	/**
	 * Rewrites the Fabrik form menu links from the old form id to the new one and returns affected rows.
	 */
	private function updateMenuLink(int $oldId, int $newId): int
	{
		$db      = $this->databaseService->getDatabase();
		$oldLink = 'index.php?option=com_fabrik&view=form&formid=' . $oldId;
		$newLink = 'index.php?option=com_fabrik&view=form&formid=' . $newId;

		$query = $db->getQuery(true);
		$query->update($db->qn('#__menu'))
			->set($db->qn('link') . ' = ' . $db->q($newLink))
			->where($db->qn('link') . ' = ' . $db->q($oldLink));
		$db->setQuery($query);
		$db->execute();

		$affected = $db->getAffectedRows();
		$this->logStep('jos_menu: ' . $affected . ' link(s) ' . $oldLink . ' -> ' . $newLink);

		return $affected;
	}

	/**
	 * Writes a migration step to both the console output and the tchooz log, so the whole run is traceable.
	 */
	private function logStep(string $message, string $level = 'info'): void
	{
		$tag = $level === 'error' ? 'error' : 'info';
		$this->output->writeln('<' . $tag . '>' . $message . '</' . $tag . '>');
		Log::add($message, $level === 'error' ? Log::ERROR : Log::INFO, 'tchooz');
	}

	public static function getJobName(): string
	{
		return 'Registration form check';
	}

	public static function getJobDescription(): ?string
	{
		return 'Migrate registration form if not 307';
	}

	public function isAllowFailure(): bool
	{
		return $this->allowFailure;
	}
}
