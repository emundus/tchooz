<?php

namespace Unit\Component\Emundus\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionShareFile;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;

class ActionShareFileTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionShareFile::execute
	 * @return void
	 */
	public function testExecute(): void
	{
		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/helpers/access.php';
		}

		$newUserId = $this->h_dataset->createSampleUser(2, 'newuser' . rand(0, 99999) . '@emundus.fr');
		$this->assertFalse(\EmundusHelperAccess::asAccessAction(1, 'r', $fnum, $newUserId));

		$originalContext = new ActionTargetEntity($coord, $fnum, 0, []);
		$context = new ActionTargetEntity($coord, null, $newUserId, [], null, $originalContext);
		$action = new ActionShareFile([ActionShareFile::ADD_OR_REMOVE_PARAMETER => ActionShareFile::ADD]);
		$result = $action->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);
		$this->assertTrue(\EmundusHelperAccess::asAccessAction(1, 'r', $newUserId, $fnum));
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionShareFile::execute
	 * @return void
	 */
	public function testExecuteRemoveAccess()
	{
		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		if (!class_exists('EmundusHelperAccess'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/helpers/access.php';
		}

		$newUserId = $this->h_dataset->createSampleUser(2, 'newuser' . rand(0, 99999) . '@emundus.fr');
		$this->assertFalse(\EmundusHelperAccess::asAccessAction(1, 'r', $fnum, $newUserId));

		$originalContext = new ActionTargetEntity($coord, $fnum, 0, []);
		$context = new ActionTargetEntity($coord, null, $newUserId, [], null, $originalContext);
		$action = new ActionShareFile([ActionShareFile::ADD_OR_REMOVE_PARAMETER => ActionShareFile::ADD]);
		$result = $action->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);
		$this->assertTrue(\EmundusHelperAccess::asAccessAction(1, 'r', $newUserId, $fnum));

		// Now remove access
		$actionRemove = new ActionShareFile([ActionShareFile::ADD_OR_REMOVE_PARAMETER => ActionShareFile::REMOVE]);
		$resultRemove = $actionRemove->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $resultRemove);
		$this->assertFalse(\EmundusHelperAccess::asAccessAction(1, 'r', $newUserId, $fnum));
	}
}