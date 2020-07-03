<!DOCTYPE html>
<html>
    <head>
        <!--Le titre de ma page, s'affiche dans le navigateur-->
        <link rel="shortcut icon" type="image/x-image" href="/img/logo.ico"/>
        <title>
            Création de sites internet
        </title>
        <!--Les metas-->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!--Les polices utilisées dans la page-->
        <link href="https://fonts.googleapis.com/css?family=Crimson+Text|Manuale|Satisfy&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <!--Les feuilles de style externes-->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
        <link rel="" href="">
        <!--Les feuilles de style propre à la landing page-->
        <link rel="stylesheet"type="text/css" href="./css/style.css">
    </head> <!-- Fin du Head -->

    <?php define('activepage', $_SERVER['PHP_SELF']); ?>

    <header>
        <div class="logo">
            <img alt="Création de site internet symfony 5 pour particuliers et professionnel." height="85" src="/img/logo.jpg">
        </div>
        <div class="navbar-top">
            <h2>
                <a class="<?php if(activepage=='/index.php')echo 'active'; ?>" href="index.php">Accueil</a>
            </h2>
            <h2 hidden>
                <a class="<?php if(activepage=='/offers.php')echo 'active'; ?>" href="offers.php">Offres</a>
            </h2>
            <h2>
                <a class="<?php if(activepage=='/profil.php')echo 'active'; ?>" href="profil.php">Profil</a>
            </h2>
            <h2>
                <a class="<?php if(activepage=='/contact.php')echo 'active'; ?>" href="contact.php">Contacter</a>
            </h2>
        </div>
        <hr>
        <h1>
            Création de sites internet
        </h1>
    </header>
</html>