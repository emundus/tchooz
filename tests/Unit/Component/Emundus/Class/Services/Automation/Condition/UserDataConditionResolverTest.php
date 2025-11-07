<?php

namespace Unit\Component\Emundus\Class\Services\Automation\Condition;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Services\Automation\Condition\UserDataConditionResolver;

class UserDataConditionResolverTest extends UnitTestCase
{
	private UserDataConditionResolver $resolver;

	private ActionTargetEntity $context;

	public function setUp(): void
	{
		parent::setUp();

		$this->resolver = new UserDataConditionResolver();
		$this->context = new ActionTargetEntity(
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			(int)$this->dataset['applicant']
		);
	}

	/**
	 * @covers UserDataConditionResolver::resolveValue
	 * @return void
	 */
	public function testResolveValue(): void
	{
		$firstname = $this->resolver->resolveValue($this->context, 'firstname');
		$this->assertNotEmpty($firstname);

		$lastname = $this->resolver->resolveValue($this->context, 'lastname');
		$this->assertNotEmpty($lastname);

		$email = $this->resolver->resolveValue($this->context, 'email');
		$this->assertNotEmpty($email);
		$this->assertMatchesRegularExpression('/^.+@.+\..+$/', $email);

		$group = $this->resolver->resolveValue($this->context, 'group');
		$this->assertIsArray($group);

		$profile = $this->resolver->resolveValue($this->context, 'profile');
		$this->assertNotEmpty($profile);
		$this->assertContains('applicant', $profile);
	}
}