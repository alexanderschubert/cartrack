# CarTrack

CarTrack ist eine selbst gehostete Webanwendung zur Verwaltung und Dokumentation von Fahrzeugen. Sie bündelt Kilometerstände, Tankvorgänge, Verbrauch, Versicherungslimits, Wartungen, Reifen, Kosten, Dokumente und Notizen. Ein Dashboard und Statistiken machen daraus eine übersichtliche Fahrzeughistorie.

Die Anwendung wird zuerst für einen Volkswagen Polo AW, Baujahr 2017, geplant. Das Datenmodell und die Benutzeroberfläche müssen von Anfang an mehrere Fahrzeuge und Benutzer unterstützen. Fahrzeugdaten wie Motorisierung, Kennzeichen, FIN, Tankvolumen und Kilometerstand werden konfiguriert und niemals fest in den Code geschrieben.

Das Projekt wird in GitHub unter einem Repository wie `github.com/<USERNAME>/cartrack` verwaltet und soll lokal auf macOS mit Docker entwickelbar sowie auf Unraid selbst hostbar sein.

## Projektziele

- Fahrzeugdaten und -historie an einem Ort verwalten.
- Kilometerstände chronologisch erfassen und daraus Fahrleistungen sowie Prognosen berechnen.
- Tankungen, Verbrauch, Kraftstoffpreise und Kosten auswerten.
- Jahresfahrleistung mit dem vereinbarten Versicherungslimit vergleichen.
- Wartungen, Reparaturen, Reifen und Dokumente nachvollziehbar ablegen.
- Eine sichere REST-API für Home Assistant und iOS-Kurzbefehle bereitstellen.
- Eine moderne, responsive Anwendung bauen, die sich eng an das bereitgestellte CarTrack-Mockup hält.
- PostgreSQL wahlweise als eigenen Container oder als bereits vorhandenen Server verwenden können.

## Produktumfang

Die Hauptnavigation umfasst:

- Dashboard
- Tanken
- Kilometer
- Versicherung
- Wartungen
- Reifen
- Kosten
- Dokumente
- Notizen
- Statistiken
- Einstellungen

Das Dashboard soll den Zustand des ausgewählten Fahrzeugs auf einen Blick zeigen: Fahrzeugkopf und aktueller Kilometerstand, Jahresfahrleistung, letzte Tankung, Durchschnittsverbrauch, Kraftstoffkosten, Kilometerdiagramm, letzte Tankungen, nächste Wartung und Kostenübersicht.

## UI und Mockup-Vorgaben

Das vom Nutzer bereitgestellte Mockup ist die verbindliche visuelle Referenz. Die Umsetzung soll sich so eng wie praktikabel an dessen Anordnung, Hierarchie und Stil halten. Ist das Bild im Repository nicht verfügbar, muss es vor der UI-Umsetzung als Referenzdatei ergänzt werden; fehlende Details dürfen nicht durch ein beliebiges Admin-Template ersetzt werden.

Gestaltungsziele:

- Dark Mode als Standard und eine moderne, hochwertige Anmutung.
- Dunkle Oberflächen mit Blau als primärer Akzentfarbe.
- Abgerundete Karten, zurückhaltende Schatten sowie dezente Verläufe und Leuchteffekte.
- Klare Typografie, gut lesbare große Kennzahlen, passende Icons und übersichtliche Diagramme.
- Desktop-Layout mit linker Sidebar; auf Smartphones kompakte, touchfreundliche Navigation.
- Responsive Darstellung für Desktop, Tablet und Mobile.
- Fahrzeugkopf mit Name, Baureihe/Baujahr, optionalem Fahrzeugbild und prominentem Kilometerstand.
- Einheitliche Komponenten und Zustände für Laden, leere Daten, Validierungsfehler und Erfolgsmeldungen.

Die Navigation und Dashboard-Karten sollen dem Mockup entsprechen. Beispielwerte wie „87.452 km“ oder „12.340 / 15.000 km“ sind ausschließlich illustrative Beispieldaten und keine fest eingebauten Fahrzeugdaten.

