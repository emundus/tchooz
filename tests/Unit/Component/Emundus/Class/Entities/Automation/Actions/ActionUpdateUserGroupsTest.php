<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use EmundusModelUsers;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateUserGroups;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;

class ActionUpdateUserGroupsTest extends UnitTestCase
{
	public function setUp(): void
	{
		parent::setUp();
		if (!class_exists('EmundusModelUsers'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
		}
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateUserGroups::execute
	 * @return void
	 */
	public function testExecute(): void
	{
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$userId = $this->h_dataset->createSampleUser(null, 'user.test+actionUpdateGrp+' . rand(0, 9999) . '@emundus.fr');
		$this->assertNotEmpty($userId);

		$evalGroup = 2;
		$action = new ActionUpdateUserGroups([
			ActionUpdateUserGroups::PARAMETER_ACTION_TYPE => ActionUpdateUserGroups::PARAMETER_ACTION_TYPE_ADD,
			ActionUpdateUserGroups::PARAMETER_USER_GROUPS => [$evalGroup]
		]);

		$targetEntity = new ActionTargetEntity($coord, null, $userId, []);
		$status = $action->execute($targetEntity);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $status);

		// check if it truly worked
		$usersModel = new \EmundusModelUsers();
		$userGroupIds = $usersModel->getUserGroups($targetEntity->getUserId(), 'Column');
		$this->assertContains($evalGroup, $userGroupIds, 'The user should belong to the evaluation group.');

		// Now remove from group
		$actionRemove = new ActionUpdateUserGroups([
			ActionUpdateUserGroups::PARAMETER_ACTION_TYPE => ActionUpdateUserGroups::PARAMETER_ACTION_TYPE_REMOVE,
			ActionUpdateUserGroups::PARAMETER_USER_GROUPS => [$evalGroup]
		]);
		$statusRemove = $actionRemove->execute($targetEntity);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $statusRemove);

		// check if it truly worked
		$userGroupIdsAfter = $usersModel->getUserGroups($targetEntity->getUserId(), 'Column');
		$this->assertNotContains($evalGroup, $userGroupIdsAfter, 'The user should not belong to the evaluation group anymore.');
	}

	/**
	 * Make sure that users cannot get automatically elevated to the all_rights group, it should be done manually only.
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateUserGroups::execute
	 * @return void
	 */
	public function testNoUserElevationPossible(): void
	{
		$emundusCmptConfig = ComponentHelper::getParams('com_emundus');
		$allRightsGrp = $emundusCmptConfig->get('all_rights_group', 1);

		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$userId = $this->h_dataset->createSampleUser(null, 'user.test+actionUpdateGrp+' . rand(0, 9999) . '@emundus.fr');
		$this->assertNotEmpty($userId);

		$action = new ActionUpdateUserGroups([
			ActionUpdateUserGroups::PARAMETER_ACTION_TYPE => ActionUpdateUserGroups::PARAMETER_ACTION_TYPE_ADD,
			ActionUpdateUserGroups::PARAMETER_USER_GROUPS => [$allRightsGrp]
		]);

		$targetEntity = new ActionTargetEntity($coord, null, $userId, []);
		// and exception should be thrown
		$this->expectException(\InvalidArgumentException::class);
		$action->execute($targetEntity);
	}
}