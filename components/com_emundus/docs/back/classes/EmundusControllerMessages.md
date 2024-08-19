***

# EmundusControllerMessages

eMundus Component Controller



* Full name: `\EmundusControllerMessages`
* Parent class: [`JControllerLegacy`](./JControllerLegacy.md)



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

### gettemplate

Get all of the information for an email template.

```php
public gettemplate(): mixed
```












***

### setcategory

Get email templates by category.

```php
public setcategory(): mixed
```












***

### uploadfiletosend

Upload a file from computer to be attached to the emails sent.

```php
public uploadfiletosend(): mixed
```












***

### getcandidatefilenames

Gets the names of the candidate files.

```php
public getcandidatefilenames(): mixed
```












***

### getletterfilenames

Gets the names of the letter files.

```php
public getletterfilenames(): mixed
```












***

### previewemail

Builds an HTML preview of the message to be sent alongside a recap of other information.

```php
public previewemail(): mixed
```












***

### applicantemail

Send the email defined in the dialog.

```php
public applicantemail(): mixed
```












***

### useremail

Send an email to a user, regardless of fnum.

```php
public useremail(): mixed
```












***

### sendEmail

The generic function used for sending emails.

```php
public sendEmail(mixed $fnum, mixed $email_id, null $post = null, array $attachments = [], bool $bcc = false, mixed $sender_id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$email_id` | **mixed** |  |
| `$post` | **null** |  |
| `$attachments` | **array** |  |
| `$bcc` | **bool** |  |
| `$sender_id` | **mixed** |  |





***

### sendEmailNoFnum

The generic function used for sending emails outside of emundus.

```php
public sendEmailNoFnum(string $email_address, mixed $email, null $post = null, null $user_id = null, array $attachments = [], array $fnum = null, mixed $log_email = true): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$email_address` | **string** |  |
| `$email` | **mixed** | If a numeric ID is provided, use that, if a string is provided, get the email with that label. |
| `$post` | **null** |  |
| `$user_id` | **null** |  |
| `$attachments` | **array** |  |
| `$fnum` | **array** | If we need to replace fabrik tags |
| `$log_email` | **mixed** |  |





***

### sendMessage

send message in chat

```php
public sendMessage(): mixed
```












***

### sendChatroomMessage

send message in chatroom

```php
public sendChatroomMessage(): mixed
```












***

### updatemessages

update message list

```php
public updatemessages(): mixed
```












***

### getTypeAttachment



```php
public getTypeAttachment(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getTypeLetters



```php
public getTypeLetters(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### getrecapbyfnum



```php
public getrecapbyfnum(): mixed
```












***

### getmessagerecapbyfnum



```php
public getmessagerecapbyfnum(): mixed
```












***

### sendemailtocandidat



```php
public sendemailtocandidat(): mixed
```












***

### addtagsbyfnum



```php
public addtagsbyfnum(): mixed
```












***

### getalldocumentsletters



```php
public getalldocumentsletters(): mixed
```












***

### getattachmentsbyprofiles



```php
public getattachmentsbyprofiles(): mixed
```












***

### getallattachments



```php
public getallattachments(): mixed
```












***

### addtagsbyfnums



```php
public addtagsbyfnums(): mixed
```












***

### getAllCategories



```php
public getAllCategories(): mixed
```












***

### getAllMessages



```php
public getAllMessages(): mixed
```












***


***
> Automatically generated on 2024-08-19
