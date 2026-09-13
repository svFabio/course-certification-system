# Reglas de Negocio — Plataforma de Formación Continua con Certificación Digital Verificable

## 1. Precios de Cursos

| Carga Horaria | UMSS | Externo | Auxiliar |
|---------------|------|---------|----------|
| 20 horas      | Bs. 80 | Bs. 100 | Bs. 40  |
| 30 horas      | Bs. 120 | Bs. 150 | Bs. 60  |

- **Descuento auxiliar**: 50% sobre el precio UMSS.
- **Implementación**: `App\Support\BusinessRules::calculatePrice()`

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
- **Código único**: Generado automáticamente para cada certificado.
- **Flujo de firma**:
  1. `pendiente`: Certificado generado, esperando firma
  2. `firmado`: Certificado firmado digitalmente
  3. `rechazado`: Firma rechazada
- **Verificación**: Disponible vía código QR o código único.

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
| `cancelado` | Curso cancelado |

## 11. Firmado Digital de Certificados

- **Flujo**: Generación → Revisión → Firma → Verificación
- Los certificados firmados incluyen código QR verificable en línea.
- La verificación se realiza consultando el código único en la base de datos.

## 12. Acceso a Paneles

| Panel | URL | Rol requerido |
|-------|-----|---------------|
| Admin | `/admin` | `admin` |
| Instructor | `/instructor` | `instructor` |
| Público | `/` | Sin autenticación |

## 13. Geolocalización

- **Precisión requerida**: `enableHighAccuracy: true`
- **Timeout**: 10 segundos
- **Fórmula**: Haversine para calcular distancia entre两点
- **Implementación**: `App\Support\BusinessRules::haversineDistance()`
