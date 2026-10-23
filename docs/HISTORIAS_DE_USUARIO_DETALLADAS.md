# Universidad Mayor de San Simón — Formación Continua
# Documento de Especificación de Historias de Usuario 

---

## Control del Documento
- **Proyecto:** Sistema de Gestión Académica y Certificación Digital Verificable
- **Cliente:** Departamento de Informática y Sistemas — UMSS
- **Metodología:** Scrum / BDD 
- **Marco Académico:** Taller de Ingeniería de Software (TIS)
- **Estado Global:** Sprint 1 — Finalizado (44 Story Points)

---

## Matriz de Cobertura — Sprint 1 (14/09/2026 – 27/09/2026)

| Código | Título de la Historia de Usuario | Rol Principal | SP | Prioridad | Estado |
| :--- | :--- | :--- | :---: | :---: | :---: |
| **HU-01** | Catálogo Público de Oferta Académica con Filtros Dinámicos | Estudiante / Público | 5 | Alta | Finalizada |
| **HU-02** | Preinscripción en Línea en Dos Pasos y Reserva de Cupo | Estudiante / Participante | 8 | Alta | Finalizada |
| **HU-03** | Parametrización de Cursos y Tarifas Institucionales | Administrador | 5 | Alta | Finalizada |
| **HU-04** | Apertura y Control de Horarios de Grupos Académicos | Administrador | 5 | Alta | Finalizada |
| **HU-05** | Verificación y Registro de Pagos en Caja Facultativa | Cajero / Administrador | 8 | Alta | Finalizada |
| **HU-06** | Habilitación Automática y Monitoreo de Grupos por Cupo Mínimo | Administrador | 3 | Media | Finalizada |
| **HU-07** | Validación de Descuento para Auxiliares de Docencia (50%) | Auxiliar / Cajero | 5 | Media | Finalizada |
| **HU-08** | Emisión y Descarga de Boleta de Inscripción con Código QR | Estudiante / Cajero | 5 | Alta | Finalizada |

---

## Especificación Detallada de Historias de Usuario



### HU-01: Catálogo Público de Cursos Ofertados

- **Puntos de Historia:** 5
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 1
- **Módulos Asociados:** Catálogo Público (`CatalogComponent`), Vista Principal (`home.blade.php`), Modelo `Course`

#### Descripción de la Historia
> **Como** visitante o estudiante interesado  
> **Quiero** explorar la oferta de cursos de formación continua con filtros temáticos y disponibilidad en tiempo real  
> **Para** seleccionar un curso y horario conveniente e iniciar mi preinscripción en el sistema.

El usuario accede a la página principal de la plataforma institucional donde visualiza las tarjetas de los cursos vigentes con sus aranceles diferenciados, carga horaria y disponibilidad de plazas. La interfaz reactiva permite filtrar por área temática o búsqueda textual sin recargar la página.

#### Criterios de Aceptación

1. **Dado** un visitante en la página principal, **cuando** carga el catálogo, **entonces** el sistema visualiza únicamente los cursos con estado `Publicado`, ocultando borradores o cursos cancelados.

2. **Dado** el catálogo de cursos desplegado, **cuando** el usuario ingresa un texto en la barra de búsqueda o selecciona un área temática, **entonces** la cuadrícula se actualiza dinámicamente mostrando solo los cursos coincidentes sin recargar la página.

3. **Dado** un curso ofertado en el catálogo, **cuando** se visualiza su tarjeta o modal de detalles, **entonces** el sistema exhibe los aranceles oficiales para `Estudiante UMSS` y `Público Externo` de forma clara y sin recalcular montos en el cliente.

4. **Dado** un curso con grupos abiertos, **cuando** el usuario presiona el botón para consultar horarios, **entonces** se despliega un modal listando cada grupo habilitado, su instructor, su horario asignado y la cantidad de plazas disponibles respecto al cupo máximo.

5. **Dado** un grupo con plazas libres dentro del modal, **cuando** el usuario hace clic en 'Preinscribirme', **entonces** el sistema lo redirige al formulario de preinscripción vinculado específicamente a ese grupo.

6. **Dado** un curso donde la totalidad de grupos alcanzaron su cupo máximo, **cuando** el usuario intenta consultar horarios, **entonces** el sistema despliega el formulario 'Registrar Interés' para anotarse en lista de espera ante futuras aperturas.

---



### HU-02: Preinscripción en Línea en Dos Pasos

- **Puntos de Historia:** 8
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 1
- **Módulos Asociados:** Preinscripciones Web (`PreinscriptionComponent`), Servicio de Preinscripción (`PreinscriptionService`), Modelo `Preinscription`

#### Descripción de la Historia
> **Como** estudiante o participante postulante  
> **Quiero** ingresar mis datos personales y seleccionar mi tipo de participante en un formulario guiado de dos pasos  
> **Para** reservar temporalmente una plaza en el grupo elegido y obtener las instrucciones oficiales de pago.

El postulante ingresa al formulario de preinscripción estructurado en dos pasos: (1) Datos personales y selección de categoría, y (2) Verificación de resumen, cálculo de arancel y confirmación. El sistema garantiza la reserva temporal de la plaza en estado Pendiente de Pago.

#### Criterios de Aceptación

1. **Dado** que el usuario selecciona la categoría `Estudiante UMSS`, **cuando** intenta avanzar al paso 2, **entonces** el sistema exige de forma obligatoria el ingreso del número de matrícula/Código SIS y valida que contenga solo dígitos numéricos.

2. **Dado** un postulante que ya posee una preinscripción activa en un grupo específico, **cuando** intenta registrarse nuevamente en el mismo grupo con el mismo CI y complemento, **entonces** el sistema bloquea el registro con un mensaje de duplicado.

