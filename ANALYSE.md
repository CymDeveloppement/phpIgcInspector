# Analyse de la librairie phpIgcInspector

## Vue d'ensemble

**phpIgcInspector** est une bibliothèque PHP permettant de lire, valider et manipuler des fichiers IGC (International Gliding Commission). Le format IGC est utilisé pour enregistrer les données de vol des planeurs et contient des informations GPS, météorologiques et techniques.

### Informations générales
- **Namespace** : `Ycdev\PhpIgcInspector`
- **Version PHP requise** : >= 7.4
- **License** : GPL-3.0-only
- **Auteur** : Yann (yann@cymdev.com)

## Architecture

### Structure du projet

```
phpIgcInspector/
├── src/
│   ├── PhpIgcInspector.php          # Classe principale
│   ├── Exception/
│   │   └── InvalidIgcException.php  # Exception personnalisée
│   ├── Data/
│   │   └── ManufacturerCodesData.php # Codes fabricants IGC
│   └── RecordTypes/                 # Types d'enregistrements
│       ├── RecordTypeInterface.php  # Interface commune
│       ├── AbstractRecordType.php   # Classe abstraite de base
│       ├── RecordTypeA.php          # Manufacturer
│       ├── RecordTypeB.php           # Fix (points GPS)
│       ├── RecordTypeC.php           # Task/declaration
│       ├── RecordTypeD.php           # Differential GPS
│       ├── RecordTypeE.php           # Event
│       ├── RecordTypeF.php           # Constellation
│       ├── RecordTypeG.php           # Security (checksum)
│       ├── RecordTypeH.php           # File header
│       ├── RecordTypeI.php           # Extension data list
│       ├── RecordTypeJ.php           # Extension K data list
│       ├── RecordTypeK.php           # Extension data
│       └── RecordTypeL.php           # Logbook/comments
└── tests/
    └── RecordTypes/
        └── RecordTypeATest.php
```

## Fonctionnement principal

### Classe PhpIgcInspector

La classe principale `PhpIgcInspector` est le point d'entrée de la librairie. Elle gère :

1. **Chargement du fichier IGC** : via le constructeur ou la méthode statique `fromFile()`
2. **Validation** : la méthode `validate()` parse et valide tout le fichier
3. **Extraction des données** : les données parsées sont stockées dans un objet `flight`
4. **Export** : conversion en JSON via `toJson()` ou `stringify()`

#### Méthodes principales

- `__construct(string $content, bool $withRaw = false)` : Constructeur avec contenu brut
- `fromFile(string $filePath, bool $withRaw = false)` : Création depuis un fichier
- `validate(): bool` : Valide et parse le fichier IGC complet
- `getFlight(): ?object` : Retourne l'objet flight parsé
- `getMetadata(): ?object` : Retourne uniquement les métadonnées (records uniques)
- `toJson(): ?string` : Exporte le flight en JSON
- `stringify(int $flags): ?string` : Exporte avec options JSON personnalisées

### Processus de validation et parsing

Le processus de validation suit ces étapes :

1. **Vérification initiale** : Le fichier ne doit pas être vide
2. **Traitement ligne par ligne** :
   - Suppression des lignes vides
   - Identification du type d'enregistrement (première lettre)
   - Instanciation de la classe `RecordType` correspondante
   - Vérification des contraintes (unicité, ordre, etc.)
   - Validation du format de la ligne
   - Parsing et extraction des données
   - Stockage dans l'objet `flight`

3. **Gestion des types d'enregistrements** :
   - **Records uniques** (`singleRecord = true`) : doivent apparaître une seule fois (ex: RecordTypeA)
   - **Records multiples fusionnés** (`singleObject = true`) : plusieurs occurrences fusionnées dans un objet (ex: RecordTypeH)
   - **Records multiples** : stockés dans un tableau (ex: RecordTypeB - Fix)

4. **Gestion des erreurs** : Les erreurs sont enregistrées dans `OtherInformation->errors` sans interrompre le parsing

## Système de types d'enregistrements

### Architecture des RecordTypes

Tous les types d'enregistrements héritent de `AbstractRecordType` qui implémente `RecordTypeInterface`. Cette architecture permet :

- **Extensibilité** : Ajout facile de nouveaux types d'enregistrements
- **Validation uniforme** : Méthode `check()` standardisée
- **Parsing flexible** : Système de format basé sur des regex

### Classe AbstractRecordType

#### Propriétés importantes

