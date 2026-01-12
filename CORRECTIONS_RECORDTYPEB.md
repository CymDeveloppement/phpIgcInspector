# Corrections des erreurs de calcul dans RecordTypeB

## Problèmes identifiés

### 🔴 ERREUR CRITIQUE #1 : Conversion incorrecte des coordonnées GPS

#### Problème

Le format IGC utilise un système de coordonnées spécifique où les **3 derniers chiffres représentent des millièmes de minute**, pas des secondes.

**Format IGC :**
- **Latitude** : `DDMMmmm` (7 caractères)
  - `DD` = degrés (2 chiffres)
  - `MM` = minutes (2 chiffres)
  - `mmm` = **millièmes de minute** (3 chiffres, donc mmm/1000 = minutes décimales)

- **Longitude** : `DDDMMmmm` (8 caractères)
  - `DDD` = degrés (3 chiffres)
  - `MM` = minutes (2 chiffres)
  - `mmm` = **millièmes de minute** (3 chiffres)

#### Code actuel (INCORRECT)

```php
// Ligne 59 - Latitude
$data['latitude'] = $this->degreeToDecimal(
    (int) substr($data['latitude'], 0, 2),  // DD = 51
    (int) substr($data['latitude'], 2, 2),    // MM = 11
    (int) substr($data['latitude'], 4, 3)   // mmm = 299 (traité comme secondes !)
);

// Ligne 64 - Longitude
$data['longitude'] = $this->degreeToDecimal(
    (int) substr($data['longitude'], 0, 3),  // DDD = 001
    (int) substr($data['longitude'], 3, 2),   // MM = 01
    (int) substr($data['longitude'], 5, 3)    // mmm = 710 (traité comme secondes !)
);

// Fonction degreeToDecimal (ligne 171-174)
private function degreeToDecimal($degree, $minutes, $seconds)
{
    return $degree + $minutes / 60 + $seconds / 3600;  // ❌ INCORRECT pour IGC
}
```

#### Exemple de calcul incorrect

Pour la latitude `5111299N` :
- Code actuel : `51 + 11/60 + 299/3600 = 51.2997°` ❌
- Résultat attendu : `51 + 11.299/60 = 51.1883°` ✅

**Erreur** : ~0.11° soit environ **12 km** de décalage !

#### Correction proposée

```php
/**
 * Convertit les coordonnées IGC (DDMMmmm) en degrés décimaux
 * 
 * @param int $degrees Degrés
 * @param int $minutes Minutes
 * @param int $thousandths Millièmes de minute (pas des secondes !)
 * @return float Degrés décimaux
 */
private function degreeToDecimal($degrees, $minutes, $thousandths)
{
    // Conversion correcte : mmm représente des millièmes de minute
    // Minutes décimales = MM + mmm/1000
    // Degrés décimaux = DD + (MM + mmm/1000) / 60
    $decimalMinutes = $minutes + ($thousandths / 1000);
    return $degrees + ($decimalMinutes / 60);
}
```

---

### 🟡 PROBLÈME #2 : Extraction des coordonnées depuis la ligne

#### Problème

Les regex d'extraction ne gèrent pas correctement les espaces dans le format IGC. Le format réel est :
```
B HHMMSS DDMMmmmN DDDMMmmmE A ...
```

Il y a des **espaces** entre les champs, mais les regex actuelles supposent que tout est collé.

#### Code actuel

```php
protected array $format = [
    ['time', '/^B(\d{6})/', '/^\d{6}$/'],
    ['latitude', '/^(\d{7})/', '/^\d{7}$/'],        // ❌ Ne gère pas les espaces
    ['latitudeNS', '/^([NS])/', '/^[NS]$/'],
    ['longitude', '/^(\d{8})/', '/^\d{8}$/'],        // ❌ Ne gère pas les espaces
    // ...
];
```

#### Analyse d'une ligne réelle

Ligne IGC : `B0909325111299N00101710WA000960022900700100000000041970000001920100-010-09`

- `B` = type
- `090932` = heure (09:09:32)
- `5111299` = latitude (7 chiffres)
- `N` = hémisphère Nord
- `00101710` = longitude (8 chiffres)
- `W` = hémisphère Ouest
- `A` = validité
- etc.

**Note** : Dans cet exemple, il n'y a pas d'espaces, mais selon la spécification IGC, les espaces sont optionnels. Le code actuel fonctionne si les espaces sont absents, mais pourrait échouer avec des fichiers formatés différemment.

