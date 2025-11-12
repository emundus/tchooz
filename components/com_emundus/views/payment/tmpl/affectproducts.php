<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Repositories\Payment\ProductRepository;

$app = Factory::getApplication();

$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();

assert($this->product_repository instanceof ProductRepository);
$products = $this->product_repository->getProducts(100);

$datas = [
	'fnums' => $this->fnums,
    'shortLang' => $short_lang,
    'currentLanguage' => $current_lang,
];
?>

<div id="em-component-vue"
     component="Payment/AlterFilesProducts"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash . uniqid() ?>"></script>
