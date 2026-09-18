## Épicas

|  |  |  |  |  |  |  |  |  |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Plataforma de Formación Continua con Certificación Digital Verificable — Épicas |  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |  |
| ID Épica | Nombre de la Épica | Descripción | Prioridad |  |  |  |  |  |
| EP-01 | Gestión de Cursos | Creación, configuración, edición y administración de la oferta de cursos de actualización tecnológica: nombre, contenido, carga horaria, horarios, instructor, nivel y costos. | Highest |  |  |  |  |  |
|  | Publicación y Difusión de Cursos | Publicación del curso en la plataforma para habilitar el periodo de preinscripción y apoyo a su difusión (afiches, redes sociales, WhatsApp). | High |  |  |  |  |  |
| EP-02 | Preinscripción de Estudiantes | Registro de interesados en un curso y grupo específico, control de cupos mínimos/máximos, fusión y cierre de grupos, y gestión de lista de espera. | Highest |  |  |  |  |  |
|  | Inscripción y Pagos | Confirmación de inscripción tras la verificación del pago en caja facultativa, cálculo de precios diferenciados y becas, emisión de comprobantes y gestión de cambios/retiros. | Highest |  |  |  |  |  |
| EP-03 | Desarrollo del Curso (Sesiones y Asistencia) | Programación de sesiones del curso y registro del control de asistencia tanto del estudiante como del instructor durante las dos semanas de duración. | High |  |  |  |  |  |
|  | Evaluación y Calificación | Configuración de criterios de evaluación por curso, registro de notas y cálculo automático de la nota final y el tipo de certificado correspondiente. | High |  |  |  |  |  |
| EP-04 | Emisión y Gestión de Certificados | Generación de certificados digitales de aprobación/asistencia para estudiantes e instructores, con código único de verificación y flujo de firma de autoridades. | Highest |  |  |  |  |  |
|  | Verificación Pública de Certificados | Portal público de consulta que permite validar la autenticidad de un certificado emitido mediante su código único, sin necesidad de iniciar sesión. | High |  |  |  |  |  |
| EP-05 | Reportes Administrativos | Generación de reportes de cierre de curso, reportes financieros y consulta del historial de cursos dictados por periodo, para uso de jefatura y administración. | Medium |  |  |  |  |  |
| EP-06 | Gestión de Usuarios y Autenticación | Registro de cuentas, inicio de sesión diferenciado por rol (administrador, instructor, estudiante), recuperación de contraseña y control de permisos. | High |  |  |  | Gestión y Publicación de Cursos | EP-01 + EP-02 |
|  |  |  |  |  |  |  | Preinscripción e Inscripción | EP-03 + EP-04 |
|  |  |  |  |  |  |  | Desarrollo y Evaluación del Curso | EP-05 + EP-06 |
|  |  |  |  |  |  |  | Certificación (emisión + verificación) | EP-07 + EP-08 |
|  |  |  |  |  |  |  | Reportes Administrativos | EP-09 |
|  |  |  |  |  |  |  | Usuarios y Accesos | EP-10 |

## Product Backlog

