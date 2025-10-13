# SePay PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/sepay/sepay-pg.svg)](https://packagist.org/packages/sepay/sepay-pg)
[![PHP Version](https://img.shields.io/packagist/php-v/sepay/sepay-pg.svg)](https://packagist.org/packages/sepay/sepay-pg)
[![License](https://img.shields.io/packagist/l/sepay/sepay-pg.svg)](https://packagist.org/packages/sepay/sepay-pg)

Bản dịch: [Tiếng Việt](README.vi.md) | [Tiếng Anh](README.md)

SDK PHP chính thức cho Cổng thanh toán SePay. Tích hợp dễ dàng thanh toán, chuyển khoản ngân hàng, VietQR và thanh toán định kỳ.

## Cài đặt

Cài đặt SDK trực tiếp từ GitHub bằng Composer:

```bash
composer config repositories.sepay vcs https://github.com/sepayvn/sepay-pg-php

composer require sepay/sepay-pg --prefer-source
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

// Tạo các trường form với chữ ký
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
$sepay->setRetryAttempts(3)
      ->setRetryDelay(1000); // mili giây
```

### Builder Pattern

Bạn có thể dùng CheckoutBuilder để code sạch hơn, hoặc truyền array trực tiếp.

```php
// Sử dụng CheckoutBuilder (khuyến nghị)
$checkoutData = CheckoutBuilder::make()
    ->currency('VND')
    ->orderAmount(100000)
    ->operation('PURCHASE')
    ->orderDescription('Thanh toán thử nghiệm')
    ->build();

// Sử dụng array trực tiếp (tốt cho dữ liệu động)
$checkoutArray = [
    'currency' => 'VND',
    'order_amount' => 100000,
    'operation' => 'PURCHASE',
    'order_description' => 'Thanh toán thử nghiệm',
];

// Cả hai đều hoạt động với generateFormFields
$formFields = $sepay->checkout()->generateFormFields($checkoutData);
$formFields = $sepay->checkout()->generateFormFields($checkoutArray);
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

#### Hai Cách Sử dụng generateFormFields

**Cách 1: Sử dụng CheckoutBuilder**

-   Type-safe và API sạch
-   Tự động validate
-   Hỗ trợ IDE tốt
-   Dễ đọc

**Cách 2: Sử dụng Array**

-   Linh hoạt hơn
-   Tốt cho dữ liệu động
-   Dễ tích hợp với code hiện có
-   Cùng validation và bảo mật

```php
// Lấy URL endpoint checkout
$checkoutUrl = $sepay->checkout()->getCheckoutUrl('sandbox'); // hoặc 'production'

// Tạo form HTML hoàn chỉnh
$htmlForm = $sepay->checkout()->generateFormHtml($checkoutData, 'sandbox', [
    'id' => 'payment-form',
    'class' => 'checkout-form',
]);

```

#### Phương thức thanh toán

```php
// Thanh toán bằng thẻ sử dụng CheckoutBuilder
$cardCheckout = CheckoutBuilder::make()
    ->paymentMethod('CARD')
    ->currency('VND')
    ->orderAmount(250000)
    ->operation('PURCHASE')
    ->orderDescription('Thanh toán bằng thẻ')
    ->orderInvoiceNumber('CARD_001')
    ->successUrl('https://yoursite.com/success')
    ->build();

// Thanh toán chuyển khoản ngân hàng sử dụng CheckoutBuilder
$bankCheckout = CheckoutBuilder::make()
    ->paymentMethod('BANK_TRANSFER')
    ->currency('VND')
    ->orderAmount(150000)
    ->operation('PURCHASE')
    ->orderDescription('Thanh toán chuyển khoản ngân hàng')
    ->orderInvoiceNumber('BANK_001')
    ->successUrl('https://yoursite.com/success')
    ->build();

// Cách khác: Sử dụng array trực tiếp
$cardArray = [
    'payment_method' => 'CARD',
    'currency' => 'VND',
    'order_amount' => 250000,
    'operation' => 'PURCHASE',
    'order_description' => 'Thanh toán bằng thẻ',
    'order_invoice_number' => 'CARD_001',
    'success_url' => 'https://yoursite.com/success',
];

$bankArray = [
    'payment_method' => 'BANK_TRANSFER',
    'currency' => 'VND',
    'order_amount' => 150000,
    'operation' => 'PURCHASE',
    'order_description' => 'Thanh toán chuyển khoản ngân hàng',
    'order_invoice_number' => 'BANK_001',
    'branch_code' => '001',
    'success_url' => 'https://yoursite.com/success',
];

// Cả hai cách đều hoạt động giống nhau
$cardFields = $sepay->checkout()->generateFormFields($cardArray);
$bankFields = $sepay->checkout()->generateFormFields($bankArray);
```

### Đơn hàng

```php
// Lấy đơn hàng theo số hóa đơn
$order = $sepay->orders()->retrieve('order_invoice_number');

// Liệt kê đơn hàng với bộ lọc
$orders = $sepay->orders()->list([
    'per_page' => 10,
    'order_status' => 'CAPTURED',
    'start_created_at' => '2024-01-01',
    'end_created_at' => '2024-12-31',
]);

// Hủy giao dịch (hủy thanh toán)
$result = $sepay->orders()->voidTransaction('order_invoice_number');
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
    $order = $sepay->orders()->retrieve('order_invoice_number');
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

### Biến Môi trường

```php
// Bạn có thể sử dụng biến môi trường để cấu hình
$sepay = new SePayClient(
    $_ENV['SEPAY_MERCHANT_ID'],
    $_ENV['SEPAY_SECRET_KEY'],
    $_ENV['SEPAY_ENVIRONMENT'] ?? SePayClient::ENVIRONMENT_SANDBOX
);
```

### Cấu hình Tùy chỉnh

```php
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
-   Tài liệu: <https://docs.sepay.vn>
-   Issues: <https://github.com/sepayvn/sepay-pg-php/issues>

## Lịch sử thay đổi

Xem [CHANGELOG.md](CHANGELOG.md) để biết lịch sử phiên bản và thay đổi.
