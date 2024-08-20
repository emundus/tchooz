***

# EmundusControllerDashboard

Emundus Dashboard Controller



* Full name: `\EmundusControllerDashboard`
* Parent class: [`BaseController`](./Joomla/CMS/MVC/Controller/BaseController.md)



## Properties


### _user



```php
private \Joomla\CMS\User\User|\JUser|mixed|null $_user
```






***

### m_dashboard



```php
private \EmundusModelDashboard $m_dashboard
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

### getallwidgetsbysize

Get all widgets by size

```php
public getallwidgetsbysize(): mixed
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.







***

### getpalettecolors

Get colors to apply on widgets

```php
public getpalettecolors(): mixed
```






* **Warning:** this method is **deprecated**. This means that this method will likely be removed in a future version.







***

### getwidgets

Get widgets to display for logged user for current profile

```php
public getwidgets(): mixed
```












***

### updatemydashboard

Update widgets to display for logged user for current profile

```php
public updatemydashboard(): mixed
```












***

### getfilters

Get filters for a widget

```php
public getfilters(): mixed
```












***

### renderchartbytag

Render chart

```php
public renderchartbytag(): mixed
```












***

### getarticle

Get article to display in widget

```php
public getarticle(): mixed
```












***

### geteval

Render widget via PHP Code (cannot be applied for applicant users)

```php
public geteval(): mixed
```












***


***
> Last updated on 20/08/2024
