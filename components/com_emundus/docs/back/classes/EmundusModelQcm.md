***

# EmundusModelQcm





* Full name: `\EmundusModelQcm`
* Parent class: [`JModelList`](./JModelList.md)




## Methods


### __construct

Constructor

```php
public __construct(mixed $model = &#039;qcm&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model` | **mixed** |  |





***

### getQcm



```php
public getQcm(mixed $formid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$formid` | **mixed** |  |





***

### getQcmApplicant



```php
public getQcmApplicant(mixed $fnum, mixed $qcm): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$qcm` | **mixed** |  |





***

### initQcmApplicant



```php
public initQcmApplicant(mixed $fnum, mixed $idqcm): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$idqcm` | **mixed** |  |





***

### getQuestions



```php
public getQuestions(mixed $question_ids, mixed $with_answers = false): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$question_ids` | **mixed** |  |
| `$with_answers` | **mixed** |  |





***

### saveAnswer



```php
public saveAnswer(mixed $question, mixed $answers, mixed $current_user, mixed $formid, mixed $module): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$question` | **mixed** |  |
| `$answers` | **mixed** |  |
| `$current_user` | **mixed** |  |
| `$formid` | **mixed** |  |
| `$module` | **mixed** |  |





***

### checkPoints



```php
public checkPoints(mixed $answers, mixed $module, mixed $good_answers): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$answers` | **mixed** |  |
| `$module` | **mixed** |  |
| `$good_answers` | **mixed** |  |





***

### updatePending



```php
public updatePending(mixed $pending, mixed $current_user, mixed $formid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$pending` | **mixed** |  |
| `$current_user` | **mixed** |  |
| `$formid` | **mixed** |  |





***

### getIntro



```php
public getIntro(mixed $module): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$module` | **mixed** |  |





***


***
> Automatically generated on 2024-08-02
