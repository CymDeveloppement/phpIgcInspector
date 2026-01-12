# RecordTypeD - Differential GPS (DGPS)

## Description

Le **RecordTypeD** (enregistrement de type D) est utilisé pour indiquer l'utilisation du **GPS différentiel (DGPS - Differential GPS)** pendant le vol.

## Utilité

Le GPS différentiel est une technique qui améliore la précision des positions GPS en utilisant des corrections depuis une station de référence au sol. Cette technique était particulièrement importante dans les premiers systèmes GPS civils qui avaient une précision limitée (quelques dizaines de mètres).

### Principe du GPS différentiel

1. **Station de référence** : Une station au sol avec une position connue avec précision
2. **Corrections** : La station calcule les erreurs du signal GPS et émet des corrections
3. **Récepteur mobile** : Le récepteur GPS du planeur reçoit ces corrections et améliore sa précision
4. **Résultat** : Précision améliorée de quelques mètres au lieu de dizaines de mètres

## Format IGC

```
D Q SSSS CRLF
```

Où :
- **D** : Type d'enregistrement
- **Q** : Qualificateur GPS (1 caractère)
  - `1` = GPS standard
  - `2` = DGPS (GPS différentiel)
- **SSSS** : Identifiant de la station DGPS (4 caractères alphanumériques)
- **CRLF** : Retour à la ligne

### Exemple

```
D2ABCD
```

Signifie :
- Qualificateur : `2` (DGPS utilisé)
- Station DGPS : `ABCD`

## Position dans le fichier IGC

L'enregistrement de type D doit être placé :
- **Après** les enregistrements de type H (header), I, J et C
- **Avant** le premier enregistrement de type B (fix/position)

## Utilisation actuelle

### Historique

Le GPS différentiel était très utilisé dans les années 1990-2000 lorsque :
- Les GPS civils avaient une précision limitée (SA - Selective Availability activé)
- Les compétitions de vol à voile nécessitaient une précision élevée
- Les systèmes DGPS étaient nécessaires pour valider les passages de turnpoints

### Aujourd'hui

Le GPS différentiel est **rarement utilisé** dans les fichiers IGC modernes car :
- ✅ **SA désactivé** : Depuis 2000, le gouvernement américain a désactivé la dégradation intentionnelle du signal GPS
- ✅ **GPS modernes précis** : Les récepteurs GPS modernes (GPS, GLONASS, Galileo, BeiDou) offrent une précision de 2-5 mètres sans corrections
- ✅ **Multi-constellation** : Les récepteurs multi-constellation améliorent encore la précision
- ✅ **RTK et autres techniques** : D'autres techniques plus modernes remplacent le DGPS classique

## Dans phpIgcInspector

### État actuel

Le **RecordTypeD** est actuellement :
- ✅ **Reconnu** : La classe existe et détecte les lignes commençant par `D`
- ❌ **Ignoré** : `ignoreRecord = true` - les données ne sont pas parsées
- ❌ **Non implémenté** : Le format n'est pas parsé, seule la ligne brute est stockée (si `withRaw = true`)

### Implémentation future (optionnelle)

Si vous souhaitez implémenter le parsing du RecordTypeD :

```php
protected array $format = [
    ['qualifier', '/^D([12])/', '/^[12]$/'],
    ['stationId', '/^[12](.{4})/', '/^[A-Z0-9]{4}$/']
];
```

**Priorité** : 🟢 Basse (peu utilisé dans les fichiers modernes)

## Exemple de fichier avec RecordTypeD

```
ALXV6MSFLIGHT:1
HFDTE050822
HFPLTPILOT:Mike Young
...
D2ABCD          ← Utilisation du DGPS avec station ABCD
B0909325111299N00101710WA000960022900700100000000041970000001920100-010-09
B0909335111299N00101710WA000960023000700400000000391970000001920100-010-09
...
```

## Conclusion

Le RecordTypeD est un **legacy** du format IGC, important historiquement mais rarement utilisé aujourd'hui. Il peut être utile pour :
- Analyser des fichiers IGC anciens (années 1990-2000)
- Comprendre l'historique des techniques de navigation
- Valider l'intégrité des fichiers IGC complets

Pour la plupart des fichiers IGC modernes, cet enregistrement est absent car les GPS modernes n'ont plus besoin de corrections différentielles.
