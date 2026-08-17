<?php
namespace Worldline\Connect\Sdk\Communication;

use PHPUnit\Framework\TestCase;

/**
 * @group default_connection
 *
 */
class ConnectionResponseGetDispositionFilenameTest extends TestCase
{
    /**
     * @dataProvider dispositionFilenameProvider
     */
    public function testGetDispositionFilename(string $headerValue, ?string $expected)
    {
        $headers = array('Content-Disposition' => $headerValue);
        $this->assertEquals($expected, ConnectionResponse::getDispositionFilename($headers));
    }

    public function dispositionFilenameProvider(): array
    {
        return array(
            array('attachment; filename=testfile',   'testfile'),
            array('attachment; filename="testfile"', 'testfile'),
            array('attachment; filename="testfile',  '"testfile'),
            array('attachment; filename=testfile"',  'testfile"'),
            array("attachment; filename='testfile'", 'testfile'),
            array("attachment; filename='testfile",  "'testfile"),
            array("attachment; filename=testfile'",  "testfile'"),

            array('filename=testfile',   'testfile'),
            array('filename="testfile"', 'testfile'),
            array('filename="testfile',  '"testfile'),
            array('filename=testfile"',  'testfile"'),
            array("filename='testfile'", 'testfile'),
            array("filename='testfile",  "'testfile"),
            array("filename=testfile'",  "testfile'"),
            array('attachment; filename=testfile; x=y',   'testfile'),
            array('attachment; filename="testfile"; x=y', 'testfile'),
            array('attachment; filename="testfile; x=y',  '"testfile'),
            array('attachment; filename=testfile"; x=y',  'testfile"'),
            array("attachment; filename='testfile'; x=y", 'testfile'),
            array("attachment; filename='testfile; x=y",  "'testfile"),
            array("attachment; filename=testfile'; x=y",  "testfile'"),

            array('', null),
            array('filename="', '"'),
            array("filename='", "'")
        );
    }
}
