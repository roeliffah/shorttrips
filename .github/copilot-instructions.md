# Copilot Instructions for Freestays Hotel Booking Platform

## Architectuur en Belangrijkste Componenten
- **WordPress Back-end**: De map `freestays-hotelplatform` bevat een volledige WordPress-installatie. De kern van de maatwerkfunctionaliteit zit in de plugin `freestays-booking` (`wp-content/plugins/freestays-booking`).
- **Pluginstructuur**: De plugin bevat een API-client voor Sunhotels (`includes/api/class-sunhotels-client.php`), een algemene API-laag (`class-freestays-api.php`), booking handlers, shortcodes en admin settings.
- **Frontend**: De map `frontend` bevat een losstaande React-app (Create React App). Deze communiceert mogelijk via REST of een bridge met de WordPress-backend.

## Database & API Connectie
- Database- en API-gegevens worden beheerd via een `.env` bestand in `freestays-hotelplatform/config/` (zie `sample.env`).
  - Voorbeeld variabelen: `DB_HOST`, `DB_USER`, `DB_PASS`, `API_USER`, `API_PASS`, `API_URL` (Sunhotels endpoint).
- De Sunhotels API wordt benaderd via de `SunhotelsClient` class. Authenticatiegegevens worden uit de configuratie geladen.
- Voor productie: gebruik de bridge/database connectie-instellingen van freestays.eu.

## Belangrijke Workflows
- **Plugin installatie**: Upload de map `freestays-booking` naar `wp-content/plugins/` en activeer via het WordPress admin menu.
- **Configuratie**: Kopieer `config/sample.env` naar `.env` en vul de juiste waarden in voor database en API.
- **Frontend**: Start lokaal met `npm start` in de `frontend` map. Voor productie: build met `npm run build`.

## Projectspecifieke Patronen
- **API-integratie**: Alle externe hoteldata komt via de Sunhotels API. Zie `class-sunhotels-client.php` voor request/response structuur.
- **Shortcodes**: Gebruik WordPress shortcodes om zoekformulieren en hoteloverzichten in pagina's te plaatsen.
- **Templates**: Aanpasbare weergave via PHP-templates in de pluginmap (`templates/`).
- **Bridge**: Voor externe connecties (zoals database op freestays.eu) altijd `.env` gebruiken, niet hardcoden.

## Bestanden en Directories om te kennen
- `freestays-hotelplatform/config/README.md` en `sample.env`: uitleg en voorbeeld voor configuratie.
- `freestays-hotelplatform/wp-content/plugins/freestays-booking/README.md`: plugin documentatie.
- `freestays-hotelplatform/README.md`: projectstructuur en overzicht.
- `frontend/README.md`: frontend build/test instructies.

## Debuggen & Testen
- **WordPress logs**: Raadpleeg de standaard WordPress debug logs voor fouten.
- **Frontend**: Gebruik `npm test` voor React tests.
- **API-fouten**: Controleer response van Sunhotels API via logging in de plugin.

## Integratie & Uitbreiding
- Nieuwe API's of externe systemen? Volg het patroon van `class-sunhotels-client.php` voor authenticatie en request handling.
- Nieuwe shortcodes of templates? Voeg toe in de pluginmap en registreer via de bestaande handler classes.

---

> Raadpleeg altijd de genoemde README's en `.env` voorbeeld voor actuele details. Voor gevoelige connecties (zoals bridge/database) nooit credentials hardcoden, altijd via configuratie.