3. **Dado** un correo electrónico ingresado en el formulario, **cuando** se valida el campo, **entonces** el sistema verifica exhaustivamente la sintaxis estándar RFC sin realizar consultas DNS remotas que demoren la respuesta.

4. **Dado** que el usuario completó los campos del Paso 1, **cuando** presiona 'Continuar al Paso 2', **entonces** el sistema muestra el resumen de sus datos y el monto exacto asignado a su tipo de participante sin permitir alteraciones manuales.

5. **Dado** que el usuario confirma su preinscripción en el Paso 2, **cuando** se procesa la solicitud, **entonces** el sistema almacena el registro en estado `Pendiente de Pago`, computa la plaza como cupo comprometido y muestra la pantalla con instrucciones de pago.

6. **Dado** un grupo cuyo estado sea `Cerrado` o pertenezca a un curso no publicado, **cuando** un usuario intenta acceder a la URL directa de preinscripción, **entonces** el sistema deniega el acceso y redirige al catálogo general.

---



### HU-03: Parametrización y Edición de Cursos Académicos

- **Puntos de Historia:** 5
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 1
- **Módulos Asociados:** Panel Administrativo (`CourseResource`), Reglas de Negocio (`BusinessRules`), Modelo `Course`

#### Descripción de la Historia
> **Como** administrador académico del sistema  
> **Quiero** configurar cursos definiendo su carga horaria (20h o 30h), contenidos y aranceles editables  
> **Para** publicar ofertas académicas oficiales con precios aprobados por la jefatura del departamento.

El administrador dispone de un asistente en el panel de control donde define los atributos generales del curso. Al seleccionar la carga horaria académica (20 o 30 horas), el sistema precalcula automáticamente las tarifas sugeridas por normativa institucional, manteniendo las columnas de precios editables como fuente de verdad.

#### Criterios de Aceptación

1. **Dado** el formulario de creación de curso, **cuando** el administrador selecciona una carga horaria de `20 horas`, **entonces** el sistema sugiere automáticamente los aranceles por defecto: 80 Bs (UMSS), 100 Bs (Externo) y 40 Bs (Auxiliar).

2. **Dado** el formulario de creación de curso, **cuando** el administrador selecciona una carga horaria de `30 horas`, **entonces** el sistema sugiere automáticamente los aranceles por defecto: 120 Bs (UMSS), 150 Bs (Externo) y 60 Bs (Auxiliar).

3. **Dado** que el administrador modifica manualmente los aranceles sugeridos por resolución específica, **cuando** guarda el registro del curso, **entonces** el sistema persiste los valores ingresados como fuente de verdad inmutable para cobros y boletas.

4. **Dado** el campo de carga de portada, **cuando** se adjunta una imagen (JPG/PNG), **entonces** el sistema procesa el archivo hacia el almacenamiento oficial en la nube y asocia la ruta optimizada al curso.

5. **Dado** un curso en estado `Publicado`, **cuando** el administrador cambia su estado a `Cancelado`, **entonces** el sistema exige de forma obligatoria el campo `motivo_cancelacion` antes de permitir guardar los cambios.

---



### HU-04: Apertura y Control de Horarios de Grupos

- **Puntos de Historia:** 5
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 1
- **Módulos Asociados:** Panel Administrativo (`GroupResource`, `GroupsRelationManager`), Modelo `Group`

#### Descripción de la Historia
> **Como** administrador académico  
> **Quiero** crear grupos definiendo instructor, aula, cupos (mínimo 15) y hora de inicio  
> **Para** calcular automáticamente la hora de finalización y organizar el cronograma de 10 clases hábiles.

La apertura de un grupo requiere asignar un docente responsable y los parámetros de aula y capacidad. El sistema calcula de manera reactiva la hora_fin de acuerdo a la carga horaria del curso (1.5 horas por sesión para 20h; 2.5 horas por sesión para 30h), asegurando la unicidad del nombre del grupo dentro del curso.

#### Criterios de Aceptación

1. **Dado** un grupo perteneciente a un curso de `20 horas`, **cuando** el administrador ingresa la hora de inicio `09:45`, **entonces** el sistema calcula y fija reactivamente la hora de fin en `11:15` (1.5 horas por sesión).

2. **Dado** un grupo perteneciente a un curso de `30 horas`, **cuando** el administrador ingresa la hora de inicio `14:00`, **entonces** el sistema calcula y fija reactivamente la hora de fin en `16:30` (2.5 horas por sesión).

3. **Dado** un curso que ya cuenta con un grupo denominado `Grupo 1`, **cuando** el administrador intenta crear otro grupo con el mismo nombre para el mismo curso, **entonces** el sistema rechaza la creación por restricción de nombre único.

4. **Dado** el campo de cupo mínimo en el formulario, **cuando** se intenta definir un valor inferior a `15`, **entonces** el sistema restablece o advierte el límite mínimo institucional garantizando la viabilidad del grupo.

5. **Dado** un grupo con fecha de inicio y horario establecidos, **cuando** se activa la programación de sesiones, **entonces** el sistema programa 10 sesiones hábiles consecutivas de lunes a viernes, omitiendo feriados institucionales.

---



### HU-05: Verificación y Confirmación de Pagos en Caja

- **Puntos de Historia:** 8
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 1
- **Módulos Asociados:** Módulo de Caja y Pagos (`PaymentResource`, `PaymentService`, `PaymentObserver`)

#### Descripción de la Historia
> **Como** cajero o administrador facultativo  
> **Quiero** verificar el comprobante de pago físico o digital del postulante y contrastar el monto exacto  
> **Para** cambiar la preinscripción a estado 'Inscrito', consolidar la matrícula y despachar el correo de confirmación.

