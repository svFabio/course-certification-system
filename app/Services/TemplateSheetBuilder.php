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
        $rows[] = ['Nro', 'CI', 'APE_PAT', 'APE_MAT', 'NOMBRES', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10', 'TOTAL ASIST', 'CRITERIO 1', 'CRITERIO 2', 'NOTA FINAL'];
        $rows[] = ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '50%', '30%', '70%', ''];

        return $rows;
    }
}