### Dashboard-Inhalte

- **Fahrzeugkopf:** Name, Modell/Baureihe, Baujahr, optionale Motorisierung, aktueller Kilometerstand und Veränderung heute.
- **Jahresfahrleistung:** gefahrene Kilometer, Limit, Fortschritt in Prozent, verbleibende Kilometer und Prognose.
- **Letzte Tankung:** Datum, Liter, Preis je Liter, Gesamtpreis, Tankstelle und Ort.
- **Verbrauch:** Durchschnitt in l/100 km und Veränderung gegenüber einem geeigneten Vergleichszeitraum.
- **Kraftstoffkosten:** Summe, Liter und Zeitraum.
- **Kilometerentwicklung:** auswählbarer Zeitraum und verständliche Zeitreihe.
- **Aktivität:** letzte Tankungen, nächste Wartung und Kosten nach Kategorie.

## Technologiestack

Vorgesehener Stack für die erste Umsetzung:

| Bereich | Technologie |
| --- | --- |
| Backend | Laravel 13, PHP 8.5 |
| Web-Frontend | Vue 3, Inertia.js, Tailwind CSS, Vite |
| Diagramme | Chart.js |
| Datenbank | PostgreSQL |
| Cache und Queue | Redis |
| API-Authentifizierung | Laravel Sanctum oder gleichwertige Laravel-Token-Authentifizierung |
| Laufzeit | Docker und Docker Compose |
| Zielplattform | Unraid; lokale Entwicklung auf macOS mit Docker |
| Quellcode | GitHub |

Die Versionsangaben sind der vorgesehene Projektstand und müssen bei Einrichtung gegen die tatsächlich verfügbaren, unterstützten Versionen und die Abhängigkeiten geprüft werden. Abweichungen sind zu dokumentieren. Zusätzliche Frameworks oder Dienste nur einführen, wenn sie einen klaren technischen Nutzen haben.

## Architektur und Betrieb

Die Anwendung wird als Laravel-Webanwendung mit Vue/Inertia umgesetzt. PostgreSQL speichert dauerhafte Anwendungsdaten. Redis unterstützt Cache und Queue. Hochgeladene Dateien liegen in einem persistenten Storage. Der App-Container enthält keine alleinige Kopie der Nutzerdaten und kann aktualisiert oder ersetzt werden, ohne Daten zu verlieren.

```text
iOS-Kurzbefehle ─┐
                  ├── HTTPS / REST API ── CarTrack (Laravel + Vue/Inertia)
Home Assistant ──┘                             │
                                      ┌────────┴────────┐
                                      │                 │
                                PostgreSQL           Redis
                                      │
                              persistente Dateien
```

In der Entwicklung können App, PostgreSQL und Redis gemeinsam über Docker Compose gestartet werden. In Unraid können App und Redis mit einer lokalen CarTrack-PostgreSQL-Instanz oder mit einem bereits vorhandenen PostgreSQL-Server betrieben werden. Reverse Proxy und TLS werden über die vorhandene Infrastruktur, zum Beispiel Nginx Proxy Manager, bereitgestellt.

## PostgreSQL: interne oder bestehende Instanz

Beide Betriebsarten sind gleichwertige, unterstützte Konfigurationen. Die Laravel-Anwendung verbindet sich über Konfigurationswerte aus der Umgebung; sie darf nicht voraussetzen, dass PostgreSQL im selben Compose-Projekt läuft.

### Option A: PostgreSQL-Container für CarTrack

Docker Compose startet einen eigenen PostgreSQL-Dienst. Daten liegen in einem benannten Volume oder einem explizit eingebundenen persistenten Pfad. Dieser Dienst ist für lokale Entwicklung und optional für den Produktivbetrieb vorgesehen.

```text
Unraid / Docker Compose
├── CarTrack
├── PostgreSQL für CarTrack (optional)
└── Redis
```

### Option B: vorhandener PostgreSQL-Server

