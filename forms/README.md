# Formulare

Das Bestellformular wird durch das getrennte PHP-Backend verarbeitet:

```text
order.html -> backend/send-order.php
```

Die öffentliche Website besteht weiterhin aus statischen HTML-, CSS- und JavaScript-Dateien. PHP wird nur für die Formularprüfung, temporäre Uploads und den E-Mail-Versand benötigt.

## E-Mail-Versand

`backend/send-order.php` versendet die Anfrage und die Kundenbestätigung über authentifiziertes STRATO SMTP. Die Zugangsdaten werden aus diesen Dateien geladen:

- `backend/config.php`: allgemeine, versionierte Einstellungen
- `backend/config.local.php`: lokales SMTP-Passwort; wird nicht in Git gespeichert

`config.local.php` muss beim Upload manuell im Ordner `backend/` abgelegt werden. Die dortige `.htaccess` verhindert den direkten Abruf im Browser.

## Voraussetzungen

Der Webserver benötigt PHP mit diesen Erweiterungen:

- `openssl` für die verschlüsselte SMTP-Verbindung
- `fileinfo` für die sichere Prüfung hochgeladener Bilder

Für einen lokalen Test im Projektordner:

```powershell
php -S localhost:8080
```

Danach `http://localhost:8080/order.html` öffnen. Eine statische Vorschau kann die PHP-Sicherheitsfrage und den E-Mail-Versand nicht ausführen.
