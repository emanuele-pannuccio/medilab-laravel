#!/bin/ash

# 1. Logica di inizializzazione (Bootstrap)
if [[ ! -d ./vendor ]]; then
    echo "Files mancanti, eseguo installazione iniziale..."
    composer install --no-interaction
    
    # Nota: Assicurati che l'utente esista nel container se usi chown
    chown 1000:1000 -R .
    
    cp .env.example .env
    php artisan key:generate
    php artisan migrate --force
    php artisan reverb:install
    npm install
fi

# 2. Avvio di Supervisord
echo "Avvio di Supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf