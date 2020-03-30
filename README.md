# Yireo AutoFlushCache
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
