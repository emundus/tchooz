<?php
/**
 * @package     Unit\Component\Emundus\Controller
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Controller;

use Joomla\CMS\User\User;
use Joomla\Tests\Unit\UnitTestCase;

if (!class_exists('EmundusControllerApplication'))
{
	require_once JPATH_SITE . '/components/com_emundus/controllers/application.php';
}

/**
 * @package     Unit\Component\Emundus\Controller
 *
 * @since       version 2.25.2
 * @covers      \EmundusControllerApplication
 */
class ApplicationControllerTest extends UnitTestCase
{
	private \EmundusControllerApplication $controller;

	private int $collaboratorId = 0;

	protected function setUp(): void
	{
		parent::setUp();

		$this->controller = new \EmundusControllerApplication(['base_path' => JPATH_SITE . '/components/com_emundus']);
	}

	protected function tearDown(): void
	{
		if (!empty($this->collaboratorId))
		{
			$this->h_dataset->deleteSampleUser($this->collaboratorId);
		}

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Collaboration — who can manage collaborators of a file
	// -------------------------------------------------------------------------

	/**
	 * @covers \EmundusControllerApplication::canManageCollaboration
	 * @return void
	 */
	public function testCanManageCollaborationWhenUserOwnsTheFileThenReturnsTrue(): void
	{
		$this->controller->setUser(new User($this->dataset['applicant']));

		$this->assertTrue(
			self::callPrivateMethod($this->controller, 'canManageCollaboration', [$this->dataset['fnum'], (int) $this->dataset['ccid']]),
			'The owner of the file should manage its collaborators'
		);
	}

	/**
	 * @covers \EmundusControllerApplication::canManageCollaboration
	 * @return void
	 */
	public function testCanManageCollaborationWhenUserIsACollaboratorThenReturnsFalse(): void
	{
		$this->collaboratorId = $this->h_dataset->createSampleUser(1000, 'collaborator_unit_test' . rand(0, 999999) . '@emundus.fr');
		$this->controller->setUser(new User($this->collaboratorId));

		$this->assertFalse(
			self::callPrivateMethod($this->controller, 'canManageCollaboration', [$this->dataset['fnum'], (int) $this->dataset['ccid']]),
			'An applicant who does not own the file must not manage its collaborators, even if the file is shared with them'
		);
	}

	/**
	 * @covers \EmundusControllerApplication::canManageCollaboration
	 * @return void
	 */
	public function testCanManageCollaborationWhenCcidDoesNotMatchTheFnumThenReturnsFalse(): void
	{
		$this->controller->setUser(new User($this->dataset['applicant']));

		$this->assertFalse(
			self::callPrivateMethod($this->controller, 'canManageCollaboration', [$this->dataset['fnum'], (int) $this->dataset['ccid'] + 1]),
			'Owning the fnum must not allow acting on the collaborators of another ccid'
		);
	}

	/**
	 * @covers \EmundusControllerApplication::canManageCollaboration
	 * @return void
	 */
	public function testCanManageCollaborationWhenUserIsAManagerThenReturnsTrue(): void
	{
		$this->controller->setUser(new User($this->dataset['coordinator']));

		$this->assertTrue(
			self::callPrivateMethod($this->controller, 'canManageCollaboration', [$this->dataset['fnum'], (int) $this->dataset['ccid']]),
			'A manager (partner access level) should manage the collaborators of a file'
		);
	}

	/**
	 * @covers \EmundusControllerApplication::canManageCollaboration
	 * @return void
	 */
	public function testCanManageCollaborationWhenFnumIsUnknownThenReturnsFalse(): void
	{
		$this->controller->setUser(new User($this->dataset['applicant']));

		$this->assertFalse(
			self::callPrivateMethod($this->controller, 'canManageCollaboration', ['0000000000000000000000000000', (int) $this->dataset['ccid']]),
			'An unknown fnum must be refused'
		);
	}

	/**
	 * @covers \EmundusControllerApplication::canManageCollaboration
	 * @return void
	 */
	public function testCanManageCollaborationWhenParametersAreEmptyThenReturnsFalse(): void
	{
		$this->controller->setUser(new User($this->dataset['applicant']));

		$this->assertFalse(
			self::callPrivateMethod($this->controller, 'canManageCollaboration', ['', 0]),
			'Missing fnum and ccid must be refused'
		);
	}
}
