<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use App\Support\DesignTokens;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CoursesGeneralExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $filters = [],
    ) {}

    public function title(): string
    {
        return 'Catálogo de Cursos';
    }

    public function array(): array
    {
        $query = Course::query()->with(['instructor', 'groups.preinscriptions']);

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['carga_horaria'])) {
            $query->where('carga_horaria', $this->filters['carga_horaria']);
        }

        $courses = $query->orderBy('created_at', 'desc')->get();

        $rows = [];

        // Header institucional
        $rows[] = ['UNIVERSIDAD MAYOR DE SAN SIMÓN'];
        $rows[] = ['DEPARTAMENTO DE INFORMÁTICA Y SISTEMAS - FORMACIÓN CONTINUA'];
        $rows[] = ['REPORTE GENERAL DE CURSOS'];
        $rows[] = ['Fecha Generación:', now()->format('d/m/Y H:i'), '', 'Total Cursos:', $courses->count()];
        $rows[] = [''];

        // Encabezados
        $rows[] = [
            'ID',
            'Curso',
            'Carga Horaria',
            'Nivel',
            'Período',
            'Estado',
            'Docente Asignado',
            'Precio UMSS (Bs.)',
            'Precio Externo (Bs.)',
            'Precio Auxiliar (Bs.)',
            'Cant. Grupos',
            'Preinscritos',
            'Inscritos Pagados',
        ];

        foreach ($courses as $c) {
            $allPreinscriptions = $c->groups->flatMap->preinscriptions;
            $totalPreinscritos = $allPreinscriptions->count();
            $totalInscritos = $allPreinscriptions->where('status', PreinscriptionStatus::INSCRITO)->count();

            $rows[] = [
                $c->id,
                $c->nombre,
                $c->carga_horaria.' hrs',
                $c->nivel?->value ?? $c->nivel ?? '—',
                $c->periodo ?? '—',
                $c->status?->value ?? '—',
                $c->instructor?->name ?? 'Sin asignar',
                $c->precio_umss,
                $c->precio_externo,
                $c->precio_auxiliar,
                $c->groups->count(),
                $totalPreinscritos,
                $totalInscritos,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $navy = ltrim(DesignTokens::NAVY, '#');
        $white = ltrim(DesignTokens::WHITE, '#');

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
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $navy]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            6 => [
                'font' => ['bold' => true, 'color' => ['rgb' => $white]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $navy],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
