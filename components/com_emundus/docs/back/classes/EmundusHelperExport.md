***

# EmundusHelperExport

Content Component Query Helper



* Full name: `\EmundusHelperExport`




## Methods


### buildFormPDF



```php
public static buildFormPDF(mixed $fnumInfos, mixed $sid, mixed $fnum, int $form_post, null $form_ids = null, null $options = null, null $application_form_order = null, null $elements = null, mixed $attachments = true): string
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnumInfos` | **mixed** |  |
| `$sid` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$form_post` | **int** |  |
| `$form_ids` | **null** |  |
| `$options` | **null** |  |
| `$application_form_order` | **null** |  |
| `$elements` | **null** |  |
| `$attachments` | **mixed** |  |






***

### buildCustomizedPDF



```php
public buildCustomizedPDF(mixed $fnumInfos, mixed $forms, mixed $elements, mixed $options = null, mixed $application_form_order = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnumInfos` | **mixed** |  |
| `$forms` | **mixed** |  |
| `$elements` | **mixed** |  |
| `$options` | **mixed** |  |
| `$application_form_order` | **mixed** |  |






***

### buildHeaderPDF



```php
public static buildHeaderPDF(mixed $fnumInfos, mixed $sid, mixed $fnum, mixed $options = null): string
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnumInfos` | **mixed** |  |
| `$sid` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$options` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)




***

### pdftest_is_encrypted

Check whether pdf is encrypted or password protected.

```php
public static pdftest_is_encrypted(string $file): bool
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **string** |  |






***

### get_pdf_prop



```php
public static get_pdf_prop(mixed $file): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **mixed** |  |






***

### isEncrypted



```php
public static isEncrypted(mixed $file): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **mixed** |  |






***

### getAttachmentPDF



```php
public static getAttachmentPDF(mixed& $exports, mixed& $tmpArray, mixed $files, mixed $sid): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$exports` | **mixed** |  |
| `$tmpArray` | **mixed** |  |
| `$files` | **mixed** |  |
| `$sid` | **mixed** |  |






***

### getEvalPDF



```php
public static getEvalPDF(mixed $fnum, mixed $options = null): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$options` | **mixed** |  |






***

### getDecisionPDF



```php
public static getDecisionPDF(mixed $fnum, mixed $options = null): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$options` | **mixed** |  |






***

### getAdmissionPDF



```php
public static getAdmissionPDF(mixed $fnum, mixed $options = null): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$options` | **mixed** |  |






***

### makePDF



```php
public static makePDF(mixed $fileName, mixed $ext, mixed $aid, mixed $i): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fileName` | **mixed** |  |
| `$ext` | **mixed** |  |
| `$aid` | **mixed** |  |
| `$i` | **mixed** |  |






***

### getArticle

Gets the content of a Joomla article.

```php
public getArticle(mixed $id): mixed
```

Used for defining articles as PDF templates.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***


***
> Last updated on 20/08/2024
