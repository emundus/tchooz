<?php

namespace Unit\Component\Emundus\Class\Repositories\Emails;

use Joomla\CMS\Language\Text;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Emails\TagRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Emails
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Emails\TagRepository
 */
class TagRepositoriesTest extends UnitTestCase
{
	private TagRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new TagRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\Emails\TagRepository::getAllFabrikTags
	 * @return void
	 */
	public function testGetAllFabrikTags(): void
	{
		try {
			$tags = $this->repository->getAllFabrikTags('DESC', '', 25, 0, 'all',0, 0, 1);
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
	 * @covers \Tchooz\Repositories\Emails\TagRepository::getAllFabrikTags
	 * @return void
	 */
	public function testGetAllFabrikTagsIncludesCampaignMoreElements(): void
	{
		$campaignRepository = new CampaignRepository(false);

		if (empty($campaignRepository->getCampaignMoreFormId())) {
			$this->markTestSkipped('No campaign additional informations element is configured on this instance');
		}

		$tags = $this->repository->getAllFabrikTags('DESC', '', 0, 0, 'campaign', 0, 0, $this->dataset['coordinator']);

		$this->assertArrayHasKey('datas', $tags, 'Tags array have "datas" key');
		$this->assertNotEmpty($tags['datas'], 'The elements of the campaign additional informations form should be listed');

		$campaign_more_label = Text::_('COM_EMUNDUS_CAMPAIGN_MORE');
		foreach ($tags['datas'] as $tag) {
			$this->assertEquals($campaign_more_label, $tag->table_label, 'Only campaign elements should be listed when the campaign form type is asked');
			$this->assertNotEmpty($tag->id, 'A tag should carry the fabrik element id it is built from');
			$this->assertNotEmpty($tag->element_name, 'A tag should carry the name of its element');
			$this->assertNotEmpty($tag->plugin_label, 'A tag should carry the label of the plugin of its element');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Emails\TagRepository::getAllOtherTags
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