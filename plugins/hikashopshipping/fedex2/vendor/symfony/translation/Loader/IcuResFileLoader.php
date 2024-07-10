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


namespace Symfony\Component\Translation\Loader;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\MessageCatalogue;

class IcuResFileLoader implements LoaderInterface
{
    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        if (!stream_is_local($resource)) {
            throw new InvalidResourceException(sprintf('This is not a local file "%s".', $resource));
        }

        if (!is_dir($resource)) {
            throw new NotFoundResourceException(sprintf('File "%s" not found.', $resource));
        }

        try {
            $rb = new \ResourceBundle($locale, $resource);
        } catch (\Exception) {
            $rb = null;
        }

        if (!$rb) {
            throw new InvalidResourceException(sprintf('Cannot load resource "%s".', $resource));
        } elseif (intl_is_failure($rb->getErrorCode())) {
            throw new InvalidResourceException($rb->getErrorMessage(), $rb->getErrorCode());
        }

        $messages = $this->flatten($rb);
        $catalogue = new MessageCatalogue($locale);
        $catalogue->add($messages, $domain);

        if (class_exists(DirectoryResource::class)) {
            $catalogue->addResource(new DirectoryResource($resource));
        }

        return $catalogue;
    }

    protected function flatten(\ResourceBundle $rb, array &$messages = [], ?string $path = null): array
    {
        foreach ($rb as $key => $value) {
            $nodePath = $path ? $path.'.'.$key : $key;
            if ($value instanceof \ResourceBundle) {
                $this->flatten($value, $messages, $nodePath);
            } else {
                $messages[$nodePath] = $value;
            }
        }

        return $messages;
    }
}
