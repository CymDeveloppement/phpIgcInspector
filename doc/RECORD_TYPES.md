# Types d'enregistrements IGC

## A - FR manufacturer and identification
Identifie le fabricant et le modèle de l'enregistreur de vol.

## B - Fix
Points GPS contenant les coordonnées, l'heure, l'altitude, etc.

## C - Task/declaration
Déclaration de la tâche de vol.

## D - Differential GPS
Données GPS différentielles pour améliorer la précision.
📄 Voir [RECORDTYPE_D.md](RECORDTYPE_D.md) pour plus de détails.

## E - Event
Événements marqués pendant le vol (ex: départ, arrivée, etc.).
📄 Voir [RECORDTYPE_E.md](RECORDTYPE_E.md) pour plus de détails.

## F - Constellation
Informations sur la constellation GPS utilisée.
📄 Voir [RECORDTYPE_F.md](RECORDTYPE_F.md) pour plus de détails.

## G - Security
Checksum SHA1 pour validation de l'intégrité du fichier.
📄 Voir [RECORDTYPE_G.md](RECORDTYPE_G.md) pour plus de détails.

## H - File header
En-têtes contenant les métadonnées du vol (pilote, planeur, date, etc.).

## I - List of extension data
Liste des données d'extension incluses à la fin de chaque fix B.

## J - List of data in extension (K) Record
Liste des données incluses dans chaque enregistrement d'extension K.

## K - Extension data
Données d'extension supplémentaires.

## L - Logbook/comments
Commentaires et annotations dans le carnet de vol.

