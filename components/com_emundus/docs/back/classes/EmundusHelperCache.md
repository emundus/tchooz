***

# EmundusHelperCache

Content Component Cache Helper



* Full name: `\EmundusHelperCache`



## Properties


### cache



```php
private $cache
```






***

### group



```php
private $group
```






***

### cache_enabled



```php
private $cache_enabled
```






***

## Methods


### __construct



```php
public __construct(mixed $group = &#039;com_emundus&#039;, mixed $handler = &#039;&#039;, mixed $lifetime = &#039;&#039;, mixed $context = &#039;component&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |
| `$handler` | **mixed** |  |
| `$lifetime` | **mixed** |  |
| `$context` | **mixed** |  |





***

### isEnabled



```php
public isEnabled(): mixed
```












***

### get



```php
public get(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### set



```php
public set(mixed $id, mixed $data): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |
| `$data` | **mixed** |  |





***

### clean



```php
public clean(mixed $admin = false, mixed $group = &#039;&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$admin` | **mixed** |  |
| `$group` | **mixed** |  |





***

### getCurrentGitHash



```php
public static getCurrentGitHash(): mixed
```



* This method is **static**.








***

### deleteDir



```php
private deleteDir(mixed $group): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **mixed** |  |





***


***
> Automatically generated on 2024-08-19
