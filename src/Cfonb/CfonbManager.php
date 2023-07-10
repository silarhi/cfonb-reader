<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Cfonb;

use App\Exception\DataNotReadableException;
use Silarhi\Cfonb\Banking\Operation;
use Silarhi\Cfonb\Banking\Transaction;
use Silarhi\Cfonb\Banking\Transfer;
use Silarhi\Cfonb\CfonbReader;
use Silarhi\Cfonb\Exceptions\ParseException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CfonbManager
{
    final public const TYPE_120 = 'cfonb_120';
    final public const TYPE_240 = 'cfonb_240';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function guessTypeFromContent(string $content): ?string
    {
        $content = str_replace("\r\n", "\n", $content);
        if ('' === $content) {
            return null;
        }

        $lengthByTypes = [
            self::TYPE_120 => 120,
            self::TYPE_240 => 240,
        ];

        foreach ($lengthByTypes as $type => $length) {
            if (0 === strlen($content) % $length || "\n" === substr($content, $length, 1)) {
                return $type;
            }
        }

        return null;
    }

    public function getData(string $content, string $type): array
    {
        $data = [];
        $reader = new CfonbReader();

        try {
            switch ($type) {
                case self::TYPE_120:
                    $data = $this->getCfonb120Data($content, $reader);
                    break;
                case self::TYPE_240:
                    $data = $this->getCfonb240Data($content, $reader);
                    break;
            }
        } catch (ParseException $e) {
            throw new DataNotReadableException('CFONB parsing exception', $e->getCode(), $e);
        }

        return $data;
    }

    public function getAllCfonb120Metadata(): array
    {
        $metadata = [];
        $zonesByQualifier = $this->getOperationMetadataZones();
        foreach ($zonesByQualifier as $qualifier => $zones) {
            foreach (array_keys($zones) as $i) {
                $key = sprintf('%s.%d', $qualifier, $i + 1);
                $metadata[$key] = $this->getMetadataName($qualifier, $i + 1);
            }
        }

        return $metadata;
    }

    private function getCfonb120Data(string $content, CfonbReader $reader): array
    {
        $content = str_replace("\r\n", "\n", $content);

        $data = [
            'oldBalance' => null,
            'operations' => [],
            'newBalance' => null,
        ];
        foreach ($reader->parseCfonb120($content) as $statement) {
            $data['oldBalance'] ??= [
                'date' => $statement->getOldBalance()->getDate(),
                'amount' => $statement->getOldBalance()->getAmount(),
            ];
            $data['newBalance'] = [
                'date' => $statement->getNewBalance()->getDate(),
                'amount' => $statement->getNewBalance()->getAmount(),
            ];

            foreach ($statement->getOperations() as $operation) {
                $data['operations'][] = [
                    'label' => $operation->getLabel(),
                    'date' => $operation->getDate(),
                    'amount' => $operation->getAmount(),
                    'metadata' => $this->getCfonb120Metadata($operation),
                ];
            }
        }

        return $data;
    }

    private function getCfonb240Data(string $content, CfonbReader $reader): array
    {
        $data = [
            'transactions' => [],
        ];

        foreach ($reader->parseCfonb240($content) as $transfer) {
            assert($transfer instanceof Transfer);

            foreach ($transfer->getTransactions() as $transaction) {
                $data['transactions'][] = [
                    'date' => $transaction->getSettlementDate(),
                    'amount' => $transaction->getTransactionAmount(),
                    'label' => $transaction->getDescription(),
                    'metadata' => $this->getCfonb240Metadata($transaction),
                ];
            }
        }

        return $data;
    }

    private function getOperationMetadataZones(): array
    {
        return [
            ...array_fill_keys(['LIB', 'NPY', 'NBE', 'NPO', 'NBU', 'LCC', 'LCC2', 'LCS', 'CBE', 'CPY'], [
                [0, 70],
            ]),
            ...array_fill_keys(['MMO'], [
                [0, 35],
                [35, 35],
                [4, 14],
                [18, 2],
                [20, 11],
                [31, 39],
            ]),
            ...array_fill_keys(['RUM'], [
                [0, 35],
                [35, 4],
            ]),
            ...array_fill_keys(['IPY', 'IBE', 'IPO', 'IBU', 'RCN', 'REF'], [
                [0, 35],
                [35, 35],
            ]),
        ];
    }

    private function getCfonb120Metadata(Operation $operation): array
    {
        $defaultZone = [0, null];
        $allZones = $this->getOperationMetadataZones();
        $details = [];
        foreach ($operation->getDetails() as $detail) {
            $qualifier = $detail->getQualifier();
            $zones = $allZones[$qualifier] ?? [$defaultZone];

            foreach ($zones as $i => $zone) {
                $start = $zone[0];
                $length = $zone[1];
                $value = substr($detail->getAdditionalInformations(), $start, $length);

                if (strlen($value) > 0 && $this->hasMetadataName($qualifier, $i + 1)) {
                    $key = sprintf('%s.%d', $qualifier, $i + 1);
                    $details[$key] = [
                        'name' => $this->getMetadataName($qualifier, $i + 1),
                        'value' => $value,
                    ];
                }
            }
        }

        return $details;
    }

    private function getMetadataName(string $qualifier, int $index): string
    {
        $labelKey = sprintf('cfonb.qualifier.%s.%d', $qualifier, $index);
        $labelValue = $this->translator->trans($labelKey, [], 'cfonb');

        return $labelValue === $labelKey
            ? sprintf('%s - %d', $qualifier, $index)
            : $labelValue;
    }

    private function hasMetadataName(string $qualifier, int $index): bool
    {
        $labelKey = sprintf('cfonb.qualifier.%s.%d', $qualifier, $index);
        $labelValue = $this->translator->trans($labelKey, [], 'cfonb');

        return $labelValue !== $labelKey;
    }

    public function getAllCfonb240Metadata(): array
    {
        $valueNames = [
            'sequenceNumber',
            'nationalIssuerNumber',
            'recipientName1',
            'recipientName2',
            'recipientAccountNumber',
            'recipientAccountNumber2',
            'recipientBankCode1',
            'recipientBankCode2',
            'presenterReference',
            'curIndex',
            'initialTransactionSettlementDate',
            'initialOperationPresenterReference',
            'operationCode',
        ];

        $metadata = [];
        foreach ($valueNames as $name) {
            $metadata[$name] = $this->translator->trans(sprintf('transaction.%s', $name), [], 'cfonb');
        }

        return $metadata;
    }

    private function getCfonb240Metadata(Transaction $transaction): array
    {
        return [
            'sequenceNumber' => [
                'name' => $this->translator->trans('transaction.sequenceNumber', [], 'cfonb'),
                'value' => $transaction->getSequenceNumber(),
            ],
            'nationalIssuerNumber' => [
                'name' => $this->translator->trans('transaction.nationalIssuerNumber', [], 'cfonb'),
                'value' => $transaction->getNationalIssuerNumber(),
            ],
            'recipientName1' => [
                'name' => $this->translator->trans('transaction.recipientName1', [], 'cfonb'),
                'value' => $transaction->getRecipientName1(),
            ],
            'recipientName2' => [
                'name' => $this->translator->trans('transaction.recipientName2', [], 'cfonb'),
                'value' => $transaction->getRecipientName2(),
            ],
            'recipientAccountNumber' => [
                'name' => $this->translator->trans('transaction.recipientAccountNumber', [], 'cfonb'),
                'value' => $transaction->getRecipientAccountNumber1(),
            ],
            'recipientAccountNumber2' => [
                'name' => $this->translator->trans('transaction.recipientAccountNumber2', [], 'cfonb'),
                'value' => $transaction->getRecipientAccountNumber2(),
            ],
            'recipientBankCode1' => [
                'name' => $this->translator->trans('transaction.recipientBankCode1', [], 'cfonb'),
                'value' => $transaction->getRecipientBankCode1(),
            ],
            'recipientBankCode2' => [
                'name' => $this->translator->trans('transaction.recipientBankCode2', [], 'cfonb'),
                'value' => $transaction->getRecipientBankCode2(),
            ],
            'presenterReference' => [
                'name' => $this->translator->trans('transaction.presenterReference', [], 'cfonb'),
                'value' => $transaction->getPresenterReference(),
            ],
            'curIndex' => [
                'name' => $this->translator->trans('transaction.curIndex', [], 'cfonb'),
                'value' => $transaction->getCurIndex(),
            ],
            'initialTransactionSettlementDate' => [
                'name' => $this->translator->trans('transaction.initialTransactionSettlementDate', [], 'cfonb'),
                'value' => $transaction->getInitialTransactionSettlementDate()->format('d/m/Y'),
            ],
            'initialOperationPresenterReference' => [
                'name' => $this->translator->trans('transaction.initialOperationPresenterReference', [], 'cfonb'),
                'value' => $transaction->getInitialOperationPresenterReference(),
            ],
            'operationCode' => [
                'name' => $this->translator->trans('transaction.operationCode', [], 'cfonb'),
                'value' => $transaction->getOperationCode(),
            ],
        ];
    }
}
