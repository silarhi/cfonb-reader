<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Storage;

use App\Exception\DataNotFoundException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DataStorageHandler
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function store(array $data): string
    {
        $id = uniqid();
        $cacheKey = $this->getCacheKey($id);

        $this
            ->cache
            ->get($cacheKey, function (ItemInterface $item) use ($data) {
                $item->expiresAfter(3600);

                return $data;
            });

        return $id;
    }

    /**
     * @throws DataNotFoundException
     */
    public function get(string $id): array
    {
        $cacheKey = $this->getCacheKey($id);

        return $this->cache->get($cacheKey, fn () => throw new DataNotFoundException(sprintf('No data found for "%s"', $cacheKey)));
    }

    private function getCacheKey(string $id): string
    {
        return sprintf('data.%s', $id);
    }
}
