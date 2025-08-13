# KPI Dashboard (Versión PHP + MariaDB)

Esta es una aplicación web para visualizar Indicadores Clave de Rendimiento (KPIs) para un gerente comercial. Está construida con PHP, MariaDB y Bootstrap, y se ejecuta sobre un servidor Apache (idealmente usando XAMPP).

## Características

- **Dashboard Interactivo:** Visualiza KPIs generales, por sede, por agente y por closer.
- **Entrada de Datos por Formularios:** Se elimina la necesidad de cargar archivos. Los datos se ingresan directamente en la base de datos a través de formularios web.
- **Visualización Dinámica:** Los gráficos y tablas se generan en tiempo real a partir de los datos almacenados.
- **Exportación a PDF:** Genera un resumen en PDF del estado actual de los KPIs.

## Requisitos

- XAMPP (con Apache, MariaDB y PHP 8.0+).
- Un navegador web moderno.

## Instalación

1.  **Clonar o Descargar:** Coloca los archivos de la aplicación en una carpeta dentro del directorio `htdocs` de tu instalación de XAMPP (ej: `C:/xampp/htdocs/kpi-dashboard`).
2.  **Crear la Base de Datos:**
    -   Abre phpMyAdmin (`http://localhost/phpmyadmin`).
    -   Crea una nueva base de datos llamada `kpi_dashboard` (asegúrate de usar el cotejamiento `utf8mb4_general_ci`).
3.  **Importar la Estructura:**
    -   Dentro de la base de datos `kpi_dashboard`, ve a la pestaña "Importar".
    -   Selecciona el archivo `database.sql` que se encuentra en el directorio del proyecto.
    -   Haz clic en "Continuar" para crear las tablas y cargar los datos de ejemplo.
4.  **Configurar la Conexión:**
    -   Abre el archivo `db.php`.
    -   Si tu configuración de MariaDB en XAMPP no usa el usuario `root` o no tiene contraseña, ajusta las credenciales en este archivo. Por defecto, viene configurado para una instalación estándar de XAMPP.
5.  **Acceder a la Aplicación:**
    -   Abre tu navegador y ve a `http://localhost/kpi-dashboard/` (o el nombre de la carpeta que hayas usado).

## Uso

1.  **Navegación:** Utiliza el menú lateral para cambiar entre la vista del "Dashboard" y la de "Gestión de Datos".
2.  **Ingresar Datos:**
    -   Ve a "Gestión de Datos".
    -   Selecciona el mes y año para el cual deseas ingresar o actualizar información.
    -   Rellena los formularios para los datos financieros, y el rendimiento de sedes, agentes y closers.
    -   Haz clic en "Guardar Datos" para cada formulario. El sistema guardará la información en la base de datos.
3.  **Visualizar el Dashboard:**
    -   Regresa a la vista de "Dashboard".
    -   Los gráficos y tablas se actualizarán automáticamente con los últimos datos disponibles en la base de datos.
