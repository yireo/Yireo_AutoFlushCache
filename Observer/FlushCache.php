<?php declare(strict_types=1);

namespace Yireo\AutoFlushCache\Observer;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\State;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class FlushCache implements ObserverInterface
{
    public const ACL_RESOURCE = 'Magento_Backend::refresh_cache_type';
    public const CACHE_TYPES = ['config', 'block_html', 'full_page'];

    private TypeListInterface $cacheTypeList;
    private AuthorizationInterface $authorization;
    private State $appState;
    private AuthSession $authSession;
    private LoggerInterface $logger;

    public function __construct(
        TypeListInterface $cacheTypeList,
        AuthorizationInterface $authorization,
        State $appState,
        AuthSession $authSession,
        LoggerInterface $logger
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->authorization = $authorization;
        $this->appState = $appState;
        $this->authSession = $authSession;
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        if ($this->appState->getMode() !== State::MODE_DEVELOPER) {
            return;
        }

        if (!$this->authorization->isAllowed(self::ACL_RESOURCE)) {
            return;
        }

        foreach (self::CACHE_TYPES as $type) {
            $this->cacheTypeList->cleanType($type);
        }

        $user = $this->authSession->getUser();
        $this->logger->info('Yireo_AutoFlushCache: Cleaned cache types after saving the configuration', [
            'types' => self::CACHE_TYPES,
            'admin_user' => $user ? (string)$user->getUserName() : '',
        ]);
    }
}
