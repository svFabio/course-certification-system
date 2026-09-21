# AGENTS.md — Formación Continua UMSS

Este documento define las reglas de arquitectura, estándares de código, invariantes de dominio y convenciones que todos los agentes de IA y desarrolladores deben respetar al contribuir en este repositorio.

---

## 1. Visión General y Stack Tecnológico

Sistema de gestión académica para cursos de Formación Continua y certificación digital verificable de la Universidad Mayor de San Simón (UMSS).

- **Backend:** PHP 8.2+ | Laravel 11
- **Admin & Instructor Panels:** Filament v3
- **Frontend Reactivo Público:** Livewire v3 | Blade | Tailwind CSS
- **Base de Datos:** PostgreSQL (producción/desarrollo) | SQLite (testing)
- **Almacenamiento en la Nube:** Cloudinary (vía Flysystem / Laravel Cloudinary para imágenes y PDFs)
- **Testing:** Pest PHP / PHPUnit
- **Code Style:** Laravel Pint

---

## 2. Invariantes del Dominio y Reglas de Negocio

Todas las reglas de cálculo y validación de negocio residen en `App\Support\BusinessRules` y los servicios en `App\Services\`.

1. **Precios de Cursos (`BusinessRules::calculatePrice`):**
   - Basados estrictamente en `carga_horaria` (20 o 30 horas) y `tipo_participante` (`umss`, `externo`, `auxiliar`).
   - Ningún componente o controlador debe hardcodear montos o fórmulas de precios.
2. **Capacidad de Grupos (`cupo_minimo`, `cupo_maximo`):**
   - El cupo mínimo para habilitar un grupo es `BusinessRules::MIN_GROUP_CAPACITY` (15 participantes).
   - Capacidad disponible considera preinscripciones en `pendiente_pago` e `inscrito`.
3. **Máquina de Estados de Pagos y Preinscripciones:**
   - Preinscripción: `pendiente_pago` ➔ `inscrito` (pago verificado) | `rechazado` (pago rechazado) | `retirado`.
   - Pago: `pendiente` ➔ `verificado` | `rechazado`.
   - Al verificar un pago: la preinscripción pasa a `inscrito` y se notifica al estudiante.
   - Al rechazar un pago: se registra el `motivo_rechazo` y la preinscripción pasa a `rechazado` liberando el cupo del grupo.

---

## 3. Estándares de Arquitectura y Buenas Prácticas

### Backend & Laravel
- **Strict Types:** Todo archivo PHP nuevo debe incluir `declare(strict_types=1);`.
- **Enums Nativos:** Usar siempre los enums de dominio (`CourseStatus`, `GroupStatus`, `PreinscriptionStatus`, `PaymentMethod`, etc.) en lugar de strings mágicos.
- **Observers Seguros:**
  - El envío de correos o notificaciones (`Mail`, `Notification`) dentro de observers o eventos SIEMPRE debe ser encolado (`Mail::to(...)->queue(...)`), nunca síncrono.
  - Sincronizar siempre las fuentes de verdad (ej. `estado` de pago vs `verificado_por`).

### Filament v3
- **Reactividad:**
  - Usar inyección de dependencias oficial de Filament: `function (Forms\Get $get, Forms\Set $set, $state)`.
  - NUNCA acceder a `request()->input(...)` para leer estado de formularios Livewire/Filament.
  - En `RelationManager`, el modelo contenedor/padre se accede mediante `$this->getOwnerRecord()`, no `$this->getRecord()`.

### Livewire v3 & Blade
- **Cero Queries N+1 en Vistas:**
  - PROHIBIDO ejecutar consultas a base de datos dentro de plantillas Blade (e.g. `$group->preinscriptions()->count()`).
  - Todo conteo o relación debe cargarse previamente en el componente usando `withCount` y `with()`.
- **Reactividad en Formularios:**
  - Si un campo dispara cambios en otros campos (como previsualizaciones de precio o selectores dependientes), usar `wire:model.live` o hooks de ciclo de vida (`updatedPropName()`).
- **Manejo de Fechas:**
  - Atributos casteados a fecha/hora (e.g. `datetime:H:i`) retornan instancias de `Carbon\Carbon`. Usar siempre `->format('H:i')` en Blade, nunca `substr()` sobre el objeto.

### Almacenamiento y Cloudinary (Imágenes y Certificados PDF)
- **Disco Oficial:** Todo archivo subido (fotografías de cursos/usuarios y documentos PDF de certificados) debe gestionarse mediante el disco `cloudinary` configurado en Flysystem (`Storage::disk('cloudinary')`) o Spatie MediaLibrary.
- **Diferenciación de Resource Types:**
  - **Imágenes (`image/*`):** Se clasifican como `resource_type => 'image'` para transformaciones, recorte y entrega optimizada por CDN.
  - **Certificados PDF (`application/pdf`):** Se clasifican como `resource_type => 'raw'` para preservar la integridad binaria del documento descargable y verificable.
- **Cero SDK Acoplado en Controladores:** Nunca importar ni llamar directamente al SDK de Cloudinary en controladores o vistas; interactuar siempre a través de `Storage::disk('cloudinary')` o servicios de dominio (`CertificateService`).
- **Zero Secrets & Testing:** Las credenciales (`CLOUDINARY_URL`, `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`) residen exclusivamente en `.env`. En tests unitarios y CI se debe usar siempre `Storage::fake('cloudinary')`.

---

## 4. Convenciones de Commits y Control de Versiones

- **Conventional Commits:** Formato obligatorio: `tipo(alcance opcional): descripción en presente`.
  - Ejemplos: `feat: implement public course catalog`, `fix: handle date formatting in blade`.
- **Sin Atribuciones IA:** Nunca agregar "Co-Authored-By", firmas automáticas de herramientas de IA ni trailers promocionales a los commits.
- **Código Limpio:** Ejecutar `./vendor/bin/pint` antes de commitear cambios.

---

## 5. Seguridad y Fuga de Secretos (Zero Secrets Policy)

La IA y los hooks de revisión deben BLOQUEAR cualquier commit que contenga:
- **Credenciales y Secretos:** Contraseñas, API keys, tokens de acceso (Bearer, GitHub, AWS, etc.), claves privadas (RSA, SSH, PGP) o secrets de autenticación.
- **Variables de Entorno Sensibles:** Jamás incluir valores reales de producción en archivos de configuración o código fuente. Todo valor sensible debe leerse mediante `config(...)` y residir exclusivamente en `.env` (ignorado por git).
- **Datos Sensibles en Tests/Seeders:** Usar siempre `fake()` o credenciales genéricas documentadas en `.env.example`.

---

## 6. Design System & Tokens de UI (CERO HEX HARDCODEADOS)

El proyecto tiene configurados los tokens institucionales de la UMSS en `tailwind.config.js`.

### Tipografía
- **Familia principal:** `Montserrat` (`font-sans`).

### Paleta Institucional y Mapeo de Tokens
PROHIBIDO escribir colores hexadecimales arbitrarios en clases Tailwind (e.g. `text-[#0E2E5F]`, `bg-[#F5F5F5]`). Se DEBE usar siempre la clase semántica correspondiente:

| Color Institucional | HEX | Clase Tailwind Obligatoria | Prohibido Hardcodear |
| :--- | :--- | :--- | :--- |
| **Azul marino institucional** | `#0E2E5F` | `text-umss-navy` / `bg-umss-navy` / `border-umss-navy` | `[#0E2E5F]` |
| **Azul marino oscuro** | `#0A2247` | `text-umss-navy-dark` / `bg-umss-navy-dark` | `[#0A2247]` |
| **Rojo institucional** | `#E01D2E` | `text-umss-red` / `bg-umss-red` / `border-umss-red` | `[#E01D2E]` |
| **Rojo oscuro** | `#8B0000` | `text-umss-red-dark` / `bg-umss-red-dark` | `[#8B0000]` |
| **Blanco** | `#FFFFFF` | `text-umss-white` / `bg-umss-white` (o `white`) | `[#FFFFFF]` |
| **Gris 100** | `#F5F5F5` | `bg-umss-gray-100` / `text-umss-gray-100` | `[#F5F5F5]` |
| **Gris 700** | `#4A4A4A` | `text-umss-gray-700` / `border-umss-gray-700` | `[#4A4A4A]` |
| **Negro** | `#121212` | `text-umss-black` / `bg-umss-black` | `[#121212]` |

Cualquier nuevo componente, vista Blade o componente Vue debe utilizar exclusivamente estos tokens.

