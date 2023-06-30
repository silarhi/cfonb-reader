<?php

/*
 * This file is part of CFONB Reader.
 * Copyright (c) 2023 - present SILARHI - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Developed by SILARHI <dev@silarhi.fr>
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class FileType extends Constraint
{
    public string $noTypeGuessedMessage = 'Il est impossible de mettre ce fichier en mode automatique';
    public string $notValidContentMessage = "Nous n'avons pas réussi à récupérer les informations de votre fichier. Est-il bien formaté ?";
}
