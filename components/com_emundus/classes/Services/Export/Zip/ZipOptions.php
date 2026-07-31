<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Zip;

use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Services\Export\ExportOptions;
use Tchooz\Services\Export\HeadersEnum;
use Tchooz\Services\Export\OptionsSchema\ZipOptionsSchema;

class ZipOptions extends ExportOptions
{
	private bool $displayHeader;

	/**
	 * @var array<HeadersEnum|string>
	 */
	private array $headers;

	private array $pageHeaders;

	/**
	 * Synthesis fields (element IDs or HeadersEnum keys) rendered on the cover/synthesis page of the per-fnum PDF.
	 *
	 * @var array
	 */
	private array $synthesis;

	/**
	 * Attachment type IDs to embed in the ZIP (or to merge into the PDF when concatAttachmentsWithForm = true).
	 *
	 * @var array
	 */
	private array $attachments;

	/**
	 * Raw "formId|trainingCode|campaignId" tokens emitted by the legacy frontend.
	 *
	 * @var array
	 */
	private array $formIds;

	/**
	 * Raw "attachmentId|trainingCode|campaignId" tokens emitted by the legacy frontend.
	 *
	 * @var array
	 */
	private array $attachIds;

	/**
	 * Evaluation steps payload: ['tables' => [...], 'groups' => [...], 'elements' => [...]].
	 *
	 * @var array
	 */
	private array $evalSteps;

	/**
	 * Header inclusion options (legacy 'options' array, e.g. [0|1|2|3]) forwarded to buildFormPDF.
	 *
	 * @var array
	 */
	private array $legacyHeaderOptions;

	private bool $concatAttachmentsWithForm;

	private bool $convertDocxToPdf;

	private bool $displayPageNumbers;

	/**
	 * Whether to render every applicant form into the per-fnum PDF (1) or none (0).
	 */
	private int $formPost;

	/**
	 * Whether to embed any attachment by default (1) or only the explicitly selected ones (0).
	 */
	private int $attachmentDefault;

	private string $filename;

	const DEFAULT_HEADERS = [
		HeadersEnum::ID,
		HeadersEnum::FNUM,
		HeadersEnum::EMAIL,
		HeadersEnum::SUBMITTED_DATE,
		HeadersEnum::PRINTED_DATE,
		HeadersEnum::STATUS,
		HeadersEnum::STICKERS,
	];

	public function __construct(
		bool  $displayHeader = true,
		array $synthesis = [],
		array $headers = self::DEFAULT_HEADERS,
		array $attachments = [],
		array $formIds = [],
		array $attachIds = [],
		array $evalSteps = [],
		array $legacyHeaderOptions = [],
		bool  $concatAttachmentsWithForm = false,
		bool  $convertDocxToPdf = false,
		bool  $displayPageNumbers = true,
		int   $formPost = 1,
		int   $attachmentDefault = 1
	)
	{
		parent::__construct(
			'zip',
			0,
			'default',
			[],
			'en-GB'
		);

		$this->displayHeader             = $displayHeader;
		$this->synthesis                 = $synthesis;
		$this->headers                   = $headers;
		$this->pageHeaders               = [];
		$this->attachments               = $attachments;
		$this->formIds                   = $formIds;
		$this->attachIds                 = $attachIds;
		$this->evalSteps                 = $evalSteps;
		$this->legacyHeaderOptions       = $legacyHeaderOptions;
		$this->concatAttachmentsWithForm = $concatAttachmentsWithForm;
		$this->convertDocxToPdf          = $convertDocxToPdf;
		$this->displayPageNumbers        = $displayPageNumbers;
		$this->formPost                  = $formPost;
		$this->attachmentDefault         = $attachmentDefault;
		$this->filename                  = '';
	}

