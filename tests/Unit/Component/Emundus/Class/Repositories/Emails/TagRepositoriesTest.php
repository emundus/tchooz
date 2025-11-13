<?php

namespace Unit\Component\Emundus\Class\Repositories\Emails;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\Emails\TagRepository;

class TagRepositoriesTest extends UnitTestCase
{
	private TagRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new TagRepository();
	}

	/**
	 * @covers TagRepository::getAllFabrikTags
	 * @return void
	 */
	public function testGetAllFabrikTags(): void
	{
		try {
			$tags = $this->repository->getAllFabrikTags('DESC', '', 25, 0, 'all',0, 0, $this->dataset['coordinator']);
			$this->assertIsArray($tags);
			$this->assertNotEmpty($tags);
			$this->assertArrayHasKey('datas', $tags, 'Tags array have "datas" key');
			$this->assertArrayHasKey('count', $tags);
			$this->assertGreaterThan(0, $tags['datas'], 'Tags datas returned');
			$this->assertGreaterThan(0, $tags['count']);
		} catch (\Exception $e) {
			$this->fail('Exception thrown: ' . $e->getMessage());
		}
	}

	/**
	 * @covers TagRepository::getAllOtherTags
	 * @return void
	 */
	public function testGetAllOtherTags(): void
	{
		try {
			$tags = $this->repository->getAllOtherTags('DESC', '', 25, 0, 'all',0, 0, $this->dataset['coordinator']);
			$this->assertIsArray($tags);
			$this->assertNotEmpty($tags);
			$this->assertArrayHasKey('datas', $tags, 'Tags array have "datas" key');
			$this->assertArrayHasKey('count', $tags);
			$this->assertGreaterThan(0, $tags['datas'], 'Tags datas returned');
			$this->assertGreaterThan(0, $tags['count']);
		} catch (\Exception $e) {
			$this->fail('Exception thrown: ' . $e->getMessage());
		}
	}
}