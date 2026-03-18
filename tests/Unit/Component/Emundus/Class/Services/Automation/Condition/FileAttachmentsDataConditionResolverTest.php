<?php

namespace Unit\Component\Emundus\Class\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Services\Automation\Condition\FileAttachmentsDataConditionResolver;

class FileAttachmentsDataConditionResolverTest extends UnitTestCase
{
	private FileAttachmentsDataConditionResolver $resolver;

	private ActionTargetEntity $context;

	public function setUp(): void
	{
		parent::setUp();
		$this->resolver = new FileAttachmentsDataConditionResolver();
		$this->context = new ActionTargetEntity(
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			(int)$this->dataset['applicant']
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FileAttachmentsDataConditionResolver::getAvailableFields
	 * @return void
	 */
	public function testGetAvailableFields(): void
	{
		$fields = $this->resolver->getAvailableFields([]);
		$this->assertIsArray($fields, 'Expected an array of fields');
		$this->assertNotEmpty($fields, 'Expected at least one field to be available');
	}

	/**
	 * @covers \Tchooz\Services\Automation\Condition\FileAttachmentsDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValue(): void
	{
		$fields = $this->resolver->getAvailableFields([]);
		$uploadId = $this->h_dataset->createSampleUpload($this->dataset['fnum'], $this->dataset['campaign'], $this->dataset['applicant'], $fields[0]->getName());

		$value = $this->resolver->resolveValue($this->context, $fields[0]->getName());
		$this->assertNotNull($value, 'Expected a non-null value for the existing attachment type');
		$this->assertIsArray($value, 'Expected the resolved value to be an array of uploads');
		$this->assertEquals($uploadId, $value[0]->getId(), 'Expected the resolved upload ID to match the created upload ID');
	}
}