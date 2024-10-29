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

final class TransMethodVisitor extends AbstractVisitor implements NodeVisitor
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
        if (!$node instanceof Node\Expr\MethodCall && !$node instanceof Node\Expr\FuncCall) {
            return null;
        }

        if (!\is_string($node->name) && !$node->name instanceof Node\Identifier && !$node->name instanceof Node\Name) {
            return null;
        }

        $name = $node->name instanceof Node\Name ? $node->name->getLast() : (string) $node->name;

        if ('trans' === $name || 't' === $name) {
            $firstNamedArgumentIndex = $this->nodeFirstNamedArgumentIndex($node);

            if (!$messages = $this->getStringArguments($node, 0 < $firstNamedArgumentIndex ? 0 : 'message')) {
                return null;
            }

            $domain = $this->getStringArguments($node, 2 < $firstNamedArgumentIndex ? 2 : 'domain')[0] ?? null;

            foreach ($messages as $message) {
                $this->addMessageToCatalogue($message, $domain, $node->getStartLine());
            }
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?Node
    {
        return null;
    }
}
