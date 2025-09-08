<?php

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checklist;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class TchoozChecklistJob extends TchoozJob
{
	protected const KEYWORDS = [
		[
			'type'   => 'comment',
			'word'   => '->updateState(',
			'advice' => 'It contains updateState, maybe you can replace it with new event handler.',
			'group'  => 'event_handler'
		],
		[
			'type'   => 'comment',
			'word'   => '->sendEmail(',
			'advice' => 'It contains sendEmail(), maybe you can replace it with new event handler.',
			'group'  => 'event_handler'
		],
		[
			'type'   => 'comment',
			'word'   => '->sendEmailNoFnum(',
			'advice' => 'It contains sendEmailNoFnum(), maybe you can replace it with new event handler.',
			'group'  => 'event_handler'
		],
		[
			'type'   => 'comment',
			'word'   => '->tagFile(',
			'advice' => 'It contains tagFile(), maybe you can replace it with new event handler.',
			'group'  => 'event_handler'
		],
		[
			'type'   => 'error',
			'word'   => 'Factory::getMailer',
			'advice' => 'It contains Factory::getMailer. You should at least replace it by using sendEmail method.'
		],
		[
			'type'   => 'error',
			'word'   => 'EmundusController',
			'advice' => 'You should not use controller in event handler code. You should call models methods directly instead.'
		],
		[
			'type'  => 'warning',
			'word'  => '_emundus_evaluations___',
			'advice' => 'Table emundus_evaluations used, it has been replaced by emundus_evaluations_n as it can now contain multiple evaluations for the same file.',
		],
		[
			'type'  => 'warning',
			'word'  => 'emundus_final_grade',
			'advice' => 'Table emundus_final_grade used, it has been replaced by evaluations steps, you should use emundus_evaluations_n instead.',
		],
		[
			'type'  => 'warning',
			'word'  => 'emundus_admission',
			'advice' => 'Table emundus_admission used, it has been replaced by evaluations steps, you should use emundus_evaluations_n instead.',
		],
		[
			'type'      => 'error',
			'word'      => 'rowid',
			'advice'    => 'Fnum is no more stored in rowid, you should not get it this way. The fnum field in the form should be enough to get the fnum.',
			'group'     => 'calc'
		],
		[
			'type'      => 'error',
			'word'      => 'rowid',
			'advice'    => 'Fnum is no more stored in rowid, you should not get it this way. Maybe use session instead or EmundusHelperFiles::getFnumFromId() method if no other way.',
			'group'     => 'fnum'
		],
		[
			'type' 	=> 'error',
			'word' 	=> 'EmundusModelCustom',
			'advice' => 'EmundusModelCustom is deprecated, the functions should be moved to a generic model.',
		]
	];

	public function __construct(
		private readonly object            $logger
	)
	{
		parent::__construct($logger);
	}

	/**
	 * Verify the compatibility of the provided code with PHPStan.
	 *
	 * @param string $code
	 * @throws \Exception
	 */
	protected function verifyCodeCompatibility(string $code, OutputInterface $output, InputInterface $input, string $group = '')
	{
		// 1. Écrire le code dans un fichier temporaire
		$tmpFile = tempnam(sys_get_temp_dir(), 'event_handler_') . '.php';
		file_put_contents($tmpFile, "<?php\n" . $code);

		// 2. Lancer PHPStan sur ce fichier
		$phpstanCmd = 'libraries/emundus/vendor/bin/phpstan analyse -c "libraries/emundus/phpstan.neon" --error-format=table --memory-limit=1G ' . escapeshellarg($tmpFile);

		try {
			$outputStan = shell_exec($phpstanCmd);

			// 3. Afficher le résultat
			if (empty($outputStan)) {
				$output->writeln('<error>PHPStan n\'a rien retourné. Vérifiez la configuration ou les chemins.</error>');
			} elseif (str_contains($outputStan, 'Result is incomplete because of severe errors')) {
				$output->writeln('<error>PHPStan: Résultat incomplet à cause d\'erreurs graves:</error>');
			} elseif (str_contains($outputStan, ' [ERROR] ')) {
				$output->writeln('<error>PHPStan errors detected for this code:</error>');
			}

			if (!empty($outputStan)) {
				$output->writeln($outputStan);
			}
		} catch (\Exception $e) {
			$output->writeln('<error>Error executing PHPStan: ' . $e->getMessage() . '</error>');
		}

		// 4. Nettoyer le fichier temporaire
		@unlink($tmpFile);

		// catch some keywords that could be replaced or are deprecated
		foreach (self::KEYWORDS as $keyword) {
			if ($keyword['group'] && $keyword['group'] !== $group) {
				continue;
			}

			if (str_contains($code, $keyword['word'])) {
				$matches = [];
				$pattern = '/(' .preg_quote($keyword['word'], '/') . '[a-zA-Z_]*)/';
				preg_match_all($pattern, $code, $matches);

				$output->writeln('<' . $keyword['type'] . '> Code [' . implode(',', $matches[0]) . ']: ' . $keyword['advice'] . '</' . $keyword['type'] . '>');
			}
		}

		$helper = new QuestionHelper();
		$question = new ConfirmationQuestion('Press enter to continue', true);
		$helper->ask($input, $output, $question);
	}
}