Der Betreiber trägt Host, Port, Datenbank, Benutzer und Passwort der vorhandenen Instanz ein. Auf dem Server wird eine eigene Datenbank `cartrack` und ein eigener Datenbankbenutzer mit nur den benötigten Rechten angelegt. Datenbanken anderer Anwendungen, etwa Immich oder Paperless, werden nicht gemeinsam verwendet.

```text
PostgreSQL-Server
├── immich
├── paperless
└── cartrack
```

Beispielkonfiguration (Werte in der echten `.env` anpassen und geheim halten):

```dotenv
DB_CONNECTION=pgsql
DB_HOST=192.168.1.100
DB_PORT=5432
DB_DATABASE=cartrack
DB_USERNAME=cartrack
DB_PASSWORD=change-me
DB_SSLMODE=prefer
```

Für den lokalen Compose-Dienst ist `DB_HOST` typischerweise dessen Compose-Dienstname, zum Beispiel `postgres`. Für den vorhandenen Server wird dessen erreichbarer Hostname oder seine IP-Adresse verwendet. SSL/TLS zur Datenbank muss abhängig vom Server konfigurierbar sein; `require` ist zu verwenden, wenn der Server es unterstützt und vorschreibt. Zugangsdaten dürfen niemals in Git eingecheckt oder in Logs ausgegeben werden.

Die Erstinstallation muss klar dokumentieren, welche Umgebungsvariablen erforderlich sind, wie die Verbindung geprüft wird und wie Migrationen ausgeführt werden. Falls eine Einrichtungsoberfläche angeboten wird, dürfen darin Zugangsdaten nur geschützt verarbeitet werden; sie darf keine vorhandene Datenbank oder fremde Tabellen automatisch verändern. Datenbankmigrationen sollen ausschließlich das CarTrack-Schema verwalten.

## Docker und Konfiguration

Vorgesehene Repository-Dateien (Details dürfen der tatsächlichen Laravel-Projektstruktur angepasst werden):

```text
cartrack/
├── app/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── docker/
├── resources/
│   ├── css/
│   └── js/
├── routes/
├── storage/
├── tests/
├── .env.example
├── Dockerfile
├── docker-compose.yml
├── docker-compose.local-db.yml
└── README.md
```

`docker-compose.yml` soll die Anwendung und notwendige Laufzeitdienste enthalten, ohne eine externe PostgreSQL-Instanz zu erzwingen. Ein separates Compose-Overlay oder Profil kann den optionalen lokalen PostgreSQL-Dienst ergänzen. Redis ist konfigurierbar und für den vorgesehenen Betrieb dokumentiert.

Beispiel für `.env.example` (ohne echte Secrets):

```dotenv
APP_NAME=CarTrack
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cartrack.example.com
APP_KEY=

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=cartrack
DB_USERNAME=cartrack
DB_PASSWORD=
DB_SSLMODE=prefer

REDIS_HOST=redis
REDIS_PORT=6379

FILESYSTEM_DISK=local
```

`.env`, Schlüssel, API-Tokens, Datenbank-Dumps, Dokumente und private Fahrzeugdaten gehören in `.gitignore` und dürfen nicht ins GitHub-Repository gelangen. `.env.example` enthält nur Variablennamen und unverfängliche Beispielwerte.

## Datenmodell

Das relationale Modell ist auf mehrere Benutzer und mehrere Fahrzeuge ausgelegt. Daten werden über Fremdschlüssel und Policies sauber isoliert. Kilometer- und Tankhistorie werden als Ereignisse gespeichert; Kennzahlen werden aus den gespeicherten Ereignissen berechnet und nicht als widersprüchliche Kopien gepflegt.

### Kernentitäten

