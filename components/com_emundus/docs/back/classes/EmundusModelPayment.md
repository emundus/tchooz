***

# EmundusModelPayment





* Full name: `\EmundusModelPayment`
* Parent class: [`JModelList`](./JModelList.md)




## Methods


### __construct



```php
public __construct(): mixed
```












***

### getPrice



```php
public getPrice(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### isScholarshipStudent

Detect if student is a scholarship student

```php
public isScholarshipStudent(mixed $fnum): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** | string |





***

### doesScholarshipHoldersNeedToPay



```php
public doesScholarshipHoldersNeedToPay(): bool
```












***

### setPaymentUniqid



```php
public setPaymentUniqid(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### createPaymentOrder



```php
public createPaymentOrder(mixed $fnum, mixed $type, mixed $order_number = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$type` | **mixed** |  |
| `$order_number` | **mixed** |  |





***

### getUserIdFromFnum



```php
private getUserIdFromFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getHikashopUserId



```php
private getHikashopUserId(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### createHikashopUser



```php
private createHikashopUser(mixed $user_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user_id` | **mixed** |  |





***

### updateEmundusHikashopOrderId



```php
private updateEmundusHikashopOrderId(mixed $fnum, mixed $order_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** | string |
| `$order_id` | **mixed** | int |





***

### insertEmundusHikashopOrderId



```php
private insertEmundusHikashopOrderId(mixed $fnum, mixed $order_id): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$order_id` | **mixed** |  |





***

### getPaymentInfos



```php
public getPaymentInfos(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getProduct



```php
public getProduct(mixed $product_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$product_id` | **mixed** |  |





***

### getProductByFnum



```php
public getProductByFnum(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### updateFlywirePaymentInfos



```php
public updateFlywirePaymentInfos(string $callback_id, array $data): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$callback_id` | **string** |  |
| `$data` | **array** |  |





***

### getFnumFromCallbackId

Find callback_id in emundus_hikashop table and return fnum

```php
private getFnumFromCallbackId(string $callback_id): string|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$callback_id` | **string** |  |





***

### getFnumFromOrderId

Find callback_id in emundus_hikashop table and return fnum

```php
private getFnumFromOrderId(mixed $order): string|false
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$order` | **mixed** |  |





***

### checkAmountCoherence



```php
private checkAmountCoherence(mixed $fnum, mixed $amount): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$amount` | **mixed** |  |





***

### updateHikashopPayment



```php
private updateHikashopPayment(mixed $fnum, mixed $hikashop_status, mixed $data, mixed $type = &#039;flywire&#039;, mixed $order_number = null): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$hikashop_status` | **mixed** |  |
| `$data` | **mixed** |  |
| `$type` | **mixed** |  |
| `$order_number` | **mixed** |  |





***

### updateFnumStateFromFlywire



```php
private updateFnumStateFromFlywire(mixed $fnum): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getFlywireExtendedConfig



```php
public getFlywireExtendedConfig(mixed $config): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$config` | **mixed** |  |





***

### getConfig



```php
public getConfig(mixed $fnum): array
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### saveConfig



```php
public saveConfig(mixed $fnum, mixed $new_config): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** | string |
| `$new_config` | **mixed** | array |





***

### updateFileTransferPayment



```php
public updateFileTransferPayment(mixed $user): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |





***

### updateHikashopOrderType



```php
private updateHikashopOrderType(mixed $order_id, mixed $type, mixed $order_number = null): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$order_id` | **mixed** |  |
| `$type` | **mixed** |  |
| `$order_number` | **mixed** |  |





***

### updateAxeptaPaymentInfos



```php
public updateAxeptaPaymentInfos(mixed $order, mixed $status, mixed $id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$order` | **mixed** |  |
| `$status` | **mixed** |  |
| `$id` | **mixed** |  |





***

### resetPaymentSession



```php
public resetPaymentSession(): mixed
```












***

### checkPaymentSession



```php
public checkPaymentSession(mixed $fnum = null, mixed $caller = &#039;&#039;): bool
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$caller` | **mixed** |  |





***

### getHikashopUser



```php
private getHikashopUser(mixed $fnum): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |





***

### getGeneratedCoupon



```php
public getGeneratedCoupon(mixed $fnum, mixed $hikashop_product_category, mixed $code_like = null): int
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$hikashop_product_category` | **mixed** |  |
| `$code_like` | **mixed** |  |





***

### generateCoupon

Create a discount coupon for given user
$fnum string
$discount_amount price or percent of the discount
$discount_amount_type flat (for price) OR percent
$hikashop_product_category category on which discount can be applied
$discount_duration

```php
public generateCoupon(mixed $fnum, mixed $discount_amount, mixed $discount_amount_type = &#039;flat&#039;, mixed $hikashop_product_category, mixed $discount_duration = 1800): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fnum` | **mixed** |  |
| `$discount_amount` | **mixed** |  |
| `$discount_amount_type` | **mixed** |  |
| `$hikashop_product_category` | **mixed** |  |
| `$discount_duration` | **mixed** |  |





***

### didUserPay



```php
public didUserPay(mixed $user, mixed $fnum, mixed $product_id): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** |  |
| `$fnum` | **mixed** |  |
| `$product_id` | **mixed** |  |





***


***
> Last updated on 20/08/2024
