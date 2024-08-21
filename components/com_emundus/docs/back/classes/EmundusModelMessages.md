***

# EmundusModelMessages

Model class for handling lists of items.



* Full name: `\EmundusModelMessages`
* Parent class: [`\Joomla\CMS\MVC\Model\ListModel`](./Joomla/CMS/MVC/Model/ListModel.md)



## Properties


### user



```php
private $user
```






***

### db



```php
private $db
```






***

## Methods


### __construct

Constructor

```php
public __construct(array $config = array()): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** |  |






***

### getAllMessages

Gets all published message templates of a certain type.

```php
public getAllMessages(int $type = 2): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **int** | The type of email to get, type 2 is by default (Templates). |


**Return Value:**

False if the query fails and nothing can be loaded. An array of objects describing the messages. (sender, subject, body, etc..)





***

### getAllCategories

Gets all published message categories of a certain type.

```php
public getAllCategories(int $type = 2): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **int** | The type of category to get, type 2 is by default (Templates). |


**Return Value:**

False if the query fails and nothing can be loaded. An array of the categories.





***

### getAttachments

Gets all published attachments unless a filter is active.

```php
public getAttachments(): bool|array
```









**Return Value:**

False if the query fails and nothing can be loaded. or An array of objects describing attachments.





***

### getLetters

Gets all published letters unless a filter is active.

```php
public getLetters(): bool
```









**Return Value:**

False if the query fails and nothing can be loaded.





***

### getEmail

Gets a message template.

```php
public getEmail(mixed $id): object
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |


**Return Value:**

The email we seek, false if none is found.





***

### getEmailsByCategory

Gets the email templates by using the category.

```php
public getEmailsByCategory(string $category): object|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$category` | **string** | The name of the category. |


**Return Value:**

The list of templates corresponding.





***

### get_upload

Gets the a file from the setup_attachment table linked to an fnum.

```php
public get_upload(string $fnum, int $attachment_id): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **string** | the fnum used for getting the attachment. |
| `$attachment_id` | **int** | the ID of the attachment used in setup_attachment |






***

### get_filename

Gets the a file type label from the setup_attachment table .

```php
public get_filename(int $attachment_id): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attachment_id` | **int** | the ID of the attachment used in setup_attachment |






***

### get_letter

Gets the a file from the setup_letters table linked to an fnum.

```php
public get_letter(int $letter_id): object|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$letter_id` | **int** | the ID of the letter used in setup_letters |


**Return Value:**

The letter object as found in the DB, also contains the status and training.





***

### getCandidateFileNames

Gets the names of candidate files.

```php
public getCandidateFileNames(mixed $ids): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |


**Return Value:**

A list of objects containing the names and ids of the candidate files.





***

### getLetterFileNames

Gets the names of candidate files.

```php
public getLetterFileNames(mixed $ids): array|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ids` | **mixed** |  |


**Return Value:**

A list of objects containing the names and ids of the candidate files.





***

### generateLetterDoc

Generates a DOC file for setup_letters

```php
public generateLetterDoc(object $letter, string $fnum): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$letter` | **object** | The template for the doc to create. |
| `$fnum` | **string** | The fnum used to generate the tags. |


**Return Value:**

The path to the saved file.



**Throws:**

- [`CopyFileException`](./PhpOffice/PhpWord/Exception/CopyFileException.md)

- [`CreateTemporaryFileException`](./PhpOffice/PhpWord/Exception/CreateTemporaryFileException.md)

- [`Exception`](./PhpOffice/PhpWord/Exception/Exception.md)




***

### getContacts

get all contacts the current user has received or sent a message as well as their latest message.

