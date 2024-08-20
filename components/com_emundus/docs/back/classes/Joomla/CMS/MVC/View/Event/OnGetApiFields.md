***

# OnGetApiFields

Event for getting extra API Fields and Relations to render with an entity



* Full name: `\Joomla\CMS\MVC\View\Event\OnGetApiFields`
* Parent class: [`AbstractImmutableEvent`](../../../Event/AbstractImmutableEvent.md)
* This class is marked as **final** and can't be subclassed
* This class is a **Final class**


## Constants

| Constant | Visibility | Type | Value |
|:---------|:-----------|:-----|:------|
|`LIST`|public| |&#039;list&#039;|
|`ITEM`|public| |&#039;item&#039;|

## Properties


### extraRelations

List of names of properties that will be rendered as relations

```php
private string[] $extraRelations
```






***

### extraAttributes

List of names of properties that will be rendered as data

```php
private string[] $extraAttributes
```






***

## Methods


### __construct

Constructor.

```php
public __construct(string $name, array $arguments = []): mixed
```

Mandatory arguments:
type         string          The type of the field. Should be a constant from static::VIEW_TYPE
fields       fields          The list of fields that will be rendered in the API.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | **string** | The event name. |
| `$arguments` | **array** | The event arguments. |




**Throws:**

- [`BadMethodCallException`](../../../../../BadMethodCallException.md)




***

### setType

Setter for the type argument

```php
protected setType(int $value): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **int** | The constant from VIEW_TYPE |




**Throws:**
<p>if the argument is not of the expected type</p>

- [`BadMethodCallException`](../../../../../BadMethodCallException.md)




***

### setFields

Setter for the fields argument

```php
protected setFields(mixed $value): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **mixed** | The value to set |




**Throws:**
<p>if the argument is not a non-empty array</p>

- [`BadMethodCallException`](../../../../../BadMethodCallException.md)




***

### setRelations

Setter for the relations argument

```php
protected setRelations(mixed $value): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | **mixed** | The value to set |




**Throws:**
<p>if the argument is not a non-empty array</p>

- [`BadMethodCallException`](../../../../../BadMethodCallException.md)




***

### addFields

Allows the user to add names of properties that will be interpreted as relations
Note that if there is an existing data property it will also be displayed as well
as the relation due to the internal implementation (this behaviour is not part of this API
however and should not be guaranteed)

```php
public addFields(string[] $fields): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fields` | **string[]** | The array of additional fields to add to the data of the attribute |






***

### addRelations

Allows the user to add names of properties that will be interpreted as relations
Note that if there is an existing data property it will also be displayed as well
as the relation due to the internal implementation (this behaviour is not part of this API
however and should not be guaranteed)

```php
public addRelations(string[] $fields): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fields` | **string[]** | The array of additional fields to add as relations |






***

### getAllPropertiesToRender

Get properties to render.

```php
public getAllPropertiesToRender(): array
```













***

### getAllRelationsToRender

Get properties to render.

```php
public getAllRelationsToRender(): array
```













***


***
> Last updated on 20/08/2024
