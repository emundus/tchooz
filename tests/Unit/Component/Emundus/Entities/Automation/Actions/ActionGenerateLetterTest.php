<?php

namespace Unit\Component\Emundus\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionGenerateLetter;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;

class ActionGenerateLetterTest extends UnitTestCase
{
	public function setUp(): void
	{
		parent::setUp();

		define('EMUNDUS_PATH_ABS', JPATH_ROOT .'/images/emundus/files/');
		define('EMUNDUS_PATH_REL', 'images/emundus/files/');
	}


	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionGenerateLetter::execute
	 * @return void
	 */
	public function testExecute(): void
	{
		$letterAttachementId = $this->h_dataset->createSampleAttachment();
		$letterId = $this->h_dataset->createSampleLetter($letterAttachementId, 1);

		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$target = new ActionTargetEntity($coord, $this->dataset['fnum'], $this->dataset['applicant']);
		$action = new ActionGenerateLetter([ActionGenerateLetter::LETTER_PARAMETER => [$letterId]]);

		$result = $action->execute($target);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);
		// we should find a generated letter in the attachments for the fnum

		if (!class_exists('EmundusModelApplication'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/application.php';
		}
		$m_application = new \EmundusModelApplication();
		$uploads = $m_application->getFileUploadsByAttachmentId($this->dataset['fnum'], $letterAttachementId);
		$this->assertNotEmpty($uploads, 'There should be generated letter uploads for the fnum and the letter attachment id');
	}
}