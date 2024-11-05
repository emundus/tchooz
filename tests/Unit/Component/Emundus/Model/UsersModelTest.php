<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use Joomla\Tests\Unit\UnitTestCase;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelUsers
 */
class UsersModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '', $className = null)
	{
		parent::__construct('users', $data, $dataName, 'EmundusModelUsers');
	}

	/**
	 * @covers EmundusModelUsers::getNonApplicantId
	 * Function getNonApplicantId return an array of array containing user_id entry key
	 * It should only return user_ids that are not only applicant (at least one profile is not an applicant profile)
	 * @return void
	 */
	public function testgetNonApplicantId()
	{
		$this->assertSame([], $this->model->getNonApplicantId(0));

		$this->assertSame([], $this->model->getNonApplicantId($this->dataset['applicant']), 'User with only applicant profile should not appear in the list of non applicant users');

		$nonApplicantIds = $this->model->getNonApplicantId($this->dataset['coordinator']);
		$this->assertNotEmpty($nonApplicantIds, 'User with at least one non applicant profile should appear in the list of non applicant users');

		$user_is_not_applicant = false;
		foreach ($nonApplicantIds as $nonApplicantId) {
			if ($nonApplicantId['user_id'] == $this->dataset['coordinator']) {
				$user_is_not_applicant = true;
			}
		}
		$this->assertTrue($user_is_not_applicant, 'Non applicant user appears in the list of non applicant users');

		$nonApplicantIds = $this->model->getNonApplicantId([$this->dataset['coordinator'], $this->dataset['applicant']]);
		$this->assertNotEmpty($nonApplicantIds, 'Passing an array of user ids should return an array of non applicant users');
		$this->assertSame(1, count($nonApplicantIds), 'Since only one of the two users is not an applicant, only one user should appear in the list of non applicant users');

		$this->assertSame([], $this->model->getNonApplicantId([$this->dataset['applicant'], $this->dataset['applicant'], 'test passing a string instead of an id']), 'Passing an incorrect array should return an empty array');
	}

	/**
	 * @covers EmundusModelUsers::affectToGroups
	 *
	 * @since version 1.0.0
	 */
	public function testaffectToGroups()
	{
		$this->assertEmpty($this->model->affectToGroups([], []), 'Passing an incorrect user id should return false');
		$this->assertEmpty($this->model->affectToGroups([['user_id' => 99999]], []), 'Passing an incorrect array of group ids should return false');

		$nonApplicantIds = $this->model->getNonApplicantId($this->dataset['coordinator']);
		$this->assertTrue($this->model->affectToGroups($nonApplicantIds, [1]), 'Affect user to group, using getNonApplicantId result should return true');
	}

	/**
	 * @covers EmundusModelUsers::getProfileDetails
	 *
	 * @since version 1.0.0
	 */
	public function testgetProfileDetails()
	{

		$this->assertEmpty($this->model->getProfileDetails(0), 'Passing an incorrect user id should return false');
		$profile = $this->model->getProfileDetails(9);
		$this->assertNotEmpty($profile, 'Passing a correct user id should return an array of profile details');
		$this->assertObjectHasProperty('label', $profile, 'Profile details should contain label');
		$this->assertObjectHasProperty('class', $profile, 'Profile details should contain class');
	}
}