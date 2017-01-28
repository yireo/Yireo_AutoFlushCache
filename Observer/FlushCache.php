<?php
namespace Yireo\AutoFlushCache\Observer;

use Magento\Framework\Event\ObserverInterface;

class FlushCache implements ObserverInterface
{
    public function __construct(
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    ) {
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $types = array('config','block_html','full_page');
        foreach ($types as $type) {
            $this->_cacheTypeList->cleanType($type);
        }
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}