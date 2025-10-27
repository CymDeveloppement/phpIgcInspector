# Spécifications du format IGC

## Structure générale

Un fichier IGC est composé d'enregistrements sur plusieurs lignes, chaque ligne commençant par une lettre majuscule indiquant le type d'enregistrement.

## Types d'enregistrements

### H - Enregistrements d'en-tête

Contiennent les métadonnées du vol :
- `HFDTE` : Date du vol
- `HFFXA` : Numéro de fix (indique le nombre de satellites capturés)
- `HFPLTPILOTINCHARGE` : Nom du pilote commandant de bord
- `HFCM2CREW2` : Nom de l'équipier
- `HFGTY` : Type de planeur
- `HFGID` : ID du planeur
- `HFDTM` : Système de référence GPS
- `HFRFW` : Firmware
- `HFRHW` : Hardware
- `HFGPS` : Récepteur GPS
- `HFPRS` : Source de pression
- `HFFTY` : Type de point de fixation
- `HFCIDCOMPETITIONID` : ID de compétition
- `HFCIDCOMPETITIONCLASS` : Classe de compétition
- `HFCCL` : Classe de croisière

### A - Enregistrements de type d'appareil

`A` suivi de 6 caractères identifiant le fabricant et le modèle de l'enregistreur de vol.

### B - Enregistrements de fix (points GPS)

Format :
```
B HHMMSS DDMMmmm N DDDMMmmm E A PPPPP GGGGG CR LF
```

- HHMMSS : Heure UTC (6 chiffres)
- DDMMmmm : Latitude (7 caractères)
- N/S : Hémisphère Nord ou Sud
- DDDMMmmm : Longitude (8 caractères)
- E/W : Hémisphère Est ou Ouest
- A : Validité du signal ('A' = 3D valide, 'V' = non valide)
- PPPPP : Pression barométrique (5 chiffres)
- GGGGG : Altitude GPS (5 chiffres)
- CR : Caractère de retour chariot
- LF : Caractère de fin de ligne

### C - Enregistrements de tâche

- `C` : Commentaires relatifs à la tâche
- Contient la déclaration de la tâche de vol

### L - Enregistrements de commentaires

- Commentaires libres
- Souvent utilisés pour des informations additionnelles

### O - Enregistrements d'OVL (Overlay)

Informations graphiques additionnelles.

### G - Enregistrements de checksum

Contient le checksum SHA1 de toutes les lignes précédentes pour validation de l'intégrité.

## Exemple d'enregistrement B (fix)

```
B 124330 4740464N 00626215E A 10066 -01 00101
```

Décomposition :
- 12:43:30 : Heure UTC
- 47°40'46".4 Nord
- 006°26'21".5 Est
- A : Signal GPS 3D valide
- Pression : 1006.6 hPa
- Altitude GPS : -1 m (ou non définie)
- Altitude barométrique : 101 m

