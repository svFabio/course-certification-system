<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use App\Models\Preinscription;
use App\Services\EvaluationService;
use App\Support\DesignTokens;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourseClosingExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private readonly Course $course,
    ) {}

    public function title(): string
    {
        $clean = preg_replace('/[\\\\\\/?*[\\]:]/', '', $this->course->nombre);

        return mb_substr('Cierre - '.$clean, 0, 31);
    }

    public function array(): array
    {
        $evaluationService = app(EvaluationService::class);

        $this->course->loadMissing([
            'instructor',
            'groups.sessions',
            'groups.preinscriptions.grades.evaluationCriteria',
            'groups.preinscriptions.certificate',
        ]);

        $rows = [];

        // Header institucional
        $rows[] = ['UNIVERSIDAD MAYOR DE SAN SIMÓN'];
        $rows[] = ['DEPARTAMENTO DE INFORMÁTICA Y SISTEMAS - FORMACIÓN CONTINUA'];
        $rows[] = ['ACTA DE CIERRE Y CALIFICACIONES DEL CURSO'];
        $rows[] = [''];

        // Metadatos del curso
        $instructorName = $this->course->instructor?->name ?? 'Sin asignar';
        $rows[] = ['Curso:', $this->course->nombre, '', 'Docente:', $instructorName];
        $rows[] = ['Carga Horaria:', $this->course->carga_horaria.' horas', '', 'Período:', $this->course->periodo ?? 'N/A'];
        $rows[] = ['Estado del Curso:', $this->course->status?->value ?? '', '', 'Fecha Emisión:', now()->format('d/m/Y H:i')];
        $rows[] = [''];

        // Obtener todas las preinscripciones inscritas
        $preinscriptions = Preinscription::query()
            ->whereHas('group', fn ($q) => $q->where('course_id', $this->course->id))
            ->where('status', PreinscriptionStatus::INSCRITO)
            ->with(['group.sessions', 'grades.evaluationCriteria', 'certificate'])
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get();

        $totalInscritos = $preinscriptions->count();
        $aprobados = 0;
        $reprobados = 0;

        $studentRows = [];
        $index = 1;

        foreach ($preinscriptions as $pre) {
            $breakdown = $evaluationService->breakdown($pre);
            $finalGrade = $breakdown['final_grade'];
            $passed = $breakdown['passed'];

            if ($passed) {
                $aprobados++;
            } else {
                $reprobados++;
            }

            $certCode = $pre->certificate?->codigo_unico ?? ($passed ? 'En trámite' : 'No aplica');

            $studentRows[] = [
                $index++,
                $pre->ci,
                $pre->cod_sis ?? '—',
                $pre->apellido_paterno.' '.($pre->apellido_materno ?? '').' '.$pre->nombres,
                $pre->group?->nombre ?? '—',
                mb_strtoupper((string) ($pre->tipo_participante?->value ?? $pre->tipo_participante)),
                $breakdown['attendance']['weighted'],
                round($finalGrade - $breakdown['attendance']['weighted'], 2),
                $finalGrade,
                $passed ? 'APROBADO' : 'REPROBADO',
                $certCode,
            ];
        }

        $tasaAprobacion = $totalInscritos > 0 ? round(($aprobados / $totalInscritos) * 100, 1) : 0.0;

        // Resumen
        $rows[] = ['RESUMEN ESTADÍSTICO'];
        $rows[] = ['Total Participantes:', $totalInscritos, 'Aprobados:', $aprobados, 'Reprobados:', $reprobados, '% Aprobación:', $tasaAprobacion.'%'];
        $rows[] = [''];

        // Cabecera de la tabla
        $rows[] = [
            '#',
            'C.I.',
            'Cód. SIS',
            'Apellidos y Nombres',
            'Grupo',
            'Tipo',
            'Ptos. Asistencia',
            'Ptos. Criterios',
            'Nota Final',
            'Condición',
            'Código Certificado',
        ];

        foreach ($studentRows as $sRow) {
            $rows[] = $sRow;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $navy = ltrim(DesignTokens::NAVY, '#');
        $red = ltrim(DesignTokens::RED, '#');
        $white = ltrim(DesignTokens::WHITE, '#');
        $gray100 = ltrim(DesignTokens::GRAY_100, '#');

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
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $red]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            9 => [
                'font' => ['bold' => true, 'color' => ['rgb' => $navy]],
            ],
            10 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $gray100],
                ],
            ],
            12 => [
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
