# Bridge API voor Freestays

Deze bridge-API (`api.php`) gebruikt nu `.env` variabelen voor alle gevoelige connectiegegevens.

## Vereisten
- PHP 7.2+
- Composer dependency: `vlucas/phpdotenv`
- `.env` bestand in `freestays-hotelplatform/config/` (zie `sample.env` voor voorbeeld)

## Configuratie
1. Kopieer `freestays-hotelplatform/config/sample.env` naar `.env` en vul de juiste waarden in:
   - DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
   - API_USER, API_PASS, API_URL
   - CORS_ALLOWED_ORIGINS
2. Installeer dependencies in de root van het project:
   ```
   composer install
   ```
3. De bridge laadt automatisch `.env` bij elke request.

## Gebruik
- Alle credentials worden geladen uit `.env` via `phpdotenv`.
- CORS origins zijn configureerbaar via de variabele `CORS_ALLOWED_ORIGINS` (komma-gescheiden).
- Nooit credentials hardcoden in de code!

## Debuggen
- Zet `DEBUG_MODE=true` in `.env` voor extra logging.
- Fouten worden gelogd via `error_log()` en als JSON response teruggegeven.

## Voorbeeld `.env`
Zie `freestays-hotelplatform/config/sample.env` voor een volledig voorbeeld.
