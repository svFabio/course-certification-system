# Plataforma de Formación Continua con Certificación Digital Verificable

Sistema web para gestión de cursos, preinscripciones, asistencia geolocalizada y emisión de certificados digitales verificables.

## Requisitos

- PHP 8.3+
- Composer 2+
- Node.js 20+
- Docker & Docker Compose (opcional)

> 💡 **¿Desarrollas en Windows sin Docker ni WSL?** Consulta la guía paso a paso: [SETUP_WINDOWS.md](SETUP_WINDOWS.md).

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/umss/formacion-continua.git
cd formacion-continua
```

### 2. Configurar entorno

```bash
cp .env.example .env
```

### 3. Iniciar servicios Docker

```bash
docker compose up -d
```

### 4. Instalar dependencias PHP

```bash
docker compose exec app composer install
```

### 5. Generar clave de aplicación

```bash
docker compose exec app php artisan key:generate
```

### 6. Ejecutar migraciones y seeds

```bash
docker compose exec app php artisan migrate --seed
```

### 7. Instalar dependencias Node

```bash
npm install
```

### 8. Compilar assets

```bash
npm run dev
```

## URLs de Acceso

| URL | Descripción |
|-----|-------------|
| `http://localhost` | Página pública - Catálogo de cursos |
| `http://localhost/admin` | Panel de administración |
| `http://localhost/instructor` | Panel de instructor |
| `http://localhost/verificar-certificado` | Verificación de certificados |
| `http://localhost/asistencia` | Registro de asistencia (QR + GPS) |

## Credenciales de Prueba

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin@umss.edu.bo | password | Administrador |
| instructor@umss.edu.bo | password | Instructor |
| student@umss.edu.bo | password | Estudiante |

## Stack Tecnológico

- **Backend**: Laravel 11 LTS
- **Base de datos**: PostgreSQL 16
- **Panel admin**: Filament v3
- **Roles**: spatie/laravel-permission
- **Frontend público**: Livewire + Blade
- **Componente de asistencia**: Vue 3 + html5-qrcode (isla Vite)
- **PDF**: barryvdh/laravel-dompdf + simple-qrcode
- **Reportes Excel**: maatwebsite/laravel-excel
- **Colas**: Laravel Queue (driver database)
- **Testing**: Pest PHP
- **Linting**: Laravel Pint
- **Container**: Docker Compose

## Arquitectura

```
┌─────────────────────────────────────────────────────────┐
│                    Panel Admin (/admin)                  │
│                    Filament v3 + Spatie Roles            │
├─────────────────────────────────────────────────────────┤
│                    Panel Instructor (/instructor)        │
│                    Filament v3 + Spatie Roles            │
├─────────────────────────────────────────────────────────┤
│              Página Pública (Blade + Livewire)           │
│              Catálogo, Preinscripción, Verificación      │
├─────────────────────────────────────────────────────────┤
│           Asistencia QR+GPS (Vue 3 "isla" Vite)         │
│           POST /asistencia/registrar                     │
├─────────────────────────────────────────────────────────┤
│              Servicios (app/Services/)                   │
│              BusinessRules (app/Support/)                │
├─────────────────────────────────────────────────────────┤
│               PostgreSQL 16                              │
└─────────────────────────────────────────────────────────┘
```

## Estructura del Proyecto

```
app/
├── Enums/                    # Backed enums para status fields
├── Filament/Admin/Resources/ # Resources del panel admin (Cursos = referencia completa)
├── Filament/Instructor/Resources/ # Resources del panel instructor
├── Http/Controllers/         # Controllers (API routes)
├── Http/Middleware/           # EnsureUserIsAdmin, EnsureUserIsInstructor
├── Livewire/                 # Componentes públicos (catálogo, preinscripción, verificación)
├── Models/                   # Eloquent models con relationships
├── Notifications/            # Notificaciones queued (mail)
├── Policies/                 # Authorization por rol
├── Providers/                # AdminPanelProvider, InstructorPanelProvider
├── Services/                 # Lógica de negocio pura
└── Support/BusinessRules.php # Constantes centralizadas de reglas de negocio
```

## Módulos

| Módulo | Estado | Archivos |
|--------|--------|----------|
| Auth y Roles | Configurado | Spatie Permission + seeder |
| Cursos | **Completo (referencia)** | Model, Migration, Factory, Policy, Filament Resource con Wizard |
| Grupos | Stub | Model, Migration, Policy, Resource |
| Preinscripciones | Stub | Model, Migration, Policy, Livewire |
| Inscripciones y Pagos | Stub | Model, Migration, Policy, Resource |
| Sesiones y Asistencia | Stub | Model, Migration, Policy, Resource + Vue QR scanner |
| Evaluaciones | Stub | Model, Migration, Policy, Resource |
| Certificados | Stub | Model, Migration, Policy, Resource |
| Verificación Pública | Stub | Livewire component |
| Reportes | Stub | TODO |

## Reglas de Negocio

Ver `BUSINESS_RULES.md` para documentación completa.

Constantes centralizadas en `app/Support/BusinessRules.php` — importar desde ahí, nunca hardcodear.

## Desarrollo

### Ejecutar tests

```bash
./vendor/bin/pest
```

### Formatear código

```bash
./vendor/bin/pint
```

### Compilar assets en modo watch

```bash
npm run dev
```

## Licencia

MIT License - Universidad Mayor de San Simón
