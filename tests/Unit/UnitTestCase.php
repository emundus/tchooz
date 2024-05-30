<?php

/**
 * @package        Joomla.UnitTest
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * @link           http://www.phpunit.de/manual/current/en/installation.html
 */

namespace Joomla\Tests\Unit;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\TestCase;
use Unit\Helper\Dataset;

//require_once __DIR__ . '/Helper/Dataset.php';

/**
 * Base Unit Test case for common behaviour across unit tests
 *
 * @since   4.0.0
 */
abstract class UnitTestCase extends TestCase
{
	/**
	 * @var    DatabaseInterface
	 * @since  4.0.0
	 */
	protected $db;

	/**
	 * @var    Dataset
	 * @since  4.0.0
	 */
	protected $h_dataset;

	/**
	 * @since  4.0.0
	 */
	protected $model;

	/**
	 * @var    array
	 * @since  4.0.0
	 */
	protected $dataset = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '', $className = null)
	{
		parent::__construct($name, $data, $dataName);

		if (!empty($name))
		{
			require_once JPATH_BASE . '/components/com_emundus/models/' . $name . '.php';
			$this->model = new $className();
		}

		$config   = new \stdClass();
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		require_once __DIR__ . '/Helper/Dataset.php';
		$this->h_dataset = new Dataset();
	}

	/**
	 * Returns a database query instance.
	 *
	 * @param   DatabaseInterface  $db  The database
	 *
	 * @return  QueryInterface
	 *
	 * @since   4.2.0
	 */
	protected function getQueryStub(DatabaseInterface $db): QueryInterface
	{
		return new class ($db) extends DatabaseQuery {
			public function groupConcat($expression, $separator = ',')
			{
			}

			public function processLimit($query, $limit, $offset = 0)
			{
			}
		};
	}

	protected function setUp(): void
	{
		$this->dataset['applicant']   = $this->h_dataset->createSampleUser(9, 'applicant_' . rand(0, 1000) . '@emundus.fr');
		$this->dataset['coordinator'] = $this->h_dataset->createSampleUser(2, 'coordinator_' . rand(0, 1000) . '@emundus.fr','test1234',[2,7]);
		$this->dataset['program']     = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $this->dataset['coordinator']);
		$this->dataset['campaign']    = $this->h_dataset->createSampleCampaign($this->dataset['program'], $this->dataset['coordinator']);
		$this->dataset['fnum']        = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
	}

	protected function tearDown(): void
	{
		$this->h_dataset->deleteSampleUser($this->dataset['applicant']);
		$this->h_dataset->deleteSampleUser($this->dataset['coordinator']);
		$this->h_dataset->deleteSampleProgram($this->dataset['program']['programme_id']);
		$this->h_dataset->deleteSampleCampaign($this->dataset['campaign']);
		$this->h_dataset->deleteSampleFile($this->dataset['fnum']);
	}
}
