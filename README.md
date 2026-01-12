# phpIgcInspector

Bibliothèque PHP pour lire et manipuler les fichiers IGC (International Gliding Commission).

## Installation

```bash
composer require ycdev/php-igc-inspector
```

## Utilisation de base

```php
use Ycdev\PhpIgcInspector\PhpIgcInspector;

// Créer une instance depuis un fichier
$inspector = PhpIgcInspector::fromFile('vol.igc');

// Valider et parser le fichier
$inspector->validate();

// Récupérer les données parsées
$flight = $inspector->getFlight();

// Exporter en JSON
$json = $inspector->toJson();
```

## Documentation

Consultez le dossier `doc/` pour les spécifications du format IGC.

## Structure

```
phpIgcInspector/
├── src/
│   ├── PhpIgcInspector.php          # Classe principale
│   ├── PhpIgcUtils.php              # Fonctions utilitaires
│   ├── Exception/
│   │   └── InvalidIgcException.php  # Exception personnalisée
│   ├── Data/
│   │   ├── EventTypeCode.php        # Codes d'événements
│   │   └── ManufacturerCodesData.php # Codes fabricants IGC
│   └── RecordTypes/                 # Types d'enregistrements IGC
└── doc/                              # Documentation et spécifications IGC
```

## API - Classe PhpIgcInspector

### Constructeur

```php
public function __construct(string $content, bool $withRaw = false)
```

Crée une instance avec le contenu brut du fichier IGC.

**Paramètres :**
- `$content` : Contenu du fichier IGC
- `$withRaw` : Si `true`, ajoute le champ 'raw' dans les données parsées

**Exemple :**
```php
$content = file_get_contents('vol.igc');
$inspector = new PhpIgcInspector($content);
```

### Méthodes statiques

#### `fromFile()`

```php
public static function fromFile(string $filePath, bool $withRaw = false): self
```

Crée une instance depuis un fichier.

**Paramètres :**
- `$filePath` : Chemin vers le fichier IGC
- `$withRaw` : Si `true`, ajoute le champ 'raw' dans les données parsées

**Retour :** Instance de `PhpIgcInspector`

**Exemple :**
```php
$inspector = PhpIgcInspector::fromFile('vol.igc');
```

#### `rawExtractFromFile()`

```php
public static function rawExtractFromFile(string $igcFilePath, string $outputDirectory, ?string $prefix = null): array
```

Extrait chaque type d'enregistrement depuis un fichier IGC dans des fichiers séparés.

**Paramètres :**
- `$igcFilePath` : Chemin vers le fichier IGC source
- `$outputDirectory` : Répertoire de sortie pour les fichiers extraits
- `$prefix` : Préfixe pour les noms de fichiers (optionnel)

**Retour :** Tableau associatif avec le type d'enregistrement comme clé et le chemin du fichier comme valeur

**Exemple :**
```php
$files = PhpIgcInspector::rawExtractFromFile('vol.igc', './extracted', 'mon_vol');
// Retourne : ['A' => './extracted/mon_vol_record_A.igc', 'B' => './extracted/mon_vol_record_B.igc', ...]
```

### Méthodes d'instance

#### `validate()`

```php
public function validate(): bool
```

Valide et parse le contenu du fichier IGC.

**Retour :** `true` si le fichier est valide

**Lance :** `InvalidIgcException` si le fichier n'est pas valide

**Exemple :**
```php
try {
    $inspector->validate();
    echo "Fichier valide !";
} catch (InvalidIgcException $e) {
    echo "Erreur : " . $e->getMessage();
}
```

#### `getFlight()`

```php
public function getFlight(): ?object
```

Retourne l'objet flight parsé contenant toutes les données du fichier IGC.

**Retour :** Objet flight ou `null` si le fichier n'a pas été validé

**Exemple :**
```php
$flight = $inspector->getFlight();
echo "Nombre de points GPS : " . count($flight->Fix);
echo "Pilote : " . $flight->OtherInformation->pilot;
```

#### `getMetadata()`

```php
public function getMetadata(): ?object
```

Retourne uniquement les métadonnées du vol (records uniques uniquement, sans les tableaux de points GPS).

**Retour :** Objet contenant uniquement les métadonnées ou `null`

**Exemple :**
```php
$metadata = $inspector->getMetadata();
// Contient : OtherInformation, Task, Manufacturer, etc.
// Ne contient PAS : Fix (tableau de points GPS)
```

#### `toJson()`

```php
public function toJson(): ?string
```

Retourne l'objet flight au format JSON (avec formatage et caractères Unicode non échappés).

