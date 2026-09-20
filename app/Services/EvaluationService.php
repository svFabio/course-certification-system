<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
use App\Models\EvaluationCriteria;
use App\Models\Grade;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function setCriteria(Course $course, array $criteria): void
    {
        $totalWeight = array_sum(array_column($criteria, 'ponderacion'));

        if ($totalWeight !== BusinessRules::EVALUATION_TOTAL_PERCENT) {
            throw ValidationException::withMessages([
                'criteria' => "Criteria weights must sum to 100%. Current sum: {$totalWeight}%.",
            ]);
        }

        DB::transaction(function () use ($course, $criteria) {
            $course->evaluationCriteria()->delete();

            foreach ($criteria as $item) {
                EvaluationCriteria::create([
                    'course_id' => $course->id,
                    'nombre' => $item['nombre'],
                    'ponderacion' => $item['ponderacion'],
                ]);
            }
        });
    }

    public function recordGrades(Preinscription $preinscription, array $grades): void
    {
        DB::transaction(function () use ($preinscription, $grades) {
            foreach ($grades as $criteriaId => $score) {
                Grade::updateOrCreate(
                    [
                        'preinscription_id' => $preinscription->id,
                        'evaluation_criteria_id' => $criteriaId,
                    ],
                    ['nota' => $score]
                );
            }
        });
    }

    public function calculateFinalGrade(Preinscription $preinscription): float
    {
        $grades = $preinscription->grades()->with('evaluationCriteria')->get();

        if ($grades->isEmpty()) {
            return 0.0;
        }

        $weighted = $grades->sum(
            fn ($grade) => ($grade->nota * $grade->evaluationCriteria->ponderacion) / 100
        );

        return round($weighted, 2);
    }

    public function getGradeReport(Preinscription $preinscription): array
    {
        $grades = $preinscription->grades()->with('evaluationCriteria')->get();
        $finalGrade = $this->calculateFinalGrade($preinscription);

        return [
            'preinscription_id' => $preinscription->id,
            'full_name' => $preinscription->full_name,
            'final_grade' => $finalGrade,
            'passed' => $finalGrade >= BusinessRules::MINIMUM_PASSING_GRADE,
            'criteria' => $grades->map(fn ($g) => [
                'name' => $g->evaluationCriteria->nombre,
                'weight' => $g->evaluationCriteria->ponderacion,
                'score' => $g->nota,
                'weighted' => round(($g->nota * $g->evaluationCriteria->ponderacion) / 100, 2),
            ])->toArray(),
        ];
    }
}
