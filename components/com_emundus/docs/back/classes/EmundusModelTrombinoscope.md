***

# EmundusModelTrombinoscope





* Full name: `\EmundusModelTrombinoscope`
* Parent class: [`JModelLegacy`](./JModelLegacy.md)



## Properties


### default_margin



```php
public $default_margin
```






***

### default_header_height



```php
public $default_header_height
```






***

### pdf_margin_top



```php
public $pdf_margin_top
```






***

### pdf_margin_right



```php
public $pdf_margin_right
```






***

### pdf_margin_left



```php
public $pdf_margin_left
```






***

### pdf_margin_header



```php
public $pdf_margin_header
```






***

### pdf_margin_footer



```php
public $pdf_margin_footer
```






***

## Methods


### __construct



```php
public __construct(): mixed
```












***

### fnums_json_decode



```php
public fnums_json_decode(mixed $string_fnums): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$string_fnums` | **mixed** |  |





***

### set_template



```php
public set_template(mixed $programme_code, mixed $format = &#039;trombi&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$programme_code` | **mixed** |  |
| `$format` | **mixed** |  |





***

### getProgByFnum



```php
public getProgByFnum(mixed $fnum): \Exception|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### generate_pdf



```php
public generate_pdf(mixed $html_value, mixed $format): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$html_value` | **mixed** |  |
| `$format` | **mixed** |  |





***

### selectHTMLLetters



```php
public selectHTMLLetters(): mixed
```












***

### selectLabelSetupAttachments



```php
public selectLabelSetupAttachments(mixed $attachment_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attachment_id` | **mixed** |  |





***


***
> Automatically generated on 2024-08-20