**Retour :** Représentation JSON de l'objet flight ou `null`

**Exemple :**
```php
$json = $inspector->toJson();
file_put_contents('flight.json', $json);
```

#### `stringify()`

```php
public function stringify(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): ?string
```

Convertit l'objet flight en JSON avec des options personnalisées.

**Paramètres :**
- `$flags` : Options pour `json_encode()` (défaut: `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE`)

**Retour :** Représentation JSON de l'objet flight ou `null`

**Exemple :**
```php
// JSON compact
$json = $inspector->stringify(JSON_UNESCAPED_UNICODE);

// JSON avec options personnalisées
$json = $inspector->stringify(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
```

#### `validTurnPoint()`

```php
public function validTurnPoint(float $proximityRadius = 5000.0): bool
```

Valide les turnpoints en vérifiant la proximité des points GPS avec les waypoints de la tâche.

**Paramètres :**
- `$proximityRadius` : Distance en mètres pour valider la proximité d'un turnpoint (défaut: 5000 m)

**Retour :** `true` si tous les turnpoints sont validés dans l'ordre

**Exemple :**
```php
if ($inspector->validTurnPoint(5000.0)) {
    echo "Tous les turnpoints sont validés !";
    $task = $inspector->getFlight()->Task;
    $validation = $task->turnPointValidation;
    echo "Points validés : " . $validation->validatedCount . " / " . $validation->totalWaypoints;
}
```

#### `rawExtract()`

```php
public function rawExtract(string $outputDirectory, ?string $prefix = null): array
```

Extrait chaque type d'enregistrement dans un fichier séparé.

**Paramètres :**
- `$outputDirectory` : Répertoire de sortie pour les fichiers extraits
- `$prefix` : Préfixe pour les noms de fichiers (optionnel)

**Retour :** Tableau associatif avec le type d'enregistrement comme clé et le chemin du fichier comme valeur

**Lance :** `\RuntimeException` si le répertoire ne peut pas être créé ou si l'écriture échoue

**Exemple :**
```php
$files = $inspector->rawExtract('./extracted', 'mon_vol');
foreach ($files as $type => $filepath) {
    echo "Type $type extrait dans : $filepath\n";
}
```

## API - Classe PhpIgcUtils

Classe utilitaire contenant des fonctions statiques pour les opérations courantes sur les fichiers IGC.

### Calcul de distances GPS

#### `calculateDistance()`

```php
public static function calculateDistance(
    float $latitude1,
    float $longitude1,
    float $latitude2,
    float $longitude2
): float
```

Calcule la distance entre deux points GPS en utilisant la formule de Haversine.

**Paramètres :**
- `$latitude1`, `$longitude1` : Coordonnées du premier point (degrés décimaux)
- `$latitude2`, `$longitude2` : Coordonnées du deuxième point (degrés décimaux)

**Retour :** Distance en mètres

**Exemple :**
```php
$distance = PhpIgcUtils::calculateDistance(51.1883, -1.0285, 51.2000, -1.0400);
echo "Distance : " . round($distance, 2) . " mètres";
```

#### `calculateProximity()`

```php
public static function calculateProximity(
    float $latitude1,
    float $longitude1,
    float $latitude2,
    float $longitude2
): float
```

Alias pour `calculateDistance()`.

### Conversions de coordonnées

#### `igcToDecimal()`

```php
public static function igcToDecimal(int $degrees, int $minutes, int $thousandths): float
```

Convertit des coordonnées IGC (DDMMmmm) en degrés décimaux.

**Paramètres :**
- `$degrees` : Degrés (DD ou DDD)
- `$minutes` : Minutes (MM)
- `$thousandths` : Millièmes de minute (mmm)

**Retour :** Coordonnée en degrés décimaux

**Exemple :**
```php
// Latitude : 51° 11.299' = 5111299
$latitude = PhpIgcUtils::igcToDecimal(51, 11, 299);
// Retourne : 51.1883
```

#### `decimalToIgc()`

```php
public static function decimalToIgc(float $decimalDegrees, bool $isLongitude = false): array
```

Convertit des degrés décimaux en format IGC (DDMMmmm).

**Paramètres :**
- `$decimalDegrees` : Coordonnée en degrés décimaux
- `$isLongitude` : `true` si c'est une longitude (DDDMMmmm), `false` pour latitude (DDMMmmm)

**Retour :** Tableau avec `degrees`, `minutes`, `thousandths`

**Exemple :**
```php
$coords = PhpIgcUtils::decimalToIgc(51.1883, false);
// Retourne : ['degrees' => 51, 'minutes' => 11, 'thousandths' => 299]
```

