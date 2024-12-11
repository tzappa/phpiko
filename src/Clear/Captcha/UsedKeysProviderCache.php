<?php

declare(strict_types=1);

namespace Clear\Captcha;

use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;

final class UsedKeysProviderCache implements UsedKeysProviderInterface
{
    /**
     * The cache pool instance.
     *
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    private $cachePool;

    /**
     * Sets a cache pool.
     *
     * @param \Psr\Cache\CacheItemPoolInterface $cachePool
     */
    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    /**
     * {@inheritDoc}
     */
    public function add(string $id, int $expiresAfter): bool
    {
        // check we have the key
        $item =$this->cachePool->getItem(sha1($id));
        if ($item->isHit()) {
            return false;
        }

        $item->set('*')->expiresAfter($expiresAfter);
        $this->cachePool->save($item);

        return true;
    }
}
