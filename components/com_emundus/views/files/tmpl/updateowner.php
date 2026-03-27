<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

Text::script('BACK');
Text::script('SEARCH');

$data = LayoutFactory::prepareVueData();
?>

<div id="em-update-owner"
     component="Application/UpdateOwner"
     shortlang="<?php echo $data['short_lang'] ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo uniqid(); ?>"></script>