| Entität | Zweck und wesentliche Daten |
| --- | --- |
| `users` | Benutzerkonto und Authentifizierung. |
| `vehicles` | Besitzer, Marke, Modell, Baureihe, Baujahr, Motorisierung, Kraftstoffart, Tankvolumen, Kennzeichen, FIN, Startdatum und weitere konfigurierbare Fahrzeugdaten. Sensible Felder besonders schützen. |
| `odometer_readings` | Fahrzeug, Zeitpunkt/Datum, Kilometerstand, Quelle (`manual`, `home_assistant`, `ios_shortcut`, `api`, `import`), optionaler Hinweis und Erstellungszeit. |
| `fuel_entries` | Fahrzeug, Datum, Kilometerstand, Tankstelle, Ort, Liter, Preis je Liter, Gesamtpreis, Kraftstoffart, Volltank-Kennzeichen und Notiz. Geld- und Mengenwerte präzise speichern. |
| `insurance_periods` | Fahrzeug, Zeitraum, Startkilometer, Jahres-/Periodenlimit und optionale Notizen. |
| `maintenance_records` | Fahrzeug, Datum, Kilometerstand, Kategorie, Titel, Beschreibung, Kosten, Werkstatt, nächste Fälligkeit nach Datum und/oder Kilometerstand. |
| `tire_sets` | Fahrzeug, Typ (Sommer/Winter/Ganzjahr), Hersteller, Modell, Dimension, DOT, Kaufdaten und Lagerort. |
| `tire_events` | Montage/Demontage, Reifensatz, Datum, Kilometerstand und Profiltiefen pro Reifenposition; hält die Nutzungshistorie fest. |
| `expenses` | Fahrzeug, Datum, Kategorie, Betrag, Beschreibung und optionaler Bezug zu Tankung, Wartung oder anderem Vorgang. Doppelte Kostenbuchungen vermeiden. |
| `documents` | Fahrzeug und optional verknüpfter Vorgang, Dateimetadaten, Kategorie, Storage-Schlüssel und Zeitstempel; Binärdatei liegt außerhalb des Repositories. |
| `notes` | Benutzer/Fahrzeug, Text, optionaler Bezug zu einem Datensatz sowie Aufgabenstatus und Zeitstempel. |
| `personal_access_tokens` | Widerrufbare API-Tokens mit Berechtigungen, Besitzer, letzter Verwendung und optionalem Ablaufdatum. |

Alle benutzerbezogenen Abfragen müssen Berechtigungen serverseitig durchsetzen. IDs in URLs dürfen keinen Zugriff auf fremde Fahrzeuge oder Datensätze ermöglichen. Datumswerte und Zeitzonen sind konsistent zu behandeln. Kilometerstände dürfen nicht stillschweigend rückwärts springen; Korrekturen und Ausnahmen müssen nachvollziehbar validiert werden.

### Berechnungen

- Kilometer je Tag, Woche, Monat und Jahr aus den passenden Odometer-Ereignissen.
- Jahresfahrleistung und verbleibende Kilometer eines Versicherungszeitraums.
- Prognose der Jahresleistung aus den verfügbaren Daten; bei zu wenig oder ungeeigneten Daten einen neutralen Hinweis statt einer scheinpräzisen Prognose anzeigen.
- Verbrauch zwischen geeigneten aufeinanderfolgenden Volltankungen berechnen. Teilbetankungen fließen gemäß dokumentierter Regel in die Liter- und Kostenstatistik ein, dürfen aber keine irreführende Verbrauchszahl erzeugen.
- Gesamtpreis aus Liter × Preis pro Liter berechnen, Rundungsregeln dokumentieren und manuelle Korrekturen nachvollziehbar behandeln.
- Kosten pro Kilometer und Kosten nach Jahr/Kategorie aus den zugeordneten Kosten berechnen, ohne Tankungen oder Wartungen doppelt zu zählen.
- Fehlende Historie, Nullwerte und unvollständige Zeiträume in der Oberfläche kenntlich machen.

## REST API

Die versionierte API beginnt unter `/api/v1/`. Antworten sind konsistent, Eingaben validiert und Fehler mit passenden HTTP-Statuscodes und einer verständlichen JSON-Struktur versehen. Schreibzugriffe benötigen Authentifizierung und Berechtigung.

Vorgesehene Endpunkte:

