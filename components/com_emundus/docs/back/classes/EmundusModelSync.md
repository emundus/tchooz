***

# EmundusModelSync





* Full name: `\EmundusModelSync`
* Parent class: [`JModelList`](./JModelList.md)




## Methods


### __construct



```php
public __construct(mixed $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **mixed** |  |





***

### getConfig



```php
public getConfig(mixed $type): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **mixed** |  |





***

### saveConfig



```php
public saveConfig(mixed $config, mixed $type): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **mixed** |  |
| `$type` | **mixed** |  |





***

### saveParams



```php
public saveParams(mixed $key, mixed $value, mixed $type): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **mixed** |  |
| `$value` | **mixed** |  |
| `$type` | **mixed** |  |





***

### getAspects



```php
public getAspects(): mixed
```












***

### uploadAspectFile



```php
public uploadAspectFile(mixed $file): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **mixed** |  |





***

### updateAspectListFromFile



```php
public updateAspectListFromFile(mixed $file): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$file` | **mixed** |  |





***

### getDocuments



```php
public getDocuments(): mixed
```












***

### getEmundusTags



```php
public getEmundusTags(): mixed
```












***

### updateDocumentSync



```php
public updateDocumentSync(mixed $did, mixed $sync): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |
| `$sync` | **mixed** |  |





***

### updateDocumentSyncMethod



```php
public updateDocumentSyncMethod(mixed $did, mixed $sync_method): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$did` | **mixed** |  |
| `$sync_method` | **mixed** |  |





***

### isSyncModuleActive



```php
public isSyncModuleActive(): mixed
```












***

### getSyncType



```php
public getSyncType(mixed $upload_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$upload_id` | **mixed** |  |





***

### checkIfTypeIsActive



```php
public checkIfTypeIsActive(mixed $type): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **mixed** |  |





***

### getUploadSyncState



```php
public getUploadSyncState(mixed $upload_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$upload_id` | **mixed** |  |





***

### synchronizeAttachments



```php
public synchronizeAttachments(mixed $upload_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$upload_ids` | **mixed** |  |





***

### deleteAttachments



```php
public deleteAttachments(mixed $upload_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$upload_ids` | **mixed** |  |





***

### checkAttachmentsExists



```php
public checkAttachmentsExists(mixed $upload_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$upload_ids` | **mixed** |  |





***

### getUploadIdsByType



```php
private getUploadIdsByType(mixed $upload_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$upload_ids` | **mixed** |  |





***

### synchronizeAttachmentsByType



```php
private synchronizeAttachmentsByType(mixed $type, mixed $upload_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **mixed** |  |
| `$upload_ids` | **mixed** |  |





***

### deleteAttachmentsByType



```php
private deleteAttachmentsByType(mixed $type, mixed $upload_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **mixed** |  |
| `$upload_ids` | **mixed** |  |





***

### checkAttachmentsExistsByType



```php
private checkAttachmentsExistsByType(mixed $type, mixed $upload_ids): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **mixed** |  |
| `$upload_ids` | **mixed** |  |





***

### getAttachmentAspectsConfig



```php
public getAttachmentAspectsConfig(mixed $attachment_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attachment_id` | **mixed** |  |





***

### saveAttachmentAspectsConfig



```php
public saveAttachmentAspectsConfig(mixed $attachment_id, mixed $aspectsConfig): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attachment_id` | **mixed** |  |
| `$aspectsConfig` | **mixed** |  |





***

### getNodeId



```php
public getNodeId(mixed $upload_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$upload_id` | **mixed** |  |





***


***
> Automatically generated on 2024-08-20
