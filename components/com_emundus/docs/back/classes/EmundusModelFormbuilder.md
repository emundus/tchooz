***

# EmundusModelFormbuilder





* Full name: `\EmundusModelFormbuilder`
* Parent class: [`JModelList`](./JModelList.md)



## Properties


### app



```php
private $app
```






***

### m_translations



```php
private $m_translations
```






***

### h_fabrik



```php
private $h_fabrik
```






***

### db



```php
private $db
```






***

## Methods


### __construct



```php
public __construct(mixed $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **mixed** |  |





***

### replaceAccents



```php
public replaceAccents(mixed $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **mixed** |  |





***

### translate

TRANSLATION SYSTEM

```php
public translate(mixed $key, mixed $values, mixed $reference_table = &#039;&#039;, mixed $id = &#039;&#039;, mixed $reference_field = &#039;&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **mixed** |  |
| `$values` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$id` | **mixed** |  |
| `$reference_field` | **mixed** |  |





***

### updateTranslation



```php
public updateTranslation(mixed $key, mixed $values, mixed $reference_table = &#039;&#039;, mixed $reference_id, mixed $reference_field = &#039;&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **mixed** |  |
| `$values` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$reference_id` | **mixed** |  |
| `$reference_field` | **mixed** |  |





***

### deleteTranslation



```php
public deleteTranslation(mixed $text): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$text` | **mixed** |  |





***

### copyFileToAdministration

Copy languages file to administration to get elements translations in backoffice

```php
public copyFileToAdministration(mixed $langtag): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$langtag` | **mixed** |  |





***

### getTranslation

Ge translation of an element in all languages

```php
public getTranslation(mixed $text, mixed $code_lang): string|string[]
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$text` | **mixed** |  |
| `$code_lang` | **mixed** |  |





***

### getJTEXTA

Get translation of an array

```php
public getJTEXTA(mixed $toJTEXT): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$toJTEXT` | **mixed** |  |





***

### getJTEXT

Get translation of a text

```php
public getJTEXT(mixed $toJTEXT): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$toJTEXT` | **mixed** |  |





***

### formsTrad

Update translations

```php
public formsTrad(mixed $labelTofind, mixed $NewSubLabel, mixed $element = null, mixed $group = null, mixed $page = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$labelTofind` | **mixed** |  |
| `$NewSubLabel` | **mixed** |  |
| `$element` | **mixed** |  |
| `$group` | **mixed** |  |
| `$page` | **mixed** |  |





***

### getSpecialCharacters

END TRANSLATION SYSTEM

```php
public getSpecialCharacters(): mixed
```












***

### htmlspecial_array



```php
public htmlspecial_array(mixed& $variable): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$variable` | **mixed** |  |





***

### updateElementWithoutTranslation



```php
public updateElementWithoutTranslation(mixed $eid, mixed $label): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |
| `$label` | **mixed** |  |





***

### updateGroupWithoutTranslation



```php
public updateGroupWithoutTranslation(mixed $gid, mixed $label): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |
| `$label` | **mixed** |  |





***

### updatePageWithoutTranslation



```php
public updatePageWithoutTranslation(mixed $pid, mixed $label): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** |  |
| `$label` | **mixed** |  |





***

### updatePageIntroWithoutTranslation



```php
public updatePageIntroWithoutTranslation(mixed $pid, mixed $intro): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pid` | **mixed** |  |
| `$intro` | **mixed** |  |





***

### createApplicantMenu



```php
public createApplicantMenu(mixed $label, mixed $intro, mixed $prid, mixed $template): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$intro` | **mixed** |  |
| `$prid` | **mixed** |  |
| `$template` | **mixed** |  |





***

### createFabrikForm



```php
public createFabrikForm(mixed $prid, mixed $label, mixed $intro, mixed $type = &#039;&#039;, mixed $user = null): false|int|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** | int profile id |
| `$label` | **mixed** | array labels by language |
| `$intro` | **mixed** | array intros by language |
| `$type` | **mixed** | string (form &amp;#124;&amp;#124; eval) |
| `$user` | **mixed** |  |





***

### createFabrikList



```php
public createFabrikList(mixed $prid, mixed $formid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |
| `$formid` | **mixed** |  |





***

### joinFabrikListToProfile



```php
public joinFabrikListToProfile(mixed $listid, mixed $prid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$listid` | **mixed** |  |
| `$prid` | **mixed** |  |





***

### createSubmittionPage



```php
public createSubmittionPage(mixed $label, mixed $intro, mixed $prid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$intro` | **mixed** |  |
| `$prid` | **mixed** |  |





***

### deleteMenu



```php
public deleteMenu(mixed $menu): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$menu` | **mixed** |  |





***

### saveAsTemplate



```php
public saveAsTemplate(mixed $menu, mixed $template): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$menu` | **mixed** |  |
| `$template` | **mixed** |  |





***

### createGroup



```php
public createGroup(mixed $label, mixed $fid, mixed $repeat_group_show_first = 1, mixed $mode = &#039;form&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$fid` | **mixed** |  |
| `$repeat_group_show_first` | **mixed** |  |
| `$mode` | **mixed** |  |





***

### deleteGroup



```php
public deleteGroup(mixed $group): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |





***

### createSectionSimpleElements



```php
public createSectionSimpleElements(mixed $gid, mixed $plugins, mixed $mode = &#039;forms&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |
| `$plugins` | **mixed** |  |
| `$mode` | **mixed** |  |





***

### createElement



```php
public createElement(mixed $name, mixed $group_id, mixed $plugin, mixed $label, mixed $default = &#039;&#039;, mixed $hidden, mixed $create_column = 1, mixed $show_in_list_summary = 1, mixed $published = 1, mixed $parent_id, mixed $width = 20): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **mixed** |  |
| `$group_id` | **mixed** |  |
| `$plugin` | **mixed** |  |
| `$label` | **mixed** |  |
| `$default` | **mixed** |  |
| `$hidden` | **mixed** |  |
| `$create_column` | **mixed** |  |
| `$show_in_list_summary` | **mixed** |  |
| `$published` | **mixed** |  |
| `$parent_id` | **mixed** |  |
| `$width` | **mixed** |  |





***

### createSimpleElement

Returns the element id of the created element
false if error

```php
public createSimpleElement(mixed $gid, mixed $plugin, mixed $attachementId = null, mixed $evaluation, mixed $labels = null, mixed $user = null): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |
| `$plugin` | **mixed** |  |
| `$attachementId` | **mixed** |  |
| `$evaluation` | **mixed** |  |
| `$labels` | **mixed** |  |
| `$user` | **mixed** |  |





***

### updateGroupElementsOrder



```php
public updateGroupElementsOrder(mixed $elements, mixed $group_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements` | **mixed** |  |
| `$group_id` | **mixed** |  |





***

### updateOrder

Update orders of a group's elements

```php
public updateOrder(mixed $elements, mixed $group_id, mixed $user, mixed $moved_el = null): array|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elements` | **mixed** |  |
| `$group_id` | **mixed** |  |
| `$user` | **mixed** |  |
| `$moved_el` | **mixed** |  |





***

### updateElementOrder



```php
public updateElementOrder(mixed $group_id, mixed $element_id, mixed $new_index): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group_id` | **mixed** |  |
| `$element_id` | **mixed** |  |
| `$new_index` | **mixed** |  |





***

### ChangeRequire



```php
public ChangeRequire(mixed $element, mixed $user): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$user` | **mixed** |  |





***

### UpdateParams



```php
public UpdateParams(mixed $element, mixed $user): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$user` | **mixed** |  |





***

### updateGroupParams



```php
public updateGroupParams(mixed $group_id, mixed $params, mixed $lang = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group_id` | **mixed** |  |
| `$params` | **mixed** |  |
| `$lang` | **mixed** |  |





***

### duplicateElement



```php
public duplicateElement(mixed $eid, mixed $group, mixed $old_group, mixed $form_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |
| `$group` | **mixed** |  |
| `$old_group` | **mixed** |  |
| `$form_id` | **mixed** |  |





***

### getElement

Return an element with fabrik parameters

```php
public getElement(mixed $element, mixed $gid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$gid` | **mixed** |  |





***

### getSimpleElement



```php
public getSimpleElement(mixed $eid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |





***

### deleteElement



```php
public deleteElement(mixed $elt): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elt` | **mixed** |  |





***

### reorderMenu



```php
public reorderMenu(mixed $menus, mixed $profile): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$menus` | **mixed** |  |
| `$profile` | **mixed** |  |





***

### getGroupOrdering



```php
public getGroupOrdering(mixed $gid, mixed $fid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |
| `$fid` | **mixed** |  |





***

### reorderGroup



```php
public reorderGroup(mixed $gid, mixed $fid, mixed $order): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |
| `$fid` | **mixed** |  |
| `$order` | **mixed** |  |





***

### getPagesModel

Get menus templates

```php
public getPagesModel(mixed $form_ids = [], mixed $model_ids = []): array|mixed|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_ids` | **mixed** |  |
| `$model_ids` | **mixed** |  |





***

### createMenuFromTemplate

Create a menu from a choosen template

```php
public createMenuFromTemplate(mixed $label, mixed $intro, mixed $formid, mixed $prid, bool $keep_structure = false): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$intro` | **mixed** |  |
| `$formid` | **mixed** |  |
| `$prid` | **mixed** |  |
| `$keep_structure` | **bool** | keep structure true means that the new form id will store data in same table as the template |





***

### checkIfModelTableIsUsedInForm



```php
public checkIfModelTableIsUsedInForm(mixed $model_id, mixed $profile_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model_id` | **mixed** |  |
| `$profile_id` | **mixed** |  |





***

### createDatabaseTableFromTemplate



```php
public createDatabaseTableFromTemplate(string $template_table_name, int $profile_id, string $parent_table_name = &#039;&#039;, mixed $group_id): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$template_table_name` | **string** |  |
| `$profile_id` | **int** |  |
| `$parent_table_name` | **string** |  |
| `$group_id` | **mixed** |  |





***

### checkConstraintGroup



```php
public checkConstraintGroup(mixed $cid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |





***

### checkVisibility



```php
public checkVisibility(mixed $group, mixed $cid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$cid` | **mixed** |  |





***

### publishUnpublishElement



```php
public publishUnpublishElement(mixed $element): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |





***

### hiddenUnhiddenElement



```php
public hiddenUnhiddenElement(mixed $element): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |





***

### getDatabasesJoin



```php
public getDatabasesJoin(): mixed
```












***

### getDatabaseJoinOrderColumns



```php
public getDatabaseJoinOrderColumns(mixed $database_name): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$database_name` | **mixed** |  |





***

### getAllDatabases



```php
public getAllDatabases(): mixed
```












***

### enableRepeatGroup



```php
public enableRepeatGroup(mixed $gid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### getFabrikGroup



```php
private getFabrikGroup(mixed $gid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### disableRepeatGroup



```php
public disableRepeatGroup(mixed $gid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### displayHideGroup



```php
public displayHideGroup(mixed $gid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |





***

### updateMenuLabel



```php
public updateMenuLabel(mixed $label, mixed $pid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$label` | **mixed** |  |
| `$pid` | **mixed** |  |





***

### getFormTesting



```php
public getFormTesting(mixed $prid, mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$prid` | **mixed** |  |
| `$uid` | **mixed** |  |





***

### createTestingFile



```php
public createTestingFile(mixed $cid, mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |
| `$uid` | **mixed** |  |





***

### deleteFormTesting



```php
public deleteFormTesting(mixed $fnum, mixed $uid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$uid` | **mixed** |  |





***

### retriveElementFormAssociatedDoc



```php
public retriveElementFormAssociatedDoc(mixed $gid, mixed $docid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$gid` | **mixed** |  |
| `$docid` | **mixed** |  |





***

### updateDefaultValue



```php
public updateDefaultValue(mixed $eid, mixed $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |
| `$value` | **mixed** |  |





***

### getSection



```php
public getSection(mixed $section): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$section` | **mixed** |  |





***

### updateElementOption



```php
public updateElementOption(mixed $element, mixed $oldOptions, mixed $index, mixed $newTranslation, mixed $lang = &#039;fr&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$oldOptions` | **mixed** |  |
| `$index` | **mixed** |  |
| `$newTranslation` | **mixed** |  |
| `$lang` | **mixed** |  |





***

### getGroupId



```php
private getGroupId(mixed $element): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |





***

### getFabrikElementParams



```php
private getFabrikElementParams(mixed $element): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |





***

### updateFabrikElementParams



```php
private updateFabrikElementParams(mixed $element, mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$params` | **mixed** |  |





***

### getElementSubOption



```php
public getElementSubOption(mixed $element): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |





***

### addElementSubOption



```php
public addElementSubOption(mixed $element, mixed $newOption, mixed $lang): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$newOption` | **mixed** |  |
| `$lang` | **mixed** |  |





***

### deleteElementSubOption



```php
public deleteElementSubOption(mixed $element, mixed $index): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$index` | **mixed** |  |





***

### updateElementSubOptionsOrder



```php
public updateElementSubOptionsOrder(mixed $element, mixed $old_order, mixed $new_order): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$element` | **mixed** |  |
| `$old_order` | **mixed** |  |
| `$new_order` | **mixed** |  |





***

### getFormId



```php
private getFormId(mixed $group_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group_id` | **mixed** |  |





***

### addFormModel



```php
public addFormModel(mixed $form_id_to_copy, mixed $label): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id_to_copy` | **mixed** |  |
| `$label` | **mixed** |  |





***

### deleteFormModel



```php
public deleteFormModel(mixed $form_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id` | **mixed** |  |





***

### deleteFormModelFromIds



```php
public deleteFormModelFromIds(mixed $model_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model_ids` | **mixed** |  |





***

### copyForm

Duplicate fabrik form, return the id of the form copy, 0 if failed

```php
public copyForm(mixed $form_id_to_copy, mixed $label_prefix = &#039;&#039;): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id_to_copy` | **mixed** |  |
| `$label_prefix` | **mixed** |  |





***

### copyList



```php
public copyList(mixed $list_model, mixed $form_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$list_model` | **mixed** |  |
| `$form_id` | **mixed** |  |





***

### getList



```php
public getList(mixed $form_id, mixed $columns = &#039;*&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id` | **mixed** |  |
| `$columns` | **mixed** |  |





***

### copyGroups



```php
public copyGroups(mixed $form_id_to_copy, mixed $new_form_id, mixed $new_list_id, mixed $db_table_name, mixed $label_prefix = &#039;&#039;, mixed $user = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id_to_copy` | **mixed** |  |
| `$new_form_id` | **mixed** |  |
| `$new_list_id` | **mixed** |  |
| `$db_table_name` | **mixed** |  |
| `$label_prefix` | **mixed** |  |
| `$user` | **mixed** |  |





***

### getDocumentSample



```php
public getDocumentSample(mixed $attachment_id, mixed $profile_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attachment_id` | **mixed** |  |
| `$profile_id` | **mixed** |  |





***

### getSqlDropdownOptions



```php
public getSqlDropdownOptions(mixed $table, mixed $key, mixed $value, mixed $translate): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table` | **mixed** |  |
| `$key` | **mixed** |  |
| `$value` | **mixed** |  |
| `$translate` | **mixed** |  |





***


***
> Last updated on 20/08/2024
