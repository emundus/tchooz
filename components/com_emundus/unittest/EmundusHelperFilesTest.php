<?php

use PHPUnit\Framework\TestCase;
ini_set( 'display_errors', false );
error_reporting(E_ALL);
define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);
define('JPATH_BASE', dirname(__DIR__) . '/../../');

include_once(JPATH_BASE . 'includes/defines.php');
include_once(JPATH_BASE . 'includes/framework.php');
include_once(JPATH_SITE . '/components/com_emundus/helpers/files.php');

jimport('joomla.user.helper');
jimport('joomla.application.application');
jimport('joomla.plugin.helper');

// set global config --> initialize Joomla Application with default param 'site'
JFactory::getApplication('site');

// set false ini_get('session.use_cookies') and set false headers_sent
!ini_get('session.use_cookies') && !headers_sent($file, $line);

// activate session
session_start();


class EmundusHelperFilesTest extends TestCase {

	private $h_files;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->h_files = new EmundusHelperFiles;
	}

	public function testFoo()
	{
		$foo = true;
		$this->assertSame(true, $foo);
	}

	/**
	 * @test
	 * @covers EmundusHelperFiles::createFnum
	 */
	public function testCreateFnum() {
		$this->assertSame('', $this->h_files->createFnum(0, 0, false), 'Create fnum with wrong campaign_id and user_id returns empty');
		$this->assertSame('', $this->h_files->createFnum(0, 95, false), 'Create fnum with wrong campaign_id returns empty');
		$this->assertSame('', $this->h_files->createFnum(1, 0, false), 'Create fnum with wrong user_id returns empty');
		$this->assertNotEmpty($this->h_files->createFnum(1, 95, false), 'Create fnum with correct campaign_id and user_id returns not empty');
		$this->assertNotEmpty($this->h_files->createFnum(1, 95), 'Create fnum with correct campaign_id and user_id and redirect to true returns not empty');
	}
}