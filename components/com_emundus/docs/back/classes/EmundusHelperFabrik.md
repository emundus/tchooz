***

# EmundusHelperFabrik

Content Component Query Helper



* Full name: `\EmundusHelperFabrik`




## Methods


### updateParam



```php
public static updateParam(mixed $params, mixed $attribute, mixed $value): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |
| `$attribute` | **mixed** |  |
| `$value` | **mixed** |  |





***

### prepareListParams



```php
public static prepareListParams(): mixed
```



* This method is **static**.








***

### prepareFormParams



```php
public static prepareFormParams(mixed $init_plugins = true, mixed $type = &#039;&#039;): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$init_plugins` | **mixed** |  |
| `$type` | **mixed** |  |





***

### prepareSubmittionPlugin



```php
public prepareSubmittionPlugin(mixed $params): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### prepareGroupParams



```php
public static prepareGroupParams(): mixed
```



* This method is **static**.








***

### prepareElementParameters



```php
public static prepareElementParameters(mixed $plugin, mixed $notempty = true, mixed $attachementId): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$plugin` | **mixed** |  |
| `$notempty` | **mixed** |  |
| `$attachementId` | **mixed** |  |





***

### getDBType



```php
public static getDBType(mixed $plugin): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$plugin` | **mixed** |  |





***

### initLabel



```php
public static initLabel(mixed $plugin): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$plugin` | **mixed** |  |





***

### prepareFabrikMenuParams



```php
public static prepareFabrikMenuParams(): mixed
```



* This method is **static**.








***

### addOption



```php
public static addOption(mixed $eid, mixed $label, mixed $value): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |
| `$label` | **mixed** |  |
| `$value` | **mixed** |  |





***

### addNotEmptyValidation



```php
public static addNotEmptyValidation(mixed $eid, mixed $message = &#039;&#039;, mixed $condition = &#039;&#039;): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |
| `$message` | **mixed** |  |
| `$condition` | **mixed** |  |





***

### checkFabrikJoins



```php
public static checkFabrikJoins(mixed $eid, mixed $name, mixed $plugin, mixed $group_id): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |
| `$name` | **mixed** |  |
| `$plugin` | **mixed** |  |
| `$group_id` | **mixed** |  |





***

### addJsAction



```php
public static addJsAction(mixed $eid, mixed $action): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |
| `$action` | **mixed** |  |





***

### getTableFromFabrik



```php
public static getTableFromFabrik(mixed $id, mixed $object = &#039;list&#039;): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$object` | **mixed** |  |





***

### createFilterList



```php
public static createFilterList(mixed& $filters, mixed $eid, mixed $value, mixed $condition = &#039;=&#039;, mixed $join = &#039;AND&#039;, mixed $hidden, mixed $raw): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filters` | **mixed** |  |
| `$eid` | **mixed** |  |
| `$value` | **mixed** |  |
| `$condition` | **mixed** |  |
| `$join` | **mixed** |  |
| `$hidden` | **mixed** |  |
| `$raw` | **mixed** |  |





***

### getFormattedPhoneNumberValue



```php
public static getFormattedPhoneNumberValue(mixed $phone_number, mixed $format = PhoneNumberFormat::E164): string
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$phone_number` | **mixed** | string The phone number to format |
| `$format` | **mixed** | int The format to use<br />0 =&gt; E164<br />1 =&gt; INTERNATIONAL<br />2 =&gt; NATIONAL<br />3 =&gt; RFC3966 |


**Return Value:**

The formatted phone number, if the phone number is not valid, empty string is returned




***

### getDbTableName



```php
public static getDbTableName(mixed $formid): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$formid` | **mixed** |  |





***

### formatElementValue



```php
public static formatElementValue(mixed $elt_name, mixed $raw_value, mixed $groupId = null, mixed $uid = null, mixed $html = false): mixed|string|null
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elt_name` | **mixed** | string fabrik element name |
| `$raw_value` | **mixed** | string&amp;#124;array raw value of the element |
| `$groupId` | **mixed** | int group ID of the element |
| `$uid` | **mixed** | int user ID for replace in databasejoin |
| `$html` | **mixed** | bool if the value should be formatted with HTML tags |




**Throws:**

- [`Exception`](./Exception.md)



***

### encryptDatas



```php
public static encryptDatas(mixed $value, mixed $encryption_key = null, mixed $cipher = &#039;aes-128-cbc&#039;, mixed $iv = null): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **mixed** |  |
| `$encryption_key` | **mixed** |  |
| `$cipher` | **mixed** |  |
| `$iv` | **mixed** |  |





***

### decryptDatas



```php
public static decryptDatas(mixed $value, mixed $encryption_key = null, mixed $cipher = &#039;aes-128-cbc&#039;): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **mixed** |  |
| `$encryption_key` | **mixed** |  |
| `$cipher` | **mixed** |  |





***

### oldDecryptDatas



```php
public static oldDecryptDatas(mixed $value, mixed $encryption_key = null): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **mixed** |  |
| `$encryption_key` | **mixed** |  |





***

### migrateEncryptDatas



```php
public static migrateEncryptDatas(mixed $old_cipher, mixed $new_cipher, mixed $old_key, mixed $new_key, mixed $datas, mixed $iv = null): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$old_cipher` | **mixed** |  |
| `$new_cipher` | **mixed** |  |
| `$old_key` | **mixed** |  |
| `$new_key` | **mixed** |  |
| `$datas` | **mixed** |  |
| `$iv` | **mixed** |  |





***

### getFabrikDateParam



```php
public static getFabrikDateParam(mixed $elt, mixed $param): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elt` | **mixed** |  |
| `$param` | **mixed** |  |





***


***
> Automatically generated on 2024-08-19
