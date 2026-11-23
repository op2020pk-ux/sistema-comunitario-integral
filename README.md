# Manual de Instalación y Uso — Sistema Comunal

**Sistema Comunitario Integral**  
*Guía rápida de instalación en entorno local (XAMPP / Linux Mint) y manual de operación por módulos.*

---

## Tabla de Contenidos

1. [Requisitos Previos](#1-requisitos-previos)

2. [Instalación del Sistema](#2-instalación-del-sistema)

   - [2.1 Ubicación de los archivos en XAMPP](#21-ubicación-de-los-archivos-en-xampp)
   - [2.2 Creación e importación de la Base de Datos](#22-creación-e-importación-de-la-base-de-datos)
   - [2.3 Configuración de la conexión](#23-configuración-de-la-conexión)
   - [2.4 Permisos de carpetas de imágenes](#24-permisos-de-carpetas-de-imágenes)
   - [2.5 Primer acceso al sistema](#25-primer-acceso-al-sistema)

3. [Guía de Uso por Módulos](#3-guía-de-uso-por-módulos)

   - [3.1 Portada e Inicio de Sesión (`index.php`)](#31-portada-e-inicio-de-sesión-indexphp)
   - [3.2 Panel Principal (`dashboard.php`)](#32-panel-principal-dashboardphp)
   - [3.3 Comunidades, Calles y Viviendas](#33-comunidades-calles-y-viviendas)
   - [3.4 Familias y Habitantes (`modulos/habitantes/`)](#34-familias-y-habitantes-moduloshabitantes)
   - [3.5 Estudiantes, Beneficios y Fallecidos](#35-estudiantes-beneficios-y-fallecidos)
   - [3.6 Carnets de Jefe de Familia y Usuario (`carnet/`)](#36-carnets-de-jefe-de-familia-y-usuario-carnet)
   - [3.7 Usuarios y Fotografía de Perfil (`modulos/usuarios/` e `img_usuarios/`)](#37-usuarios-y-fotografía-de-perfil-modulosusuarios-e-img_usuarios)
   - [3.8 Círculo de Abuelos (`modulos/circulo_abuelos/`)](#38-círculo-de-abuelos-moduloscirculo_abuelos)

---

## 1. Requisitos Previos

* **Sistema Operativo:** Linux Mint / Ubuntu (o Windows con XAMPP).
* **Servidor Web y Base de Datos:** XAMPP con **Apache** y **MySQL / MariaDB** activos (PHP 8.0 o superior recomendado).
* **Navegador Web:** Google Chrome, Firefox, Brave o Edge actualizado.

---

## 2. Instalación del Sistema

### 2.1 Ubicación de los archivos en XAMPP

1. Copia la carpeta completa del proyecto `sistema_comunitario` dentro del directorio raíz de XAMPP:
   * En **Linux Mint**: `/opt/lampp/htdocs/sistema_comunitario`
   * En **Windows**: `C:\xampp\htdocs\sistema_comunitario`

2. Abre el panel de control de XAMPP y asegúrate de que los servicios **Apache** y **MySQL** estén iniciados:

```bash
sudo /opt/lampp/lampp start
```

> **[ESPACIO PARA IMAGEN — Panel de control de XAMPP / Carpeta del proyecto en `htdocs`]**  
> `![Instalación en XAMPP](imagenes_manual/01_instalacion_xampp.png)`

---

### 2.2 Creación e importación de la Base de Datos

1. Abre tu navegador e ingresa a **phpMyAdmin**:  
   `http://localhost/phpmyadmin`
2. Haz clic en la pestaña **SQL** (o en **Importar** si tienes el archivo `.sql`).
3. Pega o carga el script de creación de la base de datos **`sistema_comunitario_integral`** (incluye tablas, vistas `vista_consulta_ciudadano`, `vista_fallecidos_detalle` y el trigger `after_insert_fallecido`) junto con el usuario inicial `admin`.
4. Presiona **Continuar** para ejecutar la creación.

> **[ESPACIO PARA IMAGEN — Base de datos `sistema_comunitario_integral` creada en phpMyAdmin]**  
> `![Base de Datos en phpMyAdmin](imagenes_manual/02_base_datos_phpmyadmin.png)`

---

### 2.3 Configuración de la conexión

Verifica que el archivo `config/conexion.php` tenga los parámetros locales de tu servidor MySQL:

* **Host:** `localhost`
* **Usuario BD:** `root`
* **Contraseña BD:** `""` *(vacía por defecto en XAMPP)*
* **Base de datos:** `sistema_comunitario_integral`

---

### 2.4 Permisos de carpetas de imágenes

En Linux Mint, otorga permisos de escritura a las carpetas donde se almacenan los logos, fotos de usuarios y fotografías de los habitantes para los carnets:

```bash
cd /opt/lampp/htdocs/sistema_comunitario
mkdir -p fotos uploads/sistema uploads/usuarios
sudo chmod -R 777 fotos uploads
```

---

### 2.5 Primer acceso al sistema

1. Entra en tu navegador a:  
   `http://localhost/sistema_comunitario/` 
2. Haz clic en **Entrar al Sistema** e inicia sesión con las credenciales iniciales:
   * **Usuario:** `admin`
   * **Contraseña:** `admin123`

---

## 3. Guía de Uso por Módulos

### 3.1 Portada e Inicio de Sesión (`index.php`)

Es la pantalla principal de acceso al **Sistema Comunal**. Muestra la imagen institucional a pantalla completa y un botón para desplegar el formulario de inicio de sesión seguro.

* **Uso:** Haz clic en **Entrar al Sistema**, escribe tu usuario y contraseña, y presiona **Ingresar al Sistema**.

> **[ESPACIO PARA IMAGEN — Portada e inicio de sesión `index.php`]**  
> `![Portada y Login](capture/1_inicio.png)`

---

### 3.2 Panel Principal (`dashboard.php`)

Muestra el resumen estadístico del censo comunitario y el menú lateral de navegación. Si ingresas como **SuperAdmin**, puedes filtrar los indicadores por comunidad o ver el consolidado general.

* **Uso:** Revisa los contadores generales y selecciona en el menú izquierdo el módulo con el que deseas trabajar.

> **[ESPACIO PARA IMAGEN — Panel Principal `dashboard.php`]**  
> `![Panel Principal](capture/4_dashboar.png)`

---

### 3.3 Comunidades, Calles y Viviendas

Permite estructurar territorialmente cada Consejo Comunal antes de registrar familias:

* **Comunidades:** Registra sectores, RIF o código, parroquia, municipio y estado.
* **Calles / Veredas:** Subdivide cada comunidad y asigna su vocero o líder de calle.
* **Viviendas:** Registra casas o apartamentos, tipo de tenencia, condición estructural y servicios básicos (agua, electricidad, gas directo, internet).

> **[ESPACIO PARA IMAGEN — Módulo de Comunidades / Calles / Viviendas]**  
> `![Territorio y Viviendas](capture/6_viviendas.png)`

---

### 3.4 Familias y Habitantes (`modulos/habitantes/`)

Gestiona los núcleos familiares y el censo individual de cada ciudadano:

* **Familias:** Agrupa a los integrantes que comparten un núcleo familiar dentro de una vivienda.
* **Registrar / Editar Habitante (`registrar_habitante.php` / `editar_habitante.php`):** Permite cargar los datos personales, parentesco, caracterización social/salud y **subir o actualizar la fotografía** (guardada en `fotos/`) para su carnet.

> **[ESPACIO PARA IMAGEN — Registro y Edición de Habitantes con fotografía]**  
> `![Módulo de Habitantes](capture/11_configuracion.png)`

---

### 3.5 Estudiantes, Beneficios y Fallecidos

* **Estudiantes:** Registra el nivel educativo, grado o semestre e institución de los habitantes marcados como estudiantes.
* **Beneficios Entregados:** Controla la entrega de bolsas CLAP, cilindros de gas, kits escolares y jornadas médicas por familia.
* **Fallecidos:** Registra defunciones y desactiva automáticamente al habitante del censo activo mediante el trigger de la base de datos.

> **[ESPACIO PARA IMAGEN — Módulos de Estudiantes, Beneficios y Fallecidos]**  
> `![Estudiantes Beneficios y Fallecidos](capture/9_beneficios.png)`

---

### 3.6 Carnets de Jefe de Familia y Usuario (`carnet/`)

Genera credenciales ampliadas listas para imprimir o guardar en PDF:

* **Carnet de Jefe de Familia (`carnet/carnet_jefe_familia.php`):** Muestra la foto del titular, datos del núcleo familiar, dirección, total de integrantes y código QR.
* **Carnet Institucional de Usuario (`carnet/carnet_usuario.php`):** Muestra la foto del operador/vocero, rol, cédula, usuario, comunidad y código QR.

> **[ESPACIO PARA IMAGEN — Vista de Carnet de Jefe de Familia y Carnet de Usuario]**  
> `![Carnets del Sistema](capture/12_carnet.png)`

---

### 3.7 Usuarios y Fotografía de Perfil (`modulos/usuarios/` e `img_usuarios/`)

Permite administrar el acceso de voceros y administradores al sistema:

* **Gestión de Usuarios (`modulos/usuarios/guardar_usuario.php`):** Crea o edita usuarios, asigna roles (`SuperAdmin`, `Administrador Comunidad`, `Vocero/Censador`), comunidad y fotografía.
* **Subir Imagen de Usuario (`img_usuarios/subir_img_usuario.php`):** Permite cambiar de forma rápida la foto de perfil almacenada en `uploads/usuarios/`.

> **[ESPACIO PARA IMAGEN — Gestión de Usuarios y Carga de Imagen]**  
> `![Usuarios y Foto de Perfil](capture/10_usuarios.png)`

### Carnet de todos los integrantes de la comunidad
> `![Carnet de los usuarios](capture/12_carnet.png)`

![Imagen de Inicio del Sistema](Img_Sistema/Bienvenida.png)


---

