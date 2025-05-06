# ระบบส่งอีเมลจำนวนมากสำหรับ Amazon SES (Mass Email Sender for Amazon SES)

[English version below](#mass-email-sender-for-amazon-ses)

ระบบพัฒนาขึ้นบน PHP เพื่อให้สามารถส่งข้อความทางอีเมลได้โดยใช้ระบบ Amazon Web Service SES (Amazon SES)
แรกระบบนี้ทำขึ้นเพื่อใช้งานส่วนตัวเพื่อส่งข้อความถึงเนื่องจากได้รับโจทย์ให้ส่งอีเมลจำนวนมากถึงผู้ใช้

และต่อมาได้โจทย์นำนองเดียวกันจึงนำโปรเจคเก่ามาเก็บไว้ใน GitHub เผื่อใช้งานในครั้งต่อไป


## ฟีเจอร์ / Features

- ส่งอีเมลถึงผู้รับหลาย ๆ คนพร้อมกันได้
- สามารถใส่ชื่อผู้รับอยู่ในรูปแบบไฟล์ CSV (1 บรรทัด ต่อ 1 อีเมล) ได้
- ส่งได้ทั้งแบบ HTML และแบบข้อความทั่วไป
- ใส่ชื่อแบบ Placeholders ได้
- แนบไฟล์ได้
- ส่งเป็นก้อน (Batch) และเลือกขนาดได้ เพื่อให้ตรงกับลิมิตของ AWS แต่ล่ะบัญชี
- ดูประวัติการส่งได้
- ความสามารถในการส่งซ้ำ

## ความต้องการ / Requirements

- PHP 7.4 หรือสูงกว่า
- Composer
- SQLite ตัวเสริม PHP <- ส่วนใหญ่เปิดไว้อยู่แล้ว
- บัญชี Amazon AWS ที่มีสิทธิ SES
- PHPUnit (สำหรับการทดสอบ / for testing)

## การติดตั้ง

1. Download โปรเจคนี้ไป
2. รันคำสั่ง:

```
composer install
```

3. เนื่องจากเราใช้ SQLLite อย่าลืมอนุญาติให้เขียนไฟล์ไดด้ด้วย
4. เปิดใช้งานตามปกติหรือถ้ารันใช้ภายในเครื่องให้ใช้คำสั่ง

```
php -S 127.0.0.1:8888
```

## การทดสอบ / Testing

ระบบนี้มีชุดทดสอบ (Unit Tests) สำหรับทดสอบฟังก์ชันการทำงานต่าง ๆ เพื่อให้มั่นใจว่าระบบทำงานได้อย่างถูกต้อง

### การติดตั้งเพื่อทดสอบ / Test Installation

```
composer install --dev
```

### การรันทดสอบ / Running Tests

สำหรับ Linux/Mac:
```
./vendor/bin/phpunit
```

สำหรับ Windows:
```
vendor\bin\phpunit
```

หรือใช้สคริปต์ที่เตรียมไว้ให้:
- Linux/Mac: `./run_tests.sh`
- Windows: `run_tests.bat`

หากต้องการรันเฉพาะไฟล์ทดสอบบางไฟล์:

```
./vendor/bin/phpunit tests/ConfigTest.php
```

หรือใช้สคริปต์:
- Linux/Mac: `./run_tests.sh tests/ConfigTest.php`
- Windows: `run_tests.bat tests\ConfigTest.php`

### ชุดทดสอบที่มี / Available Test Suites

- ConfigTest - ทดสอบฟังก์ชันการตั้งค่า
- DatabaseTest - ทดสอบการทำงานกับฐานข้อมูล
- EmailTest - ทดสอบการส่งอีเมล
- HistoryTest - ทดสอบการจัดการประวัติการส่งอีเมล
- IndexTest - ทดสอบหน้าหลักของแอปพลิเคชัน
- SettingsTest - ทดสอบการจัดการการตั้งค่า
- ResendTest - ทดสอบการส่งอีเมลซ้ำ

---

# Mass Email Sender for Amazon SES

This system is developed in PHP to send email messages using Amazon Web Service SES (Amazon SES).
It was initially created for personal use to send messages to users when tasked with sending mass emails.

Later, when similar requirements arose, the project was stored on GitHub for future use.

## Features

- Send emails to multiple recipients simultaneously
- Import recipients from a CSV file (1 line per email)
- Send in both HTML and plain text formats
- Use name placeholders for personalization
- Attach files
- Send in batches with configurable size to comply with AWS account limits
- View sending history
- Ability to resend emails

## Requirements

- PHP 7.4 or higher
- Composer
- SQLite PHP extension (usually enabled by default)
- Amazon AWS account with SES permissions
- PHPUnit (for testing)

## Installation

1. Download this project
2. Run the command:

```
composer install
```

3. Since we use SQLite, make sure the directory is writable
4. Open normally or if running locally, use the command:

```
php -S 127.0.0.1:8888
```

## Testing

The system includes unit tests to ensure all functionality works correctly.

### Test Installation

```
composer install --dev
```

### Running Tests

For Linux/Mac:
```
./vendor/bin/phpunit
```

For Windows:
```
vendor\bin\phpunit
```

Or use the provided scripts:
- Linux/Mac: `./run_tests.sh`
- Windows: `run_tests.bat`

To run specific test files:

```
./vendor/bin/phpunit tests/ConfigTest.php
```

Or using scripts:
- Linux/Mac: `./run_tests.sh tests/ConfigTest.php`
- Windows: `run_tests.bat tests\ConfigTest.php`

### Available Test Suites

- ConfigTest - Tests configuration functions
- DatabaseTest - Tests database operations
- EmailTest - Tests email sending
- HistoryTest - Tests email history management
- IndexTest - Tests the main application page
- SettingsTest - Tests settings management
- ResendTest - Tests email resending functionality