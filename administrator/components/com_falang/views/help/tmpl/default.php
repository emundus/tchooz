<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

if (!empty($this->sidebar)): ?>
<div id="j-sidebar-container" class="span2">
    <?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
    <?php else : ?>
    <div id="j-main-container">
        <?php endif; ?>
        <div id="jfhelp">
            <div class="row-fluid">
                <div class="span10">
                    <div id="content" class="col width-70">
                        <?php include($this->get('helppath')); ?>
                    </div>
                    <div id="adminJFSidebar">
                        <div id="infosidebar">
                            <?php echo $this->loadTemplate('sidemenu'); ?>
                            <?php echo $this->loadTemplate('credits'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="clr"></div>
    </div>
</div>
