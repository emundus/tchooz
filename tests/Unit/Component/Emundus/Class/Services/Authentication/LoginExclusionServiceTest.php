<?php

namespace Unit\Component\Emundus\Class\Services\Authentication;

use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;
use Tchooz\Services\Authentication\LoginExclusionService;

/**
 * @package     Unit\Component\Emundus\Class\Services\Authentication
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Authentication\LoginExclusionService
 */
class LoginExclusionServiceTest extends TestCase
{
	/**
	 * Builds a service backed by the given component params.
	 *
	 * @param   array  $config
	 *
	 * @return LoginExclusionService
	 */
	private function makeService(array $config): LoginExclusionService
	{
		return new LoginExclusionService(new Registry($config));
	}

	// -------------------------------------------------------------------------
	// isEnabled
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isEnabled
	 * @return void
	 */
	public function testIsEnabledReturnsTrueWhenRestrictLoginIsOne(): void
	{
		$service = $this->makeService(['restrict_login' => 1]);

		$this->assertTrue($service->isEnabled(), 'isEnabled should be true when restrict_login is 1');
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isEnabled
	 * @return void
	 */
	public function testIsEnabledReturnsFalseWhenRestrictLoginIsZero(): void
	{
		$service = $this->makeService(['restrict_login' => 0]);

		$this->assertFalse($service->isEnabled(), 'isEnabled should be false when restrict_login is 0');
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isEnabled
	 * @return void
	 */
	public function testIsEnabledReturnsFalseWhenRestrictLoginIsMissing(): void
	{
		$service = $this->makeService([]);

		$this->assertFalse($service->isEnabled(), 'isEnabled should default to false when restrict_login is not set');
	}

	// -------------------------------------------------------------------------
	// getPatterns
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::getPatterns
	 * @return void
	 */
	public function testGetPatternsReturnsEmptyArrayWhenNotConfigured(): void
	{
		$service = $this->makeService([]);

		$this->assertSame([], $service->getPatterns(), 'getPatterns should return an empty array when no patterns are set');
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::getPatterns
	 * @return void
	 */
	public function testGetPatternsSplitsTrimsAndRemovesEmptyLines(): void
	{
		$service = $this->makeService([
			'restrict_login_patterns' => "  @etu\\.example\\.fr$  \n\n^banned_\r\n   ",
		]);

		$this->assertSame(
			['@etu\\.example\\.fr$', '^banned_'],
			$service->getPatterns(),
			'getPatterns should split on newlines, trim each line and drop empty lines'
		);
	}

	// -------------------------------------------------------------------------
	// getRejectionMessage
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::getRejectionMessage
	 * @return void
	 */
	public function testGetRejectionMessageReturnsConfiguredMessage(): void
	{
		$service = $this->makeService(['restrict_login_message' => 'Accès refusé aux étudiants.']);

		$this->assertSame(
			'Accès refusé aux étudiants.',
			$service->getRejectionMessage(),
			'getRejectionMessage should return the configured message when set'
		);
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::getRejectionMessage
	 * @return void
	 */
	public function testGetRejectionMessageReturnsDefaultKeyWhenEmpty(): void
	{
		$service = $this->makeService(['restrict_login_message' => '   ']);

		$this->assertSame(
			'PLG_USER_EMUNDUS_RESTRICT_LOGIN_DEFAULT_MESSAGE',
			$service->getRejectionMessage(),
			'getRejectionMessage should fall back to the default language key when the message is empty'
		);
	}

	// -------------------------------------------------------------------------
	// isExcluded — guards (disabled / no patterns)
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedReturnsFalseWhenDisabledEvenIfEmailMatches(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 0,
			'restrict_login_patterns' => '@etu\\.example\\.fr$',
		]);

		$this->assertFalse(
			$service->isExcluded('jdoe', 'jdoe@etu.example.fr'),
			'isExcluded should return false when the rule is disabled, even on a matching email'
		);
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedReturnsFalseWhenNoPatterns(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => '',
		]);

		$this->assertFalse(
			$service->isExcluded('jdoe', 'jdoe@etu.example.fr'),
			'isExcluded should return false when no pattern is configured'
		);
	}

	// -------------------------------------------------------------------------
	// isExcluded — matching (email / username / case)
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedReturnsTrueWhenEmailMatchesStudentPattern(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => '@etu\\.example\\.fr$',
		]);

		$this->assertTrue(
			$service->isExcluded('jdoe', 'student@etu.example.fr'),
			'isExcluded should return true when the email matches the student pattern'
		);
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedReturnsTrueWhenUsernameMatches(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => '^banned_',
		]);

		$this->assertTrue(
			$service->isExcluded('banned_user', 'someone@example.com'),
			'isExcluded should return true when the username matches a pattern'
		);
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedIsCaseInsensitive(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => '@etu\\.example\\.fr$',
		]);

		$this->assertTrue(
			$service->isExcluded('jdoe', 'JDOE@ETU.EXAMPLE.FR'),
			'isExcluded should match case-insensitively'
		);
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedReturnsFalseWhenNeitherUsernameNorEmailMatches(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => '@etu\\.example\\.fr$',
		]);

		$this->assertFalse(
			$service->isExcluded('jdoe', 'staff@example.fr'),
			'isExcluded should return false for a staff email that does not contain the etu. subdomain'
		);
	}

	// -------------------------------------------------------------------------
	// isExcluded — invalid patterns (fail-safe)
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedSkipsInvalidPatternButStillMatchesValidOne(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => "([invalid\n@etu\\.example\\.fr$",
		]);

		$this->assertTrue(
			$service->isExcluded('jdoe', 'student@etu.example.fr'),
			'isExcluded should ignore an invalid pattern and still match a subsequent valid one'
		);
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedReturnsFalseWhenOnlyPatternIsInvalid(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => '([invalid',
		]);

		$this->assertFalse(
			$service->isExcluded('jdoe', 'student@etu.example.fr'),
			'isExcluded should fail safe (no exclusion) when the only pattern is invalid'
		);
	}

	// -------------------------------------------------------------------------
	// isExcluded — @emundus.fr safety net
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedReturnsFalseForEmundusEmailEvenIfPatternMatches(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => '@emundus\\.fr$',
		]);

		$this->assertFalse(
			$service->isExcluded('staff', 'admin@emundus.fr'),
			'isExcluded must never exclude @emundus.fr emails, even when a pattern would match them'
		);
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedEmundusBypassIsCaseInsensitive(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => '@emundus\\.fr$',
		]);

		$this->assertFalse(
			$service->isExcluded('staff', 'Admin@EMUNDUS.FR'),
			'The @emundus.fr bypass must be case-insensitive'
		);
	}

	/**
	 * @covers \Tchooz\Services\Authentication\LoginExclusionService::isExcluded
	 * @return void
	 */
	public function testIsExcludedStillBlocksWhenEmailIsNotEmundus(): void
	{
		$service = $this->makeService([
			'restrict_login'          => 1,
			'restrict_login_patterns' => '@etu\\.example\\.fr$',
		]);

		$this->assertTrue(
			$service->isExcluded('jdoe', 'student@etu.example.fr'),
			'Non-@emundus.fr emails must still go through pattern matching'
		);
	}
}