### Conversions de temps

#### `secondsToTime()`

```php
public static function secondsToTime(int $seconds): string
```

Convertit des secondes en format hh:mm:ss.

**Paramètres :**
- `$seconds` : Nombre de secondes

**Retour :** Temps formaté (hh:mm:ss)

**Exemple :**
```php
$time = PhpIgcUtils::secondsToTime(9332);
// Retourne : "02:35:32"
```

#### `timeToSeconds()`

```php
public static function timeToSeconds(string $timeString): int
```

Convertit un temps formaté (hh:mm:ss ou HHMMSS) en secondes.

**Paramètres :**
- `$timeString` : Temps au format hh:mm:ss ou HHMMSS

**Retour :** Nombre de secondes

**Exemple :**
```php
$seconds = PhpIgcUtils::timeToSeconds("02:35:32");
// Retourne : 9332

$seconds = PhpIgcUtils::timeToSeconds("023532");
// Retourne : 9332
```

### Formatage

#### `formatDistance()`

```php
public static function formatDistance(float $distance, int $decimals = 2): string
```

Formate une distance en mètres avec unité (m ou km).

**Paramètres :**
- `$distance` : Distance en mètres
- `$decimals` : Nombre de décimales (défaut: 2)

**Retour :** Distance formatée (ex: "125.43 km" ou "45.67 m")

**Exemple :**
```php
$formatted = PhpIgcUtils::formatDistance(125430);
// Retourne : "125.43 km"

$formatted = PhpIgcUtils::formatDistance(456.7);
// Retourne : "456.70 m"
```

#### `formatSpeed()`

```php
public static function formatSpeed(float $speed, int $decimals = 2): string
```

Formate une vitesse en km/h.

**Paramètres :**
- `$speed` : Vitesse en km/h
- `$decimals` : Nombre de décimales (défaut: 2)

**Retour :** Vitesse formatée (ex: "125.43 km/h")

**Exemple :**
```php
$formatted = PhpIgcUtils::formatSpeed(125.5);
// Retourne : "125.50 km/h"
```

### Calculs

#### `calculateSpeed()`

```php
public static function calculateSpeed(
    float $latitude1,
    float $longitude1,
    int $timestamp1,
    float $latitude2,
    float $longitude2,
    int $timestamp2
): ?float
```

Calcule la vitesse entre deux points GPS.

**Paramètres :**
- `$latitude1`, `$longitude1` : Coordonnées du premier point
- `$timestamp1` : Timestamp du premier point
- `$latitude2`, `$longitude2` : Coordonnées du deuxième point
- `$timestamp2` : Timestamp du deuxième point

**Retour :** Vitesse en km/h, ou `null` si le temps est invalide

**Exemple :**
```php
$speed = PhpIgcUtils::calculateSpeed(
    51.1883, -1.0285, 1659692972,
    51.2000, -1.0400, 1659693000
);
// Retourne la vitesse en km/h
```

#### `validatePointsProximity()`

```php
public static function validatePointsProximity(
    array $points,
    float $distance,
    string $igcContent,
    bool $includeIgcObject = false
): object
```

Valide une liste de points en vérifiant s'ils sont passés à proximité dans un fichier IGC.

**Paramètres :**
- `$points` : Liste de points à valider. Chaque point doit être un objet ou un tableau associatif contenant les clés `latitude` et `longitude` (en degrés décimaux)
- `$distance` : Distance maximale en mètres pour considérer qu'un point est validé
- `$igcContent` : Contenu du fichier IGC à analyser
- `$includeIgcObject` : Si `true`, inclut l'objet IGC parsé dans le résultat (défaut: `false`)

**Retour :** Objet contenant :
- `validatedPoints` : Tableau des points validés avec leurs détails
- `invalidatedPoints` : Tableau des points non validés
- `allValidated` : Booléen indiquant si tous les points sont validés
- `validatedCount` : Nombre de points validés
- `totalPoints` : Nombre total de points
- `distance` : Distance maximale utilisée pour la validation
- `igcObject` : Objet IGC parsé (uniquement si `$includeIgcObject` est `true`)

**Lance :** `\Exception` si le fichier IGC ne peut pas être parsé ou si les points sont invalides

