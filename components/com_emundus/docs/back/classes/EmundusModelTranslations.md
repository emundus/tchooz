***

# EmundusModelTranslations





* Full name: `\EmundusModelTranslations`
* Parent class: [`JModelList`](./JModelList.md)



## Properties


### _db



```php
public $_db
```






***

## Methods


### __construct



```php
public __construct(): mixed
```












***

### checkSetup

Check if translation tool is ready to use

```php
public checkSetup(): false|mixed|null
```












***

### configureSetup

Configure setup at first launch of the translation tool

```php
public configureSetup(): false|mixed|void
```












***

### getTranslationsObject

Get our translations definitions

```php
public getTranslationsObject(): array
```












***

### getDatas

Get references datas

```php
public getDatas(mixed $table, mixed $reference_id, mixed $label, mixed $filters): array|false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table` | **mixed** |  |
| `$reference_id` | **mixed** |  |
| `$label` | **mixed** |  |
| `$filters` | **mixed** |  |





***

### getChildrens

Get childrens to filter our translations

```php
public getChildrens(mixed $table, mixed $reference_id, mixed $label): array|false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table` | **mixed** |  |
| `$reference_id` | **mixed** |  |
| `$label` | **mixed** |  |





***

### getTranslations

Get translations with many filters

```php
public getTranslations(mixed $type = &#039;override&#039;, mixed $lang_code = &#039;*&#039;, mixed $search = &#039;&#039;, mixed $location = &#039;&#039;, mixed $reference_table = &#039;&#039;, mixed $reference_id, mixed $reference_fields = &#039;&#039;, mixed $tag = &#039;&#039;): array|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **mixed** |  |
| `$lang_code` | **mixed** |  |
| `$search` | **mixed** |  |
| `$location` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$reference_id` | **mixed** |  |
| `$reference_fields` | **mixed** |  |
| `$tag` | **mixed** |  |





***

### insertTranslation

Create a new translation in base and insert it in override file

```php
public insertTranslation(mixed $tag, mixed $override, mixed $lang_code, mixed $location = &#039;&#039;, mixed $type = &#039;override&#039;, mixed $reference_table = &#039;&#039;, mixed $reference_id, mixed $reference_field = &#039;&#039;): bool|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |
| `$override` | **mixed** |  |
| `$lang_code` | **mixed** |  |
| `$location` | **mixed** |  |
| `$type` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$reference_id` | **mixed** |  |
| `$reference_field` | **mixed** |  |





***

### updateTranslation

Update a translation
If the translation is not override (ex. com_emundus) we insert it in override file

```php
public updateTranslation(mixed $tag, mixed $override, mixed $lang_code, mixed $type = &#039;override&#039;, mixed $reference_table = &#039;&#039;, mixed $reference_id, mixed $reference_field = &#039;&#039;): bool|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |
| `$override` | **mixed** |  |
| `$lang_code` | **mixed** |  |
| `$type` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$reference_id` | **mixed** |  |
| `$reference_field` | **mixed** |  |


**Return Value:**

false if error, tag if success




***

### deleteTranslation

Delete a translation in base and then remove it from overrides files

```php
public deleteTranslation(mixed $tag = &#039;&#039;, mixed $lang_code = &#039;*&#039;, mixed $reference_table = &#039;&#039;, mixed $reference_id): false|void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |
| `$lang_code` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$reference_id` | **mixed** |  |





***

### getDefaultLanguage

Get default language of the platform

```php
public getDefaultLanguage(): false|mixed|null
```












***

### getPlatformLanguages



```php
public getPlatformLanguages(): array
```












***

### getAllLanguages

Get all languages available on our platform

```php
public getAllLanguages(): array|false|mixed
```












***

### updateLanguage

Update default language or/and secondary languages

```php
public updateLanguage(mixed $lang_code, mixed $published, mixed $default): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lang_code` | **mixed** |  |
| `$published` | **mixed** |  |
| `$default` | **mixed** | boolean to specify if we are changing default language or secondary languages |





***

### updateFalangModule



```php
public updateFalangModule(mixed $published): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$published` | **mixed** |  |





***

### getTranslationsFalang

Get translations with Falang system (campaigns, emails, programs, status)

```php
public getTranslationsFalang(mixed $default_lang, mixed $lang_to, mixed $reference_id, mixed $fields, mixed $reference_table): false|\stdClass
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$default_lang` | **mixed** |  |
| `$lang_to` | **mixed** |  |
| `$reference_id` | **mixed** |  |
| `$fields` | **mixed** |  |
| `$reference_table` | **mixed** |  |





***

### updateFalangTranslation

Update a translation with Falang system

```php
public updateFalangTranslation(mixed $value, mixed $lang_to, mixed $reference_table, mixed $reference_id, mixed $field, mixed $user_id = null): false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **mixed** |  |
| `$lang_to` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$reference_id` | **mixed** |  |
| `$field` | **mixed** |  |
| `$user_id` | **mixed** |  |





***

### getJoinReferenceId

Get reference id by filters

```php
public getJoinReferenceId(mixed $reference_table, mixed $reference_column, mixed $join_table, mixed $join_column, mixed $reference_id): array|false|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$reference_table` | **mixed** |  |
| `$reference_column` | **mixed** |  |
| `$join_table` | **mixed** |  |
| `$join_column` | **mixed** |  |
| `$reference_id` | **mixed** |  |





***

### getOrphelins



```php
public getOrphelins(mixed $default_lang, mixed $lang_code, mixed $type = &#039;override&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$default_lang` | **mixed** |  |
| `$lang_code` | **mixed** |  |
| `$type` | **mixed** |  |





***

### sendPurposeNewLanguage



```php
public sendPurposeNewLanguage(mixed $language, mixed $comment): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$language` | **mixed** |  |
| `$comment` | **mixed** |  |





***

### checkTagIsCorrect



```php
public checkTagIsCorrect(mixed $tag, mixed $override, mixed $action, mixed $lang): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |
| `$override` | **mixed** |  |
| `$action` | **mixed** |  |
| `$lang` | **mixed** |  |





***

### checkTagExists



```php
public checkTagExists(mixed $tag, mixed $reference_table, mixed $reference_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$reference_id` | **mixed** |  |





***

### checkTagExistsInOverrideFiles



```php
public checkTagExistsInOverrideFiles(mixed $tag, mixed $languages = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |
| `$languages` | **mixed** |  |





***

### generateNewTag



```php
public generateNewTag(mixed $tag, mixed $reference_table = &#039;&#039;, mixed $reference_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$reference_id` | **mixed** |  |





***

### updateElementLabel



```php
public updateElementLabel(mixed $tag, mixed $reference_table, mixed $reference_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tag` | **mixed** |  |
| `$reference_table` | **mixed** |  |
| `$reference_id` | **mixed** |  |





***


***
> Automatically generated on 2024-08-02
