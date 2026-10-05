# Formulare

Das Bestellformular wird durch das getrennte PHP-Backend verarbeitet:

```text
order.html -> backend/send-order.php
```

Die öffentliche Website besteht weiterhin aus statischen HTML-, CSS- und JavaScript-Dateien. PHP wird nur für die Formularprüfung, temporäre Uploads und den E-Mail-Versand benötigt.

## E-Mail-Versand

`backend/send-order.php` versendet die Anfrage und die Kundenbestätigung über authentifiziertes STRATO SMTP. Die geheimen Zugangsdaten liegen auf dem Server außerhalb des öffentlich erreichbaren Website-Ordners:

- `backend/config.php`: allgemeine, versionierte Einstellungen
- STRATO `/private/nunique-config.php`: SMTP-Passwort
- `backend/config.local.php`: ausschließlich für die lokale PHP-Vorschau

Eine Vorlage befindet sich in `docs/nunique-config.example.php`. Die ausgefüllte Datei wird einmal manuell nach `/private/nunique-config.php` auf STRATO übertragen. Sie wird weder in Git gespeichert noch von GitHub Actions hochgeladen.

## Schutz vor Spam und Missbrauch

Das Formular arbeitet ohne externen CAPTCHA-Dienst. Es kombiniert einen sitzungsgebundenen CSRF-Token, ein verstecktes Honeypot-Feld, eine Zeitprüfung, ein gleitendes serverseitiges Rate Limit und eine kurzzeitige Duplikaterkennung. Alle Prüfungen, die über die Annahme einer Anfrage entscheiden, laufen in PHP auf dem eigenen Server.

## Voraussetzungen

Der Webserver benötigt PHP mit diesen Erweiterungen:

- `openssl` für die verschlüsselte SMTP-Verbindung
- `fileinfo` für die sichere Prüfung hochgeladener Bilder

Für einen lokalen Test im Projektordner:

```powershell
php -S localhost:8080
```

Danach `http://localhost:8080/order.html` öffnen. Eine statische Vorschau kann die PHP-Sicherheitsfrage und den E-Mail-Versand nicht ausführen.