El personal de caja busca la preinscripción, corrobora la autenticidad del comprobante y registra la transacción atómica que valida el importe exacto, actualiza el estado a Inscrito, registra la auditoría del cajero y encola la notificación oficial.

#### Criterios de Aceptación

1. **Dado** un postulante preinscrito con tarifa exigida de `80 Bs`, **cuando** el cajero intenta registrar un pago con monto de `70 Bs`, **entonces** el sistema rechaza la operación informando que el importe no concuerda con el arancel exigido.

2. **Dado** un pago verificado correctamente con el monto exacto, **cuando** se confirma la transacción en el sistema, **entonces** el estado del pago pasa a `Verificado`, la preinscripción cambia a `Inscrito` y se registra la auditoría del cajero.

3. **Dado** un pago que ha sido consolidado como verificado, **cuando** concluye la transacción en la base de datos, **entonces** el sistema encola inmediatamente el envío del correo electrónico de confirmación con los detalles del curso.

4. **Dado** un comprobante falso, ilegible o duplicado, **cuando** el cajero pulsa 'Rechazar Pago' e ingresa el motivo correspondiente, **entonces** el pago y la preinscripción pasan a `Rechazado`, se libera el cupo y se notifica al estudiante.

5. **Dado** un participante inscrito que ya cuenta con un certificado digital emitido en el curso, **cuando** el cajero visualiza la fila del participante, **entonces** la acción de rechazo de pago permanece oculta e inhabilitada.

---



### HU-06: Habilitación Automática y Monitoreo de Grupos

- **Puntos de Historia:** 3
- **Prioridad:** Media
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 1
- **Módulos Asociados:** Observadores de Modelo (`PreinscriptionObserver`), Modelo `Group`, Dashboard (`UpcomingGroupsOverview`)

#### Descripción de la Historia
> **Como** administrador académico  
> **Quiero** evaluar en tiempo real el cupo comprometido de cada grupo mediante observadores de dominio  
> **Para** habilitar automáticamente los grupos que alcanzan el mínimo de 15 participantes y monitorear la viabilidad desde el dashboard.

El sistema monitorea de forma continua las plazas comprometidas (sumatoria de preinscripciones en estado Pendiente de Pago e Inscrito). Cuando un grupo en estado No Habilitado alcanza o supera su cupo mínimo (15), conmuta automáticamente su estado a Habilitado.

#### Criterios de Aceptación

1. **Dado** un grupo en estado `No Habilitado` con cupo mínimo configurado en 15, **cuando** se registra una nueva preinscripción que eleva el total de plazas comprometidas a 15, **entonces** el sistema actualiza automáticamente el estado a `Habilitado`.

2. **Dado** un grupo con 10 participantes `Inscritos` y 5 en `Pendiente de Pago`, **cuando** el observador evalúa la capacidad ocupada, **entonces** computa un total de 15 plazas comprometidas y promueve el grupo a `Habilitado`.

3. **Dado** un grupo `Habilitado` que aún no ha iniciado clases, **cuando** un estudiante se retira o se rechaza un pago dejando el cupo en 14, **entonces** el sistema conmuta el estado nuevamente a `No Habilitado`.

4. **Dado** un grupo cuyo estado sea `En curso` o `Finalizado`, **cuando** se procesa la baja de un participante, **entonces** el sistema mantiene el grupo en su estado operativo correspondiente y no degrada su condición a no habilitado.

5. **Dado** el panel de control del administrador, **cuando** se visualiza el widget de semáforos, **entonces** cada grupo expone con precisión su conteo de cupos comprometidos y su color semántico conforme a las reglas institucionales.

---



### HU-07: Validación de Descuento para Auxiliares de Laboratorio (50%)

- **Puntos de Historia:** 5
- **Prioridad:** Media
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 1
- **Módulos Asociados:** Preinscripciones Web (`PreinscriptionComponent`), Panel de Caja (`PaymentResource`), Almacenamiento en Nube

#### Descripción de la Historia
> **Como** auxiliar o practicante de laboratorio del Departamento de Informática  
> **Quiero** adjuntar la copia de mi certificado de auxiliar concluido durante mi preinscripción  
> **Para** acceder al beneficio del 50% de descuento sobre el precio de estudiante universitario en los cursos ofertados.

Los estudiantes que completaron funciones de auxiliares practicantes en laboratorios de cómputo tienen derecho a un 50% de descuento en aranceles. Durante la preinscripción, el sistema habilita la carga del comprobante en PDF/imagen y en caja se valida el documento antes de ratificar el pago con tarifa preferencial.

#### Criterios de Aceptación

1. **Dado** que un postulante selecciona la categoría `Auxiliar Practicante`, **cuando** intenta enviar el formulario sin adjuntar archivo, **entonces** el sistema exige de forma obligatoria el archivo probatorio en formato PDF, JPG o PNG de hasta 5 MB.

2. **Dado** un curso de 20 horas cuyo arancel de estudiante UMSS es de `80 Bs`, **cuando** el auxiliar concluye su postulación, **entonces** el sistema fija el arancel a cobrar en exactamente `40 Bs` (50% del precio regular UMSS).

3. **Dado** un curso de 30 horas cuyo arancel de estudiante UMSS es de `120 Bs`, **cuando** el auxiliar concluye su postulación, **entonces** el sistema fija el arancel a cobrar en exactamente `60 Bs`.

4. **Dado** que el cajero atiende el pago de un postulante en categoría `Auxiliar Practicante`, **cuando** abre el modal de cobranza, **entonces** el sistema provee un enlace directo para previsualizar el certificado digital adjunto antes de permitir verificar la transacción.

5. **Dado** que se carga un certificado de auxiliar válido, **cuando** se procesa la preinscripción, **entonces** el archivo se almacena en el disco oficial en la nube bajo una ruta protegida e indexada al registro del estudiante.

---



