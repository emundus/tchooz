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

use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\Exception\LogicException;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;

abstract class AbstractOperation implements OperationInterface
{
    public const OBSOLETE_BATCH = 'obsolete';
    public const NEW_BATCH = 'new';
    public const ALL_BATCH = 'all';

    protected $source;
    protected $target;
    protected $result;

    protected $messages;

    private array $domains;

    public function __construct(MessageCatalogueInterface $source, MessageCatalogueInterface $target)
    {
        if ($source->getLocale() !== $target->getLocale()) {
            throw new LogicException('Operated catalogues must belong to the same locale.');
        }

        $this->source = $source;
        $this->target = $target;
        $this->result = new MessageCatalogue($source->getLocale());
        $this->messages = [];
    }

    public function getDomains(): array
    {
        if (!isset($this->domains)) {
            $domains = [];
            foreach ([$this->source, $this->target] as $catalogue) {
                foreach ($catalogue->getDomains() as $domain) {
                    $domains[$domain] = $domain;

                    if ($catalogue->all($domainIcu = $domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX)) {
                        $domains[$domainIcu] = $domainIcu;
                    }
                }
            }

            $this->domains = array_values($domains);
        }

        return $this->domains;
    }

    public function getMessages(string $domain): array
    {
        if (!\in_array($domain, $this->getDomains())) {
            throw new InvalidArgumentException(sprintf('Invalid domain: "%s".', $domain));
        }

        if (!isset($this->messages[$domain][self::ALL_BATCH])) {
            $this->processDomain($domain);
        }

        return $this->messages[$domain][self::ALL_BATCH];
    }

    public function getNewMessages(string $domain): array
    {
        if (!\in_array($domain, $this->getDomains())) {
            throw new InvalidArgumentException(sprintf('Invalid domain: "%s".', $domain));
        }

        if (!isset($this->messages[$domain][self::NEW_BATCH])) {
            $this->processDomain($domain);
        }

        return $this->messages[$domain][self::NEW_BATCH];
    }

    public function getObsoleteMessages(string $domain): array
    {
        if (!\in_array($domain, $this->getDomains())) {
            throw new InvalidArgumentException(sprintf('Invalid domain: "%s".', $domain));
        }

        if (!isset($this->messages[$domain][self::OBSOLETE_BATCH])) {
            $this->processDomain($domain);
        }

        return $this->messages[$domain][self::OBSOLETE_BATCH];
    }

    public function getResult(): MessageCatalogueInterface
    {
        foreach ($this->getDomains() as $domain) {
            if (!isset($this->messages[$domain])) {
                $this->processDomain($domain);
            }
        }

        return $this->result;
    }

    public function moveMessagesToIntlDomainsIfPossible(string $batch = self::ALL_BATCH): void
    {
        if (!class_exists(\MessageFormatter::class)) {
            return;
        }

        foreach ($this->getDomains() as $domain) {
            $intlDomain = $domain.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;
            $messages = match ($batch) {
                self::OBSOLETE_BATCH => $this->getObsoleteMessages($domain),
                self::NEW_BATCH => $this->getNewMessages($domain),
                self::ALL_BATCH => $this->getMessages($domain),
                default => throw new \InvalidArgumentException(sprintf('$batch argument must be one of ["%s", "%s", "%s"].', self::ALL_BATCH, self::NEW_BATCH, self::OBSOLETE_BATCH)),
            };

            if (!$messages || (!$this->source->all($intlDomain) && $this->source->all($domain))) {
                continue;
            }

            $result = $this->getResult();
            $allIntlMessages = $result->all($intlDomain);
            $currentMessages = array_diff_key($messages, $result->all($domain));
            $result->replace($currentMessages, $domain);
            $result->replace($allIntlMessages + $messages, $intlDomain);
        }
    }

    abstract protected function processDomain(string $domain);
}
