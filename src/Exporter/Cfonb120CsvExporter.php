<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Exporter;

class Cfonb120CsvExporter extends AbstractCfonbCsvExporter
{
    protected function getRows(array $data): iterable
    {
        yield [
            'date' => $data['oldBalance']['date'],
            'amount' => $data['oldBalance']['amount'],
            'label' => 'Solde initial',
        ];

        foreach ($data['operations'] as $operation) {
            yield [
                'date' => $operation['date'],
                'amount' => $operation['amount'],
                'label' => $operation['label'],
                ...array_map(fn (array $metadatum) => $metadatum['value'], $operation['metadata'] ?? []),
            ];
        }

        yield [
            'date' => $data['newBalance']['date'] ?? null,
            'amount' => $data['newBalance']['amount'] ?? null,
            'label' => 'Solde final',
        ];
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