### HU-08: Emisión de Boleta de Inscripción Oficial en PDF

- **Puntos de Historia:** 5
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 1
- **Módulos Asociados:** Controlador de Boleta (`BoletaController`), Servicio de Boletas (`BoletaService`), Plantilla PDF (`boleta.blade.php`)

#### Descripción de la Historia
> **Como** estudiante con pago verificado o cajero facultativo  
> **Quiero** generar y descargar la boleta oficial de inscripción en formato PDF con membrete institucional y código QR  
> **Para** disponer del comprobante legal y académico de mi matriculación formal en el curso.

Una vez que el pago ha sido validado en caja, el sistema habilita la descarga inmediata de la boleta de inscripción oficial en PDF, con escudo de la UMSS, número correlativo, desglose de importes abonados, datos del curso/grupo y un código QR de autenticación.

#### Criterios de Aceptación

1. **Dado** un postulante en estado `Pendiente de Pago` o `Rechazado`, **cuando** se intenta acceder a la generación de su boleta, **entonces** el sistema deniega el acceso indicando que solo preinscripciones formalmente inscritas poseen boleta.

2. **Dado** el enlace provisto al estudiante o cajero para descargar la boleta, **cuando** se efectúa la petición web, **entonces** el sistema valida la firma criptográfica de la URL (`signed middleware`), impidiendo la descarga si los parámetros fueron alterados.

3. **Dado** un estudiante inscrito en un curso, **cuando** se genera la boleta en PDF, **entonces** el documento expone con exactitud el correlativo oficial, los datos del estudiante, el grupo, el horario de clases y el importe abonado.

4. **Dado** el documento PDF emitido, **cuando** se escanea el código QR impreso mediante cualquier dispositivo móvil, **entonces** el código redirecciona a la URL de validación del sistema certificando la regularidad de la matrícula.

5. **Dado** el archivo PDF generado en el navegador, **cuando** se visualiza o se envía a la impresora física de caja, **entonces** el documento mantiene sus proporciones, colores corporativos UMSS y tipografía sin desbordamientos.

---



### HU-09: Integración y Sincronización con Google Sheets

- **Puntos de Historia:** 8
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 2
- **Módulos Asociados:** Servicio de Google Sheets (`GoogleSheetsService`), Generador de Plantillas (`TemplateSheetBuilder`, `SheetExportBuilder`)

#### Descripción de la Historia
> **Como** administrador o docente  
> **Quiero** exportar y sincronizar las listas de inscritos, notas y asistencia directamente a Google Sheets mediante la API oficial  
> **Para** facilitar el llenado de planillas docentes sin duplicar tareas administrativas ni requerir doble captura de datos.

El sistema se conecta con Google Drive / Google Sheets mediante cuenta de servicio oficial para exportar la estructura de carpetas y hojas de cálculo con pestañas de Asistencia y Calificaciones, aplicando fórmulas nativas de Sheets y formateo institucional.

#### Criterios de Aceptación

1. **Dado** un grupo habilitado con estudiantes inscritos, **cuando** el administrador ejecuta la exportación a Google Sheets, **entonces** el sistema crea o actualiza la hoja de cálculo en Drive estructurada en pestañas 'Asistencia' y 'Calificaciones'.

2. **Dado** el formato de la hoja exportada, **cuando** se examinan las columnas de totales y ponderaciones, **entonces** el sistema inyecta fórmulas dinámicas de Sheets (`SUMA`, `PROMEDIO`) en lugar de valores estáticos planos.

3. **Dado** que se realizan múltiples consultas de hojas durante una exportación, **cuando** se invoca el servicio, **entonces** el sistema memoiza las pestañas del documento evitando llamadas excedentes a la API de Google.

4. **Dado** un cambio en los datos de asistencia o nómina de estudiantes, **cuando** se dispara el observador de sincronización, **entonces** el sistema encola la tarea en segundo plano respetando las cuotas de tasa de la API de Google.

5. **Dado** un fallo de conexión o cuota con los servidores de Google, **cuando** se procesa la sincronización, **entonces** el sistema registra el evento en logs de auditoría sin interrumpir el funcionamiento de la aplicación web.

---



### HU-10: Registro y Control de Asistencia Docente y QR

- **Puntos de Historia:** 5
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 2
- **Módulos Asociados:** Módulo de Asistencia (`AttendanceService`), Escáner QR (`AttendanceScanner.vue`), Matriz de Asistencia (`MarkAttendancePage`)

#### Descripción de la Historia
> **Como** instructor o docente del curso  
> **Quiero** registrar la asistencia de los estudiantes mediante escaneo de código QR o matriz interactiva en línea  
> **Para** controlar la asistencia de forma ágil y transparente, asegurando el cumplimiento del porcentaje mínimo para certificación.

El instructor dispone de dos modalidades para asentar asistencia: escaneo móvil del código QR de la credencial/boleta del alumno (con geolocalización opcional del aula) o marcado masivo en la grilla web interactiva con guardado atómico por lote.

#### Criterios de Aceptación

1. **Dado** que un estudiante presenta su código QR en el aula, **cuando** el instructor lo escanea desde el módulo móvil, **entonces** el sistema identifica la preinscripción por CI, valida que pertenezca al grupo y registra la asistencia de la sesión actual.

2. **Dado** un registro de asistencia mediante escaneo móvil, **cuando** el dispositivo proporciona coordenadas GPS, **entonces** el sistema almacena opcionalmente la latitud y longitud tras validar que estén dentro de rangos geográficos válidos.

3. **Dado** un instructor autenticado en el panel docente, **cuando** un estudiante no pertenece a su grupo asignado, **entonces** el sistema bloquea el registro con un mensaje de advertencia indicando que el alumno no corresponde a la nómina.

