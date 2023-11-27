<?php

/**
 * @package    Joomla.UnitTest
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://www.phpunit.de/manual/current/en/installation.html
 */

namespace Joomla\Tests\Unit;

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

	public function __construct(?string $name = null, array $data = [], $dataName = '', $className = null)
	{
		parent::__construct($name, $data, $dataName);

		if(!empty($name)) {
			require_once JPATH_BASE . '/components/com_emundus/models/' . $name . '.php';
			$this->model = new $className();
		}

		$config = new \stdClass();
		$this->db     = $this->createStub(DatabaseInterface::class);
		$this->db->method('loadObject')->willReturn($config);

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
}
