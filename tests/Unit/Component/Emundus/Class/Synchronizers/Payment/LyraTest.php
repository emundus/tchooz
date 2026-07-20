<?php
/**
 * @package     Unit\Component\Emundus\Class\Synchronizers\Payment
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Synchronizers\Payment;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Enums\Payment\PaymentGatewayEnum;
use Tchooz\Synchronizers\Payment\Lyra;
use Tchooz\Synchronizers\Payment\Payzen;
use Tchooz\Synchronizers\Payment\PaymentSynchronizerInterface;

/**
 * @package     Unit\Component\Emundus\Class\Synchronizers\Payment
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Synchronizers\Payment\Lyra
 * @covers      \Tchooz\Synchronizers\Payment\Payzen
 */
class LyraTest extends UnitTestCase
{
	private const HMAC_KEY = 'testkey1234';

	/**
	 * Precomputed for getSignedFields() with HMAC_KEY:
	 * base64(hmac_sha256("1000+TEST+12345678+testkey1234", "testkey1234"))
	 */
	private const SHA256_VECTOR = 'WbQUFtfWb599YtD+7pkha/AlU5697/KHjMAOCh7ifSI=';

	private Payzen $synchronizer;

	protected function setUp(): void
	{
		parent::setUp();

		$this->synchronizer = new Payzen();
	}

	private function getSignedFields(): array
	{
		return [
			'vads_amount'   => '1000',
			'vads_ctx_mode' => 'TEST',
			'vads_site_id'  => '12345678',
		];
	}

	/**
	 * Injects a payment configuration into the private Lyra::$config property,
	 * with the client secret encrypted the same way the integration handler stores it.
	 */
	private function injectConfig(): void
	{
		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
		}

		$property = new \ReflectionProperty(Lyra::class, 'config');
		$property->setValue($this->synchronizer, [
			'authentication' => [
				'client_id'     => '12345678',
				'client_secret' => \EmundusHelperFabrik::encryptDatas(self::HMAC_KEY),
			],
			'endpoint'       => 'https://secure.payzen.eu/vads-payment/',
			'mode'           => 'TEST',
			'return_url'     => '',
		]);
	}

	/**
	 * @covers \Tchooz\Synchronizers\Payment\Lyra::getSHA256Signature
	 * @return void
	 */
	public function testGetSha256SignatureMatchesKnownVector(): void
	{
		$signature = self::callPrivateMethod($this->synchronizer, 'getSHA256Signature', [$this->getSignedFields(), self::HMAC_KEY]);

		$this->assertSame(self::SHA256_VECTOR, $signature, 'SHA256 signature must match the precomputed Lyra vector');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Payment\Lyra::getSHA256Signature
	 * @return void
	 */
	public function testGetSha256SignatureIgnoresNonVadsFields(): void
	{
		$fields              = $this->getSignedFields();
		$fields['signature'] = 'should-be-ignored';
		$fields['foo']       = 'bar';

		$signature = self::callPrivateMethod($this->synchronizer, 'getSHA256Signature', [$fields, self::HMAC_KEY]);

		$this->assertSame(self::SHA256_VECTOR, $signature, 'Fields without the vads_ prefix must not alter the signature');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Payment\Lyra::verifySignature
	 * @return void
	 */
	public function testVerifySignatureReturnsTrueForValidSignature(): void
	{
		$this->injectConfig();

		$fields              = $this->getSignedFields();
		$fields['signature'] = self::SHA256_VECTOR;

		$this->assertTrue($this->synchronizer->verifySignature($fields), 'A payload signed with the configured key must be accepted');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Payment\Lyra::verifySignature
	 * @return void
	 */
	public function testVerifySignatureReturnsFalseForTamperedFields(): void
	{
		$this->injectConfig();

		$fields                = $this->getSignedFields();
		$fields['signature']   = self::SHA256_VECTOR;
		$fields['vads_amount'] = '999999';

		$this->assertFalse($this->synchronizer->verifySignature($fields), 'A payload altered after signing must be rejected');
	}


	/**
	 * @covers \Tchooz\Synchronizers\Payment\Lyra::verifyReference
	 * @return void
	 */
	public function testVerifyReferenceAcceptsShortAlphanumericReference(): void
	{
		$this->assertTrue($this->synchronizer->verifyReference('ABC123'), 'A 6-character alphanumeric reference must be accepted');
	}

	/**
	 * @covers \Tchooz\Synchronizers\Payment\Lyra::verifyReference
	 * @return void
	 */
	public function testVerifyReferenceRejectsInvalidReferences(): void
	{
		$this->assertFalse($this->synchronizer->verifyReference('ABCD1234'), 'A reference longer than 6 characters must be rejected');
		$this->assertFalse($this->synchronizer->verifyReference('AB-12'), 'A reference with non-alphanumeric characters must be rejected');
		$this->assertFalse($this->synchronizer->verifyReference(''), 'An empty reference must be rejected');
	}

	/**
	 * @covers \Tchooz\Enums\Payment\PaymentGatewayEnum::getSynchronizer
	 * @return void
	 */
	public function testPayzenGatewayResolvesToLyraSynchronizer(): void
	{
		$synchronizer = PaymentGatewayEnum::PAYZEN->getSynchronizer();

		$this->assertInstanceOf(Payzen::class, $synchronizer, 'PAYZEN must resolve to the Payzen synchronizer');
		$this->assertInstanceOf(Lyra::class, $synchronizer, 'The Payzen synchronizer must extend the shared Lyra protocol class');
		$this->assertInstanceOf(PaymentSynchronizerInterface::class, $synchronizer, 'The Payzen synchronizer must honor the synchronizer contract');
	}
}