<?php
/**
 * AutoFlushCache module for Magento
 *
 * @package     Yireo_AutoFlushCache
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2017 Yireo (https://www.yireo.com/)
 * @license     Open Source License
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Yireo_AutoFlushCache',
    __DIR__
);
