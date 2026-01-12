# Valeurs calculées après le parsing

Après le parsing complet du fichier IGC, la méthode `calculateDerivedValues()` calcule automatiquement plusieurs valeurs dérivées et les ajoute à l'objet `flight`.

## Valeurs calculées dans `OtherInformation`

### Temps et durée

#### `totalTimeFormatted` (string)
- **Format** : `hh:mm:ss`
- **Description** : Conversion du temps total de vol en format lisible
- **Exemple** : `"02:35:42"` pour 9332 secondes
- **Source** : `totalTime` (secondes)

#### `totalTimeHours` (float)
- **Format** : Nombre décimal d'heures
- **Description** : Temps total converti en heures
- **Exemple** : `2.59` pour 9332 secondes
- **Source** : `totalTime` (secondes)

#### `flightDuration` (int)
- **Format** : Secondes
- **Description** : Durée totale du vol (du premier au dernier point GPS)
- **Exemple** : `9332`
- **Source** : Différence entre le premier et dernier timestamp

#### `flightDurationFormatted` (string)
- **Format** : `hh:mm:ss`
- **Description** : Durée du vol formatée
- **Exemple** : `"02:35:42"`
- **Source** : `flightDuration`

#### `flightStart` (string)
- **Format** : `YYYY-MM-DD HH:MM:SS`
- **Description** : Date et heure du premier point GPS
- **Exemple** : `"2022-08-05 09:09:32"`
- **Source** : Premier point dans `Fix[]`

#### `flightEnd` (string)
- **Format** : `YYYY-MM-DD HH:MM:SS`
- **Description** : Date et heure du dernier point GPS
- **Exemple** : `"2022-08-05 11:45:14"`
- **Source** : Dernier point dans `Fix[]`

### Distances

#### `totalDistanceKm` (float)
- **Format** : Kilomètres (2 décimales)
- **Description** : Distance totale convertie en kilomètres
- **Exemple** : `125.43`
- **Source** : `totalDistance` (mètres)

#### `totalDistanceFormatted` (string)
- **Format** : `"XXX.XX km"`
- **Description** : Distance totale formatée avec unité
- **Exemple** : `"125.43 km"`
- **Source** : `totalDistanceKm`

### Vitesses

#### `averageSpeed` (float)
- **Format** : km/h (2 décimales)
- **Description** : Vitesse moyenne sur tout le vol
- **Calcul** : `(totalDistance / totalTime) * 3.6`
- **Exemple** : `48.25`
- **Source** : `totalDistance` et `totalTime`

#### `maxSpeedFormatted` (string)
- **Format** : `"XXX.XX km/h"`
- **Description** : Vitesse maximale formatée avec unité
- **Exemple** : `"125.50 km/h"`
- **Source** : `maxSpeed` (km/h)

### Altitudes barométriques QNH (absolues)

#### `minAltitude` (int)
- **Format** : Mètres
- **Description** : Altitude barométrique QNH minimale du vol (absolue, ramenée au niveau de la mer)
- **Exemple** : `125`
- **Source** : Minimum de tous les `pressureAltitude` (QNH) dans `Fix[]`

#### `maxAltitude` (int)
- **Format** : Mètres
- **Description** : Altitude barométrique QNH maximale du vol (absolue, ramenée au niveau de la mer)
- **Exemple** : `1850`
- **Source** : Maximum de tous les `pressureAltitude` (QNH) dans `Fix[]`

#### `altitudeRange` (int)
- **Format** : Mètres
- **Description** : Écart entre l'altitude QNH max et min
- **Calcul** : `maxAltitude - minAltitude`
- **Exemple** : `1725`
- **Source** : Calculé depuis `minAltitude` et `maxAltitude`

#### `minQnh` (int)
- **Format** : Mètres
- **Description** : Alias pour `minAltitude` (altitude barométrique QNH minimale)
- **Exemple** : `125`
- **Source** : Identique à `minAltitude`

#### `maxQnh` (int)
- **Format** : Mètres
- **Description** : Alias pour `maxAltitude` (altitude barométrique QNH maximale)
- **Exemple** : `1850`
- **Source** : Identique à `maxAltitude`

