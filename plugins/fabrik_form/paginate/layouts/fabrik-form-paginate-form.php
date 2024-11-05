<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.paginate
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
$start = Text::_('COM_FABRIK_START');
$next = Text::_('COM_FABRIK_NEXT');
$prev = Text::_('COM_FABRIK_PREV');
$end = Text::_('COM_FABRIK_END');
?>
<div class="form-actions">
    <nav>
        <ul class="pagination">
            <li data-paginate="first" class="pagination-start page-item <?php echo $displayData['first-active']?>">
                <a class="pagenav page-link" href="<?php echo $displayData['first'];?>" title="<?php echo $start; ?>">
					<?php echo $start; ?>
                </a>
            </li>
            <li data-paginate="prev" class="pagination-prev page-item <?php echo $displayData['first-active'] ?>">
                <a class="pagenav page-link" href="<?php echo $displayData['prev'];?>" title="<?php echo $prev; ?>">
					<?php echo $prev; ?>
                </a>
            </li>
            <li data-paginate="next" class="pagination-next page-item  <?php echo $displayData['last-active']?>">
                <a class="pagenav page-link" href="<?php echo $displayData['next'];?>" title="<?php echo $next; ?>">
					<?php echo $next; ?>
                </a>
            </li>
            <li data-paginate="last" class="pagination-end page-item <?php echo $displayData['last-active']?>">
                <a class="pagenav page-link" href="<?php echo $displayData['last'];?>" title="<?php echo $end; ?>">
					<?php echo $end; ?>
                </a>
            </li>
        </ul>
    </nav>
</div>
