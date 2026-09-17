<?php

declare(strict_types=1);

namespace App\Enums;

enum PreinscriptionStatus: string
{
    case PENDIENTE_PAGO = 'pendiente_pago';
    case INSCRITO = 'inscrito';
    case RETIRADO = 'retirado';
    case RECHAZADO = 'rechazado';
}
