<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\TargetPredefinitions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitions\ApplicantOtherFilesPredefinition;

class ApplicantOtherFilesPredefinitionTest extends UnitTestCase
{
	private ApplicantOtherFilesPredefinition $predefinition;

	private User $coordinatorUser;

	public function setUp(): void
	{
		parent::setUp();

		$this->coordinatorUser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$this->predefinition = new ApplicantOtherFilesPredefinition();
	}

	/**
	 * @covers \Tchooz\Entities\Automation\TargetPredefinitions\ApplicantOtherFilesPredefinition::resolve
	 * @return void
	 */
	public function testResolve()
	{
		$ctx = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], $this->dataset['applicant']);
		$newTargets = $this->predefinition->resolve($ctx);
		$this->assertIsArray($newTargets);
		$this->assertEmpty($newTargets, 'No other files exist for this applicant.');

		// Now, let's add another file for the same applicant to test multiple files scenario
		$secondFnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$newTargets = $this->predefinition->resolve($ctx);
		$this->assertIsArray($newTargets);
		$this->assertCount(1, $newTargets, 'One other file should be found for this applicant.');
		$this->assertEquals($secondFnum, $newTargets[0]->getFile(), 'The found file should match the second file created.');
	}

}