```php
public getContacts(null $user = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **null** |  |






***

### updateMessages

gets all messages received after the message $lastID

```php
public updateMessages(mixed $lastId, null $user = null, null $other_user = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lastId` | **mixed** |  |
| `$user` | **null** |  |
| `$other_user` | **null** |  |






***

### getUnread

Get number of unread messages between two users (messages with folder_id 2)

```php
public getUnread(mixed $sender, null $receiver = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sender` | **mixed** |  |
| `$receiver` | **null** |  |






***

### loadMessages

load messages between two users ( messages with folder_id 2 )

```php
public loadMessages(mixed $user1, null $user2 = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user1` | **mixed** |  |
| `$user2` | **null** |  |






***

### sendMessage

sends message folder_id=2 from user_from to user_to and sets stats to 1

```php
public sendMessage(mixed $receiver, mixed $message, null $user = null, bool $system_message = false): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$receiver` | **mixed** |  |
| `$message` | **mixed** |  |
| `$user` | **null** |  |
| `$system_message` | **bool** |  |






***

### deleteSystemMessages



```php
public deleteSystemMessages(mixed $user1, mixed $user2): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user1` | **mixed** |  |
| `$user2` | **mixed** |  |






***

### createChatroom



```php
public createChatroom(null $fnum = null, null $id = null): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **null** |  |
| `$id` | **null** |  |






***

### joinChatroom



```php
public joinChatroom(int $chatroom, mixed $users): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom` | **int** | Chatroom id, if the room doesn&#039;t exist, it will be created. |
| `$users` | **mixed** | Function is called as such : joinChatroom(4, $user1, $user2, $user3); |






***

### sendChatroomMessage



```php
public sendChatroomMessage(int $chatroom, string $message): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom` | **int** | PAGE column in jos_messages is used to indicate that it&#039;s |
| `$message` | **string** |  |






***

### getChatroomMessages



```php
public getChatroomMessages(int $chatroom): array|bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom` | **int** |  |






***

### updateChatroomMessages

gets all messages received after the message $lastID

```php
public updateChatroomMessages(mixed $lastId, int $chatroom): bool|mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$lastId` | **mixed** |  |
| `$chatroom` | **int** |  |






***

### getChatroom



```php
public getChatroom(mixed $id): bool|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **mixed** |  |






***

### getChatroomUsersId



```php
public getChatroomUsersId(int $chatroom_id): bool|mixed|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom_id` | **int** |  |






***

### getChatroomByUsers



```php
public getChatroomByUsers(mixed $users): bool|int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$users` | **mixed** |  |






***

### chatRoomExists



```php
private chatRoomExists(mixed $chatroom): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$chatroom` | **mixed** |  |






***

### getMessageRecapByFnum



```php
public getMessageRecapByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getTagsByEmail



```php
public getTagsByEmail(mixed $eid): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$eid` | **mixed** |  |






***

### addTagsByFnum



```php
public addTagsByFnum(mixed $fnum, mixed $tmpl): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$tmpl` | **mixed** |  |






***

### getActionByFnum



```php
public getActionByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |






***

### getAllDocumentsLetters



```php
public getAllDocumentsLetters(): mixed
```













***

### getAttachmentsByProfiles



```php
public getAttachmentsByProfiles(mixed $fnums = []): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |






***

### getAllAttachments



```php
public getAllAttachments(): mixed
```













***

### addTagsByFnums



```php
public addTagsByFnums(mixed $fnums, mixed $tmpl): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnums` | **mixed** |  |
| `$tmpl` | **mixed** |  |






***

### deleteMessagesBeforeADate



```php
public deleteMessagesBeforeADate(mixed $date): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | **mixed** | DateTime  Date to delete messages before |






***

### exportMessagesBeforeADate



```php
public exportMessagesBeforeADate(mixed $date): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | **mixed** | DateTime  Date to export messages before |






***


## Inherited methods


### _createFileName

Create the filename for a resource

```php
protected static _createFileName(string $type, array $parts = []): string
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The resource type to create the filename for. |
| `$parts` | **array** | An associative array of filename information. |


**Return Value:**

The filename





***

### getInstance

Returns a Model object, always creating it

```php
public static getInstance(string $type, string $prefix = &#039;&#039;, array $config = []): self|bool
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The model type to instantiate |
| `$prefix` | **string** | Prefix for the model class name. Optional. |
| `$config` | **array** | Configuration array for model. Optional. |


**Return Value:**

A \JModelLegacy instance or false on failure





***

### addTablePath

Adds to the stack of model table paths in LIFO order.

```php
public static addTablePath(mixed $path): void
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** | The directory as a string or directories as an array to add. |






***

### createModelFromComponent

Returns a Model object by loading the component from the prefix.

```php
private static createModelFromComponent(string $type, string $prefix = &#039;&#039;, array $config = []): \Joomla\CMS\MVC\Model\ModelInterface|null
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The model type to instantiate |
| `$prefix` | **string** | Prefix for the model class name. Optional. |
| `$config` | **array** | Configuration array for model. Optional. |


**Return Value:**

A ModelInterface instance or null on failure





***

### getState

Method to get state variables.

```php
public getState(string $property = null, mixed $default = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$property` | **string** | Optional parameter name |
| `$default` | **mixed** | Optional default value |


**Return Value:**

The property where specified, the state object where omitted





***

### setState

Method to set state variables.

```php
public setState(string $property, mixed $value = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$property` | **string** | The name of the property |
| `$value` | **mixed** | The value of the property to set or null |


**Return Value:**

The previous value of the property or null if not set





***

### populateState

Method to auto-populate the model state.

```php
protected populateState(string $ordering = null, string $direction = null): void
```

This method should only be called once per instantiation and is designed
to be called on the first call to the getState() method unless the model
configuration flag to ignore the request is set.

Note. Calling getState in this method will result in recursion.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ordering` | **string** | An optional ordering field. |
| `$direction` | **string** | An optional direction (asc&amp;#124;desc). |






***

### __construct

Constructor

```php
public __construct(array $config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **array** | An array of configuration options (name, state, dbo, table_path, ignore_request). |
| `$factory` | **?\Joomla\CMS\MVC\Factory\MVCFactoryInterface** | The factory. |




**Throws:**

- [`Exception`](./Exception.md)




***

### addIncludePath

Add a directory where \JModelLegacy should search for models. You may
either pass a string or an array of directories.

```php
public static addIncludePath(mixed $path = &#039;&#039;, string $prefix = &#039;&#039;): array
```



* This method is **static**.


* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** | A path or array[sting] of paths to search. |
| `$prefix` | **string** | A prefix for models. |


**Return Value:**

An array with directory elements. If prefix is equal to '', all directories are returned.





**See Also:**

*  - LegacyModelLoaderTrait::getInstance(...)

***

### getName

Method to get the model name

```php
public getName(): string
```

The model name. By default parsed using the classname or it can be set
by passing a $config['name'] in the class constructor







**Return Value:**

The name of the model



**Throws:**

- [`Exception`](./Exception.md)




***

### getMVCFactory

Returns the MVC factory.

```php
protected getMVCFactory(): \Joomla\CMS\MVC\Factory\MVCFactoryInterface
```











**Throws:**

- [`UnexpectedValueException`](./UnexpectedValueException.md)




***

### setMVCFactory

Set the MVC factory.

```php
public setMVCFactory(\Joomla\CMS\MVC\Factory\MVCFactoryInterface $mvcFactory): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mvcFactory` | **\Joomla\CMS\MVC\Factory\MVCFactoryInterface** | The MVC factory |






***

### _getList

Gets an array of objects from the results of database query.

```php
protected _getList(\Joomla\Database\DatabaseQuery|string $query, int $limitstart, int $limit): object[]
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query` | **\Joomla\Database\DatabaseQuery&#124;string** | The query. |
| `$limitstart` | **int** | Offset. |
| `$limit` | **int** | The number of records. |


**Return Value:**

An array of results.



**Throws:**

- [`RuntimeException`](./RuntimeException.md)




***

### _getListCount

Returns a record count for the query.

```php
protected _getListCount(\Joomla\Database\DatabaseQuery|string $query): int
```

Note: Current implementation of this method assumes that getListQuery() returns a set of unique rows,
thus it uses SELECT COUNT(*) to count the rows. In cases that getListQuery() uses DISTINCT
then either this method must be overridden by a custom implementation at the derived Model Class
or a GROUP BY clause should be used to make the set unique.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query` | **\Joomla\Database\DatabaseQuery&#124;string** | The query. |


**Return Value:**

Number of rows for query.





***

### _createTable

Method to load and return a table object.

```php
protected _createTable(string $name, string $prefix = &#039;Table&#039;, array $config = []): \Joomla\CMS\Table\Table|bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the view |
| `$prefix` | **string** | The class prefix. Optional. |
| `$config` | **array** | Configuration settings to pass to Table::getInstance |


**Return Value:**

Table object or boolean false if failed





**See Also:**

* \Joomla\CMS\Table\Table::getInstance() - 

***

### getTable

Method to get a table object, load it if necessary.

```php
public getTable(string $name = &#039;&#039;, string $prefix = &#039;&#039;, array $options = []): \Joomla\CMS\Table\Table
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The table name. Optional. |
| `$prefix` | **string** | The class prefix. Optional. |
| `$options` | **array** | Configuration array for model. Optional. |


**Return Value:**

A Table object



**Throws:**

- [`Exception`](./Exception.md)




***

### isCheckedOut

Method to check if the given record is checked out by the current user

```php
public isCheckedOut(\stdClass $item): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$item` | **\stdClass** | The record to check |






***

### cleanCache

Clean the cache

```php
protected cleanCache(string $group = null): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$group` | **string** | The cache group |






***

### bootComponent

Boots the component with the given name.

```php
protected bootComponent(string $component): \Joomla\CMS\Extension\ComponentInterface
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$component` | **string** | The component name, eg. com_content. |


**Return Value:**

The service container





***

### getDispatcher

Get the event dispatcher.

```php
public getDispatcher(): \Joomla\Event\DispatcherInterface
```

The override was made to keep a backward compatibility for legacy component.
TODO: Remove the override in 6.0









**Throws:**
<p>May be thrown if the dispatcher has not been set.</p>

- [`UnexpectedValueException`](./UnexpectedValueException.md)




***

### dispatchEvent

Dispatches the given event on the internal dispatcher, does a fallback to the global one.

```php
protected dispatchEvent(\Joomla\Event\EventInterface $event): void
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$event` | **\Joomla\Event\EventInterface** | The event |






***

### getDbo

Get the database driver.

```php
public getDbo(): \Joomla\Database\DatabaseInterface
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.




**Return Value:**

The database driver.



**Throws:**

- [`UnexpectedValueException`](./UnexpectedValueException.md)




***

### setDbo

Set the database driver.

```php
public setDbo(?\Joomla\Database\DatabaseInterface $db = null): void
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db` | **?\Joomla\Database\DatabaseInterface** | The database driver. |






***

### __get

Proxy for _db variable.

```php
public __get(string $name): mixed
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.



**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the element |


**Return Value:**

The value of the element if set, null otherwise





***

### loadForm

Method to get a form object.

```php
protected loadForm(string $name, string $source = null, array $options = [], bool $clear = false, string $xpath = null): \Joomla\CMS\Form\Form
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The name of the form. |
| `$source` | **string** | The form source. Can be XML string if file flag is set to false. |
| `$options` | **array** | Optional array of options for the form creation. |
| `$clear` | **bool** | Optional argument to force load a new form. |
| `$xpath` | **string** | An optional xpath to search for the fields. |




**Throws:**

- [`Exception`](./Exception.md)




**See Also:**

* \Joomla\CMS\Form\Form - 

***

### loadFormData

Method to get the data that should be injected in the form.

```php
protected loadFormData(): mixed
```









**Return Value:**

The data for the form.





***

### preprocessData

Method to allow derived classes to preprocess the data.

```php
protected preprocessData(string $context, mixed& $data, string $group = &#039;content&#039;): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$context` | **string** | The context identifier. |
| `$data` | **mixed** | The data to be processed. It gets altered directly. |
| `$group` | **string** | The name of the plugin group to import (defaults to &quot;content&quot;). |






***

### preprocessForm

Method to allow derived classes to preprocess the form.

```php
protected preprocessForm(\Joomla\CMS\Form\Form $form, mixed $data, string $group = &#039;content&#039;): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form` | **\Joomla\CMS\Form\Form** | A Form object. |
| `$data` | **mixed** | The data expected for the form. |
| `$group` | **string** | The name of the plugin group to import (defaults to &quot;content&quot;). |




**Throws:**
<p>if there is an error in the form event.</p>

- [`Exception`](./Exception.md)




**See Also:**

* \Joomla\CMS\Form\FormField - 

***

### getFormFactory

Get the FormFactoryInterface.

```php
public getFormFactory(): \Joomla\CMS\Form\FormFactoryInterface
```




* This method is **abstract**.






**Throws:**
<p>May be thrown if the FormFactory has not been set.</p>

- [`UnexpectedValueException`](./UnexpectedValueException.md)




***

### getEmptyStateQuery

Provide a query to be used to evaluate if this is an Empty State, can be overridden in the model to provide granular control.

```php
protected getEmptyStateQuery(): \Joomla\Database\DatabaseQuery
```













***

### getIsEmptyState

Is this an empty state, I.e: no items of this type regardless of the searched for states.

```php
public getIsEmptyState(): bool
```











**Throws:**

- [`Exception`](./Exception.md)




***

### _getListQuery

Method to cache the last query constructed.

```php
protected _getListQuery(): \Joomla\Database\DatabaseQuery
```

This method ensures that the query is constructed only once for a given state of the model.







**Return Value:**

A DatabaseQuery object





***

### getActiveFilters

Function to get the active filters

```php
public getActiveFilters(): array
```









**Return Value:**

Associative array in the format: array('filter_published' => 0)





***

### getItems

Method to get an array of data items.

```php
public getItems(): mixed
```









**Return Value:**

An array of data items on success, false on failure.





***

### getListQuery

Method to get a DatabaseQuery object for retrieving the data set from a database.

```php
protected getListQuery(): \Joomla\Database\DatabaseQuery|string
```









**Return Value:**

A DatabaseQuery object to retrieve the data set.





***

### getPagination

Method to get a \JPagination object for the data set.

```php
public getPagination(): \Joomla\CMS\Pagination\Pagination
```









**Return Value:**

A Pagination object for the data set.





***

### getStoreId

Method to get a store id based on the model configuration state.

```php
protected getStoreId(string $id = &#039;&#039;): string
```

This is necessary because the model is used by the component and
different modules that might need different sets of data or different
ordering requirements.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **string** | An identifier string to generate the store id. |


**Return Value:**

A store id.





***

### getTotal

Method to get the total number of items for the data set.

```php
public getTotal(): int
```









**Return Value:**

The total number of items available in the data set.





***

### getStart

Method to get the starting number of items for the data set.

```php
public getStart(): int
```









**Return Value:**

The starting number of items available in the data set.





***

### getFilterForm

Get the filter form

```php
public getFilterForm(array $data = [], bool $loadData = true): \Joomla\CMS\Form\Form|null
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | **array** | data |
| `$loadData` | **bool** | load current data |


**Return Value:**

The \JForm object or null if the form can't be found





***

### getUserStateFromRequest

Gets the value of a user state variable and sets it in the session

```php
public getUserStateFromRequest(string $key, string $request, string $default = null, string $type = &#039;none&#039;, bool $resetPage = true): mixed
```

This is the same as the method in Application except that this also can optionally
force you back to the first page when a filter has changed






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **string** | The key of the user state variable. |
| `$request` | **string** | The name of the variable passed in a request. |
| `$default` | **string** | The default value for the variable if not found. Optional. |
| `$type` | **string** | Filter for the variable. Optional.<br />@see        \Joomla\CMS\Filter\InputFilter::clean() for valid values. |
| `$resetPage` | **bool** | If true, the limitstart in request is set to zero |


**Return Value:**

The request user state.





***

### refineSearchStringToRegex

Parse and transform the search string into a string fit for regex-ing arbitrary strings against

```php
protected refineSearchStringToRegex(string $search, string $regexDelimiter = &#039;/&#039;): string
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$search` | **string** | The search string |
| `$regexDelimiter` | **string** | The regex delimiter to use for the quoting |


**Return Value:**

Search string escaped for regex





***


***
> Last updated on 20/08/2024
