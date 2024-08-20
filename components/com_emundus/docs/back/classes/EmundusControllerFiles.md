***

# EmundusControllerFiles

Class EmundusControllerFiles



* Full name: `\EmundusControllerFiles`
* Parent class: [`JControllerLegacy`](./JControllerLegacy.md)



## Properties


### app



```php
protected $app
```






***

### _user



```php
private $_user
```






***

### _db



```php
private $_db
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

### display

Method to display a view.

```php
public display(bool $cachable = false, bool $urlparams = false): \DisplayController
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cachable` | **bool** | If true, the view output will be cached. |
| `$urlparams` | **bool** | An array of safe URL parameters and their variable types.<br />@see        \Joomla\CMS\Filter\InputFilter::clean() for valid values. |


**Return Value:**

This object to support chaining.




***

### data_to_img



```php
public data_to_img(mixed $match): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$match` | **mixed** |  |





***

### applicantemail



```php
public applicantemail(): mixed
```












***

### groupmail



```php
public groupmail(): mixed
```












***

### clear



```php
public clear(): mixed
```












***

### applyfilters



```php
public applyfilters(): mixed
```












***

### setfilters



```php
public setfilters(): mixed
```












***

### loadfilters



```php
public loadfilters(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### order



```php
public order(): mixed
```












***

### setlimit



```php
public setlimit(): mixed
```












***

### savefilters



```php
public savefilters(): mixed
```












***

### newsavefilters



```php
public newsavefilters(): mixed
```












***

### getsavedfilters



```php
public getsavedfilters(): mixed
```












***

### updatefilter



```php
public updatefilter(): mixed
```












***

### deletefilters



```php
public deletefilters(): mixed
```












***

### setlimitstart



```php
public setlimitstart(): mixed
```












***

### getadvfilters



```php
public getadvfilters(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### getbox



```php
public getbox(): mixed
```











**Throws:**

- [`Exception`](./Exception.md)



***

### deladvfilter



```php
public deladvfilter(): mixed
```












***

### addcomment

Add a comment on a file.

```php
public addcomment(): mixed
```












***

### gettags



```php
public gettags(): mixed
```












***

### tagfile

Add a tag to an application.

```php
public tagfile(): mixed
```












***

### deletetags



```php
public deletetags(): mixed
```












***

### share



```php
public share(): mixed
```












***

### getstate



```php
public getstate(): mixed
```












***

### getpublish



```php
public getpublish(): mixed
```












***

### getExistEmailTrigger



```php
public getExistEmailTrigger(): mixed
```












***

### updatestate



```php
public updatestate(): mixed
```












***

### updatepublish



```php
public updatepublish(): mixed
```












***

### unlinkevaluators



```php
public unlinkevaluators(): mixed
```












***

### getfnuminfos



```php
public getfnuminfos(): mixed
```












***

### deletefile



```php
public deletefile(): mixed
```












***

### removefile



```php
public removefile(): mixed
```












***

### getformelem



```php
public getformelem(): mixed
```












***

### zip



```php
public zip(): mixed
```












***

### return_bytes



```php
public return_bytes(mixed $val): int|string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$val` | **mixed** |  |





***

### sortArrayByArray



```php
public sortArrayByArray(mixed $array, mixed $orderArray): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$array` | **mixed** |  |
| `$orderArray` | **mixed** |  |





***

### sortObjectByArray



```php
public sortObjectByArray(mixed $object, mixed $orderArray): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$object` | **mixed** |  |
| `$orderArray` | **mixed** |  |





***

### create_file_csv

Create temp CSV file for XLS extraction

```php
public create_file_csv(): string
```









**Return Value:**

json




***

### create_file_pdf

Create temp PDF file for PDF extraction

```php
public create_file_pdf(): string
```









**Return Value:**

json




***

### getfnums_csv



```php
public getfnums_csv(): mixed
```












***

### getfnums



```php
public getfnums(): mixed
```












***

### getallfnums



```php
public getallfnums(): mixed
```












***

### getcolumn



```php
public getcolumn(mixed $elts): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elts` | **mixed** |  |





***

### generate_array

Add lines to temp CSV file

```php
public generate_array(): string
```









**Return Value:**

json



**Throws:**

- [`Exception`](./Exception.md)



***

### getformslist



```php
public getformslist(): mixed
```












***

### getdoctype



```php
public getdoctype(): mixed
```












***

### generate_pdf

Add lines to temp PDF file

```php
public generate_pdf(): string
```









**Return Value:**

json



**Throws:**

- [`Exception`](./Exception.md)



***

### generate_customized_pdf



```php
public generate_customized_pdf(): mixed
```












***

### export_letter



```php
public export_letter(): mixed
```












***

### export_xls_from_csv



```php
public export_xls_from_csv(): mixed
```












***

### export_xls



```php
public export_xls(mixed $fnums, mixed $objs, mixed $element_id, int $methode): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$objs` | **mixed** |  |
| `$element_id` | **mixed** |  |
| `$methode` | **int** |  |




**Throws:**

- [`Exception`](./PhpOffice/PhpSpreadsheet/Exception.md)

- [`Exception`](./PhpOffice/PhpSpreadsheet/Writer/Exception.md)



***

### get_mime_type



```php
public get_mime_type(mixed $filename, string $mimePath = &#039;../etc&#039;): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filename` | **mixed** |  |
| `$mimePath` | **string** |  |





***

### download



```php
public download(): mixed
```












***

### export_zip

Create a zip file containing all documents attached to application fil number

```php
public export_zip(array $fnums, mixed $form_post = 1, mixed $attachment = 1, mixed $assessment = 1, mixed $decision = 1, mixed $admission = 1, mixed $form_ids = null, mixed $attachids = null, mixed $options = null, mixed $acl_override = false): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **array** |  |
| `$form_post` | **mixed** |  |
| `$attachment` | **mixed** |  |
| `$assessment` | **mixed** |  |
| `$decision` | **mixed** |  |
| `$admission` | **mixed** |  |
| `$form_ids` | **mixed** |  |
| `$attachids` | **mixed** |  |
| `$options` | **mixed** |  |
| `$acl_override` | **mixed** |  |





***

### export_zip_pcl



```php
public export_zip_pcl(mixed $fnums): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |





***

### getformid



```php
public getformid(): mixed
```












***

### getdecisionformid



```php
public getdecisionformid(): mixed
```












***

### exportzipdoc



```php
public exportzipdoc(): mixed
```












***

### getPDFProgrammes



```php
public getPDFProgrammes(): mixed
```












***

### getPDFCampaigns



```php
public getPDFCampaigns(): mixed
```












***

### getProgrammes



```php
public getProgrammes(): mixed
```












***

### getProgramCampaigns



```php
public getProgramCampaigns(): mixed
```












***

### saveExcelFilter



```php
public saveExcelFilter(): mixed
```












***

### savePdfFilter



```php
public savePdfFilter(): mixed
```












***

### deletePdfFilter



```php
public deletePdfFilter(): mixed
```












***

### getExportExcelFilter



```php
public getExportExcelFilter(): mixed
```












***

### getAllExportPdfFilter



```php
public getAllExportPdfFilter(): mixed
```












***

### getExportPdfFilterById



```php
public getExportPdfFilterById(): mixed
```












***

### getExportExcelFilterById



```php
public getExportExcelFilterById(): mixed
```












***

### getAllLetters



```php
public getAllLetters(): mixed
```












***

### getexcelletter



```php
public getexcelletter(): mixed
```












***

### checkforms



```php
public checkforms(): mixed
```












***

### getproductpdf

Generates or (if it exists already) loads the PDF for a certain GesCOF product.

```php
public getproductpdf(): mixed
```












***

### getValueByFabrikElts



```php
public getValueByFabrikElts(mixed $fabrikElts, mixed $fnumsArray): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fabrikElts` | **mixed** |  |
| `$fnumsArray` | **mixed** |  |





***

### exportfile



```php
public exportfile(): mixed
```












***

### getfabrikdatabyelements



```php
public getfabrikdatabyelements(): mixed
```












***

### getselectedelements



```php
public getselectedelements(): mixed
```












***

### generateletter



```php
public generateletter(): mixed
```












***

### getfabrikvaluebyid



```php
public getfabrikvaluebyid(): mixed
```












***

### getactionsonfnum



```php
public getactionsonfnum(): mixed
```












***

### getattachmentcategories



```php
public getattachmentcategories(): mixed
```












***

### getattachmentprogress



```php
public getattachmentprogress(): mixed
```












***

### isdataanonymized



```php
public isdataanonymized(): mixed
```












***

### exportLogs



```php
public exportLogs(): mixed
```












***

### checkIfSomeoneElseIsEditing



```php
public checkIfSomeoneElseIsEditing(): mixed
```












***

### getalllogactions



```php
public getalllogactions(): mixed
```












***

### getuserslogbyfnum



```php
public getuserslogbyfnum(): mixed
```












***

### checkmenufilterparams



```php
public checkmenufilterparams(): mixed
```












***

### getFiltersAvailable



```php
public getFiltersAvailable(): mixed
```












***

### setFiltersValuesAvailability



```php
public setFiltersValuesAvailability(): mixed
```












***

### getfiltervalues



```php
public getfiltervalues(): mixed
```












***

### countfilesbeforeaction



```php
public countfilesbeforeaction(): mixed
```












***


***
> Automatically generated on 2024-08-20
