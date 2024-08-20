***

# EmundusHelperDate

Content Component Query Helper



* Full name: `\EmundusHelperDate`




## Methods


### getNow

Return actual date formatted in UTC timezone

```php
public static getNow(mixed $timezone = &#039;UTC&#039;): string
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$timezone` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### displayDate

Return a saved date formatted to the current timezone

```php
public static displayDate(mixed $date, mixed $format = &#039;DATE_FORMAT_LC2&#039;, mixed $local = 1): string
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | **mixed** |  |
| `$format` | **mixed** |  |
| `$local` | **mixed** |  |




**Throws:**

- [`Exception`](./Exception.md)



***

### isNull

Check if date is null

```php
public static isNull(mixed $date): bool
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | **mixed** |  |





***


***
> Last updated on 20/08/2024
