<?php

include('ipprotection.php');

function clearDir($dossier) {
    $ouverture=@opendir($dossier);
    if (!$ouverture) return;
    while($fichier=readdir($ouverture)) {
        if ($fichier == '.' || $fichier == '..') continue;
            if (is_dir($dossier."/".$fichier)) {
                $r=clearDir($dossier."/".$fichier);
                if (!$r) return false;
            } else {
                $r = unlink($dossier."/".$fichier);
                if (!$r) return false;
            }
	}
    closedir($ouverture);
    $r=@rmdir($dossier);
    if (!$r) return false;
    return true;
}

$error = null;
if(isset($_POST['submit'])){
    $error = false;
    $trace = array(); $i = 0;
    foreach($_POST['dir'] as $dir){
        $trace[$i]['chemin'] = $dir; 
        $trace[$i]['etat'] = clearDir('../app/cache/'.$dir); 
        if(!$trace[$i]['etat']){
            $error = true;
        }
        $i++;
    }
}

?>
<html>
    <head>
        <title>Effacer le cache de symfony</title>
        <link href="css/main.css" rel="stylesheet" type="text/css" />
    </head>
    <body class="container">
        <h2>Effacer le cache de symfony</h2>
        <hr>
        <?php if($error){ ?>
        <p class="alert alert-danger">Une erreur est survenue.</p>
        <?php } ?>
        <pre>
        <?php 
        if($error !== null){
            foreach($trace as $operation){
                echo "\nSuppression de ".$operation['chemin'].' ................ '.($operation['etat']?'OK':'ERREUR');
            } 
            echo "\n";
        }?>
        </pre>
        <div class="space"></div>
        <form action="" method="post">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="dir[]" value="dev" id="env_dev" class="ace"/>
                    <span class="lbl"> dev</span>
                </label>
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="dir[]" value="dev_old" id="env_dev_old" class="ace" />
                    <span class="lbl"> dev_old</span>
                </label>
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="dir[]" value="prod" id="env_prod" class="ace" />
                    <span class="lbl"> prod</span>
                </label>
            </div>
            <hr>
            <p>
                <input type="submit" name="submit" value="Confirmer" class="btn btn-primary"/>
            </p>
        </form>
    </body>
</html>