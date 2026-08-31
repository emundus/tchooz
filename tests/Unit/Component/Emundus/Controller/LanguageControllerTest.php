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
use Joomla\Input\Input;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tchooz\Controller\LanguageController;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Language\LanguageEntity;
use Tchooz\Entities\List\ListResult;
use Tchooz\Repositories\Language\LanguageRepository;
use Tchooz\Transformers\Language\TranslationListItemTransformer;

/**
 * @package     Unit\Component\Emundus\Controller
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Controller\LanguageController
 */
class LanguageControllerTest extends TestCase
{
	/** @var LanguageController&MockObject */
	private $controller;

	/** @var Input&MockObject */
	private $input;

	/** @var LanguageRepository&MockObject */
	private $languageRepository;

	/** @var TranslationListItemTransformer&MockObject */
	private $transformer;

	protected function setUp(): void
	{
		parent::setUp();

		$this->input = $this->getMockBuilder(Input::class)
			->disableOriginalConstructor()
			->addMethods(['getInt', 'getString'])
			->onlyMethods(['get'])
			->getMock();

		$this->languageRepository = $this->createMock(LanguageRepository::class);
		$this->transformer        = $this->createMock(TranslationListItemTransformer::class);

		$user        = $this->createMock(User::class);
		$user->id    = 42;
		$user->email = 'admin@example.com';

		$this->controller = $this->getMockBuilder(LanguageController::class)
			->disableOriginalConstructor()
			->onlyMethods(['checkToken'])
			->getMock();
		$this->controller->method('checkToken')->willReturn(true);

		$this->controller->setInput($this->input);
		$this->controller->setUser($user);
		$this->setPrivateProperty($this->controller, 'languageRepository', $this->languageRepository);
		$this->setPrivateProperty($this->controller, 'transformer', $this->transformer);
	}

	private function setPrivateProperty(object $object, string $property, mixed $value): void
	{
		$reflection = new \ReflectionProperty(LanguageController::class, $property);
		$reflection->setAccessible(true);
		$reflection->setValue($object, $value);
	}

	/**
	 * Configure the mocked Input so getInt / getString / get resolve from a per-name map.
	 */
	private function configureInput(array $ints = [], array $strings = [], array $arrays = []): void
	{
		$this->input->method('getInt')->willReturnCallback(
			static fn(string $name, int $default = 0) => $ints[$name] ?? $default
		);
		$this->input->method('getString')->willReturnCallback(
			static fn(string $name, string $default = '') => $strings[$name] ?? $default
		);
		$this->input->method('get')->willReturnCallback(
			static fn(string $name, $default = null, $filter = 'cmd') => $arrays[$name] ?? $default
		);
	}

	private function makeTranslation(int $id = 0): LanguageEntity
	{
		return new LanguageEntity(
			'MY_TAG',
			'fr-FR',
			'Override value',
			'Override value',
			'override',
			id: $id
		);
	}

	// -------------------------------------------------------------------------
	// gettranslations
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Controller\LanguageController::gettranslations
	 * @return void
	 */
	public function testGetTranslationsReturnsOkWithDataAndCount(): void
	{
		$this->configureInput(
			['lim' => 0, 'page' => 1, 'form' => 0],
			['recherche' => '', 'sort' => 'ASC', 'order_by' => 'tag', 'lang_code' => 'fr-FR', 'published' => '']
		);

		$this->languageRepository->method('buildOrderBy')->willReturn('');
		$this->languageRepository->expects($this->once())
			->method('getList')
			->willReturn(new ListResult([(object) ['tag' => 'MY_TAG']], 7));
		$this->transformer->method('transformAll')->willReturn([['tag' => 'MY_TAG']]);

		$response = $this->controller->gettranslations();

		$this->assertInstanceOf(EmundusResponse::class, $response, 'gettranslations should return an EmundusResponse');
		$this->assertSame(EmundusResponse::HTTP_OK, $response->getCode(), 'gettranslations should return a 200 response');
		$data = $response->getData();
		$this->assertArrayHasKey('datas', $data, 'response data should contain a datas key');
		$this->assertArrayHasKey('count', $data, 'response data should contain a count key');
		$this->assertSame(7, $data['count'], 'count should be the total items reported by the repository');
	}

