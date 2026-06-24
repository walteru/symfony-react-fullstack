#!/bin/sh
set -e

# El codigo vive en un volumen montado desde el host. var/ (cache y logs) lo
# escribe Apache (www-data), asi que le damos la propiedad en cada arranque.
mkdir -p var
chown -R www-data:www-data var

# Primer arranque tras el clone: vendor/ no existe, lo instalamos. Esto hace que
# "clone & run" funcione sin tener PHP ni Composer en el host.
if [ ! -f vendor/autoload.php ]; then
    echo "[api] instalando dependencias (composer install)..."
    composer install --no-interaction --prefer-dist --no-progress
    chown -R www-data:www-data var
fi

# Esperamos a que MySQL acepte conexiones (el servicio db puede tardar en el
# primer arranque mientras inicializa los datos).
echo "[api] esperando a MySQL..."
until php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; do
    sleep 2
done

# Migraciones idempotentes: aplica solo las pendientes. --allow-no-migration
# evita error si no hubiera ninguna.
echo "[api] aplicando migraciones..."
su www-data -s /bin/sh -c "php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration"

# Datos demo. La fixture es idempotente (no duplica si el usuario demo ya existe)
# y --append evita purgar la base en cada reinicio. Si fallara, abortamos el
# arranque (set -e): es preferible un error visible a un demo sin el usuario
# prometido en el README.
echo "[api] cargando datos demo (idempotente)..."
su www-data -s /bin/sh -c "php bin/console doctrine:fixtures:load --append --no-interaction"

exec apache2-foreground