```text
GET    /api/v1/vehicles
GET    /api/v1/vehicles/{vehicle}

GET    /api/v1/vehicles/{vehicle}/odometer
POST   /api/v1/vehicles/{vehicle}/odometer

GET    /api/v1/vehicles/{vehicle}/fuel
POST   /api/v1/vehicles/{vehicle}/fuel

GET    /api/v1/vehicles/{vehicle}/statistics
GET    /api/v1/vehicles/{vehicle}/insurance
GET    /api/v1/vehicles/{vehicle}/maintenance
```

Kilometerstand übermitteln:

```http
POST /api/v1/vehicles/1/odometer
Authorization: Bearer <token>
Content-Type: application/json
```

```json
{
  "odometer": 87452,
  "recorded_at": "2026-10-05T08:30:00+02:00",
  "source": "home_assistant"
}
```

Beispielantwort:

```json
{
  "data": {
    "id": 123,
    "vehicle": "Volkswagen Polo",
    "odometer": 87452,
    "recorded_at": "2026-10-05T08:30:00+02:00",
    "source": "home_assistant"
  }
}
```

API-Verträge müssen dokumentieren, ob die Übermittlung eines identischen Werts ein neues Ereignis erzeugt, wie Duplikate erkannt werden und wie ungültige bzw. niedrigere Kilometerstände behandelt werden. Für wiederholbare Integrationsaufrufe eine geeignete Idempotenz- oder Duplikatstrategie vorsehen. Antworten dürfen keine fremden Benutzer- oder Fahrzeuginformationen preisgeben.

## Home Assistant und iOS-Kurzbefehle

Der bestehende Ablauf soll weiter unterstützt werden: CarPlay/Benutzer löst einen iOS-Kurzbefehl aus, der Kilometerstand wird erfasst und kann über Home Assistant an CarTrack übermittelt werden. Ein späterer direkter Aufruf der CarTrack-API durch den Kurzbefehl ist ebenfalls möglich.

```text
CarPlay / iPhone
       ↓
iOS-Kurzbefehl
       ↓
Home Assistant (optional)
       ↓ HTTPS mit API-Token
CarTrack REST API
       ↓
Kilometerhistorie und Kennzahlen
```

Home-Assistant-Sensoren, die sich aus CarTrack-Daten ableiten lassen:

```text
sensor.cartrack_polo_odometer
sensor.cartrack_polo_yearly_kilometers
sensor.cartrack_polo_remaining_insurance_km
sensor.cartrack_polo_fuel_consumption
sensor.cartrack_polo_fuel_price
sensor.cartrack_polo_last_refuel
sensor.cartrack_polo_next_maintenance
```

Die API muss ausschließlich über HTTPS erreichbar sein, wenn sie außerhalb des geschützten lokalen Netzes genutzt wird. Integrationen erhalten eigene Tokens mit möglichst engen Berechtigungen. Tokens müssen einzeln widerrufbar sein und dürfen weder in Home-Assistant-Logs noch in Screenshots oder Versionskontrolle landen. Die Integrationsanleitung soll Beispielkonfigurationen enthalten, aber keine echten Zugangsdaten.

## Sicherheit und Datenschutz

