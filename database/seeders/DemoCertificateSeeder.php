<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
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

        $preinscription = Preinscription::firstOrCreate(
            [
                'group_id' => $group->id,
                'ci' => '9335660',
            ],
            [
                'email' => $student->email,
                'nombres' => 'Estudiante',
                'apellido_paterno' => 'Demo',
                'apellido_materno' => null,
                'celular' => '71234567',
                'tipo_participante' => 'umss',
                'status' => 'inscrito',
            ]
        );

        if ($preinscription->email !== $student->email) {
            $preinscription->update(['email' => $student->email]);
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
