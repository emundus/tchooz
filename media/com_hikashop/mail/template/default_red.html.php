<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><style type="text/css">
body.hikashop_mail { background-color:#ffffff; color:#575757; }
.ReadMsgBody{width:100%;}
.ExternalClass{width:100%;}
div, p, a, li, td {-webkit-text-size-adjust:none;}
@media (min-width:600px){
	#hikashop_mail {width:600px !important;margin:auto !important;}
	.pict img {max-width:500px !important;height:auto !important;}
}
@media (max-width:330px){
	#hikashop_mail{width:300px !important; margin:auto !important;}
	table[class=w600], td[class=w600], table[class=w598], td[class=w598], table[class=w500], td[class=w500], img[class="w600"]{width:100% !important;}
	td[class="w49"] { width: 10px !important;}
	.pict img {max-width:278px; height:auto !important;}
}
@media (min-width:331px) and (max-width:480px){
	#hikashop_mail{width:450px !important; margin:auto !important;}
	table[class=w600], td[class=w600], table[class=w598], td[class=w598], table[class=w500], td[class=w500], img[class="w600"]{width:100% !important;}
	td[class="w49"] { width: 20px !important;}
	.pict img {max-width:408px;  height:auto !important;}
}
h1{color:#e82525 !important;font-size:16px;font-weight:bold;border-bottom:1px solid #ddd; padding-bottom:10px;}
h2{color:#e82525 !important;font-size:14px;font-weight:bold;margin-top:20px;margin-bottom:5px;border-bottom:1px solid #d6d6d6;padding-bottom:4px;}
.hika_template_color {color:#e82525 !important;}
a:visited{cursor:pointer;color:#2d9cbb;text-decoration:none;border:none;}
.cart_button.hika_template_color{
	color: #fff!important;
    font-size: 15px;
    font-weight: bold;
    background-color: #e82525;
    border-radius: 5px;
    padding: 5px 10px;
}
a.cart_button.hika_template_color:hover {
    background-color: #961313;
    text-decoration: none;
}
{VAR:TPL_CSS}
</style>

<div id="hikashop_mail" style="font-family:Arial, Helvetica,sans-serif;font-size:12px;line-height:18px;width:100%;background-color:#ffffff;padding-bottom:20px;color:#5b5b5b;">
<!--{IF:TPL_HEADER}-->
	<div class="hikashop_online" style="font-family:Arial, Helvetica,sans-serif;font-size:11px;line-height:18px;color:#6a5c6b;text-decoration:none;margin:10px;text-align:center;">
<!--{IF:TPL_HEADER_URL}-->
		<a style="cursor:pointer;color:#2d9cbb;text-decoration:none;border:none;" href="{VAR:TPL_HEADER_URL}">
<!--{ENDIF:TPL_HEADER_URL}-->
			<span class="hikashop_online" style="color:#6a5c6b;text-decoration:none;font-size:11px;margin-top:10px;margin-bottom:10px;text-align:center;">
				{TXT:TPL_HEADER_TEXT}
			</span>
<!--{IF:TPL_HEADER_URL}-->
		</a>
<!--{ENDIF:TPL_HEADER_URL}-->
	</div>
<!--{ENDIF:TPL_HEADER}-->
	<table class="w600" style="font-family:Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;margin:auto;background-color:#ebebeb;text-align:center;" border="0" cellspacing="0" cellpadding="0" width="600">
		<tr style="line-height: 0px;">
			<td class="w600" style="line-height:0px" width="600" valign="bottom">
				<img class="w600" src="{VAR:LIVE_SITE}media/com_hikashop/images/mail/header_red.png" border="0" alt="" />
			</td>
		</tr>
		<tr>
			<td class="w600" style="text-align:left;" width="600">
{VAR:TPL_CONTENT}
			</td>
		</tr>
		<tr style="line-height: 0px;">
			<td class="w600" style="line-height:0px" width="600" valign="top">
				<img class="w600" src="{VAR:LIVE_SITE}media/com_hikashop/images/mail/footer_red.png" border="0" alt="--" />
			</td>
		</tr>
	</table>
<!--{IF:TPL_FOOTER}-->
	<div class="hikashop_online" style="font-family:Arial, Helvetica,sans-serif;font-size:11px;line-height:18px;color:#6a5c6b;text-decoration:none;margin:10px;text-align:center;">
<!--{IF:TPL_FOOTER_URL}-->
		<a style="cursor:pointer;color:#2d9cbb;text-decoration:none;border:none;" href="{VAR:TPL_FOOTER_URL}">
<!--{ENDIF:TPL_FOOTER_URL}-->
			<span class="hikashop_online" style="color:#6a5c6b;text-decoration:none;font-size:11px;margin-top:10px;margin-bottom:10px;text-align:center;">
				{TXT:TPL_FOOTER_TEXT}
			</span>
<!--{IF:TPL_FOOTER_URL}-->
		</a>
<!--{ENDIF:TPL_FOOTER_URL}-->
	</div>
<!--{ENDIF:TPL_FOOTER}-->
</div>
