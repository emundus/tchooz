<?php

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checklist;

use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CheckEmundusTagsJob extends TchoozChecklistJob
{
	private OutputInterface $output;

	public function __construct(
		private readonly object          $logger,
		private readonly DatabaseService $databaseServiceSource,
		private readonly DatabaseService $databaseService
	)
	{
		parent::__construct($logger);
	}

	public static function getJobName(): string
	{
		return 'Emundus Tags';
	}

	public static function getJobDescription(): ?string
	{
		return 'Helps to check if emundus custom tags contains php code that contains errors.';
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->output = $output;

		$db    = $this->databaseService->getDatabase();
		$query = $db->getQuery(true);

		$query->select('id, tag, request, description')
			->from($db->quoteName('#__emundus_setup_tags'))
			->where($db->quoteName('published') . ' = 1')
			->andWhere($db->quoteName('request') . ' LIKE ' . $db->quote('php|%'));

		try
		{
			$db->setQuery($query);
			$tags = $db->loadAssocList();

			if (!empty($tags))
			{
				$this->output->writeln(sizeof($tags) . ' custom event handlers found:');

				foreach ($tags as $tag)
				{
					$this->output->writeln('====================================');
					$this->output->writeln('Tag: ' . $tag['tag']);
					$this->output->writeln('Description: ' . $tag['description']);

					$code = $tag['request'];
					// remove the "php|" prefix
					$code = str_replace('php|', '', $code);

					if (empty($code))
					{
						$this->output->writeln('Code: No code found, maybe delete this tag.');
					}
					else
					{
						$this->output->writeln('Code compatibility test... ');
						$this->verifyCodeCompatibility($code, $output, $input);
					}
				}
			}
			else
			{
				$this->output->writeln('No emundus tags containing php code found.');
			}
		}
		catch (\Exception $e)
		{
			throw new \Exception('Error while fetching emundus tags: ' . $e->getMessage());
		}
	}

	public function isAllowFailure(): bool
	{
		return $this->allowFailure;
	}
}