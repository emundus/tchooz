<?php
/**
 * Default Form Template: Custom CSS
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

/**
 * If you need to make small adjustments or additions to the CSS for a Fabrik
 * template, you can create a custom_css.php file, which will be loaded after
 * the main template_css.php for the template.
 *
 * This file will be invoked as a PHP file, so the view type and form ID
 * can be used in order to narrow the scope of any style changes.  You do
 * this by prepending #{$view}_$c to any selectors you use.  This will become
 * (say) #form_12, or #details_11, which will be the HTML ID of your form
 * on the page.
 *
 * See examples below, which you should remove if you copy this file.
 *
 * Don't edit anything outside of the BEGIN and END comments.
 *
 * For more on custom CSS, see the Wiki at:
 *
 * http://www.fabrikar.com/forums/index.php?wiki/form-and-details-templates/#the-custom-css-file
 *
 * NOTE - for backward compatibility with Fabrik 2.1, and in case you
 * just prefer a simpler CSS file, without the added PHP parsing that
 * allows you to be be more specific in your selectors, we will also include
 * a custom.css we find in the same location as this file.
 *
 */


echo "<style>

.body {
	display: inline-block;
  width: 100%;
}

.title {
	text-align: center;
	margin-top: 30px !important;
}

table {
	width: 100%;
}

table td {
	text-align: center;
	border: 1px solid black;
}

table thead tr td {
	font-weight: bold;
}

.content-right {
	float: right;
	display: inline-block;
}

.content-right p {
	position: absolute;
	right: 0;
}

table.borderless {
	border: none;
}

table.borderless td {
	border: none;
}

.right-box tbody{
	width: 300px;
}

.right-box td {
	text-align: right;
}

.bottom-infos {
	page-break-inside: avoid;
	position: fixed !important;
  bottom: 0 !important;
}

</style>";

?>