<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo TITLE ?> | Tribu-Terre</title>

    <!-- Meta tags essentiels -->
    <meta property="og:title" content="<?php echo TITLE ?>">
    <meta property="og:image" content="/-images/imgLogoMini.png">
    <meta
            property="og:description"
            content="Tribu-Terre, Association des Étudiants en Sciences de l'Université d'Orléans."
    >
    <meta
            name="description"
            content="Tribu-Terre, Association des Étudiants en Sciences de l'Université d'Orléans."
    >
    <meta property="og:url" content="https://bde-tribu-terre.fr/">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Meta tags recommandés -->
    <meta property="og:site_name" content="BDE Tribu-Terre">
    <meta name="twitter:image:alt" content="Logo de Tribu-Terre">

    <!-- Meta tags recommandés -->
    <!-- <meta property="fb:app_id" content="your_app_id"> <- Il faut un token pour avoir l'ID de la page -->
    <meta name="twitter:site" content="@tributerre45">

    <!-- Bootstrap -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--<link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
            integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3"
            crossorigin="anonymous"
    >-->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

    <!-- Feuille de style générale -->
    <link rel="stylesheet" type="text/css" href="/vue/style.min.css">

    <!-- Fonctions Javascript -->
    <script src="/vue/script.min.js"></script>
</head>
<body>
<div class="page-complete">
    <header>
        <div class="jumbotron">
            <div class="container text-center">
                <h3>Tribu-Terre</h3>
                <h2>Interface Administrateur</h2>
                <?php if (defined('NOM_MEMBRE')) { echo '<h4>Connecté en tant que ' . NOM_MEMBRE . '</h4>'; } ?>
            </div>
        </div>

        <nav class="navbar">
        </nav>

        <div class="container">
            <div<?php echo empty($GLOBALS['messages']) ? ' style="display: none"' : '' ?>>
                <div class="row">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6">
                        <div class="well">
                            <h3 class="text-center">Message(s)</h3>
                            <hr>
                            <ul class="text-left">
                                <?php
                                foreach ($GLOBALS['messages'] as $arrMessage) {
                                    switch (substr($arrMessage[0], 0, 1)) {
                                        case '1':
                                            $color = '';
                                            break;
                                        case '2':
                                            $color = ' style="color: green;"';
                                            break;
                                        case '4':
                                            $color = ' style="color: orange"';
                                            break;
                                        case '5':
                                            $color = ' style="color: red"';
                                            break;
                                        case '6':
                                            $color = ' style="color: purple"';
                                            break;
                                        default:
                                            $color = ' style="color: blue"';
                                    }
                                    echo '<li' . $color . '>' . $arrMessage[0] . ' : ' . $arrMessage[1] . '</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-3"></div>
                </div>
            </div>
        </div>
    </header>
    <main>
        <?php require_once RACINE . 'vue/gabarits/' . GABARIT; ?>
    </main>
    <footer>
        <div class="container-fluid text-center">
            <div class="texte-footer">
                <p>Tribu-Terre 2022 | 1A Rue de la Férollerie, 45071, Orléans Cedex 2</p>
                <p><strong>Site Tribu-Terre ADMIN version <?php echo VERSION_SITE ?></strong></p>
                <p><small>Développé avec ❤️ par Anaël BARODINE</small></p>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
