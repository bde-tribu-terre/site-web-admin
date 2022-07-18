<?php
#

########################################################################################################################
# Constantes Générales                                                                                                 #
########################################################################################################################
/**
 * Variable contenant la version actuelle du site indiquée dans le fichier ."vue/version.txt".
 */
define('VERSION_SITE', file_get_contents(RACINE . '/version.txt'));

########################################################################################################################
# Fonction d'Initialisation des Constantes Spécifiques & Affichage du Cadre                                            #
########################################################################################################################
/**
 * Exécute l'affichage de la page.
 * @param string $title
 * Le title qui sera affiché sur l'onglet du navigateur.
 * @param string $gabarit
 * Le nom du fichier gabarit qui sera utilisé (extension de fichier comprise). Exemple : 'accueil.php'.
 */
function afficherPage(string $title, string $gabarit) {
    define('NOM_MEMBRE', $_SESSION['membre']['prenom'] . ' <span class="pc">' . $_SESSION['membre']['nom'] . '</span>');
    define('MESSAGES', $GLOBALS['messages']);
    define('TITLE', $title);
    define('GABARIT', $gabarit);

    require_once(RACINE . 'vue/cadre.php');
}

########################################################################################################################
# Admin                                                                                                                #
########################################################################################################################
# Système
function afficherConnexion() {
    afficherPage('Accueil', 'connexion.php');
}

function afficherMenu() {
    afficherPage('Menu administrateur', 'menu.php');
}

# Journaux
function afficherAjouterJournal() {
    afficherPage('Ajouter un journal', 'ajouterJournal.php');
}

function afficherSupprimerJournal() {
    $journaux = '';
    foreach ($GLOBALS['retoursModele']['journaux'] as $journal) {
        $journaux .=
            '
            <option value="' . $journal['id'] . '">
                ' . $journal['titre'] . '
            </option>
            ';
    }
    define('JOURNAUX', $journaux);

    afficherPage('Supprimer un journal', 'supprimerJournal.php');
}

# Liens
function afficherAjouterLienPratique() {
    afficherPage('Ajouter un lien', 'ajouterLienPratique.php');
}

function afficherSupprimerLienPratique() {
    $liens = '';
    foreach ($GLOBALS['retoursModele']['liensPratiques'] as $lien) {
        $liens .=
            '
            <option value="' . $lien['id'] . '">
                ' . $lien['titre'] . '
            </option>
            ';
    }
    define('LIENS_PRATIQUES', $liens);

    afficherPage('Supprimer un lien', 'supprimerLienPratique.php');
}

# Log
function afficherLog() {
    $log = '';
    foreach ($GLOBALS['retoursModele']['log'] as $ligneLog) {
        $dateHeure = explode(' ', $ligneLog['date']);
        $date = $dateHeure[0];
        $heure = $dateHeure[1];
        $log .=
            '
            <tr>
            <th scope="row">' . $date . ' ' . $heure . '</th>
            <th>' . $ligneLog['description'] . '</th>
            </tr>
            ';
    }
    define('LOG', $log);

    afficherPage('Log', 'log.php');
}

########################################################################################################################
# Admin - Inscription                                                                                                  #
########################################################################################################################
function afficherInscription() {
    afficherPage('Inscription', 'inscription.php');
}
