<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2011-2021. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

?>
<form name="lang" method="post" action="<?php echo htmlspecialchars(Uri::current()); ?>">
    <?php if (!$params->get('advanced_dropdown',0)) : ?>
    	<select class="inputbox" onchange="document.location.replace(this.value);" >
            <?php foreach($list as $language):?>
                <?php if ($language->display) { ?>
                    <option value="<?php echo $language->link;?>" <?php echo !empty($language->active) ? 'selected="selected"' : ''?>><?php echo $language->title_native;?></option>
                <?php } else { ?>
                    <option disabled="disabled" style="opacity: 0.5" value="<?php echo $language->link;?>" <?php echo !empty($language->active) ? 'selected="selected"' : ''?>><?php echo $language->title_native;?></option>
                <?php } ?>
            <?php endforeach; ?>
        </select>
    <?php else : ?>
        <style type="text/css">
            /*https://stackoverflow.com/questions/3331353/transitions-on-the-css-display-property*/
            div.mod-languages.ad-<?php echo $module->id;?> ul {
                overflow: hidden;  /* Hide the element content, while height = 0 */
                height: 0;
                opacity: 0;
                -webkit-transition: height 0ms 400ms, opacity 400ms 0ms;
                -moz-transition: height 0ms 400ms, opacity 400ms 0ms;
                -o-transition: height 0ms 400ms, opacity 400ms 0ms;
                transition: height 0ms 400ms, opacity 400ms 0ms;
            }

            div.mod-languages.ad-<?php echo $module->id;?> ul.displayed {
                height: auto;
                opacity: 1;
                -webkit-transition: height 0ms 0ms, opacity 600ms 0ms;
                -moz-transition: height 0ms 0ms, opacity 600ms 0ms;
                -o-transition: height 0ms 0ms, opacity 600ms 0ms;
                transition: height 0ms 0ms, opacity 600ms 0ms;
            }
        </style>

        <script type="application/javascript">
            //can't use var or let here for the querySelector
            document.querySelector('div.ad-<?php echo $module->id;?>').addEventListener("mouseover", mOver, false);
            document.querySelector('div.ad-<?php echo $module->id;?>').addEventListener("mouseout", mOut, false);

            function mOver() {
                document.querySelector('div.ad-<?php echo $module->id;?>').querySelector('ul').classList.add("displayed")
            }

            function mOut() {
                document.querySelector('div.ad-<?php echo $module->id;?>').querySelector('ul').classList.remove("displayed")
            }
        </script>

        

        <!-- >>> [FREE] >>> -->
    <?php foreach($list as $language):?>
        <?php if ($language->active) :?>
        <a href="javascript:;" class="langChoose">
            <?php if ($params->get('image', 1)):?>
                <?php echo HTMLHelper::_('image', 'mod_falang/'.$language->image.'.gif', $language->title_native, array('title'=>$language->title_native), $relativePath);?>
            <?php else : ?>
                <?php echo $params->get('full_name', 1) ? $language->title_native : strtoupper($language->sef);?>
            <?php endif; ?>
            <span class="caret"></span>
        </a>
    <?php endif; ?>
    <?php endforeach;?>
        <ul class="<?php echo $params->get('inline', 1) ? 'lang-inline' : 'lang-block';?>">
            <?php foreach($list as $language):?>
                <?php if ($params->get('show_active', 0) || !$language->active):?>
                    <li class="<?php echo $language->active ? 'lang-active' : '';?>" dir="<?php echo $language->rtl ? 'rtl' : 'ltr' ?>">
                        <?php if ($language->display) { ?>
                            <a href="<?php echo $language->link;?>">
                                <?php if ($params->get('image', 1)):?>
                                    <?php echo HTMLHelper::_('image', 'mod_falang/'.$language->image.'.gif', $language->title_native, array('title'=>$language->title_native), $relativePath);?>
                                <?php endif; ?>
                                <?php if ($params->get('show_name', 1)):?>
                                    <?php echo $params->get('full_name', 1) ? $language->title_native : strtoupper($language->sef);?>
                                <?php endif; ?>
                                <?php if($language->active){?> <i class="fa fa-check lang_checked"></i> <?php } ?>
                            </a>
                        <?php } else { ?>
                            <?php if ($params->get('image', 1)):?>
                                <?php echo HTMLHelper::_('image', 'mod_falang/'.$language->image.'.gif', $language->title_native, array('title'=>$language->title_native,'style'=>'opacity:0.5'), $relativePath);?>
                            <?php else : ?>
                                <?php if ($params->get('show_name', 1)):?>
                                    <?php echo $params->get('full_name', 1) ? $language->title_native : strtoupper($language->sef);?>
                                <?php endif; ?>
                                <?php if($language->active){?> <i class="fa fa-check lang_checked"></i> <?php } ?>
                            <?php endif; ?>
                        <?php } ?>
                    </li>
                <?php endif;?>
            <?php endforeach;?>
        </ul>
        <!-- <<< [FREE] <<< -->
    <?php endif; ?>
</form>

