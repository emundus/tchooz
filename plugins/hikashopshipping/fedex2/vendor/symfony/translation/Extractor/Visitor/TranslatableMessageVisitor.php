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


namespace Symfony\Component\Translation\Extractor\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitor;

final class TranslatableMessageVisitor extends AbstractVisitor implements NodeVisitor
{
    public function beforeTraverse(array $nodes): ?Node
    {
        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Expr\New_) {
            return null;
        }

        if (!($className = $node->class) instanceof Node\Name) {
            return null;
        }

        if (!\in_array('TranslatableMessage', $className->getParts(), true)) {
            return null;
        }

        $firstNamedArgumentIndex = $this->nodeFirstNamedArgumentIndex($node);

        if (!$messages = $this->getStringArguments($node, 0 < $firstNamedArgumentIndex ? 0 : 'message')) {
            return null;
        }

        $domain = $this->getStringArguments($node, 2 < $firstNamedArgumentIndex ? 2 : 'domain')[0] ?? null;

        foreach ($messages as $message) {
            $this->addMessageToCatalogue($message, $domain, $node->getStartLine());
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?Node
    {
        return null;
    }
}