4. **Dado** el módulo de marcado manual por matriz, **cuando** el docente ajusta estados (`Presente`, `Falta`, `Licencia`) para múltiples alumnos y guarda, **entonces** el sistema persiste el lote de forma atómica en una única transacción.

5. **Dado** un grupo cuya ponderación de asistencia forma parte de la nota final, **when** se asienta una asistencia, **then** el sistema actualiza reactivamente el porcentaje acumulado del estudiante.

---



### HU-11: Calendario de Feriados y Reprogramación de Sesiones

- **Puntos de Historia:** 3
- **Prioridad:** Media
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 2
- **Módulos Asociados:** Gestión de Feriados (`HolidayService`), Modelo `Holiday`, Planificador de Sesiones (`SessionService`)

#### Descripción de la Historia
> **Como** administrador académico o instructor  
> **Quiero** registrar días feriados en el calendario institucional y reprogramar sesiones académicas afectadas  
> **Para** garantizar que cada curso complete sus 10 clases reglamentarias sin solapamiento de horarios de docentes ni aulas.

El sistema administra el calendario de feriados oficiales y jornadas de suspensión de actividades. Al programar grupos o posponer una clase, el sistema busca automáticamente el siguiente día hábil disponible verificando que no existan conflictos de horario del docente.

#### Criterios de Aceptación

1. **Dado** un feriado registrado en el calendario (ej. feriado nacional o departamental), **cuando** el sistema genera el cronograma de un grupo, **entonces** salta automáticamente dicha fecha y programa la sesión en el siguiente día hábil de lunes a viernes.

2. **Dado** que una sesión debe ser pospuesta por causas de fuerza mayor, **cuando** el docente solicita la reprogramación, **entonces** la sesión se marca con la bandera `es_pospuesta` y se le asigna la nueva fecha y hora autorizada.

3. **Dado** un intento de reprogramar una sesión a un horario determinado, **cuando** el instructor ya tiene otra clase programada en ese mismo rango horario, **entonces** el sistema rechaza la reprogramación por colisión de agenda docente.

4. **Dado** un intento de reprogramar una sesión, **cuando** otra sesión del mismo grupo coincide en el mismo día y horario, **entonces** el sistema rechaza la operación informando del solapamiento.

5. **Dado** que una sesión reprogramada es confirmada, **cuando** se persiste el cambio, **entonces** se dispara la sincronización automática de la fecha en la planilla de Google Sheets correspondiente.

---



### HU-12: Fusión Asistida de Grupos y Traspaso de Estudiantes

- **Puntos de Historia:** 8
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 2
- **Módulos Asociados:** Servicio de Grupos (`GroupService`), Fusión Asistida en Filament (`GroupsTableAction`), Observer de Fusión

#### Descripción de la Historia
> **Como** administrador académico  
> **Quiero** fusionar dos grupos paralelos de un mismo curso traspasando participantes de un grupo de baja demanda hacia un grupo receptor  
> **Para** consolidar grupos viables con al menos 15 alumnos y optimizar la asignación de aulas y carga horaria docente.

Cuando uno o más grupos no alcanzan el cupo mínimo requerido (15), el administrador puede ejecutar la fusión asistida. El sistema valida la capacidad disponible en el grupo de destino, traspasa las preinscripciones activas, reasigna asistencias previas y cierra el grupo origen notificando a los estudiantes.

#### Criterios de Aceptación

1. **Dado** un grupo origen con 8 inscritos y un grupo destino con capacidad libre suficiente, **cuando** el administrador ejecuta la fusión asistida, **entonces** el sistema traslada a los participantes al grupo receptor de forma atómica.

2. **Dado** un grupo destino con capacidad disponible de 5 plazas, **cuando** se intenta fusionar un grupo origen que tiene 7 participantes a trasladar, **entonces** el sistema bloquea la fusión informando exceso de capacidad máxima.

3. **Dado** que un participante en el grupo origen solicitó devolución y se encuentra en trámite, **cuando** se calcula la capacidad necesaria en destino, **entonces** el sistema excluye a los participantes con devolución en curso del conteo requerido.

4. **Dado** que se concreta la fusión satisfactoriamente, **cuando** finaliza la operación, **entonces** el sistema envía una notificación por correo a cada estudiante trasladado indicándole su nuevo horario y aula asignada.

5. **Dado** un estudiante que casualmente ya poseía una preinscripción en el grupo destino, **cuando** se intenta fusionar, **entonces** el sistema detecta la duplicidad y solicita la resolución previa del registro antes de completar la fusión.

---



### HU-13: Gestión de Reembolsos y Devoluciones de Caja

- **Puntos de Historia:** 6
- **Prioridad:** Media
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 2
- **Módulos Asociados:** Servicio de Pagos (`PaymentService`), Flujo de Devoluciones (`RefundService`), Reportes Financieros

#### Descripción de la Historia
> **Como** cajero o administrador facultativo  
> **Quiero** procesar solicitudes de devolución de arancel cuando un grupo se cierra o el estudiante ejerce su derecho a retiro  
> **Para** mantener el balance de caja transparente y registrar la devolución formal liberando la matrícula del estudiante.

Si un grupo es cerrado por falta de cupo o un participante formaliza su renuncia antes del inicio del curso, se gestiona el reembolso. El sistema registra el estado transitorio 'Devolución Pendiente' y, tras el desembolso en efectivo o comprobante bancario, consolida el estado 'Reembolsado'.

#### Criterios de Aceptación

1. **Dado** un participante con preinscripción en estado `Inscrito` y pago verificado, **cuando** se aprueba una devolución, **entonces** la preinscripción y su pago pasan al estado transitorio `Devolución Pendiente`.

2. **Dado** un participante que ya tiene una solicitud de reembolso pendiente o no cuenta con pago verificado, **cuando** se intenta solicitar otra devolución, **entonces** el sistema bloquea la acción.

