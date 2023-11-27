# Unit Tests for Joomla 5.x

This folder contains the unit tests for the Joomla CMS. The tests are run with phpunit and the actual tests.

## How to create new tests
### Create a new test class
Create a new test class in the folder `tests/Unit/Component/Emundus/` with the name `ClassTypeTest.php` and the following content:

```php
<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Extension
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Unit\Component\Emundus\Model;

use Joomla\Tests\Unit\UnitTestCase;

class ClassTypeTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('class', $data, $dataName, 'EmundusTypeClass');
	}
}
```

### Create a new test method
Create a new test method in the class `ClassTypeTest.php` with the following content:

```php
public function testMethod()
{
    $this->assertTrue(true);
}
```

In all test methods you have access to the following variables:
```php
$this->model // The model of the tested class
$this->db // The database connection
$this->h_dataset // The dataset helper
```

## Run the tests
### Run all tests
To run all tests, execute the following command in the root directory of the Joomla CMS:
```bash
docker exec -it joomla5 libraries/emundus/phpunit.phar --configuration tests/phpunit.xml
```
