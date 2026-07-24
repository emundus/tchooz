<?php

namespace Tchooz\Services\Poll;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Tchooz\Entities\Event\SlotEntity;
use Tchooz\Entities\Poll\PollAnswerEntity;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Enums\Poll\AnswerTypeEnum;
use Tchooz\Repositories\Poll\PollRepository;

/**
 * Build an XLSX export for one or more polls. Owns spreadsheet construction, sheet titling,
 * row building, and tmp filesystem layout. Kept separate from PollService so business logic
 * does not pull in PhpSpreadsheet for runtime paths that never export.
 */
class PollExportService
{
	private PollRepository $pollRepository;

	public function __construct(?PollRepository $pollRepository = null)
	{
		$this->pollRepository = $pollRepository ?? new PollRepository();

		Log::addLogger(['text_file' => 'com_emundus.poll.php'], Log::ALL, ['com_emundus.poll']);
	}

	/**
	 * Export one or more polls to a single XLSX file. Each poll becomes a sheet whose rows are
	 * the participant answers, ordered by slot start date ascending.
	 *
	 * @param   int[]  $pollIds  List of poll ids to export.
	 *
	 * @return  string  Absolute filesystem path to the generated XLSX file.
	 *
	 * @throws \InvalidArgumentException  When no valid poll id is provided.
	 * @throws \RuntimeException          When no poll is loadable or the file cannot be written.
	 */
	public function exportToExcel(array $pollIds): string
	{
		$ids = array_values(array_unique(array_filter(
			array_map('intval', $pollIds),
			static fn(int $id): bool => $id > 0
		)));

		if (empty($ids))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_POLL_RUN_NO_IDS'));
		}

		require_once(JPATH_LIBRARIES . '/emundus/vendor/autoload.php');

		$polls = [];
		foreach ($ids as $pollId)
		{
			$poll = $this->pollRepository->getItemByField(
				'id',
				$pollId,
				true,
				$this->pollRepository->getTableColumns(PollRepository::class)
			);

			if ($poll instanceof PollEntity)
			{
				$polls[] = $poll;
			}
		}