3. **Dado** un reembolso en estado `Devolución Pendiente`, **cuando** el cajero entrega los fondos y confirma el reintegro, **entonces** el pago y la preinscripción pasan a estado definitivo `Reembolsado`.

4. **Dado** un estudiante cuya devolución se encuentra en trámite, **cuando** la administración decide revertir la solicitud por acuerdo con el alumno, **entonces** el sistema restaura la preinscripción a `Inscrito` sin duplicar transacciones.

5. **Dado** que un pago es marcado como reembolsado, **cuando** se emiten las planillas de arqueo de caja, **entonces** el importe devuelto se deduce del total consolidado de recaudación del curso.

---



### HU-14: Bandeja de Solicitudes Estudiantiles (Traspasos y Devoluciones)

- **Puntos de Historia:** 6
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 2
- **Módulos Asociados:** Módulo de Solicitudes (`StudentRequestService`), Panel Estudiante (`StudentRequestResource`), Bandeja Admin

#### Descripción de la Historia
> **Como** estudiante autenticado y administrador  
> **Quiero** gestionar trámites académicos en línea para solicitar cambio de grupo o devolución de dinero  
> **Para** evitar filas presenciales y permitir a la administración aprobar o rechazar solicitudes de forma centralizada.

El estudiante dispone de un portal personal donde visualiza sus preinscripciones activas y puede radicar solicitudes de cambio de grupo o devolución. El administrador atiende la bandeja de entrada, donde puede aprobar con un clic (ejecutando el traspaso automático) o rechazar con causal.

#### Criterios de Aceptación

1. **Dado** un estudiante autenticado con preinscripción activa, **cuando** solicita un cambio de grupo dentro del mismo curso, **entonces** el sistema registra la solicitud en estado `Pendiente` y notifica a la coordinación.

2. **Dado** que un estudiante ya tiene una solicitud pendiente del mismo tipo sobre su preinscripción, **cuando** intenta ingresar una segunda solicitud idéntica, **entonces** el sistema bloquea el duplicado.

3. **Dado** que el administrador revisa una solicitud de traspaso y el grupo destino cuenta con plazas disponibles, **cuando** pulsa 'Aprobar', **entonces** el sistema traslada automáticamente al estudiante y marca la solicitud como `Aprobada`.

4. **Dado** que el grupo destino no cuenta con cupo disponible en el momento de la revisión, **cuando** el administrador intenta aprobar el traspaso, **entonces** el sistema detiene la operación advirtiendo la falta de cupo.

5. **Dado** que el administrador rechaza una solicitud argumentando la causal, **cuando** confirma el rechazo, **entonces** la solicitud pasa a estado `Rechazado` y se despacha un correo explicativo al estudiante.

---



### HU-15: Lista de Interés y Alerta de Demanda para Cursos Llenos

- **Puntos de Historia:** 5
- **Prioridad:** Media
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 2
- **Módulos Asociados:** Servicio de Interés (`CourseInterestService`), Componente Catálogo, Notificaciones en Cola

#### Descripción de la Historia
> **Como** estudiante interesado y administrador académico  
> **Quiero** anotarme en lista de espera para cursos sin cupo y notificar al administrador sobre demanda insatisfecha  
> **Para** evaluar la apertura de nuevos grupos adicionales basados en el interés real de estudiantes en espera.

Cuando un curso tiene todos sus grupos llenos, el catálogo activa el registro de interés. Si la demanda acumulada alcanza el umbral de viabilidad (15 interesados), el sistema alerta a la administración para habilitar un nuevo grupo y notifica por correo a los interesados para que procedan a preinscribirse.

#### Criterios de Aceptación

1. **Dado** un curso cuyos grupos habilitados están al 100% de capacidad, **cuando** un visitante abre el catálogo, **entonces** se visualiza la llamada a la acción 'Registrar Interés' solicitando nombre y correo.

2. **Dado** un visitante que ya registró su correo en la lista de espera de un curso, **cuando** intenta registrar el mismo correo nuevamente, **entonces** el sistema muestra un mensaje amigable indicando que ya se encuentra en lista.

3. **Dado** un curso con cupos libres en al menos uno de sus grupos, **cuando** el visitante consulta el catálogo, **entonces** el sistema oculta el formulario de lista de espera y prioriza la preinscripción directa.

4. **Dado** que la lista de espera acumula 15 interesados, **cuando** se alcanza dicho número, **entonces** el sistema emite una alerta de demanda al administrador sugiriendo la apertura de un grupo adicional.

5. **Dado** que la administración habilita un nuevo grupo para el curso demandado, **cuando** se publica la apertura, **entonces** el sistema envía una notificación por correo a todos los interesados registrados invitándolos a preinscribirse.

---



### HU-16: Matriz de Calificaciones y Criterios de Evaluación Docente

- **Puntos de Historia:** 5
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 3
- **Módulos Asociados:** Servicio de Evaluación (`EvaluationService`), Matriz de Calificaciones (`GradeMatrixPage`), Modelo `Grade`

#### Descripción de la Historia
> **Como** instructor o docente del curso  
> **Quiero** configurar criterios de evaluación ponderados (hasta 100%) y registrar calificaciones finales en una matriz interactiva  
> **Para** evaluar objetivamente el rendimiento académico de los participantes y determinar la condición de aprobación.

El docente ingresa al panel de calificaciones donde define sus criterios (ej. Prácticas 40%, Proyecto 40%, Asistencia 20%). En una matriz tabular reactiva ingresa las notas de cada estudiante, visualizando el promedio en tiempo real antes de guardar de forma atómica.

#### Criterios de Aceptación

