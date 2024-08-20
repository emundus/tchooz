***

# EmundusControllerWebhook

eMundus Component Controller



* Full name: `\EmundusControllerWebhook`
* Parent class: [`JControllerLegacy`](./JControllerLegacy.md)



## Properties


### app



```php
protected $app
```






***

### m_files



```php
private $m_files
```






***

## Methods


### __construct

Constructor.

```php
public __construct(array $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An optional associative array of configuration settings. |





**See Also:**

* \JController - 

***

### callback



```php
public callback(): mixed
```












***

### yousign

Downloads the file associated to the YouSign procedure that was pushed.

```php
public yousign(): mixed
```












***

### addpipe

Gets video info from addpipe webhook

```php
public addpipe(): bool|string
```











**Throws:**

- [`Exception`](./Exception.md)



***

### FileSizeConvert

Converts bytes into human readable file size.

```php
public FileSizeConvert(string $bytes): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$bytes` | **string** |  |


**Return Value:**

human readable file size (2,87 Мб)




***

### is_file_uploaded

Check if video upladed by addpipe has been moved to applicant files.

```php
public is_file_uploaded(): void
```











**Throws:**

- [`Exception`](./Exception.md)



***

### setUserParam



```php
private setUserParam(string $user_email, mixed $param, string $value): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_email` | **string** |  |
| `$param` | **mixed** |  |
| `$value` | **string** |  |





***

### export_siscole



```php
public export_siscole(): false|void
```











**Throws:**

- [`Exception`](./Exception.md)



***

### export_banner



```php
public export_banner(): mixed
```












***

### process_banner



```php
public process_banner(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### update_banner



```php
public update_banner(mixed $id, mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### getzoomsession



```php
public getzoomsession(): mixed
```












***

### updateFlywirePaymentInfos

POST method
Waiting for :
 - callback_id
 - amount
 - at
 - status

```php
public updateFlywirePaymentInfos(): string
```









**Return Value:**

json_encoded




***

### updateAxeptaPaymentInfos



```php
public updateAxeptaPaymentInfos(): mixed
```












***

### getwidgets



```php
public getwidgets(): mixed
```












***


***
> Last updated on 20/08/2024
