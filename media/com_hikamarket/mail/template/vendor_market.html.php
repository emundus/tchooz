<?php
/**
 * @package    HikaMarket for Joomla!
 * @version    5.0.0
 * @author     Obsidev S.A.R.L.
 * @copyright  (C) 2011-2024 OBSIDEV. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><style type="text/css">
body.hikashop_mail { background-color:#ffffff; color:#575757; }
.ReadMsgBody{width:100%;}
.ExternalClass{width:100%;}
div, p, a, li, td {-webkit-text-size-adjust:none;}
@media (min-width:600px){
	#hikamarket_mail {width:600px !important;margin:auto !important;}
	.pict img {max-width:500px !important;height:auto !important;}
}
@media (max-width:330px){
	#hikamarket_mail{width:300px !important; margin:auto !important;}
	table[class=w600], td[class=w600], table[class=w598], td[class=w598], table[class=w500], td[class=w500], img[class="w600"]{width:100% !important;}
	td[class="w49"] { width: 10px !important;}
	.pict img {max-width:278px; height:auto !important;}
}
@media (min-width:331px) and (max-width:480px){
	#hikamarket_mail{width:450px !important; margin:auto !important;}
	table[class=w600], td[class=w600], table[class=w598], td[class=w598], table[class=w500], td[class=w500], img[class="w600"]{width:100% !important;}
	td[class="w49"] { width: 20px !important;}
	.pict img {max-width:408px;  height:auto !important;}
}
h1{color:#1c8faf;font-size:16px;font-weight:bold;border-bottom:1px solid #ddd; padding-bottom:10px;}
h2{color:#89a9c1;font-size:14px;font-weight:bold;margin-top:20px;margin-bottom:5px;border-bottom:1px solid #d6d6d6;padding-bottom:4px;}
a:visited{cursor:pointer;color:#2d9cbb;text-decoration:none;border:none;}
</style>

<div id="hikamarket_mail" style="font-family:Arial, Helvetica,sans-serif;font-size:12px;line-height:18px;width:100%;background-color:#ffffff;padding-bottom:20px;color:#5b5b5b;">
	<div class="hikashop_online" style="font-family:Arial, Helvetica,sans-serif;font-size:11px;line-height:18px;color:#6a5c6b;text-decoration:none;margin:10px;text-align:center;">
		<a style="cursor:pointer;color:#2d9cbb;text-decoration:none;border:none;" href="{VAR:URL}">
			<span class="hikashop_online" style="color:#6a5c6b;text-decoration:none;font-size:11px;margin-top:10px;margin-bottom:10px;text-align:center;">
				{TXT:HIKASHOP_MAIL_HEADER}
			</span>
		</a>
	</div>
	<table class="w600" style="font-family:Arial, Helvetica, sans-serif;font-size:12px;line-height:18px;margin:auto;background-color:#ebebeb;" border="0" cellspacing="0" cellpadding="0" width="600" align="center">
		<tr style="line-height: 0px;">
			<td class="w600" style="line-height:0px" width="600" valign="bottom">
				<img class="w600" src="{VAR:LIVE_SITE}media/com_hikamarket/images/mail/header.png" border="0" alt="" />
			</td>
		</tr>
		<tr>
			<td class="w600" style="" width="600" align="center">
{VAR:TPL_CONTENT}
			</td>
		</tr>
		<tr style="line-height: 0px;">
			<td class="w600" style="line-height:0px" width="600" valign="top">
				<img class="w600" src="{VAR:LIVE_SITE}/media/com_hikamarket/images/mail/footer.png" border="0" alt="--" />
			</td>
		</tr>
	</table>
</div>