		if (empty($polls))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_POLLS_ERROR_NOT_FOUND'));
		}

		$spreadsheet = new Spreadsheet();
		$spreadsheet->removeSheetByIndex(0);

		$usedTitles = [];
		foreach ($polls as $index => $poll)
		{
			assert($poll instanceof PollEntity);

			$sheet = $spreadsheet->createSheet();
			$sheet->setTitle($this->buildSheetTitle($poll->getName(), $index, $usedTitles));

			$this->fillPollSheet($sheet, $poll);
		}

		$spreadsheet->setActiveSheetIndex(0);

		$tmpDir = JPATH_SITE . '/tmp';
		if (!is_dir($tmpDir) && !mkdir($tmpDir, 0755, true) && !is_dir($tmpDir))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_POLL_EXPORT_TMP_DIR_FAILED'));
		}

		$filename = 'export_polls_' . Factory::getDate()->format('Ymd_His') . '.xlsx';
		$filepath = $tmpDir . '/' . $filename;

		try
		{
			$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
			$writer->save($filepath);
		}
		catch (\Throwable $e)
		{
			Log::add('Failed to write poll export file: ' . $e->getMessage(), Log::ERROR, 'com_emundus.poll');
			throw new \RuntimeException(Text::_('COM_EMUNDUS_POLL_EXPORT_WRITE_FAILED'), 0, $e);
		}
		finally
		{
			$spreadsheet->disconnectWorksheets();
		}

		return $filepath;
	}

	private function fillPollSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, PollEntity $poll): void
	{
		$headers = [
			Text::_('COM_EMUNDUS_POLL_EXPORT_SLOT_START'),
			Text::_('COM_EMUNDUS_POLL_EXPORT_SLOT_END'),
			Text::_('COM_EMUNDUS_FORM_LAST_NAME'),
			Text::_('COM_EMUNDUS_FORM_FIRST_NAME'),
			Text::_('COM_EMUNDUS_EMAIL'),
			Text::_('COM_EMUNDUS_POLL_REPLY_ANSWER'),
			Text::_('COM_EMUNDUS_POLL_REPLY_COMMENT'),
		];

		$sheet->fromArray($headers, null, 'A1');

		$lastColumn  = Coordinate::stringFromColumnIndex(count($headers));
		$headerStyle = $sheet->getStyle('A1:' . $lastColumn . '1');
		$headerStyle->getFont()->setBold(true);
		$headerStyle->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_CENTER)
			->setVertical(Alignment::VERTICAL_CENTER);
		$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE5E7EB');

		$rows = $this->buildPollRows($poll);

		if (!empty($rows))
		{
			$sheet->fromArray($rows, null, 'A2');
		}

		$widths = [20, 20, 20, 20, 32, 20, 40];
		foreach ($widths as $i => $width)
		{
			$sheet->getColumnDimensionByColumn($i + 1)->setWidth($width);
		}

		$sheet->freezePane('A2');
	}

	/**
	 * @return  array<int, array<int, string>>
	 */
	private function buildPollRows(PollEntity $poll): array
	{
		$slots = $poll->getSlots();
		usort($slots, static function (SlotEntity $a, SlotEntity $b): int {
			return $a->getStart() <=> $b->getStart();
		});

		$participants = $poll->getParticipants();

		$rows = [];
		foreach ($slots as $slot)
		{
			assert($slot instanceof SlotEntity);

			$slotStart = $slot->getStart()->format('Y-m-d H:i');
			$slotEnd   = $slot->getEnd()->format('Y-m-d H:i');

			// Index this slot's answers by participant id so we can detect non-respondents.
			$answersByParticipant = [];
			foreach ($slot->getAnswers() as $answer)
			{
				assert($answer instanceof PollAnswerEntity);

				$participantId = $answer->getParticipant()?->getId();
				if (!empty($participantId))
				{
					$answersByParticipant[$participantId] = $answer;
				}
			}

			// When the poll has no participants at all, keep a single empty row for the slot.
			if (empty($participants))
			{
				$rows[] = [$slotStart, $slotEnd, '', '', '', '', ''];
				continue;
			}

			// One row per participant: respondents show their answer, others are flagged as not answered.
			foreach ($participants as $participant)
			{
				assert($participant instanceof PollParticipantsEntity);

				$answer = $answersByParticipant[$participant->getId()] ?? null;

				$rows[] = [
					$slotStart,
					$slotEnd,
					$participant->getLastname() ?? '',
					$participant->getFirstname() ?? '',
					$participant->getEmail() ?? '',
					$answer !== null
						? $this->getAnswerLabel($answer->getAnswer())
						: $this->getAnswerLabel(AnswerTypeEnum::NOT_ANSWERED),
					$answer !== null ? $answer->getComment() : '',
				];
			}
		}

		return $rows;
	}

	private function getAnswerLabel(AnswerTypeEnum $answer): string
	{
		return match ($answer)
		{
			AnswerTypeEnum::AVAILABLE => Text::_('COM_EMUNDUS_POLL_REPLY_AVAILABLE'),
			AnswerTypeEnum::NOT_AVAILABLE => Text::_('COM_EMUNDUS_POLL_REPLY_UNAVAILABLE'),
			AnswerTypeEnum::IF_NEEDED => Text::_('COM_EMUNDUS_POLL_REPLY_IF_NEEDED'),
			AnswerTypeEnum::NOT_ANSWERED => Text::_('COM_EMUNDUS_POLL_REPLY_STATE_NO_ANSWER'),
		};
	}

	/**
	 * Build a worksheet title compatible with Excel constraints (31 chars max, no \/?*:[] and unique).
	 *
	 * @param   array<string, true>  $usedTitles  Map of already used titles passed by reference.
	 */
	private function buildSheetTitle(string $rawName, int $index, array &$usedTitles): string
	{
		$sanitized = preg_replace('/[\\\\\/\?\*\:\[\]]/', '_', $rawName) ?? '';
		$sanitized = trim($sanitized);

		if ($sanitized === '')
		{
			$sanitized = Text::_('COM_EMUNDUS_POLL_EXPORT_SHEET_TITLE') . ' ' . ($index + 1);
		}

		$base  = mb_substr($sanitized, 0, 31);
		$title = $base;
		$i     = 2;
		while (isset($usedTitles[$title]))
		{
			$suffix = ' (' . $i++ . ')';
			$title  = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
		}

		$usedTitles[$title] = true;

		return $title;
	}
}
