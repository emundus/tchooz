<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Profile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Profile;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Profile\ProfileEntity;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Profile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Profile\ProfileEntity
 */
class ProfileEntityTest extends UnitTestCase
{
	private function makeProfile(
		int    $id           = 1,
		string $label        = 'Test Profile',
		string $description  = 'A description',
		bool   $published    = true,
		string $menutype     = 'mainmenu',
		int    $aclAroGroups = 2,
		string $class        = 'applicant'
	): ProfileEntity
	{
		return new ProfileEntity($id, $label, $description, $published, $menutype, $aclAroGroups, $class);
	}

	// -------------------------------------------------------------------------
	// Constructor / getters
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::__construct
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getId
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getLabel
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getDescription
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::isPublished
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getMenutype
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getAclAroGroups
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getClass
	 */
	public function testConstructorInitializesAllProperties(): void
	{
		$profile = $this->makeProfile(42, 'Master 2', 'Programme M2', false, 'profile42', 5, 'evaluator');

		$this->assertSame(42, $profile->getId());
		$this->assertSame('Master 2', $profile->getLabel());
		$this->assertSame('Programme M2', $profile->getDescription());
		$this->assertFalse($profile->isPublished());
		$this->assertSame('profile42', $profile->getMenutype());
		$this->assertSame(5, $profile->getAclAroGroups());
		$this->assertSame('evaluator', $profile->getClass());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::isPublished
	 */
	public function testIsPublishedReturnsTrueWhenPublished(): void
	{
		$profile = $this->makeProfile(published: true);
		$this->assertTrue($profile->isPublished());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::isPublished
	 */
	public function testIsPublishedReturnsFalseWhenUnpublished(): void
	{
		$profile = $this->makeProfile(published: false);
		$this->assertFalse($profile->isPublished());
	}

	// -------------------------------------------------------------------------
	// Setters — value updated
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setId
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getId
	 */
	public function testSetIdUpdatesId(): void
	{
		$profile = $this->makeProfile(id: 1);
		$profile->setId(99);
		$this->assertSame(99, $profile->getId());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setLabel
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getLabel
	 */
	public function testSetLabelUpdatesLabel(): void
	{
		$profile = $this->makeProfile(label: 'Old label');
		$profile->setLabel('New label');
		$this->assertSame('New label', $profile->getLabel());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setDescription
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getDescription
	 */
	public function testSetDescriptionUpdatesDescription(): void
	{
		$profile = $this->makeProfile(description: 'Old description');
		$profile->setDescription('New description');
		$this->assertSame('New description', $profile->getDescription());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setPublished
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::isPublished
	 */
	public function testSetPublishedToFalseUnpublishesProfile(): void
	{
		$profile = $this->makeProfile(published: true);
		$profile->setPublished(false);
		$this->assertFalse($profile->isPublished());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setPublished
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::isPublished
	 */
	public function testSetPublishedToTruePublishesProfile(): void
	{
		$profile = $this->makeProfile(published: false);
		$profile->setPublished(true);
		$this->assertTrue($profile->isPublished());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setMenutype
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getMenutype
	 */
	public function testSetMenutypeUpdatesMenutype(): void
	{
		$profile = $this->makeProfile(menutype: 'mainmenu');
		$profile->setMenutype('profile1000');
		$this->assertSame('profile1000', $profile->getMenutype());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setAclAroGroups
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getAclAroGroups
	 */
	public function testSetAclAroGroupsUpdatesAclAroGroups(): void
	{
		$profile = $this->makeProfile(aclAroGroups: 1);
		$profile->setAclAroGroups(1001);
		$this->assertSame(1001, $profile->getAclAroGroups());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setClass
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getClass
	 */
	public function testSetClassUpdatesClass(): void
	{
		$profile = $this->makeProfile(class: 'applicant');
		$profile->setClass('coordinator');
		$this->assertSame('coordinator', $profile->getClass());
	}

	// -------------------------------------------------------------------------
	// Fluent interface — every setter must return $this
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setId
	 */
	public function testSetIdReturnsSelf(): void
	{
		$profile = $this->makeProfile();
		$this->assertSame($profile, $profile->setId(2));
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setLabel
	 */
	public function testSetLabelReturnsSelf(): void
	{
		$profile = $this->makeProfile();
		$this->assertSame($profile, $profile->setLabel('Fluent'));
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setDescription
	 */
	public function testSetDescriptionReturnsSelf(): void
	{
		$profile = $this->makeProfile();
		$this->assertSame($profile, $profile->setDescription('Fluent'));
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setPublished
	 */
	public function testSetPublishedReturnsSelf(): void
	{
		$profile = $this->makeProfile();
		$this->assertSame($profile, $profile->setPublished(false));
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setMenutype
	 */
	public function testSetMenutypeReturnsSelf(): void
	{
		$profile = $this->makeProfile();
		$this->assertSame($profile, $profile->setMenutype('test'));
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setAclAroGroups
	 */
	public function testSetAclAroGroupsReturnsSelf(): void
	{
		$profile = $this->makeProfile();
		$this->assertSame($profile, $profile->setAclAroGroups(3));
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setClass
	 */
	public function testSetClassReturnsSelf(): void
	{
		$profile = $this->makeProfile();
		$this->assertSame($profile, $profile->setClass('evaluator'));
	}

	// -------------------------------------------------------------------------
	// Fluent chaining
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setId
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setLabel
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setDescription
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setPublished
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setMenutype
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setAclAroGroups
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setClass
	 */
	public function testFluentChainingUpdatesAllProperties(): void
	{
		$profile = $this->makeProfile();

		$profile
			->setId(10)
			->setLabel('Chained label')
			->setDescription('Chained description')
			->setPublished(false)
			->setMenutype('chained_menu')
			->setAclAroGroups(7)
			->setClass('evaluator');

		$this->assertSame(10, $profile->getId());
		$this->assertSame('Chained label', $profile->getLabel());
		$this->assertSame('Chained description', $profile->getDescription());
		$this->assertFalse($profile->isPublished());
		$this->assertSame('chained_menu', $profile->getMenutype());
		$this->assertSame(7, $profile->getAclAroGroups());
		$this->assertSame('evaluator', $profile->getClass());
	}

	// -------------------------------------------------------------------------
	// Edge cases
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::__construct
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getId
	 */
	public function testConstructorAcceptsIdZero(): void
	{
		$profile = $this->makeProfile(id: 0);
		$this->assertSame(0, $profile->getId());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::__construct
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getLabel
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getDescription
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getMenutype
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getClass
	 */
	public function testConstructorAcceptsEmptyStrings(): void
	{
		$profile = $this->makeProfile(label: '', description: '', menutype: '', class: '');

		$this->assertSame('', $profile->getLabel());
		$this->assertSame('', $profile->getDescription());
		$this->assertSame('', $profile->getMenutype());
		$this->assertSame('', $profile->getClass());
	}

	/**
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::setAclAroGroups
	 * @covers \Tchooz\Entities\Profile\ProfileEntity::getAclAroGroups
	 */
	public function testAclAroGroupsAcceptsZero(): void
	{
		$profile = $this->makeProfile(aclAroGroups: 5);
		$profile->setAclAroGroups(0);
		$this->assertSame(0, $profile->getAclAroGroups());
	}
}
