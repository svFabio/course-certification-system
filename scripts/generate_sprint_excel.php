<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet;

// Paleta Institucional UMSS
$navyUmss = '0E2E5F';
$navyDark = '0A2247';
$redUmss = 'E01D2E';
$grayLight = 'F8FAFC';
$grayBorder = 'CBD5E1';
$white = 'FFFFFF';

// ==========================================
// PESTAÑA 1: RESUMEN DE SPRINTS
// ==========================================
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Resumen de Sprints');

// Título general
$sheet1->mergeCells('A1:F1');
$sheet1->setCellValue('A1', 'UNIVERSIDAD MAYOR DE SAN SIMÓN — FORMACIÓN CONTINUA');
$sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(15)->setColor(new Color($white));
$sheet1->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyUmss);
$sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet1->mergeCells('A2:F2');
$sheet1->setCellValue('A2', 'PLAN Y DISTRIBUCIÓN DE SPRINTS (CRONOGRAMA OFICIAL 14/09 AL 23/10)');
$sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(11)->setColor(new Color($white));
$sheet1->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyDark);
$sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Encabezados Tabla Sprints
$headers1 = ['Sprint', 'Fechas / Duración', 'Objetivo Principal del Sprint', 'Cant. HUs', 'Puntos Historia (SP)', 'Estado'];
$cols1 = ['A', 'B', 'C', 'D', 'E', 'F'];

