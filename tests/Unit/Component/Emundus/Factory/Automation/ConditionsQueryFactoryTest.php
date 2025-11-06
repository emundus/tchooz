<?php

namespace Unit\Component\Emundus\Factory\Automation;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Factories\Automation\ConditionsQueryFactory;
use Tchooz\Services\Automation\ConditionRegistry;

class ConditionsQueryFactoryTest extends UnitTestCase
{
	private ConditionsQueryFactory $factory;

	private User $coordinatorUser;

	public function setUp(): void
	{
		parent::setUp();
		$registry = new ConditionRegistry();
		$this->factory = new ConditionsQueryFactory($this->db, $registry);
		$this->coordinatorUser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
	}

	/**
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 */
	public function testBuildConditionsQuery(): void
	{
		$query = $this->factory->buildConditionsQuery([]);
		$this->assertEquals('', $query);

		$campaignId = $this->dataset['campaign'];
		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CAMPAIGNDATA, 'id', ConditionOperatorEnum::EQUALS, $campaignId);
		$query = $this->factory->buildConditionsQuery([$condition]);
		$this->assertStringContainsString($this->db->quoteName('esc.id') . ' = ' . $this->db->quote($campaignId), $query->__toString());

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results');
			$this->assertContains($this->dataset['fnum'], $results, 'The fnum from the dataset should be in the results');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query);
		}

		$failingCondition = new ConditionEntity(2, 1, ConditionTargetTypeEnum::CAMPAIGNDATA, 'id', ConditionOperatorEnum::EQUALS, 999999);
		$query = $this->factory->buildConditionsQuery([$failingCondition]);

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertEmpty($results, 'The query should return no results for a non-matching campaign ID');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query);
		}
	}

	/**
	 * @return void
	 * @throws \Exception
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 */
	public function testBuildConditionsQueryWithMultipleConditions(): void
	{
		$campaignId = $this->dataset['campaign'];
		$condition1 = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CAMPAIGNDATA, 'id', ConditionOperatorEnum::EQUALS, $campaignId);
		$condition2 = new ConditionEntity(2, 1, ConditionTargetTypeEnum::CAMPAIGNDATA, 'published', ConditionOperatorEnum::EQUALS, '1');

		$query = $this->factory->buildConditionsQuery([$condition1, $condition2]);
		$this->assertStringContainsString($this->db->quoteName('esc.id') . ' = ' . $this->db->quote($campaignId), $query->__toString());
		$this->assertStringContainsString($this->db->quoteName('esc.published') . ' = ' . $this->db->quote('1'), $query->__toString());

		$this->assertEquals(1, substr_count($query, 'INNER JOIN'), 'Same joins are not duplicated in the query');

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results');
			$this->assertContains($this->dataset['fnum'], $results, 'The fnum from the dataset should be in the results');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}
	}

	/**
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 * @return void
	 */
	public function testBuildConditionsQueryWithMultipleTargetTypes(): void
	{
		$campaignId = $this->dataset['campaign'];
		$programId = $this->dataset['program']['programme_id'];
		$condition1 = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CAMPAIGNDATA, 'id', ConditionOperatorEnum::EQUALS, $campaignId);
		$condition2 = new ConditionEntity(2, 1, ConditionTargetTypeEnum::PROGRAMDATA, 'id', ConditionOperatorEnum::EQUALS, $programId);

		$query = $this->factory->buildConditionsQuery([$condition1, $condition2]);
		$this->assertStringContainsString($this->db->quoteName('esc.id') . ' = ' . $this->db->quote($campaignId), $query->__toString());
		$this->assertStringContainsString($this->db->quoteName('esp.id') . ' = ' . $this->db->quote($programId), $query->__toString());

		$this->assertEquals(2, substr_count($query, 'INNER JOIN'), 'Expected two different joins for different target types');

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results');
			$this->assertContains($this->dataset['fnum'], $results, 'The fnum from the dataset should be in the results');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}
	}

	/**
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 * @return void
	 */
	public function testBuildConditionsQueryWithUserData(): void
	{
		$applicantEmail = $this->dataset['applicant_email'];
		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::USERDATA, 'email', ConditionOperatorEnum::EQUALS, $applicantEmail);
		$query = $this->factory->buildConditionsQuery([$condition], TargetTypeEnum::FILE);

		// we are looking for fnums based on user data, so the query should contain a join to the users table
		$this->assertStringContainsString($this->db->quoteName('u.email') . ' = ' . $this->db->quote($applicantEmail), $query->__toString());
		$this->assertStringContainsString('INNER JOIN', $query, 'The query should contain a join for user data');

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results');
			$this->assertContains($this->dataset['fnum'], $results, 'The fnum from the dataset should be in the results');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}

		$failingCondition = new ConditionEntity(2, 1, ConditionTargetTypeEnum::USERDATA, 'email', ConditionOperatorEnum::EQUALS, 'wrongemail@example.com');
		$query = $this->factory->buildConditionsQuery([$condition, $failingCondition], TargetTypeEnum::FILE);

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertEmpty($results, 'The query should return no results for a non-matching email');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}
	}

	/**
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 * @return void
	 */
	public function testBuildConditionsQueryWithUserDataOnProfile()
	{
		$applicantProfile = 1000;
		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::USERDATA, 'profile', ConditionOperatorEnum::EQUALS, $applicantProfile);
		$query = $this->factory->buildConditionsQuery([$condition], TargetTypeEnum::FILE);

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results, because the applicant has the profile ' . $applicantProfile);
			$this->assertContains($this->dataset['fnum'], $results, 'The fnum from the dataset should be in the results based on profile condition');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}
	}

	/**
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 * @return void
	 */
	public function testBuildConditionsQueryWithUserTarget()
	{
		$applicantProfile = 1000;
		$applicantId = $this->dataset['applicant'];

		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::USERDATA, 'profile', ConditionOperatorEnum::EQUALS, $applicantProfile);
		$query = $this->factory->buildConditionsQuery([$condition], TargetTypeEnum::USER);

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results, because the applicant has the profile ' . $applicantProfile);
			$this->assertContains($applicantId, $results, 'The user ID from the dataset should be in the results based on profile condition');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}
	}

	/**
	 * Tester de récupérer des utilisateurs ayant un dossier sur la campagne X
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 */
	public function testBuildConditionsQueryWithUserHavingFileOnCampaign(): void
	{
		$campaignId = $this->dataset['campaign'];
		$condition  = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CAMPAIGNDATA, 'id', ConditionOperatorEnum::EQUALS, $campaignId);
		$query      = $this->factory->buildConditionsQuery([$condition], TargetTypeEnum::USER);

		try
		{
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results, because there are users with files on the campaign ' . $campaignId);
			$this->assertContains($this->dataset['applicant'], $results, 'The user ID from the dataset should be in the results based on having a file on the campaign');
		}
		catch (\Exception $e)
		{
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}
	}

	/**
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 * @return void
	 * @throws \Exception
	 */
	public function testBuildConditionQueryOnFormData(): void
	{
		$formId = 102;
		$elementId = $this->h_dataset->getFormElementForTest($formId, 'status');
		$fieldName = $formId . '.' . $elementId;
		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::FORMDATA, $fieldName, ConditionOperatorEnum::EQUALS, '0');
		$query = $this->factory->buildConditionsQuery([$condition], TargetTypeEnum::FILE);

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results, because there are files with the form data condition');
			$this->assertContains($this->dataset['fnum'], $results, 'The fnum from the dataset should be in the results based on form data condition');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}

		$failingCondition = new ConditionEntity(2, 1, ConditionTargetTypeEnum::FORMDATA, $fieldName, ConditionOperatorEnum::EQUALS, '999');
		$query = $this->factory->buildConditionsQuery([$failingCondition], TargetTypeEnum::FILE);

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertEmpty($results, 'The query should return no results for a non-matching form data value');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}
	}

	/**
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 * @return void
	 */
	public function testBuildConditionQueryOnFormDataWithComplexFields(): void
	{
		$formId = $this->h_dataset->getUnitTestFabrikForm();
		$this->h_dataset->insertUnitTestFormData($this->dataset['applicant'], $this->dataset['fnum']);
		$dbJoinMultiElementId = $this->h_dataset->getFormElementForTest($formId, 'dbjoin_multi');

		$fieldName = $formId . '.' . $dbJoinMultiElementId;
		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::FORMDATA, $fieldName, ConditionOperatorEnum::CONTAINS, 17);

		$query = $this->factory->buildConditionsQuery([$condition], TargetTypeEnum::FILE);
		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results, because there are files with the form data condition');
			$this->assertContains($this->dataset['fnum'], $results, 'The fnum from the dataset should be in the results based on form data condition');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}

		$failingCondition = new ConditionEntity(2, 1, ConditionTargetTypeEnum::FORMDATA, $fieldName, ConditionOperatorEnum::NOT_EQUALS, 17);
		$query = $this->factory->buildConditionsQuery([$failingCondition], TargetTypeEnum::FILE);

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertEmpty($results, 'The query should return no results for a non-matching form data value');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}
	}

	/**
	 * @covers ConditionsQueryFactory::buildConditionsQuery
	 * @return void
	 * @throws \Exception
	 */
	public function testBuildConditionsQueryWithNoConditionWithValueAsCONST_SAME_AS_CURRENT_FILE(): void
	{
		$fnum2 = $this->h_dataset->createSampleFile($this->dataset['campaign'], (int)$this->dataset['applicant']);
		$originalContext = new ActionTargetEntity($this->coordinatorUser, $this->dataset['fnum'], null, []);
		$context = new ActionTargetEntity($this->coordinatorUser, $fnum2, null, [], null, $originalContext);

		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CAMPAIGNDATA, 'id', ConditionOperatorEnum::EQUALS, ConditionEntity::SAME_AS_CURRENT_FILE);
		$query = $this->factory->buildConditionsQuery([$condition], TargetTypeEnum::FILE, $context);

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotEmpty($results, 'The query should return some results');
			$this->assertContains($fnum2, $results, 'The fnum2 should be in the results');
			$this->assertContains($this->dataset['fnum'], $results, 'The original fnum should be in the results');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}


		$otherCampaignId = $this->h_dataset->createSampleCampaign($this->dataset['program'], $this->dataset['coordinator']);
		$fnum3 = $this->h_dataset->createSampleFile($otherCampaignId, (int)$this->dataset['applicant']);
		$context = new ActionTargetEntity($this->coordinatorUser, $fnum3, null, [], null, $originalContext);

		$query = $this->factory->buildConditionsQuery([$condition], TargetTypeEnum::FILE, $context);
		try {
			$this->db->setQuery($query);
			$results = $this->db->loadColumn();

			$this->assertNotContains($fnum3, $results, 'The fnum3 should not be in the results as it belongs to a different campaign');
		} catch (\Exception $e) {
			$this->fail('Query execution failed: ' . $e->getMessage() . $query->__toString());
		}
	}
}