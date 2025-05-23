<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_newsfeeds
 *
 * @copyright   (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Newsfeeds\Site\Helper\RouteHelper;

/** @var \Joomla\Component\Newsfeeds\Administrator\View\Newsfeeds\HtmlView $this */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('core')
    ->useScript('modal-content-select');

$app = Factory::getApplication();

// @todo: Use of Function is deprecated and should be removed in 6.0. It stays only for backward compatibility.
$function  = $app->getInput()->getCmd('function', 'jSelectNewsfeed');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$multilang = Multilanguage::isEnabled();
?>
<div class="container-popup">

    <form action="<?php echo Route::_('index.php?option=com_newsfeeds&view=newsfeeds&layout=modal&tmpl=component&function=' . $function); ?>" method="post" name="adminForm" id="adminForm">

        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
            </div>
        <?php else : ?>
            <table class="table table-sm">
                <caption class="visually-hidden">
                    <?php echo Text::_('COM_NEWSFEEDS_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                </caption>
                <thead>
                    <tr>
                        <th scope="col" class="w-1 text-center">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="title">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.name', $listDirn, $listOrder); ?>
                        </th>
                        <th scope="col" class="w-15 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
                        </th>
                        <?php if ($multilang) : ?>
                            <th scope="col" class="w-15 d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'language_title', $listDirn, $listOrder); ?>
                            </th>
                        <?php endif; ?>
                        <th scope="col" class="w-1 d-none d-md-table-cell">
                            <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $iconStates = [
                    -2 => 'icon-trash',
                    0  => 'icon-unpublish',
                    1  => 'icon-publish',
                    2  => 'icon-archive',
                ];
                ?>
                <?php foreach ($this->items as $i => $item) : ?>
                    <?php
                    $lang = '';
                    if ($item->language && $multilang) {
                        $tag = \strlen($item->language);
                        if ($tag == 5) {
                            $lang = substr($item->language, 0, 2);
                        } elseif ($tag == 6) {
                            $lang = substr($item->language, 0, 3);
                        }
                    }

                    $link     = RouteHelper::getNewsfeedRoute($item->id, $item->catid, $item->language);
                    $itemHtml = '<a href="' . $this->escape($link) . '"' . ($lang ? ' hreflang="' . $lang . '"' : '') . '>' . $item->name . '</a>';
                    ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td class="text-center">
                            <span class="tbody-icon">
                                <span class="<?php echo $iconStates[$this->escape($item->published)]; ?>" aria-hidden="true"></span>
                            </span>
                        </td>
                        <th scope="row">
                            <?php $attribs = 'data-content-select data-content-type="com_newsfeeds.newsfeed"'
                                . ' data-id="' . $item->id . '"'
                                . ' data-title="' . $this->escape($item->name) . '"'
                                . ' data-cat-id="' . $this->escape($item->catid) . '"'
                                . ' data-uri="' . $this->escape($link) . '"'
                                . ' data-language="' . $this->escape($lang) . '"'
                                . ' data-html="' . $this->escape($itemHtml) . '"';
                            ?>
                            <a href="javascript:void(0)" <?php echo $attribs; ?>
                               onclick="if (window.parent && !window.parent.JoomlaExpectingPostMessage) window.parent.<?php echo $this->escape($function); ?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->name)); ?>', '<?php echo $this->escape($item->catid); ?>', null, '<?php echo $this->escape($link); ?>', '<?php echo $this->escape($lang); ?>', null);">
                            <?php echo $this->escape($item->name); ?></a>
                            <div class="small">
                                <?php echo Text::_('JCATEGORY') . ': ' . $this->escape($item->category_title); ?>
                            </div>
                        </th>
                        <td class="small d-none d-md-table-cell">
                            <?php echo $this->escape($item->access_level); ?>
                        </td>
                        <?php if ($multilang) : ?>
                            <td class="small d-none d-md-table-cell">
                                <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                            </td>
                        <?php endif; ?>
                        <td class="d-none d-md-table-cell">
                            <?php echo (int) $item->id; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php // load the pagination. ?>
            <?php echo $this->pagination->getListFooter(); ?>

        <?php endif; ?>

        <input type="hidden" name="task" value="">
        <input type="hidden" name="boxchecked" value="0">
        <input type="hidden" name="forcedLanguage" value="<?php echo $app->getInput()->get('forcedLanguage', '', 'CMD'); ?>">
        <?php echo HTMLHelper::_('form.token'); ?>

    </form>
</div>
