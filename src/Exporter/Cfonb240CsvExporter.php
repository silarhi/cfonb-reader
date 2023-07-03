<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Exporter;

class Cfonb240CsvExporter extends AbstractCfonbCsvExporter
{
    protected function getRows(array $data): iterable
    {
        foreach ($data['transactions'] as $transaction) {
            yield [
                'date' => $transaction['date'],
                'amount' => $transaction['amount'],
                'label' => $transaction['label'],
                ...array_map(fn (array $metadatum) => $metadatum['value'], $transaction['metadata'] ?? []),
            ];
        }
    }

    protected function getHeaders(array $metadataList): array
    {
        return [
            'date' => 'Date',
            'amount' => 'Montant',
            'label' => 'Libellé',
            ...$metadataList,
        ];
    }
}
