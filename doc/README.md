# Documentation phpIgcInspector

## Fichiers IGC

Le format IGC (International Gliding Commission) est un format standard pour enregistrer les données de vol des planeurs.

### Structure des fichiers IGC

Les fichiers IGC contiennent plusieurs sections :

1. **En-tête (Header Records)** : Informations sur le vol, le pilote, l'avion, etc.
2. **Enregistrements A (Task Records)** : Définition de la tâche de vol
3. **Enregistrements de cheminement (Track Records)** : Points GPS et autres données de navigation
4. **Commentaires (Comments)** : Notes diverses

### Spécifications techniques

- Format de fichier textuel (ASCII)
- Chaque ligne commence par une lettre indiquant le type d'enregistrement
- Les coordonnées GPS sont en degrés décimaux

### Ressources

- [Fédération Aéronautique Internationale - Spécifications IGC](https://www.fai.org/sporting-code/sc3)
- Format de date : DDMMYY
- Format de date étendue : DDMMYYYY

