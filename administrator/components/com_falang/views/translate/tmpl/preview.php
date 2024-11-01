<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Uri\Uri;


// URI::base() returns admin path so go up one level
$live_site = URI::base()."..";
$base = '<base href="'.$live_site.'/index.html" />';

Factory::getApplication()->getDocument()->addCustomTag($base);

?>
	<script>

	var form = window.parent.document.adminForm	;
	var title = form.refField_title.value;
	var title_orig = form.origText_title.value;

	var alltext="";
	var alltext_orig = window.parent.document.getElementById("original_value_introtext").innerHTML;

	if (window.parent.getRefField){
		alltext = window.parent.getRefField("introtext");
		if (window.parent.getRefField("fulltext")) {
			alltext += window.parent.getRefField("fulltext");
		}
		else if (form.refField_fulltext) {
			alltext += form.refField_fulltext.value;
		}
	}
	else {
        alltext = window.parent.document.getElementById("refField_introtext").innerHTML;
        alltext += window.parent.document.getElementById("refField_fulltext").innerHTML;
	}
    console.log(alltext);

	</script>
<table align="center" width="100%" cellspacing="2" cellpadding="2" border="0">
	<tr>
		<th ><h2><?php echo Text::_("Original");?></h2></th>
		<th ><h2><?php echo Text::_("Translation");?></h2></th>
	</tr>
	<tr>
		<td class="contentheading" style="width:50%!important"><script>document.write(title_orig);</script></td>
		<td class="contentheading" ><script>document.write(title);</script></td>
	</tr>
	<tr>
		<script>document.write("<td valign=\"top\" >" + alltext_orig + "</td>");</script>
		<script>document.write("<td valign=\"top\" >" + alltext + "</td>");</script>
	</tr>
	<tr>
		<td align="center" colspan="2"><a href="javascript:;" onClick="window.print(); return false"><?php echo Text::_("Print");?></a></td>
	</tr>
</table>
