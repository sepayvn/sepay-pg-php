# SePay PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/sepay/sepay-pg.svg)](https://packagist.org/packages/sepay/sepay-pg)
[![PHP Version](https://img.shields.io/packagist/php-v/sepay/sepay-pg.svg)](https://packagist.org/packages/sepay/sepay-pg)
[![License](https://img.shields.io/packagist/l/sepay/sepay-pg.svg)](https://packagist.org/packages/sepay/sepay-pg)
[![Total Downloads](https://img.shields.io/packagist/dt/sepay/sepay-pg.svg?style=flat-square)](https://packagist.org/packages/sepay/sepay-pg)

Bản dịch: [Tiếng Việt](README.vi.md) | [Tiếng Anh](README.md)

SDK PHP chính thức cho Cổng thanh toán SePay. Tích hợp dễ dàng thanh toán, chuyển khoản ngân hàng, VietQR và thanh toán định kỳ.

## Cài đặt

Bạn có thể cài đặt SDK bằng cách chạy lệnh sau:

```bash
composer require sepay/sepay-pg
```

## Yêu cầu

- PHP 7.4 trở lên
- ext-json
- ext-curl
- Guzzle HTTP client

## Bắt đầu nhanh

```php
<?php

require_once 'vendor/autoload.php';

use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;

// Khởi tạo client
$sepay = new SePayClient(
    'SP-TEST-XXXXXXX',
    'spsk_live_xxxxxxxxxxxo99PoE7RsBpss3EFH5nV',
    SePayClient::ENVIRONMENT_SANDBOX // hoặc ENVIRONMENT_PRODUCTION
);

// Tạo checkout
$checkoutData = CheckoutBuilder::make()
    ->currency('VND')
    ->orderAmount(100000) // 100,000 VND
    ->operation('PURCHASE')
    ->orderDescription('Thanh toán thử nghiệm')
    ->orderInvoiceNumber('INV_001')
    ->successUrl('https://yoursite.com/success')
    ->build();

// Hiển thị form checkout để thanh toán
echo $sepay->checkout()->generateFormHtml($checkoutData);

// Hoặc tự tạo các trường form với chữ ký
$formFields = $sepay->checkout()->generateFormFields($checkoutData);

echo "Form checkout đã được tạo với chữ ký: " . $formFields['signature'];
```

## Ví dụ

Chạy các ví dụ để xem cách sử dụng:

```bash
# Tổng quan
php examples/basic-usage.php

# Các ví dụ cụ thể
php examples/01-checkout-basic.php      # Thanh toán cơ bản
php examples/02-orders-management.php   # Quản lý đơn hàng
php examples/03-payment-methods.php     # Phương thức thanh toán
```

## Khái niệm cốt lõi

### Khởi tạo Client

```php
use SePay\SePayClient;

$sepay = new SePayClient(
    $merchantId,
    $secretKey,
    $environment, // 'sandbox' hoặc 'production'
    $config       // Mảng cấu hình tùy chọn
);

// Bật chế độ debug
$sepay->enableDebugMode();

// Cấu hình hành vi thử lại
$sepay
    ->setRetryAttempts(3)
    ->setRetryDelay(1000); // mili giây
```

## Tài nguyên API

### Checkout

Tạo các trường form an toàn để xử lý thanh toán. Đây là cách bắt đầu thanh toán với SePay.

```php
// Checkout đơn giản sử dụng CheckoutBuilder
$checkoutData = CheckoutBuilder::make()
    ->currency('VND')
    ->orderAmount(100000) // 100,000 VND
    ->operation('PURCHASE')
    ->orderDescription('Thanh toán cho đơn hàng #123')
    ->orderInvoiceNumber('INV_123')
    ->customerId('customer_001')
    ->successUrl('https://yoursite.com/success')
    ->errorUrl('https://yoursite.com/error')
    ->cancelUrl('https://yoursite.com/cancel')
    ->build();

// Tạo các trường form với chữ ký
$formFields = $sepay->checkout()->generateFormFields($checkoutData);

// Cách khác: Truyền array trực tiếp
$checkoutArray = [
    'currency' => 'VND',
    'order_amount' => 100000,
    'operation' => 'PURCHASE',
    'order_description' => 'Thanh toán cho đơn hàng #123',
    'order_invoice_number' => 'INV_123',
    'customer_id' => 'customer_001',
    'success_url' => 'https://yoursite.com/success',
    'error_url' => 'https://yoursite.com/error',
    'cancel_url' => 'https://yoursite.com/cancel',
];

$formFields = $sepay->checkout()->generateFormFields($checkoutArray);
```

#### Phương thức thanh toán

```php
// Thanh toán bằng thẻ
CheckoutBuilder::make()
    ->paymentMethod('CARD')
    // ...
    ->build();

// Thanh toán chuyển khoản ngân hàng
$bankCheckout = CheckoutBuilder::make()
    ->paymentMethod('BANK_TRANSFER')
    // ...
    ->build();

// Hiển thị tất cả phương thức thanh toán
$allMethodsCheckout = CheckoutBuilder::make()
    // ->paymentMethod('...')
    // ...
    ->build();
```

### Đơn hàng

```php
// Lấy đơn hàng theo số hóa đơn
$order = $sepay->orders()->retrieve('ORDER_INVOICE_NUMBER');

// Liệt kê đơn hàng với bộ lọc
$orders = $sepay->orders()->list([
    'per_page' => 10,
    'order_status' => 'CAPTURED',
    'from_created_at' => '2024-01-01',
    'to_created_at' => '2024-12-31',
]);

// Hủy giao dịch (hủy thanh toán)
$result = $sepay->orders()->voidTransaction('ORDER_INVOICE_NUMBER');
```

Lưu ý: Đơn hàng được tạo khi khách hàng hoàn thành checkout, không trực tiếp qua API.

## Xử lý lỗi

SDK có các loại exception khác nhau cho từng loại lỗi:

```php
use SePay\Exceptions\AuthenticationException;
use SePay\Exceptions\ValidationException;
use SePay\Exceptions\NotFoundException;
use SePay\Exceptions\RateLimitException;
use SePay\Exceptions\ServerException;

try {
    $order = $sepay->orders()->retrieve('ORDER_INVOICE_NUMBER');
} catch (AuthenticationException $e) {
    // Thông tin đăng nhập hoặc chữ ký không hợp lệ
    echo "Xác thực thất bại: " . $e->getMessage();
} catch (ValidationException $e) {
    // Dữ liệu yêu cầu không hợp lệ
    echo "Lỗi xác thực: " . $e->getMessage();

    // Lấy lỗi cụ thể cho từng trường
    if ($e->hasFieldError('amount')) {
        $errors = $e->getFieldErrors('amount');
        echo "Lỗi số tiền: " . implode(', ', $errors);
    }
} catch (NotFoundException $e) {
    // Không tìm thấy tài nguyên
    echo "Không tìm thấy: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Vượt quá giới hạn tốc độ
    echo "Bị giới hạn tốc độ. Thử lại sau: " . $e->getRetryAfter() . " giây";
} catch (ServerException $e) {
    // Lỗi máy chủ (5xx)
    echo "Lỗi máy chủ: " . $e->getMessage();
}
```

## Cấu hình

```php
$merchantId = 'SP-LIVE-XXXXXXX';
$secretKey = 'spsk_live_xxxxxxxxxxxo99PoE7RsBpss3EFH5nV';

$config = [
    'timeout' => 60,           // Thời gian chờ yêu cầu tính bằng giây
    'retry_attempts' => 5,     // Số lần thử lại
    'retry_delay' => 2000,     // Độ trễ giữa các lần thử lại tính bằng mili giây
    'debug' => true,           // Bật ghi log debug
    'user_agent' => 'MyApp/1.0 SePay-PHP-SDK/1.0.0',
    'logger' => $customLogger, // Logger tương thích PSR-3
];

$sepay = new SePayClient($merchantId, $secretKey, $environment, $config);
```

## Kiểm thử

Chạy bộ kiểm thử:

```bash
# Chạy tất cả kiểm thử
composer test

# Chạy kiểm thử với độ bao phủ
composer test-coverage

# Chạy phân tích tĩnh
composer phpstan

# Sửa kiểu code
composer cs-fix
```

## Đóng góp

1. Fork repository
2. Tạo nhánh tính năng
3. Thực hiện thay đổi
4. Thêm kiểm thử cho chức năng mới
5. Đảm bảo tất cả kiểm thử đều pass
6. Gửi pull request

## Giấy phép

SDK này được cấp phép theo MIT License. Xem [LICENSE](LICENSE) để biết chi tiết.

## Hỗ trợ

-   Email: <info@sepay.vn>
-   Tài liệu: <https://developer.sepay.vn>
-   Issues: <https://github.com/sepayvn/sepay-pg-php/issues>

## Lịch sử thay đổi

Xem [CHANGELOG.md](CHANGELOG.md) để biết lịch sử phiên bản và thay đổi.
