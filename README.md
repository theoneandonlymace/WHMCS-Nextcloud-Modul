# WHMCS Nextcloud Provisioning Module

## Überblick

Dieses WHMCS-Provisioning-Modul ermöglicht die automatische Verwaltung von Nextcloud-Benutzerkonten über die [OCS Provisioning API](https://docs.nextcloud.com/server/latest/admin_manual/configuration_user/user_provisioning_api.html).

### Funktionen

- **Benutzer erstellen** – Automatische Erstellung eines Nextcloud-Accounts bei Bestellung
- **Gruppenzuweisung** – Automatische Zuweisung zu einer konfigurierbaren Nextcloud-Gruppe (wird bei Bedarf erstellt)
- **Quota-Management** – Speicherplatz wird gemäß gebuchtem Paket gesetzt
- **Paketänderung** – Quota wird bei Up-/Downgrade automatisch angepasst
- **Suspend/Unsuspend** – Benutzer wird bei Zahlungsverzug deaktiviert und bei Zahlung wieder aktiviert
- **Kündigung** – Konfigurierbar: Benutzer löschen oder deaktivieren
- **Passwortänderung** – Unterstützung für Passwortänderungen über WHMCS
- **Client Area** – Zeigt gebuchten und genutzten Speicherplatz mit Fortschrittsbalken
- **Nextcloud-Link** – Direktlink zur Nextcloud-Instanz im Kundenbereich
- **E-Mail-Sync** – E-Mail-Adresse wird bei Profiländerungen an Nextcloud übertragen

## Installation

1. Den Ordner `modules/servers/nextcloud/` in das WHMCS-Installationsverzeichnis kopieren:

```
whmcs/modules/servers/nextcloud/
├── lib/
│   └── NextcloudAPI.php
├── templates/
│   ├── overview.tpl
│   └── error.tpl
├── hooks.php
└── nextcloud.php
```

2. In WHMCS unter **Setup → Products/Services → Servers** einen neuen Server anlegen:
   - **Type**: Nextcloud
   - **Hostname**: Ihre Nextcloud-Domain (z.B. `cloud.example.com`)
   - **Username**: Nextcloud-Admin-Benutzername
   - **Password**: Nextcloud-Admin-Passwort
   - **Secure**: Anhaken für HTTPS (empfohlen)
   - Klicken Sie auf **Test Connection** um die Verbindung zu prüfen

3. Ein Produkt erstellen unter **Setup → Products/Services → Products/Services**:
   - **Module Settings** Tab:
     - **Module Name**: Nextcloud
     - **Server**: Den zuvor erstellten Server auswählen
     - **Quota**: Speicherplatz eingeben (z.B. `5 GB`, `50 GB`, `1 TB`)
     - **Gruppenname**: Nextcloud-Gruppenname (optional)
     - **Termination**: Verhalten bei Kündigung wählen
   - **Custom Fields** Tab:
     - Feld erstellen: **Feldname** = `Nextcloud Username`, **Typ** = Text, **Required** = Ja
     - Feld erstellen: **Feldname** = `Nextcloud Password`, **Typ** = Password, **Required** = Ja

## Konfigurationsoptionen

| Option | Beschreibung | Beispiel |
|--------|-------------|----------|
| Quota | Speicherplatz für den Nextcloud-Benutzer | `5 GB`, `50 GB`, `1 TB` |
| Gruppenname | Nextcloud-Gruppe für die Benutzerzuweisung | `Premium-Users` |
| Termination | Verhalten bei Kündigung | `disable` oder `delete` |

## Voraussetzungen

- WHMCS 7.0 oder höher
- PHP 7.2 oder höher mit cURL-Erweiterung
- Nextcloud-Server mit aktivierter OCS Provisioning API
- Ein Nextcloud-Admin-Account für die API-Zugriffe

## Nextcloud-Anforderungen

Die OCS Provisioning API muss auf dem Nextcloud-Server erreichbar sein. Stellen Sie sicher, dass:

- Der Nextcloud-Server über HTTPS erreichbar ist
- Ein Admin-Benutzer mit vollen Rechten existiert
- Die Provisioning API nicht durch Firewall-Regeln blockiert wird

## Lizenz

MIT License
