***

# EmundusHelperEmails

Content Component Query Helper



* Full name: `\EmundusHelperEmails`




## Methods


### createEmailBlock



```php
public createEmailBlock(mixed $params, mixed $users = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | **mixed** |  |
| `$users` | **mixed** |  |





***

### getEmail



```php
public static getEmail(mixed $lbl): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lbl` | **mixed** |  |





***

### getAllEmail



```php
public getAllEmail(mixed $type = 2): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **mixed** |  |





***

### getTemplate



```php
public static getTemplate(): mixed
```



* This method is **static**.








***

### sendGroupEmail



```php
public sendGroupEmail(): mixed
```












***

### sendApplicantEmail



```php
public static sendApplicantEmail(): mixed
```



* This method is **static**.








***

### assertCanSendMailToUser

Assert that emails can be sent to user, by checking user params and email validity

```php
public assertCanSendMailToUser(mixed $user_id = null, mixed $fnum = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |
| `$fnum` | **mixed** |  |





***

### correctEmail

Check given email is not empty, has a valid format, and email dns exists

```php
public correctEmail(mixed $email): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$email` | **mixed** |  |





***

### getLogo



```php
public static getLogo(mixed $only_filename = false, mixed $training = null): string
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$only_filename` | **mixed** |  |
| `$training` | **mixed** |  |





***

### getCustomHeader



```php
public static getCustomHeader(): string
```



* This method is **static**.








***


***
> Automatically generated on 2024-08-20
