# Reglas de Negocio — Plataforma de Formación Continua con Certificación Digital Verificable

## 1. Precios de Cursos

| Carga Horaria | UMSS | Externo | Auxiliar |
|---------------|------|---------|----------|
| 20 horas      | Bs. 80 | Bs. 100 | Bs. 40  |
| 30 horas      | Bs. 120 | Bs. 150 | Bs. 60  |

- **Descuento auxiliar**: 50% sobre el precio UMSS.
- **Fuente de verdad**: las columnas `precio_umss`, `precio_externo` y `precio_auxiliar` del curso (editables por el administrador). Todo cálculo, cobro y exhibición debe leer esas columnas — nunca recalcular.
- **Precalculado por defecto**: `App\Support\BusinessRules::calculatePrice()` solo precarga los valores al crear un curso o al cambiar la carga horaria. No es la fuente de lectura para cobro ni exhibición.

## 2. Grupo

- **Cupo mínimo**: 15 participantes por defecto.
- **Cupo máximo**: Definido por el administrador.
- **Estado del grupo**: `habilitado` | `deshabilitado` | `completo` | `en_curso` | `finalizado`
- Un curso puede tener múltiples grupos.

## 3. Sesiones

- **Duración de sesión**: Definida por horario (`hora_inicio` → `hora_fin`).
- **Total de sesiones**: Calculado según carga horaria del curso.
- **Reprogramación**: Campo `motivo_reprogramacion` disponible.
- **Estado de sesión**: `dictada` (booleano).
- **Código QR**: Generado automáticamente para cada sesión, usado para registro de asistencia.

## 4. Asistencia

- **Radio máximo permitido**: 100 metros (fijo).
- **Cálculo de distancia**: Fórmula de Haversine (`App\Support\BusinessRules::haversineDistance()`).
- **Estados de asistencia**:
  - `presente`: Distancia ≤ 100m
  - `ausente`: No registrado
  - `justificado`: Justificación manual
  - `para_revision`: Distancia > 100m (enviado para revisión manual)
- **Implementación**: `App\Services\AttendanceService`

## 5. Evaluación

- **Criterios de evaluación**: Cada curso tiene criterios definidos por el instructor.
- **Ponderación total**: Debe ser exactamente 100% por curso.
- **Rango de notas**: 0 a 100.
- **Nota mínima de aprobación**: 70 puntos.

## 6. Certificados

- **Tipos de certificado**:
  - **Aprobación**: Nota promedio ≥ 70
  - **Asistencia**: Nota promedio < 70
  - **Instructor**: Certificado emitido al docente del curso
- **Código único**: Generado automáticamente para cada certificado.
- **Emisión directa**: Al generarse el certificado tras la finalización del curso y registro de notas, el documento queda emitido y listo para descarga inmediata.
- **Verificación**: Disponible públicamente vía código QR impreso en el documento o mediante búsqueda por código único.

## 7. Preinscripción

- **Campos requeridos**: CI, nombres, apellido paterno, email, tipo participante.
- **CI única por grupo**: No se permite doble inscripción al mismo grupo.
- **Estado de preinscripción**:
  1. `pendiente`: Registrada, esperando pago
  2. `confirmada`: Pago verificado y confirmado
  3. `cancelada`: Cancelada por el participante o administrador

## 8. Pagos

- **Métodos de pago**: Efectivo, Transferencia, QR.
- **Verificación**: Manual por el administrador.
- **Campos de verificación**: Número de comprobante, verificado por, fecha de verificación.
- **Flujo de verificación**:
  1. Participante realiza pago
  2. Administrador revisa comprobante
  3. Verifica y confirma el pago
  4. Estado de preinscripción cambia a `confirmada`

## 9. Participantes

- **Tipos de participante**:
  - `umss`: Precio especial UMSS
  - `externo`: Precio estándar
  - `auxiliar`: Precio con descuento (50%)

## 10. Estados del Curso

| Estado | Descripción |
|--------|-------------|
| `en_preparacion` | Curso en diseño, aún no publicado |
| `publicado` | Disponible para preinscripción |
| `preinscripcion_cerrada` | Cerrado para nuevas inscripciones |
| `en_curso` | Curso en ejecución |
| `finalizado` | Curso completado |
| `cancelado` | Curso cancelado con registro explícito de motivo administrativo/técnico |

## 11. Emisión y Verificación de Certificados

- **Flujo**: Finalización del curso y notas → Emisión directa del certificado → Descarga inmediata → Verificación pública.
- Los certificados emitidos incluyen código QR y código único verificable en línea.
- La verificación se realiza consultando el código único en la base de datos pública.

## 12. Acceso a Paneles

| Panel | URL | Rol requerido |
|-------|-----|---------------|
| Admin | `/admin` | `admin` |
| Instructor | `/instructor` | `instructor` |
| Estudiante | `/estudiante` | `student` |
| Público | `/` | Sin autenticación |

## 13. Registro de Asistencia

- **Mecanismo**: Escaneo de código QR provisto en la sesión.
- **Validación**: Asociación estricta de la sesión al grupo e inscripción confirmada del estudiante.
- **Cálculo de distancia**: Utilidad matemática `App\Support\BusinessRules::haversineDistance()` para telemetría opcional.

## 14. Reportes y Conciliación Académica y de Caja

- **Acta de Cierre para Jefatura (HU-34)**: Generación en formato Excel (`CourseClosingExport`) con resumen estadístico de participantes, aprobados, reprobados, porcentaje de aprobación y detalle individual de calificaciones finales y certificados.
- **Reporte Financiero y Conciliación de Caja (HU-35)**: Generación en formato Excel (`FinancialReportExport`) con discriminación de ingresos por método de pago (efectivo, QR, transferencia), devoluciones/reembolsos y total neto recaudado por curso y consolidado.
- **Catálogo de Cursos Filtrable (HU-36)**: Exportación completa en Excel (`CoursesGeneralExport`) para auditoría y seguimiento académico.
