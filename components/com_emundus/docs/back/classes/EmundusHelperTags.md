***

# EmundusHelperTags

Content Component Query Helper



* Full name: `\EmundusHelperTags`




## Methods


### getVariables

Find all variables like ${var} or [var] in string.

```php
public getVariables(string $str, int $type = &#039;CURLY&#039;): string[]
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$str` | **string** |  |
| `$type` | **int** | type of bracket default CURLY else SQUARE |





***

### getTags



```php
public getTags(mixed $tags = [], mixed $published = 1): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tags` | **mixed** |  |
| `$published` | **mixed** |  |





***


***
> Automatically generated on 2024-08-02