$row = 4;
foreach ($headers1 as $i => $h) {
    $col = $cols1[$i];
    $sheet1->setCellValue("{$col}{$row}", $h);
    $sheet1->getStyle("{$col}{$row}")->getFont()->setBold(true)->setColor(new Color($white));
    $sheet1->getStyle("{$col}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyUmss);
    $sheet1->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
}

$sprintsData = [
    [
        'Sprint 1',
        '14/09/2026 - 27/09/2026 (2 semanas)',
        'Arquitectura base, catálogo público de cursos con diseño institucional, preinscripción en línea de participantes (2 pasos), apertura y control de grupos con horarios automáticos, verificación de pagos en caja, descuento para auxiliares (50%) y emisión de boleta PDF.',
        '8 HUs (HU-01 a HU-08)',
        '44 SP',
        'Completado (100%)',
    ],
    [
        'Sprint 2',
        '28/09/2026 - 11/10/2026 (2 semanas)',
        'Gestión académica avanzada: sincronización con Google Sheets, asistencia docente en grilla y por código QR, feriados institucionales, fusión asistida de grupos con devoluciones de caja, bandeja de solicitudes estudiantiles y lista de espera para cursos llenos.',
        '7 HUs (HU-09 a HU-15)',
        '41 SP',
        'Completado (100%)',
    ],
    [
        'Sprint 3',
        '12/10/2026 - 23/10/2026 (2 semanas)',
        'Evaluación y certificación: matriz de calificaciones docente (100%), cierre de curso con emisión directa de certificados verificables con QR en Cloudinary, actas de cierre y reportes de caja en Excel para Jefatura, difusión en redes sociales (Telegram Bot, WhatsApp, Facebook, OpenGraph) y dashboard de monitoreo con semáforo.',
        '6 HUs (HU-16 a HU-21)',
        '35 SP',
        'En curso / Cierre (95%)',
    ],
];

foreach ($sprintsData as $idx => $s) {
    $row++;
    $sheet1->setCellValue("A{$row}", $s[0]);
    $sheet1->setCellValue("B{$row}", $s[1]);
    $sheet1->setCellValue("C{$row}", $s[2]);
    $sheet1->setCellValue("D{$row}", $s[3]);
    $sheet1->setCellValue("E{$row}", $s[4]);
    $sheet1->setCellValue("F{$row}", $s[5]);

    $sheet1->getStyle("A{$row}")->getFont()->setBold(true);
    $sheet1->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet1->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet1->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet1->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet1->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $colorEstado = ($idx == 2) ? '0284C7' : '16A34A';
    $sheet1->getStyle("F{$row}")->getFont()->setBold(true)->setColor(new Color($colorEstado));

    if ($idx % 2 == 1) {
        $sheet1->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($grayLight);
    }
}

// Borde a la tabla
$sheet1->getStyle("A4:F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($grayBorder);

// Fila Total
$row++;
$sheet1->mergeCells("A{$row}:C{$row}");
$sheet1->setCellValue("A{$row}", 'TOTAL PROYECTO FORMACIÓN CONTINUA');
$sheet1->setCellValue("D{$row}", '21 HUs');
$sheet1->setCellValue("E{$row}", '120 SP');
$sheet1->setCellValue("F{$row}", 'En fase final (23/10)');
$sheet1->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
$sheet1->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
$sheet1->getStyle("D{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Auto-ajustar columnas
foreach (['A', 'B', 'D', 'E', 'F'] as $c) {
    $sheet1->getColumnDimension($c)->setAutoSize(true);
}
$sheet1->getColumnDimension('C')->setWidth(65);
$sheet1->getStyle('C')->getAlignment()->setWrapText(true);

// ==========================================
// PESTAÑA 2: HISTORIAS DE USUARIO (HUs)
// ==========================================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Historias de Usuario (HUs)');

// Título pestaña 2
$sheet2->mergeCells('A1:J1');
$sheet2->setCellValue('A1', 'MATRIZ DETALLADA DE HISTORIAS DE USUARIO (SCRUM / TIS)');
$sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color($white));
$sheet2->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyUmss);
$sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$headers2 = [
    'ID HU',
    'Sprint',
    'Título de la Historia',
    'Como (Rol)',
    'Quiero (Acción)',
    'Para (Beneficio)',
    'Criterios de Aceptación Clave',
    'Módulos y Componentes Afectados',
    'Commits Clave',
    'Puntos (SP)',
];
$cols2 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

$row = 3;
foreach ($headers2 as $i => $h) {
    $col = $cols2[$i];
    $sheet2->setCellValue("{$col}{$row}", $h);
    $sheet2->getStyle("{$col}{$row}")->getFont()->setBold(true)->setColor(new Color($white));
    $sheet2->getStyle("{$col}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyUmss);
    $sheet2->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
}

$hus = [
    // SPRINT 1 (14/09 - 27/09)
    [
        'HU-01', 'Sprint 1', 'Catálogo Público de Cursos Ofertados',
        'Estudiante / Público',
        'Ver el catálogo de cursos publicados con filtros y cupos en tiempo real',
        'Conocer la oferta académica disponible e iniciar la preinscripción',
        '1. Filtrado por área temática y nivel. 2. Visualización de cupos disponibles y precio según tipo. 3. Diseño responsivo con paleta UMSS.',
        'CatalogComponent.php, catalog-component.blade.php, Course model',
        '87d5df8, b9907ec, 9b4129d, 65f5b1b', 5,
    ],
    [
        'HU-02', 'Sprint 1', 'Preinscripción en Línea en Dos Pasos',
        'Estudiante',
        'Registrar mis datos (CI, nombres, email, cod_sis si aplica) y seleccionar mi tipo de participante',
        'Reservar plaza temporal y recibir instrucciones para completar el pago',
        '1. Formulario en 2 pasos (datos + confirmación). 2. Validación estricta de CI único por grupo. 3. Cálculo de arancel sin mutar BD.',
        'PreinscriptionComponent.php, preinscription-component.blade.php',
        '9b4129d, ec8d5b8, 2c0b3c9, b021925', 8,
    ],
    [
        'HU-03', 'Sprint 1', 'Parametrización y Edición de Cursos',
        'Administrador',
        'Crear y editar cursos con su carga horaria, contenido y aranceles editables (UMSS, Externo, Auxiliar)',
        'Mantener actualizados los cursos según resoluciones facultativas vigentes',
        '1. Columnas de precios editables como fuente de verdad. 2. Precalculado automático por BusinessRules. 3. Carga de foto de portada.',
        'CourseResource.php, BusinessRules.php',
        '87d5df8, 9b4129d, 75d19f9, 3780d09, e1eb6d1', 5,
    ],
    [
        'HU-04', 'Sprint 1', 'Apertura y Control de Horarios de Grupos',
        'Administrador',
        'Abrir grupos definiendo instructor, aula, cupo mínimo (15) y horario automático',
        'Planificar las sesiones asegurando 1.5h (20h) o 2.5h (30h) por clase de lunes a viernes',
        '1. Cálculo reactivo de hora_fin a partir de hora_inicio. 2. Validación de cupo mínimo de 15. 3. Nombre único de grupo por curso.',
        'GroupResource.php, GroupsRelationManager.php',
        '87d5df8, 9b4129d, d825d56, 26_09_24', 5,
    ],
    [
        'HU-05', 'Sprint 1', 'Verificación y Confirmación de Pagos en Caja',
        'Administrador / Cajero',
        'Verificar los pagos en ventanilla (Efectivo o QR) ingresando comprobante y fotocopia de CI',
        'Confirmar la preinscripción a estado "inscrito" y enviar notificación al estudiante',
        '1. Transacción atómica DB::transaction. 2. Registro de auditoría del usuario cajero. 3. Correo encolado con PaymentConfirmedNotification.',
        'PaymentResource.php, PaymentService.php, PaymentObserver.php',
        '9b4129d, 6da4de3, 64936b7, 9df99d5, 4f31205', 8,
    ],
    [
        'HU-06', 'Sprint 1', 'Habilitación Automática y Monitoreo de Grupos',
        'Administrador',
        'Monitorear el cupo alcanzado y habilitar el grupo automáticamente al llegar al mínimo de 15',
        'Asegurar la rentabilidad operativa del curso sin intervención manual',
        '1. Regla de cupo comprometido: pendiente_pago + inscrito. 2. Disparo de observer reactivo. 3. Alertas visuales de estado.',
        'GroupStatus.php, PreinscriptionObserver.php, UpcomingGroupsWidget.php',
        '9b4129d, 78941e3, 8750e16, 3b8a12e', 3,
    ],
    [
        'HU-07', 'Sprint 1', 'Validación de Descuento para Auxiliares (50%)',
        'Auxiliar de Docencia / Admin',
        'Adjuntar certificado de auxiliar concluido y pagar el 50% del precio UMSS',
        'Beneficiar al personal auxiliar según normativa del Departamento',
        '1. Validación del campo fotocopia_certificado. 2. Tarifa con 50% de descuento. 3. Aprobación en caja del documento probatorio.',
        'PreinscriptionComponent.php, PreinscriptionResource.php, BusinessRules.php',
        '3353e74, 2026_09_23_000001_add_auxiliar_certificate', 5,
    ],
    [
        'HU-08', 'Sprint 1', 'Emisión de Boleta de Inscripción Oficial en PDF',
        'Estudiante / Cajero',
        'Generar y descargar la boleta de pago e inscripción en PDF con código QR y sello oficial',
        'Tener un comprobante formal de respaldo académico y financiero',
        '1. Generación de PDF con DomPDF. 2. Código QR embebido para validación. 3. Desglose detallado de monto pagado y datos del curso.',
        'BoletaService.php, resources/views/pdf/boleta.blade.php',
        '3353e74, 43a83f1', 5,
    ],

    // SPRINT 2 (28/09 - 11/10)
    [
        'HU-09', 'Sprint 2', 'Integración y Exportación a Google Sheets',
        'Administrador / Docente',
        'Sincronizar y exportar las listas de preinscritos, notas y asistencia a hojas de cálculo Google',
        'Permitir que los instructores utilicen sus planillas de trabajo sin inconsistencias de datos',
        '1. Creación de pestañas automáticas con formato. 2. Fórmulas de asistencia y ponderación. 3. Sincronización encolada en background.',
        'GoogleSheetsService.php, SheetExportService.php, TemplateSheetBuilder.php',
        '11d2e9d, c93b132, db61f2c, e3cc65b, 84a7b7b', 8,
    ],
    [
        'HU-10', 'Sprint 2', 'Registro y Control de Asistencia Docente y QR',
        'Instructor',
        'Generar código QR por sesión y registrar la asistencia desde una matriz visual rápida',
        'Controlar el presentismo diario de estudiantes en aula en menos de 2 minutos',
        '1. Página MarkAttendance en panel de docente. 2. QR dinámico por sesión. 3. Estados: presente, ausente, justificado.',
        'MarkAttendance.php, AttendanceService.php, SessionResource.php',
        'b1b29d6, e525be5, 95665d2, 31e1a50', 5,
    ],
    [
        'HU-11', 'Sprint 2', 'Calendario de Feriados y Reprogramación de Sesiones',
        'Administrador',
        'Registrar feriados nacionales/universitarios y reprogramar sesiones con motivo explícito',
        'Garantizar que el curso cumpla estrictamente los 10 días hábiles requeridos',
        '1. Detección automática de feriados al crear sesiones. 2. Formulario de reprogramación con justificación. 3. Ajuste de fecha fin.',
        'HolidayResource.php, HolidayService.php, CourseSession model',
        'e7a88c1, 2026_09_21_000001_create_holidays_table', 3,
    ],
    [
        'HU-12', 'Sprint 2', 'Fusión Asistida de Grupos y Traspaso de Estudiantes',
        'Administrador',
        'Transferir masivamente o fusionar estudiantes de un grupo no habilitado a otro paralelo',
        'Evitar la cancelación del servicio a estudiantes inscritos cuando un grupo no alcanza el cupo',
        '1. Diálogo de confirmación y selección de grupo destino. 2. Traspaso atómico de inscripciones y pagos. 3. Recálculo de cupos.',
        'GroupMergeService.php, GroupResource.php',
        'e7a88c1, 3353e74, 1c04bb8, afabb7f', 8,
    ],
    [
        'HU-13', 'Sprint 2', 'Gestión de Reembolsos y Devoluciones de Caja',
        'Administrador / Cajero',
        'Tramitar la devolución del arancel cancelado cuando el estudiante rechaza ser transferido',
        'Cerrar administrativamente el ciclo financiero con total transparencia de egresos',
        '1. Estado "devolución_solicitada" y "reembolsado". 2. Liberación inmediata de cupo. 3. Registro del motivo y comprobante de egreso.',
        'PaymentService.php, PreinscriptionResource.php',
        '3353e74, 464f43f, 8750e16', 6,
    ],
    [
        'HU-14', 'Sprint 2', 'Bandeja de Solicitudes Estudiantiles (Traspasos y Devoluciones)',
        'Estudiante / Administrador',
        'Solicitar cambio de grupo o devolución desde el portal del alumno y aprobarlo desde panel admin',
        'Eliminar trámites manuales en papel y descentralizar la gestión de excepciones',
        '1. Formulario de solicitud en panel estudiante. 2. Bandeja de entrada con aprobación en 1 clic. 3. Ejecución automática de la acción.',
        'StudentRequestResource.php, StudentRequestService.php',
        'c76482c, df2bffa, ca67976', 6,
    ],
    [
        'HU-15', 'Sprint 2', 'Lista de Interés y Alerta de Demanda para Cursos Llenos',
        'Estudiante / Administrador',
        'Registrar correo en lista de espera cuando un curso esté lleno y recibir aviso al liberar cupos',
        'Capturar demanda insatisfecha para evaluar la apertura de nuevos grupos paralelos',
        '1. Botón "Avisarme si se abren cupos" en catálogo. 2. Alerta administrativa de alta demanda. 3. Notificación masiva encolada por correo.',
        'CourseInterestService.php, CourseDemandService.php, CatalogComponent.php',
        '3df6245, 8902d6a, 2317584, d86a0f0, 51ff850', 5,
    ],

    // SPRINT 3 (12/10 - 23/10)
    [
        'HU-16', 'Sprint 3', 'Matriz de Calificaciones y Criterios de Evaluación Docente',
        'Instructor',
        'Definir criterios de evaluación con suma 100% y asentar notas por estudiante en grilla',
        'Calcular automáticamente los promedios y definir quién aprueba (nota >= 70)',
        '1. Validación de suma de ponderaciones igual a 100%. 2. Ingreso matricial dinámico en GradeMatrix. 3. Diferenciación aprobado/reprobado.',
        'GradeMatrix.php, EvaluationService.php, InstructorCourseResource.php',
        'b9907ec, 81204c1', 5,
    ],
    [
        'HU-17', 'Sprint 3', 'Cierre de Curso y Emisión Directa de Certificados Digitales',
        'Administrador / Instructor',
        'Cerrar el curso y emitir certificados PDF con código único y QR almacenados en Cloudinary',
        'Entregar certificados legítimos e infalsificables a estudiantes aprobados y al docente',
        '1. Cierre en ViewCourse dispara emisión masiva. 2. Almacenamiento binario en Cloudinary. 3. Certificados de Aprobación, Asistencia y Docente.',
        'CourseService.php, CertificateService.php, CertificateResource.php',
        '8b5b2c9, 6bd2ac4, 47712e8, 1805628, 3cbfe66, 13e5f09, 73da6ab', 8,
    ],
    [
        'HU-18', 'Sprint 3', 'Verificación Pública de Certificados en Línea',
        'Público / Empleadores',
        'Validar la autenticidad de un certificado ingresando el código único o escaneando el código QR',
        'Comprobar la validez oficial del título emitido por la UMSS sin intermediación manual',
        '1. Ruta pública /verificar/{codigo}. 2. Exhibición de datos: curso, estudiante, nota, carga horaria, instructor. 3. Descarga de copia oficial.',
        'CertificateVerificationComponent.php, certificate-verification-component.blade.php',
        'b9907ec, 6bd2ac4', 3,
    ],
    [
        'HU-19', 'Sprint 3', 'Exportación de Actas de Cierre y Balances Financieros en Excel',
        'Administrador / Jefatura',
        'Generar el Acta de Cierre Académica, el Reporte Financiero de Caja y el Catálogo General en Excel',
        'Rendir cuentas a Jefatura de Departamento y Dirección de Carrera con balances oficiales',
        '1. CourseClosingExport con desglose de aprobados/reprobados. 2. FinancialReportExport con conciliación de ingresos/devoluciones. 3. Formato oficial.',
        'CourseClosingExport.php, FinancialReportExport.php, CoursesGeneralExport.php',
        '81204c1, e527018', 8,
    ],
    [
        'HU-20', 'Sprint 3', 'Campaña de Difusión en Redes Sociales (Telegram, WhatsApp, FB)',
        'Administrador',
        'Publicar el curso con 1 clic en el canal oficial de Telegram y generar tarjetas Open Graph',
        'Masificar la inscripción en redes sociales con un afiche profesional y enlace directo',
        '1. TelegramChannelService con envío de afiche y copy en HTML. 2. Modal Filament con enlaces a WhatsApp y Facebook. 3. Meta tags Open Graph.',
        'TelegramChannelService.php, campana-redes-links.blade.php, app.blade.php',
        '434437e, 6e0cac6, 95ce455', 6,
    ],
    [
        'HU-21', 'Sprint 3', 'Dashboard Integral de Monitoreo y Semáforos de Capacidad',
        'Administrador',
        'Visualizar métricas clave de recaudación, inscritos y semáforos de grupos en riesgo',
        'Tomar decisiones operativas inmediatas sobre apertura o suspensión de grupos',
        '1. Widget UpcomingGroups con semáforo por cupo mínimo y comprometidos. 2. Métricas consolidadas en StatsOverview. 3. Acción directa a gestión.',
        'UpcomingGroupsWidget.php, StatsOverviewWidget.php',
        '78941e3, 3b8a12e', 5,
    ],
];

foreach ($hus as $idx => $hu) {
    $row++;
    $sheet2->setCellValue("A{$row}", $hu[0]);
    $sheet2->setCellValue("B{$row}", $hu[1]);
    $sheet2->setCellValue("C{$row}", $hu[2]);
    $sheet2->setCellValue("D{$row}", $hu[3]);
    $sheet2->setCellValue("E{$row}", $hu[4]);
    $sheet2->setCellValue("F{$row}", $hu[5]);
    $sheet2->setCellValue("G{$row}", $hu[6]);
    $sheet2->setCellValue("H{$row}", $hu[7]);
    $sheet2->setCellValue("I{$row}", $hu[8]);
    $sheet2->setCellValue("J{$row}", $hu[9]);

    $sheet2->getStyle("A{$row}")->getFont()->setBold(true);
    $sheet2->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet2->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet2->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet2->getStyle("J{$row}")->getFont()->setBold(true);

    if ($idx % 2 == 1) {
        $sheet2->getStyle("A{$row}:J{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($grayLight);
    }
}

// Bordes tabla HUs
$sheet2->getStyle("A3:J{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($grayBorder);

// Ajuste anchos pestaña 2
foreach (['A', 'B', 'J'] as $c) {
    $sheet2->getColumnDimension($c)->setAutoSize(true);
}
$sheet2->getColumnDimension('C')->setWidth(28);
$sheet2->getColumnDimension('D')->setWidth(18);
$sheet2->getColumnDimension('E')->setWidth(35);
$sheet2->getColumnDimension('F')->setWidth(30);
$sheet2->getColumnDimension('G')->setWidth(35);
$sheet2->getColumnDimension('H')->setWidth(30);
$sheet2->getColumnDimension('I')->setWidth(25);

foreach (['C', 'D', 'E', 'F', 'G', 'H', 'I'] as $c) {
    $sheet2->getStyle($c)->getAlignment()->setWrapText(true);
}

// ==========================================
// PESTAÑA 3: TRAZABILIDAD COMMITS VS HUs
// ==========================================
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Trazabilidad Commits vs HUs');

// Título pestaña 3
$sheet3->mergeCells('A1:F1');
$sheet3->setCellValue('A1', 'MAPEO Y TRAZABILIDAD: COMMITS EN GIT VS HISTORIAS DE USUARIO');
$sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color($white));
$sheet3->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyUmss);
$sheet3->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$headers3 = ['Hash Commit', 'Fecha', 'Sprint', 'HU(s) Asociada(s)', 'Tipo de Cambio', 'Descripción / Mensaje del Commit'];
$cols3 = ['A', 'B', 'C', 'D', 'E', 'F'];

$row = 3;
foreach ($headers3 as $i => $h) {
    $col = $cols3[$i];
    $sheet3->setCellValue("{$col}{$row}", $h);
    $sheet3->getStyle("{$col}{$row}")->getFont()->setBold(true)->setColor(new Color($white));
    $sheet3->getStyle("{$col}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyUmss);
    $sheet3->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
}

// Extracción de commits representativos vinculados al cronograma 14/09 a 23/10
$commitMapping = [
    // Sprint 1 (14/09 - 27/09)
    ['160c256', '2026-09-14', 'Sprint 1', 'HU-01, HU-03', 'Security Baseline', 'Remove test credentials from README & finalize env baseline'],
    ['8daf901', '2026-09-15', 'Sprint 1', 'HU-01, HU-03, HU-04', 'Scaffolding Fix', 'fix: audit fixes — enums, fields, routes, config, and consistency'],
    ['9b4129d', '2026-09-16', 'Sprint 1', 'HU-01, HU-02, HU-04, HU-05, HU-06', 'Core Business', 'feat: implement HU-01, HU-02, HU-08, HU-09, HU-15 (Catalog, Preinscription, Groups, Payments)'],
    ['6da4de3', '2026-09-18', 'Sprint 1', 'HU-05', 'Payment Rules', 'fix: resolve architecture violations, add PaymentStatus enum, clean UI tokens'],
    ['6c2c312', '2026-09-20', 'Sprint 1', 'HU-01, HU-03', 'Cloudinary Storage', 'feat: integrate Cloudinary filesystem driver for images and certificate PDFs'],
    ['75d19f9', '2026-09-21', 'Sprint 1', 'HU-02, HU-03, HU-04', 'Academic Fields', 'feat(academic): add cod_sis, fotocopia_ci and aula fields to domain and ui'],
    ['78941e3', '2026-09-21', 'Sprint 1', 'HU-06, HU-21', 'Dashboard Widget', 'feat(dashboard): add key stats overview and upcoming groups capacity monitoring widgets'],
    ['ec8d5b8', '2026-09-21', 'Sprint 1', 'HU-02', 'Preinscription Step 2', 'feat(preinscription): add submission confirmation screen and strict regex validation'],
    ['d825d56', '2026-09-21', 'Sprint 1', 'HU-04', 'Auto Duration', 'feat(groups): auto-calculate hora_fin, fix session durations, periodo select'],
    ['2c0b3c9', '2026-09-22', 'Sprint 1', 'HU-02', 'Atomic Duplicate Lock', 'feat(preinscription): block duplicate enrollments with clear messages and rate limit'],
    ['3353e74', '2026-09-24', 'Sprint 1', 'HU-07, HU-08', 'Finance & Boleta PDF', 'feat(finance): add auxiliar discount gate, refunds, group transfer, and boleta pdf'],
    ['3780d09', '2026-09-25', 'Sprint 1', 'HU-03, HU-07', 'Course Pricing Source', 'feat(pricing): use editable course prices as source of truth'],
    ['9df99d5', '2026-09-25', 'Sprint 1', 'HU-05', 'Cashier CI Gate', 'feat(payments): unify payment registration and require photocopy of CI'],

    // Sprint 2 (28/09 - 11/10)
    ['464f43f', '2026-09-29', 'Sprint 2', 'HU-05, HU-06, HU-13', 'Cashier Auto-Enablement', 'feat(preinscriptions): centralize cashier flow, add refund actions and bidirectional auto-enablement'],
    ['1c04bb8', '2026-10-01', 'Sprint 2', 'HU-12', 'Assisted Merge', 'feat(groups): implement assisted group merge and enable preinscriptions on unenabled groups'],
    ['6bd2ac4', '2026-10-02', 'Sprint 2', 'HU-17, HU-18', 'Public Download', 'feat(certificates): enable public certificate download via unique code on verification view'],
    ['63149f4', '2026-10-03', 'Sprint 2', 'HU-05, HU-13', 'Payment Rejection', 'fix(preinscriptions): add reject payment action, reactivate from RETIRADO, and notify on rejection'],
    ['c76482c', '2026-10-06', 'Sprint 2', 'HU-14', 'Student Requests Domain', 'feat(requests): add student request domain for group change and refund flows'],
    ['df2bffa', '2026-10-06', 'Sprint 2', 'HU-14', 'Admin Requests Inbox', 'feat(requests): add student request inbox and admin approval panel'],
    ['ca67976', '2026-10-07', 'Sprint 2', 'HU-14', 'Action Consolidation', 'refactor(preinscriptions): remove duplicated group change and refund actions from admin resource'],
    ['3df6245', '2026-10-08', 'Sprint 2', 'HU-15', 'Course Interest Queue', 'feat(interests): add course interest registry for courses at capacity'],
    ['8902d6a', '2026-10-09', 'Sprint 2', 'HU-15', 'Slot Available Email', 'feat(interests): queue slot-available notification to interested students'],
    ['2317584', '2026-10-09', 'Sprint 2', 'HU-15', 'Catalog CTA', 'feat(interests): add interested-student call to action to public catalog'],
    ['d86a0f0', '2026-10-10', 'Sprint 2', 'HU-15', 'Admin Interest View', 'feat(interests): add admin interest overview and notify action'],
    ['51ff850', '2026-10-11', 'Sprint 2', 'HU-15', 'Conversion Exclude', 'fix(interests): exclude converted students before notifying slot availability'],

    // Sprint 3 (12/10 - 23/10)
    ['82f350a', '2026-10-12', 'Sprint 3', 'HU-17', 'Notification Polish', 'Notificaciones: improve feedback on certificate issuance'],
    ['47712e8', '2026-10-12', 'Sprint 3', 'HU-17', 'Direct Certificate Approval', 'feat(certificates): add direct approve action to skip signature steps'],
    ['1805628', '2026-10-15', 'Sprint 3', 'HU-10, HU-17', 'Signature Workflow Removal', 'refactor: remove digital signature stamping and gps attendance restrictions'],
    ['3cbfe66', '2026-10-15', 'Sprint 3', 'HU-17', 'Direct Certificate Issue', 'refactor: eliminate signature status workflow and enable direct certificate issuance'],
    ['13e5f09', '2026-10-15', 'Sprint 3', 'HU-17', 'Hierarchical Certificates', 'feat(certificates): group global certificates by course and add relation manager to courses'],
    ['73da6ab', '2026-10-15', 'Sprint 3', 'HU-17', 'Drill-Down Management', 'feat(certificates): implement hierarchical certificate management from course to student'],
    ['fff3bc3', '2026-10-15', 'Sprint 3', 'HU-02, HU-05', 'Inscripciones Module', 'feat(preinscriptions): rename module to inscripciones and add status tabs with default pending filter'],
    ['3fdb844', '2026-10-16', 'Sprint 3', 'HU-05, HU-10', 'Audit Payments Log', 'feat(ux): hide orphan crud menus and convert payments to audit log with sessions relation manager'],
    ['81204c1', '2026-10-16', 'Sprint 3', 'HU-16, HU-17, HU-19', 'Closing & Excel Reports', 'feat: implement course closing reports, password recovery flow, and course cancellation management'],
    ['8750e16', '2026-10-17', 'Sprint 3', 'HU-06, HU-13', 'Domain Invariants Test', 'test(domain): cover group auto-enablement and refund flows'],
    ['434437e', '2026-10-19', 'Sprint 3', 'HU-20', 'SEO & OpenGraph Cards', 'feat(seo): add open graph and twitter card meta tags for course preinscriptions'],
    ['6e0cac6', '2026-10-19', 'Sprint 3', 'HU-20', 'Telegram Broadcast Service', 'feat(telegram): add channel broadcast service for published courses'],
    ['95ce455', '2026-10-20', 'Sprint 3', 'HU-20', 'Social Campaign Modal', 'feat(filament): add social media campaign action and group table actions'],
    ['3b8a12e', '2026-10-20', 'Sprint 3', 'HU-06, HU-21', 'Upcoming Semaphore Rules', 'fix(dashboard): align upcoming groups semaphore with committed capacity rules'],
];

foreach ($commitMapping as $idx => $cm) {
    $row++;
    $sheet3->setCellValue("A{$row}", $cm[0]);
    $sheet3->setCellValue("B{$row}", $cm[1]);
    $sheet3->setCellValue("C{$row}", $cm[2]);
    $sheet3->setCellValue("D{$row}", $cm[3]);
    $sheet3->setCellValue("E{$row}", $cm[4]);
    $sheet3->setCellValue("F{$row}", $cm[5]);

    $sheet3->getStyle("A{$row}")->getFont()->setBold(true);
    $sheet3->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet3->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet3->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet3->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet3->getStyle("D{$row}")->getFont()->setBold(true);

    if ($idx % 2 == 1) {
        $sheet3->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($grayLight);
    }
}

// Bordes pestaña 3
$sheet3->getStyle("A3:F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($grayBorder);

foreach (['A', 'B', 'C', 'D', 'E'] as $c) {
    $sheet3->getColumnDimension($c)->setAutoSize(true);
}
$sheet3->getColumnDimension('F')->setWidth(50);
$sheet3->getStyle('F')->getAlignment()->setWrapText(true);

// Guardar archivo Excel
$outputFile = __DIR__.'/../docs/Historias_de_Usuario_Sprints_Formacion_Continua.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outputFile);

echo "EXCEL_GENERATED_SUCCESSFULLY: {$outputFile}\n";