**Exemple :**
```php
$points = [
    ['latitude' => 51.1883, 'longitude' => -1.0285],
    ['latitude' => 51.2000, 'longitude' => -1.0400],
];

$igcContent = file_get_contents('vol.igc');
$result = PhpIgcUtils::validatePointsProximity($points, 5000.0, $igcContent);

if ($result->allValidated) {
    echo "Tous les points sont validés !\n";
} else {
    echo "Points validés : " . $result->validatedCount . " / " . $result->totalPoints . "\n";
    foreach ($result->invalidatedPoints as $point) {
        echo "Point non validé : " . $point->reason . "\n";
    }
}

// Avec l'objet IGC inclus
$result = PhpIgcUtils::validatePointsProximity($points, 5000.0, $igcContent, true);
$flight = $result->igcObject; // Objet flight complet
```

## Exemples complets

### Exemple 1 : Lecture et validation d'un fichier IGC

```php
use Ycdev\PhpIgcInspector\PhpIgcInspector;
use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;

try {
    $inspector = PhpIgcInspector::fromFile('vol.igc');
    $inspector->validate();
    
    $flight = $inspector->getFlight();
    
    echo "Pilote : " . $flight->OtherInformation->pilot . "\n";
    echo "Date : " . $flight->OtherInformation->date . "\n";
    echo "Nombre de points GPS : " . count($flight->Fix) . "\n";
    echo "Distance totale : " . $flight->OtherInformation->totalDistanceFormatted . "\n";
    echo "Durée : " . $flight->OtherInformation->flightDurationFormatted . "\n";
    
} catch (InvalidIgcException $e) {
    echo "Erreur de validation : " . $e->getMessage() . "\n";
}
```

### Exemple 2 : Validation des turnpoints

```php
$inspector = PhpIgcInspector::fromFile('vol.igc');
$inspector->validate();

if ($inspector->validTurnPoint(5000.0)) {
    $task = $inspector->getFlight()->Task;
    $validation = $task->turnPointValidation;
    
    echo "Tous les turnpoints sont validés !\n";
    echo "Points validés : " . $validation->validatedCount . " / " . $validation->totalWaypoints . "\n";
    
    foreach ($validation->validatedTurnPoints as $turnPoint) {
        echo sprintf(
            "Turnpoint %d (%s) validé à %s (distance: %.2f m)\n",
            $turnPoint->index,
            $turnPoint->type,
            $turnPoint->validatedAt,
            $turnPoint->distance
        );
    }
} else {
    echo "Certains turnpoints n'ont pas été validés.\n";
    foreach ($validation->missedTurnPoints as $missed) {
        echo "Turnpoint manqué : " . $missed->type . " (index " . $missed->index . ")\n";
    }
}
```

### Exemple 3 : Calcul de distance entre deux points

```php
use Ycdev\PhpIgcInspector\PhpIgcUtils;

$distance = PhpIgcUtils::calculateDistance(
    51.1883,  // Latitude point 1
    -1.0285,  // Longitude point 1
    51.2000,  // Latitude point 2
    -1.0400   // Longitude point 2
);

echo "Distance : " . PhpIgcUtils::formatDistance($distance) . "\n";
```

### Exemple 4 : Validation de points personnalisés

```php
use Ycdev\PhpIgcInspector\PhpIgcUtils;

// Liste de points à valider (waypoints personnalisés)
$waypoints = [
    ['latitude' => 51.1883, 'longitude' => -1.0285, 'name' => 'Départ'],
    ['latitude' => 51.2000, 'longitude' => -1.0400, 'name' => 'Turnpoint 1'],
    ['latitude' => 51.2100, 'longitude' => -1.0500, 'name' => 'Arrivée'],
];

$igcContent = file_get_contents('vol.igc');
$result = PhpIgcUtils::validatePointsProximity($waypoints, 5000.0, $igcContent);

echo "Résultats de validation :\n";
echo "Points validés : " . $result->validatedCount . " / " . $result->totalPoints . "\n";
echo "Distance maximale : " . PhpIgcUtils::formatDistance($result->distance) . "\n";

foreach ($result->validatedPoints as $point) {
    echo sprintf(
        "✓ Point validé (distance: %.2f m) à %s\n",
        $point->minDistance,
        $point->validatedAt ?? 'N/A'
    );
}

foreach ($result->invalidatedPoints as $point) {
    echo sprintf(
        "✗ Point non validé : %s (distance min: %s)\n",
        $point->reason,
        $point->minDistance !== null ? PhpIgcUtils::formatDistance($point->minDistance) : 'N/A'
    );
}
```

## Développement

- **Namespace** : `Ycdev\PhpIgcInspector`
- **License** : GPL-3.0-only
- **Version PHP requise** : >= 7.4

## Auteur

Yann (yann@cymdev.com)
