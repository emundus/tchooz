<?php

namespace Unit\Component\Emundus\Class\Factories;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Factories\LayoutFactory;

/**
 * Sous-classe testable de LayoutFactory.
 * Override les méthodes protégées qui appellent des helpers statiques
 * non disponibles en contexte de test unitaire.
 */
class TestableLayoutFactory extends LayoutFactory
{
	protected static function checkCoordinatorAccess(int $userId): bool
	{
		return true;
	}

	protected static function checkSysadminAccess(int $userId): bool
	{
		return false;
	}

	protected static function resolveMenuLink(string $link): string
	{
		return '/mocked-route/' . md5($link);
	}

	protected static function getCurrentGitHash(): string
	{
		return 'mocked-git-hash';
	}
}

/**
 * @package     Unit\Component\Emundus\Class\Factories
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\LayoutFactory
 */
class LayoutFactoryTest extends UnitTestCase
{
	private User $user;

	protected function setUp(): void
	{
		parent::setUp();

		if (!class_exists('EmundusHelperCache'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
		}

		$this->user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
	}

	private function createObject(string $label, ?string $menuLink = null): object
	{
		$obj        = new \stdClass();
		$obj->label = $label;
		$obj->menuLink = $menuLink;

		return $obj;
	}

	// ==========================================
	// prepareVueData tests
	// ==========================================

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataReturnsArray(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertIsArray($data);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataContainsRequiredCamelCaseKeys(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertArrayHasKey('shortLang', $data);
		$this->assertArrayHasKey('currentLang', $data);
		$this->assertArrayHasKey('manyLanguages', $data);
		$this->assertArrayHasKey('defaultLang', $data);
		$this->assertArrayHasKey('coordinatorAccess', $data);
		$this->assertArrayHasKey('sysadminAccess', $data);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataContainsRequiredSnakeCaseKeys(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertArrayHasKey('short_lang', $data);
		$this->assertArrayHasKey('current_lang', $data);
		$this->assertArrayHasKey('many_languages', $data);
		$this->assertArrayHasKey('default_lang', $data);
		$this->assertArrayHasKey('coordinator_access', $data);
		$this->assertArrayHasKey('sysadmin_access', $data);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataContainsHashKey(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertArrayHasKey('hash', $data);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataCamelAndSnakeKeysAreEqual(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertEquals($data['shortLang'], $data['short_lang']);
		$this->assertEquals($data['currentLang'], $data['current_lang']);
		$this->assertEquals($data['manyLanguages'], $data['many_languages']);
		$this->assertEquals($data['defaultLang'], $data['default_lang']);
		$this->assertEquals($data['coordinatorAccess'], $data['coordinator_access']);
		$this->assertEquals($data['sysadminAccess'], $data['sysadmin_access']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataShortLangIsTwoChars(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertEquals(2, strlen($data['shortLang']));
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataCurrentLangIsLangTag(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertMatchesRegularExpression('/^[a-z]{2}-[A-Z]{2}$/', $data['currentLang']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataManyLanguagesIsStringZeroOrOne(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertContains($data['manyLanguages'], ['0', '1']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataCoordinatorAccessUsesOverride(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertTrue($data['coordinatorAccess']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataSysadminAccessUsesOverride(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertFalse($data['sysadminAccess']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataHashIsString(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertIsString($data['hash']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataCoordinatorAccessIsBool(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertIsBool($data['coordinatorAccess']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::prepareVueData
	 */
	public function testPrepareVueDataSysadminAccessIsBool(): void
	{
		$data = TestableLayoutFactory::prepareVueData($this->user);

		$this->assertIsBool($data['sysadminAccess']);
	}

	// ==========================================
	// buildLongLayout — empty objects
	// ==========================================

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutReturnsArray(): void
	{
		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE', 'NO_DATA', []);

		$this->assertIsArray($result);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutReturnedArrayHasShortTagsKey(): void
	{
		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE', 'NO_DATA', []);

		$this->assertArrayHasKey('shortTags', $result);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutReturnedArrayHasLongTagsKey(): void
	{
		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE', 'NO_DATA', []);

		$this->assertArrayHasKey('longTags', $result);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithEmptyObjectsReturnsNoDataMessage(): void
	{
		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE', 'NO_DATA_MSG', []);

		$this->assertEquals('NO_DATA_MSG', $result['shortTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithEmptyObjectsReturnsNullLongTags(): void
	{
		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE', 'NO_DATA', []);

		$this->assertNull($result['longTags']);
	}

	// ==========================================
	// buildLongLayout — single object
	// ==========================================

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithSingleObjectWithoutMenuLink(): void
	{
		$objects = [$this->createObject('Mon Label')];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE', 'NO_DATA', $objects);

		$this->assertStringContainsString('Mon Label', $result['shortTags']);
		$this->assertStringContainsString('<span', $result['shortTags']);
		$this->assertStringNotContainsString('<a ', $result['shortTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithSingleObjectWithMenuLink(): void
	{
		$objects = [$this->createObject('Mon Label', 'index.php?option=com_emundus&view=test')];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE', 'NO_DATA', $objects);

		$this->assertStringContainsString('Mon Label', $result['shortTags']);
		$this->assertStringContainsString('<a ', $result['shortTags']);
		$this->assertStringContainsString('href=', $result['shortTags']);
		$this->assertStringContainsString('/mocked-route/', $result['shortTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithSingleObjectLongTagsIsNull(): void
	{
		$objects = [$this->createObject('Label')];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE', 'NO_DATA', $objects);

		$this->assertNull($result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithSingleObjectShortTagsContainsCssClasses(): void
	{
		$objects = [$this->createObject('Label')];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE', 'NO_DATA', $objects);

		$this->assertStringContainsString('em-campaign-tag', $result['shortTags']);
	}

	// ==========================================
	// buildLongLayout — multiple objects
	// ==========================================

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsReturnsLongTags(): void
	{
		$objects = [
			$this->createObject('Label 1'),
			$this->createObject('Label 2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertNotNull($result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsLongTagsContainsTitle(): void
	{
		$objects = [
			$this->createObject('Label 1'),
			$this->createObject('Label 2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('MY_TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringContainsString('MY_TITLE', $result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsLongTagsContainsAllLabels(): void
	{
		$objects = [
			$this->createObject('Premier'),
			$this->createObject('Deuxième'),
			$this->createObject('Troisième'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringContainsString('Premier', $result['longTags']);
		$this->assertStringContainsString('Deuxième', $result['longTags']);
		$this->assertStringContainsString('Troisième', $result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsShortTagsContainsCount(): void
	{
		$objects = [
			$this->createObject('Label 1'),
			$this->createObject('Label 2'),
			$this->createObject('Label 3'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'Il y a %s éléments', 'NO_DATA', $objects);

		$this->assertStringContainsString('3', $result['shortTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsWithoutMenuLinksUsesSpans(): void
	{
		$objects = [
			$this->createObject('Label 1'),
			$this->createObject('Label 2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringContainsString('<span', $result['longTags']);
		$this->assertStringNotContainsString('<a ', $result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsWithMenuLinksUsesAnchors(): void
	{
		$objects = [
			$this->createObject('Label 1', 'index.php?option=com_emundus&view=test1'),
			$this->createObject('Label 2', 'index.php?option=com_emundus&view=test2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringContainsString('<a ', $result['longTags']);
		$this->assertStringContainsString('href=', $result['longTags']);
		$this->assertStringContainsString('/mocked-route/', $result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsMixedMenuLinks(): void
	{
		$objects = [
			$this->createObject('Avec Lien', 'index.php?option=com_emundus&view=test'),
			$this->createObject('Sans Lien'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringContainsString('<a ', $result['longTags']);
		$this->assertStringContainsString('Avec Lien', $result['longTags']);
		$this->assertStringContainsString('<span', $result['longTags']);
		$this->assertStringContainsString('Sans Lien', $result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsLongTagsContainsH2(): void
	{
		$objects = [
			$this->createObject('Label 1'),
			$this->createObject('Label 2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringContainsString('<h2', $result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsLongTagsContainsCssClasses(): void
	{
		$objects = [
			$this->createObject('Label 1'),
			$this->createObject('Label 2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringContainsString('em-campaign-tag', $result['longTags']);
		$this->assertStringContainsString('tw-flex', $result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsShortTagsIsNotLongTags(): void
	{
		$objects = [
			$this->createObject('Label 1'),
			$this->createObject('Label 2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertNotEquals($result['shortTags'], $result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsShortTagsDoesNotContainLabels(): void
	{
		$objects = [
			$this->createObject('SpecificLabel1'),
			$this->createObject('SpecificLabel2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringNotContainsString('SpecificLabel1', $result['shortTags']);
		$this->assertStringNotContainsString('SpecificLabel2', $result['shortTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsLongTagsWrappedInDivs(): void
	{
		$objects = [
			$this->createObject('Label 1'),
			$this->createObject('Label 2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringStartsWith('<div>', $result['longTags']);
		$this->assertStringEndsWith('</div>', $result['longTags']);
	}

	/**
	 * @covers \Tchooz\Factories\LayoutFactory::buildLongLayout
	 */
	public function testBuildLongLayoutWithMultipleObjectsShortTagsWrappedInDiv(): void
	{
		$objects = [
			$this->createObject('Label 1'),
			$this->createObject('Label 2'),
		];

		$result = TestableLayoutFactory::buildLongLayout('TITLE', 'SUBTITLE %s', 'NO_DATA', $objects);

		$this->assertStringStartsWith('<div>', $result['shortTags']);
		$this->assertStringEndsWith('</div>', $result['shortTags']);
	}
}