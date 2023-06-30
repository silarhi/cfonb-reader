<?php

declare(strict_types=1);


use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonyLevelSetList;

return static function (RectorConfig $config): void {
    $config->importShortClasses();
    $config->importNames();

    $config->paths([
        __DIR__ . '/src',
    ]);

    $config->import(LevelSetList::UP_TO_PHP_81);
    $config->import(SymfonyLevelSetList::UP_TO_SYMFONY_62);
    $config->import(SetList::CODE_QUALITY);
};
