<?php
if (strlen(session_id()) < 1) session_start();
const RACINE = './';
require_once(RACINE . '../connect.php');
require_once(RACINE . 'modele.php');
require_once(RACINE . 'vue/vue.php');
require_once(RACINE . 'controleur.php');

if (isset($_SESSION['membre'])) { // Un membre est actuellement connecté.
    if ( // Gabarit Ajouter Journal
        $form['_name'] == 'formAjouterJournal' &&
        $form['_submit'] == 'ajouterJournal'
    ) {
        CtlAjouterJournalExecuter(
            $form['titre'],
            $form['mois'],
            $form['annee'],
            'formAjouterJournal_fichierPDF'
        );
    } elseif ( // Gabarit Supprimer Journal
        $form['_name'] == 'formSupprimerJournal' &&
        $form['_submit'] == 'supprimer'
    ) {
        CtlSupprimerJournalExecuter(
            $form['id']
        );
    }  elseif ( // Gabarit Ajouter Lien Pratique
        $form['_name'] == 'formAjouterLienPratique' &&
        $form['_submit'] == 'ajouter'
    ) {
        CtlAjouterLienPratiqueExecuter(
            $form['titre'],
            $form['url']
        );
    } elseif ( // Gabarit Supprimer Lien Pratique
        $form['_name'] == 'formSupprimerLienPratique' &&
        $form['_submit'] == 'supprimer'
    ) {
        CtlSupprimerLienPratiqueExecuter(
            $form['id']
        );
    } elseif ( // Gabarit Menu
        $form['_name'] == 'formJournal' &&
        $form['_submit'] == 'supprimerJournalMenu'
    ) {
        CtlSupprimerJournal();
    } elseif (
        $form['_name'] == 'formLiensPratiques' &&
        $form['_submit'] == 'ajouterLienPratiqueMenu'
    ) {
        CtlAjouterLienPratique();
    } elseif (
        $form['_name'] == 'formLiensPratiques' &&
        $form['_submit'] == 'supprimerLienPratiqueMenu'
    ) {
        CtlSupprimerLienPratique();
    } elseif (
        $form['_name'] == 'formLog' &&
        $form['_submit'] == 'afficherLog'
    ) {
        CtlLog();
    } elseif (
        $form['_name'] == 'formDeconnexion' &&
        $form['_submit'] == 'deconnexion'
    ) {
        CtlDeconnexion();
    } // Globaux : apparaissent dans plusieurs gabarits
    elseif (
        $form['_name'] == 'formRetourMenu' &&
        $form['_submit'] == 'retourMenu'
    ) {
        CtlMenu();
    } else { // Si aucun formulaire d'envoyé...
        CtlMenu();
    }
} else {
    // Gabarit Connexion
    if (
        $form['_name'] == 'formConnexion' &&
        $form['_submit'] == 'seConnecter'
    ) {
        CtlVerifConnexion(
            $form['login'],
            $form['mdp']
        );
    } elseif (isset($_POST)) {
        // Si on accède à admin.php suite à un formulaire POST, et qu'il n'y a pas de session, c'est que la session
        // a expiré.
        ajouterMessage(100, 'La session a expiré.');
        CtlConnexion();
    } else {
        CtlConnexion();
    }
}
