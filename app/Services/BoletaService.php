<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Preinscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;

class BoletaService
{
    public function correlative(Preinscription $preinscription): string
    {
        return sprintf('BOL-%d-%05d', now()->year, $preinscription->id);
    }

    public function generateForPreinscription(Preinscription $preinscription)
    {
        $payment = $this->resolveVerifiedPayment($preinscription);

        $preinscription->loadMissing('group.course');
        $group = $preinscription->group;
        $course = $group->course;

        $pdf = Pdf::loadView('pdf.boleta', [
            'boleta' => [
                'correlativo' => $this->correlative($preinscription),
                'emitida_en' => now(),
                'participante' => $preinscription->full_name,
                'ci' => $preinscription->ci,
                'cod_sis' => $preinscription->cod_sis,
                'tipo_participante' => match ($preinscription->tipo_participante) {
                    'umss' => 'Comunidad UMSS',
                    'externo' => 'Participante Externo',
                    'auxiliar' => 'Auxiliar de Docencia',
                    default => $preinscription->tipo_participante,
                },
                'curso' => $course->nombre,
                'carga_horaria' => $course->carga_horaria,
                'grupo' => $group->nombre,
                'aula' => $group->aula,
                'horario' => sprintf(
                    '%s - %s',
                    $group->hora_inicio->format('H:i'),
                    $group->hora_fin->format('H:i')
                ),
                'monto' => $payment->monto,
                'metodo' => $payment->metodo->value,
                'numero_comprobante' => $payment->numero_comprobante,
                'estado' => $payment->estado->getLabel(),
            ],
        ])->setPaper('a4');

        return $pdf;
    }

    public function download(Preinscription $preinscription)
    {
        $pdf = $this->generateForPreinscription($preinscription);

        return $pdf->download("{$this->correlative($preinscription)}.pdf");
    }

    private function resolveVerifiedPayment(Preinscription $preinscription): Payment
    {
        $payment = $preinscription->payments()
            ->whereIn('estado', [PaymentStatus::VERIFICADO->value, PaymentStatus::REEMBOLSADO->value])
            ->latest('id')
            ->first();

        if ($payment === null) {
            throw ValidationException::withMessages([
                'boleta' => 'No existe un pago verificado para emitir la boleta de inscripción.',
            ]);
        }

        return $payment;
    }
}
