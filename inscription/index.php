<?php
const RACINE = '../';
require_once(RACINE . '../connect.php');
require_once(RACINE . 'modele.php');
require_once(RACINE . 'vue/vue.php');
require_once(RACINE . 'controleur.php');
if (
    $form['_name'] == 'formInscription' &&
    $form['_submit'] == 'inscription'
) {
    CtlInscriptionExecuter(
        $form['cleInscription'],
        $form['prenom'],
        $form['nom'],
        $form['login'],
        $form['mdp']
    );
} else {
    CtlInscription();
}