|  |  |  |  |  |  |  |  |  |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Product Backlog — Plataforma de Formación Continua con Certificación Digital Verificable |  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |  |
| Orden | ID HU | Épica | Título de la Historia de Usuario | Rol | Prioridad | Puntos de Historia | Estado |  |
| 1 | HU-01 | EP-01 - Gestión de Cursos | Crear curso | Administrador | Highest | 5 | Pendiente |  |
| 2 | HU-02 | EP-01 - Gestión de Cursos | Configurar grupos y cupos del curso | Administrador | Highest | 5 | Pendiente |  |
| 3 | HU-08 | EP-03 - Preinscripción de Estudiantes | Consultar catálogo público de cursos | Visitante / Estudiante | Highest | 3 | Pendiente | Fabio Salguero |
| 4 | HU-09 | EP-03 - Preinscripción de Estudiantes | Preinscribirse a un grupo de un curso | Estudiante / Visitante | Highest | 5 | Pendiente |  |
| 5 | HU-15 | EP-04 - Inscripción y Pagos | Confirmar inscripción tras verificación de pago | Administrador | Highest | 5 | Pendiente |  |
| 6 | HU-16 | EP-04 - Inscripción y Pagos | Aplicar precio diferenciado y descuento de beca | Sistema / Administrador | Highest | 3 | Pendiente |  |
| 7 | HU-21 | EP-05 - Desarrollo del Curso (Sesiones y Asistencia) | Registrar asistencia del estudiante | Instructor | Highest | 5 | Pendiente | Manuel |
| 8 | HU-25 | EP-06 - Evaluación y Calificación | Registrar notas de evaluación por estudiante | Instructor | Highest | 5 | Pendiente |  |
| 9 | HU-26 | EP-06 - Evaluación y Calificación | Calcular tipo de certificado según nota y asistencia | Sistema | Highest | 3 | Pendiente |  |
| 10 | HU-28 | EP-07 - Emisión y Gestión de Certificados | Generar certificado digital individual | Sistema / Administrador | Highest | 8 | Pendiente |  |
| 11 | HU-31 | EP-07 - Emisión y Gestión de Certificados | Descargar certificado digital | Estudiante | Highest | 3 | Pendiente |  |
| 12 | HU-33 | EP-08 - Verificación Pública de Certificados | Consultar validez de un certificado por código | Visitante / Público | Highest | 5 | Pendiente |  |
| 13 | HU-37 | EP-10 - Gestión de Usuarios y Autenticación | Iniciar sesión según rol | Administrador / Instructor / Estudiante | Highest | 5 | Pendiente | Jazmin Ruiz Massi |
| 14 | HU-06 | EP-02 - Publicación y Difusión de Cursos | Publicar curso para preinscripción | Administrador | High | 3 | Pendiente |  |
| 15 | HU-07 | EP-02 - Publicación y Difusión de Cursos | Cerrar preinscripción de un curso | Administrador | High | 3 | Pendiente |  |
| 16 | HU-10 | EP-03 - Preinscripción de Estudiantes | Visualizar cupos disponibles por grupo en tiempo real | Visitante / Estudiante | High | 2 | Pendiente |  |
| 17 | HU-11 | EP-03 - Preinscripción de Estudiantes | Evaluar cumplimiento del cupo mínimo por grupo | Administrador | High | 3 | Pendiente | Alex |
| 18 | HU-12 | EP-03 - Preinscripción de Estudiantes | Fusionar grupos que no alcanzan el cupo mínimo | Administrador | High | 5 | Pendiente |  |
| 19 | HU-17 | EP-04 - Inscripción y Pagos | Generar boleta/recibo individual de inscripción | Administrador | High | 3 | Pendiente |  |
| 20 | HU-19 | EP-04 - Inscripción y Pagos | Visualizar listado oficial de inscritos confirmados | Administrador / Instructor | High | 3 | Pendiente |  |
| 21 | HU-24 | EP-06 - Evaluación y Calificación | Configurar criterios de evaluación del curso | Instructor | High | 3 | Pendiente |  |
| 22 | HU-27 | EP-06 - Evaluación y Calificación | Visualizar y exportar planilla de notas del curso | Instructor / Administrador | High | 3 | Pendiente |  |
| 23 | HU-29 | EP-07 - Emisión y Gestión de Certificados | Gestionar flujo de firma de autoridades | Administrador | High | 5 | Pendiente |  |
| 24 | HU-03 | EP-01 - Gestión de Cursos | Editar información del curso | Administrador | Medium | 3 | Pendiente |  |
| 25 | HU-04 | EP-01 - Gestión de Cursos | Cancelar un curso | Administrador | Medium | 3 | Pendiente |  |
| 26 | HU-05 | EP-01 - Gestión de Cursos | Visualizar listado de cursos | Administrador | Medium | 2 | Pendiente |  |
| 27 | HU-13 | EP-03 - Preinscripción de Estudiantes | Registrar retiro de preinscripción con devolución de pago | Administrador | Medium | 3 | Pendiente |  |
| 28 | HU-20 | EP-05 - Desarrollo del Curso (Sesiones y Asistencia) | Programar sesiones/clases del curso | Administrador | Medium | 3 | Pendiente |  |
| 29 | HU-22 | EP-05 - Desarrollo del Curso (Sesiones y Asistencia) | Registrar asistencia del instructor | Administrador | Medium | 3 | Pendiente |  |
| 30 | HU-23 | EP-05 - Desarrollo del Curso (Sesiones y Asistencia) | Visualizar porcentaje de asistencia por estudiante | Instructor / Administrador | Medium | 2 | Pendiente |  |
| 31 | HU-30 | EP-07 - Emisión y Gestión de Certificados | Notificar a estudiantes la disponibilidad del certificado | Sistema | Medium | 3 | Pendiente |  |
| 32 | HU-32 | EP-07 - Emisión y Gestión de Certificados | Emitir certificado del instructor | Administrador | Medium | 3 | Pendiente |  |
| 33 | HU-34 | EP-09 - Reportes Administrativos | Generar reporte de cierre de curso para jefatura | Administrador | Medium | 3 | Pendiente |  |
| 34 | HU-35 | EP-09 - Reportes Administrativos | Generar reporte financiero del curso | Administrador | Medium | 3 | Pendiente |  |
| 35 | HU-38 | EP-10 - Gestión de Usuarios y Autenticación | Recuperar contraseña | Administrador / Instructor / Estudiante | Medium | 3 | Pendiente |  |
| 36 | HU-39 | EP-10 - Gestión de Usuarios y Autenticación | Gestionar cuentas y asignaciones de instructores | Administrador | Medium | 3 | Pendiente |  |
| 37 | HU-14 | EP-03 - Preinscripción de Estudiantes | Habilitar apertura de un grupo adicional por demanda | Administrador | Low | 3 | Pendiente |  |
| 38 | HU-18 | EP-04 - Inscripción y Pagos | Cambiar de curso o grupo a un estudiante ya inscrito | Administrador | Low | 3 | Pendiente |  |
| 39 | HU-36 | EP-09 - Reportes Administrativos | Consultar historial de cursos por periodo | Administrador | Low | 2 | Pendiente |  |
|  |  |  |  |  | TOTAL | 138 |  |  |

## Historias de Usuario

