<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;

class ActionUpdateStatusTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateStatus::execute
	 * @return void
	 */
	public function testExecute(): void
	{
		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$newStatus = 1;

		$context = new ActionTargetEntity($coord, $fnum, 0, []);
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$result = $action->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		// verify that the status was updated in the database
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('status'))
			->from($db->quoteName('#__emundus_campaign_candidature'))
			->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
		$db->setQuery($query);
		$status = $db->loadResult();
		$this->assertEquals($newStatus, $status);
	}
}