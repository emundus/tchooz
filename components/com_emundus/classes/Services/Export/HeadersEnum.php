<?php
/**
 * @package     Tchooz\Services\Export\Pdf
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\User\EmundusUserEntity;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Repositories\Label\LabelRepository;
use Tchooz\Repositories\User\EmundusUserRepository;

enum HeadersEnum: string
{
	case ID = 'id';
	case FNUM = 'fnum';
	case EMAIL = 'email';
	case LASTNAME = 'lastname';
	case FIRSTNAME = 'firstname';
	case FULLNAME = 'fullname';

	case SUBMITTED_DATE = 'submitted_date';
	case PRINTED_DATE = 'printed_date';
	case STATUS = 'status';
	case STICKERS = 'stickers';
	case PROGRESS_FORMS = 'progress_forms';
	case PROGRESS_ATTACHMENTS = 'progress_attachments';

	case CAMPAIGN_LABEL = 'campaign_label';
	case CAMPAIGN_YEAR = 'campaign_year';
	case CAMPAIGN_START_DATE = 'campaign_start_date';
	case CAMPAIGN_END_DATE = 'campaign_end_date';

	case PROGRAM_NAME = 'program_name';
	case PROGRAM_CATEGORY = 'program_category';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::ID => Text::_('COM_EMUNDUS_USERNAME'),
			self::FNUM => Text::_('FNUM'),
			self::EMAIL => Text::_('COM_EMUNDUS_EMAIL'),
			self::LASTNAME => Text::_('COM_EMUNDUS_FORM_LAST_NAME'),
			self::FIRSTNAME => Text::_('COM_EMUNDUS_FORM_FIRST_NAME'),
			self::FULLNAME => Text::_('COM_EMUNDUS_ONBOARD_LABEL_CONTACTS'),

			self::SUBMITTED_DATE => Text::_('APPLICATION_SENT_ON'),
			self::PRINTED_DATE => Text::_('DOCUMENT_PRINTED_ON'),
			self::STATUS => Text::_('COM_EMUNDUS_EXPORTS_PDF_STATUS'),
			self::STICKERS => Text::_('COM_EMUNDUS_FILES_TAGS'),
			self::PROGRESS_FORMS => Text::_('COM_EMUNDUS_PROGRESS_FORMS_PERCENTAGE'),
			self::PROGRESS_ATTACHMENTS => Text::_('COM_EMUNDUS_PROGRESS_ATTACHMENTS_PERCENTAGE'),

			self::CAMPAIGN_LABEL => Text::_('COM_EMUNDUS_CAMPAIGN'),
			self::CAMPAIGN_YEAR => Text::_('COM_EMUNDUS_CAMPAIGN_YEAR'),
			self::CAMPAIGN_START_DATE => Text::_('COM_EMUNDUS_CAMPAIGN_START_DATE'),
			self::CAMPAIGN_END_DATE => Text::_('COM_EMUNDUS_CAMPAIGN_END_DATE'),

			self::PROGRAM_NAME => Text::_('COM_EMUNDUS_PROGRAMME'),
			self::PROGRAM_CATEGORY => Text::_('COM_EMUNDUS_PROGRAMME_CATEGORY'),
		};
	}

	public function transform(
		ApplicationFileEntity $file,
		?LabelRepository      $labelRepository = null,
		?EmundusUserEntity    $emundusUser = null,
		ValueFormatEnum       $format = ValueFormatEnum::FORMATTED
	)
	{
		switch ($this)
		{
			case self::ID:
				return $file->getId();
			case self::FNUM:
				return $file->getFnum();
			case self::EMAIL:
				return $file->getUser()->email;
			case self::LASTNAME:
				if (!$emundusUser)
				{
					$emundusUserRepository = new EmundusUserRepository();
					$emundusUser           = $emundusUserRepository->getByUserId($file->getUser()->id);
				}

				$lastName = $emundusUser->getLastname();

				return $lastName ?: $file->getUser()->name;
			case self::FIRSTNAME:
				if (!$emundusUser)
				{
					$emundusUserRepository = new EmundusUserRepository();
					$emundusUser           = $emundusUserRepository->getByUserId($file->getUser()->id);
				}

				$firstName = $emundusUser->getFirstname();

				return $firstName ?: $file->getUser()->name;
			case self::FULLNAME:
				return $file->getUser()->name;

			case self::SUBMITTED_DATE:
				$date_submitted = Text::_('NOT_SENT');
				if (!empty($file->getDateSubmitted()))
				{
					$date_submitted = $file->getDateSubmitted()->format('Y-m-d H:i:s');
				}

				if ($format == ValueFormatEnum::RAW)
				{
					return $date_submitted;
				}

				return \EmundusHelperDate::displayDate($date_submitted);
			case self::PRINTED_DATE:
				$timezone = new \DateTimeZone(Factory::getApplication()->get('offset'));
				$date_printed = new Date('now', $timezone);

				if ($format == ValueFormatEnum::RAW)
				{
					return $date_printed->format('Y-m-d H:i:s', true);
				}

				return \EmundusHelperDate::displayDate($date_printed, 'DATE_FORMAT_LC2', 0);
			case self::STATUS:
				return $file->getStatus()->getLabel();
			case self::PROGRESS_FORMS:
				return $file->getFormProgress();
			case self::PROGRESS_ATTACHMENTS:
				return $file->getAttachmentProgress();
			case self::STICKERS:
				if (!$labelRepository)
				{
					$labelRepository = new LabelRepository();
				}

				$stickers = $labelRepository->getByFnum($file->getFnum());

				if ($format == ValueFormatEnum::RAW)
				{
					$stickerLabels = [];
					foreach ($stickers as $sticker)
					{
						$stickerLabels[] = $sticker->getLabel();
					}

					return implode(', ', $stickerLabels);
				}

				$stickersHtml = '';
				foreach ($stickers as $sticker)
				{
					// Stickers can contain emojis in their label, we need to sanitize them for PDF rendering
					$label        = $this->removeEmojis($sticker->getLabel());
					$class        = str_replace('label-', '', $sticker->getClass());
					$stickersHtml .= '<span class="sticker label-' . $class . '">' . $label . '</span>&nbsp;';
				}

				return $stickersHtml;

			case self::CAMPAIGN_LABEL:
				return $file->getCampaign()->getLabel();
			case self::CAMPAIGN_YEAR:
				return $file->getCampaign()->getYear();
			case self::CAMPAIGN_START_DATE:
				$startDate = $file->getCampaign()->getStartDate();
				$startDate = $startDate?->format('Y-m-d H:i:s') ?? '';
				if ($format == ValueFormatEnum::RAW)
				{
					return $startDate;
				}

				return \EmundusHelperDate::displayDate($startDate, 'DATE_FORMAT_LC2', 0);
			case self::CAMPAIGN_END_DATE:
				$endDate = $file->getCampaign()->getEndDate();
				$endDate = $endDate?->format('Y-m-d H:i:s') ?? '';
				if ($format == ValueFormatEnum::RAW)
				{
					return $endDate;
				}

				return \EmundusHelperDate::displayDate($endDate, 'DATE_FORMAT_LC2', 0);

			case self::PROGRAM_NAME:
				return $file->getCampaign()->getProgram()->getLabel();
			case self::PROGRAM_CATEGORY:
				return $file->getCampaign()->getProgram()->getProgrammes();
			default:
				return null;
		}
	}

	private function removeEmojis(string $text): string
	{
		return preg_replace(
			'/[\p{Extended_Pictographic}\x{FE0F}\x{200D}]/u',
			'',
			$text
		);
	}
}
