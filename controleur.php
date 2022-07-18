<?php
########################################################################################################################
# Vérification du protocole (les deux fonctionnent mais on veut forcer le passage par HTTPS)                           #
########################################################################################################################
if($_SERVER["HTTPS"] != "on") {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

########################################################################################################################
# Initialisation des tableaux globaux                                                                                  #
########################################################################################################################
# Messages
$GLOBALS['messages'] = array();

# Retours d'appels de fonctions du modèle
$GLOBALS['retoursModele'] = array();

########################################################################################################################
# Initialisation du tableau formulaire                                                                                 #
########################################################################################################################
$form = array();
foreach ($_POST as $keyInput => $valInput) {
    $arrayInput = explode('_', $keyInput);
    if (isset($form['_name']) && $form['_name'] != $arrayInput[0]) {
        ajouterMessage(502, 'Attention : la convention d\'attribut "name" des inputs n\'est pas respectée.');
    } else {
        $form['_name'] = $arrayInput[0];
    }
    if (isset($arrayInput[2]) && $arrayInput[2] == 'submit') {
        $form['_submit'] = $arrayInput['1'];
    } else {
        $form[explode('_', $keyInput)[1]] = $valInput;
    }
}

if (count($form) == 0) {
    $form['_name'] = NULL;
    $form['_submit'] = NULL;
}

########################################################################################################################
# DEBUG pour pendant le développement                                                                                  #
# /!\ Tout ce qui suit doit être en commentaire dans la version définitive du site /!\                                     #
########################################################################################################################
# Visualisation du formulaire POST
//ajouterMessage(0, print_r($form, true));

########################################################################################################################
# Fonctions d'ajout dans les tableaux globaux (pour la lisibilité)                                                     #
########################################################################################################################
function ajouterMessage($code, $texte) {
    $GLOBALS['messages'][] = [$code, htmlentities($texte, ENT_QUOTES, 'UTF-8')];
}

function ajouterRetourModele($cle, $resultats) {
    $GLOBALS['retoursModele'][$cle] = $resultats;
}

########################################################################################################################
# Admin                                                                                                                #
########################################################################################################################
# Connexion
function CtlConnexion() {
    afficherConnexion();
}

function CtlVerifConnexion($login, $mdp) {
    try {
        if (
            !empty($login) &&
            !empty($mdp)
        ) {
            $membre = MdlVerifConnexion($login, $mdp);
            if ($membre != false) {
                $_SESSION['membre'] = $membre;
                CtlMenu();
            } else {
                throw new Exception('Login ou mot de passe invalide.', 401);
            }
        } else {
            throw new Exception('Veuillez remplir tous les champs.', 400);
        }
    } catch (Exception $e) {
        ajouterMessage($e->getCode(), $e->getMessage());
        CtlConnexion();
    }
} // CtlConnexionExecuter en suivant le reste des standards.

# Menu
function CtlMenu() {
    afficherMenu();
}

function CtlDeconnexion() {
    try {
        $_SESSION = array();
        if (isset($COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-42000, '/');
        }
        session_destroy();
        ajouterMessage(200, 'Session déconnectée avec succès.');
        CtlConnexion();
    } catch (Exception $e) {
        ajouterMessage($e->getCode(), $e->getMessage());
        CtlConnexion();
    }
}

# Journaux
function CtlAjouterJournal() {
    afficherAjouterJournal();
}

function CtlAjouterJournalExecuter($titre, $mois, $annee, $fileImput) {
    try {
        if (
            !empty($titre) &&
            !empty($mois) &&
            !empty($annee) &&
            !empty($_FILES[$fileImput]['name'])
        ) {
            MdlAjouterJournal(
                RACINE . 'journaux/',
                $titre,
                $mois,
                $annee,
                $fileImput
            );
            CtlAjouterJournal();
        } else {
            throw new Exception('Veuillez remplir tous les champs.', 400);
        }
    } catch (Exception $e) {
        ajouterMessage($e->getCode(), $e->getMessage());
        CtlAjouterJournal();
    }
}

function CtlSupprimerJournal() {
    MdlJournauxTous(NULL);
    afficherSupprimerJournal();
}

function CtlSupprimerJournalExecuter($id) {
    try {
        if (
            !empty($id)
        ) {
            MdlSupprimerJournal(
                RACINE . 'journaux/',
                $id
            );
            CtlSupprimerJournal();
        } else {
            throw new Exception('Veuillez sélectionner un journal.', 400);
        }
    } catch (Exception $e) {
        ajouterMessage($e->getCode(), $e->getMessage());
        CtlSupprimerJournal();
    }
}

# Liens
function CtlAjouterLienPratique() {
    afficherAjouterLienPratique();
}

function CtlAjouterLienPratiqueExecuter($titre, $url) {
    try {
        if (
            !empty($titre) &&
            !empty($url)
        ) {
            MdlAjouterLienPratique(
                $titre,
                $url
            );
            CtlAjouterLienPratique();
        } else {
            throw new Exception('Veuillez remplir tous les champs.', 400);
        }
    } catch (Exception $e) {
        ajouterMessage($e->getCode(), $e->getMessage());
        CtlAjouterLienPratique();
    }
}

function CtlSupprimerLienPratique() {
    MdlLiensPratiquesTous();
    afficherSupprimerLienPratique();
}

function CtlSupprimerLienPratiqueExecuter($id) {
    try {
        if (
            !empty($id)
        ) {
            MdlSupprimerLienPratique(
                $id
            );
            CtlSupprimerLienPratique();
        } else {
            throw new Exception('Veuillez remplir tous les champs.', 400);
        }
    } catch (Exception $e) {
        ajouterMessage($e->getCode(), $e->getMessage());
        CtlSupprimerLienPratique();
    }
}

# Log
function CtlLog() {
    MdlGetLog();
    afficherLog();
}

########################################################################################################################
# Admin - Inscription                                                                                                  #
########################################################################################################################
function CtlInscription() {
    afficherInscription();
}

function CtlInscriptionExecuter($cleInscription, $prenom, $nom, $login, $mdp) {
    try {
        if (
            !empty($cleInscription) &&
            !empty($prenom) &&
            !empty($nom) &&
            !empty($login) &&
            !empty($mdp)
        ) {
            if (MdlCleExiste($cleInscription)) { // Si trouvée, alors elle est détruite.
                MdlAjouterMembre(
                    $prenom,
                    $nom,
                    $login,
                    $mdp
                );
                CtlInscription();
            } else {
                throw new Exception('La clé d\'inscription saisie n\'existe pas.', 402);
            }
        } else {
            throw new Exception('Veuillez remplir tous les champs.', 400);
        }
    } catch (Exception $e) {
        ajouterMessage($e->getCode(), $e->getMessage());
        CtlInscription();
    }
}