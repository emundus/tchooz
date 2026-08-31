<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage  Repositories\Language
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Language;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Language\LanguageEntity;
use Tchooz\Repositories\Language\LanguageRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Language\LanguageRepository
 */
class LanguageRepositoryTest extends UnitTestCase
{
	private LanguageRepository $repository;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->repository = new LanguageRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::flush
	 */
	public function testFlush(): void
	{
		$this->repository->setLangCode('fr-FR');

		$tag = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);

		$language = new LanguageEntity(
			$tag,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		$executed = $this->repository->flush($language);
		$this->assertTrue($executed, 'flush method should return true on insert');
		$this->assertGreaterThan(0, $language->getId(), 'Inserted language should have an ID');

		$language->setOverride('Updated test language tag');
		$updated = $this->repository->flush($language);
		$this->assertTrue($updated, 'Language should be updated successfully');
		$this->assertEquals('Updated test language tag', $language->getOverride());

		$languageWithoutTag = new LanguageEntity(
			'',
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		try
		{
			$this->repository->flush($languageWithoutTag);
			$this->fail('Expected RuntimeException for empty tag was not thrown');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringContainsString('Language tag cannot be empty', $e->getMessage());
			$this->assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
		}

		$languageWithoutLangCode = new LanguageEntity(
			rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000),
			'',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		try
		{
			$this->repository->flush($languageWithoutLangCode);
			$this->fail('Expected RuntimeException for empty language code was not thrown');
		}
		catch (\RuntimeException $e)
		{
			$this->assertStringContainsString('Language code cannot be empty', $e->getMessage());
			$this->assertInstanceOf(\InvalidArgumentException::class, $e->getPrevious());
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::delete
	 */
	public function testDelete(): void
	{
		$this->repository->setLangCode('fr-FR');

		$tag = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);

		$language = new LanguageEntity(
			$tag,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		$inserted = $this->repository->flush($language);
		$this->assertTrue($inserted);
		$this->assertGreaterThan(0, $language->getId());

		$this->assertNotNull($this->repository->getById($language->getId()));

		$deleted = $this->repository->delete($language->getId());
		$this->assertTrue($deleted, 'delete method should return true');

		$this->assertNull($this->repository->getById($language->getId()));
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getCount
	 */
	public function testGetCount()
	{
		$this->repository->setLangCode('fr-FR');

		$tag1 = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);
		$tag2 = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);
		$tag3 = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);

		$language1 = new LanguageEntity(
			$tag1,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		$language2 = new LanguageEntity(
			$tag2,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		$language3 = new LanguageEntity(
			$tag3,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		$this->repository->flush($language1);
		$this->repository->flush($language2);
		$this->repository->flush($language3);

		$languagesCount = $this->repository->getCount();
		$this->assertGreaterThan(3, $languagesCount, 'The count should be greater or equal to 3');
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getAll
	 */
	public function testGetAll()
	{
		$this->repository->setLangCode('fr-FR');

		$tag1 = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);
		$tag2 = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);
		$tag3 = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);

		$language1 = new LanguageEntity(
			$tag1,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		$language2 = new LanguageEntity(
			$tag2,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		$language3 = new LanguageEntity(
			$tag3,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);

		$this->repository->flush($language1);
		$this->repository->flush($language2);
		$this->repository->flush($language3);

		$allLanguages = $this->repository->getAll();
		$this->assertIsArray($allLanguages, 'The getAll method should return an array');
		$this->assertGreaterThan(3, count($allLanguages), 'The count should be greater or equal to 3');
		$this->assertContainsOnlyInstancesOf(LanguageEntity::class, $allLanguages, 'The getAll method should return an array of LanguageEntity');
		$languagesIds = array_map(fn($c) => $c->getId(), $allLanguages);
		$this->assertContains($language1->getId(), $languagesIds, 'The language1 id found should be in the array');
		$this->assertContains($language2->getId(), $languagesIds, 'The language2 id found should be in the array');
		$this->assertContains($language3->getId(), $languagesIds, 'The language3 id found should be in the array');

		$allLanguagesWithLimit = $this->repository->getAll([], true, 10);
		$this->assertIsArray($allLanguages, 'The getAll method should return an array');
		$this->assertLessThanOrEqual(10, count($allLanguagesWithLimit), 'The count should be less or equal to 10');

		$allLanguagesWithoutLoadingEntity = $this->repository->getAll([], true, 1, 0, false);
		$this->assertNotEmpty($allLanguagesWithoutLoadingEntity, 'The allLanguagesWithoutLoadingEntity array should not be empty');
		$first = array_key_first($allLanguagesWithoutLoadingEntity);
		$this->assertIsObject($allLanguagesWithoutLoadingEntity[$first], 'The element of the array should be an object');
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getById
	 * @return void
	 */
	public function testGetById()
	{
		$this->repository->setLangCode('fr-FR');
		$language = new LanguageEntity(
			rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000),
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);
		$this->repository->flush($language);

		$languageFound = $this->repository->getById($language->getId());
		$this->assertNotNull($languageFound, 'The getById method should return a language entity');
		$this->assertEquals($languageFound->getId(), $language->getId(), 'The language entity found should have the same ID as the original');
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getByTag
	 * @return void
	 */
	public function testGetByTag()
	{
		$this->repository->setLangCode('fr-FR');
		$language = new LanguageEntity(
			rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000),
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);
		$this->repository->flush($language);

		$languageFound = $this->repository->getByTag($language->getTag());
		$this->assertNotNull($languageFound, 'The getByTag method should return a language entity');
		$this->assertEquals($languageFound->getId(), $language->getId(), 'The language entity found should have the same ID as the original');
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getPlatformLanguages
	 * @return void
	 */
	public function testGetPlatformLanguages()
	{
		$insert1 = (object) [
			'lang_code'    => 'ts-TEST',
			'title'        => 'Language de Test',
			'title_native' => 'Langue de Test',
			'sef'          => 'test-test',
			'image'        => 'test-image',
			'description'  => 'Description de Test',
			'metakey'      => 'metakey test',
			'metadesc'     => 'metadesc test',
			'published'    => 1,
		];
		$this->db->insertObject('jos_languages', $insert1);

		$insert2 = (object) [
			'lang_code'    => 'az-ERTY',
			'title'        => 'Language de Test',
			'title_native' => 'Langue de Test',
			'sef'          => 'AZERTY',
			'image'        => 'test-image',
			'description'  => 'Description de Test',
			'metakey'      => 'metakey test',
			'metadesc'     => 'metadesc test',
			'published'    => 0,
		];
		$this->db->insertObject('jos_languages', $insert2);

		$platformLanguages = $this->repository->getPlatformLanguages();
		$this->assertIsArray($platformLanguages, 'The getPlatformLanguages method should return an array');
		$this->assertNotEmpty($platformLanguages, 'The getPlatformLanguages method should return an array with at least one element');
		$this->assertContains('ts-TEST', $platformLanguages, 'The platform language code ts-TEST should be in the array');
		$this->assertNotContains('az-ERTY', $platformLanguages, 'The platform language code az-ERTY should not be in the array because it is not published');

		$query = $this->db->getQuery(true);
		$query->delete('jos_languages')
			->where(
				$this->db->quoteName('lang_code') . ' IN (' .
				$this->db->quote('ts-TEST') . ', ' .
				$this->db->quote('az-ERTY') .
				')'
			);
		$this->db->setQuery($query)->execute();
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getLanguage
	 * @return void
	 */
	public function testGetLanguage()
	{
		$insert = (object) [
			'lang_code'    => 'ts-LANG',
			'title'        => 'Language de Test',
			'title_native' => 'Langue de Test',
			'sef'          => 'test-lang',
			'image'        => 'test-image',
			'description'  => 'Description de Test',
			'metakey'      => 'metakey test',
			'metadesc'     => 'metadesc test',
			'published'    => 1,
		];
		$this->db->insertObject('jos_languages', $insert);

		$language = $this->repository->getLanguage('ts-LANG');;
		$this->assertIsObject($language, 'The getLanguage method should return an object');
		$this->assertObjectHasProperty('lang_code',$language, 'The getLanguage method should return an object with a lang_code property');
		$this->assertObjectHasProperty('title_native',$language, 'The getLanguage method should return an object with a title_native property');
		$this->assertEquals('ts-LANG', $language->lang_code, 'The getLanguage method should return an object with a lang_code property set to ts-LANG');
		$this->assertEquals('Langue de Test', $language->title_native, 'The getLanguage method should return an object with a title_native property set to Langue de Test');

		$query = $this->db->getQuery(true);
		$query->delete('jos_languages')
			->where(
				$this->db->quoteName('lang_code') . ' IN (' .
				$this->db->quote('ts-LANG') . ')'
			);
		$this->db->setQuery($query)->execute();
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getLanguages
	 * @return void
	 */
	public function testGetLanguages()
	{
		$insert1 = (object) [
			'lang_code'    => 'LANG1',
			'title'        => 'Language de Test',
			'title_native' => 'Langue de Test 1',
			'sef'          => 'test-lang-1',
			'image'        => 'test-image',
			'description'  => 'Description de Test',
			'metakey'      => 'metakey test',
			'metadesc'     => 'metadesc test',
			'published'    => 1,
		];
		$this->db->insertObject('jos_languages', $insert1);

		$insert2 = (object) [
			'lang_code'    => 'LANG2',
			'title'        => 'Language de Test',
			'title_native' => 'Langue de Test 2',
			'sef'          => 'test-lang-2',
			'image'        => 'test-image',
			'description'  => 'Description de Test',
			'metakey'      => 'metakey test',
			'metadesc'     => 'metadesc test',
			'published'    => 0,
		];
		$this->db->insertObject('jos_languages', $insert2);

		$languages = $this->repository->getLanguages();

		$this->assertIsArray($languages, 'getLanguages should return an array');
		$this->assertNotEmpty($languages, 'getLanguages should not return an empty array');

		$this->assertIsObject($languages[0], 'Each item should be an object (from loadObjectList)');
		$this->assertNotInstanceOf(LanguageEntity::class, $languages[0], 'Items should not be instances of LanguageEntity');

		foreach ($languages as $lang)
		{
			$this->assertObjectHasProperty('lang_code', $lang);
			$this->assertObjectHasProperty('title_native', $lang);
			$this->assertObjectHasProperty('published', $lang);
		}

		$langCodes = array_map(static fn($l) => $l->lang_code, $languages);

		$this->assertContains('LANG1', $langCodes, 'LANG1 should be in the results');
		$this->assertContains('LANG2', $langCodes, 'LANG2 should be in the results');

		$found1 = null;
		foreach ($languages as $l)
		{
			if ($l->lang_code === 'LANG1')
			{
				$found1 = $l;
				break;
			}
		}
		$this->assertNotNull($found1, 'Inserted language LANG1 should be found');
		$this->assertEquals('Langue de Test 1', $found1->title_native);
		$this->assertEquals(1, $found1->published);

		$query = $this->db->getQuery(true)
			->clear()
			->delete('jos_languages')
			->where($this->db->quoteName('lang_code') . ' IN (' . implode(',', array_map([$this->db, 'quote'], ['LANG1', 'LANG2'])) . ')');

		$this->db->setQuery($query)->execute();
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getOrphans
	 * @return void
	 */
	public function testGetOrphans(): void
	{
		$defaultLang = 'fr-FR';
		$langCode    = 'en-GB';
		$type        = 'override';

		$tagOrphan1 = rand(1, 1000) . '_TEST_ORPHAN_1_' . rand(1, 1000);
		$tagOrphan2 = rand(1, 1000) . '_TEST_ORPHAN_2_' . rand(1, 1000);
		$tagCommon  = rand(1, 1000) . '_TEST_COMMON_' . rand(1, 1000);

		$rowsToInsert = [
			(object) ['tag' => $tagOrphan1, 'lang_code' => $defaultLang, 'type' => $type],
			(object) ['tag' => $tagOrphan2, 'lang_code' => $defaultLang, 'type' => $type],
			(object) ['tag' => $tagCommon,  'lang_code' => $defaultLang, 'type' => $type],
			(object) ['tag' => $tagCommon,  'lang_code' => $langCode,   'type' => $type],
		];

		foreach ($rowsToInsert as $row)
		{
			$this->db->insertObject('#__emundus_setup_languages', $row);
		}

		try
		{
			$orphans = $this->repository->getOrphans($defaultLang, $langCode, $type);

			$this->assertIsArray($orphans, 'getOrphans should return an array');
			$this->assertNotEmpty($orphans, 'getOrphans should return at least one orphan');
			$this->assertIsObject($orphans[0], 'Each orphan should be an object (loadObjectList)');
			$this->assertObjectHasProperty('tag', $orphans[0], 'Orphan object should have a tag property');
			$this->assertObjectHasProperty('lang_code', $orphans[0], 'Orphan object should have a lang_code property');
			$this->assertObjectHasProperty('type', $orphans[0], 'Orphan object should have a type property');

			$tags = array_map(static fn($o) => $o->tag, $orphans);

			$this->assertContains($tagOrphan1, $tags, 'tagOrphan1 should be returned as orphan');
			$this->assertContains($tagOrphan2, $tags, 'tagOrphan2 should be returned as orphan');
			$this->assertNotContains($tagCommon, $tags, 'tagCommon should NOT be returned (exists in both languages)');
		}
		finally
		{
			$createdTags = [$tagOrphan1, $tagOrphan2, $tagCommon];

			$cleanup = $this->db->getQuery(true)
				->delete($this->db->qn('#__emundus_setup_languages'))
				->where($this->db->qn('tag') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $createdTags)) . ')');

			$this->db->setQuery($cleanup)->execute();
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getLangCode
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::setLangCode
	 * @return void
	 */
	public function testGetAndSetLangCode()
	{
		$this->repository->setLangCode('ts-TEST');
		$this->assertEquals('ts-TEST', $this->repository->getLangCode());
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getList
	 * @return void
	 */
	public function testGetList()
	{
		$this->repository->setLangCode('fr-FR');

		$tag = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);

		$language = new LanguageEntity(
			$tag,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);
		$this->repository->flush($language);

		$list = $this->repository->getList();
		$this->assertInstanceOf(\Tchooz\Entities\List\ListResult::class, $list, 'getList should return a ListResult');
		$this->assertIsArray($list->getItems(), 'getList items should be an array');
		$this->assertGreaterThan(0, $list->getTotalItems(), 'getList total items should be greater than 0');

		// Filtering on the tag should return exactly the row we created.
		$filtered = $this->repository->getList(['tag' => $tag]);
		$this->assertEquals(1, $filtered->getTotalItems(), 'getList filtered by tag should count exactly one row');
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::tagExists
	 * @return void
	 */
	public function testTagExists()
	{
		$this->repository->setLangCode('fr-FR');

		$tag = rand(1, 1000) . 'TEST_LANGUAGE_TAG' . rand(1, 1000);

		$this->assertFalse(
			$this->repository->tagExists($tag, 'fr-FR'),
			'tagExists should return false before the tag is inserted'
		);

		$language = new LanguageEntity(
			$tag,
			'fr-FR',
			'Test Language Tag',
			'Test Language Tag',
			'override'
		);
		$this->repository->flush($language);

		$this->assertTrue(
			$this->repository->tagExists($tag, 'fr-FR'),
			'tagExists should return true once the tag is inserted for the language'
		);

		$this->assertFalse(
			$this->repository->tagExists($tag, 'en-GB'),
			'tagExists should return false for a different language code'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::buildQuery
	 * @return void
	 */
	public function testBuildQuery()
	{
		$this->repository->setLangCode('fr-FR');

		$query = $this->repository->buildQuery();
		$this->assertInstanceOf(\Joomla\Database\QueryInterface::class, $query, 'buildQuery should return a QueryInterface');
		$this->assertStringContainsString('WHERE `lang_code`', $query->__toString(), 'buildQuery should filter on lang_code by default');

		$queryAll = $this->repository->buildQuery('all');
		$this->assertStringNotContainsString('WHERE `lang_code`', $queryAll->__toString(), 'buildQuery with "all" should not filter on lang_code');
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getReferencedForms
	 * @return void
	 */
	public function testGetReferencedForms()
	{
		$forms = $this->repository->getReferencedForms();
		$this->assertIsArray($forms, 'getReferencedForms should return an array');

		foreach ($forms as $form)
		{
			$this->assertIsObject($form, 'Each referenced form should be an object');
			$this->assertObjectHasProperty('id', $form, 'Each referenced form should have an id property');
			$this->assertObjectHasProperty('label', $form, 'Each referenced form should have a label property');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::getDefaultLanguage
	 * @return void
	 */
	public function testGetDefaultLanguage()
	{
		$language = $this->repository->getDefaultLanguage();
		$this->assertIsObject($language, 'getDefaultLanguage should return an object');
		$this->assertObjectHasProperty('lang_code', $language, 'getDefaultLanguage should return an object with a lang_code property');
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::updateFalangModule
	 * @return void
	 */
	public function testUpdateFalangModule()
	{
		$updated = $this->repository->updateFalangModule(0);
		$this->assertIsBool($updated, 'updateFalangModule should return a boolean');
		$this->assertTrue($updated, 'updateFalangModule should return true when the query executes');
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::findReferences
	 * @return void
	 */
	public function testFindReferences()
	{
		$key    = 'NON_EXISTENT_TRANSLATION_KEY_' . rand(1, 100000);
		$result = $this->repository->findReferences($key);

		$this->assertIsArray($result, 'findReferences should return an array');
		$this->assertArrayHasKey('reference_table', $result, 'findReferences result should have a reference_table key');
		$this->assertArrayHasKey('reference_id', $result, 'findReferences result should have a reference_id key');
		$this->assertArrayHasKey('reference_field', $result, 'findReferences result should have a reference_field key');
		$this->assertNull($result['reference_table'], 'reference_table should be null when the key is not referenced anywhere');
		$this->assertNull($result['reference_id'], 'reference_id should be null when the key is not referenced anywhere');
		$this->assertNull($result['reference_field'], 'reference_field should be null when the key is not referenced anywhere');

		$key    = 'SETUP_LETTERS_INTRO';
		$result = $this->repository->findReferences($key);

		$this->assertIsArray($result, 'findReferences should return an array');
		$this->assertNotNull($result, 'findReferences should return a not-null result');
	}

	/**
	 * @covers \Tchooz\Repositories\Language\LanguageRepository::updateContentLanguage
	 * @return void
	 */
	public function testUpdateContentLanguage()
	{
		$langCode = 'ts-UPD';

		$insert = (object) [
			'lang_code'    => $langCode,
			'title'        => 'Language de Test',
			'title_native' => 'Langue de Test',
			'sef'          => 'test-upd',
			'image'        => 'test-image',
			'description'  => 'Description de Test',
			'metakey'      => 'metakey test',
			'metadesc'     => 'metadesc test',
			'published'    => 0,
		];
		$this->db->insertObject('jos_languages', $insert);

		try
		{
			// Non-default branch: only toggles the published flag of the given language.
			$updated = $this->repository->updateContentLanguage($langCode, 1, 0);
			$this->assertTrue($updated, 'updateContentLanguage should return true when publishing a language');

			$query = $this->db->getQuery(true);
			$query->select($this->db->qn('published'))
				->from($this->db->qn('#__languages'))
				->where($this->db->qn('lang_code') . ' = ' . $this->db->quote($langCode));
			$this->db->setQuery($query);
			$this->assertEquals(1, (int) $this->db->loadResult(), 'The language should now be published');
		}
		finally
		{
			$cleanup = $this->db->getQuery(true)
				->delete('jos_languages')
				->where($this->db->quoteName('lang_code') . ' = ' . $this->db->quote($langCode));
			$this->db->setQuery($cleanup)->execute();
		}
	}
}