- Anmeldung und serverseitige Autorisierung für alle Web- und API-Ressourcen.
- Strikte Isolation der Daten je Benutzer und Fahrzeug; keine Autorisierung allein anhand einer übermittelten ID.
- Sichere Passwortspeicherung mit den Laravel-Standards, CSRF-Schutz und Schutz vor Session-Missbrauch.
- Validierung und Normalisierung aller Eingaben; sichere ORM-Abfragen und parametrisierte Datenbankzugriffe.
- Rate-Limits für API-Endpunkte und sinnvolle Begrenzung von Uploads.
- API-Tokens gehasht speichern, Berechtigungen begrenzen, Ablauf/Widerruf ermöglichen und niemals im Klartext protokollieren.
- Sichere Datei-Uploads: erlaubte Dateitypen und Größen begrenzen, Dateinamen nicht als Pfad übernehmen, Zugriffe autorisieren und Dokumente nicht öffentlich ablegen.
- Secrets ausschließlich über Umgebungsvariablen oder einen Secret Store bereitstellen. `APP_DEBUG=false` im Produktivbetrieb.
- PostgreSQL mit eigenem CarTrack-Benutzer und minimal notwendigen Rechten betreiben; externe Verbindungen nach Möglichkeit per TLS schützen.
- Keine privaten Fahrzeugdaten, Kennzeichen, FIN, Dokumente oder Produktionsdaten in Beispieldaten, Logs oder GitHub einchecken.
- Backups für PostgreSQL und Dokumente getrennt planen und eine Wiederherstellung dokumentieren.
- Fehler und Audit-Informationen protokollieren, ohne Passwörter, Tokens oder vertrauliche Nutzdaten zu erfassen.

## Entwicklungsphasen

Die Reihenfolge liefert früh ein nutzbares Fundament und hält die Mockup-Treue von Beginn an im Blick.

### Phase 1 – Fundament und erstes Dashboard

- Laravel-/Vue-/Inertia-Projekt, Docker und Umgebungsbeispiele einrichten.
- PostgreSQL intern und extern konfigurierbar machen; Redis integrieren.
- Anmeldung, Benutzer-/Fahrzeugberechtigungen und Migrationen aufsetzen.
- Fahrzeuge anlegen und bearbeiten; mehrere Fahrzeuge im Datenmodell berücksichtigen.
- UI-Grundsystem, responsive Navigation und Dashboard nach Mockup umsetzen.

### Phase 2 – Kilometer und Integrationsbasis

- Kilometerstände manuell erfassen, auflisten und korrigieren.
- Kilometerhistorie, Tages-/Jahreswerte und belastbare Prognose bereitstellen.
- Versionierte, authentifizierte Kilometer-API und Token-Verwaltung implementieren.
- Anleitung für Home Assistant und iOS-Kurzbefehle erstellen.

### Phase 3 – Tankungen, Verbrauch und Kosten

- Tankungen erfassen und bearbeiten.
- Volltank-basierte Verbrauchsberechnung, Kraftstoffstatistiken und Kosten integrieren.
- Doppelte Kostenbuchungen vermeiden und Berechnungsregeln sichtbar dokumentieren.

### Phase 4 – Versicherung

- Versicherungszeiträume und Kilometerlimits verwalten.
- Fortschritt, verbleibende Kilometer und nachvollziehbare Prognose anzeigen.
- Warnstufen sachlich und anhand konfigurierbarer Schwellenwerte darstellen.

### Phase 5 – Wartungen und Reifen

- Wartungen und Reparaturen samt Kosten und nächster Fälligkeit verwalten.
- Reifen/Reifensätze, Montagehistorie, Profiltiefen und Lagerort abbilden.
- Fällige Wartungen im Dashboard hervorheben.

### Phase 6 – Dokumente, Notizen und Statistiken

- Geschützte Dokumentablage und Zuordnung zu Fahrzeugen/Vorgängen.
- Notizen und einfache Aufgabenliste ergänzen.
- Statistikseite für Kilometer, Verbrauch, Preise und Kosten vervollständigen.

### Phase 7 – Betrieb und Veröffentlichung

- Produktionsfähiges Container-Image und Unraid-Betriebsanleitung erstellen.
- Backup-/Restore-Ablauf, Updates und Migrationen dokumentieren.
- GitHub-Ablauf für Pull Requests und Image-Builds einrichten, sofern für das Repository gewünscht.

## Entwicklungsleitlinien

