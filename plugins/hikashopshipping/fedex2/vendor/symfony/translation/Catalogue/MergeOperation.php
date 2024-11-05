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


namespace Symfony\Component\Translation\Catalogue;

use Symfony\Component\Translation\MessageCatalogueInterface;

class MergeOperation extends AbstractOperation
{
    protected function processDomain(string $domain)
    {
        $this->messages[$domain] = [
            'all' => [],
            'new' => [],
            'obsolete' => [],
        ];
        $intlDomain = $domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;

        foreach ($this->target->getCatalogueMetadata('', $domain) ?? [] as $key => $value) {
            if (null === $this->result->getCatalogueMetadata($key, $domain)) {
                $this->result->setCatalogueMetadata($key, $value, $domain);
            }
        }

        foreach ($this->target->getCatalogueMetadata('', $intlDomain) ?? [] as $key => $value) {
            if (null === $this->result->getCatalogueMetadata($key, $intlDomain)) {
                $this->result->setCatalogueMetadata($key, $value, $intlDomain);
            }
        }

        foreach ($this->source->all($domain) as $id => $message) {
            $this->messages[$domain]['all'][$id] = $message;
            $d = $this->source->defines($id, $intlDomain) ? $intlDomain : $domain;
            $this->result->add([$id => $message], $d);
            if (null !== $keyMetadata = $this->source->getMetadata($id, $d)) {
                $this->result->setMetadata($id, $keyMetadata, $d);
            }
        }

        foreach ($this->target->all($domain) as $id => $message) {
            if (!$this->source->has($id, $domain)) {
                $this->messages[$domain]['all'][$id] = $message;
                $this->messages[$domain]['new'][$id] = $message;
                $d = $this->target->defines($id, $intlDomain) ? $intlDomain : $domain;
                $this->result->add([$id => $message], $d);
                if (null !== $keyMetadata = $this->target->getMetadata($id, $d)) {
                    $this->result->setMetadata($id, $keyMetadata, $d);
                }
            }
        }
    }
}