- `$line` : La ligne brute à parser
- `$lineNumber` : Numéro de ligne dans le fichier
- `$previousRecordType` : Type d'enregistrement précédent (pour validation contextuelle)
- `$flight` : Pointeur vers l'objet flight complet (pour écriture directe)
- `$singleRecord` : Si true, l'enregistrement doit être unique
- `$singleObject` : Si true, les multiples occurrences sont fusionnées
- `$ignoreRecord` : Si true, l'enregistrement est ignoré lors du parsing
- `$withRaw` : Si true, ajoute le champ 'raw' avec la ligne complète
- `$recordId` : Identifiant pour stocker dans l'objet flight
- `$format` : Définition du format de parsing

#### Système de format

Le format est défini dans la propriété `$format` de chaque classe. Trois formats sont supportés :

1. **Format simple** : `['fieldName']` - Extrait tout le reste de la ligne
2. **Format complet** : `['fieldName', '/regexSearch/', '/regexValidate/']`
   - `regexSearch` : Regex pour extraire la valeur
   - `regexValidate` : Regex pour valider le format
3. **Format multiple** : Tableau associatif avec plusieurs formats possibles
   ```php
   ['data' => [
       ['date', '/^HFDTE(\d{6})/', '/^\d{6}$/'],
       ['pilot', '/^HFPLTPILOT:([A-Za-z\s]+)/', '/^[A-Za-z\s]+$/']
   ]]
   ```

#### Méthodes clés

- `matches(string $line): bool` : Vérifie si une ligne correspond au type
- `check(): bool` : Valide le format de la ligne
- `parse()` : Parse la ligne et retourne un objet avec les données
- `extract(bool $validate = true): array` : Extrait les valeurs selon le format
- `checkFormat(?array $data = null): bool` : Valide les données extraites
- `recordError($errorMessage)` : Enregistre une erreur dans `OtherInformation->errors`

### Types d'enregistrements implémentés

#### RecordTypeA - Manufacturer
- **Unique** : Oui (doit être en première ligne)
- **Contenu** : Code fabricant (3 caractères), numéro de série, données additionnelles
- **Fonctionnalités** : Résolution du nom du fabricant depuis `ManufacturerCodesData`, détection des fabricants approuvés IGC

#### RecordTypeB - Fix (Points GPS)
- **Unique** : Non (peut apparaître des milliers de fois)
- **Contenu** : Heure UTC, latitude/longitude, altitude barométrique/GPS, validité, précision, satellites, etc.
- **Fonctionnalités avancées** :
  - Conversion des coordonnées DDDMMmmm en décimales
  - Calcul de la distance entre points GPS (formule de Haversine)
  - Calcul de la vitesse entre points
  - Validation de la vitesse (max 400 km/h par défaut)
  - Calcul des statistiques : distance totale, temps total, vitesse max
  - Génération de timestamp et datetime
  - Filtrage des points invalides (NaN, infini, vitesse excessive)

#### RecordTypeH - File Header
- **Unique** : Non, mais fusionné dans un seul objet (`singleObject = true`)
- **Contenu** : Métadonnées variées (date, pilote, planeur, firmware, etc.)
- **Format** : `H + TLC (Three Letter Code) + : + valeur`
- **Exemples** :
  - `HFDTE050822` → Date: 05/08/22
  - `HFPLTPILOT:Mike Young` → Pilote: Mike Young
  - `HFGIDGLIDERID:D-KVMY` → ID Planeur: D-KVMY

#### RecordTypeI - Extension Data List
- **Ignoré** : Oui (`ignoreRecord = true`)
- **Raison** : Liste des extensions, pas de données à extraire directement

#### Autres types
- **RecordTypeC** : Task/declaration
- **RecordTypeD** : Differential GPS
- **RecordTypeE** : Event
- **RecordTypeF** : Constellation
- **RecordTypeG** : Security (checksum SHA1)
- **RecordTypeJ** : List of extension K data
- **RecordTypeK** : Extension data
- **RecordTypeL** : Logbook/comments

## Flux de données

### Exemple d'utilisation

```php
use Ycdev\PhpIgcInspector\PhpIgcInspector;

// Chargement depuis un fichier
$igc = PhpIgcInspector::fromFile('vol.igc', true);

// Validation et parsing
$igc->validate();

// Récupération des données
$flight = $igc->getFlight();
$metadata = $igc->getMetadata();

// Export JSON
$json = $igc->toJson();
```

### Structure de l'objet flight

L'objet `flight` contient :

