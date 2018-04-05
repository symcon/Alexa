# Alexa

Dieses Modul ermöglicht die Sprachsteuerung von IP-Symcon durch Amazon Alexa.

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Schalten und Auslesen von Geräten durch Alexa:
  * Lichter (Schalter, Dimmer und Farbe)
  * Schlösser
  * Temperatursensoren
  * Thermostate
* Szenen mit Alexa schalten

### 2. Voraussetzungen

- IP-Symcon ab Version 5.x
- Aktivierte Verbindung zum Connect Dienst

### 3. Software-Installation

- Falls das Modul IQL4SmartHome installliert ist, müssen die entsprechenden Instanzen und das Modul gelöscht werden. Existierende Einstellungen gehen dabei verloren!
- Über das Modul-Control folgende URL hinzufügen: `https://github.com/symcon/Alexa.git`

### 4. Einrichten der Instanzen in IP-Symcon

Da sich der Skill momentan in der Beta-Phase befindet, muss man sich für die Verwendung des Beta-Skills anmelden. Mehr Information dazu sind hier zu finden: https://www.symcon.de/forum/threads/36948-Amazon-Echo-%28Alexa%29-mit-IP-Symcon-verbinden-%28V3-Beta%29

Mit dem aktuellen nicht-Beta-Skill kann das Modul nicht verwendet werden.

- Unter "Instanz hinzufügen" ist das 'Alexa'-Modul unter dem Hersteller 'Amazon' aufgeführt.
- Geräte und Szenen über die Konfigurationsseite einrichten
- Mit der Alexa App oder auf alexa.amazon.de den Symcon-Skill installieren und verbinden
- Mit Alexa nach neuen Geräten suchen

__Konfigurationsseite__:

Die Konfigurationsseite enthält für jeden Gerätetyp eine Liste, in welche die zu schaltenden Geräte eingepflegt werden können.

___Spalten für alle Listen___

Spalte | Beschreibung
------ | --------------------
ID     | ID des Gerätes bei Alexa - diese ist über alle Geräte und Szenen unterschiedlich und kann nicht bearbeitet werden
Name   | Der Name des Gerätes  - dies ist der Name mit dem das Gerät bzw. die Szene über Alexa geschaltet werden kann
Status | Gibt an, ob das Gerät bzw. die Szene korrekt eingerichtet ist - "OK" bestätigt, dass das Element so benutzt werden kann, ansonsten befindet sich in dieser Spalte eine Fehlermeldung

___Spalten für Light(Switch)___

Spalte   | Beschreibung
-------- | ---------------------------------
Variable | Die Variable, die das Licht beschreibt - Hierbei muss es sich um eine Boolean Variable mit Aktion handeln

___Spalten für Light(Dimmer)___

Spalte   | Beschreibung
-------- | ---------------------------------
Variable | Die Variable, die das dimmbare Licht beschreibt - Hierbei muss es sich um eine Float oder Integer Variable mit Aktion handeln

___Spalten für Light(Color)___

Spalte   | Beschreibung
-------- | ---------------------------------
Variable | Die Variable, die das farbige Licht beschreibt - Hierbei muss es sich um eine Integer Variable mit Aktion handeln, es sollte das Profil ~HexColor verwendet werden

___Spalten für Lock___

Spalte   | Beschreibung
-------- | ---------------------------------
Variable | Die Variable, die das Schloss beschreibt - Hierbei muss es sich um eine Boolean Variable mit Aktion handeln

___Spalten für Temperature Sensor___

Spalte         | Beschreibung
-------------- | ---------------------------------
SensorVariable | Die Variable, die die detektierte Temperatur enthält - Hierbei muss es sich um eine Float Variable handeln

___Spalten für Thermostat___

Spalte   | Beschreibung
-------- | ---------------------------------
Variable | Die Variable, die die anvisierte Temperatur enthält - Hierbei muss es sich um eine Float Variable mit Aktion handeln

___Spalten für Scenes___

Spalte   | Beschreibung
-------- | ---------------------------------
Script   | Das Skript, welches bei aktivieren der Szene ausgeführt werden soll

Nachdem Änderungen vorgenommen wurden, muss Alexa erneut nach Geräten suchen, damit diese übernommen werden.

Schaltbare Statusvariablen von Instanzen verfügen bereits über native Aktionen und können direkt verwendet werden. Sollen andere Variablen über Alexa geschaltet werden, so muss hierfür ein Aktionsskript angelegt werden. Mehr Informationen dazu sind hier zu finden: https://www.symcon.de/service/dokumentation/konzepte/skripte/aktionsskripte/ 

### 5. Statusvariablen und Profile

Es werden keine Statusvariablen und Profile angelegt

### 6. WebFront

Über das WebFront können keine Einstellungen vorgenommen werden.

### 7. PHP-Befehlsreferenz

Das Modul stellt keine weiteren PHP-Befehle zur Verfügung.
