<?php

namespace App\Enums;

enum MissingServiceRequirement: string
{
    case IDENTITY = 'identity';
    case TERRITORY = 'territory';
    case IDENTITY_AND_TERRITORY = 'identity_and_territory';
    case AUTHORIZATION = 'authorization';
}
