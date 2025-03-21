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


return array_replace_recursive(require __DIR__.'/en.php', [
    'weekdays' => ['𑄢𑄧𑄝𑄨𑄝𑄢𑄴', '𑄥𑄧𑄟𑄴𑄝𑄢𑄴', '𑄟𑄧𑄁𑄉𑄧𑄣𑄴𑄝𑄢𑄴', '𑄝𑄪𑄖𑄴𑄝𑄢𑄴', '𑄝𑄳𑄢𑄨𑄥𑄪𑄛𑄴𑄝𑄢𑄴', '𑄥𑄪𑄇𑄴𑄇𑄮𑄢𑄴𑄝𑄢𑄴', '𑄥𑄧𑄚𑄨𑄝𑄢𑄴'],
    'weekdays_short' => ['𑄢𑄧𑄝𑄨', '𑄥𑄧𑄟𑄴', '𑄟𑄧𑄁𑄉𑄧𑄣𑄴', '𑄝𑄪𑄖𑄴', '𑄝𑄳𑄢𑄨𑄥𑄪𑄛𑄴', '𑄥𑄪𑄇𑄴𑄇𑄮𑄢𑄴', '𑄥𑄧𑄚𑄨'],
    'weekdays_min' => ['𑄢𑄧𑄝𑄨', '𑄥𑄧𑄟𑄴', '𑄟𑄧𑄁𑄉𑄧𑄣𑄴', '𑄝𑄪𑄖𑄴', '𑄝𑄳𑄢𑄨𑄥𑄪𑄛𑄴', '𑄥𑄪𑄇𑄴𑄇𑄮𑄢𑄴', '𑄥𑄧𑄚𑄨'],
    'months' => ['𑄎𑄚𑄪𑄠𑄢𑄨', '𑄜𑄬𑄛𑄴𑄝𑄳𑄢𑄪𑄠𑄢𑄨', '𑄟𑄢𑄴𑄌𑄧', '𑄃𑄬𑄛𑄳𑄢𑄨𑄣𑄴', '𑄟𑄬', '𑄎𑄪𑄚𑄴', '𑄎𑄪𑄣𑄭', '𑄃𑄉𑄧𑄌𑄴𑄑𑄴', '𑄥𑄬𑄛𑄴𑄑𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄃𑄧𑄇𑄴𑄑𑄬𑄝𑄧𑄢𑄴', '𑄚𑄧𑄞𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄓𑄨𑄥𑄬𑄟𑄴𑄝𑄧𑄢𑄴'],
    'months_short' => ['𑄎𑄚𑄪', '𑄜𑄬𑄛𑄴', '𑄟𑄢𑄴𑄌𑄧', '𑄃𑄬𑄛𑄳𑄢𑄨𑄣𑄴', '𑄟𑄬', '𑄎𑄪𑄚𑄴', '𑄎𑄪𑄣𑄭', '𑄃𑄉𑄧𑄌𑄴𑄑𑄴', '𑄥𑄬𑄛𑄴𑄑𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄃𑄧𑄇𑄴𑄑𑄮𑄝𑄧𑄢𑄴', '𑄚𑄧𑄞𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄓𑄨𑄥𑄬𑄟𑄴𑄝𑄢𑄴'],
    'months_short_standalone' => ['𑄎𑄚𑄪𑄠𑄢𑄨', '𑄜𑄬𑄛𑄴𑄝𑄳𑄢𑄪𑄠𑄢𑄨', '𑄟𑄢𑄴𑄌𑄧', '𑄃𑄬𑄛𑄳𑄢𑄨𑄣𑄴', '𑄟𑄬', '𑄎𑄪𑄚𑄴', '𑄎𑄪𑄣𑄭', '𑄃𑄉𑄧𑄌𑄴𑄑𑄴', '𑄥𑄬𑄛𑄴𑄑𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄃𑄧𑄇𑄴𑄑𑄮𑄝𑄧𑄢𑄴', '𑄚𑄧𑄞𑄬𑄟𑄴𑄝𑄧𑄢𑄴', '𑄓𑄨𑄥𑄬𑄟𑄴𑄝𑄧𑄢𑄴'],
    'formats' => [
        'LT' => 'h:mm a',
        'LTS' => 'h:mm:ss a',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM, YYYY',
        'LLL' => 'D MMMM, YYYY h:mm a',
        'LLLL' => 'dddd, D MMMM, YYYY h:mm a',
    ],
]);