- **Manufacturer** (objet) : Informations du fabricant (RecordTypeA)
- **OtherInformation** (objet) : Métadonnées fusionnées (RecordTypeH)
  - `date`, `pilot`, `gliderType`, `gliderId`, etc.
  - `errors` : Tableau des erreurs rencontrées
  - `errorCount` : Nombre d'erreurs
  - `fixRecordCount` : Nombre de points GPS
  - `totalDistance` : Distance totale en mètres
  - `totalTime` : Temps total en secondes
  - `maxSpeed` : Vitesse maximale en km/h
- **Fix** (tableau) : Points GPS (RecordTypeB)
  - Chaque point contient : `time`, `latitude`, `longitude`, `timestamp`, `dateTime`, `gnssAltitude`, `pressureAltitude`, `speed`, `distanceFromLastRecord`, etc.
- **Event** (tableau) : Événements (RecordTypeE)
- **Task** (objet/tableau) : Tâche de vol (RecordTypeC)
- **Security** (objet) : Checksum (RecordTypeG)
- Autres types selon le fichier

## Points clés de l'implémentation

### 1. Gestion des erreurs non bloquantes

La librairie continue le parsing même en cas d'erreurs mineures. Les erreurs sont enregistrées dans `OtherInformation->errors` plutôt que de lever des exceptions qui interrompraient le traitement.

### 2. Validation contextuelle

Certains enregistrements nécessitent le contexte (ex: RecordTypeB a besoin de la date du RecordTypeH pour générer le timestamp). Le pointeur `$flight` permet d'accéder aux données déjà parsées.

### 3. Filtrage intelligent

RecordTypeB filtre automatiquement les points invalides :
- Vitesse excessive (> 400 km/h)
- Valeurs NaN ou infinies
- Ces points sont ignorés (retourne `null` dans `parse()`)

### 4. Calculs automatiques

Pour les points GPS (RecordTypeB), la librairie calcule automatiquement :
- Distance depuis le point précédent
- Vitesse entre points
- Statistiques globales (distance totale, temps, vitesse max)

### 5. Conversion de formats

- Coordonnées : Conversion DDDMMmmm → décimales
- Date : Conversion format IGC (DDMMYY) → ISO (YYYY-MM-DD)
- Timestamp : Génération depuis date + heure

### 6. Support des fabricants

La classe `ManufacturerCodesData` contient une base de données complète des codes fabricants IGC, permettant d'identifier automatiquement le fabricant et de détecter si c'est un fabricant approuvé IGC.

## Extensibilité

### Ajouter un nouveau type d'enregistrement

1. Créer une nouvelle classe dans `src/RecordTypes/` héritant de `AbstractRecordType`
2. Implémenter `matches()`, `check()`, et éventuellement surcharger `parse()`
3. Définir `$format` avec les regex appropriées
4. Configurer les propriétés (`$singleRecord`, `$singleObject`, `$recordId`, etc.)

### Exemple de structure minimale

```php
class RecordTypeX extends AbstractRecordType
{
    protected string $recordId = 'MyRecord';
    protected bool $singleRecord = false;
    
    protected array $format = [
        ['field1', '/^X(\d+)/', '/^\d+$/'],
        ['field2', '/^([A-Z]+)/', '/^[A-Z]+$/']
    ];
    
    public function matches(string $line): bool
    {
        return strlen($line) > 0 && $line[0] === 'X';
    }
    
    public function check(): bool
    {
        // Validation spécifique
        return true;
    }
}
```

## Limitations et améliorations possibles

### Limitations actuelles

1. **Validation incomplète** : Certains `check()` retournent simplement `true` (TODO)
2. **Gestion des extensions** : RecordTypeI et RecordTypeJ sont partiellement implémentés
3. **Performance** : Parsing séquentiel ligne par ligne (pourrait être optimisé pour très gros fichiers)
4. **Tests** : Couverture de tests limitée (seulement RecordTypeA testé)

### Améliorations possibles

1. Compléter la validation de tous les types d'enregistrements
2. Implémenter complètement le support des extensions (RecordTypeI, J, K)
3. Ajouter des méthodes de filtrage/requêtage sur les points GPS
4. Améliorer la gestion des erreurs avec différents niveaux de sévérité
5. Ajouter des méthodes utilitaires (calcul de trajectoire, détection de thermiques, etc.)
6. Support de l'export vers d'autres formats (GPX, KML, etc.)

## Conclusion

**phpIgcInspector** est une librairie bien structurée pour le parsing de fichiers IGC. Son architecture modulaire basée sur les RecordTypes permet une extension facile, et la gestion non bloquante des erreurs assure une robustesse pour traiter des fichiers réels qui peuvent contenir des anomalies mineures. La librairie fournit déjà des fonctionnalités avancées comme le calcul automatique de distances et vitesses, ainsi qu'une base de données complète des fabricants IGC.
