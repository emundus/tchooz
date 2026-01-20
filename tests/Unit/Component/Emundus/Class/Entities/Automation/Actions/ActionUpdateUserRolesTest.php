<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateUserRoles;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;

class ActionUpdateUserRolesTest extends UnitTestCase
{
	private int $evaluatorProfileId = 0;

	public function setUp(): void
	{
		parent::setUp();
		if (!class_exists('EmundusModelProfile'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/profile.php');
		}
		if (!class_exists('EmundusModelUsers'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
		}
		$usersModel = new \EmundusModelUsers();

		$partnerProfiles = $usersModel->getNonApplicantProfiles();
		foreach ($partnerProfiles as $profile)
		{
			if ($profile->is_evaluator == 1)
			{
				$this->evaluatorProfileId = $profile->id;
				break;
			}
		}
		$this->assertNotEmpty($this->evaluatorProfileId);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateUserGroups::execute
	 * @return void
	 * @throws \Exception
	 */
	public function testExecute(): void
	{
		$this->assertNotEmpty($this->evaluatorProfileId, 'Evaluator profile ID should be set in setUp method.');
		$coord  = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$userId = $this->h_dataset->createSampleUser(null, 'user.test+actionUpdateRole+' . rand(0, 9999) . '@emundus.fr');
		$this->assertNotEmpty($userId, 'Failed to create sample user.');

		$action       = new ActionUpdateUserRoles([
			ActionUpdateUserRoles::PARAMETER_ACTION_TYPE => ActionUpdateUserRoles::PARAMETER_ACTION_TYPE_ADD,
			ActionUpdateUserRoles::PARAMETER_USER_ROLES  => [$this->evaluatorProfileId]
		]);
		$targetEntity = new ActionTargetEntity($coord, null, $userId, []);
		$status       = $action->execute($targetEntity);

		if ($status !== ActionExecutionStatusEnum::COMPLETED) {
			$messages = $action->getExecutionMessages();
			$messageTexts = array_map(fn($msg) => $msg->getMessage(), $messages);
			$this->fail('Action execution failed with messages: ' . implode('; ', $messageTexts));
		}

		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $status);

		// check if it truly worked
		$profileModel = new \EmundusModelProfile();
		$profiles     = $profileModel->getUserProfiles($userId);
		$profileIds   = array_map(function ($profile) {
			return $profile->id;
		}, $profiles);

		$this->assertContains($this->evaluatorProfileId, $profileIds, 'The user should have the evaluator profile.');

		// Now remove from role
		$actionRemove = new ActionUpdateUserRoles([
			ActionUpdateUserRoles::PARAMETER_ACTION_TYPE => ActionUpdateUserRoles::PARAMETER_ACTION_TYPE_REMOVE,
			ActionUpdateUserRoles::PARAMETER_USER_ROLES  => [$this->evaluatorProfileId]
		]);
		$statusRemove = $actionRemove->execute($targetEntity);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $statusRemove);

		// check if it truly worked
		$profilesAfter   = $profileModel->getUserProfiles($userId);
		$profileIdsAfter = array_map(function ($profile) {
			return $profile->id;
		}, $profilesAfter);

		$this->assertNotContains($this->evaluatorProfileId, $profileIdsAfter, 'The user should not have the evaluator profile anymore.');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateUserRoles::execute
	 * @return void
	 */
	public function testFailExecutionWithInvalidUser()
	{
		$coord         = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$action        = new ActionUpdateUserRoles([
			ActionUpdateUserRoles::PARAMETER_ACTION_TYPE => ActionUpdateUserRoles::PARAMETER_ACTION_TYPE_ADD,
			ActionUpdateUserRoles::PARAMETER_USER_ROLES  => [$this->evaluatorProfileId]
		]);
		$invalidUserId = 999999;

		$targetEntity = new ActionTargetEntity($coord, null, $invalidUserId, []);
		$status       = $action->execute($targetEntity);

		$this->assertEquals(ActionExecutionStatusEnum::FAILED, $status);
	}

	/**
	 * Make sure that users cannot get automatically elevated to the an administration role, it should be done manually only.
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateUserRoles::execute
	 * @return void
	 */
	public function testNoUserElevationPossible(): void
	{
		$coord  = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$userId = $this->h_dataset->createSampleUser(null, 'user.test+actionUpdateRole+' . rand(0, 9999) . '@emundus.fr');
		$this->assertNotEmpty($userId);

		$superAdminProfile = 1;
		$action            = new ActionUpdateUserRoles([
			ActionUpdateUserRoles::PARAMETER_ACTION_TYPE => ActionUpdateUserRoles::PARAMETER_ACTION_TYPE_ADD,
			ActionUpdateUserRoles::PARAMETER_USER_ROLES  => [$superAdminProfile]
		]);
		$targetEntity      = new ActionTargetEntity($coord, null, $userId, []);
		$this->expectException(\InvalidArgumentException::class);
		$action->execute($targetEntity);
	}
}