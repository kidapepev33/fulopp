# Documentacion de Archivos JS y PHP

Fecha: 2026-04-19
Proyecto: fulopp

## JavaScript (assets/js)
- `add_student.js`: Maneja el formulario de agregar estudiante, construye/valida datos y envia al backend.
- `load_routes.js`: Carga rutas desde backend y pinta tarjetas/listado de rutas con filtros por rol.
- `load_students.js`: Carga estudiantes por ruta para vistas operativas y aplica reglas de acceso.
- `load_students_all.js`: Carga informe global, buscador y escaneo QR/codigo de barras con render de resultado.
- `perfil.js`: Controla el modulo Gestion (sidebar/paneles), perfil, alta/edicion de choferes y vehiculos, y permisos por rol.
- `student_editor.js`: Gestiona la edicion de estudiantes desde informe/ruta (lectura de datos, guardado y validaciones).

## JavaScript (assets/js/components)
- `header.js`: Inserta el header compartido, aplica reglas de visibilidad por autenticacion y logout.
- `toast.js`: Componente global de notificaciones/confirmaciones en esquina superior derecha (maximo 3 toasts).

## PHP (includes/functions)
- `auth_scope.php`: Centraliza alcance por sesion/rol (rutas permitidas y validaciones de seguridad).
- `create_driver.php`: Crea cuentas de chofer/admin y asigna vehiculo+rutas segun reglas.
- `create_student.php`: Registra estudiantes y datos base, con validaciones y restricciones por rol.
- `create_vehicle.php`: Crea vehiculos (placa, capacidad, estado, codigo interno) con validaciones.
- `delete_driver_account.php`: Elimina cuenta de chofer y limpia asignaciones relacionadas.
- `get_profile_data.php`: Devuelve datos para Gestion (perfil activo, rutas, vehiculos y choferes para admin).
- `get_student.php`: Consulta detalle de un estudiante para vista/edicion.
- `load_routes.php`: Lista rutas visibles segun permisos del usuario.
- `load_students.php`: Lista estudiantes por ruta con filtro segun alcance del usuario.
- `load_students_all.php`: Lista global de estudiantes (informe), respetando restricciones por rol.
- `profile_schema.php`: Asegura estructura minima requerida en BD para el modulo de perfil/gestion.
- `scan_student_barcode.php`: Procesa escaneo de codigo de barras y valida acceso por rutas asignadas.
- `update_driver_account.php`: Actualiza chofer (vehiculo y hasta dos rutas) con reglas de negocio.
- `update_student.php`: Actualiza datos de estudiante con control de permisos.
- `update_vehicle.php`: Actualiza vehiculo y, si pasa a mantenimiento, desasigna del chofer correspondiente.