	// -------------------------------------------------------------------------
	// getplatformlanguages
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Controller\LanguageController::getplatformlanguages
	 * @return void
	 */
	public function testGetPlatformLanguagesReturnsOnlyPublished(): void
	{
		$this->languageRepository->method('getLanguages')->willReturn([
			(object) ['lang_code' => 'fr-FR', 'published' => 1],
			(object) ['lang_code' => 'en-GB', 'published' => 0],
			(object) ['lang_code' => 'de-DE', 'published' => 1],
		]);

		$response = $this->controller->getplatformlanguages();

		$this->assertInstanceOf(EmundusResponse::class, $response, 'getplatformlanguages should return an EmundusResponse');
		$this->assertSame(EmundusResponse::HTTP_OK, $response->getCode(), 'getplatformlanguages should return a 200 response');
		$languages = $response->getData();
		$this->assertCount(2, $languages, 'only published languages should be returned');
		$codes = array_map(static fn(object $l) => $l->lang_code, $languages);
		$this->assertNotContains('en-GB', $codes, 'the unpublished language should be filtered out');
	}

	// -------------------------------------------------------------------------
	// addtranslation
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Controller\LanguageController::addtranslation
	 * @return void
	 */
	public function testAddTranslationFailsWhenTagIsEmpty(): void
	{
		$this->configureInput([], ['tag' => ''], ['overrides' => ['fr-FR' => 'value']]);

		$response = $this->controller->addtranslation();

		$this->assertInstanceOf(EmundusResponse::class, $response, 'addtranslation should return an EmundusResponse');
		$this->assertSame(EmundusResponse::HTTP_BAD_REQUEST, $response->getCode(), 'an empty tag should return a 400 response');
	}

	/**
	 * @covers \Tchooz\Controller\LanguageController::addtranslation
	 * @return void
	 */
	public function testAddTranslationFailsWhenNoOverrideProvided(): void
	{
		$this->configureInput([], ['tag' => 'MY_TAG'], ['overrides' => ['fr-FR' => '']]);

		$response = $this->controller->addtranslation();

		$this->assertSame(EmundusResponse::HTTP_BAD_REQUEST, $response->getCode(), 'no non-empty override should return a 400 response');
	}

	/**
	 * @covers \Tchooz\Controller\LanguageController::addtranslation
	 * @return void
	 */
	public function testAddTranslationFailsWhenLanguageUnknown(): void
	{
		$this->configureInput([], ['tag' => 'MY_TAG'], ['overrides' => ['zz-ZZ' => 'value']]);
		$this->languageRepository->method('getLanguages')->willReturn([
			(object) ['lang_code' => 'fr-FR', 'published' => 1],
		]);

		$response = $this->controller->addtranslation();

		$this->assertSame(EmundusResponse::HTTP_BAD_REQUEST, $response->getCode(), 'an override for an unpublished language should return a 400 response');
	}

	/**
	 * @covers \Tchooz\Controller\LanguageController::addtranslation
	 * @return void
	 */
	public function testAddTranslationFailsWhenTagAlreadyExists(): void
	{
		$this->configureInput([], ['tag' => 'MY_TAG'], ['overrides' => ['fr-FR' => 'value']]);
		$this->languageRepository->method('getLanguages')->willReturn([
			(object) ['lang_code' => 'fr-FR', 'published' => 1],
		]);
		$this->languageRepository->method('tagExists')->willReturn(true);

		$response = $this->controller->addtranslation();

		$this->assertSame(EmundusResponse::HTTP_CONFLICT, $response->getCode(), 'an existing tag should return a 409 response');
	}

