***

# MVCFactoryServiceTrait

Defines the trait for a MVC factory service class.



* Full name: `\Joomla\CMS\MVC\Factory\MVCFactoryServiceTrait`



## Properties


### mvcFactory

The MVC Factory.

```php
private \Joomla\CMS\MVC\Factory\MVCFactoryInterface $mvcFactory
```






***

## Methods


### getMVCFactory

Get the factory.

```php
public getMVCFactory(): \Joomla\CMS\MVC\Factory\MVCFactoryInterface
```











**Throws:**
<p>May be thrown if the factory has not been set.</p>

- [`UnexpectedValueException`](../../../../UnexpectedValueException.md)




***

### setMVCFactory

The MVC Factory.

```php
public setMVCFactory(\Joomla\CMS\MVC\Factory\MVCFactoryInterface $mvcFactory): void
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mvcFactory` | **\Joomla\CMS\MVC\Factory\MVCFactoryInterface** | The factory |






***

***
> Last updated on 20/08/2024

