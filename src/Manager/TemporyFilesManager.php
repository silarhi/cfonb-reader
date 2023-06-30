<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Manager;

use RuntimeException;

class TemporyFilesManager
{
    /**
     * @var resource[]
     */
    private array $temporaryResources = [];

    public function __construct()
    {
        register_shutdown_function([$this, 'removeTemporaryFiles']);
    }

    public function __destruct()
    {
        $this->removeTemporaryFiles();
    }

    public function create(): string
    {
        $tmpfile = tmpfile();

        // We need to store resource in order to avoid garbage collector closing pointer
        $this->temporaryResources[] = $tmpfile;

        return stream_get_meta_data($tmpfile)['uri'];
    }

    /**
     * @param resource $stream
     */
    public function createFromStream($stream): string
    {
        $tmpfile = tmpfile();

        // We need to store resource in order to avoid garbage collector closing pointer
        $this->temporaryResources[] = $tmpfile;
        $tmpfilePath = stream_get_meta_data($tmpfile)['uri'];

        $pos = ftell($stream);

        try {
            if (!rewind($stream)) {
                throw new RuntimeException('Failed to rewind the stream');
            }

            stream_copy_to_stream($stream, $tmpfile);
        } finally {
            fseek($stream, $pos);
        }

        return $tmpfilePath;
    }

    /**
     * Removes all temporary files.
     */
    public function removeTemporaryFiles(): void
    {
        foreach ($this->temporaryResources as $temporaryResource) {
            unset($temporaryResource); // Call GC
        }
        $this->temporaryResources = [];
    }
}
