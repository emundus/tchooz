<?php
/**
 * @package     controllers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Services\Reference\InternalReferenceService;

class EmundusControllerReference extends EmundusController
{
	private InternalReferenceService $internalReferenceService;

	function __construct($config = array())
	{
		parent::__construct($config);

		$this->internalReferenceService    = new InternalReferenceService(
			new DateProvider(),
			new ApplicationFileRepository()
		);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => ActionEnum::CUSTOM_REFERENCE, 'mode' => CrudEnum::CREATE]
	])]
	public function generate(): EmundusResponse
	{
		$fnums = Factory::getApplication()->getUserState('com_emundus.files.generatereference.fnums');
		if (empty($fnums))
		{
			throw new \Exception(Text::_('COM_EMUNDUS_ERROR_NO_FILES_SELECTED'));
		}

		$referencesSerialized = $this->internalReferenceService->generatePreviewReferences($fnums);

		return EmundusResponse::ok($referencesSerialized, Text::_('COM_EMUNDUS_CUSTOM_REFERENCES_GENERATED_SUCCESSFULLY'));
	}
	
	public function save(): EmundusResponse
	{
		$fnums = Factory::getApplication()->getUserState('com_emundus.files.generatereference.fnums');
		if (empty($fnums))
		{
			throw new \Exception(Text::_('COM_EMUNDUS_ERROR_NO_FILES_SELECTED'));
		}

		try
		{
			$this->internalReferenceService->generateReferences($fnums);
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_CUSTOM_REFERENCES_SAVED_SUCCESSFULLY'));
	}
}