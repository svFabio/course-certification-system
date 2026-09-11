<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Group;
use App\Models\Preinscription;

class PreinscriptionService
{
    public function __construct()
    {
    }

    // TODO: Implement register — create preinscription, validate capacity
    public function register(array $data): Preinscription
    {
        throw new \RuntimeException('TODO: Implement register');
    }

    // TODO: Implement evaluateCupoMinimo — check if group meets MIN_GROUP_CAPACITY
    public function evaluateCupoMinimo(Group $group): bool
    {
        throw new \RuntimeException('TODO: Implement evaluateCupoMinimo');
    }

    // TODO: Implement getAvailableGroups — return groups with available capacity
    public function getAvailableGroups(int $courseId)
    {
        throw new \RuntimeException('TODO: Implement getAvailableGroups');
    }

    // TODO: Implement processPaymentConfirmation — confirm payment, update status
    public function processPaymentConfirmation(Preinscription $preinscription): Preinscription
    {
        throw new \RuntimeException('TODO: Implement processPaymentConfirmation');
    }
}
