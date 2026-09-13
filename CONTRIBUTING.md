# Contribuir — Plataforma de Formación Continua

## Convención de Ramas

- **Feature**: `feature/EP-XX-nombre-corto`
- **Fix**: `fix/EP-XX-nombre-corto`
- **Hotfix**: `hotfix/EP-XX-nombre-corto`
- **Refactor**: `refactor/EP-XX-nombre-corto`

## Convención de Commits (Conventional Commits)

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Tipos

- `feat`: Nueva funcionalidad
- `fix`: Corrección de bug
- `chore`: Tareas de mantenimiento
- `docs`: Documentación
- `style`: Formato de código
- `refactor`: Refactorización sin cambio de funcionalidad
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

**IMPORTANTE**: Las reglas de negocio SIEMPRE se definen primero en:

1. `BUSINESS_RULES.md` — Documentación
2. `app/Support/BusinessRules.php` — Implementación

**NUNCA** hardcodear reglas de negocio en controladores o modelos.

## Estilo de Código

- **Formatter**: Laravel Pint
- **Ejecutar**: `./vendor/bin/pint`
- **Verificar**: `./vendor/bin/pint --test`

## Guías de PR

1. **Título claro**: Describir el cambio en una línea
2. **Descripción**: Explicar QUÉ y POR QUÉ (no CÓMO)
3. **Tests**: Incluir tests para nuevas funcionalidades
4. **Documentación**: Actualizar `BUSINESS_RULES.md` si aplica
5. **Commits limpios**: Usar convención de commits

## Testing

```bash
# Ejecutar todos los tests
./vendor/bin/pest

# Ejecutar tests específicos
./vendor/bin/pest tests/Feature/CourseTest.php

# Cobertura
./vendor/bin/pest --coverage
```

## Estructura del Proyecto

```
app/
├── Enums/                    # Estados y enumeraciones
├── Filament/Admin/          # Panel de administración
├── Filament/Instructor/     # Panel de instructor
├── Http/Controllers/        # Controladores
├── Livewire/                # Componentes Livewire
├── Models/                  # Modelos Eloquent
├── Services/                # Servicios de negocio
└── Support/                 # Utilidades y reglas de negocio
```

## Requisitos

- PHP 8.2+
- Laravel 11
- Node.js 18+
- Docker (recomendado)
