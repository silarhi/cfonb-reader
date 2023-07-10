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
            'date' => $data['oldBalance']['date'] ?? null,
            'amount' => $data['oldBalance']['amount'] ?? null,
            'debit' => isset($data['oldBalance']['amount']) && $data['oldBalance']['amount'] < 0.0 ? abs($data['oldBalance']['amount']) : null,
            'credit' => isset($data['oldBalance']['amount']) && $data['oldBalance']['amount'] >= 0.0 ? $data['oldBalance']['amount'] : null,
            'label' => 'Solde initial',
        ];

        foreach ($data['operations'] as $operation) {
            $labelParts = [
                $operation['label'],
                ...array_map(fn (array $metadatum) => trim($metadatum['value']), $operation['metadata'] ?? []),
            ];
            yield [
                'date' => $operation['date'],
                'debit' => $operation['amount'] < 0.0 ? abs($operation['amount']) : null,
                'credit' => $operation['amount'] >= 0.0 ? $operation['amount'] : null,
                'label' => $operation['label'],
                'fullLabel' => implode(' ', $labelParts),
                ...array_map(fn (array $metadatum) => trim($metadatum['value']), $operation['metadata'] ?? []),
            ];
        }

        yield [
            'date' => $data['newBalance']['date'] ?? null,
            'amount' => $data['newBalance']['amount'] ?? null,
            'debit' => isset($data['newBalance']['amount']) && $data['newBalance']['amount'] < 0.0 ? abs($data['newBalance']['amount']) : null,
            'credit' => isset($data['newBalance']['amount']) && $data['newBalance']['amount'] >= 0.0 ? $data['newBalance']['amount'] : null,
            'label' => 'Solde final',
        ];
    }

    protected function getHeaders(array $metadataList): array
    {
        return [
            'date' => 'Date',
            'amount' => 'Montant',
            'debit' => 'Débit',
            'credit' => 'Crédit',
            'label' => 'Libellé',
            'fullLabel' => 'Libellé concaténé',
            ...$metadataList,
        ];
    }
}
