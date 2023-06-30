<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Exporter;

use App\Manager\TemporyFilesManager;
use DateTimeInterface;

abstract class AbstractCfonbCsvExporter
{
    public function __construct(
        protected TemporyFilesManager $temporyFilesManager
    ) {
    }

    public function export(array $data, array $metadataList): string
    {
        $tempFile = $this->temporyFilesManager->create();
        $file = fopen($tempFile, 'w+');

        $headers = $this->getHeaders($metadataList);
        $BOM = "\xEF\xBB\xBF"; // UTF-8 BOM
        fwrite($file, $BOM);
        fputcsv($file, $headers, ';');

        $rows = $this->getRows($data);
        foreach ($rows as $row) {
            fputcsv($file, $this->getColsFromRow($headers, $row), ';');
        }

        fclose($file);

        return $tempFile;
    }

    abstract protected function getHeaders(array $metadataList): array;

    abstract protected function getRows(array $data): iterable;

    private function getColsFromRow(array $headers, array $row): array
    {
        $cols = [];
        foreach (array_keys($headers) as $headerKey) {
            $value = $row[$headerKey] ?? null;
            if ($value instanceof DateTimeInterface) {
                $value = $value->format('d/m/Y');
            }

            $cols[] = $value;
        }

        return $cols;
    }
}