|  |  |  |  |  |  |  |  |
| --- | --- | --- | --- | --- | --- | --- | --- |
| HU-01 |  | EP-01 · Gestión de Cursos |  |  |  |  |  |
| Crear curso |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador del laboratorio, quiero registrar un nuevo curso con su información general, para publicarlo posteriormente y habilitar el proceso de preinscripción. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al hacer clic en "Nuevo curso" se despliega un formulario con los campos: nombre del curso, contenido/temario, carga horaria (20 o 30 horas académicas), periodo (primer o segundo semestre), nivel (básico, intermedio, avanzado) e instructor asignado. |  |  |  |  |  |  |  |
| 2. Los campos nombre del curso, carga horaria, periodo e instructor son obligatorios; el sistema no permite guardar si están vacíos. |  |  |  |  |  |  |  |
| 3. Al seleccionar la carga horaria (20 o 30 horas), el sistema calcula automáticamente el número de sesiones (10 clases) y la duración por sesión (1.5 horas para 20h, 2.5 horas para 30h). |  |  |  |  |  |  |  |
| 4. Al seleccionar la carga horaria, el sistema propone automáticamente el costo base para estudiante UMSS, estudiante externo y auxiliar practicante según la tabla vigente (20h: 80/100/40 Bs; 30h: 120/150/60 Bs), permitiendo que el administrador los edite. |  |  |  |  |  |  |  |
| 5. Al guardar el curso, el sistema lo registra con estado "En preparación" y no lo hace visible al público todavía. |  |  |  |  |  |  |  |
| 6. Al hacer clic en "Cancelar" el formulario se cierra sin guardar cambios. |  |  |  |  |  |  |  |
| 7. Si se intenta crear un curso con el mismo nombre y periodo ya existentes, se muestra el mensaje "Ya existe un curso con este nombre en este periodo". |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-02 |  | EP-01 · Gestión de Cursos |  |  |  |  |  |
| Configurar grupos y cupos del curso |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero definir uno o más grupos con su horario y cupo para cada curso, para organizar la oferta según la demanda de estudiantes. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al editar un curso se puede agregar uno o más grupos indicando: nombre/número de grupo, horario de inicio y fin, cupo mínimo (por defecto 15) y cupo máximo. |  |  |  |  |  |  |  |
| 2. El sistema no permite guardar un grupo si la hora de inicio es posterior o igual a la hora de fin. |  |  |  |  |  |  |  |
| 3. Al agregar un grupo, el sistema calcula automáticamente la hora de finalización de cada sesión a partir de la hora de inicio y la duración de sesión definida por la carga horaria del curso. |  |  |  |  |  |  |  |
| 4. El administrador puede eliminar un grupo mientras no tenga estudiantes preinscritos. |  |  |  |  |  |  |  |
| 5. El administrador puede modificar el cupo mínimo y máximo de un grupo antes de que inicie la preinscripción. |  |  |  |  |  |  |  |
| 6. Al guardar, el sistema muestra la lista de grupos configurados con su horario y cupo. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-03 |  | EP-01 · Gestión de Cursos |  |  |  |  |  |
| Editar información del curso |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero editar los datos de un curso ya registrado, para corregir información o actualizar detalles antes de su publicación. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al hacer clic en "Editar" sobre un curso en estado "En preparación" o "Publicado", se abre el formulario con los datos actuales precargados. |  |  |  |  |  |  |  |
| 2. Se pueden modificar todos los campos, excepto la carga horaria si el curso ya tiene estudiantes preinscritos. |  |  |  |  |  |  |  |
| 3. Al guardar los cambios, el sistema actualiza el registro y muestra el mensaje "Curso actualizado correctamente". |  |  |  |  |  |  |  |
| 4. Si el curso ya está publicado, cualquier cambio en horario o costo genera una notificación a los estudiantes ya preinscritos. |  |  |  |  |  |  |  |
| 5. Al hacer clic en "Cancelar" no se guardan los cambios realizados. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-04 |  | EP-01 · Gestión de Cursos |  |  |  |  |  |
| Cancelar un curso |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero cancelar un curso que no se llevará a cabo, para informar oportunamente a los estudiantes y liberar el listado de la oferta vigente. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al hacer clic en "Cancelar curso" el sistema solicita confirmación mostrando el mensaje "¿Confirmar cancelación del curso? Esta acción notificará a todos los preinscritos". |  |  |  |  |  |  |  |
| 2. Si el curso tiene estudiantes inscritos con pago confirmado, el sistema exige registrar el motivo de cancelación antes de continuar. |  |  |  |  |  |  |  |
| 3. Al confirmar, el curso cambia a estado "Cancelado" y deja de ser visible en el catálogo de preinscripción. |  |  |  |  |  |  |  |
| 4. Al cancelar el curso, el sistema genera automáticamente una lista de estudiantes a los que corresponde devolución de pago. |  |  |  |  |  |  |  |
| 5. Un curso finalizado (con certificados ya emitidos) no puede eliminarse ni cancelarse. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-05 |  | EP-01 · Gestión de Cursos |  |  |  |  |  |
| Visualizar listado de cursos |  |  |  |  |  |  |  |
| Puntos de Historia: 2 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero visualizar el listado de todos los cursos registrados con su estado, para dar seguimiento a la oferta académica vigente. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al ingresar a la sección "Cursos" se muestra una tabla con nombre, periodo, carga horaria, instructor, estado (en preparación, publicado, en curso, finalizado, cancelado) y número de inscritos. |  |  |  |  |  |  |  |
| 2. Se puede filtrar el listado por estado, periodo o instructor. |  |  |  |  |  |  |  |
| 3. Se puede buscar un curso por nombre. |  |  |  |  |  |  |  |
| 4. Al hacer clic sobre un curso se accede al detalle del mismo. |  |  |  |  |  |  |  |
| 5. Si no existen cursos registrados, se muestra el mensaje "Aún no se han registrado cursos". |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-06 |  | EP-02 · Publicación y Difusión de Cursos |  |  |  |  |  |
| Publicar curso para preinscripción |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero publicar un curso ya configurado, para que quede visible al público y se habilite el periodo de preinscripción. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Un curso solo puede publicarse si tiene al menos un grupo configurado con horario y cupo definidos. |  |  |  |  |  |  |  |
| 2. Al hacer clic en "Publicar" el sistema solicita confirmar la fecha de inicio y fin del periodo de preinscripción. |  |  |  |  |  |  |  |
| 3. Al confirmar, el curso cambia a estado "Publicado" y aparece en el catálogo público de cursos disponibles. |  |  |  |  |  |  |  |
| 4. Una vez publicado, el sistema genera automáticamente un resumen del curso (nombre, horario, costo, cupos) descargable, para compartir en redes sociales y WhatsApp. |  |  |  |  |  |  |  |
| 5. Si la fecha de preinscripción configurada ya pasó, el sistema no permite publicar el curso. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-07 |  | EP-02 · Publicación y Difusión de Cursos |  |  |  |  |  |
| Cerrar preinscripción de un curso |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero cerrar manual o automáticamente el periodo de preinscripción de un curso, para proceder con la validación de cupos mínimos por grupo. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El sistema cierra automáticamente la preinscripción de un curso al llegar la fecha límite configurada. |  |  |  |  |  |  |  |
| 2. El administrador puede cerrar la preinscripción de forma anticipada desde el detalle del curso. |  |  |  |  |  |  |  |
| 3. Al cerrarse la preinscripción, el sistema deja de aceptar nuevas preinscripciones para ese curso y muestra el estado "Preinscripción cerrada". |  |  |  |  |  |  |  |
| 4. Al cerrarse la preinscripción, el sistema genera automáticamente un resumen de cupos alcanzados por grupo. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-08 |  | EP-03 · Preinscripción de Estudiantes |  |  |  |  |  |
| Consultar catálogo público de cursos |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Visitante / Estudiante |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como estudiante o persona externa interesada, quiero visualizar el catálogo de cursos publicados, para elegir el curso y grupo que se ajusten a mi disponibilidad. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El catálogo es de acceso público, sin necesidad de iniciar sesión. |  |  |  |  |  |  |  |
| 2. Se muestra para cada curso: nombre, contenido, carga horaria, nivel, costo diferenciado (UMSS/externo/auxiliar) y grupos disponibles con horario y cupos restantes. |  |  |  |  |  |  |  |
| 3. Se puede filtrar el catálogo por nivel, carga horaria o periodo. |  |  |  |  |  |  |  |
| 4. Un curso con todos sus grupos llenos se muestra con la etiqueta "Cupos llenos" y no permite preinscripción a ese grupo. |  |  |  |  |  |  |  |
| 5. Un curso en estado "Preinscripción cerrada" o "Cancelado" no aparece en el catálogo. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-09 |  | EP-03 · Preinscripción de Estudiantes |  |  |  |  |  |
| Preinscribirse a un grupo de un curso |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Estudiante / Visitante |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como interesado en un curso, quiero preinscribirme indicando mis datos personales y el grupo de mi preferencia, para asegurar mi cupo antes de confirmar mi inscripción con el pago. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El formulario de preinscripción solicita: código CIS (si es estudiante UMSS), carnet de identidad, nombres, apellido paterno, apellido materno, celular, correo electrónico y tipo de participante (estudiante UMSS / externo / ex-auxiliar practicante). |  |  |  |  |  |  |  |
| 2. Los campos carnet, nombres, apellidos, celular y correo son obligatorios; el código CIS es obligatorio solo si el tipo de participante es "estudiante UMSS". |  |  |  |  |  |  |  |
| 3. El sistema valida el formato del correo electrónico y del número de celular antes de permitir enviar el formulario. |  |  |  |  |  |  |  |
| 4. El sistema no permite seleccionar un grupo cuyo cupo máximo ya fue alcanzado. |  |  |  |  |  |  |  |
| 5. Al enviar el formulario, el sistema registra la preinscripción con estado "Pendiente de pago" y descuenta un cupo disponible del grupo seleccionado. |  |  |  |  |  |  |  |
| 6. Al confirmar la preinscripción, se muestra un resumen del curso, grupo, horario, costo a pagar y las indicaciones para formalizar el pago en caja facultativa. |  |  |  |  |  |  |  |
| 7. Si el carnet ya se encuentra preinscrito en el mismo curso, el sistema muestra el mensaje "Ya cuentas con una preinscripción activa para este curso". |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-10 |  | EP-03 · Preinscripción de Estudiantes |  |  |  |  |  |
| Visualizar cupos disponibles por grupo en tiempo real |  |  |  |  |  |  |  |
| Puntos de Historia: 2 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Visitante / Estudiante |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como interesado en un curso, quiero ver la cantidad de cupos disponibles por grupo actualizada en tiempo real, para decidir a qué grupo preinscribirme. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Cada grupo muestra el número de cupos ocupados y el cupo máximo (ej. "12/20"). |  |  |  |  |  |  |  |
| 2. El contador se actualiza inmediatamente después de cada preinscripción o retiro confirmado. |  |  |  |  |  |  |  |
| 3. Un grupo con cupos ocupados iguales al cupo máximo se marca visualmente como "Lleno" y deshabilita el botón de preinscripción. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-11 |  | EP-03 · Preinscripción de Estudiantes |  |  |  |  |  |
| Evaluar cumplimiento del cupo mínimo por grupo |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero que el sistema me indique qué grupos alcanzaron el cupo mínimo al cierre de la preinscripción, para decidir si se habilitan, fusionan o cancelan. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al cerrarse la preinscripción, el sistema clasifica automáticamente cada grupo como "Habilitado" (cupo ≥ mínimo) o "No habilitado" (cupo < mínimo). |  |  |  |  |  |  |  |
| 2. El sistema muestra un resumen por curso con el detalle de cupos alcanzados por cada grupo. |  |  |  |  |  |  |  |
| 3. Si todos los grupos de un curso no alcanzan el cupo mínimo, el sistema sugiere la cancelación del curso. |  |  |  |  |  |  |  |
| 4. El administrador puede consultar el listado de preinscritos de los grupos no habilitados para gestionar su reubicación. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-12 |  | EP-03 · Preinscripción de Estudiantes |  |  |  |  |  |
| Fusionar grupos que no alcanzan el cupo mínimo |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero fusionar los preinscritos de un grupo que no alcanzó el cupo mínimo con otro grupo del mismo curso, para completar el cupo y evitar cancelar el curso. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El administrador selecciona un grupo "No habilitado" y un grupo destino con cupo disponible para fusionar. |  |  |  |  |  |  |  |
| 2. Antes de fusionar, el sistema requiere el registro de la decisión de cada estudiante afectado (aceptar cambio de grupo o solicitar devolución). |  |  |  |  |  |  |  |
| 3. Al fusionar, los estudiantes que aceptaron el cambio se reasignan al grupo destino, respetando el cupo máximo de este. |  |  |  |  |  |  |  |
| 4. El grupo de origen cambia a estado "Cerrado" una vez fusionado. |  |  |  |  |  |  |  |
| 5. El sistema notifica al estudiante el nuevo horario asignado tras la fusión. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-13 |  | EP-03 · Preinscripción de Estudiantes |  |  |  |  |  |
| Registrar retiro de preinscripción con devolución de pago |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero registrar el retiro de un estudiante preinscrito que no acepta el cambio de grupo o desiste del curso, para gestionar la devolución de su pago cuando corresponda. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al seleccionar una preinscripción y hacer clic en "Retirar", el sistema solicita el motivo del retiro. |  |  |  |  |  |  |  |
| 2. Si el estudiante ya había realizado el pago, el sistema marca la devolución como "Pendiente" y registra el monto a devolver. |  |  |  |  |  |  |  |
| 3. Al confirmar el retiro, la preinscripción cambia a estado "Retirado" y el cupo del grupo se libera automáticamente. |  |  |  |  |  |  |  |
| 4. El administrador puede marcar la devolución como "Realizada" una vez efectuado el reembolso. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-14 |  | EP-03 · Preinscripción de Estudiantes |  |  |  |  |  |
| Habilitar apertura de un grupo adicional por demanda |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Low |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero habilitar un nuevo grupo de un curso ya publicado cuando exista demanda suficiente, para atender a más estudiantes interesados. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El sistema permite registrar una lista de espera para estudiantes interesados cuando todos los grupos de un curso están llenos. |  |  |  |  |  |  |  |
| 2. Cuando la lista de espera alcanza el cupo mínimo (15), el sistema sugiere al administrador habilitar un nuevo grupo. |  |  |  |  |  |  |  |
| 3. Al crear el nuevo grupo, el sistema permite convertir automáticamente a los interesados de la lista de espera en preinscritos del nuevo grupo. |  |  |  |  |  |  |  |
| 4. El nuevo grupo queda visible en el catálogo con su propio horario y cupo. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-15 |  | EP-04 · Inscripción y Pagos |  |  |  |  |  |
| Confirmar inscripción tras verificación de pago |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero confirmar la inscripción de un estudiante preinscrito una vez verificado su pago en caja facultativa, para incluirlo en la lista oficial del curso. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al buscar una preinscripción con estado "Pendiente de pago", el administrador puede registrar el pago indicando método (efectivo o QR) y el número de comprobante. |  |  |  |  |  |  |  |
| 2. Al confirmar el pago, la preinscripción cambia a estado "Inscrito" y el estudiante pasa a la lista oficial del curso. |  |  |  |  |  |  |  |
| 3. El sistema calcula automáticamente el monto a cobrar según el tipo de participante (UMSS, externo, auxiliar practicante) y la carga horaria del curso. |  |  |  |  |  |  |  |
| 4. Si el estudiante indica ser ex-auxiliar practicante, el sistema exige registrar el respaldo del certificado de auxiliar antes de aplicar el 50% de descuento. |  |  |  |  |  |  |  |
| 5. El sistema registra si el estudiante presentó la fotocopia de carnet; en caso contrario marca la observación "Debe presentar el primer día de clases". |  |  |  |  |  |  |  |
| 6. Un estudiante no puede quedar como "Inscrito" si no se registra el pago o la exoneración correspondiente. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-16 |  | EP-04 · Inscripción y Pagos |  |  |  |  |  |
| Aplicar precio diferenciado y descuento de beca |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Sistema / Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero que el sistema calcule automáticamente el costo de inscripción según el tipo de participante y la carga horaria, para asegurar que se cobre el monto correcto en cada caso. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Para cursos de 20 horas académicas, el sistema calcula 80 Bs (estudiante UMSS), 100 Bs (externo) y 40 Bs (ex-auxiliar practicante). |  |  |  |  |  |  |  |
| 2. Para cursos de 30 horas académicas, el sistema calcula 120 Bs (estudiante UMSS), 150 Bs (externo) y 60 Bs (ex-auxiliar practicante). |  |  |  |  |  |  |  |
| 3. El descuento de ex-auxiliar practicante (50%) se aplica sobre el costo de estudiante UMSS y es válido para uno o más cursos en los que se inscriba durante el periodo vigente. |  |  |  |  |  |  |  |
| 4. Si el estudiante no cuenta con el certificado de auxiliar practicante vigente, el sistema no permite aplicar el descuento. |  |  |  |  |  |  |  |
| 5. El monto calculado se muestra al administrador antes de confirmar el registro del pago y se refleja en el comprobante generado. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-17 |  | EP-04 · Inscripción y Pagos |  |  |  |  |  |
| Generar boleta/recibo individual de inscripción |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero generar un comprobante de pago individual por cada estudiante inscrito, para entregarle su respaldo del pago realizado. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al confirmar la inscripción de un estudiante, el sistema genera automáticamente un comprobante con: nombre del estudiante, curso, grupo, monto pagado, método de pago y fecha. |  |  |  |  |  |  |  |
| 2. El comprobante puede visualizarse, imprimirse o descargarse en PDF desde el detalle de la inscripción. |  |  |  |  |  |  |  |
| 3. Cada comprobante cuenta con un número correlativo único. |  |  |  |  |  |  |  |
| 4. El sistema mantiene un historial de comprobantes emitidos por curso, consultable por el administrador. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-18 |  | EP-04 · Inscripción y Pagos |  |  |  |  |  |
| Cambiar de curso o grupo a un estudiante ya inscrito |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Low |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero cambiar a un estudiante inscrito a otro grupo o curso, para atender solicitudes de reubicación antes del inicio de clases. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El cambio de grupo solo está disponible antes de la fecha de inicio del curso. |  |  |  |  |  |  |  |
| 2. Al cambiar de grupo, el sistema valida que el grupo destino tenga cupo disponible. |  |  |  |  |  |  |  |
| 3. Si el estudiante cambia a un curso con un costo diferente, el sistema recalcula el saldo a favor o a cobrar. |  |  |  |  |  |  |  |
| 4. El sistema registra en el historial del estudiante el cambio realizado, con fecha y motivo. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-19 |  | EP-04 · Inscripción y Pagos |  |  |  |  |  |
| Visualizar listado oficial de inscritos confirmados |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Administrador / Instructor |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador o instructor, quiero visualizar el listado oficial de estudiantes con inscripción confirmada de un curso, para dar inicio al desarrollo de las clases. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El listado oficial solo incluye estudiantes con estado "Inscrito" (pago verificado). |  |  |  |  |  |  |  |
| 2. El listado muestra nombre completo, carnet, celular, correo, grupo y tipo de participante. |  |  |  |  |  |  |  |
| 3. El listado puede exportarse a Excel o PDF. |  |  |  |  |  |  |  |
| 4. El administrador puede compartir el acceso al listado con el instructor asignado al curso. |  |  |  |  |  |  |  |
| 5. El instructor solo puede visualizar el listado de los cursos/grupos que tiene asignados. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-20 |  | EP-05 · Desarrollo del Curso (Sesiones y Asistencia) |  |  |  |  |  |
| Programar sesiones/clases del curso |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero que el sistema genere automáticamente el calendario de las 10 sesiones del curso a partir de la fecha de inicio, para llevar el control del desarrollo de las clases. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al confirmar el inicio del curso, el sistema genera 10 sesiones de lunes a viernes durante dos semanas, a partir de la fecha de inicio. |  |  |  |  |  |  |  |
| 2. Si una sesión cae en día feriado, el sistema la reprograma automáticamente para el siguiente día hábil disponible sin clase asignada. |  |  |  |  |  |  |  |
| 3. El administrador puede reprogramar manualmente una sesión indicando el motivo. |  |  |  |  |  |  |  |
| 4. Cada sesión muestra fecha, hora de inicio y hora de fin según el horario del grupo. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-21 |  | EP-05 · Desarrollo del Curso (Sesiones y Asistencia) |  |  |  |  |  |
| Registrar asistencia del estudiante |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Instructor |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como instructor, quiero registrar la asistencia de cada estudiante en cada sesión del curso, para llevar el control necesario para la emisión del certificado correspondiente. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al ingresar a una sesión, el instructor visualiza la lista de estudiantes inscritos en su grupo con un control para marcar "Presente", "Ausente" o "Justificado". |  |  |  |  |  |  |  |
| 2. El sistema no permite registrar asistencia de sesiones futuras a la fecha actual. |  |  |  |  |  |  |  |
| 3. Una vez registrada la asistencia de una sesión, el instructor puede editarla el mismo día o el día siguiente; después de ese plazo requiere autorización del administrador. |  |  |  |  |  |  |  |
| 4. El sistema calcula automáticamente el porcentaje de asistencia acumulado de cada estudiante tras cada sesión registrada. |  |  |  |  |  |  |  |
| 5. El sistema resalta a los estudiantes con 3 faltas continuas para que el instructor pueda hacer el seguimiento correspondiente. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-22 |  | EP-05 · Desarrollo del Curso (Sesiones y Asistencia) |  |  |  |  |  |
| Registrar asistencia del instructor |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero registrar la asistencia del instructor a cada sesión dictada, para verificar el cumplimiento de la carga horaria del curso. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El administrador puede marcar cada sesión como "Dictada" o "No dictada" por el instructor asignado. |  |  |  |  |  |  |  |
| 2. Si una sesión se marca como "No dictada", el sistema solicita registrar el motivo y sugiere su reprogramación. |  |  |  |  |  |  |  |
| 3. El sistema muestra el porcentaje de sesiones efectivamente dictadas por el instructor sobre el total programado. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-23 |  | EP-05 · Desarrollo del Curso (Sesiones y Asistencia) |  |  |  |  |  |
| Visualizar porcentaje de asistencia por estudiante |  |  |  |  |  |  |  |
| Puntos de Historia: 2 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Instructor / Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como instructor o administrador, quiero visualizar el porcentaje de asistencia acumulado de cada estudiante durante el curso, para considerarlo en la evaluación final. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Se muestra por estudiante el número de sesiones asistidas sobre el total de sesiones dictadas. |  |  |  |  |  |  |  |
| 2. El listado se puede ordenar de mayor a menor porcentaje de asistencia. |  |  |  |  |  |  |  |
| 3. Se resalta visualmente a los estudiantes con asistencia por debajo del mínimo configurado por el instructor. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-24 |  | EP-06 · Evaluación y Calificación |  |  |  |  |  |
| Configurar criterios de evaluación del curso |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Instructor |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como instructor, quiero definir los criterios y ponderaciones de evaluación de mi curso (asistencia, examen, prácticas, proyecto), para calcular la nota final según mi propio criterio pedagógico. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El instructor puede agregar uno o más criterios de evaluación indicando nombre y ponderación (%). |  |  |  |  |  |  |  |
| 2. El sistema valida que la suma de las ponderaciones de todos los criterios sea igual a 100%. |  |  |  |  |  |  |  |
| 3. Los criterios configurados quedan disponibles para el registro de notas de cada estudiante. |  |  |  |  |  |  |  |
| 4. El instructor puede modificar los criterios únicamente antes de registrar la primera nota. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-25 |  | EP-06 · Evaluación y Calificación |  |  |  |  |  |
| Registrar notas de evaluación por estudiante |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Instructor |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como instructor, quiero registrar la nota obtenida por cada estudiante en cada criterio de evaluación configurado, para calcular su nota final del curso. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El instructor visualiza una planilla con todos los estudiantes inscritos y una columna por cada criterio de evaluación configurado. |  |  |  |  |  |  |  |
| 2. El sistema solo permite ingresar valores numéricos entre 0 y 100 en cada criterio. |  |  |  |  |  |  |  |
| 3. Al guardar, el sistema recalcula automáticamente la nota final ponderada del estudiante. |  |  |  |  |  |  |  |
| 4. El instructor puede editar las notas mientras el curso no haya sido cerrado/finalizado. |  |  |  |  |  |  |  |
| 5. El sistema guarda un historial de cambios en las notas con fecha y usuario que realizó la modificación. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-26 |  | EP-06 · Evaluación y Calificación |  |  |  |  |  |
| Calcular tipo de certificado según nota y asistencia |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Sistema |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como sistema, quiero determinar automáticamente si un estudiante corresponde a certificado de aprobación o de asistencia, para aplicar el criterio institucional definido por jefatura. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Un estudiante con nota final mayor o igual a 70 obtiene "Certificado de aprobación". |  |  |  |  |  |  |  |
| 2. Un estudiante con nota final menor a 70 obtiene "Certificado de asistencia". |  |  |  |  |  |  |  |
| 3. El cálculo se actualiza automáticamente si la nota final de un estudiante es modificada antes del cierre del curso. |  |  |  |  |  |  |  |
| 4. El resultado (tipo de certificado) se muestra junto a la nota final en la planilla del instructor. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-27 |  | EP-06 · Evaluación y Calificación |  |  |  |  |  |
| Visualizar y exportar planilla de notas del curso |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Instructor / Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como instructor o administrador, quiero visualizar y exportar la planilla de notas de un curso, para reportarla a jefatura al finalizar el curso. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. La planilla muestra: nombre del estudiante, carnet, porcentaje de asistencia, nota por criterio, nota final y tipo de certificado correspondiente. |  |  |  |  |  |  |  |
| 2. La planilla puede exportarse en formato Excel o PDF. |  |  |  |  |  |  |  |
| 3. Solo se puede exportar la planilla si todos los estudiantes tienen sus notas completas. |  |  |  |  |  |  |  |
| 4. El administrador puede cerrar el curso desde esta vista una vez validada la planilla, bloqueando ediciones posteriores. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-28 |  | EP-07 · Emisión y Gestión de Certificados |  |  |  |  |  |
| Generar certificado digital individual |  |  |  |  |  |  |  |
| Puntos de Historia: 8 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Sistema / Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero que el sistema genere el certificado digital de cada estudiante al cerrar el curso, para su verificación y descarga posterior. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al cerrar el curso, el sistema genera un certificado por cada estudiante con: nombre completo, curso, tipo de certificado (aprobación/asistencia), carga horaria, fechas del curso y código único de verificación. |  |  |  |  |  |  |  |
| 2. El código único se genera de forma alfanumérica y no se repite entre certificados. |  |  |  |  |  |  |  |
| 3. El certificado se genera en formato PDF descargable. |  |  |  |  |  |  |  |
| 4. El certificado incluye un enlace o código QR que redirige al portal público de verificación. |  |  |  |  |  |  |  |
| 5. Mientras el certificado no cuente con las firmas de las autoridades, se muestra con estado "Pendiente de firma". |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-29 |  | EP-07 · Emisión y Gestión de Certificados |  |  |  |  |  |
| Gestionar flujo de firma de autoridades |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: High |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero registrar el avance de la firma de los certificados por parte del jefe de departamento, director académico y decano, para dar seguimiento al proceso administrativo de emisión. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El sistema muestra el estado de firma de cada certificado (Pendiente, Firmado por jefe de departamento, Firmado por director académico, Firmado por decano, Certificado listo). |  |  |  |  |  |  |  |
| 2. El administrador puede actualizar manualmente el estado conforme se van recabando las firmas. |  |  |  |  |  |  |  |
| 3. Al llegar al estado "Certificado listo", el sistema habilita la descarga del certificado firmado en digital y lo marca como disponible para el estudiante. |  |  |  |  |  |  |  |
| 4. El sistema calcula y muestra el tiempo transcurrido desde el cierre del curso hasta la emisión final, para dar seguimiento a la demora administrativa. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-30 |  | EP-07 · Emisión y Gestión de Certificados |  |  |  |  |  |
| Notificar a estudiantes la disponibilidad del certificado |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Sistema |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como estudiante, quiero recibir una notificación cuando mi certificado esté disponible, para saber cuándo puedo descargarlo o recogerlo físicamente. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al cambiar el estado del certificado a "Certificado listo", el sistema envía una notificación al correo electrónico registrado del estudiante. |  |  |  |  |  |  |  |
| 2. La notificación incluye el enlace de descarga del certificado digital y las indicaciones para el retiro del certificado físico. |  |  |  |  |  |  |  |
| 3. El administrador puede reenviar manualmente la notificación en caso de que el estudiante no la haya recibido. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-31 |  | EP-07 · Emisión y Gestión de Certificados |  |  |  |  |  |
| Descargar certificado digital |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Estudiante |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como estudiante, quiero descargar mi certificado digital en formato PDF, para compartirlo en mis redes profesionales o postulaciones laborales. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El estudiante accede a la sección "Mis certificados" iniciando sesión en la plataforma o mediante el enlace enviado por correo. |  |  |  |  |  |  |  |
| 2. Solo se muestran los certificados con estado "Certificado listo". |  |  |  |  |  |  |  |
| 3. Al hacer clic en "Descargar" se genera el archivo PDF del certificado. |  |  |  |  |  |  |  |
| 4. El estudiante puede copiar el enlace público de verificación de su certificado para compartirlo en LinkedIn u otros sitios. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-32 |  | EP-07 · Emisión y Gestión de Certificados |  |  |  |  |  |
| Emitir certificado del instructor |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero generar el certificado de participación del instructor por cada curso dictado, para reconocer su labor docente. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El certificado del instructor incluye: nombre del instructor, curso, grupo(s) dictados y carga horaria total. |  |  |  |  |  |  |  |
| 2. El certificado sigue el mismo flujo de firmas (jefe de departamento, director académico, decano) que el certificado de estudiante. |  |  |  |  |  |  |  |
| 3. Si el instructor dictó más de un grupo del mismo curso, el sistema permite indicarlo en un solo certificado o generar uno por grupo, según la configuración del administrador. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-33 |  | EP-08 · Verificación Pública de Certificados |  |  |  |  |  |
| Consultar validez de un certificado por código |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Visitante / Público |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como reclutador o tercero interesado, quiero verificar la validez de un certificado ingresando su código único, para confirmar la autenticidad de la certificación presentada por un postulante. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. La consulta es de acceso público, sin necesidad de iniciar sesión. |  |  |  |  |  |  |  |
| 2. Al ingresar un código válido, el sistema muestra: nombre del participante, curso, duración, tipo de certificado, fecha de emisión y el estado "Certificado válido". |  |  |  |  |  |  |  |
| 3. Al ingresar un código inexistente o inválido, el sistema muestra el mensaje "Certificado no encontrado. Verifique el código ingresado". |  |  |  |  |  |  |  |
| 4. El sistema no expone datos personales sensibles del participante (solo nombre y datos del curso). |  |  |  |  |  |  |  |
| 5. Se puede acceder directamente a la validación mediante una URL única por certificado (ej. certificado.cs.umss.edu.bo/CODIGO). |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-34 |  | EP-09 · Reportes Administrativos |  |  |  |  |  |
| Generar reporte de cierre de curso para jefatura |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero generar el reporte de cierre de un curso con las notas y certificados correspondientes, para entregarlo a jefatura dentro de los plazos establecidos. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El reporte incluye: listado de inscritos, nota final, tipo de certificado y estado de asistencia de cada estudiante. |  |  |  |  |  |  |  |
| 2. El sistema permite generar el reporte solo una vez que el curso fue cerrado (planilla de notas completa). |  |  |  |  |  |  |  |
| 3. El reporte puede exportarse en formato PDF y Excel. |  |  |  |  |  |  |  |
| 4. El sistema registra la fecha de generación del reporte, para verificar el cumplimiento del plazo (dos días hábiles tras finalizar el curso). |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-35 |  | EP-09 · Reportes Administrativos |  |  |  |  |  |
| Generar reporte financiero del curso |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero generar el reporte financiero de un curso con el monto total recaudado, para su rendición a la caja facultativa. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El reporte muestra el monto pagado por cada estudiante inscrito, el método de pago y el monto total recaudado del curso. |  |  |  |  |  |  |  |
| 2. El reporte diferencia los montos por tipo de participante (UMSS, externo, auxiliar practicante). |  |  |  |  |  |  |  |
| 3. El reporte puede exportarse a Excel para su presentación a la caja facultativa. |  |  |  |  |  |  |  |
| 4. El sistema advierte si existen pagos pendientes de verificación al momento de generar el reporte. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-36 |  | EP-09 · Reportes Administrativos |  |  |  |  |  |
| Consultar historial de cursos por periodo |  |  |  |  |  |  |  |
| Puntos de Historia: 2 |  | Prioridad: Low |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero consultar el historial de cursos dictados en periodos anteriores, para analizar la oferta académica y apoyar la planificación de futuros cursos. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Se puede filtrar el historial por periodo (semestre/gestión), nivel o instructor. |  |  |  |  |  |  |  |
| 2. El historial muestra el número de inscritos, ingresos generados y porcentaje de aprobación de cada curso. |  |  |  |  |  |  |  |
| 3. Se puede exportar el historial consolidado a Excel. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-37 |  | EP-10 · Gestión de Usuarios y Autenticación |  |  |  |  |  |
| Iniciar sesión según rol |  |  |  |  |  |  |  |
| Puntos de Historia: 5 |  | Prioridad: Highest |  | Estado: Pendiente |  | Rol: Administrador / Instructor / Estudiante |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como usuario del sistema, quiero iniciar sesión con mi correo y contraseña, para acceder a las funcionalidades correspondientes a mi rol. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El formulario de inicio de sesión solicita correo electrónico y contraseña. |  |  |  |  |  |  |  |
| 2. Si las credenciales son correctas, el sistema redirige al usuario al panel correspondiente a su rol (Administrador, Instructor o Estudiante). |  |  |  |  |  |  |  |
| 3. Si las credenciales son incorrectas, se muestra el mensaje "Correo o contraseña incorrectos". |  |  |  |  |  |  |  |
| 4. Tras 5 intentos fallidos consecutivos, el sistema bloquea temporalmente el acceso a la cuenta por 15 minutos. |  |  |  |  |  |  |  |
| 5. Cada rol accede únicamente a los módulos y datos que le corresponden (por ejemplo, un instructor no puede editar el catálogo de cursos ni ver reportes financieros). |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-38 |  | EP-10 · Gestión de Usuarios y Autenticación |  |  |  |  |  |
| Recuperar contraseña |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador / Instructor / Estudiante |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como usuario del sistema, quiero recuperar el acceso a mi cuenta mediante mi correo electrónico, para poder ingresar nuevamente si olvidé mi contraseña. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. Al hacer clic en "¿Olvidaste tu contraseña?" el sistema solicita el correo electrónico registrado. |  |  |  |  |  |  |  |
| 2. Si el correo está registrado, se envía un enlace de recuperación válido por 30 minutos. |  |  |  |  |  |  |  |
| 3. Si el correo no está registrado, el sistema no envía ningún correo y no revela si el correo existe o no en la base de datos. |  |  |  |  |  |  |  |
| 4. Al acceder al enlace de recuperación, el usuario puede definir una nueva contraseña que cumpla los requisitos mínimos de seguridad. |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| HU-39 |  | EP-10 · Gestión de Usuarios y Autenticación |  |  |  |  |  |
| Gestionar cuentas y asignaciones de instructores |  |  |  |  |  |  |  |
| Puntos de Historia: 3 |  | Prioridad: Medium |  | Estado: Pendiente |  | Rol: Administrador |  |
|  |  |  |  |  |  |  |  |
| Mockup: (pegar aquí la captura / diseño de referencia) |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
|  |  |  |  |  |  |  |  |
| Como administrador, quiero registrar y gestionar las cuentas de los instructores del laboratorio, para asignarles los cursos y grupos que dictarán. |  |  |  |  |  |  |  |
| ---- |  |  |  |  |  |  |  |
| Criterios de aceptación: |  |  |  |  |  |  |  |
| 1. El administrador puede crear una cuenta de instructor indicando nombre, correo y curso(s)/grupo(s) asignados. |  |  |  |  |  |  |  |
| 2. Un instructor solo visualiza y gestiona la asistencia, notas y planillas de los cursos/grupos que tiene asignados. |  |  |  |  |  |  |  |
| 3. El administrador puede desactivar la cuenta de un instructor que ya no colabora con el laboratorio. |  |  |  |  |  |  |  |
| 4. Al desactivar una cuenta de instructor, el histórico de cursos que dictó se mantiene visible en los reportes. |  |  |  |  |  |  |  |
