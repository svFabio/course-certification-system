# Guía de Instalación y Desarrollo en Windows Nativo (Sin Docker / Sin WSL)

Esta guía detalla los pasos exactos para configurar y levantar la **Plataforma de Formación Continua (UMSS)** en Windows de forma nativa.

---

## 1. Requisitos Previos (Herramientas a Instalar)

### A. PHP 8.3 (64-bit Thread Safe)
1. Descarga el paquete ZIP **VS16 x64 Thread Safe** de PHP 8.3 desde:  
   👉 [https://windows.php.net/download/](https://windows.php.net/download/)
2. Extrae el contenido en una carpeta como: `C:\php83`.
3. Agrega `C:\php83` a la variable de entorno **PATH** de tu sistema:
   - Presiona `Win + R`, escribe `sysdm.cpl` y Enter.
   - Pestaña **Opciones avanzadas** → **Variables de entorno**.
   - En **Variables del sistema**, selecciona `Path` → **Editar** → **Nuevo** → ingresa `C:\php83` y guarda.
4. Abre PowerShell y verifica con:
   ```powershell
   php -v
   ```

### B. Configurar `php.ini` (¡Paso crítico!)
1. Ve a `C:\php83`, haz una copia de `php.ini-development` y renómbrala como `php.ini`.
2. Abre `php.ini` en tu editor (VS Code, Notepad, etc.).
3. Busca la línea:
   ```ini
   ;extension_dir = "ext"
   ```
   Quita el punto y coma (`;`) para descomentarla:
   ```ini
   extension_dir = "ext"
   ```
4. Busca las siguientes extensiones y quítales el punto y coma (`;`) al inicio para habilitarlas:
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
5. Guarda los cambios. Verifica en PowerShell que no haya errores de carga:
   ```powershell
   php -m
   ```

---

### C. PostgreSQL para Windows
1. Descarga el instalador oficial de PostgreSQL (versión 15 o 16):  
   👉 [https://www.enterprisedb.com/downloads/postgres-postgresql-downloads](https://www.enterprisedb.com/downloads/postgres-postgresql-downloads)
2. Instálalo recordando la contraseña que le asignas al usuario `postgres`.
3. Abre **pgAdmin** o la consola **SQL Shell (psql)** y crea la base de datos:
   ```sql
   CREATE DATABASE plataforma_formacion;
   ```

---

### D. Composer
1. Descarga y ejecuta el instalador oficial:  
   👉 [https://getcomposer.org/Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe)
2. El instalador detectará automáticamente `C:\php83\php.exe`.
3. Verifica en PowerShell:
   ```powershell
   composer --version
   ```

---

### E. Node.js (LTS)
1. Descarga el instalador LTS de Node.js (v20 o superior):  
   👉 [https://nodejs.org/](https://nodejs.org/)
2. Verifica en PowerShell:
   ```powershell
   node -v
   npm -v
   ```

---

## 2. Configuración del Proyecto

Abre PowerShell en la carpeta raíz del proyecto (`03-course-certification-system`):

### Paso 1: Copiar el archivo de entorno
```powershell
copy .env.example .env
```

### Paso 2: Configurar la conexión a PostgreSQL en `.env`
Abre el archivo `.env` y ajusta las siguientes variables con tus credenciales locales:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=plataforma_formacion
DB_USERNAME=postgres
DB_PASSWORD=tu_password_de_postgres
```

### Paso 3: Instalar dependencias
```powershell
composer install
npm install
```

### Paso 4: Generar clave y enlaces de almacenamiento
```powershell
php artisan key:generate
php artisan storage:link
```
> **Nota para Windows:** Si `php artisan storage:link` da error de privilegios, activa el **Modo de desarrollador** en la configuración de Windows (*Privacidad y seguridad → Para programadores → Modo de desarrollador*) o abre PowerShell como Administrador.

### Paso 5: Ejecutar migraciones y datos de prueba
```powershell
php artisan migrate --seed
```

---

## 3. Ejecución en Desarrollo

Recomendamos usar **Windows Terminal** con 3 pestañas abiertas en la raíz del proyecto:

### Pestaña 1: Servidor Web (Laravel)
```powershell
php artisan serve
```
- Aplicación pública: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- Panel Administrador (Filament): [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)
- Panel Instructor (Filament): [http://127.0.0.1:8000/instructor](http://127.0.0.1:8000/instructor)

### Pestaña 2: Bundler de Frontend (Vite)
```powershell
npm run dev
```

### Pestaña 3: Cola de Tareas en Segundo Plano (Notificaciones / Mails)
```powershell
php artisan queue:work
```

---

## 4. Credenciales de Prueba (Seeders)

| Rol | Correo | Contraseña |
|---|---|---|
| **Administrador** | `admin@umss.edu.bo` | `password` |
| **Instructor** | `instructor@umss.edu.bo` | `password` |
| **Estudiante** | `student@umss.edu.bo` | `password` |

---

## 5. Ejecutar Pruebas Automatizadas (Pest)

Para correr la suite de pruebas:
```powershell
./vendor/bin/pest
```
O con el comando de Artisan:
```powershell
php artisan test
```