1. **Dado** un docente configurando los criterios de evaluación de su curso, **cuando** define las ponderaciones, **entonces** el sistema valida que la sumatoria total de los criterios equivalga exactamente al 100% de la nota.

2. **Dado** un curso con ponderación de asistencia asignada, **cuando** se visualiza la matriz de notas, **entonces** el sistema calcula automáticamente el puntaje proporcional de asistencia a partir de las sesiones dictadas y asistidas.

3. **Dado** que el docente ingresa o edita notas en la grilla interactiva, **cuando** modifica un valor, **entonces** el sistema calcula la previsualización de la nota final reactivamente y resalta los cambios no guardados.

4. **Dado** un valor de nota ingresado fuera del rango permitido (menor a 0 o mayor a 100), **cuando** el docente intenta guardar, **entonces** el sistema valida y rechaza la entrada indicando el error de rango.

5. **Dado** que existen cambios staged no guardados en la matriz, **cuando** el usuario intenta cambiar de grupo o abandonar la vista, **entonces** el sistema despliega un diálogo de confirmación para evitar la pérdida accidental de datos.

---



### HU-17: Cierre de Curso y Emisión Directa de Certificados Digitales

- **Puntos de Historia:** 8
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 3
- **Módulos Asociados:** Servicio de Cierre (`CourseService`), Servicio de Certificados (`CertificateService`), Almacenamiento en Nube

#### Descripción de la Historia
> **Como** administrador académico o instructor  
> **Quiero** formalizar el cierre definitivo del curso y emitir masivamente los certificados con código único y código QR  
> **Para** otorgar acreditaciones académicas oficiales infalsificables a estudiantes aprobados y al docente instructor.

Al concluir las sesiones y calificaciones, se ejecuta la acción de Cierre de Curso. El sistema genera los certificados PDF para alumnos con nota >= 70 (Aprobación), alumnos con nota < 70 y asistencia cumplida (Asistencia) y para el instructor, subiéndolos a Cloudinary y notificando por correo.

#### Criterios de Aceptación

1. **Dado** un curso en estado `En curso` con todas sus sesiones concluidas, **cuando** el administrador presiona 'Cerrar Curso', **entonces** el estado del curso cambia a `Finalizado` y se bloquea la alteración de notas.

2. **Dado** el proceso de cierre, **cuando** se evalúa a cada estudiante inscrito, **entonces** el sistema genera certificado de `Aprobación` para notas >= 70 puntos y certificado de `Asistencia` para notas menores que cumplan asistencia mínima.

3. **Dado** el cierre del curso, **cuando** se emite el certificado para el docente instructor, **entonces** el documento acredita la carga horaria total dictada y los grupos que estuvieron a su cargo.

4. **Dado** un certificado generado por el sistema, **cuando** se crea el registro, **entonces** se le asigna un código alfanumérico único irrepetible (`CERT-YYYY-XXXXXXXX`) y un código QR que apunta a la URL oficial de verificación.

5. **Dado** que los documentos PDF son generados, **cuando** se persisten en almacenamiento en la nube, **entonces** se clasifican bajo el tipo de recurso binario protegido y se encola la notificación por correo al alumno con su enlace de descarga.

---



### HU-18: Verificación Pública de Certificados en Línea

- **Puntos de Historia:** 3
- **Prioridad:** Media
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 3
- **Módulos Asociados:** Verificador Público (`CertificateVerificationComponent`), Ruta Pública (`/verificar-certificado/{codigo}`)

#### Descripción de la Historia
> **Como** cualquier persona, empleador o entidad externa  
> **Quiero** ingresar el código único de un certificado o escanear su código QR en la plataforma web pública  
> **Para** constatar la autenticidad, vigencia y datos académicos del certificado expedido por la UMSS.

Cualquier persona puede verificar la autenticidad de un certificado emitido mediante la ruta pública `/verificar-certificado`. El sistema busca el código en la base de datos y despliega los detalles oficiales: nombre del estudiante, curso, carga horaria, fechas, nota final y enlace de descarga del documento legal.

#### Criterios de Aceptación

1. **Dado** un usuario que ingresa a la página pública de verificación, **cuando** digita un código único existente y presiona 'Verificar', **entonces** el sistema exhibe los metadatos oficiales del certificado con distintivos institucionales de la UMSS.

2. **Dado** un certificado emitido y vigente en el sistema, **cuando** se consulta su información pública, **entonces** la vista ofrece un botón oficial para descargar el archivo PDF original de respaldo.

3. **Dado** que el usuario ingresa un código inexistente o alterado, **cuando** consulta el verificador, **entonces** el sistema despliega una alerta clara indicando: 'No se encontró ningún certificado emitido con el código ingresado.'

4. **Dado** un usuario que escanea el código QR físico de un certificado impreso, **cuando** el escáner abre el enlace en su navegador, **entonces** el sistema lo redirige de inmediato a la vista de verificación del código correspondiente.

5. **Dado** el endpoint público de verificación, **cuando** recibe peticiones sucesivas, **entonces** se aplica limitación de tasa (`throttle: 30 peticiones por minuto`) para salvaguardar el servicio frente a intentos de fuerza bruta.

---



### HU-19: Exportación de Actas de Cierre y Balances Financieros en Excel

- **Puntos de Historia:** 8
- **Prioridad:** Alta
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 3
- **Módulos Asociados:** Exportador Financiero (`FinancialReportExport`), Servicio de Cierre (`CourseClosingService`), Laravel Excel

#### Descripción de la Historia
> **Como** administrador o autoridad del departamento  
> **Quiero** exportar planillas financieras y actas de cierre en formato Microsoft Excel (.xlsx)  
> **Para** respaldar legalmente los ingresos recaudados, honorarios docentes y actas de calificaciones para Jefatura y Auditoría.