	/**
	 * @covers \Tchooz\Controller\LanguageController::addtranslation
	 * @return void
	 */
	public function testAddTranslationCreatesAndReturnsOk(): void
	{
		$this->configureInput([], ['tag' => 'MY_TAG'], ['overrides' => ['fr-FR' => 'value']]);
		$this->languageRepository->method('getLanguages')->willReturn([
			(object) ['lang_code' => 'fr-FR', 'published' => 1],
		]);
		$this->languageRepository->method('tagExists')->willReturn(false);
		$this->languageRepository->expects($this->once())->method('flush')->willReturn(true);
		$this->transformer->method('transform')->willReturn((object) ['tag' => 'MY_TAG']);

		$response = $this->controller->addtranslation();

		$this->assertInstanceOf(EmundusResponse::class, $response, 'addtranslation should return an EmundusResponse');
		$this->assertSame(EmundusResponse::HTTP_OK, $response->getCode(), 'a valid translation should return a 200 response');
	}

	// -------------------------------------------------------------------------
	// savetranslation
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Controller\LanguageController::savetranslation
	 * @return void
	 */
	public function testSaveTranslationFailsWhenParametersMissing(): void
	{
		$this->configureInput(['id' => 0], ['lang_code' => '', 'override' => '']);

		$response = $this->controller->savetranslation();

		$this->assertSame(EmundusResponse::HTTP_BAD_REQUEST, $response->getCode(), 'missing id / lang_code should return a 400 response');
	}

	/**
	 * @covers \Tchooz\Controller\LanguageController::savetranslation
	 * @return void
	 */
	public function testSaveTranslationFailsWhenTranslationNotFound(): void
	{
		$this->configureInput(['id' => 5], ['lang_code' => 'fr-FR', 'override' => 'new value']);
		$this->languageRepository->method('getById')->with(5)->willReturn(null);

		$response = $this->controller->savetranslation();

		$this->assertSame(EmundusResponse::HTTP_NOT_FOUND, $response->getCode(), 'an unknown id should return a 404 response');
	}

	/**
	 * @covers \Tchooz\Controller\LanguageController::savetranslation
	 * @return void
	 */
	public function testSaveTranslationUpdatesAndReturnsOk(): void
	{
		$this->configureInput(['id' => 5], ['lang_code' => 'fr-FR', 'override' => 'new value']);
		$this->languageRepository->method('getById')->with(5)->willReturn($this->makeTranslation(5));
		$this->languageRepository->expects($this->once())->method('flush')->willReturn(true);
		$this->transformer->method('transform')->willReturn((object) ['tag' => 'MY_TAG']);

		$response = $this->controller->savetranslation();

		$this->assertInstanceOf(EmundusResponse::class, $response, 'savetranslation should return an EmundusResponse');
		$this->assertSame(EmundusResponse::HTTP_OK, $response->getCode(), 'a valid update should return a 200 response');
	}

	// -------------------------------------------------------------------------
	// deletetranslation
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Controller\LanguageController::deletetranslation
	 * @return void
	 */
	public function testDeleteTranslationFailsWhenIdMissing(): void
	{
		$this->configureInput(['id' => 0]);

		$response = $this->controller->deletetranslation();

		$this->assertSame(EmundusResponse::HTTP_BAD_REQUEST, $response->getCode(), 'a missing id should return a 400 response');
	}

	/**
	 * @covers \Tchooz\Controller\LanguageController::deletetranslation
	 * @return void
	 */
	public function testDeleteTranslationFailsWhenTranslationNotFound(): void
	{
		$this->configureInput(['id' => 5]);
		$this->languageRepository->method('getById')->with(5)->willReturn(null);

		$response = $this->controller->deletetranslation();

		$this->assertSame(EmundusResponse::HTTP_NOT_FOUND, $response->getCode(), 'an unknown id should return a 404 response');
	}

	/**
	 * @covers \Tchooz\Controller\LanguageController::deletetranslation
	 * @return void
	 */
	public function testDeleteTranslationDeletesAndReturnsOk(): void
	{
		$this->configureInput(['id' => 5]);
		$this->languageRepository->method('getById')->with(5)->willReturn($this->makeTranslation(5));
		$this->languageRepository->expects($this->once())->method('delete')->with(5)->willReturn(true);

		$response = $this->controller->deletetranslation();

		$this->assertInstanceOf(EmundusResponse::class, $response, 'deletetranslation should return an EmundusResponse');
		$this->assertSame(EmundusResponse::HTTP_OK, $response->getCode(), 'a valid delete should return a 200 response');
	}
}
