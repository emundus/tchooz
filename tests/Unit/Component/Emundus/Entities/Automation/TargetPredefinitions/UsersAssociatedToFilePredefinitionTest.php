<?php

namespace Unit\Component\Emundus\Entities\Automation\TargetPredefinitions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitions\UsersAssociatedToFilePredefinition;

class UsersAssociatedToFilePredefinitionTest extends UnitTestCase
{
	private UsersAssociatedToFilePredefinition $predefinition;

	private User $coordinatorUser;

	public function setUp(): void
	{
		parent::setUp();

		$this->coordinatorUser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$this->predefinition = new UsersAssociatedToFilePredefinition();
	}

	/**
	 * @covers \Tchooz\Entities\Automation\TargetPredefinitions\UsersAssociatedToFilePredefinition::resolve
	 * @return void
	 */
	public function testResolve(): void
	{
		$ctx = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], $this->dataset['applicant']);
		$newTargets = $this->predefinition->resolve($ctx);
		$this->assertIsArray($newTargets);
		$this->assertNotEmpty($newTargets, 'There should be users associated with the file.');

		$anotherApplicant = $this->h_dataset->createSampleUser(9, 'anotherapplicant@emundus.fr');

		// coordinator should be one of the users associated with the file
		// applicant should not be included in this list
		$userIds = array_map(fn($target) => $target->getUserId(), $newTargets);
		$this->assertContains($this->dataset['coordinator'], $userIds, 'Coordinator should be in the list of associated users.');
		$this->assertNotContains($this->dataset['applicant'], $userIds, 'Applicant should not be in the list of associated users.');
		$this->assertNotContains($anotherApplicant, $userIds, 'Another applicant should not be in the list of associated users.');

		// todo: add cases with partners, some that have and access and some that don't
	}
}