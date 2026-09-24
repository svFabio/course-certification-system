<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Preinscription;
use App\Services\BoletaService;
use Symfony\Component\HttpFoundation\Response;

class BoletaController extends Controller
{
    public function __invoke(Preinscription $preinscription, BoletaService $service): Response
    {
        return $service->download($preinscription);
    }
}
