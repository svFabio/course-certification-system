<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\CertificateType;
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
                'portada_path' => 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?auto=format&fit=crop&w=1000&q=80',
                'carga_horaria' => '20',
                'nivel' => 'Básico',
                'periodo' => '1-2026',
                'status' => CourseStatus::PUBLICADO,
                'instructor_id' => $inst1->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo 1 (Mañana)',
                        'aula' => 'Laboratorio de Informática 1',
                        'hora_inicio' => '10:00',
                        'hora_fin' => '11:30',
                        'cupo_minimo' => 15,
                        'cupo_maximo' => 25,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 18,
                    ],
                    [
                        'nombre' => 'Grupo 2 (Tarde)',
                        'aula' => 'Laboratorio de Redes',
                        'hora_inicio' => '14:15',
                        'hora_fin' => '15:45',
                        'cupo_minimo' => 15,
                        'cupo_maximo' => 20,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 12,
                    ],
                ],
            ],
            [
                'nombre' => 'Programación con Scratch para Niños y Jóvenes',
                'contenido' => 'Fundamentos de lógica algorítmica, animación interactiva, estructuras condicionales y desarrollo de videojuegos didácticos con bloques visuales.',
                'portada_path' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=1000&q=80',
                'carga_horaria' => '20',
                'nivel' => 'Básico',
                'periodo' => '1-2026',
                'status' => CourseStatus::PUBLICADO,
                'instructor_id' => $inst2->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo 1',
                        'aula' => 'Laboratorio de Informática 2',
                        'hora_inicio' => '09:45',
                        'hora_fin' => '11:15',
                        'cupo_minimo' => 15,
                        'cupo_maximo' => 30,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 22,
                    ],
                ],
            ],
            [
                'nombre' => 'Desarrollo de Aplicaciones Web con Laravel y Livewire',
                'contenido' => 'Arquitectura MVC, Eloquent ORM avanzado, Livewire 3 reactivo, diseño con Tailwind CSS y generación de APIs REST seguras.',
                'portada_path' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=1000&q=80',
                'carga_horaria' => '30',
                'nivel' => 'Intermedio',
                'periodo' => '1-2026',
                'status' => CourseStatus::PUBLICADO,
                'instructor_id' => $inst3->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo Único',
                        'aula' => 'Aula 692A - Facultad de Tecnología',
                        'hora_inicio' => '18:45',
                        'hora_fin' => '20:15',
                        'cupo_minimo' => 15,
                        'cupo_maximo' => 35,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 16,
                    ],
                ],
            ],
            [
                'nombre' => 'Diseño Gráfico Publicitario e Identidad Corporativa',
                'contenido' => 'Creación de piezas gráficas para redes sociales, retoque digital, teoría del color, tipografía y branding empresarial.',
                'portada_path' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?auto=format&fit=crop&w=1000&q=80',
                'carga_horaria' => '20',
                'nivel' => 'Básico',
                'periodo' => '1-2026',
                'status' => CourseStatus::PUBLICADO,
                'instructor_id' => $inst4->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo Mañana',
                        'aula' => 'Laboratorio Multimedia',
                        'hora_inicio' => '08:15',
                        'hora_fin' => '09:45',
                        'cupo_minimo' => 15,
                        'cupo_maximo' => 20,
                        'status' => GroupStatus::HABILITADO,
                        'participants_count' => 15,
                    ],
                ],
            ],
            [
                'nombre' => 'Administración de Redes y Seguridad Informática',
                'contenido' => 'Configuración de switches y routers Cisco, protocolos TCP/IP, subnetting IPv4/IPv6, firewalls y hardening de servidores Linux.',
                'portada_path' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?auto=format&fit=crop&w=1000&q=80',
                'carga_horaria' => '30',
                'nivel' => 'Avanzado',
                'periodo' => '2-2026',
                'status' => CourseStatus::EN_PREPARACION,
                'instructor_id' => $inst1->id,
                'groups' => [
                    [
                        'nombre' => 'Grupo Propedéutico',
                        'aula' => 'Laboratorio de Redes y Telecomunicaciones',
                        'hora_inicio' => '17:15',
                        'hora_fin' => '18:45',
                        'cupo_minimo' => 15,
                        'cupo_maximo' => 25,
                        'status' => GroupStatus::NO_HABILITADO,
                        'participants_count' => 5,
                    ],
                ],
            ],
        ];

        // Estudiantes realistas para poblar
        $poolEstudiantes = [
            ['ci' => '7912552', 'cod_sis' => '202002515', 'nom' => 'Christian Mauricio', 'pat' => 'Arias', 'mat' => 'Chubarieva', 'tipo' => TipoParticipante::AUXILIAR, 'cel' => '75468783'],
            ['ci' => '9516503', 'cod_sis' => '202503252', 'nom' => 'Giliani Anel', 'pat' => 'Balderrama', 'mat' => 'Cordova', 'tipo' => TipoParticipante::UMSS, 'cel' => '65518177'],
            ['ci' => '13163508', 'cod_sis' => '202202517', 'nom' => 'Jazmin', 'pat' => 'Cuizara', 'mat' => 'Segarra', 'tipo' => TipoParticipante::AUXILIAR, 'cel' => '63873042'],
            ['ci' => '9409282', 'cod_sis' => '202100112', 'nom' => 'Scarlet', 'pat' => 'Davila', 'mat' => 'Montaño', 'tipo' => TipoParticipante::UMSS, 'cel' => '69524424'],
            ['ci' => '12968669', 'cod_sis' => '202103793', 'nom' => 'Kevin Antonio', 'pat' => 'Fernandez', 'mat' => 'Aguilar', 'tipo' => TipoParticipante::UMSS, 'cel' => '69529974'],
            ['ci' => '13378682', 'cod_sis' => '202401362', 'nom' => 'Francisco', 'pat' => 'Lazarte', 'mat' => 'Salazar', 'tipo' => TipoParticipante::AUXILIAR, 'cel' => '70739012'],
            ['ci' => '15568524', 'cod_sis' => '202500690', 'nom' => 'Nadir Fabricio', 'pat' => 'Lizarazu', 'mat' => 'Flores', 'tipo' => TipoParticipante::UMSS, 'cel' => '69533334'],
            ['ci' => '9508215', 'cod_sis' => '201709859', 'nom' => 'Jhojan Enrique', 'pat' => 'Manzel', 'mat' => 'Mollo', 'tipo' => TipoParticipante::UMSS, 'cel' => '75968023'],
            ['ci' => '13589123', 'cod_sis' => '202401590', 'nom' => 'Leonardo Peter', 'pat' => 'Marca', 'mat' => 'Salas', 'tipo' => TipoParticipante::UMSS, 'cel' => '77965548'],
            ['ci' => '8739408', 'cod_sis' => null, 'nom' => 'Rodrigo Javier', 'pat' => 'Molina', 'mat' => 'Peredo', 'tipo' => TipoParticipante::EXTERNO, 'cel' => '65704302'],
            ['ci' => '14077852', 'cod_sis' => '202300188', 'nom' => 'Brenda Katherine', 'pat' => 'Ancieta', 'mat' => 'Herrera', 'tipo' => TipoParticipante::UMSS, 'cel' => '71485962'],
            ['ci' => '14829705', 'cod_sis' => '202304910', 'nom' => 'Santiago', 'pat' => 'Anzaldo', 'mat' => 'Flores', 'tipo' => TipoParticipante::UMSS, 'cel' => '78451296'],
            ['ci' => '15501046', 'cod_sis' => null, 'nom' => 'Matias', 'pat' => 'Ayala', 'mat' => 'Villarroel', 'tipo' => TipoParticipante::EXTERNO, 'cel' => '69584120'],
            ['ci' => '16245988', 'cod_sis' => '202409841', 'nom' => 'Wendy Nicole', 'pat' => 'Ballesteros', 'mat' => 'Morales', 'tipo' => TipoParticipante::UMSS, 'cel' => '77412589'],
            ['ci' => '16361541', 'cod_sis' => '202409842', 'nom' => 'Jonathan', 'pat' => 'Ballesteros', 'mat' => 'Morales', 'tipo' => TipoParticipante::UMSS, 'cel' => '77412590'],
            ['ci' => '15077789', 'cod_sis' => '202201995', 'nom' => 'Briana Victoria', 'pat' => 'Cespedes', 'mat' => 'Chavez', 'tipo' => TipoParticipante::UMSS, 'cel' => '68541230'],
            ['ci' => '17143554', 'cod_sis' => null, 'nom' => 'Emilio', 'pat' => 'Claure', 'mat' => 'Choque', 'tipo' => TipoParticipante::EXTERNO, 'cel' => '70789456'],
            ['ci' => '15799776', 'cod_sis' => '202403120', 'nom' => 'Thiago', 'pat' => 'Flores', 'mat' => 'Gamboa', 'tipo' => TipoParticipante::UMSS, 'cel' => '63985214'],
            ['ci' => '13590051', 'cod_sis' => '202105412', 'nom' => 'Pablo Alejandro', 'pat' => 'Gonzales', 'mat' => 'Cortez', 'tipo' => TipoParticipante::UMSS, 'cel' => '72951478'],
            ['ci' => '17008486', 'cod_sis' => '202501980', 'nom' => 'Alison Silvana', 'pat' => 'Gonzales', 'mat' => 'Cortez', 'tipo' => TipoParticipante::UMSS, 'cel' => '67412589'],
            ['ci' => '15900533', 'cod_sis' => '202302450', 'nom' => 'Brenda Belen', 'pat' => 'Gonzales', 'mat' => 'Veliz', 'tipo' => TipoParticipante::UMSS, 'cel' => '75986321'],
            ['ci' => '14836794', 'cod_sis' => '202208741', 'nom' => 'Carlos Manuel', 'pat' => 'Guaman', 'mat' => 'Cespedes', 'tipo' => TipoParticipante::UMSS, 'cel' => '68451239'],
            ['ci' => '16768480', 'cod_sis' => null, 'nom' => 'Ian Fernando', 'pat' => 'Lizarazu', 'mat' => 'Calizaya', 'tipo' => TipoParticipante::EXTERNO, 'cel' => '71458963'],
            ['ci' => '16268688', 'cod_sis' => '202407412', 'nom' => 'Ainhoa Evangeline', 'pat' => 'Lopez', 'mat' => 'Vargas', 'tipo' => TipoParticipante::UMSS, 'cel' => '69852147'],
            ['ci' => '14441951', 'cod_sis' => '202109852', 'nom' => 'Ana Paula', 'pat' => 'Lopez', 'mat' => 'Parra', 'tipo' => TipoParticipante::UMSS, 'cel' => '76541238'],
        ];

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
                    'precio_umss' => BusinessRules::calculatePrice($courseHours, 'umss'),
                    'precio_externo' => BusinessRules::calculatePrice($courseHours, 'externo'),
                    'precio_auxiliar' => BusinessRules::calculatePrice($courseHours, 'auxiliar'),
                    'instructor_id' => $cData['instructor_id'],
                ]
            );

            // Criterios de evaluación para el curso (100% ponderación)
            $crit1 = EvaluationCriteria::firstOrCreate(
                ['course_id' => $course->id, 'nombre' => 'Asistencia y Participación'],
                ['ponderacion' => 50.0]
            );
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

                // Inscribir participantes
                $targetCount = min($gData['participants_count'], count($poolEstudiantes));
                for ($i = 0; $i < $targetCount; $i++) {
                    $est = $poolEstudiantes[($i + ($cIdx * 3) + $gIdx) % count($poolEstudiantes)];

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
                    $monto = BusinessRules::calculatePrice($courseHours, $est['tipo']->value);
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
                        $grade1 = 100.0; // asistencia completa
                        $grade2 = rand(75, 100);
                        $grade3 = rand(73, 100);

                        Grade::updateOrCreate(
                            ['evaluation_criteria_id' => $crit1->id, 'preinscription_id' => $preinscription->id],
                            ['nota' => $grade1]
                        );
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
                                    'pdf_path' => 'certificates/cert-'.Str::uuid().'.pdf',
                                    'signature_status' => SignatureStatus::LISTO,
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
