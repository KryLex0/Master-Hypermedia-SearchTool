<?php

$pathParent = dirname(__FILE__);

include $pathParent . "/credentials/credentials.php";
header('Content-Type: text/html; charset=utf-8');
$path = $pathParent . "/fichiers_txt";
$nbMotsTotal = 0;
$nbMotsSave = 0;

$mysqlClient = new PDO($dbname, $login, $password);

//-----------------------------------------------------------//

//lecture du fichier de mots vide et ajout des mots vide dans une array
function addEmptyWordToArray(){
    $tab_mots_vide = array();
    $fichier_mots_vide = strtolower(file_get_contents($GLOBALS["pathParent"] . '/mots_vide/fichier_mots_vide.txt'));

    $separateurs =  "'’. -/\n\t\r,…][(«»<>)";
    $tok =  strtok($fichier_mots_vide, $separateurs);

    while ($tok !== false) {
        //echo $tok . " || ";
        array_push($tab_mots_vide, $tok);
        $tok = strtok($separateurs);
    }
    return $tab_mots_vide;
}

//-----------------------------------------------------------//

//scan du dossier contenant tout les fichiers textes
//afin de mettre les noms des fichiers dans une array de type (nomFichier=>timestampModif)
function addFileNameToArray(){
    $fichiers_txt = scandir($GLOBALS["path"]);
    array_shift($fichiers_txt); //supprime les 2 premiers resultats vides '.' et '..'
    array_shift($fichiers_txt); 

    foreach ($fichiers_txt as $fichier) {
        if ($fichier != '.' || $fichier != '..') {
            $tab_fichiers[$fichier] = filemtime($GLOBALS["path"] . "/" . $fichier);
        }
    }
    return $tab_fichiers;
}


//mettre les mots et occurences de chaque fichiers dans une array de type (mot=>nbOccurence)
function addFileWordOccurence($tab_fichiers){
    $all_tab = array();
    $tab_tok = array();

    $tabs_mots_vide = addEmptyWordToArray();

    foreach ($tab_fichiers as $fichier => $val) {
        $texte = strtolower(file_get_contents($GLOBALS["path"] . "/" . $fichier));

        $separateurs =  "'’. -/\n\t\r,…][(«»<>)";
        $tok =  strtok($texte, $separateurs);

        while ($tok !== false) {
            if ((strlen($tok) > 2) && !in_array($tok, $tabs_mots_vide)) {
                if (array_key_exists($tok, $tab_tok)) {
                    $tab_tok[$tok] += 1;
                } else {
                    $tab_tok[$tok] = 1;
                    $GLOBALS["nbMotsSave"] += 1;
                }
                $GLOBALS["nbMotsTotal"] += 1;
            }
            $tok = strtok($separateurs);
        }
        arsort($tab_tok);

        $all_tab[$fichier] = $tab_tok;
        unset($tab_tok);
        $tab_tok = array();
    }

    return $all_tab;
}



//sauvegarder les noms des fichiers dans la BDD
//ajoute dans une table le nomFichier et dateModif
function addFileDataToDatabase($tab_fichiers){  //tab_fichiers = array(nomFichier, timestamp),...
    $i=0;    
    foreach ($tab_fichiers as $fichier) {
        $fileName = array_keys($tab_fichiers)[$i];
        $sqlQuery = "INSERT INTO filelastupdate(nom_fichier, timestamp_modif) VALUES('$fileName', '$fichier')";
        $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
        $result->execute();
        $i += 1;
    }
    
}





//-----------------------------------------------------------//

//ajoute dans la BDD les données (mot, nbOccurence, nomFichier)
function addDataToDatabase($tab_fichiers, $all_tab){
    $i = 0;
    addFileDataToDatabase($tab_fichiers);
    
    foreach($all_tab as $tab){
        foreach($tab as $key=>$val){
            $fileName = array_keys($tab_fichiers)[$i];
            $sqlQuery = "INSERT INTO searchwordfile(mots, nb_occurence, nom_fichier) VALUES('$key', '$val', '$fileName')";
            $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
            $result->execute();
        }
        $i += 1;
    }
    //echo "Il y a " . $GLOBALS["nbMotsTotal"] . " mots au total dont " . $GLOBALS["nbMotsSave"] . " ajoutés a la Base de Données";
}


