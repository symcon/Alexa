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

Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/symcon/Alexa.git`  

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'Alexa'-Modul unter dem Hersteller 'Amazon' aufgeführt.  

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
Variable | Die Variable, die das Licht beschreibt - Hierbei muss es sich um eine Boolean Variable mit Aktionsskript handeln

___Spalten für Light(Dimmer)___

Spalte   | Beschreibung
-------- | ---------------------------------
Variable | Die Variable, die das dimmbare Licht beschreibt - Hierbei muss es sich um eine Float oder Integer Variable mit Aktionsskript handeln

___Spalten für Light(Color)___

Spalte   | Beschreibung
-------- | ---------------------------------
Variable | Die Variable, die das farbige Licht beschreibt - Hierbei muss es sich um eine Boolean Variable mit Aktionsskript handeln

___Spalten für Lock___

Spalte   | Beschreibung
-------- | ---------------------------------
Variable | Die Variable, die das Schloss beschreibt - Hierbei muss es sich um eine Boolean Variable mit Aktionsskript handeln

___Spalten für Temperature Sensor___

Spalte         | Beschreibung
-------------- | ---------------------------------
SensorVariable | Die Variable, die die detektierte Temperatur enthält - Hierbei muss es sich um eine Float Variable handeln

___Spalten für Thermostat___

Spalte   | Beschreibung
-------- | ---------------------------------
Variable | Die Variable, die die anvisierte Temperatur enthält - Hierbei muss es sich um eine Float Variable mit Aktionsskript handeln

___Spalten für Scenes___

Spalte   | Beschreibung
-------- | ---------------------------------
Script   | Das Skript, welches bei aktivieren der Szene ausgeführt werden soll


### 5. Statusvariablen und Profile

Es werden keine Statusvariablen und Profile angelegt

### 6. WebFront

Über das WebFront können keine Einstellungen vorgenommen werden.

### 7. PHP-Befehlsreferenz

Das Modul stellt keine weiteren PHP-Befehle zur Verfügung.
