<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php 
	$your_order_text = '{TXT:YOUR_ORDER}';
?>
<style type="text/css">
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
h1 { text-align: center;color: #e30013; border-bottom: 3px solid #e30013 !important;}
h2 {color: #e30013 !important; padding: 0px !important; margin-bottom: 0px; border-bottom: 1px solid #d6d6d6;}
.hika_template_color {color:#e30013 !important;}
div#title h1, div#title h2 {text-align: center; border-width: 0px; color: #fff !important;}
a:visited{cursor:pointer;color:#2d9cbb;text-decoration:none;border:none;}
div#title h1 {border-bottom: 1px solid #fff !important; font-size: 1.7em !important;}
div#content p {margin: 0 0 5px 0;}
div#title img {display: none;}

table.w550 {
	margin-top: 25px !important;
    width: 530px !important;
    margin: 25px 10px 0 10px;
    padding: 10px 0 0 0 !important;
    border: 1px solid #e30013;
    border-width: 1px 0 0 0;
}
div.w550 br + a {
	display: block;
    border: 1px solid #e30013;
    background-color: #fe005a;
    border-radius: 5px;
    margin: 10px 0 0 0;
    margin-left: auto;
    margin-right: auto;
    height: 35px;
    width: 100px;
    color: #fe005a;
    white-space: nowrap;
    overflow: hidden;
}
div.w550 br + a:hover {
	background-color: #e30013;
	color: #e30013;
}
div.w550 br + a:before {
    display: block;
    content: '<?php echo $your_order_text; ?>';
    font-size: 1.2em;
    font-weight: bold;
    color: #fff;
    margin: 8px 10px 0 10px;
}
div#title {
    margin: 20px 0 0 0;
    padding: 10px 5px;
    border-radius: 5px 5px 0 0;
    border: 1px solid #adadad;
    border-width: 1px 1px 0 1px;
    background-color: #fe005a;
    background: -webkit-linear-gradient(left,#fe005a,#e30013);
    background: -o-linear-gradient(right,#fe005a,#e30013);
    background: -moz-linear-gradient(right,#fe005a,#e30013);
    background: linear-gradient(to right,#fe005a,#e30013);
}
.cart_button.hika_template_color{
	color: #fff!important;
    font-size: 15px;
    font-weight: bold;
	background-color: #fe005a;
    border-radius: 5px;
    padding: 5px 10px;
}
a.cart_button.hika_template_color:hover {
    background-color: #e30013;
    text-decoration: none;
}
div#hikashop_mail {
    background-color: #ebebeb !important;
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
<!--			<img class="w600" src="{VAR:LIVE_SITE}media/com_hikashop/images/mail/header.png" border="0" alt="" /> -->
			</td>
		</tr>
		<tr>
			<td class="w600" style="text-align:left;" width="600">
{VAR:TPL_CONTENT}
			</td>
		</tr>
		<tr style="line-height: 0px;">
			<td class="w600" style="line-height:0px" width="600" valign="top">
<!--			<img class="w600" src="{VAR:LIVE_SITE}media/com_hikashop/images/mail/footer.png" border="0" alt="--" /> -->
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
