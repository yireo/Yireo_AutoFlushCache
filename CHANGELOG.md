# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.3] - 25 November 2026
### Added
- Log the admin user that triggered the automatic cache refresh

### Changed
- The cache is now only refreshed automatically in developer mode
- Only the config, block_html and full_page cache types are cleaned, instead of flushing the entire cache storage (such as a Redis FLUSHDB)
- Dropped support for PHP 8.1 and older and for Magento versions before 2.4; added PHP 8.5 support

### Fixed
- Cache was also refreshed when a configuration save was denied or failed; it now only happens after a successful save by an admin user with the Magento_Backend::refresh_cache_type permission
- Removed the unused Magento_Config dependency and declared the composer packages the module actually uses (magento/module-backend, psr/log)

## [2.0.2] - 27 September 2023
### Fixed
- Remove PHP dep from composer
- Fixed dynamic properties under PHP 8.0+

## [2.0.1] - 29 July 2020
### Added
- Magento 2.4 compatibility

## [2.0.0] - July 2019
### Added
- Initial public release