- Das Mockup ist die visuelle Referenz für alle Oberflächen.
- Zuerst die Datenmodelle und Berechnungsregeln sauber festlegen; Kennzahlen nicht als feste Beispielwerte implementieren.
- Fahrzeugdaten, Zugangsdaten und Instanzdetails ausschließlich konfigurierbar halten.
- Funktionen in nachvollziehbaren Phasen umsetzen; jede Phase soll in sich nutzbar und dokumentiert sein.
- Laravel-Konventionen und eine klare Trennung zwischen Validierung, Berechtigungen, Anwendungslogik und Darstellung verwenden.
- Datenbankmigrationen reversibel und mit PostgreSQL als Zielsystem entwickeln.
- Keine unnötigen Dienste oder Frameworks hinzufügen.
- Vor einer Änderung prüfen, dass sie für mehrere Fahrzeuge und Benutzer funktioniert.

## Definition of Done

Eine Funktion gilt als abgeschlossen, wenn die zutreffenden Punkte erfüllt sind:

- Die Funktion erfüllt die beschriebene Anforderung und verwendet persistente, korrekt verknüpfte Daten.
- Berechtigungen werden serverseitig geprüft; Benutzer können keine fremden Fahrzeuge oder Datensätze lesen oder verändern.
- Eingaben sind validiert; Fehler- und Leerzustände sind verständlich dargestellt.
- Die Oberfläche funktioniert auf Desktop und Mobile und folgt dem Mockup-Stil.
- Berechnungen, Rundungen, Einheiten und Umgang mit unvollständigen Daten sind dokumentiert und plausibel.
- Die Funktion funktioniert mit PostgreSQL sowohl im Docker-Netz als auch mit einer externen Instanz.
- Neue Umgebungsvariablen sind in `.env.example` beschrieben; keine Secrets oder privaten Daten gelangen ins Repository.
- API-Änderungen sind versioniert und mit Request-/Response-Beispielen dokumentiert.
- Dateiablage und Datenbankzustand bleiben über Container-Neustarts und Updates erhalten.
- Migrationen und ein reproduzierbarer Installations-/Updateweg sind vorhanden.
- Relevante automatisierte Prüfungen für Berechnungen, Berechtigungen und Integrationen laufen erfolgreich.
- Betriebs- und Benutzeranleitungen sind aktualisiert.

## Erste Schritte für die Implementierung

1. Mockup als Datei im Repository ablegen und UI-Anforderungen daraus konkretisieren.
2. Laravel-Projekt mit dem festgelegten Stack und einer Docker-Entwicklungsumgebung anlegen.
3. PostgreSQL-Verbindung so konfigurieren, dass sie ohne Codeänderung auf den lokalen Compose-Dienst oder einen externen Server zeigen kann.
4. Benutzer-, Fahrzeug- und Kilometerdatenmodell sowie Berechtigungen erstellen.
5. Responsive Dashboard-Grundlayout nach Mockup bauen und anschließend die Funktionen phasenweise ergänzen.

## Lizenz und Projektstatus

Lizenz und produktive Zugangsdaten werden vom Betreiber festgelegt. Der konkrete Stand der ersten Implementierung folgt im Abschnitt „Umsetzungsstand“; die übrigen Produktziele und Phasen bleiben die Zielvision.

## Umsetzungsstand

Der erste Arbeitsstand basiert auf Laravel 13, PHP 8.5, Vue 3, Inertia, Tailwind CSS, Chart.js und PostgreSQL. Die verbindliche UI-Referenz liegt unter [`docs/cartrack-mockup.png`](docs/cartrack-mockup.png). Fahrzeugdaten werden je Benutzer in PostgreSQL gespeichert; es gibt keine eingebauten Polo-Messwerte.

Bereits umgesetzt sind Benutzeranmeldung, mehrere Fahrzeuge pro Benutzer, ein responsives Dashboard, Kilometerstände, Tankungen, Versicherungszeiträume, eine Verbrauchsberechnung auf Basis geeigneter Volltankungen sowie ein versionierter Sanctum-API-Grundumfang. Im Bereich **Einstellungen → API-Tokens** können widerrufbare Tokens mit eingeschränkten Berechtigungen für Kurzbefehle und Home Assistant erstellt werden. Die weiteren Bereiche in der Produktvision (Wartungen, Reifen, Dokumente, Notizen und vollständige Kostenstatistiken) folgen in späteren Phasen.