//permet de supprimer le contenu de toutes les tables 
function removeDataToDatabase(){
    $sqlQuery = "TRUNCATE TABLE searchwordfile";
    $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
    $result->execute();

    $sqlQuery = "TRUNCATE TABLE filelastupdate";
    $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
    $result->execute();
}


//mets à jour les données dans la bdd en fonction d'une modification sur un fichier, d'un ajout ou d'une suppression d'un fichier
function updateDataToDatabase(){
    $sqlQuery = "SELECT nom_fichier, timestamp_modif FROM filelastupdate";
    $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
    $result->execute();
    $fileLastUpdateDB = $result->fetchAll();

    //print_r($fileLastUpdateDB[0]);

    $allFilesFolder = addFileNameToArray(); //fichier present uniquement dans le dossiers contenant les textes sous la forme array(nomFichier=>timestamp) 
    print_r($allFilesFolder);
    $allFileDB = array();//fichiers present dans la bdd sous la forme d'array(nomFichier=>timestamp)
    foreach($fileLastUpdateDB as $key => $val){
        
        if(!in_array($val["nom_fichier"], array_keys($allFilesFolder))){
            echo $val["nom_fichier"] . " supprimé |||||||";
            $fileName = $val['nom_fichier'];
            $sqlQuery = "DELETE FROM searchwordfile WHERE nom_fichier='$fileName'"; //supprime toutes les lignes dont les fichiers ont été modifiés
            $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
            $result->execute();

            $sqlQuery = "DELETE FROM filelastupdate WHERE nom_fichier='$fileName'"; //supprime toutes les lignes dont les fichiers ont été modifiés
            $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
            $result->execute();
        }else{
        
            $allFileDB[$val["nom_fichier"]] = filemtime($GLOBALS["path"] . "/" . $val["nom_fichier"]); 
            if(in_array($val["nom_fichier"], array_keys($allFilesFolder))){   //verifie si le fichier présent dans la BDD est aussi présent dans les dossiers de l'outil
                $actualTimestampFile = filemtime($GLOBALS["path"] . "/" . $val["nom_fichier"]);
                if($actualTimestampFile != $val["timestamp_modif"]){   //si le fichier a été modifié, update la BDD
                    $all_tab = addFileWordOccurence(array($val["nom_fichier"]=>$actualTimestampFile),$GLOBALS["path"]);
                    updateSearchWordFile($all_tab, $actualTimestampFile, $GLOBALS["path"]);
                }
            }
        }

    }

    foreach($allFilesFolder as $file => $timestamp){
        if(!in_array($file, array_keys($allFileDB))){
            

            echo $file . "////";
            $timestampNewFile = filemtime($GLOBALS["path"] . "/" . $file);
            
            $sqlQuery = "INSERT INTO filelastupdate(nom_fichier, timestamp_modif) VALUES('$file', '$timestampNewFile')";  //ajoute les mots et nb_occurence des ficheirs modifiés
            $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
            $result->execute();

            $tabs = addFileWordOccurence(array($file=>$timestampNewFile));

            foreach($tabs as $key=>$array){
                foreach($array as $word=>$nbOccur){
                    $sqlQuery = "INSERT INTO searchwordfile(mots, nb_occurence, nom_fichier) VALUES('$word', '$nbOccur', '$key')";
                    $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
                    $result->execute();

                }
            }

        }
    }


}


function updateSearchWordFile($all_tab, $actualTimestampFile){
    $i = 0;    
    foreach($all_tab as $tab){
        $fileName = array_keys($all_tab)[$i];

        $sqlQuery = "DELETE FROM searchwordfile WHERE nom_fichier='$fileName'"; //supprime toutes les lignes dont les fichiers ont été modifiés
        $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
        $result->execute();
        
        $sqlQuery = "UPDATE filelastupdate SET timestamp_modif='$actualTimestampFile' WHERE nom_fichier='$fileName'"; //supprime toutes les lignes dont les fichiers ont été modifiés
        $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
        $result->execute();

        foreach($tab as $key=>$val){    
            $sqlQuery = "INSERT INTO searchwordfile(mots, nb_occurence, nom_fichier) VALUES('$key', '$val', '$fileName')";  //ajoute les mots et nb_occurence des ficheirs modifiés
            $result = $GLOBALS["mysqlClient"]->prepare($sqlQuery);
            $result->execute();
        }
        $i += 1;
    }
}


?>