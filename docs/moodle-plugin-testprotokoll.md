# Testprotokoll: Checkliste für ein perfektes Moodle-Plugin

> **Zweck:** Diese Checkliste begleitet ein Plugin von der ersten Code-Zeile bis zur Veröffentlichung im Moodle Plugin Directory. Hake jeden Punkt vor dem Release ab. Bereiche, die für dein Plugin nicht zutreffen (z. B. Web Services), kannst du als „n. z." markieren.

> **Geprüftes Plugin:** `bookingextension_oneclick` (Booking-Subplugin „Booking AI: One-click instance")
> **Version:** 2026061602 / `v1.1.0` · **Geprüft am:** 2026-06-18 · **Moodle:** 5.1.1+ · **PHP:** 8.3.28 · **DB:** MariaDB 10.11
> **Legende:** `[x]` erfüllt · `[ ]` offen/nicht erfüllt · `[~]` teilweise · **n. z.** = nicht zutreffend

## 1. Plugin-Struktur & Metadaten

- [x] Verzeichnisstruktur entspricht dem Plugin-Typ — korrektes Booking-Subplugin unter `mod/booking/bookingextension/oneclick/`
- [x] `version.php` vorhanden und vollständig
- [x] `$plugin->component` korrekt gesetzt — `bookingextension_oneclick`, deckt sich mit Verzeichnis
- [x] `$plugin->version` als Datumsformat `JJJJMMTTXX` — `2026061602`
- [x] `$plugin->requires` auf die Mindest-Moodle-Version gesetzt — `2024100700` (Moodle 4.5)
- [ ] `$plugin->maturity` korrekt — **fehlt**: kein `$plugin->maturity` in `version.php` gesetzt
- [x] `$plugin->release` als menschenlesbare Versionsangabe — `v1.1.0`
- [x] `$plugin->dependencies` deklariert — `mod_booking` + `bookingextension_agent`
- [~] Keine überflüssigen / temporären Dateien im Release — kein `node_modules`; aber das Plugin ist ein **eigenes Git-Repo** (`.git/` im Ordner), das beim Release-Export auszuschließen ist
- [x] Komponentenname kollidiert nicht mit Core- oder Drittanbieter-Plugins — eindeutig

## 2. Lizenz & rechtliche Grundlagen

- [x] Lizenz ist GPL v3 oder kompatibel — GPL v3 or later
- [x] Jede PHP-Datei enthält den GPL-Lizenzheader — in allen Dateien vorhanden
- [x] `@copyright`, `@license` und `@package` in den Datei-Headern korrekt — durchgängig „2026 Wunderbyte GmbH"
- [x] Verwendete Bibliotheken / Assets haben kompatible Lizenzen — n. z., keine externen Libs/Assets gebündelt
- [x] Keine fremden Markenrechte / geschützten Inhalte ohne Erlaubnis

## 3. Coding Standards & Code-Qualität

- [x] Code folgt den offiziellen Moodle Coding Guidelines
- [~] `local_codechecker` läuft ohne Fehler — Quellcode sauber; **1 ERROR + 1 WARNING in `tests/delete_instance_skill_test.php`** (Z. 94 „Expected 1 space after class keyword", Z. 98 fehlender Docblock für `__construct`)
- [x] PHPDoc-Blöcke für alle Klassen, Methoden und Funktionen
- [x] Keine veralteten (deprecated) Core-Funktionen verwendet
- [x] Keine direkten Datenbankzugriffe an der DB-API vorbei — ausschließlich `$DB`
- [x] Konsistente Einrückung, Namenskonventionen und Frankenstyle-Präfixe
- [x] Keine auskommentierten Code-Leichen oder `var_dump`/`error_log`-Reste — nutzt `debugging()`
- [x] PHP-Linting ohne Syntaxfehler — `php -l` über alle Dateien sauber (PHP 8.3)

## 4. Sicherheit (kritisch!)

- [x] **SQL-Injection:** Ausschließlich `$DB`-API mit Platzhaltern — inkl. `get_in_or_equal()`, keine Konkatenation
- [x] **XSS:** Ausgaben bereinigt — JS-Preview nutzt durchgängig `escapeHtml()`; PHP-Texte über `get_string()`
- [x] **CSRF:** Aktion läuft über die External/AJAX-API (sesskey-geschützt), kein eigenes ungeschütztes Formular
- [x] **Zugriffskontrolle:** External-API ruft `validate_context()`; Einstieg ist der Agent (eigene Login-Prüfung)
- [x] **Kontextprüfung:** `require_capability('bookingextension/oneclick:viewjobstatus', $context)` im Webservice
- [x] Benutzereingaben validiert — `external_value` mit `PARAM_INT`/`PARAM_ALPHAEXT`; Skills via `check_structure`/`preflight`
- [x] Datei-Zugriffe über die Moodle File API — n. z., das Plugin verarbeitet keine Dateien/Uploads
- [~] Keine Offenlegung sensibler Daten — Shared Secret bleibt rein serverseitig (`configpasswordunmask`, nie im Browser). **Hinweis:** `provisioner_client` setzt bewusst `curl(['ignoresecurity' => true])` und umgeht damit die SSRF-/cURL-Security-Guard. Begründet (admin-only, nicht nutzergesteuerte URL, interner Host) und im Code dokumentiert — bei einem Audit explizit benennen
- [x] Keine Ausführung von Benutzereingaben (`eval`/`system`/dynamische Includes) — keine vorhanden

## 5. Datenbank (XMLDB & Upgrades)

- [x] `db/install.xml` mit dem XMLDB-Editor erstellt — eine Tabelle `bookingextension_oneclick_jobs`
- [x] Tabellen-/Spaltennamen folgen Moodle-Konventionen
- [x] Sinnvolle Indizes und Schlüssel definiert — Primary, FK `userid→user`, Index `jobid`, UNIQUE `targetrelease`
- [ ] `db/upgrade.php` deckt alle Schemaänderungen ab — **nicht vorhanden**; aktuell nur Erstinstall-Schema. Bei künftigen Schemaänderungen zwingend nachzuziehen
- [ ] Jeder Upgrade-Schritt setzt `upgrade_plugin_savepoint()` — n. z. (kein `upgrade.php`)
- [ ] Upgrade von der ältesten unterstützten Version getestet — **nicht getestet**
- [x] Saubere Deinstallation — kein Sonder-Cleanup nötig; Tabelle wird über `install.xml` entfernt, personenbezogene Daten über Privacy-API löschbar; `db/uninstall.php` nicht erforderlich
- [ ] Funktioniert mit MySQL/MariaDB **und** PostgreSQL — nur auf MariaDB verifiziert; XMLDB ist portabel, PostgreSQL nicht gegengeprüft

## 6. Capabilities & Berechtigungen

- [x] `db/access.php` definiert alle benötigten Capabilities — 3 Stück
- [x] Capability-Namen folgen dem Schema — `bookingextension/oneclick:skill_oneclick_create_instance`, `:skill_oneclick_delete_instance`, `:viewjobstatus`
- [x] Korrekte `riskbitmask`-Angaben — `RISK_SPAM` (create), `RISK_DATALOSS` (delete); `viewjobstatus` ist read ohne Risk
- [x] Sinnvolle Standardrechte pro Rolle (`archetypes`) — `manager => CAP_ALLOW`
- [x] Sprachstrings für jede Capability vorhanden — in `lang/en` und `lang/de`
- [x] Berechtigungen werden im Code tatsächlich geprüft — `require_capability` im Webservice; Skill-Caps durch die Agent-Engine erzwungen

## 7. Datenschutz / DSGVO (Privacy API)

- [x] Privacy API ist implementiert — `classes/privacy/provider.php`
- [x] Bei personenbezogenen Daten: `metadata\provider` beschreibt alle Daten — DB-Tabelle + `add_external_location_link('oneclick_provisioner', …)`
- [x] Datenexport implementiert — `get_users_in_context`, `get_contexts_for_userid`, `export_user_data`
- [x] Datenlöschung implementiert — `delete_data_for_user`, `delete_data_for_all_users_in_context`, `delete_data_for_users`
- [x] Ohne personenbezogene Daten: `null_provider` — n. z., es werden personenbezogene Daten gespeichert
- [ ] Privacy-Unit-Tests laufen erfolgreich — **fehlen** (kein `tests/privacy/provider_test.php`)
- [x] Externe Datenübermittlungen sind dokumentiert — User-ID, E-Mail, IP, Host an den oneclick-provisioner; in der Privacy-Metadata erfasst (Einwilligung wird separat über das Trial-Consent-Modal abgebildet)

## 8. Sprache & Internationalisierung (i18n)

- [x] Alle Strings in `lang/en/bookingextension_oneclick.php`
- [~] Keine fest codierten, sichtbaren Texte — **nutzer-sichtbare** UI-Texte gehen über `get_string` (en+de). Englisch hartcodiert sind die **LLM-/Planner-seitigen** Schema-/Property-/Trigger-Beschreibungen und `build_observation()`-Zeilen in den Skills sowie die JS-Fallback-Strings (nur im `get_strings`-Fehlerfall) — alle nicht Teil der regulären UI
- [x] Ausgaben über `get_string()` bzw. `lang_string`
- [x] Platzhalter (`{$a}`) statt Konkatenation — z. B. `clarify_template_unknown`
- [x] Pflichtstring `pluginname` vorhanden — en + de
- [x] Mehrsprachigkeit — vollständige `lang/de`; Standardausgabe filterfähig
- [ ] Optional: Übersetzung über AMOS einreichbar / vorbereitet — nicht vorbereitet

## 9. Barrierefreiheit (Accessibility)

- [ ] Erfüllt WCAG 2.1 Level AA — nicht formal geprüft
- [x] Vollständige Tastaturbedienbarkeit — nur Standard-Links/-Buttons, keine reinen Maus-Aktionen
- [x] Sinnvolle ARIA-Rollen / -Labels — Spinner mit `role="status"` + `sr-only`/`visually-hidden`-Text
- [ ] Ausreichender Farbkontrast — nicht geprüft (verwendet Bootstrap-Utility-Klassen)
- [x] Bilder/Icons mit `alt` bzw. dekorativ markiert — n. z., keine inhaltlichen Bilder (nur Spinner mit sr-only-Text)
- [ ] Mit Screenreader getestet — nein
- [x] Formularfelder mit Labels verknüpft — Admin-Settings über Moodle-Form-API

## 10. UI / UX & Templates

- [~] Oberfläche nutzt Mustache-Templates statt HTML im PHP-Code — kein HTML im PHP; das Live-Preview-Markup wird jedoch im AMD-Modul (`spawn_preview.js`) per String zusammengebaut, nicht über ein Mustache-Template
- [ ] `renderer.php` / Output-API verwendet — nicht verwendet (keine eigene Seite; Darstellung erfolgt im Side-Preview der Agent-Engine)
- [x] Responsive Darstellung — Bootstrap-`card`/Utility-Klassen
- [x] Kompatibel mit Boost (und Classic) — nur Standard-Klassen
- [x] Folgt den Moodle-UI-Konventionen — `btn`, `spinner-border`, `card`, Notifications
- [x] Keine Inline-Styles / kein themeüberschreibendes CSS — nur Utility-Klassen, kein `style=""`
- [x] JavaScript als AMD-Modul — `amd/src/spawn_preview.js` → `amd/build/` gebaut

## 11. Automatisierte Tests

- [x] PHPUnit-Tests für die Kernlogik vorhanden — 5 Klassen / 43 Tests (Naming, Repository, Settings, beide Skills)
- [ ] Behat-Tests für zentrale Workflows — **fehlen** (kein `tests/behat/`)
- [ ] Tests laufen lokal und in CI ohne Fehler — **2 Failures in `settings_helper_test`**: `test_defaults_when_unset` und `test_is_configured_requires_secret_and_templates`. Ursache: `settings.php` liefert eine Default-Vorlage `sport1`, sodass `get_templates()` „unset" nicht leer ist und `is_configured()` schon mit gesetztem Secret `true` zurückgibt — Test-Erwartung und Default widersprechen sich (zu klären, welche Seite korrigiert wird). Übrige 41 Tests grün
- [x] Sinnvolle Code-Coverage der Geschäftslogik — Skills, Client-Fakes, Naming, Repository, Settings abgedeckt; Edge Cases enthalten
- [x] Generators für Testdaten — n. z.
- [ ] Privacy-Provider-Tests enthalten — **fehlen**

## 12. Backup, Restore & Reset

- [x] Backup-Logik unter `backup/moodle2/` — n. z.: kein Aktivitätsmodul; gespeichert werden nutzerbezogene Provisioning-Jobs, keine sicherbaren Kursinhalte
- [x] Restore-Logik — n. z. (siehe oben)
- [x] Dateien, Bewertungen, Benutzerdaten übertragen — n. z.
- [x] „Kurs zurücksetzen" (Reset) — n. z.
- [x] Backup/Restore versionsübergreifend — n. z.

## 13. Events, Logging & Bewertungen

- [ ] Relevante Aktionen lösen Events über die Event API aus — **keine Events** definiert; create/delete einer Instanz (R3-Aktionen) lösen keine Moodle-Events aus (empfehlenswert für Audit/Nachvollziehbarkeit, optional)
- [ ] Events erscheinen korrekt im Protokoll — n. z. (keine Events)
- [x] Gradebook-Integration — n. z. (keine Bewertungen)
- [x] Geplante Aufgaben über die Task API statt alter Cron-Funktion — n. z.: keine geplanten Tasks; keine veraltete `cron`-Funktion verwendet
- [x] Kalender-/Completion-Integration — n. z.

## 14. Funktions- & Integrationstests

- [x] Saubere Neuinstallation funktioniert fehlerfrei — PHPUnit-Init installiert das Schema sauber; Lint/Codechecker ok (UI-Klickpfad nicht manuell verifiziert)
- [ ] Upgrade von der Vorversion funktioniert fehlerfrei — nicht getestet (kein `upgrade.php`)
- [x] Vollständige Deinstallation hinterlässt keine Datenreste — Tabelle via `install.xml`, personenbezogene Daten via Privacy-API
- [x] Alle Kernfunktionen verhalten sich wie spezifiziert — über Unit-Tests belegt (Spawn/Execute, Delete, Status-Poll, Naming)
- [x] Edge Cases getestet — leere/Unicode-/überlange Namen, unbekanntes Template, mehrere Instanzen, Terminal-Status, Transport-Fehler
- [~] Mehrere gleichzeitige Nutzer / Rollen geprüft — Ownership-Scoping getestet (`get_owned` user-gebunden, `X-Requester-User-Id`); volle Rollenmatrix nicht manuell durchgespielt
- [x] Fehlermeldungen verständlich und nutzerfreundlich — lokalisiert, HTTP-Status sauber gemappt

## 15. Kompatibilität

- [~] Getestet auf allen angegebenen Moodle-Versionen — `$plugin->supported = [405, 501]`; hier nur auf 5.1 verifiziert, 4.5 nicht
- [~] Getestet auf allen unterstützten PHP-Versionen — nur PHP 8.3
- [ ] Funktioniert mit den unterstützten Datenbank-Engines — nur MariaDB getestet
- [ ] In aktuellen Browsern getestet — nicht durchgeführt
- [x] Funktioniert in der Moodle Mobile App — n. z. (kein `db/mobile.php`)
- [x] Verträgt sich mit gängigen anderen Plugins — saubere Abhängigkeit zu `mod_booking`/`bookingextension_agent`, keine Konflikte bekannt

## 16. Performance & Skalierung

- [x] Caching über die MUC — n. z.: keine schweren Wiederholabfragen; Status wird stattdessen in der eigenen Tabelle zwischengespeichert
- [x] Keine N+1-Datenbankabfragen / Abfragen in Schleifen — nur Einzelabfragen
- [x] Effiziente Abfragen mit passenden Indizes — `jobid`/`userid` indiziert
- [ ] Last-/Mengentest mit realistischer Datenmenge — nicht durchgeführt
- [x] Keine spürbaren Performance-Einbrüche auf Standard-Seiten — nur Settings-Tree + clientseitiges Polling (10 s)

## 17. Web Services / Externe APIs *(falls zutreffend)*

- [x] Externe Funktionen definiert — `db/services.php` → `bookingextension_oneclick\external\get_job_status`
- [x] Strenge Parameter- und Rückgabe-Validierung — `external_function_parameters` + `external_single_structure`
- [x] Berechtigungen je Web-Service-Funktion geprüft — `capabilities` in `services.php` + `require_capability` in `execute()`
- [x] Web-Service-Funktionen dokumentiert — `description`-Feld + ausführliche PHPDoc

## 18. Dokumentation

- [ ] `README.md` — **fehlt**
- [ ] Installationsanleitung — **fehlt**
- [ ] `CHANGELOG.md` — **fehlt**
- [~] Konfigurations- und Nutzungshinweise — ausführliche Settings-Hilfetexte vorhanden; keine separate Doku (dieses Testprotokoll liegt unter `docs/`)
- [ ] Bekannte Einschränkungen / Roadmap — nicht dokumentiert
- [~] Kontakt- / Support-Information — nur über Copyright-Header („Wunderbyte GmbH <info@wunderbyte.at>")

## 19. CI/CD & Build

- [ ] `moodle-plugin-ci` eingerichtet — keine plugin-eigene Konfiguration gefunden
- [ ] CI-Pipeline (GitHub/GitLab) prüft Linting/Codechecker/PHPUnit/Behat — keine `.github/`/`.gitlab-ci.yml` im Plugin-Repo
- [ ] Build der AMD-/CSS-Assets reproduzierbar dokumentiert — Build (`amd/build/`) vorhanden, Bauweg (Moodle-Standard `grunt amd`) nicht dokumentiert
- [ ] Pipeline läuft auf der Versionsmatrix grün — n. z. (keine Pipeline)

## 20. Veröffentlichung im Plugin Directory *(falls geplant)*

- [ ] Erfüllt die Anforderungen des Moodle Plugin Directory — noch nicht (offene Doku/Tests)
- [~] Öffentliches Quellcode-Repository verlinkt — eigenes Git-Repo (`origin/main`) vorhanden, Sichtbarkeit/Verlinkung offen
- [ ] Aussagekräftige Beschreibung, Screenshots und Logo vorbereitet — nicht vorbereitet
- [x] Unterstützte Moodle-Versionen klar angegeben — `$plugin->supported = [405, 501]`
- [ ] Plugin durchläuft den „Prechecks"-Report ohne kritische Befunde — nicht durchlaufen

---

## Abschluss-Bewertung

- [~] Alle kritischen Punkte (Sicherheit, Datenschutz, Datenbank-Upgrade) bestanden — Sicherheit solide; Datenschutz implementiert, aber **Privacy-Tests fehlen**; **DB-Upgrade-Pfad ungetestet/kein `upgrade.php`**
- [ ] Keine offenen Blocker — **Blocker:** 2 fehlschlagende Unit-Tests (`settings_helper_test`), fehlende README/CHANGELOG, fehlendes `$plugin->maturity`, fehlende Privacy- und Behat-Tests
- [ ] Freigabe zum Release erteilt — noch nicht

**Gesamtergebnis:** ☐ Bestanden ☑ Mit Auflagen ☐ Nicht bestanden

**Bemerkungen:** Code-Qualität und Architektur sind gut (saubere Trennung Client/Repository/Skill/Privacy, durchgängige PHPDoc, lokalisierte UI in en+de, sichere serverseitige API-Anbindung). Vor dem Release abzuarbeiten:
1. **`settings_helper_test` reparieren** — Konflikt zwischen Default-Vorlage `sport1` (settings.php) und Test-Erwartung „keine Default-Templates" auflösen.
2. **`$plugin->maturity`** in `version.php` setzen.
3. **README.md + CHANGELOG.md** ergänzen, AMD-Buildweg dokumentieren.
4. **Privacy-Provider-Tests** ergänzen; optional **Behat**-Workflows.
5. Codechecker-Befunde in `tests/delete_instance_skill_test.php` bereinigen (Z. 94/98).
6. `upgrade.php` für künftige Schemaänderungen vorsehen; auf PostgreSQL/4.5/PHP-Matrix gegenprüfen.
7. SSRF-Guard-Bypass (`ignoresecurity`) im Sicherheits-Review explizit bestätigen.

**Freigabe durch / Datum:** _________________________ (Automatisierter Prüflauf: 2026-06-18)
