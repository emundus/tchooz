<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php


namespace Symfony\Component\Translation\Provider;

use Symfony\Component\Translation\Exception\IncompleteDsnException;

abstract class AbstractProviderFactory implements ProviderFactoryInterface
{
    public function supports(Dsn $dsn): bool
    {
        return \in_array($dsn->getScheme(), $this->getSupportedSchemes(), true);
    }

    abstract protected function getSupportedSchemes(): array;

    protected function getUser(Dsn $dsn): string
    {
        return $dsn->getUser() ?? throw new IncompleteDsnException('User is not set.', $dsn->getScheme().'://'.$dsn->getHost());
    }

    protected function getPassword(Dsn $dsn): string
    {
        return $dsn->getPassword() ?? throw new IncompleteDsnException('Password is not set.', $dsn->getOriginalDsn());
    }
}
