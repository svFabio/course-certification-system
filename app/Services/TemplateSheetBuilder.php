<?php

declare(strict_types=1);

namespace App\Services;

class TemplateSheetBuilder
{
    public function buildCashTemplate(): array
    {
        $rows = [];
        $rows[] = ['PLANILLA DE CAJA / INSCRIPCIÓN'];
        $rows[] = ['Curso:', '', 'Período:'];
        $rows[] = ['Instructor:', '', 'Horario:'];
        $rows[] = ['Precio UMSS:', '', 'Precio Externo:', '', 'Precio Auxiliar:'];
        $rows[] = [];
        $rows[] = ['Nro', 'COD-SIS', 'CI', 'APE_PAT', 'APE_MAT', 'NOMBRES', 'CELULAR', 'MONTO FISICO', 'MONTO QR', 'OBS', 'FOTOCOPIA C.I.'];

        return $rows;
    }

    public function buildTeacherTemplate(): array
    {
        $rows = [];
        $rows[] = ['PLANILLA DOCENTE - NOTAS Y ASISTENCIA'];
        $rows[] = ['Curso:', '', 'Aula:', '', 'Período:'];
        $rows[] = ['Instructor:', '', 'Horario:'];
        $rows[] = [];
        $rows[] = ['Nro', 'CI', 'APE_PAT', 'APE_MAT', 'NOMBRES', 'DÍA 1', 'DÍA 2', 'DÍA 3', 'DÍA 4', 'DÍA 5', 'DÍA 6', 'DÍA 7', 'DÍA 8', 'DÍA 9', 'DÍA 10', 'Asistencia', 'Puntaje Asistencia S/ 5', 'CRITERIO 1', 'CRITERIO 2', 'NOTA FINAL'];
        $rows[] = ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '50%', '30%', '70%', '100'];

        return $rows;
    }
}
