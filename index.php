<?php

$pathParent = dirname(__FILE__);
include $pathParent . "/credentials/credentials.php";
require $pathParent . "/save_bdd.php";

header('Content-Type: text/html; charset=utf-8');

$pathTextFile = $pathParent . "/fichiers_txt";

?>
<!DOCTYPE html>
<script src="searchbar.js" type="text/javascript"></script>
<link rel="stylesheet" href="style/searchbar.css">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<button onclick="addDataBDD()">Ajouter données BDD</button>
<button onclick="removeDataBDD()">Supprimer données BDD</button>
<button onclick="updateDataBDD()">Vérifier données BDD</button>

<h3 style="text-align: center;">Outil de recherche</h3>
<form method="POST" action="index.php" id="rechercheMot" style="text-align: center;">
    <span><input type="text" name="searchText" required="required">
    <input type=submit value="Rechercher" name="searchButton">
</form>




<?php

$tab_fichiers = addFileNameToArray(); // array (nomFichier => timestamp) de tout les fichiers

$all_tab = addFileWordOccurence($tab_fichiers); //array (nomFichier => array(mots=>nbOccurence))
//updateDataToDatabase();


if(isset($_POST['action']) && $_POST['action'] == 'addSuccess') {
    addDataToDatabase($tab_fichiers, $all_tab);
}
if(isset($_POST['action']) && $_POST['action'] == 'removeSuccess') {
    removeDataToDatabase();
}
if(isset($_POST['action']) && $_POST['action'] == 'updateSuccess') {
    updateDataToDatabase();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST)) {
    try {
        $wordToSearch = strtolower($_POST["searchText"]);
        $mysqlClient = new PDO($dbname, $login, $password);

        $sqlQuery = "SELECT * FROM searchwordfile WHERE mots='$wordToSearch' ORDER BY nb_occurence DESC";
        $result = $mysqlClient->prepare($sqlQuery);
        $result->execute();
        $searchWordFile = $result->fetchAll();

        ?>
        <p style="text-align: center;">'<?php echo $wordToSearch; ?>' est présent dans <?php echo count($searchWordFile); ?> fichier(s).</p>
        <?php

        foreach ($searchWordFile as $tab => $val) {
            $urlTxt = $pathTextFile . "/" . $val["nom_fichier"]; ?>
            <ul>
                <a href="#">
                    <li style="text-align: center;" id="<?php echo $val["nom_fichier"]; ?>" onclick="test(this)">
                        <?php echo "[" . $val["nom_fichier"] . "] : " . $val["nb_occurence"]; ?>
                    </li>
                </a>
            </ul>

<?php

        }

        unset($_POST);
        unset($_REQUEST);
    } catch (Exception $e) {
        // En cas d'erreur, on affiche un message et on arrête tout
        die('Erreur : ' . $e->getMessage());
    }
}

?>