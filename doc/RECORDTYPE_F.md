# RecordTypeF - Constellation (Constellation GPS)

## Description

Le **RecordTypeF** (enregistrement de type F) fournit des informations sur la **constellation de satellites GPS** utilisée pour déterminer la position de l'aéronef à un moment donné.

## Utilité

Les enregistrements de type F permettent de :
- **Évaluer la précision** des données de positionnement
- **Comprendre la qualité du signal GPS** à différents moments du vol
- **Déboguer les problèmes de positionnement** (perte de signal, précision dégradée)
- **Valider l'intégrité des données** en vérifiant la disponibilité des satellites

## Format IGC

```
F HHMMSS SSSSSSSS... CRLF
```

Où :
- **F** : Type d'enregistrement
- **HHMMSS** : Heure UTC (6 chiffres)
- **SSSSSSSS...** : Liste des identifiants des satellites utilisés (variable)

### Format détaillé

Chaque satellite est représenté par :
- **2 chiffres** : Identifiant du satellite (01-32 pour GPS, autres pour GLONASS/Galileo)
- Les satellites sont listés consécutivement sans séparateur

### Exemple

```
F09093227163023070801103221
```

Décomposition :
- Heure : `090932` (09:09:32)
- Satellites : `27`, `16`, `30`, `23`, `07`, `08`, `01`, `10`, `32`, `21` (10 satellites)

## Position dans le fichier IGC

Les enregistrements de type F :
- Apparaissent **régulièrement** pendant le vol (généralement toutes les quelques minutes)
- Sont **associés temporellement** aux enregistrements de type B (fix)
- Peuvent apparaître **plusieurs fois** dans le fichier

## Relation avec les RecordTypeB

Les enregistrements F sont souvent **synchronisés** avec les points GPS :
- Un enregistrement F indique quels satellites étaient utilisés pour calculer les positions B suivantes
- Permet de comprendre pourquoi certains points GPS peuvent être moins précis

## Utilisation pratique

### Pour l'analyse de vol

Les données de constellation permettent de :
1. **Évaluer la qualité GPS** : Nombre de satellites disponibles
2. **Détecter les problèmes** : Perte de satellites, précision dégradée
3. **Valider les données** : Vérifier que suffisamment de satellites étaient disponibles
4. **Comprendre les erreurs** : Analyser pourquoi certains points peuvent être moins précis

### Pour les compétitions

Les données de constellation sont importantes pour :
- **Validation technique** : Vérifier que les données GPS sont fiables
- **Contrôle qualité** : S'assurer que les positions sont calculées avec suffisamment de satellites
- **Résolution de litiges** : Analyser les problèmes de positionnement

## Dans phpIgcInspector

### État actuel

Le **RecordTypeF** est actuellement :
- ✅ **Reconnu** : La classe existe et détecte les lignes commençant par `F`
- ❌ **Ignoré** : `ignoreRecord = true` - les données ne sont pas parsées
- ❌ **Non implémenté** : Le format n'est pas parsé, seule la ligne brute est stockée (si `withRaw = true`)

### Implémentation future (optionnelle)

Une implémentation pourrait inclure :

```php
protected array $format = [
    ['time', '/^F(\d{6})/', '/^\d{6}$/'],
    ['satellites']
];
```

**Priorité** : 🟡 Moyenne (données techniques utiles mais pas critiques)

## Exemple de fichier avec RecordTypeF

```
B0909325111299N00101710WA000960022900700100000000041970000001920100-010-09
F09093227163023070801103221    ← Constellation GPS à 09:09:32
B0909335111299N00101710WA000960023000700400000000391970000001920100-010-09
...
F0912432716302307080110143221  ← Nouvelle constellation à 09:12:43
B0912445204577N00307663WA000960023000700400000000391970000001920100-010-09
```

## Conclusion

Le RecordTypeF fournit des **informations techniques** sur la qualité du signal GPS. Bien que moins critique que les événements (E) ou la sécurité (G), il est utile pour l'analyse approfondie des données de vol et la validation technique des fichiers IGC.
