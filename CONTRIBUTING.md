# Contribuir — Plataforma de Formacion Continua

## Convencion de Ramas

- **Feature**: `feature/EP-XX-nombre-corto`
- **Fix**: `fix/EP-XX-nombre-corto`
- **Hotfix**: `hotfix/EP-XX-nombre-corto`
- **Refactor**: `refactor/EP-XX-nombre-corto`

## Convencion de Commits (Conventional Commits)

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Tipos

- `feat`: Nueva funcionalidad
- `fix`: Correccion de bug
- `chore`: Tareas de mantenimiento
- `docs`: Documentacion
- `style`: Formato de codigo
- `refactor`: Refactorizacion sin cambio de funcionalidad
- `test`: Agregar o corregir tests
- `perf`: Mejora de rendimiento

### Ejemplos

```
feat(courses): add automatic pricing based on load hours
fix(attendance): correct haversine distance calculation
chore(deps): update laravel/framework to v11.0
docs(business-rules): update pricing table
```

## Reglas de Negocio

Las reglas de negocio se definen siempre en:

1. `BUSINESS_RULES.md` — Documentacion
2. `app/Support/BusinessRules.php` — Implementacion

Nunca hardcodear reglas de negocio en controladores o modelos.

## Estilo de Codigo

- **Formatter**: Laravel Pint
- **Ejecutar**: `./vendor/bin/pint`
- **Verificar**: `./vendor/bin/pint --test`

## Guias de PR

1. **Titulo claro**: Describir el cambio en una linea
2. **Descripcion**: Explicar que y por que (no como)
3. **Tests**: Incluir tests para nuevas funcionalidades
4. **Documentacion**: Actualizar `BUSINESS_RULES.md` si aplica
5. **Commits limpios**: Usar convencion de commits

## Testing

```bash
# Ejecutar todos los tests
docker compose exec app ./vendor/bin/pest

# Ejecutar tests especificos
docker compose exec app ./vendor/bin/pest tests/Feature/CourseTest.php

# Cobertura
docker compose exec app ./vendor/bin/pest --coverage
```

## Estructura del Proyecto

```
app/
├── Enums/                    # Estados y enumeraciones
├── Filament/Admin/           # Panel de administracion
├── Filament/Instructor/      # Panel de instructor
├── Http/Controllers/         # Controladores
├── Livewire/                 # Componentes Livewire
├── Models/                   # Modelos Eloquent
├── Services/                 # Servicios de negocio
└── Support/                  # Utilidades y reglas de negocio
```

## Requisitos

- PHP 8.3+
- Laravel 11
- Node.js 20+
- Docker (recomendado)
