<?php declare(strict_types=1);

namespace Yireo\AutoFlushCache\Test\Integration\Observer;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\State;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Event\Config\Reader as EventConfigReader;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Yireo\AutoFlushCache\Observer\FlushCache;

class FlushCacheTest extends TestCase
{
    private const BLOCK_CACHE_ID = 'yireo_autoflushcache_test_block';
    private const OTHER_CACHE_ID = 'yireo_autoflushcache_test_other';
    private const OTHER_CACHE_TAG = 'YIREO_AUTOFLUSHCACHE_TEST';

    protected function tearDown(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        if ($objectManager instanceof ObjectManager) {
            $objectManager->removeSharedInstance(FlushCache::class);
        }

        $objectManager->get(CacheInterface::class)->remove(self::OTHER_CACHE_ID);
        $objectManager->get(BlockCache::class)->remove(self::BLOCK_CACHE_ID);
    }

    public function testObserverIsOnlyRegisteredForConfigSaveEventInAdminhtml(): void
    {
        $reader = Bootstrap::getObjectManager()->get(EventConfigReader::class);
        $adminhtmlEvents = $reader->read('adminhtml');

        $this->assertSame(
            FlushCache::class,
            $adminhtmlEvents['admin_system_config_save']['auto_flush_cache']['instance'] ?? null
        );
        $this->assertArrayNotHasKey(
            'auto_flush_cache',
            $adminhtmlEvents['controller_action_postdispatch_adminhtml_system_config_save'] ?? []
        );

        foreach (['global', 'frontend'] as $scope) {
            foreach ($reader->read($scope) as $eventName => $observers) {
                $this->assertArrayNotHasKey('auto_flush_cache', $observers, $scope . ': ' . $eventName);
            }
        }
    }

    public function testDoesNothingWithoutAclPermission(): void
    {
        $cacheTypeList = $this->createMock(TypeListInterface::class);
        $cacheTypeList->expects($this->never())->method('cleanType');

        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects($this->once())
            ->method('isAllowed')
            ->with('Magento_Backend::refresh_cache_type')
            ->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');

        $this->createObserver($cacheTypeList, $authorization, State::MODE_DEVELOPER, $logger)
            ->execute(new Observer());
    }

    /**
     * @dataProvider getNonDeveloperModes
     */
    public function testDoesNothingOutsideDeveloperMode(string $mode): void
    {
        $cacheTypeList = $this->createMock(TypeListInterface::class);
        $cacheTypeList->expects($this->never())->method('cleanType');

        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(true);

        $this->createObserver($cacheTypeList, $authorization, $mode)->execute(new Observer());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function getNonDeveloperModes(): array
    {
        return [
            'production' => [State::MODE_PRODUCTION],
            'default' => [State::MODE_DEFAULT],
        ];
    }

    public function testCleansOnlyConfiguredCacheTypesAndLogsAdminUser(): void
    {
        $cleanedTypes = [];
        $cacheTypeList = $this->createMock(TypeListInterface::class);
        $cacheTypeList->expects($this->exactly(3))
            ->method('cleanType')
            ->willReturnCallback(function (string $type) use (&$cleanedTypes): void {
                $cleanedTypes[] = $type;
            });

        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(true);

        $user = Bootstrap::getObjectManager()->create(User::class);
        $user->setUserName('jdoe');
        $authSession = Bootstrap::getObjectManager()->create(AuthSession::class);
        $authSession->setUser($user);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Yireo_AutoFlushCache'),
                ['types' => ['config', 'block_html', 'full_page'], 'admin_user' => 'jdoe']
            );

        $this->createObserver($cacheTypeList, $authorization, State::MODE_DEVELOPER, $logger, $authSession)
            ->execute(new Observer());

        $this->assertSame(['config', 'block_html', 'full_page'], $cleanedTypes);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoCache block_html enabled
     */
    public function testConfigSaveEventCleansCacheTypesWithoutFlushingCacheStorage(): void
    {
        $this->registerAllowedObserver();
        $this->saveCacheEntries();

        Bootstrap::getObjectManager()->get(EventManagerInterface::class)->dispatch('admin_system_config_save');

        $this->assertFalse(Bootstrap::getObjectManager()->get(BlockCache::class)->load(self::BLOCK_CACHE_ID));
        $this->assertSame(
            'other',
            Bootstrap::getObjectManager()->get(CacheInterface::class)->load(self::OTHER_CACHE_ID)
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoCache block_html enabled
     */
    public function testConfigSavePostDispatchEventNoLongerCleansCache(): void
    {
        $this->registerAllowedObserver();
        $this->saveCacheEntries();

        Bootstrap::getObjectManager()->get(EventManagerInterface::class)
            ->dispatch('controller_action_postdispatch_adminhtml_system_config_save');

        $this->assertSame('block', Bootstrap::getObjectManager()->get(BlockCache::class)->load(self::BLOCK_CACHE_ID));
    }

    private function registerAllowedObserver(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->assertInstanceOf(ObjectManager::class, $objectManager);

        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(true);

        $observer = $this->createObserver(
            $objectManager->get(TypeListInterface::class),
            $authorization,
            State::MODE_DEVELOPER
        );
        $objectManager->addSharedInstance($observer, FlushCache::class);
    }

    private function saveCacheEntries(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->get(BlockCache::class)->save('block', self::BLOCK_CACHE_ID);
        $objectManager->get(CacheInterface::class)->save('other', self::OTHER_CACHE_ID, [self::OTHER_CACHE_TAG]);

        $this->assertSame('block', $objectManager->get(BlockCache::class)->load(self::BLOCK_CACHE_ID));
        $this->assertSame('other', $objectManager->get(CacheInterface::class)->load(self::OTHER_CACHE_ID));
    }

    private function createObserver(
        TypeListInterface $cacheTypeList,
        AuthorizationInterface $authorization,
        string $mode,
        ?LoggerInterface $logger = null,
        ?AuthSession $authSession = null
    ): FlushCache {
        /** @var State&MockObject $appState */
        $appState = $this->createMock(State::class);
        $appState->method('getMode')->willReturn($mode);

        return new FlushCache(
            $cacheTypeList,
            $authorization,
            $appState,
            $authSession ?? Bootstrap::getObjectManager()->create(AuthSession::class),
            $logger ?? $this->createMock(LoggerInterface::class)
        );
    }
}
