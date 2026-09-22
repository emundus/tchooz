<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Dompdf\Dompdf;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionPrintApplication;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Export\ExportRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Tchooz\Services\Export\HeadersEnum;
use Joomla\Registry\Registry;

class ActionPrintApplicationTest extends UnitTestCase
{
	private User $coordinator;

	private Registry $config;

	public function setUp(): void
	{
		parent::setUp();
		ob_start();

		$this->coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$campaignRepository = new CampaignRepository();
		$campaign     = $campaignRepository->getById($this->dataset['campaign']);
		$campaign->setProfileId(1001);
		$campaignRepository->flush($campaign);

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('live_site', 'https://example.com');

		// The action prints as the automated task user, which therefore needs the export_pdf action on
		// the programme of the file.
		ComponentHelper::getParams('com_emundus')->set('automated_task_user', $this->dataset['coordinator']);
	}

	protected function tearDown(): void
	{
		$this->deletePrintedApplicationFiles();
		ob_end_clean();
		parent::tearDown();
	}

	/**
	 * The printed PDFs land on the shared sample file: leaving them behind makes any other test
	 * asserting on the attachments of that applicant fail.
	 */
	private function deletePrintedApplicationFiles(): void
	{
		$uploadRepository = new UploadRepository();
		$uploads          = $uploadRepository->get([
			'fnum'          => $this->dataset['fnum'],
			'attachment_id' => ActionPrintApplication::ATTACHMENT_ID,
		]);

		$db = Factory::getContainer()->get('DatabaseDriver');
		foreach ($uploads as $upload)
		{
			assert($upload instanceof UploadEntity);

			$path = $upload->getFileInternalPath();
			if (is_file($path))
			{
				unlink($path);
			}

			$query = $db->getQuery(true)
				->delete($db->quoteName('#__emundus_uploads'))
				->where($db->quoteName('id') . ' = ' . (int) $upload->getId());
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionPrintApplication::execute
	 * @covers \Tchooz\Services\Export\Pdf\PdfService::export
	 * @covers \EmundusModelApplication::getFormsPDF
	 *
	 * @return void
	 */
	public function testExecute(): void
	{
		$targetEntity = new ActionTargetEntity($this->coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);
		$action       = new ActionPrintApplication();

		$result = $action->execute($targetEntity);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result, 'The print application action should complete successfully');

		// Verify that the PDF file was generated
		$uploadRepository = new UploadRepository();
		$uploads = $uploadRepository->get(['fnum' => $this->dataset['fnum'], 'attachment_id' => ActionPrintApplication::ATTACHMENT_ID]);
		$this->assertNotEmpty($uploads);
		$pdfUpload = $uploads[0];
		assert($pdfUpload instanceof UploadEntity);
		$this->assertFileExists($pdfUpload->getFileInternalPath(), 'The generated PDF file should exist');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionPrintApplication::execute
	 * @covers \Tchooz\Repositories\Export\ExportRepository::getSystemExportTemplates
	 *
	 * @return void
	 */
	public function testExecuteWithExportTemplate(): void
	{
		$exportRepository = new ExportRepository();
		$templateId       = $exportRepository->saveExportTemplate(
			'Print action template',
			ExportFormatEnum::PDF,
			[HeadersEnum::FNUM->value],
			[],
			[HeadersEnum::STATUS->value],
			[],
			$this->coordinator->id,
			0,
			[],
			true
		);
		$this->assertGreaterThan(0, $templateId, 'The export template should be saved');

		// Only the templates a sysadmin flagged as system ones are selectable from an automation.
		$choices = array_filter(
			$exportRepository->getSystemExportTemplates(),
			static fn ($template) => (int) $template->id === $templateId
		);
		$this->assertNotEmpty($choices, 'The saved system template should be listed');

		$targetEntity = new ActionTargetEntity($this->coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);
		$action       = new ActionPrintApplication();
		$action->setParametersValuesFromArray([ActionPrintApplication::TEMPLATE_PARAMETER => $templateId]);

		$result = $action->execute($targetEntity);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result, 'The print application action should complete with a selected template');

		$uploadRepository = new UploadRepository();
		$uploads          = $uploadRepository->get(['fnum' => $this->dataset['fnum'], 'attachment_id' => ActionPrintApplication::ATTACHMENT_ID]);
		$this->assertNotEmpty($uploads);
		$pdfUpload = end($uploads);
		assert($pdfUpload instanceof UploadEntity);
		$this->assertFileExists($pdfUpload->getFileInternalPath(), 'The generated PDF file should exist');

		$exportRepository->deleteExportTemplate($templateId);
	}

	/**
	 * A template selecting attachments must land them inside the printed PDF: PdfService merges them
	 * into the exported file only, so the action has to register that one, never its own intermediate.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionPrintApplication::execute
	 * @covers \Tchooz\Services\Export\Pdf\PdfMerger::merge
	 *
	 * @return void
	 */
	public function testExecuteConcatenatesTemplateAttachments(): void
	{
		// _cv, one of the attachment types shipped with the base dataset.
		$attachmentId = 12;
		$uploadId     = $this->h_dataset->createSampleUpload(
			$this->dataset['fnum'],
			$this->dataset['campaign'],
			(int) $this->dataset['applicant'],
			$attachmentId
		);
		$this->assertGreaterThan(0, $uploadId, 'The sample attachment should be saved');
		$this->writeSampleAttachmentFile($uploadId);

		$exportRepository = new ExportRepository();
		$withoutId        = $this->saveTemplate($exportRepository, 'Print without attachment', [], 'without_[FNUM]');
		$withId           = $this->saveTemplate($exportRepository, 'Print with attachment', [$attachmentId], 'with_[FNUM]');

		$pagesWithout = $this->countPagesOfPrintedPdf($withoutId, 'without_');
		$pagesWith    = $this->countPagesOfPrintedPdf($withId, 'with_');

		$this->assertGreaterThan(
			$pagesWithout,
			$pagesWith,
			'The attachment selected by the template should be concatenated to the printed PDF'
		);

		$exportRepository->deleteExportTemplate($withoutId);
		$exportRepository->deleteExportTemplate($withId);
		$this->h_dataset->deleteSampleUpload($uploadId);
	}

	/**
	 * The automation is authorized when it is configured, so printing must not depend on the export_pdf
	 * rights of the automated task user: PdfOptions::setSkipAccessCheck carries that intent.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionPrintApplication::execute
	 * @covers \Tchooz\Services\Export\Pdf\PdfOptions::setSkipAccessCheck
	 *
	 * @return void
	 */
	public function testExecuteWithAnAutomatedTaskUserWithoutExportRights(): void
	{
		$applicant = (int) $this->dataset['applicant'];
		$this->assertFalse(
			\EmundusHelperAccess::asAccessAction('export_pdf', 'c', $applicant, $this->dataset['fnum']),
			'The applicant is expected to hold no export_pdf right, otherwise this test proves nothing'
		);

		ComponentHelper::getParams('com_emundus')->set('automated_task_user', $applicant);

		$action = new ActionPrintApplication();
		$result = $action->execute(new ActionTargetEntity($this->coordinator, $this->dataset['fnum'], $applicant));

		$this->assertEquals(
			ActionExecutionStatusEnum::COMPLETED,
			$result,
			'The printed PDF should be produced even when the automated task user cannot export by itself'
		);
	}

	/**
	 * Deleting an export template must not break the automations pointing at it.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionPrintApplication::execute
	 *
	 * @return void
	 */
	public function testExecuteWithADeletedTemplateFallsBackToTheDefaultLayout(): void
	{
		$exportRepository = new ExportRepository();
		$templateId       = $this->saveTemplate($exportRepository, 'Deleted template', [], 'deleted_[FNUM]');
		$exportRepository->deleteExportTemplate($templateId);

		$action = new ActionPrintApplication();
		$action->setParametersValuesFromArray([ActionPrintApplication::TEMPLATE_PARAMETER => $templateId]);

		$result = $action->execute(new ActionTargetEntity($this->coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']));
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result, 'A deleted template should not fail the action');

		$uploadRepository = new UploadRepository();
		$uploads          = $uploadRepository->get(['fnum' => $this->dataset['fnum'], 'attachment_id' => ActionPrintApplication::ATTACHMENT_ID]);
		$printed          = array_filter($uploads, static fn ($upload) => str_starts_with($upload->getFilename(), 'deleted_'));
		$this->assertEmpty($printed, 'The deleted template should not drive the filename anymore');

		// ChoiceField already nulls a value absent from its choices, so the action never sees the stale
		// id and reports nothing: the invalidation is only traced in the com_emundus.action log.
		$this->assertEmpty($action->getExecutionMessages(), 'No execution message is reported for a template dropped upstream');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionPrintApplication::execute
	 *
	 * @return void
	 */
	public function testExecuteHidesThePrintedFileFromTheApplicantWhenAsked(): void
	{
		$this->assertSame(0, $this->printAndGetCanBeViewed(['can_be_viewed' => false]));
		$this->assertSame(0, $this->printAndGetCanBeViewed(['can_be_viewed' => '0']));
	}

	/**
	 * Automations configured before the parameter existed produce a document the applicant can see, and
	 * an update must not hide documents that are visible today.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionPrintApplication::execute
	 *
	 * @return void
	 */
	public function testExecuteKeepsThePrintedFileVisibleByDefault(): void
	{
		$this->assertSame(1, $this->printAndGetCanBeViewed([]));
		$this->assertSame(1, $this->printAndGetCanBeViewed(['can_be_viewed' => true]));
	}

	/**
	 * @param   array  $parameters  Values assigned to the action before running it.
	 *
	 * @return int can_be_viewed as persisted on the registered upload.
	 */
	private function printAndGetCanBeViewed(array $parameters): int
	{
		$action = new ActionPrintApplication();
		if (!empty($parameters))
		{
			$action->setParametersValuesFromArray($parameters);
		}

		$this->assertEquals(
			ActionExecutionStatusEnum::COMPLETED,
			$action->execute(new ActionTargetEntity($this->coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant'])),
			'The print application action should complete'
		);

		$uploads = (new UploadRepository())->get([
			'fnum'          => $this->dataset['fnum'],
			'attachment_id' => ActionPrintApplication::ATTACHMENT_ID,
		]);
		$this->assertNotEmpty($uploads);

		$upload = end($uploads);
		assert($upload instanceof UploadEntity);

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName('can_be_viewed'))
			->from($db->quoteName('#__emundus_uploads'))
			->where($db->quoteName('id') . ' = ' . (int) $upload->getId());
		$db->setQuery($query);

		return (int) $db->loadResult();
	}

	private function saveTemplate(ExportRepository $exportRepository, string $name, array $attachments, string $filename): int
	{
		return $exportRepository->saveExportTemplate(
			$name,
			ExportFormatEnum::PDF,
			[HeadersEnum::FNUM->value],
			[],
			[HeadersEnum::STATUS->value],
			$attachments,
			$this->coordinator->id,
			0,
			['filename' => $filename],
			true
		);
	}

	/**
	 * createSampleUpload only writes the database row; getAttachmentPDF skips any attachment with no
	 * readable file, so render a real one-page PDF at the expected location.
	 */
	private function writeSampleAttachmentFile(int $uploadId): void
	{
		$uploadRepository = new UploadRepository();
		$upload           = $uploadRepository->getById($uploadId);
		$path             = $upload->getFileInternalPath();

		if (!is_dir(dirname($path)))
		{
			mkdir(dirname($path), 0755, true);
		}

		$dompdf = new Dompdf();
		$dompdf->loadHtml('<p>Unit test attachment</p>');
		$dompdf->render();
		file_put_contents($path, $dompdf->output());

		$this->assertFileExists($path, 'The sample attachment file should exist');
	}

	private function countPagesOfPrintedPdf(int $templateId, string $filenamePrefix): int
	{
		$action = new ActionPrintApplication();
		$action->setParametersValuesFromArray([ActionPrintApplication::TEMPLATE_PARAMETER => $templateId]);

		$targetEntity = new ActionTargetEntity($this->coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);
		$this->assertEquals(
			ActionExecutionStatusEnum::COMPLETED,
			$action->execute($targetEntity),
			'The print application action should complete for template ' . $templateId
		);

		$uploadRepository = new UploadRepository();
		$uploads          = $uploadRepository->get(['fnum' => $this->dataset['fnum'], 'attachment_id' => ActionPrintApplication::ATTACHMENT_ID]);
		$printed          = array_filter($uploads, static fn ($upload) => str_starts_with($upload->getFilename(), $filenamePrefix));
		$this->assertNotEmpty($printed, 'The printed PDF should be registered as ' . $filenamePrefix . '*');

		$printedUpload = end($printed);
		assert($printedUpload instanceof UploadEntity);
		$this->assertFileExists($printedUpload->getFileInternalPath());

		preg_match_all('#/Type\s*/Page[^s]#', file_get_contents($printedUpload->getFileInternalPath()), $matches);

		return count($matches[0]);
	}
}