El sistema proporciona reportes consolidados en Excel de acuerdo a los formatos oficiales de la UMSS. El reporte financiero discrimina ingresos brutos por caja, retenciones facultativas (20%), presupuesto de laboratorio (80%) y honorarios líquidos del docente, mientras el acta de cierre respalda la nómina final de notas.

#### Criterios de Aceptación

1. **Dado** un curso finalizado, **cuando** el administrador solicita el reporte financiero en Excel, **entonces** el sistema descarga una planilla con el desglose exacto de pagos efectivos recaudados y descuentos aplicados.

2. **Dado** el balance de caja generado, **cuando** se totaliza la recaudación, **entonces** el reporte calcula automáticamente el 20% institucional para la facultad y el 80% neto asignado para gastos de laboratorio y pago del instructor.

3. **Dado** que existieron pagos rechazados o reembolsos formalizados durante el curso, **cuando** se construye el balance, **entonces** el sistema excluye dichos importes garantizando que solo los cobros efectivos alimenten el saldo total.

4. **Dado** el requerimiento del acta académica de cierre, **cuando** se exporta la nómina, **entonces** el archivo incluye la lista completa de inscritos, asistencias, notas por criterio, promedio final y condición (Aprobado / Reprobado).

5. **Dado** el formato del documento descargado, **cuando** se abre en Microsoft Excel, **entonces** presenta el encabezado corporativo con imagotipo de la UMSS, celdas bloqueadas de solo lectura y estilos institucionales.

---



### HU-20: Campaña de Difusión en Redes Sociales (Telegram, WhatsApp, FB)

- **Puntos de Historia:** 6
- **Prioridad:** Media
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 3
- **Módulos Asociados:** Servicio de Telegram (`TelegramChannelService`), Generador de Campañas (`CampanaRedes`), MetaTags OpenGraph

#### Descripción de la Historia
> **Como** administrador o encargado de difusión académica  
> **Quiero** difundir automáticamente los cursos ofertados mediante canales de Telegram y generación de enlaces de WhatsApp y Facebook  
> **Para** promocionar los cursos de manera ágil y masiva para alcanzar rápidamente el cupo mínimo de habilitación.

El administrador cuenta con herramientas de marketing integradas en el panel. Puede difundir con un clic la ficha gráfica del curso con su texto promocional al canal oficial de Telegram vía Bot API y generar enlaces formateados para estados de WhatsApp y grupos de Facebook con metatags OpenGraph.

#### Criterios de Aceptación

1. **Dado** un curso publicado con imagen de portada, **cuando** el administrador ejecuta la acción 'Difundir en Telegram', **entonces** el sistema transmite la fotografía y el texto resumen con aranceles y enlace directo al canal oficial configurado.

2. **Dado** un curso publicado sin foto de portada, **cuando** se difunde por Telegram, **entonces** el servicio conmuta automáticamente a envío de mensaje de texto formateado sin fallar.

3. **Dado** que la clave o token del bot de Telegram es inválido o no está configurado, **cuando** se intenta la difusión, **entonces** el sistema maneja la excepción elegantemente y no filtra el token en logs ni vistas de error.

4. **Dado** el modal de campaña de redes en Filament, **cuando** el administrador selecciona 'Compartir por WhatsApp', **entonces** se genera un enlace preformateado con mensaje persuasivo y URL corta del curso para chat móvil.

5. **Dado** que se comparte el enlace del curso en redes sociales (Facebook, Twitter, WhatsApp), **cuando** la plataforma genera la previsualización, **entonces** el sistema entrega las etiquetas OpenGraph y Twitter Cards con la imagen y título oficial.

---



### HU-21: Dashboard Integral de Monitoreo y Semáforos de Capacidad

- **Puntos de Historia:** 5
- **Prioridad:** Media
- **Estado:** Finalizada
- **Versión:** 1.0
- **Sprint:** Sprint 3
- **Módulos Asociados:** Panel Administrativo (`Dashboard`), Widgets Semafóricos (`UpcomingGroupsOverview`), Widgets de Métricas

#### Descripción de la Historia
> **Como** administrador y coordinador académico  
> **Quiero** visualizar métricas clave de recaudación, estado de grupos con semáforos de alerta y tasa de preinscripciones  
> **Para** tomar decisiones operativas inmediatas respecto a apertura, fusión o cancelación de cursos en tiempo real.

El panel principal de administración expone widgets ejecutivos que resumen el estado operativo de los cursos. Los grupos vigentes se muestran con un semáforo de viabilidad basado en el cupo comprometido, complementado con tarjetas estadísticas de inscritos y alertas de demanda insatisfecha.

#### Criterios de Aceptación

1. **Dado** que el administrador ingresa al panel principal, **cuando** carga el dashboard, **entonces** visualiza el widget 'Próximos Grupos' con la lista de grupos ordenados por proximidad de inicio y su estado de viabilidad.

2. **Dado** un grupo con menos de 10 preinscripciones comprometidas, **cuando** se renderiza su tarjeta, **entonces** el sistema le asigna un distintivo de alerta rojo indicando 'Riesgo de Cierre / Baja Demanda'.

3. **Dado** un grupo con entre 10 y 14 preinscripciones, **cuando** se renderiza su tarjeta, **entonces** el sistema le asigna un distintivo ámbar indicando 'Próximo a Habilitación'.

4. **Dado** un grupo con 15 o más preinscripciones, **cuando** se renderiza su tarjeta, **entonces** el sistema le asigna un distintivo verde indicando 'Grupo Habilitado / Cupo Alcanzado'.

5. **Dado** el widget de métricas globales, **cuando** se consultan las cifras, **entonces** computa el total de participantes inscritos, recaudación neta del ciclo y cantidad de cursos activos sin ejecutar consultas lentas o redundantes.

---

