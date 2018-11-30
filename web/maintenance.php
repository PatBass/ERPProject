<?php
// web/maintenance.php
$h = false;
if (isset($_GET['h'])){
    $h = strip_tags($_GET['h']);
}
?>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>KGestion en maintenance</title>
        <link href="css/main.css" rel="stylesheet" type="text/css" />
    </head>
    <body class="navbar-fixed breadcrumbs-fixed">
        <header id="navbar" class="navbar navbar-default navbar-fixed-top">
            <div id="navbar-container" class="navbar-container">
                <h1 class="navbar-header pull-left">
                    <a href="/kgestion/web/" class="navbar-brand" title="Revenir à l'accueil">
                        <i class="icon-rocket"></i>
                        KGestion
                        <small>
                            <i class="icon-double-angle-right"></i>
                            lʼapplication de gestion de consultations de voyance
                        </small>
                    </a>
                </h1>
            </div>
        </header>
        <section id="main-container" class="main-container">
            <div class="main-container-inner">
                <div class="row">
                    <div class="col-sm-10 col-sm-offset-1 center bigger-180">
                        <h1 class="red well">
                            <i class="icon-wrench icon-animated-wrench"></i>
                            KGestion est en maintenance !
                        </h1>
                        <p>
                            <br>Une mise à jour est en cours, lʼapplication est temporairement indisponible.<br>
                        </p>
                        <?php if ($h){ ?>
                        <p>
                            Retour prévu aujourd'hui à <?php echo $h;?>.
                        </p>
                        <?php } ?>
                    </div>
                </div>
            </div><!-- main-container-inner -->
            <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
		<i class="icon-double-angle-up icon-only bigger-110"></i>
            </a>
        </section><!-- main-container -->
    </body>
</html>