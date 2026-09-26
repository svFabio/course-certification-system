<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\CertificateType;
use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\SignatureStatus;
use App\Enums\TipoParticipante;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\EvaluationCriteria;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use App\Support\BusinessRules;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CoursesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@umss.edu.bo')->first();
        $instructors = User::role('instructor')->get();

        if ($instructors->isEmpty()) {
            return;
        }

        $inst1 = $instructors[0]; // Adan Llanos
        $inst2 = $instructors[1] ?? $inst1; // Santos Flores
        $inst3 = $instructors[2] ?? $inst1; // Beatriz Murillo
        $inst4 = $instructors[3] ?? $inst1; // Carlos Vargas

        $coursesData = [
            [
                'nombre' => 'Mantenimiento y Reparación de Computadoras',
                'contenido' => 'Diagnóstico de hardware, ensamble de componentes, optimización de sistemas operativos y mantenimiento preventivo y correctivo de PCs y laptops.',
                'portada_path' => null,
                'carga_horaria' => '20',
                'nivel' => CourseLevel::BASICO,
                'periodo' => '1-2026',
                'status' => CourseStatus::PUBLICADO,
                'instructor_id' => $inst1->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo 1 (Mañana)',
                        'aula' => 'Laboratorio de Informática 1',
                        'hora_inicio' => '10:00',
                        'hora_fin' => '11:30',
                        'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
                        'cupo_maximo' => 25,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 18,
                    ],
                    [
                        'nombre' => 'Grupo 2 (Tarde)',
                        'aula' => 'Laboratorio de Redes',
                        'hora_inicio' => '14:15',
                        'hora_fin' => '15:45',
                        'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
                        'cupo_maximo' => 20,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 15,
                    ],
                ],
            ],
            [
                'nombre' => 'Programación con Scratch para Niños y Jóvenes',
                'contenido' => 'Fundamentos de lógica algorítmica, animación interactiva, estructuras condicionales y desarrollo de videojuegos didácticos con bloques visuales.',
                'portada_path' => null,
                'carga_horaria' => '20',
                'nivel' => CourseLevel::BASICO,
                'periodo' => '1-2026',
                'status' => CourseStatus::PUBLICADO,
                'instructor_id' => $inst2->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo 1',
                        'aula' => 'Laboratorio de Informática 2',
                        'hora_inicio' => '09:45',
                        'hora_fin' => '11:15',
                        'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
                        'cupo_maximo' => 30,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 22,
                    ],
                ],
            ],
            [
                'nombre' => 'Desarrollo de Aplicaciones Web con Laravel y Livewire',
                'contenido' => 'Arquitectura MVC, Eloquent ORM avanzado, Livewire 3 reactivo, diseño con Tailwind CSS y generación de APIs REST seguras.',
                'portada_path' => null,
                'carga_horaria' => '30',
                'nivel' => CourseLevel::INTERMEDIO,
                'periodo' => '1-2026',
                'status' => CourseStatus::PUBLICADO,
                'instructor_id' => $inst3->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo Único',
                        'aula' => 'Aula 692A - Facultad de Tecnología',
                        'hora_inicio' => '18:45',
                        'hora_fin' => '20:15',
                        'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
                        'cupo_maximo' => 35,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 16,
                    ],
                ],
            ],
            [
                'nombre' => 'Diseño Gráfico Publicitario e Identidad Corporativa',
                'contenido' => 'Creación de piezas gráficas para redes sociales, retoque digital, teoría del color, tipografía y branding empresarial.',
                'portada_path' => null,
                'carga_horaria' => '20',
                'nivel' => CourseLevel::BASICO,
                'periodo' => '1-2026',
                'status' => CourseStatus::PUBLICADO,
                'instructor_id' => $inst4->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo Mañana',
                        'aula' => 'Laboratorio Multimedia',
                        'hora_inicio' => '08:15',
                        'hora_fin' => '09:45',
                        'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
                        'cupo_maximo' => 20,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 15,
                    ],
                ],
            ],
            [
                'nombre' => 'Administración de Redes y Seguridad Informática',
                'contenido' => 'Configuración de switches y routers Cisco, protocolos TCP/IP, subnetting IPv4/IPv6, firewalls y hardening de servidores Linux.',
                'portada_path' => null,
                'carga_horaria' => '30',
                'nivel' => CourseLevel::AVANZADO,
                'periodo' => '2-2026',
                'status' => CourseStatus::EN_PREPARACION,
                'instructor_id' => $inst1->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo Propedéutico',
                        'aula' => 'Laboratorio de Redes y Telecomunicaciones',
                        'hora_inicio' => '17:15',
                        'hora_fin' => '18:45',
                        'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
                        'cupo_maximo' => 25,
                        'status' => GroupStatus::NO_HABILITADO,
                        'participants_count' => 5,
                    ],
                ],
            ],
        ];

        // Pool de estudiantes generado con fake() — nunca datos personales reales
        // (AGENTS.md §5: seeders usan fake() / credenciales genéricas).
        $poolEstudiantes = [];
        for ($i = 0; $i < 40; $i++) {
            $tipo = match ($i % 3) {
                0 => TipoParticipante::UMSS,
                1 => TipoParticipante::AUXILIAR,
                default => TipoParticipante::EXTERNO,
            };

            $poolEstudiantes[] = [
                'ci' => (string) fake()->unique()->numberBetween(5_000_000, 12_000_000),
                'cod_sis' => $tipo === TipoParticipante::EXTERNO ? null : (string) fake()->numberBetween(2017_00000, 2025_99999),
                'nom' => fake()->firstName(),
                'pat' => fake()->lastName(),
                'mat' => fake()->lastName(),
                'tipo' => $tipo,
                'cel' => fake()->numerify('6#######'),
            ];
        }

        $estudianteCursor = 0;

        foreach ($coursesData as $cIdx => $cData) {
            $courseHours = (int) $cData['carga_horaria'];

            $course = Course::firstOrCreate(
                ['nombre' => $cData['nombre']],
                [
                    'contenido' => $cData['contenido'],
                    'portada_path' => $cData['portada_path'],
                    'carga_horaria' => $cData['carga_horaria'],
                    'nivel' => $cData['nivel'],
                    'periodo' => $cData['periodo'],
                    'status' => $cData['status'],
                    'precio_umss' => BusinessRules::calculatePrice($courseHours, TipoParticipante::UMSS->value),
                    'precio_externo' => BusinessRules::calculatePrice($courseHours, TipoParticipante::EXTERNO->value),
                    'precio_auxiliar' => BusinessRules::calculatePrice($courseHours, TipoParticipante::AUXILIAR->value),
                    'instructor_id' => $cData['instructor_id'],
                ]
            );

            // Criterios de evaluación para el curso (100% ponderación)
            $crit2 = EvaluationCriteria::firstOrCreate(
                ['course_id' => $course->id, 'nombre' => 'Evaluación Práctica'],
                ['ponderacion' => 20.0]
            );
            $crit3 = EvaluationCriteria::firstOrCreate(
                ['course_id' => $course->id, 'nombre' => 'Proyecto Final / Aplicación'],
                ['ponderacion' => 30.0]
            );

            foreach ($cData['groups'] as $gIdx => $gData) {
                $group = Group::firstOrCreate(
                    ['course_id' => $course->id, 'nombre' => $gData['nombre']],
                    [
                        'aula' => $gData['aula'],
                        'hora_inicio' => $gData['hora_inicio'],
                        'hora_fin' => $gData['hora_fin'],
                        'cupo_minimo' => $gData['cupo_minimo'],
                        'cupo_maximo' => $gData['cupo_maximo'],
                        'status' => $gData['status'],
                    ]
                );

                // Crear 10 Sesiones académicas para el grupo
                $startDate = Carbon::create(2026, 7, 6);
                $sessionCount = 0;
                $currentDay = $startDate->copy();
                $sessions = [];

                while ($sessionCount < BusinessRules::SESSIONS_PER_COURSE) {
                    // Solo días hábiles lunes a viernes
                    if ($currentDay->isWeekday()) {
                        $session = Session::firstOrCreate(
                            ['group_id' => $group->id, 'fecha' => $currentDay->format('Y-m-d')],
                            [
                                'hora_inicio' => $group->hora_inicio->format('H:i'),
                                'hora_fin' => $group->hora_fin->format('H:i'),
                                'dictada' => $sessionCount < 7, // primeras 7 ya dictadas
                            ]
                        );
                        $sessions[] = $session;
                        $sessionCount++;
                    }
                    $currentDay->addDay();
                }

                // Inscribir participantes sin repetir CI dentro del mismo curso
                $targetCount = min($gData['participants_count'], count($poolEstudiantes));
                for ($i = 0; $i < $targetCount; $i++) {
                    $est = $poolEstudiantes[$estudianteCursor % count($poolEstudiantes)];
                    $estudianteCursor++;

                    // Variedad de estados para QA
                    $status = PreinscriptionStatus::INSCRITO;
                    $fotocopia = true;
                    if ($i === $targetCount - 1) {
                        $status = PreinscriptionStatus::PENDIENTE_PAGO;
                        $fotocopia = false;
                    } elseif ($i === $targetCount - 2) {
                        $status = PreinscriptionStatus::RECHAZADO;
                        $fotocopia = false;
                    }

                    $preinscription = Preinscription::firstOrCreate(
                        ['group_id' => $group->id, 'ci' => $est['ci']],
                        [
                            'cod_sis' => $est['cod_sis'],
                            'nombres' => $est['nom'],
                            'apellido_paterno' => $est['pat'],
                            'apellido_materno' => $est['mat'],
                            'celular' => $est['cel'],
                            'email' => Str::slug($est['nom'], '.').'.'.Str::slug($est['pat']).'@gmail.com',
                            'tipo_participante' => $est['tipo'],
                            'status' => $status,
                            'fotocopia_ci' => $fotocopia,
                        ]
                    );

                    // Pagos según estado
                    $monto = match ($est['tipo']) {
                        TipoParticipante::UMSS => $course->precio_umss,
                        TipoParticipante::EXTERNO => $course->precio_externo,
                        TipoParticipante::AUXILIAR => $course->precio_auxiliar,
                    };
                    if ($status === PreinscriptionStatus::INSCRITO) {
                        $metodo = ($i % 2 === 0) ? PaymentMethod::EFECTIVO : PaymentMethod::QR;
                        Payment::firstOrCreate(
                            ['preinscription_id' => $preinscription->id],
                            [
                                'monto' => $monto,
                                'metodo' => $metodo,
                                'estado' => PaymentStatus::VERIFICADO,
                                'numero_comprobante' => 'REC-'.rand(10000, 99999),
                                'verificado_por' => $admin?->id,
                                'verificado_en' => now()->subDays(rand(1, 10)),
                            ]
                        );

                        // Asistencias en sesiones dictadas
                        foreach ($sessions as $sIdx => $ses) {
                            if ($ses->dictada) {
                                $attStatus = ($i === 2 && $sIdx === 4)
                                    ? AttendanceStatus::AUSENTE
                                    : AttendanceStatus::PRESENTE;

                                Attendance::firstOrCreate(
                                    ['session_id' => $ses->id, 'preinscription_id' => $preinscription->id],
                                    [
                                        'status' => $attStatus,
                                        'distancia_metros' => rand(10, 80),
                                    ]
                                );
                            }
                        }

                        // Calificaciones en criterios (escala 0-100, igual que EvaluationService)
                        $grade2 = rand(75, 100);
                        $grade3 = rand(73, 100);

                        Grade::updateOrCreate(
                            ['evaluation_criteria_id' => $crit2->id, 'preinscription_id' => $preinscription->id],
                            ['nota' => (float) $grade2]
                        );
                        Grade::updateOrCreate(
                            ['evaluation_criteria_id' => $crit3->id, 'preinscription_id' => $preinscription->id],
                            ['nota' => (float) $grade3]
                        );

                        // Certificado para los primeros con notas sobresalientes
                        if ($i < 5 && $course->status === CourseStatus::PUBLICADO) {
                            Certificate::firstOrCreate(
                                ['preinscription_id' => $preinscription->id, 'course_id' => $course->id],
                                [
                                    'tipo' => CertificateType::APROBACION,
                                    'codigo_unico' => 'CERT-2026-'.strtoupper(Str::random(6)),
                                    'pdf_path' => null,
                                    'signature_status' => SignatureStatus::PENDIENTE,
                                    'emitido_en' => now()->subDays(2),
                                ]
                            );
                        }
                    } elseif ($status === PreinscriptionStatus::RECHAZADO) {
                        Payment::firstOrCreate(
                            ['preinscription_id' => $preinscription->id],
                            [
                                'monto' => $monto,
                                'metodo' => PaymentMethod::QR,
                                'estado' => PaymentStatus::RECHAZADO,
                                'motivo_rechazo' => 'Comprobante QR ilegible o no depositado en cuenta UMSS.',
                                'numero_comprobante' => 'REC-'.rand(10000, 99999),
                            ]
                        );
                    }
                }
            }
        }
    }
}
