<?php

namespace Unit\Component\Emundus\Class\Factories;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Factories\ImportFactory;

/**
 * @package     Unit\Component\Emundus\Class\Factories
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\ImportFactory
 */
class ImportFactoryTest extends UnitTestCase
{
	private string $csvFilename;

	public function setUp(): void
	{
		parent::setUp();
		$this->csvFilename = JPATH_ROOT . '/tmp/import_factory_test_' . uniqid() . '.csv';
	}

	public function tearDown(): void
	{
		if (is_file($this->csvFilename))
		{
			unlink($this->csvFilename);
		}
		parent::tearDown();
	}

	/**
	 * @covers \Tchooz\Factories\ImportFactory::setColumns
	 * @covers \Tchooz\Factories\ImportFactory::getRowsToImport
	 * @return void
	 */
	public function testUnknownColumnDoesNotShiftNextColumns(): void
	{
		file_put_contents($this->csvFilename, implode("\n", [
			'Email [email];Commentaire libre;Prénom [firstname];Nom [lastname];Note [jos_emundus_evaluations___note]',
			'john@doe.fr;à ignorer;John;Doe;12',
		]));

		$importFactory = new ImportFactory(null);
		$importFactory->setCsvFilename($this->csvFilename);
		$importFactory->setCsvDelimiter();
		$importFactory->setColumns();

		$rows = $importFactory->getRowsToImport();

		$this->assertCount(1, $rows);
		$this->assertSame('john@doe.fr', $rows[0]['email']);
		$this->assertSame('John', $rows[0]['firstname'], 'Columns after an unknown one keep their own value');
		$this->assertSame('Doe', $rows[0]['lastname']);
		$this->assertSame('12', $rows[0]['jos_emundus_evaluations']['note']);
		$this->assertArrayNotHasKey('Commentaire libre', $rows[0]);
	}

	/**
	 * @covers \Tchooz\Factories\ImportFactory::setColumns
	 * @return void
	 */
	public function testDuplicatedColumnKeepsFirstOccurrence(): void
	{
		file_put_contents($this->csvFilename, implode("\n", [
			'Email [email];Email bis [email];Prénom [firstname]',
			'first@doe.fr;second@doe.fr;John',
		]));

		$importFactory = new ImportFactory(null);
		$importFactory->setCsvFilename($this->csvFilename);
		$importFactory->setCsvDelimiter();
		$importFactory->setColumns();

		$rows = $importFactory->getRowsToImport();

		$this->assertSame('first@doe.fr', $rows[0]['email']);
		$this->assertSame('John', $rows[0]['firstname']);
	}
}