#### `qnhRange` (int)
- **Format** : Mètres
- **Description** : Alias pour `altitudeRange` (écart d'altitude QNH)
- **Exemple** : `1725`
- **Source** : Identique à `altitudeRange`

#### `takeoffQnh` (int)
- **Format** : Mètres
- **Description** : Altitude QNH au décollage (premier point GPS, absolue)
- **Exemple** : `125`
- **Source** : Premier point dans `Fix[]`

#### `takeoffAltitude` (int)
- **Format** : Mètres
- **Description** : Alias pour `takeoffQnh` (altitude de décollage QNH)
- **Exemple** : `125`
- **Source** : Identique à `takeoffQnh`

### Altitudes barométriques QFE (relatives au décollage)

#### `minQfe` (int)
- **Format** : Mètres
- **Description** : Altitude QFE minimale (relative au décollage)
- **Exemple** : `-50` (50 mètres sous le niveau de décollage)
- **Source** : Minimum de tous les `qfe` dans `Fix[]`

#### `maxQfe` (int)
- **Format** : Mètres
- **Description** : Altitude QFE maximale (relative au décollage)
- **Exemple** : `1725` (1725 mètres au-dessus du niveau de décollage)
- **Source** : Maximum de tous les `qfe` dans `Fix[]`

#### `qfeRange` (int)
- **Format** : Mètres
- **Description** : Écart entre l'altitude QFE max et min
- **Calcul** : `maxQfe - minQfe`
- **Exemple** : `1775`
- **Source** : Calculé depuis `minQfe` et `maxQfe`

#### `minAltitudeRelative` (int)
- **Format** : Mètres
- **Description** : Alias pour `minQfe`
- **Exemple** : `-50`
- **Source** : Identique à `minQfe`

#### `maxAltitudeRelative` (int)
- **Format** : Mètres
- **Description** : Alias pour `maxQfe`
- **Exemple** : `1725`
- **Source** : Identique à `maxQfe`

#### `altitudeRelativeRange` (int)
- **Format** : Mètres
- **Description** : Alias pour `qfeRange`
- **Exemple** : `1775`
- **Source** : Identique à `qfeRange`

### Altitudes GPS

#### `minGnssAltitude` (int)
- **Format** : Mètres
- **Description** : Altitude GPS minimale du vol
- **Exemple** : `130`
- **Source** : Minimum de tous les `gnssAltitude` dans `Fix[]`

#### `maxGnssAltitude` (int)
- **Format** : Mètres
- **Description** : Altitude GPS maximale du vol
- **Exemple** : `1875`
- **Source** : Maximum de tous les `gnssAltitude` dans `Fix[]`

#### `gnssAltitudeRange` (int)
- **Format** : Mètres
- **Description** : Écart entre l'altitude GPS max et min
- **Calcul** : `maxGnssAltitude - minGnssAltitude`
- **Exemple** : `1745`
- **Source** : Calculé depuis `minGnssAltitude` et `maxGnssAltitude`

## Exemple d'utilisation

```php
use Ycdev\PhpIgcInspector\PhpIgcInspector;

$igc = PhpIgcInspector::fromFile('vol.igc');
$igc->validate();

$flight = $igc->getFlight();

// Accéder aux valeurs calculées
echo "Durée du vol : " . $flight->OtherInformation->flightDurationFormatted . "\n";
echo "Distance totale : " . $flight->OtherInformation->totalDistanceFormatted . "\n";
echo "Vitesse moyenne : " . $flight->OtherInformation->averageSpeed . " km/h\n";
echo "Vitesse max : " . $flight->OtherInformation->maxSpeedFormatted . "\n";
echo "Altitude QNH min : " . $flight->OtherInformation->minAltitude . " m\n";
echo "Altitude QNH max : " . $flight->OtherInformation->maxAltitude . " m\n";
echo "Plage d'altitude QNH : " . $flight->OtherInformation->altitudeRange . " m\n";
echo "Altitude de décollage (QNH) : " . $flight->OtherInformation->takeoffQnh . " m\n";
echo "Altitude QFE min : " . $flight->OtherInformation->minQfe . " m\n";
echo "Altitude QFE max : " . $flight->OtherInformation->maxQfe . " m\n";
echo "Plage d'altitude QFE : " . $flight->OtherInformation->qfeRange . " m\n";
echo "Altitude GPS min : " . $flight->OtherInformation->minGnssAltitude . " m\n";
echo "Altitude GPS max : " . $flight->OtherInformation->maxGnssAltitude . " m\n";

// Accéder aux altitudes d'un point GPS
$firstFix = $flight->Fix[0];
echo "Altitude QNH du premier point : " . $firstFix->qnh . " m (absolue)\n";
echo "Altitude QFE du premier point : " . $firstFix->qfe . " m (toujours 0, relatif au décollage)\n";

// Pour un point en vol
$fixEnVol = $flight->Fix[100];
echo "Altitude QNH : " . $fixEnVol->qnh . " m (absolue)\n";
echo "Altitude QFE : " . $fixEnVol->qfe . " m (relative au décollage)\n";
```

## Structure JSON exemple

```json
{
  "OtherInformation": {
    "date": "2022-08-05",
    "pilot": "Mike Young",
    "totalTime": 9332,
    "totalTimeFormatted": "02:35:42",
    "totalTimeHours": 2.59,
    "totalDistance": 125430,
    "totalDistanceKm": 125.43,
    "totalDistanceFormatted": "125.43 km",
    "maxSpeed": 125.50,
    "maxSpeedFormatted": "125.50 km/h",
    "averageSpeed": 48.25,
    "minAltitude": 125,
    "maxAltitude": 1850,
    "altitudeRange": 1725,
    "minQnh": 125,
    "maxQnh": 1850,
    "qnhRange": 1725,
    "takeoffQnh": 125,
    "takeoffAltitude": 125,
    "minQfe": 0,
    "maxQfe": 1725,
    "qfeRange": 1725,
    "minAltitudeRelative": 0,
    "maxAltitudeRelative": 1725,
    "altitudeRelativeRange": 1725,
    "minGnssAltitude": 130,
    "maxGnssAltitude": 1875,
    "gnssAltitudeRange": 1745,
    "flightDuration": 9332,
    "flightDurationFormatted": "02:35:42",
    "flightStart": "2022-08-05 09:09:32",
    "flightEnd": "2022-08-05 11:45:14",
    "fixRecordCount": 1245
  },
  "Fix": [
    {
      "time": "090932",
      "latitude": 51.1883,
      "longitude": -1.0285,
      "pressureAltitude": 96,
      "barometricAltitude": 96,
      "qnh": 96,
      "qfe": 0,
      "altitudeRelative": 0,
      "gnssAltitude": 229,
      "timestamp": 1659692972,
      "dateTime": "2022-08-05 09:09:32"
    }
  ]
}
```

## Altitudes barométriques dans les points GPS

Chaque point GPS (`Fix[]`) contient les altitudes barométriques avec plusieurs alias pour faciliter l'accès :

### QNH (altitude absolue)
- `pressureAltitude` : Altitude barométrique QNH absolue en mètres (champ principal, ramenée au niveau de la mer)
- `barometricAltitude` : Alias pour `pressureAltitude`
- `qnh` : Alias pour `pressureAltitude` (notation QNH standard)

### QFE (altitude relative au décollage)
- `qfe` : Altitude QFE relative au décollage (en mètres, peut être négative)
- `altitudeRelative` : Alias pour `qfe`

**Exemple d'utilisation :**
```php
foreach ($flight->Fix as $fix) {
    // Altitude QNH absolue (3 façons équivalentes)
    $qnh1 = $fix->pressureAltitude;  // Ex: 125 m (absolue)
    $qnh2 = $fix->barometricAltitude; // Alias
    $qnh3 = $fix->qnh;                // Alias
    
    // Altitude QFE relative au décollage (2 façons équivalentes)
    $qfe1 = $fix->qfe;                 // Ex: 0 (décollage) ou 1500 (1500m au-dessus)
    $qfe2 = $fix->altitudeRelative;    // Alias
}
```

**Terminologie aéronautique :**
- **QNH** : Pression ramenée au niveau de la mer (altitude absolue)
- **QFE** : Pression au niveau de l'aérodrome (altitude relative au décollage)

**Note importante** : L'altitude QFE est calculée en soustrayant l'altitude QNH du premier point (décollage) de chaque point. Ainsi :
- Le premier point aura toujours `qfe = 0`
- Les points au-dessus du décollage auront une valeur positive
- Les points en dessous du décollage auront une valeur négative

**Note** : Dans le format IGC, le champ `PPPPP` peut représenter soit :
- L'altitude barométrique directement en mètres (cas le plus courant)
- La pression barométrique en hPa avec décimale implicite (certains anciens enregistreurs)

La librairie traite ce champ comme une altitude en mètres, ce qui correspond à la majorité des fichiers IGC modernes.

## Notes importantes

1. **Calcul automatique** : Toutes ces valeurs sont calculées automatiquement après le parsing complet
2. **Disponibilité** : Les valeurs ne sont disponibles que si les données sources existent
3. **Précision** : Les distances sont arrondies à 2 décimales, les vitesses également
4. **Format de temps** : Le format `hh:mm:ss` utilise toujours 2 chiffres pour chaque composant (ex: `02:05:03`)
5. **Altitudes** : Les altitudes min/max sont calculées uniquement si des points GPS valides existent
6. **Altitudes barométriques** : 
   - **QNH** : Altitude absolue (ramenée au niveau de la mer) - `pressureAltitude`, `barometricAltitude`, `qnh`
   - **QFE** : Altitude relative au décollage - `qfe`, `altitudeRelative` (calculée automatiquement)

## Extensions possibles

D'autres valeurs pourraient être ajoutées facilement :
- Gain/perte d'altitude totale
- Nombre de thermiques détectés
- Temps de vol effectif (hors arrêts)
- Statistiques par segments
- etc.

Pour ajouter de nouvelles valeurs calculées, modifier la méthode `calculateDerivedValues()` dans `PhpIgcInspector.php`.
