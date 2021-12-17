# Master-Hypermedia-SearchTool
 
Réalisation d'un projet dans le cadre d'un cours d'option Hypermédia en Master 1 Informatique à l'Université de Paris 8.

Outil de recherche de mots dans des fichiers texte.

Plusieurs boutons sont présent afin:
 - D'ajouter des données dans la Base de Données.
 - De mettre à jour les données dans la Base de Données suite à une modification d'un fichier, un ajout ou bien une suppression de fichier.
 - De supprimer toutes les données présentes dans la Base de Données.

Le projet utilise 2 tables:
 - Une 1ère table contenant un mot, le nombre d'occurence de ce mot dans un fichier ainsi que le fichier dans lequel il est présent.
 - Une 2ème table contenant le nom de tous les fichiers présents dans les dossiers de l'outil ainsi que la date de dernière modification.

Pour la recherche, les mots présents dans les fichiers passent pas 2 vérifications avant d'être ajouté dans la Base de Données:
 - Le mot doit avoir une taille supérieur à 2.
 - Le mot ne doit pas être un mot vide (vérification effectué via un fichier contenant des mots vide)
