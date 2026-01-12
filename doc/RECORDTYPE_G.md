# RecordTypeG - Security (Sécurité / Checksum)

## Description

Le **RecordTypeG** (enregistrement de type G) contient une **signature numérique (checksum)** pour valider l'intégrité et l'authenticité du fichier IGC.

## Utilité

Le checksum permet de :
- **Vérifier l'intégrité** : Détecter toute modification du fichier après l'enregistrement
- **Authentifier les données** : Garantir que le fichier n'a pas été altéré
- **Valider pour compétitions** : Les fichiers IGC doivent avoir un checksum valide pour être acceptés
- **Assurer la traçabilité** : Garantir l'authenticité des performances enregistrées

## Format IGC

```
G AAAAAAAA... CRLF
```

Où :
- **G** : Type d'enregistrement
- **AAAAAAAA...** : Checksum SHA1 (40 caractères hexadécimaux)

### Format du checksum

- **Algorithme** : SHA1 (Secure Hash Algorithm 1)
- **Longueur** : 40 caractères hexadécimaux
- **Calcul** : Hash de toutes les lignes précédentes (sauf les lignes G précédentes)

### Exemple

```
G A1B2C3D4E5F6A7B8C9D0E1F2A3B4C5D6E7F8A9B0
```

## Position dans le fichier IGC

L'enregistrement de type G :
- Apparaît généralement **à la fin** du fichier
- Peut apparaître **plusieurs fois** (un checksum toutes les X lignes pour les longs fichiers)
- **Ne doit pas** être inclus dans le calcul du checksum suivant

## Calcul du checksum

Le checksum SHA1 est calculé sur :
- **Toutes les lignes** depuis le début du fichier
- **Excluant** les lignes G précédentes
- **Incluant** les retours à la ligne (CRLF)

### Processus de validation

1. Extraire toutes les lignes sauf les lignes G
2. Calculer le SHA1 de ces lignes
3. Comparer avec le checksum fourni dans la dernière ligne G
4. Si identique → fichier valide et non modifié

## Utilisation pratique

### Pour les compétitions

Le checksum est **obligatoire** pour :
- **Validation officielle** : Les fichiers sans checksum valide sont rejetés
- **Contrôle anti-triche** : Empêche la modification des données de vol
- **Certification** : Garantit l'authenticité des performances

### Pour l'analyse

La validation du checksum permet de :
- **S'assurer de l'intégrité** des données analysées
- **Détecter les corruptions** de fichier
- **Valider la source** des données

## Dans phpIgcInspector

### État actuel

Le **RecordTypeG** est actuellement :
- ✅ **Reconnu** : La classe existe et détecte les lignes commençant par `G`
- ❌ **Ignoré** : `ignoreRecord = true` - les données ne sont pas parsées
- ❌ **Non implémenté** : Le checksum n'est pas calculé ni validé

### Implémentation future (recommandée)

Le RecordTypeG est **critique** pour la validation. Une implémentation devrait :

1. **Parser le checksum** : Extraire les 40 caractères hexadécimaux
2. **Calculer le SHA1** : Hasher toutes les lignes précédentes
3. **Comparer** : Vérifier que les checksums correspondent
4. **Stocker le résultat** : Ajouter `isValid: true/false` dans l'objet flight

**Priorité** : 🔴 Haute (validation de l'intégrité du fichier)

## Exemple de fichier avec RecordTypeG

```
ALXV6MSFLIGHT:1
HFDTE050822
...
B1402155111643N00102000WA000960022900700400000000391970000001920100-010-09
G A1B2C3D4E5F6A7B8C9D0E1F2A3B4C5D6E7F8A9B0C1D2E3F4A5B6C7D8E9F0  ← Checksum SHA1
```

## Conclusion

Le RecordTypeG est **essentiel** pour garantir l'intégrité des fichiers IGC. Il est obligatoire pour la validation officielle des performances en compétition. Une implémentation complète avec validation du checksum serait très utile pour assurer la fiabilité des données analysées.
