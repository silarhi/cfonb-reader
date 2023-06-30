<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

$header = <<<'EOF'
This file is part of CFONB Reader.
Copyright (c) 2023 - present SILARHI - All Rights Reserved
Unauthorized copying of this file, via any medium is strictly prohibited
Proprietary and confidential
Developed by SILARHI <dev@silarhi.fr>
EOF;

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/config',
        __DIR__.'/src',
    ])
    ->append([
        __FILE__,
        __DIR__.'/rector.php',
    ])
;

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => ['import_classes' => true],
        'header_comment' => ['header' => $header],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/.php-cs-fixer.cache');
