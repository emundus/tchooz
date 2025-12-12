<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ActionPrintApplication extends ActionEntity
{
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
				$app = Factory::getApplication();

				$offset   = $app->get('offset', 'UTC');
				$dateTime = new \DateTime(gmdate("Y-m-d H:i:s"), new \DateTimeZone('UTC'));
				$dateTime = $dateTime->setTimezone(new \DateTimeZone($offset));
				$now      = $dateTime->format('Y-m-d H:i:s');

				$db = Factory::getContainer()->get('DatabaseDriver');

				$eMConfig              = ComponentHelper::getParams('com_emundus');
				$application_form_name = $eMConfig->get('application_form_name', "application_form");
				$overwrite_export_pdf  = $eMConfig->get('overwrite_old_export', 0);
				$export_path           = $eMConfig->get('export_path', null);

				$lang = $app->getLanguage();
				$lang->load('com_emundus', JPATH_SITE . '/components/com_emundus');

				if (!class_exists('EmundusModelFiles'))
				{
					require_once JPATH_SITE . '/components/com_emundus/models/files.php';
				}
				if (!class_exists('EmundusModelEmails'))
				{
					require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
				}
				$mFiles  = new \EmundusModelFiles();
				$mEmails = new \EmundusModelEmails();

				$fnum     = $context->getFile();
				$fnumInfo = $mFiles->getFnumInfos($fnum);

				// Build filename from tags, we are using helper functions found in the email model, not sending emails ;)
				$post                  = array('FNUM' => $fnum, 'CAMPAIGN_YEAR' => $fnumInfo['year'], 'PROGRAMME_CODE' => $fnumInfo['training']);
				$tags                  = $mEmails->setTags($fnumInfo['applicant_id'], $post, $fnum, '', $application_form_name . $export_path);
				$application_form_name = preg_replace($tags['patterns'], $tags['replacements'], $application_form_name);
				$application_form_name = $mEmails->setTagsFabrik($application_form_name, array($fnum));

				// Format filename
				$application_form_name = $mEmails->stripAccents($application_form_name);
				$application_form_name = preg_replace('/[^A-Za-z0-9 _.-]/', '', $application_form_name);
				$application_form_name = preg_replace('/\s/', '', $application_form_name);
				$application_form_name = strtolower($application_form_name);

				// Check if extension is present, if yes remove it
				if (str_ends_with($application_form_name, '.pdf'))
				{
					$application_form_name = substr($application_form_name, 0, -4);
				}

				$file_name        = $application_form_name . '.pdf';
				$target_file_name = $application_form_name . '.pdf';
				if ($overwrite_export_pdf != 1)
				{
					// Add timestamp to filename to avoid overwriting
					$target_file_name = $application_form_name . '_' . date('Ymd_His') . '.pdf';
				}

				$tmp_link       = JPATH_BASE . '/tmp/' . $file_name;
				$applicant_link = JPATH_BASE . '/images/emundus/files/' . $fnumInfo['applicant_id'] . '/' . $target_file_name;

				// If a file exists with that name, delete it
				if (file_exists($tmp_link))
				{
					unlink($tmp_link);
				}

				$result = $mFiles->generatePDF([$fnum], $file_name, 1, 0, 1, 1);

				// If export path is defined
				if (!empty($export_path))
				{
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

				$upload = (object) [
					'fnum'           => $fnum,
					'attachment_id'  => 26,
					'user_id'        => $fnumInfo['applicant_id'],
					'can_be_deleted' => 0,
					'filename'       => $target_file_name
				];
				if ($overwrite_export_pdf == 1)
				{
					// Update upload if exists
					$query = $db->getQuery(true);
					$query->select('id')
						->from($db->quoteName('#__emundus_uploads'))
						->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum))
						->where($db->quoteName('attachment_id') . ' = 26')
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
				Log::add('Error generating letter in ActionPrintLetter: ' . $e->getMessage(), Log::ERROR, 'com_emundus.action');
				$executed = ActionExecutionStatusEnum::FAILED;
			}
		}

		return $executed;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}
}