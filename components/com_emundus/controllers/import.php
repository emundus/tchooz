<?php

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Services\Import\EntityImporterRegistry;
use Tchooz\Services\Import\ImportModelGenerator;
use Tchooz\Services\Import\ImportOptions;
use Tchooz\Services\Import\ImportPipeline;
use Tchooz\Services\Import\Source\ImportSourceFactory;

defined('_JEXEC') or die;

class EmundusControllerImport extends EmundusController
{
	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplicationInterface $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		Log::addLogger(['text_file' => 'com_emundus.import.php'], Log::ALL, array('com_emundus.import'));
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function getEntityImportInformation(): EmundusResponse
	{
		$response = EmundusResponse::fail(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);

		$type = $this->input->getString('type', '');

		if (!empty($type) && EmundusHelperAccess::asAccessAction($type, CrudEnum::CREATE->value, $this->app->getIdentity()->id))
		{
			$registry = EntityImporterRegistry::default();

			if ($registry->has($type))
			{
				$importer = $registry->get($type);
				$response = EmundusResponse::ok([
					'fields' => $importer->getColumnMap()->describe(),
					'conflictModesSupported' => array_map(fn($mode) => $mode->value, $importer->getSupportedModes()),
					'formatsSupported' => ImportSourceFactory::SUPPORTED_FORMATS
				]);
			}
			else
			{
				$response = EmundusResponse::fail(Text::_('NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
			}
		}

		return $response;
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function dryrun(): EmundusResponse
	{
		return $this->runPipeline(dryRun: true);
	}

	#[AccessAttribute(AccessLevelEnum::PARTNER)]
	public function import(): EmundusResponse
	{
		return $this->runPipeline(dryRun: false);
	}

	private function runPipeline(bool $dryRun): EmundusResponse
	{
		$type = $this->input->getString('type', '');
		if (empty($type) || !EmundusHelperAccess::asAccessAction($type, CrudEnum::CREATE->value, $this->app->getIdentity()->id))
		{
			return EmundusResponse::fail(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$mode = $this->input->getString('mode', 'skip');
		$mode = ImportConflictModeEnum::tryFrom(strtolower($mode)) ?? ImportConflictModeEnum::SKIP;


		$registry = EntityImporterRegistry::default();

		if (!$registry->has($type))
		{
			return EmundusResponse::fail(Text::_('NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
		}

		$importer = $registry->get($type);

		if (!in_array($mode, $importer->getSupportedModes(), true))
		{
			return EmundusResponse::fail(
				Text::sprintf('COM_EMUNDUS_IMPORT_MODE_NOT_SUPPORTED', $mode->value, $type),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}

		$uploadedFile = $this->input->files->get('file');

		if (empty($uploadedFile))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UPLOAD_ERROR'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		$ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

		if (!ImportSourceFactory::supports($ext))
		{
			return EmundusResponse::fail(Text::_('COM_EMUNDUS_IMPORT_UNSUPPORTED_FORMAT'), EmundusResponse::HTTP_BAD_REQUEST);
		}

		try
		{
			$source = ImportSourceFactory::fromFile($uploadedFile['tmp_name'], $ext, $uploadedFile['name']);

			$options = new ImportOptions(
				dryRun: $dryRun,
				userId: $this->app->getIdentity()->id,
				conflictMode: $mode
			);

			$pipeline = new ImportPipeline();
			$report   = $pipeline->run($source, $importer, $options);

			return EmundusResponse::ok($report->toArray());
		}
		catch (Throwable $e)
		{
			Log::add(
				sprintf(
					'Import failed for type "%s" (mode "%s", dry run: %s): %s %s in %s:%d',
					$type,
					$mode->value,
					$dryRun ? 'yes' : 'no',
					get_class($e),
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				),
				Log::ERROR,
				'com_emundus.import'
			);

			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_IMPORT_UNEXPECTED_ERROR'),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}
	}

	#[AccessAttribute(AccessLevelEnum::COORDINATOR)]
	public function getimportmodel(): EmundusResponse
	{
		$type = $this->input->getString('type', '');

		if (empty($type) || !EmundusHelperAccess::asAccessAction($type, CrudEnum::READ->value, $this->app->getIdentity()->id))
		{
			return EmundusResponse::fail(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$registry = EntityImporterRegistry::default();
		if (!$registry->has($type))
		{
			return EmundusResponse::fail(Text::_('NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
		}

		try
		{
			$format  = $this->input->getString('format', 'csv') === 'xlsx' ? 'xlsx' : 'csv';
			$columns = $registry->get($type)->getColumnMap()->describeWithReferentials();

			return EmundusResponse::ok((new ImportModelGenerator())->build($type, $format, $columns));
		}
		catch (Throwable $e)
		{
			Log::add(
				sprintf(
					'Import model generation failed for type "%s": %s in %s:%d',
					$type,
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				),
				Log::ERROR,
				'com_emundus.import'
			);

			return EmundusResponse::fail(
				Text::_('COM_EMUNDUS_IMPORT_MODEL_GENERATION_ERROR'),
				EmundusResponse::HTTP_BAD_REQUEST
			);
		}
	}
}
