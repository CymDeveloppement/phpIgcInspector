# RecordTypeE - Event (Événements)

## Description

Le **RecordTypeE** (enregistrement de type E) est utilisé pour enregistrer des **événements spécifiques** survenus pendant le vol. Ces événements peuvent être déclenchés manuellement par le pilote ou automatiquement par l'enregistreur de vol.

## Utilité

Les événements permettent de marquer des moments importants du vol pour :
- **Analyse post-vol** : Identifier les moments clés (départ, arrivée, passages de turnpoints)
- **Validation de tâche** : Confirmer le passage aux points de virage
- **Débogage** : Comprendre le comportement de l'enregistreur
- **Compétitions** : Valider les performances et les passages obligatoires

## Format IGC

```
E HHMMSS XXX... CRLF
```

Où :
- **E** : Type d'enregistrement
- **HHMMSS** : Heure UTC de l'événement (6 chiffres)
- **XXX...** : Code ou description de l'événement (variable)

### Types d'événements courants

Les codes d'événements peuvent varier selon les fabricants, mais voici quelques exemples courants :

- **Départ** : `PEV` (Pilot Event), `START`, `ST`
- **Arrivée** : `PEV`, `FINISH`, `FN`
- **Passage de turnpoint** : `PEV`, `TP`
- **Activation moteur** : `PEV`, `MOTOR`
- **Début de vol** : `PEV`, `TAKEOFF`
- **Atterrissage** : `PEV`, `LANDING`

### Exemple

```
E091530PEVSTART
E101245PEVTP1
E120530PEVTP2
E140215PEVFN
```

## Position dans le fichier IGC

Les enregistrements de type E peuvent apparaître :
- **N'importe où** dans le fichier après les enregistrements de header
- Généralement **intercalés** entre les enregistrements de type B (fix)
- Associés temporellement aux points GPS correspondants

## Relation avec les RecordTypeB

Les événements sont souvent **associés** aux points GPS (RecordTypeB) :
- Un événement de départ correspond généralement au premier point GPS
- Un événement de passage de turnpoint correspond à un point GPS proche du waypoint
- Un événement d'arrivée correspond généralement au dernier point GPS

## Utilisation pratique

### Pour l'analyse de vol

Les événements permettent de :
1. **Identifier les phases du vol** : départ, vol libre, arrivée
2. **Valider les passages de turnpoints** : confirmer que le pilote a bien passé les points
3. **Analyser les performances** : calculer les temps entre événements
4. **Détecter les problèmes** : identifier les événements inattendus

### Pour les compétitions

Les événements sont cruciaux pour :
- **Validation de tâche** : confirmer le passage aux turnpoints
- **Calcul des scores** : déterminer les temps de vol effectifs
- **Contrôle technique** : vérifier le respect des règles

## Dans phpIgcInspector

### État actuel

Le **RecordTypeE** est actuellement :
- ✅ **Reconnu** : La classe existe et détecte les lignes commençant par `E`
- ❌ **Ignoré** : `ignoreRecord = true` - les données ne sont pas parsées
- ❌ **Non implémenté** : Le format n'est pas parsé, seule la ligne brute est stockée (si `withRaw = true`)

### Implémentation future (recommandée)

Le RecordTypeE est **important** pour l'analyse de vol. Une implémentation pourrait inclure :

```php
protected array $format = [
    ['time', '/^E(\d{6})/', '/^\d{6}$/'],
    ['eventCode', '/^\d{6}(.{3})/', '/^[A-Z0-9]{3}$/'],
    ['eventData']
];
```

**Priorité** : 🔴 Haute (données importantes pour l'analyse de vol et les compétitions)

## Exemple de fichier avec RecordTypeE

```
ALXV6MSFLIGHT:1
HFDTE050822
...
B0909325111299N00101710WA000960022900700100000000041970000001920100-010-09
E090932PEVSTART    ← Événement de départ
B0909335111299N00101710WA000960023000700400000000391970000001920100-010-09
...
B1012455204577N00307663WA000960023000700400000000391970000001920100-010-09
E101245PEVTP1      ← Passage du turnpoint 1
...
B1402155111643N00102000WA000960022900700400000000391970000001920100-010-09
E140215PEVFN       ← Événement d'arrivée
```

## Conclusion

Le RecordTypeE est **essentiel** pour l'analyse de vol et la validation des tâches en compétition. Il permet de marquer les moments importants du vol et de valider les passages aux turnpoints. Une implémentation complète serait très utile pour l'analyse des performances de vol.
