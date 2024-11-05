<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php declare(strict_types=1);

namespace FedexRest\Tests\Authorization;

use FedexRest\Authorization\Authorize;
use FedexRest\Exceptions\MissingAuthCredentialsException;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{

    public function testAuth()
    {
        $auth = (new Authorize)
            ->setClientId('l7749d031872cf4b55a7889376f360d045')
            ->setClientSecret('bd59d91084e8482895d4ae2fb4fb79a3');

        $this->assertObjectHasProperty('access_token', $auth->authorize());
    }

    public function testAuthRaw()
    {
        $auth = (new Authorize)
            ->asRaw()
            ->setClientId('l7749d031872cf4b55a7889376f360d045')
            ->setClientSecret('bd59d91084e8482895d4ae2fb4fb79a3');

        $this->assertObjectHasProperty('headers', $auth->authorize());
    }

    public function testMissingCredentials()
    {
        try {
            (new Authorize)->authorize();
        } catch (MissingAuthCredentialsException $e) {
            $this->assertEquals('Please provide auth credentials', $e->getMessage());
        }
    }
}
