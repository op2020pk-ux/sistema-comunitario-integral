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

- **Sistema Operativo:** Linux Mint / Ubuntu (o Windows con XAMPP).
- **Servidor Web y Base de Datos:** XAMPP con **Apache** y **MySQL / MariaDB** activos (PHP 8.0 o superior recomendado).
- **Navegador Web:** Google Chrome, Firefox, Brave o Edge actualizado.

---

## 2. Instalación del Sistema

### 2.1 Ubicación de los archivos en XAMPP

1. Copia la carpeta completa del proyecto `sistema_comunitario` dentro del directorio raíz de XAMPP:
  - En **Linux Mint**: `/opt/lampp/htdocs/sistema_comunitario`
  - En **Windows**: `C:\xampp\htdocs\sistema_comunitario`

2. Abre el panel de control de XAMPP y asegúrate de que los servicios **Apache** y **MySQL** estén iniciados:

```bash
sudo /opt/lampp/lampp start
