# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 1.3.0

Magento 2.4.8 compatibility release

### Added

- Support for Magento 2.4.8
- Modern code quality toolchain with PHPStan and Rector
- PHP 8.4 compatibility

### Changed

- Updated dependency versions for Magento 2.4.8
- Modernized null parameter type declarations

### Removed

- Support for PHP 8.2

## 1.2.0

Magento 2.4.7 compatibility release

### Added

- Support for Magento 2.4.7

### Removed

- Support for PHP 7.x and 8.1

## 1.1.2

### Fixed

- Disregard _Ship Bundle Items_ product setting during inventory source allocation for bundle items, contributed via PR [#2](https://github.com/netresearch/module-shipping-inventory/pull/2)

## 1.1.1

### Fixed

- Bulk process bundle items, reported via issue [#1](https://github.com/netresearch/module-shipping-inventory/issues/1)

## 1.1.0

Magento 2.4.4 compatibility release

### Added

- Support for Magento 2.4.4

### Removed

- Support for PHP 7.1

## 1.0.1

### Fixed

- Prevent bulk shipment exception when processing only one shipment item.

## 1.0.0

Initial release 