	public static function fromObject(object $options): ZipOptions
	{
		$campaign      = $options->campaign ?? 0;
		$exportVersion = $options->exportVersion ?? 'default';
		$elements      = isset($options->elements) ? self::toArray($options->elements) : [];
		$lang          = $options->lang ?? 'en-GB';

		$headers     = isset($options->headers) ? self::toArray($options->headers) : [];
		$synthesis   = isset($options->synthesis) ? self::toArray($options->synthesis) : [];
		$attachments = isset($options->attachments) ? self::toArray($options->attachments) : [];
		$formIds     = isset($options->form_ids) ? self::toArray($options->form_ids) : (isset($options->formids) ? self::toArray($options->formids) : []);
		$attachIds   = isset($options->attach_ids) ? self::toArray($options->attach_ids) : (isset($options->attachids) ? self::toArray($options->attachids) : []);

		$evalSteps = [];
		if (isset($options->eval_steps))
		{
			$evalSteps = is_string($options->eval_steps) ? (json_decode($options->eval_steps, true) ?: []) : (array) $options->eval_steps;
		}

		$legacyHeaderOptions = isset($options->legacy_header_options) ? self::toArray($options->legacy_header_options) : (isset($options->options) ? self::toArray($options->options) : []);

		$rawSettings = $options->settings ?? null;
		if (is_string($rawSettings))
		{
			$rawSettings = json_decode($rawSettings, true);
		}
		if (is_object($rawSettings))
		{
			$rawSettings = (array) $rawSettings;
		}
		$schema   = new ZipOptionsSchema();
		$settings = $schema->cast(is_array($rawSettings) ? $rawSettings : []);

		// The Options schema is the single source of truth for these toggles: cast() always
		// returns every key (default when absent), so $settings is authoritative.
		$displayHeader             = $settings[ZipOptionsSchema::DISPLAY_HEADER];
		$displayPageNumbers        = $settings[ZipOptionsSchema::DISPLAY_PAGE_NUMBERS];
		$concatAttachmentsWithForm = $settings[ZipOptionsSchema::CONCAT_ATTACHMENTS_WITH_FORM];
		$convertDocxToPdf          = $settings[ZipOptionsSchema::CONVERT_DOCX_TO_PDF];

		$formPost          = isset($options->forms) ? (int) $options->forms : (isset($options->form_post) ? (int) $options->form_post : 1);
		$attachmentDefault = isset($options->attachment) ? (int) $options->attachment : 1;

		$zipOptions = new ZipOptions(
			$displayHeader,
			$synthesis,
			$headers,
			$attachments,
			$formIds,
			$attachIds,
			$evalSteps,
			$legacyHeaderOptions,
			$concatAttachmentsWithForm,
			$convertDocxToPdf,
			$displayPageNumbers,
			$formPost,
			$attachmentDefault
		);

		$zipOptions->setCampaign((int) $campaign);
		$zipOptions->setExportVersion((string) $exportVersion);
		$zipOptions->setElements($elements);
		$zipOptions->setLang((string) $lang);

		$emConfig            = ComponentHelper::getParams('com_emundus');
		$applicationFormName = (string) $emConfig->get('application_form_name', 'application_form_pdf');
		$filename            = $settings[ZipOptionsSchema::FILENAME] ?? '';
		$zipOptions->setFilename($filename !== '' ? $filename : $applicationFormName);

		$zipOptions->setSettings($settings);

		return $zipOptions;
	}

	private static function toArray(mixed $value): array
	{
		if (is_array($value))
		{
			return array_values($value);
		}

		if ($value === null || $value === '')
		{
			return [];
		}

		if (is_string($value))
		{
			$decoded = json_decode($value, true);
			if (is_array($decoded))
			{
				return array_values($decoded);
			}

			return array_values(array_filter(explode(',', $value), fn($v) => $v !== ''));
		}

		return [(string) $value];
	}

	public function isDisplayHeader(): bool
	{
		return $this->displayHeader;
	}

	public function setDisplayHeader(bool $displayHeader): void
	{
		$this->displayHeader = $displayHeader;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function setHeaders(array $headers): void
	{
		$this->headers = $headers;
	}

	public function getPageHeaders(): array
	{
		return $this->pageHeaders;
	}

	public function setPageHeaders(array $pageHeaders): void
	{
		$this->pageHeaders = $pageHeaders;
	}

	public function getSynthesis(): array
	{
		return $this->synthesis;
	}

	public function setSynthesis(array $synthesis): void
	{
		$this->synthesis = $synthesis;
	}

	public function getAttachments(): array
	{
		return $this->attachments;
	}

	public function setAttachments(array $attachments): void
	{
		$this->attachments = $attachments;
	}

	public function getFormIds(): array
	{
		return $this->formIds;
	}

	public function setFormIds(array $formIds): void
	{
		$this->formIds = $formIds;
	}

	public function getAttachIds(): array
	{
		return $this->attachIds;
	}

	public function setAttachIds(array $attachIds): void
	{
		$this->attachIds = $attachIds;
	}

	public function getEvalSteps(): array
	{
		return $this->evalSteps;
	}

	public function setEvalSteps(array $evalSteps): void
	{
		$this->evalSteps = $evalSteps;
	}

	public function getLegacyHeaderOptions(): array
	{
		return $this->legacyHeaderOptions;
	}

	public function setLegacyHeaderOptions(array $legacyHeaderOptions): void
	{
		$this->legacyHeaderOptions = $legacyHeaderOptions;
	}

	public function isConcatAttachmentsWithForm(): bool
	{
		return $this->concatAttachmentsWithForm;
	}

	public function setConcatAttachmentsWithForm(bool $concatAttachmentsWithForm): void
	{
		$this->concatAttachmentsWithForm = $concatAttachmentsWithForm;
	}

	public function isConvertDocxToPdf(): bool
	{
		return $this->convertDocxToPdf;
	}

	public function setConvertDocxToPdf(bool $convertDocxToPdf): void
	{
		$this->convertDocxToPdf = $convertDocxToPdf;
	}

	public function isDisplayPageNumbers(): bool
	{
		return $this->displayPageNumbers;
	}

	public function setDisplayPageNumbers(bool $displayPageNumbers): void
	{
		$this->displayPageNumbers = $displayPageNumbers;
	}

	public function getFormPost(): int
	{
		return $this->formPost;
	}

	public function setFormPost(int $formPost): void
	{
		$this->formPost = $formPost;
	}

	public function getAttachmentDefault(): int
	{
		return $this->attachmentDefault;
	}

	public function setAttachmentDefault(int $attachmentDefault): void
	{
		$this->attachmentDefault = $attachmentDefault;
	}

	public function getFilename(): string
	{
		return $this->filename;
	}

	public function setFilename(string $filename): void
	{
		$this->filename = $filename;
	}
}
