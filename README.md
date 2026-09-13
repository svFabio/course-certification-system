# Plataforma de Formacion Continua con Certificacion Digital Verificable

Sistema web para gestion de cursos, preinscripciones, asistencia geolocalizada y emision de certificados digitales verificables.

## Requisitos

- Docker y Docker Compose

Para desarrollo en Windows sin Docker, consulta [SETUP_WINDOWS.md](SETUP_WINDOWS.md).

## Instalacion con Docker

```bash
git clone https://github.com/umss/formacion-continua.git
cd formacion-continua
cp .env.example .env
docker compose up -d
```

Listo. La aplicacion estara disponible en `http://localhost:8000`.

El primer arranque tarda mas porque Docker compila el frontend y ejecuta las migraciones automaticamente.

## URLs

| URL | Descripcion |
|-----|-------------|
| `http://localhost:8000` | Catalogo de cursos |
| `http://localhost:8000/admin` | Panel de administracion |
| `http://localhost:8000/instructor` | Panel de instructor |
| `http://localhost:8000/verificar-certificado` | Verificacion de certificados |
| `http://localhost:8000/asistencia` | Registro de asistencia (QR + GPS) |

## Credenciales de Prueba

| Usuario | Contrasena | Rol |
|---------|------------|-----|
| admin@umss.edu.bo | password | Administrador |
| instructor@umss.edu.bo | password | Instructor |
| student@umss.edu.bo | password | Estudiante |

## Conexion a la Base de Datos

Para conectarse con DBeaver u otro cliente:

| Campo | Valor |
|-------|-------|
| Host | `localhost` |
| Puerto | `5432` |
| Base de datos | `plataforma_formacion` |
| Usuario | `plataforma` |
| Contrasena | `plataforma_secret` |

## Stack Tecnologico

- **Backend**: Laravel 11 LTS
- **Base de datos**: PostgreSQL 16
- **Panel admin**: Filament v3
- **Roles**: spatie/laravel-permission
- **Frontend publico**: Livewire + Blade
- **Componente de asistencia**: Vue 3 + html5-qrcode (isla Vite)
- **PDF**: barryvdh/laravel-dompdf + simple-qrcode
- **Reportes Excel**: maatwebsite/laravel-excel
- **Colas**: Laravel Queue (driver database)
- **Testing**: Pest PHP
- **Linting**: Laravel Pint
- **Contenedores**: Docker Compose

## Arquitectura

```
Panel Admin (/admin)          Filament v3 + Spatie Roles
Panel Instructor (/instructor) Filament v3 + Spatie Roles
Pagina Publica                Blade + Livewire
Asistencia QR+GPS             Vue 3 (isla Vite)
Servicios                     app/Services/ + app/Support/BusinessRules.php
Base de datos                 PostgreSQL 16
```

## Estructura del Proyecto

```
app/
├── Enums/                         # Backed enums para status fields
├── Filament/Admin/Resources/      # Panel admin (Cursos = referencia completa)
├── Filament/Instructor/Resources/ # Panel instructor
├── Http/Controllers/              # Controladores (rutas API)
├── Http/Middleware/               # EnsureUserIsAdmin, EnsureUserIsInstructor
├── Livewire/                      # Componentes publicos
├── Models/                        # Modelos Eloquent con relaciones
├── Notifications/                 # Notificaciones en cola (mail)
├── Policies/                      # Autorizacion por rol
├── Providers/                     # AdminPanelProvider, InstructorPanelProvider
├── Services/                      # Logica de negocio
└── Support/BusinessRules.php      # Constantes centralizadas de reglas de negocio
```

## Modulos

| Modulo | Estado | Archivos |
|--------|--------|----------|
| Auth y Roles | Configurado | Spatie Permission + seeder |
| Cursos | Completo (referencia) | Model, Migration, Factory, Policy, Filament Resource con Wizard |
| Grupos | Stub | Model, Migration, Policy, Resource |
| Preinscripciones | Stub | Model, Migration, Policy, Livewire |
| Inscripciones y Pagos | Stub | Model, Migration, Policy, Resource |
| Sesiones y Asistencia | Stub | Model, Migration, Policy, Resource + Vue QR scanner |
| Evaluaciones | Stub | Model, Migration, Policy, Resource |
| Certificados | Stub | Model, Migration, Policy, Resource |
| Verificacion Publica | Stub | Livewire component |
| Reportes | Stub | TODO |

## Reglas de Negocio

Ver `BUSINESS_RULES.md` para documentacion completa.

Las constantes estan centralizadas en `app/Support/BusinessRules.php`. Nunca hardcodear valores de negocio en controladores o modelos.

## Desarrollo

```bash
# Ejecutar tests
docker compose exec app ./vendor/bin/pest

# Formatear codigo
docker compose exec app ./vendor/bin/pint

# Regenerar migraciones (base limpia)
docker compose exec app php artisan migrate:fresh --seed

# Si cambias archivos de frontend (Vue, CSS), reconstruir la imagen
docker compose build && docker compose up -d
```

## Licencia

MIT License - Universidad Mayor de San Simon
