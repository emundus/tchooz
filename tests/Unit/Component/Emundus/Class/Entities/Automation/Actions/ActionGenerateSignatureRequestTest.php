<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionGenerateSignatureRequest;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\NumericSign\SignConnectorsEnum;
use Tchooz\Repositories\NumericSign\RequestRepository;

class ActionGenerateSignatureRequestTest extends UnitTestCase
{
	private RequestRepository $repository;

	public function setUp(): void
	{
		parent::setUp();
		$this->repository             = new RequestRepository();
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateSignatureRequest::execute
	 * @return void
	 * @throws \Exception
	 */
	public function testExecute(): void
	{
		$requestCountBefore = $this->repository->getCountRequests();

		$coord             = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$action            = new ActionGenerateSignatureRequest();
		$attachmentOptions = $action->getAttachmentFieldOptions();
		$this->h_dataset->createSampleUpload($this->dataset['fnum'], $this->dataset['campaign'], $this->dataset['applicant'], $attachmentOptions[0]->getValue());

		$action->setParametersValuesFromArray([
				'attachment'   => $attachmentOptions[0]->getValue(),
				'synchronizer' => SignConnectorsEnum::DOCUSIGN->value,
				'subject'      => 'Test Signature Request',
				'ordered'      => true,
				'signers'      => [
					[
						'signer_type' => 'fnum',
						'order'       => 1,
						'anchor'      => 'Sign here'
					]
				]
			]
		);

		$context = new ActionTargetEntity($coord, $this->dataset['fnum'], $this->dataset['applicant']);

		$result = $action->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		$requestCountAfter = $this->repository->getCountRequests();
		$this->assertEquals($requestCountBefore + 1, $requestCountAfter, 'A new signature request should have been created');
	}
}