#### Correction proposée

Les regex devraient être plus flexibles pour gérer les espaces optionnels :

```php
protected array $format = [
    ['time', '/^B\s*(\d{6})/', '/^\d{6}$/'],
    ['latitude', '/^\s*(\d{7})/', '/^\d{7}$/'],
    ['latitudeNS', '/^\s*([NS])/', '/^[NS]$/'],
    ['longitude', '/^\s*(\d{8})/', '/^\d{8}$/'],
    ['longitudeEW', '/^\s*([EW])/', '/^[EW]$/'],
    // ...
];
```

**OU** mieux : utiliser une approche avec `offset` qui gère déjà les positions dans la ligne (ce qui semble être le cas dans `AbstractRecordType::extract()`).

---

### 🟡 PROBLÈME #3 : Calcul de distance GPS - Précision

#### Code actuel

```php
private function distanceGps($latitude1, $longitude1, $latitude2, $longitude2)
{
    if($latitude1 == $latitude2 && $longitude1 == $longitude2) {
        return 0;
    }
    //distance en mètres
    $lat1 = deg2rad($latitude1);
    $lon1 = deg2rad($longitude1);
    $lat2 = deg2rad($latitude2);
    $lon2 = deg2rad($longitude2);
    $dist = acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lon2 - $lon1)) * 6371000;
    return round($dist);
}
```

#### Problèmes potentiels

1. **Précision de `acos()`** : Pour des points très proches ou très éloignés, `acos()` peut retourner `NaN` si l'argument est hors de [-1, 1] à cause d'erreurs d'arrondi.

2. **Rayon de la Terre** : 6371000 mètres est correct (rayon moyen), mais pourrait être amélioré avec le rayon de WGS84 (6378137 mètres).

3. **Formule de Haversine** : La formule actuelle est correcte, mais la version de Haversine est plus stable numériquement pour de petites distances.

#### Correction proposée

```php
/**
 * Calcule la distance entre deux points GPS en utilisant la formule de Haversine
 * Plus stable numériquement que la formule acos() pour de petites distances
 * 
 * @param float $latitude1 Latitude du premier point (degrés décimaux)
 * @param float $longitude1 Longitude du premier point (degrés décimaux)
 * @param float $latitude2 Latitude du deuxième point (degrés décimaux)
 * @param float $longitude2 Longitude du deuxième point (degrés décimaux)
 * @return int Distance en mètres (arrondie)
 */
private function distanceGps($latitude1, $longitude1, $latitude2, $longitude2)
{
    // Points identiques
    if($latitude1 == $latitude2 && $longitude1 == $longitude2) {
        return 0;
    }
    
    // Rayon de la Terre en mètres (WGS84)
    $earthRadius = 6378137.0;
    
    // Conversion en radians
    $lat1 = deg2rad($latitude1);
    $lon1 = deg2rad($longitude1);
    $lat2 = deg2rad($latitude2);
    $lon2 = deg2rad($longitude2);
    
    // Différences
    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;
    
    // Formule de Haversine (plus stable numériquement)
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos($lat1) * cos($lat2) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;
    
    // Protection contre NaN et valeurs infinies
    if (is_nan($distance) || is_infinite($distance)) {
        return 0;
    }
    
    return (int) round($distance);
}
```

---

### 🟡 PROBLÈME #4 : Gestion des altitudes négatives

#### Code actuel

```php
['gnssAltitude', '/^(\d{5}|-\d{4})/', '/^-?\d{5}$/'],
// ...
$data['gnssAltitude'] = (int) $data['gnssAltitude'];
```

#### Problème

La regex `'/^(\d{5}|-\d{4})/'` ne capture pas correctement les altitudes négatives. Elle cherche soit 5 chiffres positifs, soit 4 chiffres précédés d'un signe moins, mais la position dans la ligne peut ne pas correspondre.

De plus, la conversion en `(int)` peut échouer si la valeur extraite est une chaîne vide ou invalide.

#### Correction proposée

```php
// Dans le format
['gnssAltitude', '/^(-?\d{5})/', '/^-?\d{5}$/'],

// Dans parse()
$data['gnssAltitude'] = isset($data['gnssAltitude']) && $data['gnssAltitude'] !== '' 
    ? (int) $data['gnssAltitude'] 
    : null;
```

---

### 🟢 PROBLÈME #5 : Code de debug à supprimer

