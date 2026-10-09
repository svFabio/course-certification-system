<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificateType;
use App\Enums\PreinscriptionStatus;
use App\Enums\SignatureStatus;
use App\Enums\TipoParticipante;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Preinscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCertificateSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::where('email', 'student@umss.edu.bo')->first();
        $course = Course::with('groups')->first();

        if (! $student || ! $course || $course->groups->isEmpty()) {
            return;
        }

        $group = $course->groups->first();

        $preinscription = Preinscription::query()
            ->where('group_id', $group->id)
            ->where('email', $student->email)
            ->first();

        if ($preinscription === null) {
            $preinscription = Preinscription::create([
                'group_id' => $group->id,
                'ci' => fake()->unique()->numerify('#######'),
                'email' => $student->email,
                'nombres' => 'Estudiante',
                'apellido_paterno' => 'Demo',
                'apellido_materno' => null,
                'celular' => fake()->numerify('7#######'),
                'tipo_participante' => TipoParticipante::UMSS->value,
                'status' => PreinscriptionStatus::INSCRITO,
            ]);
        } else {
            $preinscription->update([
                'status' => PreinscriptionStatus::INSCRITO,
            ]);
        }

        Certificate::updateOrCreate(
            ['codigo_unico' => 'CERT-2026-DEMOHU31'],
            [
                'preinscription_id' => $preinscription->id,
                'course_id' => $course->id,
                'tipo' => CertificateType::APROBACION,
                'signature_status' => SignatureStatus::LISTO,
                'emitido_en' => now(),
            ]
        );
    }
}
