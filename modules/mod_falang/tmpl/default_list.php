<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;


?>



<ul class="<?php echo $params->get('inline', 1) ? 'lang-inline' : 'lang-block';?>">
    <?php foreach($list as $language):?>

        
        <?php if ($params->get('show_active', 0) || !$language->active):?>
            <li class="<?php echo $language->active ? 'lang-active' : '';?>" dir="<?php echo  $language->rtl ? 'rtl' : 'ltr' ?>">
                <?php if ($language->display) { ?>
                    <a href="<?php echo $language->link;?>">
                        <?php if ($params->get('image', 1)):?>
                            <?php echo HTMLHelper::_('image', $imagesPath.$language->image.'.'.$imagesType, $language->title_native, array('title'=>$language->title_native), $relativePath);?>
                        <?php endif; ?>
                        <?php if ($params->get('show_name', 1)):?>
                            <?php echo $params->get('full_name', 1) ? $language->title_native : strtoupper($language->sef);?>
                        <?php endif; ?>
                    </a>
                <?php } else { ?>
                    <?php if ($params->get('image', 1)):?>
                        <?php echo HTMLHelper::_('image', $imagesPath.$language->image.'.'.$imagesType, $language->title_native, array('title'=>$language->title_native,'style'=>'opacity:0.5'), $relativePath);?>
                    <?php endif; ?>
                    <?php if ($params->get('show_name', 1)):?>
                        <?php echo $params->get('full_name', 1) ? $language->title_native : strtoupper($language->sef);?>
                    <?php endif; ?>
                <?php } ?>
            </li>
        <?php endif;?>
        
        
    <?php endforeach;?>
</ul>