## Lokal mit Docker starten

Voraussetzungen: Docker Desktop und Docker Compose v2.

```sh
cp .env.example .env
```

Passe in `.env` mindestens `APP_URL`, `DB_PASSWORD` und `APP_KEY` an. Für die lokale Datenbank starte App, PostgreSQL und Redis gemeinsam:

```sh
docker compose -f docker-compose.yml -f docker-compose.local-db.yml build
docker compose -f docker-compose.yml -f docker-compose.local-db.yml run --rm app php artisan key:generate --show
```

Setze den ausgegebenen Schlüssel als `APP_KEY` in `.env`. Danach:

```sh
docker compose -f docker-compose.yml -f docker-compose.local-db.yml run --rm app php artisan migrate --force
docker compose -f docker-compose.yml -f docker-compose.local-db.yml up -d
```

Die Web-App ist dann unter `http://localhost:8080` erreichbar. Für einen vorhandenen PostgreSQL-Server verwende nur `docker-compose.yml` und setze `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` und `DB_SSLMODE` in `.env`. Der lokale Compose-Overlay verwendet eine eigene CarTrack-Datenbank und verändert keine fremden Datenbanken.

## GitHub Container Registry und Unraid

Ein Push auf `main` baut mit GitHub Actions ein Image und veröffentlicht es als `ghcr.io/alexanderschubert/cartrack:latest` sowie mit einem Commit-Tag. Für Unraid braucht es einen PHP-/Docker-Host; GitHub Pages kann Laravel nicht ausführen. Das Image kann auf Unraid in einem Container oder Compose-Stack laufen, während PostgreSQL lokal oder auf einem vorhandenen Server bereitgestellt wird.

Für ein öffentlich abrufbares Image stelle die Sichtbarkeit des GitHub-Containerpakets auf **Public**. In Unraid sind mindestens `APP_KEY`, `APP_URL`, `DB_*`, `REDIS_HOST=redis`, `APP_DEBUG=false` und `APP_TIMEZONE=Europe/Berlin` zu konfigurieren. Port `8080` des Containers ist der HTTP-Port für den Reverse Proxy. Dokumente liegen unter `/var/www/storage/app/private` und müssen persistent bleiben; PostgreSQL-Daten werden separat gesichert. Bei manueller Container-Einrichtung müssen App und Redis im selben Docker-Netz liegen. Das Compose-Beispiel stellt App, Queue-Worker und Redis bereit; der Datenbankdienst ist im Overlay optional.

Das Initialschema wird kontrolliert mit `php artisan migrate --force` angewendet. Es gibt absichtlich kein automatisch ausgeführtes Datenbank-Setup beim Containerstart. Vor Updates PostgreSQL und persistente Dokumente sichern.

## Home Assistant und iOS-Kurzbefehle

Erstelle in **Einstellungen → API-Tokens** einen Token mit `odometer:write` (und optional `odometer:read`). Übermittle den aktuellen Kilometerstand:

```http
POST /api/v1/vehicles/{vehicle}/odometer
Authorization: Bearer <token>
Idempotency-Key: <stabile-eindeutige-id-des-kurzbefehl-laufs>
Content-Type: application/json
```

```json
{
  "odometer": 87452,
  "recorded_at": "2026-10-05T08:30:00+02:00",
  "source": "ios_shortcut"
}
```

Der `Idempotency-Key` verhindert doppelte Einträge bei wiederholten API-Aufrufen. Ein niedrigerer Stand mit aktuellem oder neuerem Zeitstempel wird mit HTTP 422 abgewiesen. Ein identischer Wert mit einem neuen Schlüssel wird als eigene Messung protokolliert. Verwende die Fahrzeug-ID aus `GET /api/v1/vehicles`. Tokens werden gehasht gespeichert, nur beim Erstellen einmal im Klartext gezeigt und können in den Einstellungen widerrufen werden. Erlaube API-Zugriffe außerhalb eines vertrauenswürdigen lokalen Netzes ausschließlich über HTTPS.
