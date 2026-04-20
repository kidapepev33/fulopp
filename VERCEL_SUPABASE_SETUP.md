# Vercel + Supabase Setup

Fecha: 2026-04-19

## Estado actual del proyecto
- Backend actual: PHP con `mysqli` (MySQL/MariaDB).
- Supabase Database: Postgres.

## Importante
- Con la arquitectura actual, **no se puede cambiar solo variables** para usar Supabase DB:
  - `mysqli` no funciona con Postgres.
  - Varias consultas SQL son MySQL-especificas.
- Lo que si queda listo con este cambio:
  - Deploy en Vercel con runtime PHP comunitario.
  - Variables de entorno preparadas.
  - Estructura para comenzar migracion a Supabase cuando decidas.

## Archivos agregados/ajustados
- `vercel.json`: runtime PHP + rewrites para soportar rutas `/fulopp/...`.
- `config/server.php`: ahora usa `DB_*` desde variables de entorno.
- `config/supabase.php`: helper de configuracion de Supabase.
- `.env.example`: plantilla de variables.
- `.gitignore`: evita subir secretos y archivos locales.

## Variables que debes configurar en Vercel
- `DB_HOST`
- `DB_PORT`
- `DB_USER`
- `DB_PASS`
- `DB_NAME`
- `SUPABASE_URL`
- `SUPABASE_ANON_KEY`
- `SUPABASE_SERVICE_ROLE_KEY`

## Recomendacion de seguridad
- Ya que las llaves de Supabase se compartieron en texto plano, rotalas en Supabase:
  - Project Settings -> API -> Regenerate keys.
- Nunca uses `SUPABASE_SERVICE_ROLE_KEY` en frontend.

## Siguiente paso para migrar realmente a Supabase DB
1. Crear capa de acceso a datos con `PDO pgsql` (o usar Supabase REST/Edge Functions).
2. Migrar consultas de `mysqli` a Postgres.
3. Importar esquema/datos en Supabase.
4. Probar endpoints uno por uno antes de despliegue final.
