***

# EmundusControllerTrombinoscope

Class EmundusControllerTrombinoscope



* Full name: `\EmundusControllerTrombinoscope`
* Parent class: [`EmundusController`](./EmundusController.md)



## Properties


### app



```php
protected $app
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

### fnums_json_decode



```php
public fnums_json_decode(mixed $string_fnums): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$string_fnums` | **mixed** |  |





***

### generate_preview

Génération de code HTML pour l'affichage de la 1ère page de prévisualisation

```php
public generate_preview(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### generate_data_for_pdf

Génération du code HTML qui sera envoyé soit pour cosntruire le pdf, soit pour afficher la prévisualisation

```php
public generate_data_for_pdf(mixed $fnums, mixed $gridL, mixed $gridH, mixed $margin, mixed $template, mixed $templHeader, mixed $templFooter, mixed $generate, bool $preview = false, bool $checkHeader = false, mixed $border = null, mixed $headerHeight = null): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$gridL` | **mixed** |  |
| `$gridH` | **mixed** |  |
| `$margin` | **mixed** |  |
| `$template` | **mixed** |  |
| `$templHeader` | **mixed** |  |
| `$templFooter` | **mixed** |  |
| `$generate` | **mixed** |  |
| `$preview` | **bool** |  |
| `$checkHeader` | **bool** |  |
| `$border` | **mixed** |  |
| `$headerHeight` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### generate_pdf



```php
public generate_pdf(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***


***
> Automatically generated on 2024-08-20
