<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionExecutionMessage;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ActionMessageTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Services\Export\Pdf\PdfOptions;
use Tchooz\Services\Export\Pdf\PdfService;

class ActionPrintApplication extends ActionEntity
{
	public const ATTACHMENT_ID = 26;

	public const TEMPLATE_PARAMETER = 'template';

	public const CAN_BE_VIEWED_PARAMETER = 'can_be_viewed';

	/**
	 * Id of the "application form" row in #__emundus_setup_step_types. Without a template the printed
	 * file must stay on those steps only, so no evaluation data ever lands in the applicant folder.
	 */
	private const APPLICATION_STEP_TYPE = 1;

	/** Last resort when com_emundus carries no application_form_name at all. */
	private const FALLBACK_FILENAME = 'application_form';

	private array $templateChoices = [];

	public static function getIcon(): ?string
	{
		return 'print';
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::FILE;
	}

	/**
	 * @inheritDoc
	 */
	public static function getType(): string
	{
		return 'print_application';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_PRINT_APPLICATION_LABEL');
	}

	public static function getDescription(): string
	{
		return Text::_('TCHOOZ_AUTOMATION_ACTION_PRINT_APPLICATION_DESCRIPTION');
	}

	/**
	 * @inheritDoc
	 */
	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::FILE];
	}

	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$executed = ActionExecutionStatusEnum::FAILED;

		$this->verifyRequiredParameters();

		if (!empty($context->getFile()))
		{
			try
			{
				if (!defined('EMUNDUS_PATH_ABS'))
				{
					define('EMUNDUS_PATH_ABS', JPATH_ROOT . '/images/emundus/files/');
				}

				if (!defined('EMUNDUS_PATH_REL'))
				{
					define('EMUNDUS_PATH_REL', 'images/emundus/files/');
				}

				$app = Factory::getApplication();

				$offset   = $app->get('offset', 'UTC');
				$dateTime = new \DateTime(gmdate("Y-m-d H:i:s"), new \DateTimeZone('UTC'));
				$dateTime = $dateTime->setTimezone(new \DateTimeZone($offset));
				$now      = $dateTime->format('Y-m-d H:i:s');

				$db = Factory::getContainer()->get('DatabaseDriver');

				$eMConfig             = ComponentHelper::getParams('com_emundus');
				$overwrite_export_pdf = $eMConfig->get('overwrite_old_export', 0);
				$export_path          = $eMConfig->get('export_path', null);

				$lang = $app->getLanguage();
				$lang->load('com_emundus', JPATH_SITE . '/components/com_emundus');

				if (!class_exists('EmundusModelFiles'))
				{
					require_once JPATH_SITE . '/components/com_emundus/models/files.php';
				}
				$mFiles = new \EmundusModelFiles();

				$fnum     = $context->getFile();
				$fnumInfo = $mFiles->getFnumInfos($fnum);

				$exportUser = $this->getAutomatedTaskUser();
				if (empty($exportUser))
				{
					throw new \Exception('No automated task user configured, cannot export the application file of ' . $fnum);
				}

				$options = $this->buildPdfOptions($overwrite_export_pdf == 1, $lang->getTag());
				$result  = (new PdfService([$fnum], $exportUser, $options))->export(JPATH_SITE . '/tmp/', null, $lang->getTag());

				if (!$result->isStatus() || empty($result->getFilePath()))
				{
					// Access is not in play here (the options skip the check), so either the render or the
					// merge with the attachments failed: com_emundus.export.pdf carries the details.
					throw new \Exception('PDF export produced no file for ' . $fnum . ', printed as user ' . $exportUser->id);
				}

				// Only the exported file is final: as soon as attachments are appended PdfService merges
				// them there and leaves the un-merged render in the applicant folder, so always copy back.
				$tmp_link         = $result->getFilePath();
				$target_file_name = basename($tmp_link);
				$applicant_link   = EMUNDUS_PATH_ABS . $fnumInfo['applicant_id'] . '/' . $target_file_name;

				// If export path is defined
				if (!empty($export_path))
				{
					if (!class_exists('EmundusModelEmails'))
					{
						require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
					}
					$mEmails = new \EmundusModelEmails();

					$post        = array('FNUM' => $fnum, 'CAMPAIGN_YEAR' => $fnumInfo['year'], 'PROGRAMME_CODE' => $fnumInfo['training']);
					$tags        = $mEmails->setTags($fnumInfo['applicant_id'], $post, $fnum, '', $export_path);
					$export_path = preg_replace($tags['patterns'], $tags['replacements'], $export_path);
					$export_path = $mEmails->setTagsFabrik($export_path, array($fnum));

					// Sanitize and build filename.
					$export_path = strtr(utf8_decode($export_path), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
					$export_path = strtolower($export_path);
					$export_path = preg_replace('`\s`', '-', $export_path);
					$export_path = str_replace(',', '', $export_path);
					$directories = explode('/', $export_path);

					$d = '';
					foreach ($directories as $dir)
					{
						$d .= $dir . '/';
						if (!file_exists(JPATH_BASE . DS . $d))
						{
							mkdir(JPATH_BASE . DS . $d);
							chmod(JPATH_BASE . DS . $d, 0755);
						}
					}
					if (file_exists(JPATH_BASE . DS . $export_path . $target_file_name))
					{
						unlink(JPATH_BASE . DS . $export_path . $target_file_name);
					}
					copy($tmp_link, JPATH_BASE . DS . $export_path . $target_file_name);
				}

				if (file_exists($applicant_link))
				{
					unlink($applicant_link);
				}
				copy($tmp_link, $applicant_link);

				if (file_exists($tmp_link))
				{
					unlink($tmp_link);
				}

				$upload = (object) [
					'fnum'           => $fnum,
					'attachment_id'  => self::ATTACHMENT_ID,
					'user_id'        => $fnumInfo['applicant_id'],
					'campaign_id'    => $fnumInfo['campaign_id'],
					'can_be_deleted' => 0,
					'can_be_viewed'  => $this->isVisibleToApplicant(),
					'filename'       => $target_file_name
				];

				if ($overwrite_export_pdf == 1)
				{
					// Update upload if exists
					$query = $db->getQuery(true);
					$query->select('id')
						->from($db->quoteName('#__emundus_uploads'))
						->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum))
						->where($db->quoteName('attachment_id') . ' = ' . (int) self::ATTACHMENT_ID)
						->where($db->quoteName('user_id') . ' = ' . (int) $fnumInfo['applicant_id']);
					$db->setQuery($query);
					$upload_id = $db->loadResult();

					if (!empty($upload_id))
					{
						$upload->id       = $upload_id;
						$upload->modified = $now;
						if($db->updateObject('#__emundus_uploads', $upload, 'id'))
						{
							$executed = ActionExecutionStatusEnum::COMPLETED;
						}
						else
						{
							$executed = ActionExecutionStatusEnum::FAILED;
						}
					}
					else
					{
						if($db->insertObject('#__emundus_uploads', $upload))
						{
							$executed = ActionExecutionStatusEnum::COMPLETED;
						}
						else
						{
							$executed = ActionExecutionStatusEnum::FAILED;
						}
					}
				}
				else
				{
					if($db->insertObject('#__emundus_uploads', $upload))
					{
						$executed = ActionExecutionStatusEnum::COMPLETED;
					}
					else
					{
						$executed = ActionExecutionStatusEnum::FAILED;
					}
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error printing application file in ActionPrintApplication: ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');
				$this->addExecutionMessage(new ActionExecutionMessage($e->getMessage(), ActionMessageTypeEnum::ERROR));
				$executed = ActionExecutionStatusEnum::FAILED;
			}
		}

		return $executed;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				new ChoiceField(self::TEMPLATE_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PRINT_APPLICATION_PARAMETER_TEMPLATE_LABEL'), $this->getTemplateChoices()),
				new BooleanField(self::CAN_BE_VIEWED_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_PRINT_APPLICATION_PARAMETER_CAN_BE_VIEWED_LABEL'))
			];
		}

		return $this->parameters;
	}

	/**
	 * Whether the applicant sees the printed file in their documents. Automations configured before this
	 * parameter existed carry no value, and their document was visible: keep it that way.
	 */
	private function isVisibleToApplicant(): int
	{
		$canBeViewed = $this->getParameterValue(self::CAN_BE_VIEWED_PARAMETER);

		if ($canBeViewed === null)
		{
			return 1;
		}

		return (int) filter_var($canBeViewed, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Build the options of the printed PDF: those of the selected export template, or the print
	 * defaults (application steps only, every attachment appended) when none is selected.
	 */
	private function buildPdfOptions(bool $overwrite, string $langTag): PdfOptions
	{
		$constraints = $this->getSelectedTemplateConstraints();

		if (empty($constraints))
		{
			$options = new PdfOptions();
			$options->setStepTypes([self::APPLICATION_STEP_TYPE]);
			$options->setAllAttachments(true);
		}
		else
		{
			$options = PdfOptions::fromObject((object) $constraints);
		}

		// The automation was authorized when it was configured, so the export runs whatever rights the
		// automated task user holds on the programme of the file.
		$options->setSkipAccessCheck(true);
		$options->setLang($langTag);
		$options->setFilename($this->buildFilenameTemplate($options->getFilename(), $overwrite));

		return $options;
	}

	/**
	 * A template that stopped being a system one leaves the parameter's choices, and ChoiceField nulls
	 * the stale id before execution — so only "gone" and "not PDF anymore" have to be handled here.
	 *
	 * @return array The constraints of the selected export template, empty when none is selected or
	 *               when the selected one no longer holds a PDF export.
	 */
	private function getSelectedTemplateConstraints(): array
	{
		$templateId = (int) $this->getParameterValue(self::TEMPLATE_PARAMETER);
		if ($templateId <= 0)
		{
			return [];
		}

		$template = (new ExportRepository())->getExportTemplate($templateId);
		if (empty($template))
		{
			$this->addExecutionMessage(new ActionExecutionMessage(
				Text::sprintf('COM_EMUNDUS_AUTOMATION_ACTION_PRINT_APPLICATION_TEMPLATE_NOT_FOUND', $templateId),
				ActionMessageTypeEnum::WARNING
			));

			return [];
		}

		$constraints = json_decode($template->constraints, true);
		if (!is_array($constraints) || ($constraints['format'] ?? null) !== ExportFormatEnum::PDF->value)
		{
			$this->addExecutionMessage(new ActionExecutionMessage(
				Text::sprintf('COM_EMUNDUS_AUTOMATION_ACTION_PRINT_APPLICATION_TEMPLATE_NOT_PDF', $templateId),
				ActionMessageTypeEnum::WARNING
			));

			return [];
		}

		return $constraints;
	}

	/**
	 * The filename template handed to PdfService, which renders its tags and sanitizes it. Without the
	 * overwrite setting a timestamp keeps every run on its own file, as the previous PDF stays attached.
	 */
	private function buildFilenameTemplate(string $filename, bool $overwrite): string
	{
		if ($filename === '')
		{
			$filename = self::FALLBACK_FILENAME;
		}

		if (str_ends_with($filename, '.pdf'))
		{
			$filename = substr($filename, 0, -4);
		}

		return $overwrite ? $filename : $filename . '_' . date('Ymd_His');
	}

	/**
	 * @return ChoiceFieldValue[] The PDF export templates a sysadmin saved as system ones. A personal
	 *                            template must never drive an automation behind its owner's back.
	 */
	private function getTemplateChoices(): array
	{
		if (empty($this->templateChoices))
		{
			$templates = (new ExportRepository())->getSystemExportTemplates();

			foreach ($templates as $template)
			{
				if ($template->format !== ExportFormatEnum::PDF->value)
				{
					continue;
				}

				$this->templateChoices[] = new ChoiceFieldValue($template->id, $template->name);
			}
		}

		return $this->templateChoices;
	}

	public function getLabelForLog(): string
	{
		$labelForLog = $this->getLabel();

		$templateId = (int) $this->getParameterValue(self::TEMPLATE_PARAMETER);
		if (!empty($templateId))
		{
			foreach ($this->getTemplateChoices() as $template)
			{
				if ($template->getValue() == $templateId)
				{
					$labelForLog .= ' (' . $template->getLabel() . ') ';
					break;
				}
			}
		}

		return $labelForLog;
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}
}