#### Lignes à supprimer

```php
// Ligne 58 et 60
var_dump($data['latitude']);
// ...
var_dump($data['latitude']);
```

---

## Code corrigé complet

Voici le code corrigé pour la méthode `parse()` et les fonctions privées :

```php
public function parse(): object|null
{
    // Vérifier la validité du record
    $this->check();
    $data = $this->extract();   
    $data['satellites'] = (int) $data['satellites'];

    //timestamp et dateTime     
    $data['timestamp'] = strtotime(((!is_null($this->flight) && isset($this->flight->OtherInformation->date)) ? $this->flight->OtherInformation->date : date('Y-m-d')).' '.$data['time']);
    $data['dateTime'] = date('Y-m-d H:i:s', $data['timestamp']);

    //latitude et longitude - CORRECTION APPLIQUÉE
    $latDegrees = (int) substr($data['latitude'], 0, 2);
    $latMinutes = (int) substr($data['latitude'], 2, 2);
    $latThousandths = (int) substr($data['latitude'], 4, 3);
    $data['latitude'] = $this->degreeToDecimal($latDegrees, $latMinutes, $latThousandths);
    
    if($data['latitudeNS'] === 'S') {
        $data['latitude'] = -$data['latitude'];
    }
    
    $lonDegrees = (int) substr($data['longitude'], 0, 3);
    $lonMinutes = (int) substr($data['longitude'], 3, 2);
    $lonThousandths = (int) substr($data['longitude'], 5, 3);
    $data['longitude'] = $this->degreeToDecimal($lonDegrees, $lonMinutes, $lonThousandths);
    
    if($data['longitudeEW'] === 'W') {
        $data['longitude'] = -$data['longitude'];
    }
    
    //altitudes - CORRECTION APPLIQUÉE
    $data['gnssAltitude'] = isset($data['gnssAltitude']) && $data['gnssAltitude'] !== '' 
        ? (int) $data['gnssAltitude'] 
        : null;
    $data['pressureAltitude'] = (int) $data['pressureAltitude'];
    
    //fixRecordCount
    if(!is_null($this->flight)) {
        if(!isset($this->flight->OtherInformation->fixRecordCount)) {
            $this->flight->OtherInformation->fixRecordCount = 1;
        } else {
            $this->flight->OtherInformation->fixRecordCount++;
        }

        // distance from last record
        if(isset($this->flight->Fix) && count($this->flight->Fix) > 0) {
            $lastRecord = $this->flight->Fix[count($this->flight->Fix) - 1];
            $data['distanceFromLastRecord'] = $this->distanceGps($lastRecord->latitude, $lastRecord->longitude, $data['latitude'], $data['longitude']);
            $data['speed'] = $this->speedGps($data['distanceFromLastRecord'], $data['timestamp'] - $lastRecord->timestamp);

            if(!$this->isCorrectRecord($data)) {
                return null;
            }

            if(!isset($this->flight->OtherInformation->totalDistance)) {
                $this->flight->OtherInformation->totalDistance = 0;
            }
            if(!isset($this->flight->OtherInformation->maxSpeed)) {
                $this->flight->OtherInformation->maxSpeed = 0;
            }
            if(!isset($this->flight->OtherInformation->totalTime)) {
                $this->flight->OtherInformation->totalTime = 0;
            }
            $this->flight->OtherInformation->totalTime += $data['timestamp'] - $lastRecord->timestamp;
            $this->flight->OtherInformation->totalDistance += $data['distanceFromLastRecord'];
            
            $this->flight->OtherInformation->maxSpeed = max($this->flight->OtherInformation->maxSpeed, $data['speed']);
        } else {
            $data['distanceFromLastRecord'] = 0;
            $data['speed'] = 0;
        }
    }

    if(!$this->isCorrectRecord($data)) {
        return null;
    }

    return (object) $data;
}

/**
 * Convertit les coordonnées IGC (DDMMmmm) en degrés décimaux
 * 
 * Format IGC : DDMMmmm où mmm sont des millièmes de minute (pas des secondes)
 * 
 * @param int $degrees Degrés
 * @param int $minutes Minutes
 * @param int $thousandths Millièmes de minute (mmm)
 * @return float Degrés décimaux
 */
private function degreeToDecimal($degrees, $minutes, $thousandths)
{
    // Conversion correcte : mmm représente des millièmes de minute
    // Minutes décimales = MM + mmm/1000
    // Degrés décimaux = DD + (MM + mmm/1000) / 60
    $decimalMinutes = $minutes + ($thousandths / 1000);
    return $degrees + ($decimalMinutes / 60);
}

/**
 * Calcule la distance entre deux points GPS en utilisant la formule de Haversine
 * Plus stable numériquement que la formule acos() pour de petites distances
 * 
 * @param float $latitude1 Latitude du premier point (degrés décimaux)
 * @param float $longitude1 Longitude du premier point (degrés décimaux)
 * @param float $latitude2 Latitude du deuxième point (degrés décimaux)
 * @param float $longitude2 Longitude du deuxième point (degrés décimaux)
 * @return int Distance en mètres (arrondie)
 */
private function distanceGps($latitude1, $longitude1, $latitude2, $longitude2)
{
    // Points identiques
    if($latitude1 == $latitude2 && $longitude1 == $longitude2) {
        return 0;
    }
    
    // Rayon de la Terre en mètres (WGS84)
    $earthRadius = 6378137.0;
    
    // Conversion en radians
    $lat1 = deg2rad($latitude1);
    $lon1 = deg2rad($longitude1);
    $lat2 = deg2rad($latitude2);
    $lon2 = deg2rad($longitude2);
    
    // Différences
    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;
    
    // Formule de Haversine (plus stable numériquement)
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos($lat1) * cos($lat2) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;
    
    // Protection contre NaN et valeurs infinies
    if (is_nan($distance) || is_infinite($distance)) {
        return 0;
    }
    
    return (int) round($distance);
}
```

