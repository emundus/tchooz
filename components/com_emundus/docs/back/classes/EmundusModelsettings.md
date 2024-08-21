***

# EmundusModelsettings





* Full name: `\EmundusModelsettings`
* Parent class: [`JModelList`](./JModelList.md)



## Properties


### db



```php
private $db
```






***

### user



```php
private $user
```






***

### app



```php
private $app
```






***

## Methods


### __construct



```php
public __construct(): mixed
```













***

### getColorClasses

Get all colors available for status and tags

```php
public getColorClasses(): string[]
```













***

### clean

A helper function that replace spaces and special characters

```php
public clean(mixed $string): array|string|string[]|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$string` | **mixed** |  |






***

### getStatus

Get all status available and check if files is associated

```php
public getStatus(): array|false|mixed
```













***

### getTags

Get all emundus tags available

```php
public getTags(): array|false|mixed
```













***

### deleteTag

Delete a tag, foreign key delete also all files associated to this tag

```php
public deleteTag(mixed $id): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### createTag

Create a emundus tag with a default label and color

```php
public createTag(): false|mixed|null
```













***

### createStatus

Create a new status

```php
public createStatus(): false|mixed|null
```













***

### updateStatus

Update a status (label and colors)

```php
public updateStatus(mixed $status, mixed $label, mixed $color): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$status` | **mixed** |  |
| `$label` | **mixed** |  |
| `$color` | **mixed** |  |






***

### updateStatusOrder



```php
public updateStatusOrder(mixed $status): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$status` | **mixed** |  |






***

### deleteStatus

Delete a status that is not associated to files

```php
public deleteStatus(mixed $id, mixed $step): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$step` | **mixed** |  |






***

### updateTags

Update emundus tags (label and colors)

```php
public updateTags(mixed $tag, mixed $label, mixed $color): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |
| `$label` | **mixed** |  |
| `$color` | **mixed** |  |






***

### getFooterArticles

Get footer articles from the module mod_emundus_footer

```php
public getFooterArticles(): false|\stdClass
```













***

### getOldFooterArticles

Deprecated footer handling
Get footer content from custom module in footer-a position

```php
private getOldFooterArticles(): mixed
```













***

### getArticle

Get a Joomla article

```php
public getArticle(mixed $lang_code, mixed $article_id, mixed $article_alias = &#039;&#039;, mixed $reference_field = &#039;introtext&#039;): false|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lang_code` | **mixed** |  |
| `$article_id` | **mixed** |  |
| `$article_alias` | **mixed** |  |
| `$reference_field` | **mixed** |  |






***

### updateArticle

Update a Joomla article

```php
public updateArticle(mixed $content, mixed $lang_code, mixed $article_id, mixed $article_alias = &#039;&#039;, mixed $reference_field = &#039;introtext&#039;, mixed $note): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$content` | **mixed** |  |
| `$lang_code` | **mixed** |  |
| `$article_id` | **mixed** |  |
| `$article_alias` | **mixed** |  |
| `$reference_field` | **mixed** |  |
| `$note` | **mixed** |  |






***

### updateFooter

Update the emundus footer module with 2 columns

```php
public updateFooter(mixed $col1, mixed $col2): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$col1` | **mixed** |  |
| `$col2` | **mixed** |  |






***

### updateOldFooter

Deprecated footer handling

```php
private updateOldFooter(mixed $col1, mixed $col2): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$col1` | **mixed** |  |
| `$col2` | **mixed** |  |






***

### getEditorVariables

Get emundus tags published for wysiwig editor (emails, settings, formbuilder)

```php
public getEditorVariables(): array|false|mixed
```













***

### updateLogo

Update the main logo store in a module

```php
public updateLogo(mixed $target_file, mixed $new_logo, mixed $ext): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$target_file` | **mixed** |  |
| `$new_logo` | **mixed** |  |
| `$ext` | **mixed** |  |






***

### onAfterCreateCampaign



```php
public onAfterCreateCampaign(mixed $user_id = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |






***

### onAfterCreateForm



```php
public onAfterCreateForm(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |






***

### createParam



```php
public createParam(mixed $param, null $user_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$param` | **mixed** | String The param to be saved in the user account. |
| `$user_id` | **null** |  |






***

### removeParam



```php
public removeParam(mixed $param, mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$param` | **mixed** |  |
| `$user_id` | **mixed** |  |






***

### getDatasFromTable



```php
public getDatasFromTable(mixed $table): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table` | **mixed** |  |






***

### saveDatas



```php
public saveDatas(mixed $form): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form` | **mixed** |  |






***

### saveImportedDatas



```php
public saveImportedDatas(mixed $form, mixed $datas): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form` | **mixed** |  |
| `$datas` | **mixed** |  |






***

### checkFirstDatabaseJoin



```php
public checkFirstDatabaseJoin(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |






***

### moveUploadedFileToDropbox



```php
public moveUploadedFileToDropbox(mixed $file, mixed $name, mixed $extension, mixed $campaign_cat, mixed $filesize): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **mixed** |  |
| `$name` | **mixed** |  |
| `$extension` | **mixed** |  |
| `$campaign_cat` | **mixed** |  |
| `$filesize` | **mixed** |  |






***

### getBannerModule



```php
public getBannerModule(): mixed
```













***

### updateBannerImage



```php
public updateBannerImage(): mixed
```













***

### getOnboardingLists



```php
public getOnboardingLists(): mixed
```













***

### getHomeArticle



```php
public getHomeArticle(): mixed
```













***

### getRgpdArticles



```php
public getRgpdArticles(): mixed
```













***

### publishArticle



```php
public publishArticle(mixed $publish, mixed $article_id, mixed $article_alias = &#039;&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$publish` | **mixed** |  |
| `$article_id` | **mixed** |  |
| `$article_alias` | **mixed** |  |






***

### getArticlePublishedState



```php
public getArticlePublishedState(mixed $article_id, mixed $article_alias = &#039;&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$article_id` | **mixed** |  |
| `$article_alias` | **mixed** |  |






***

### getMenuId



```php
public getMenuId(mixed $link = &#039;&#039;, mixed $alias = &#039;&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$link` | **mixed** |  |
| `$alias` | **mixed** |  |






***

### getEmundusParams

Get only accessibles parameters based on settings-applicants.json and settings-general.json files
This function is used to avoid exposing all parameters to the front-end

```php
public getEmundusParams(): array
```













***

### updateEmundusParam



```php
public updateEmundusParam(mixed $component, mixed $param, mixed $value, mixed $config): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$component` | **mixed** |  |
| `$param` | **mixed** |  |
| `$value` | **mixed** |  |
| `$config` | **mixed** |  |






***

### setArticleNeedToBeModify



```php
public setArticleNeedToBeModify(): mixed
```













***

### getArticleNeedToBeModify



```php
public getArticleNeedToBeModify(): mixed
```













***

### getFavicon



```php
public getFavicon(): mixed
```













***

### getEmailTemplate



```php
public getEmailTemplate(mixed $subject): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$subject` | **mixed** |  |






***

### sendTestMailSettings



```php
public sendTestMailSettings(mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |






***

### convertTextException



```php
public convertTextException(mixed $textException): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$textException` | **mixed** |  |






***


***
> Last updated on 20/08/2024
