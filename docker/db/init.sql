-- Se ejecuta una sola vez, cuando el contenedor de MySQL inicializa sus datos.
-- Crea la base de tests (Doctrine le agrega el sufijo _test) y le da acceso al
-- usuario de la aplicacion, para poder correr PHPUnit contra MySQL aislado.
CREATE DATABASE IF NOT EXISTS `app_test`;
GRANT ALL PRIVILEGES ON `app_test`.* TO 'app'@'%';
FLUSH PRIVILEGES;