---

## Tests de validation

### Exemple 1 : Latitude

**Entrée IGC** : `5111299N`
- DD = 51
- MM = 11
- mmm = 299

**Calcul attendu** :
- Minutes décimales = 11 + 299/1000 = 11.299 minutes
- Degrés décimaux = 51 + 11.299/60 = **51.1883°**

**Vérification** : 51° 11.299' = 51° 11' 17.94" = 51.1883° ✅

### Exemple 2 : Longitude

**Entrée IGC** : `00101710W`
- DDD = 001
- MM = 01
- mmm = 710

**Calcul attendu** :
- Minutes décimales = 1 + 710/1000 = 1.710 minutes
- Degrés décimaux = 1 + 1.710/60 = 1.0285°
- Longitude Ouest = **-1.0285°**

**Vérification** : 1° 1.710' Ouest = -1.0285° ✅

### Exemple 3 : Distance entre deux points

**Point 1** : 51.1883°N, -1.0285°W
**Point 2** : 51.1884°N, -1.0286°W

**Distance attendue** : ~12-15 mètres (selon la formule de Haversine)

---

## Impact des corrections

### Avant correction
- Erreur de position : **~12 km** pour chaque point GPS
- Calculs de distance : **incorrects** (basés sur des positions erronées)
- Calculs de vitesse : **incorrects** (basés sur des distances erronées)
- Statistiques de vol : **fausses** (distance totale, vitesse max, etc.)

### Après correction
- Position précise : **précision métrique** (selon la précision GPS)
- Calculs de distance : **corrects**
- Calculs de vitesse : **corrects**
- Statistiques de vol : **fiables**

---

## Priorité des corrections

1. 🔴 **CRITIQUE** : Correction de `degreeToDecimal()` - Impact majeur sur toutes les positions
2. 🟡 **IMPORTANT** : Amélioration de `distanceGps()` - Meilleure stabilité numérique
3. 🟡 **IMPORTANT** : Gestion des altitudes négatives - Correction de bugs potentiels
4. 🟢 **NORMAL** : Suppression du code de debug - Nettoyage

---

## Notes supplémentaires

### Format IGC officiel

Selon la spécification IGC (FAI), le format des coordonnées est :
- **Latitude** : `DDMMmmm` où `mmm` = millièmes de minute (0-999)
- **Longitude** : `DDDMMmmm` où `mmm` = millièmes de minute (0-999)

**Référence** : FAI Sporting Code Section 3 - IGC Flight Recorder Specification

### Précision

Avec le format IGC :
- **Précision latitude** : 1 millième de minute = 1/1000 minute = 0.001/60 degré ≈ **1.85 mètres**
- **Précision longitude** : Variable selon la latitude (1.85 m × cos(latitude))

Cette précision est suffisante pour la plupart des applications de vol à voile.
