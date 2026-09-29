-- ============================================================
-- InventaPlus - Limpieza de los datos creados por las pruebas
-- Evidencia: GA7-220501096-AA5-EV04 - Pruebas de las API con Postman
-- ============================================================
-- Los usuarios y los equipos de prueba conservan el historial de
-- la entrega, por lo que la API responde 409 al intentar borrarlos.
-- Este script retira primero la asignacion y despues los registros
-- de prueba, para devolver la base de datos a su estado inicial.
--
-- Uso:  mysql -u root < limpiar-datos-prueba.sql
-- ============================================================

USE inventario;

-- 1. Asignacion de prueba (deja disponible el equipo).
DELETE FROM asignaciones
WHERE usuario_id IN (SELECT id FROM usuarios WHERE identificacion = '900112233');

-- 2. Usuario de prueba.
DELETE FROM usuarios WHERE identificacion = '900112233';

-- 3. Equipo de prueba.
DELETE FROM equipos WHERE serial = 'SN-EV04-001';

-- 4. Area de prueba.
DELETE FROM areas WHERE nombre = 'Comercializacion y Ventas';
DELETE FROM areas WHERE nombre = 'Comercializacion';

-- Reinicio de los contadores de identificacion.
ALTER TABLE asignaciones AUTO_INCREMENT = 1;
ALTER TABLE equipos        AUTO_INCREMENT = 5;
ALTER TABLE usuarios       AUTO_INCREMENT = 5;
ALTER TABLE areas          AUTO_INCREMENT = 10;

-- Verificacion del estado inicial del proyecto.
SELECT
  (SELECT COUNT(*) FROM areas)         AS areas,
  (SELECT COUNT(*) FROM usuarios)      AS usuarios,
  (SELECT COUNT(*) FROM administradores) AS administradores,
  (SELECT COUNT(*) FROM equipos)       AS equipos,
  (SELECT COUNT(*) FROM asignaciones)  AS asignaciones;
