***

# EmundusHelperEvents

Emundus Component Events Helper



* Full name: `\EmundusHelperEvents`




## Methods


### onBeforeLoad



```php
public onBeforeLoad(mixed $params): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** | <br />Parameters available : $params[&#039;formModel&#039;] |




**Throws:**

- [`Exception`](./Exception.md)



***

### onBeforeStore



```php
public onBeforeStore(mixed $params): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** | <br />Parameters available : $params[&#039;formModel&#039;] |





***

### onAfterProcess



```php
public onAfterProcess(mixed $params): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** | <br />Parameters available : $params[&#039;formModel&#039;] |





***

### getFormsIdFromTableNames



```php
private getFormsIdFromTableNames(mixed $table_names): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$table_names` | **mixed** |  |





***

### isApplicationSent



```php
public isApplicationSent(mixed $params): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### isApplicationCompleted



```php
public isApplicationCompleted(mixed $params): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### redirect



```php
public redirect(mixed $params): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### confirmpost



```php
public confirmpost(mixed $params): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### onAfterProgramCreate



```php
public onAfterProgramCreate(mixed $params): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |





***

### logUpdateForms



```php
private logUpdateForms(mixed $params, mixed $forms_to_log = []): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |
| `$forms_to_log` | **mixed** |  |





***

### getFormElements



```php
private getFormElements(mixed $form_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_id` | **mixed** |  |





***

### logUpdateState



```php
private logUpdateState(mixed $old_status, mixed $new_status, mixed $user_id, mixed $applicant_id, mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$old_status` | **mixed** |  |
| `$new_status` | **mixed** |  |
| `$user_id` | **mixed** |  |
| `$applicant_id` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### applicationUpdating



```php
private applicationUpdating(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### checkQcmCompleted



```php
private checkQcmCompleted(mixed $fnum, mixed $forms_ids, mixed $items_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$forms_ids` | **mixed** |  |
| `$items_ids` | **mixed** |  |





***


***
> Automatically generated on 2024-08-02
