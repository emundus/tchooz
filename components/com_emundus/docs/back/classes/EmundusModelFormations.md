***

# EmundusModelFormations





* Full name: `\EmundusModelFormations`
* Parent class: [`JModelLegacy`](./JModelLegacy.md)




## Methods


### __construct



```php
public __construct(array $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** |  |





***

### checkHR



```php
public checkHR(mixed $cid, null $user = null, int $profile = 1002): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cid` | **mixed** |  |
| `$user` | **null** |  |
| `$profile` | **int** |  |





***

### deleteCompany



```php
public deleteCompany(mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |





***

### deleteAssociate

Deletes a user from a company.

```php
public deleteAssociate(mixed $user_id, mixed $cid, mixed $hr_user): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** | int the user to deassociate from company. |
| `$cid` | **mixed** | int company id |
| `$hr_user` | **mixed** | int user id of the HR |





***

### checkHRUser



```php
public checkHRUser(mixed $user_hr, null $user_intern = null, int $profile = 1002): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_hr` | **mixed** | Int The user who is supposedly a DRH |
| `$user_intern` | **null** | Int The user who is supposedly an intern._ |
| `$profile` | **int** | Int The profile which determines if a user is DRH. |





***

### checkCompanyUser



```php
public checkCompanyUser(mixed $user, mixed $company): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** | Int The user who we are checking. |
| `$company` | **mixed** | Int The company the user may be in._ |





***

### getApplicantsInSessionForDRH

Gets all applicants to a session in which the user is a DRH of the company they are signed up as.

```php
public getApplicantsInSessionForDRH(mixed $campaign, null $user = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign` | **mixed** |  |
| `$user` | **null** |  |





***

### getCompaniesDRH



```php
public getCompaniesDRH(null $user_id = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **null** |  |





***

### getUserFormationByRH

this function returns all the formations the user is signed up to by the DRH

```php
public getUserFormationByRH(null $user_id = null, mixed $user_rh = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **null** |  |
| `$user_rh` | **mixed** |  |





***


***
> Automatically generated on 2024-08-20
