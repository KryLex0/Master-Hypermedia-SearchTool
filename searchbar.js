function addDataBDD(str) {
    $.ajax({
        type: "POST",
        url: "index.php",
        data:{action:'addSuccess'},
        success:function(html) {
            alert(str  + " Les données des différents fichiers ont bien été sauvegardé dans la base de données !")
            //console.log(html);
        }
    });
}

function removeDataBDD() {
    $.ajax({
        type: "POST",
        url: "index.php",
        data:{action:'removeSuccess'},
        success:function(html) {
            alert("Les données présentes dans la BDD ont bien été supprimés !")
            //console.log(html);
        }
    });
}

function updateDataBDD() {
    $.ajax({
        type: "POST",
        url: "index.php",
        data:{action:'updateSuccess'},
        success:function(html) {
            alert("Les données présentes dans la BDD ont bien été mise à jour !")
            //console.log(html);
        }
    });
}

function test(urlTxt) {
    $.ajax({
        type: "POST",
        url: "index.php",
        data:{action:'updateSuccess'},
        success:function(html) {
            var urlSite = window.parent.location.href
            urlSite = urlSite.slice(0, urlSite.lastIndexOf('/'));
            urlSite = urlSite + "/fichiers_txt/" + urlTxt.id
            window.open(urlSite, "_blank")//window.location = urlSite
            //console.log(urlSite)//urlTxt.id)
            //console.log(html);
        }
    });
}