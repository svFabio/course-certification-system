<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use App\Models\Group;
use App\Support\DesignTokens;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinancialReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private readonly ?Course $course = null,
    ) {}

    public function title(): string
    {
        return $this->course !== null
            ? 'Financiero - '.mb_substr($this->course->nombre, 0, 20)
            : 'Reporte Financiero General';
    }

    public function array(): array
    {
        $rows = [];

        // Header institucional
        $rows[] = ['UNIVERSIDAD MAYOR DE SAN SIMÓN'];
        $rows[] = ['DEPARTAMENTO DE INFORMÁTICA Y SISTEMAS - FORMACIÓN CONTINUA'];
        $rows[] = ['REPORTE FINANCIERO Y CONCILIACIÓN DE CAJA'];
        $rows[] = [''];

        if ($this->course !== null) {
            $rows[] = ['Curso:', $this->course->nombre, 'Carga Horaria:', $this->course->carga_horaria.' hrs'];
            $groups = $this->course->groups()->with('preinscriptions.payments')->get();
        } else {
            $rows[] = ['Alcance:', 'Consolidado General de Cursos', 'Fecha Emisión:', now()->format('d/m/Y H:i')];
            $groups = Group::with(['course', 'preinscriptions.payments'])->get();
        }
        $rows[] = ['Fecha Generación:', now()->format('d/m/Y H:i')];
        $rows[] = [''];

        // Encabezado de tabla
        $rows[] = [
            'Curso',
            'Grupo',
            'Inscritos Pagados',
            'Total Recaudado (Bs.)',
            'Efectivo (Bs.)',
            'QR (Bs.)',
            'Reembolsos (Bs.)',
            'Total Neto (Bs.)',
        ];

        $totalRecaudadoGeneral = 0.0;
        $totalEfectivoGeneral = 0.0;
        $totalQrGeneral = 0.0;
        $totalReembolsadoGeneral = 0.0;
        $totalNetoGeneral = 0.0;
        $totalInscritosGeneral = 0;

        foreach ($groups as $group) {
            $courseName = $group->course?->nombre ?? 'N/A';
            $groupName = $group->nombre;

            $payments = $group->preinscriptions->flatMap->payments;

            $verifiedPayments = $payments->where('estado', PaymentStatus::VERIFICADO);
            $refundedPayments = $payments->where('estado', PaymentStatus::REEMBOLSADO);

            $efectivo = (float) $verifiedPayments->where('metodo', PaymentMethod::EFECTIVO)->sum('monto');
            $qr = (float) $verifiedPayments->where('metodo', PaymentMethod::QR)->sum('monto');

            $recaudado = $efectivo + $qr;
            $reembolsos = (float) $refundedPayments->sum('monto');
            $neto = $recaudado - $reembolsos;
            $inscritosCount = $group->preinscriptions->where('status', PreinscriptionStatus::INSCRITO)->count();

            $totalInscritosGeneral += $inscritosCount;
            $totalRecaudadoGeneral += $recaudado;
            $totalEfectivoGeneral += $efectivo;
            $totalQrGeneral += $qr;
            $totalReembolsadoGeneral += $reembolsos;
            $totalNetoGeneral += $neto;

            $rows[] = [
                $courseName,
                $groupName,
                $inscritosCount,
                number_format($recaudado, 2, '.', ''),
                number_format($efectivo, 2, '.', ''),
                number_format($qr, 2, '.', ''),
                number_format($reembolsos, 2, '.', ''),
                number_format($neto, 2, '.', ''),
            ];
        }

        // Fila de totales
        $rows[] = [
            'TOTAL GENERAL',
            '',
            $totalInscritosGeneral,
            number_format($totalRecaudadoGeneral, 2, '.', ''),
            number_format($totalEfectivoGeneral, 2, '.', ''),
            number_format($totalQrGeneral, 2, '.', ''),
            number_format($totalReembolsadoGeneral, 2, '.', ''),
            number_format($totalNetoGeneral, 2, '.', ''),
        ];

        // Distribución institucional de ingresos netos (HU-19 Criterio 2: 20% Facultad, 80% Lab y Docente)
        $retencionFacultad = $totalNetoGeneral * 0.20;
        $presupuestoDocenteLab = $totalNetoGeneral * 0.80;

        $rows[] = [''];
        $rows[] = ['DISTRIBUCIÓN INSTITUCIONAL (RECAUDACIÓN NETA)'];
        $rows[] = ['Retención Facultativa Institucional (20%):', '', '', '', '', '', '', number_format($retencionFacultad, 2, '.', '')];
        $rows[] = ['Presupuesto Laboratorio y Honorarios Docentes (80%):', '', '', '', '', '', '', number_format($presupuestoDocenteLab, 2, '.', '')];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        $navy = ltrim(DesignTokens::NAVY, '#');
        $green = ltrim(DesignTokens::GREEN, '#');
        $white = ltrim(DesignTokens::WHITE, '#');
        $greenLight = ltrim(DesignTokens::GREEN_LIGHT, '#');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $navy]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $navy]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $green]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            8 => [
                'font' => ['bold' => true, 'color' => ['rgb' => $white]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $navy],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            $lastRow => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $navy]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $greenLight],
                ],
            ],
        ];
    }
}
