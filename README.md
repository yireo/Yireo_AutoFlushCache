# Yireo AutoFlushCache

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-autoflushcache)
<!-- badges.specs.end -->

Magento 2 module to automatically flush the cache whenever you save
something in the System Configuration.

**Do NOT use this in production.**

## Overview
When saving the System Configuration within Magento 2, the core
behaviour is to add a reminder in the backend that you should manually
flush the cache. In production, this is a good practice: Instead of
flushing the cache with every change, the cache is only flushed when
needed. In development, I always keep caching on, and then this becomes
annoying. This module automatically flushes the cache for you.

## Installation
Install this module within Magento 2 using composer:

    composer require yireo/magento2-autoflushcache --dev
    bin/magento module:enable Yireo_AutoFlushCache

## Technical architecture
This module listens to the event
`controller_action_postdispatch_adminhtml_system_config_save`. Whenever
it is called, it flushes the following caches:

- `config`
- `block_html`
- `full_page`

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AutoFlushCache/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AutoFlushCache/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AutoFlushCache/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AutoFlushCache/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AutoFlushCache/compile.yml?label=compile)
<!-- badges.test.end -->
