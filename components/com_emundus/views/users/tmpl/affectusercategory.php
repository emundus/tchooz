<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Repositories\User\UserCategoryRepository;

$categoryRepository = new UserCategoryRepository();
$categories = $categoryRepository->getAllCategories();

?>

<div>
    <div class="form-group tw-flex-col tw-gap-2">
        <label><?php echo Text::_('COM_EMUNDUS_USER_CATEGORY'); ?></label>
        <select class="tw-w-full" id="user_category" name="user_category">
            <option value="0"><?= Text::_('COM_EMUNDUS_SELECT_USER_CATEGORY') ?></option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category->id ?>"><?= htmlspecialchars(Text::_($category->label), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
