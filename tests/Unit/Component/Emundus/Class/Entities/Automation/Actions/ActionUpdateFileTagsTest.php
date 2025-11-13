<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateFileTags;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;

class ActionUpdateFileTagsTest extends UnitTestCase
{

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateFileTags::execute
	 * @return void
	 */
	public function testExecute(): void
	{
		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context = new ActionTargetEntity($coord, $fnum, 0, []);
		$action = new ActionUpdateFileTags([ActionUpdateFileTags::TAGS_PARAMETER => [1], ActionUpdateFileTags::ADD_OR_REMOVE_PARAMETER => ActionUpdateFileTags::ADD]);
		$result = $action->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		if (!class_exists('EmundusModelFiles'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
		}
		$m_files = new \EmundusModelFiles();
		$tags = $m_files->getTagsByFnum([$fnum]);
		$this->assertContains(1, array_column($tags, 'id_tag'));

		// Now remove the tag
		$action->setParameterValues(ActionUpdateFileTags::ADD_OR_REMOVE_PARAMETER, ActionUpdateFileTags::REMOVE);
		$result = $action->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);
		$tags = $m_files->getTagsByFnum([$fnum]);
		$this->assertNotContains(1, array_column($tags, 'id_tag'));
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateFileTags::execute
	 * @return void
	 */
	public function testExecuteNoTags(): void
	{
		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context = new ActionTargetEntity($coord, $fnum, 0, []);
		$action = new ActionUpdateFileTags([ActionUpdateFileTags::TAGS_PARAMETER => [], ActionUpdateFileTags::ADD_OR_REMOVE_PARAMETER => ActionUpdateFileTags::ADD]);

		$this->expectException(\InvalidArgumentException::class, 'No tags provided should throw an exception');
		$action->execute($context);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateFileTags::execute
	 * @return void
	 */
	public function testExecuteRemoveTagsNotAssociated(): void
	{
		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context = new ActionTargetEntity($coord, $fnum, 0, []);
		$action = new ActionUpdateFileTags([ActionUpdateFileTags::TAGS_PARAMETER => [9999], ActionUpdateFileTags::ADD_OR_REMOVE_PARAMETER => ActionUpdateFileTags::REMOVE]);
		$result = $action->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result, 'Removing a tag that is not associated should still complete successfully');
	}
}