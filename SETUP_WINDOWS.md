# Guia de Instalacion en Windows Nativo (Sin Docker)

Esta guia detalla como configurar la Plataforma de Formacion Continua (UMSS) en Windows sin usar Docker ni WSL.

---

## 1. Requisitos Previos

### PHP 8.3 (64-bit Thread Safe)

1. Descarga el paquete ZIP **VS16 x64 Thread Safe** de PHP 8.3 desde:
   https://windows.php.net/download/
2. Extrae el contenido en `C:\php83`.
3. Agrega `C:\php83` a la variable de entorno **PATH**:
   - Presiona `Win + R`, escribe `sysdm.cpl` y Enter.
   - Pestana **Opciones avanzadas** → **Variables de entorno**.
   - En **Variables del sistema**, selecciona `Path` → **Editar** → **Nuevo** → ingresa `C:\php83`.
4. Verifica en PowerShell:
   ```powershell
   php -v
   ```

### Configurar php.ini

1. Ve a `C:\php83`, copia `php.ini-development` y renombrala como `php.ini`.
2. Abre `php.ini` en tu editor.
3. Busca y descomenta (quita el `;`):
   ```ini
   extension_dir = "ext"
   ```
4. Habilita las siguientes extensiones quitando el `;` al inicio de cada linea:
   ```ini
   extension=bcmath
   extension=curl
   extension=fileinfo
   extension=gd
   extension=intl
   extension=mbstring
   extension=exif
   extension=pdo_pgsql
   extension=pgsql
   extension=zip
   ```
5. Verifica en PowerShell:
   ```powershell
   php -m
   ```

---

### PostgreSQL

1. Descarga el instalador oficial de PostgreSQL 16 desde:
   https://www.enterprisedb.com/downloads/postgres-postgresql-downloads
2. Instala recordando la contrasena del usuario `postgres`.
3. Crea la base de datos con psql o DBeaver:
   ```sql
   CREATE DATABASE plataforma_formacion;
   ```

---

### Composer

1. Descarga y ejecuta el instalador oficial:
   https://getcomposer.org/Composer-Setup.exe
2. El instalador detectara automaticamente `C:\php83\php.exe`.
3. Verifica:
   ```powershell
   composer --version
   ```

---

### Node.js 20 LTS

1. Descarga el instalador LTS desde:
   https://nodejs.org/
2. Verifica:
   ```powershell
   node -v
   npm -v
   ```

---

## 2. Configuracion del Proyecto

Abre PowerShell en la carpeta raiz del proyecto:

```powershell
copy .env.example .env
```

Edita `.env` con tus credenciales de PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=plataforma_formacion
DB_USERNAME=postgres
DB_PASSWORD=tu_contrasena_de_postgres
```

Instala dependencias y configura:

```powershell
composer install
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm install
npm run build
```

> **Nota:** Si `php artisan storage:link` da error de privilegios, activa el Modo de desarrollador en Windows (Configuracion → Privacidad y seguridad → Para programadores) o ejecuta PowerShell como Administrador.

---

## 3. Ejecucion en Desarrollo

Abre tres terminales en la raiz del proyecto:

**Terminal 1 — Servidor web:**
```powershell
php artisan serve
```

**Terminal 2 — Frontend con recarga automatica:**
```powershell
npm run dev
```

**Terminal 3 — Cola de tareas:**
```powershell
php artisan queue:work
```

La aplicacion estara disponible en http://127.0.0.1:8000

---

## 4. Credenciales de Prueba

| Rol | Correo | Contrasena |
|-----|--------|------------|
| Administrador | admin@umss.edu.bo | password |
| Instructor | instructor@umss.edu.bo | password |
| Estudiante | student@umss.edu.bo | password |

---

## 5. Ejecutar Tests

```powershell
./vendor/bin/pest
```
