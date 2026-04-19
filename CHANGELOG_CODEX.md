# Resumen de Cambios - Sesion de Implementacion

Fecha: 2026-04-19
Proyecto: fulopp

## 1) Navegacion y Header
- Se reemplazo el enlace de "Agregar Estudiante" por "Gestion" en el header.
- Se unifico la carga de header en un solo archivo responsive (`header-desktop.html`).
- Se elimino `includes/components/header-mobile.html`.
- Se actualizo `assets/js/components/header.js` para cargar un solo header y mantener reglas de acceso.
- Se ajusto `assets/css/components/header.css` para comportamiento responsive real (desktop/tablet/mobile).

## 2) Modulo Gestion (perfil)
- Se creo la pagina de gestion completa en `pages/perfil.html` con sidebar y paneles:
  - Perfil
  - Agregar Estudiante
  - Agregar Chofer
  - Editar Choferes
  - Agregar Vehiculo
  - Editar Vehiculos
  - Vehiculos (listado con accion editar)
- Se implemento `assets/js/perfil.js` con manejo de:
  - Carga de datos de perfil
  - Alta/edicion/borrado de choferes
  - Alta/edicion de vehiculos
  - Reglas de asignacion de vehiculos
  - Cambio de paneles
  - Restricciones por rol
- Se creo y ajusto `assets/css/pages/perfil.css` (estilos de paneles, formularios y componentes del modulo).

## 3) Roles y permisos
- Se estandarizaron roles en `chofer.rol`: `admin` y `chofer`.
- Se creo `includes/functions/auth_scope.php` para centralizar alcance por sesion y rutas permitidas.
- Se forzo control por backend para que chofer no admin solo vea/edite lo permitido.
- Se limito acceso a rutas, estudiantes y recursos protegidos mediante los endpoints de datos.

## 4) Modelo de datos de rutas por chofer
- Se migro la logica de asignacion de rutas desde vehiculo a chofer.
- Se usa `chofer_rutas` como relacion principal.
- Se dejo de usar `vehiculo_rutas` en codigo.
- Se actualizaron scripts SQL:
  - `database/create_vehiculos_tables.sql`
  - `database/update_chofer_table.sql`
  - `database/cleanup_unused_tables.sql` (limpieza opcional de tabla legacy no usada)

## 5) Vehiculos
- Alta de vehiculo:
  - Estado ahora es select (`activo`/`mantenimiento`).
  - Capacidad permanece number.
  - Validaciones backend normalizadas de estado.
- Edicion de vehiculo:
  - Nuevo endpoint `includes/functions/update_vehicle.php`.
  - Permite editar placa, capacidad y estado.
  - Si pasa a mantenimiento y tiene chofer asignado, desasigna automaticamente del chofer.
  - Si no tiene chofer asignado, no aplica advertencia de desasignacion.

## 6) Choferes
- Alta de chofer:
  - Seleccion de vehiculo por codigo interno con reglas de disponibilidad.
  - Vehiculos en mantenimiento no pueden asignarse.
- Edicion de chofer:
  - Nuevo endpoint `includes/functions/update_driver_account.php`.
  - Permite cambiar/quitar vehiculo y cambiar rutas (maximo 2).
- Borrado de chofer:
  - Nuevo endpoint `includes/functions/delete_driver_account.php`.

## 7) Perfil de chofer no admin
- En Gestion, chofer no admin ve solo el panel Perfil.
- En Perfil se muestra:
  - Nombre
  - Correo
  - Vehiculo asignado
  - Rutas asignadas

## 8) Estudiantes y escaneo
- Se reforzo filtrado por rutas permitidas en:
  - `load_routes.php`
  - `load_students.php`
  - `load_students_all.php`
  - `get_student.php`
  - `update_student.php`
  - `create_student.php`
- Se creo `includes/functions/scan_student_barcode.php` para escaneo con control de permisos.
- Escaneo fuera de ruta:
  - Muestra aviso grande
  - Muestra igualmente informacion del estudiante
  - Incluye ruta/chofer correspondiente
- Se rediseño la tarjeta de resultado de escaneo para mejor lectura.

## 9) Tablas responsive
- Se hizo responsive el patron de tabla de estudiantes (informe/ruta y tablas similares del mismo estilo).
- Se agregaron `data-label` en celdas para render tipo card en mobile.
- Se mantuvieron proporciones visuales de foto y QR.

## 10) Sistema Toast global
- Se creo componente global de notificaciones:
  - CSS: `assets/css/components/toast.css`
  - JS: `assets/js/components/toast.js`
- Reglas actuales:
  - Posicion: esquina superior derecha
  - Maximo: 3 toasts simultaneos
- Se reemplazaron mensajes/alerts por toast en scripts principales:
  - `add_student.js`
  - `student_editor.js`
  - `auth/login.js`
  - `load_routes.js`
  - `load_students.js`
  - `load_students_all.js`
  - `perfil.js`

## 11) Limpieza
- Se elimino `includes/functions/get_vehicle_by_code.php` (sin uso).
- Se removio helper no usado en `profile_schema.php`.
- Se removieron estilos de toast incrustados y se movieron al componente global.

## 12) Archivos nuevos principales
- `assets/css/components/toast.css`
- `assets/js/components/toast.js`
- `database/create_vehiculos_tables.sql`
- `database/update_chofer_table.sql`
- `database/cleanup_unused_tables.sql`
- `includes/functions/auth_scope.php`
- `includes/functions/get_profile_data.php` (expandido)
- `includes/functions/scan_student_barcode.php`
- `includes/functions/update_driver_account.php`
- `includes/functions/delete_driver_account.php`
- `includes/functions/update_vehicle.php`

## 13) Notas
- Se validaron sintaxis PHP en endpoints nuevos/modificados durante el proceso.
- Se preservo la estructura existente del proyecto y se extendio progresivamente.
