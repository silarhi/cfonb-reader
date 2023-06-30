<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Validator\Constraints;

use App\Cfonb\CfonbManager;
use App\Exception\DataNotReadableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FileTypeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CfonbManager $cfonbManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array{file: UploadedFile, type: string|null} $value
     * @param FileType                                     $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var UploadedFile $file */
        $file = $value['file'];
        $type = $value['type'] ?? $this->cfonbManager->guessTypeFromContent($file->getContent());

        if (null === $type) {
            $this
                ->context
                ->buildViolation($constraint->noTypeGuessedMessage)
                ->atPath('type')
                ->addViolation();

            return;
        }

        try {
            $this->cfonbManager->getData($file->getContent(), $type);
        } catch (DataNotReadableException $exception) {
            $this->logger->info('CFONB data is not readable', [
                'exception' => $exception,
            ]);

            $this
                ->context
                ->buildViolation($constraint->notValidContentMessage)
                ->addViolation();
        }
    }
}
