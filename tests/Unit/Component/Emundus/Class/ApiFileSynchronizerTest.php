<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class;

use EmundusModelSync;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_BASE . '/components/com_emundus/classes/api/FileSynchronizer.php';

class ApiFileSynchronizerTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('sync', $data, $dataName, 'EmundusModelSync');
	}

	/**
	 * @covers EmundusModelSync::saveConfig
	 *
	 * @since version 1.0.0
	 */
	public function testSaveConfig()
	{
		$config_sample = '{"tree":[{"id":1,"level":1,"type":"[CAMPAIGN_LABEL]","parent":0,"childrens":[{"id":"1_1","level":2,"type":"[CAMPAIGN_YEAR]","parent":1,"childrens":[{"id":"1_1_1","level":3,"type":"[APPLICANT_NAME]","parent":"1_1","childrens":[]}]}]}],"name":"[FNUM]_[DOCUMENT_TYPE]_[APPLICANT_NAME]"}';

		// TEST 1 - GED configuration
		$this->assertSame(true, $this->model->saveConfig($config_sample, 'ged'));
	}

	/**
	 * @covers EmundusModelSync::getConfig
	 *
	 * @since version 1.0.0
	 */
	public function testGetConfig()
	{
		$config_sample = '{"tree":[{"id":1,"level":1,"type":"[CAMPAIGN_LABEL]","parent":0,"childrens":[{"id":"1_1","level":2,"type":"[CAMPAIGN_YEAR]","parent":1,"childrens":[{"id":"1_1_1","level":3,"type":"[APPLICANT_NAME]","parent":"1_1","childrens":[]}]}]}],"name":"[FNUM]_[DOCUMENT_TYPE]_[APPLICANT_NAME]"}';

		// TEST 1 - GED configuration
		$this->model->saveConfig($config_sample, 'ged');
		$this->assertNotEmpty($this->model->getConfig('ged'));

		// TEST 2 - FAILED WAITING - Dropbox is not configured for moment
		$this->assertEmpty($this->model->getConfig('dropbox'));
	}

	/**
	 * @covers EmundusModelSync::getDocuments
	 *
	 * @since version 1.0.0
	 */
	public function testGetDocuments()
	{
		// TEST 1 - Get attachments type
		$this->assertIsArray($this->model->getDocuments());
	}

	/**
	 * @covers EmundusModelSync::updateDocumentSync
	 *
	 * @since version 1.0.0
	 */
	public function testUpdateDocumentSync()
	{
		// TEST 1 - Add a document to ged sync
		$this->assertSame(true, $this->model->updateDocumentSync(1, 1));

		// TEST 2 - Update a document that not exist, return true
		$this->assertSame(true, $this->model->updateDocumentSync(999, 1));

		// TEST 3 - Remove the sync from a document
		$this->assertSame(true, $this->model->updateDocumentSync(1, 0));
	}

	/**
	 * @covers EmundusModelSync::updateDocumentSyncMethod
	 *
	 * @since version 1.0.0
	 */
	public function testUpdateDocumentSyncMethod()
	{
		// TEST 1 - Add a document to ged sync
		$this->assertSame(true, $this->model->updateDocumentSyncMethod(1, 'read'));

		// TEST 2 - Update a document that not exist, return true
		$this->assertSame(true, $this->model->updateDocumentSyncMethod(999, 'read'));

		// TEST 3 - Remove the sync from a document
		$this->assertSame(true, $this->model->updateDocumentSyncMethod(1, 'write'));

		// TEST 3 - Remove the method from a document
		$this->assertSame(true, $this->model->updateDocumentSyncMethod(1, null));
	}
}