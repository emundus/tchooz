<?php
defined('_JEXEC') || die;

if ($this->menuItemParams->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1>
            <?php if ($this->escape($this->menuItemParams->get('page_heading'))) : ?>
                <?php echo $this->escape($this->menuItemParams->get('page_heading')); ?>

            <?php elseif ($this->escape($this->menuItemParams->get('page_title'))) : ?>
                <?php echo $this->escape($this->menuItemParams->get('page_title')); ?>
            <?php else :
                $app = JFactory::getApplication();
                 echo $app->getMenu()->getActive()->title;
            endif; ?>
        </h1>
    </div>
<?php endif; ?>

<div class="dropfiles-page ">
    <?php echo $this->filesHtml; ?>

</div>    
