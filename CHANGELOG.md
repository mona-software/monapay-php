# Changelog

## 0.4.1 - 2026-10-05

- Phát hành lại để Packagist trỏ link tải về `github.com/mona-software/monapay-php`. Các bản trước vẫn tải từ org cũ `themonagroup` (đã bị GitHub khoá) nên `composer require` báo không tìm thấy repo. Bổ sung homepage, tác giả MONA Software và link hỗ trợ trong `composer.json`.

## 0.4.0

- Thêm `paymentProfile`, `checkouts`, xem lại/xoay secret hồ sơ và API key.
- Tự sinh `Idempotency-Key` cho tạo/huỷ checkout và cho phép truyền key riêng.

## 0.3.0

- Client credentials mặc định, token có hạn và factory `Client::fromEnv()`.
- Thêm sandbox transactions, email configs, email logs và email suppressions.

## 0.1.0

- Bản đầu tiên của SDK PHP.
