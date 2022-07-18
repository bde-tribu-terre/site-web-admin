<?php
########################################################################################################################
# Fonction techniques                                                                                                  #
########################################################################################################################
function MET_SQLLigneUnique($object) {
    if ($object) {
        $array = array();
        foreach ($object as $key => $val) {
            $array[$key] = is_string($val) ? htmlentities($val, ENT_QUOTES, 'UTF-8') : $val;
        }
        return $array;
    }
    return $object;
}

function MET_SQLLignesMultiples($arrayObject) {
    $array = array();
    foreach ($arrayObject as $objectKey => $objectValue) {
        $array[$objectKey] = MET_SQLLigneUnique($objectValue);
    }
    return $array;
}

function requeteSQL($requete, $variables = array(), $nbResultats = 2, $codeMessageSucces = NULL, $texteMessageSucces = NULL) {
    try {
        $connexion = getConnect();
        $prepare = $connexion->prepare($requete);
        foreach ($variables as $variable) {
            $data_type = $variable[2] == 'INT' ? PDO::PARAM_INT : PDO::PARAM_STR;
            $prepare->bindValue($variable[0], $variable[1], $data_type);
        }
        $prepare->execute();
        switch ($nbResultats) {
            case 0:
                $retour = NULL;
                break;
            case 1:
                $retour = MET_SQLLigneUnique($prepare->fetch());
                break;
            default:
                $retour = MET_SQLLignesMultiples($prepare->fetchAll());
        }
        $prepare->closeCursor();
        if ($codeMessageSucces && $texteMessageSucces) {
            ajouterMessage($codeMessageSucces, $texteMessageSucces);
        }
        return $retour;
    } catch (Exception $e) {
        ajouterMessage(600, $e->getMessage());
        switch ($nbResultats) {
            case 0:
            case 1:
                return NULL;
            default:
                return array();
        }
    }
}

########################################################################################################################
# Membres                                                                                                              #
########################################################################################################################
function MdlVerifConnexion($login, $mdp) {
    $membre = requeteSQL(
        "
        SELECT
            idMembre AS id,
            loginMembre AS login,
            prenomMembre AS prenom,
            nomMembre AS nom,
            mdpHashMembre AS mdpHash,
            mdpSaltMembre AS mdpSalt
        FROM
            website_membres
        WHERE
            loginMembre=:login
        ",
        array(
            [':login', $login, 'STR']
        ),
        1
    );
    if ($membre) {
        // https://youtu.be/8ZtInClXe1Q pour des explications.
        $mdpSaisieHash = hash('whirlpool', html_entity_decode($membre['mdpSalt'] . $mdp, ENT_QUOTES));
        if ($membre['mdpHash'] == $mdpSaisieHash) {
            return $membre;
        }
    }
    return false;
}

function MdlAjouterMembre($prenom, $nom, $login, $mdp) {
    $salt = '';
    for ($i = 0; $i < 32; $i++) {
        $salt .= chr(rand(33, 126));
    }
    $mdpHash = hash('whirlpool', $salt . $mdp);

    requeteSQL(
        "
        INSERT INTO
            website_membres
        VALUES
            (
                0,
                :login,
                :mdpHash,
                :mdpSalt,
                :prenom,
                :nom
            )
        ",
        array(
            [':login', $login, 'STR'],
            [':mdpHash', $mdpHash, 'STR'],
            [':mdpSalt', $salt, 'STR'],
            [':prenom', $prenom, 'STR'],
            [':nom', $nom, 'STR']
        ),
        0,
        201,
        'L\'inscription a bien été enregistrée.'
    );
    MdlLogAdmin('OK', $prenom . ' ' . $nom . ' s\'est inscrit(e) avec succès sous le login "' . $login . '".');
}

########################################################################################################################
# Clés d'inscription                                                                                                   #
########################################################################################################################
function MdlCleExiste($cle): bool {
    $cle = requeteSQL(
        "
        SELECT
            idCleInscription AS id,
            strCleInscription AS str
        FROM
            website_cles_inscription
        WHERE
            strCleInscription=:cle
        ",
        array(
            [':cle', $cle, 'STR']
        ),
        1
    );
    if ($cle) {
        requeteSQL(
            "
            DELETE FROM
                website_cles_inscription
            WHERE
                idCleInscription=:id
            ",
            array(
                [':id', $cle['id'], 'INT']
            ),
            false,
            201,
            'La clé d\'inscription "' . $cle['str'] . '" a été détruite avec succès.'
        );
        MdlLogAdmin('OK', 'Clé d\'inscription "' . $cle['str'] . '" détruite.');
        return true;
    }
    return false;
}

########################################################################################################################
# Log des actions                                                                                                      #
########################################################################################################################
function MdlGetLog() {
    ajouterRetourModele(
        'log',
        requeteSQL(
            "
            SELECT
                idLog AS idLog,
                dateLog AS date,
                messageLog AS description
            FROM
                website_log
            ORDER BY
                dateLog
                DESC
            "
        )
    );
}

function MdlLogAdmin($status, $message) {
    MdlLog('ADMIN', $status, (isset($_SESSION['membre']) ? html_entity_decode($_SESSION['membre']['prenom'], ENT_QUOTES) . ' modele.php' . html_entity_decode($_SESSION['membre']['nom'], ENT_QUOTES) . ' (' . $_SESSION['membre']['id'] . ')' : 'UNKNOWN') . ': ' . $message);
}

function MdlLog($context, $status, $message) {
    $timestamp = time();
    $dt = new DateTime('now', new DateTimeZone('Europe/Paris'));
    $dt->setTimestamp($timestamp);
    requeteSQL(
        "
        INSERT INTO
            website_log
        VALUES
            (
                0,
                :dateLog,
                :messageLog
            )
        ",
        array(
            [':dateLog', $dt->format('Y-m-d H-i-s'), 'STR'],
            [':messageLog', '[' . $context . ']' . '[' . $status . ']' . '[' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '] ' . $message, 'STR']
        ),
        0
    );
}

########################################################################################################################
# Journaux                                                                                                             #
########################################################################################################################
function MdlJournauxTous($maxi = NULL) {
    ajouterRetourModele(
        'journaux',
        requeteSQL(
            "
            SELECT
                idJournal AS id,
                titreJournal AS titre,
                dateJournal AS date,
                pdfJournal AS pdf
            FROM
                website_journaux
            ORDER BY
                dateJournal
                DESC
            " . ($maxi ? 'LIMIT ' . $maxi : '') . "
            "
        )
    );
}

function MdlAjouterJournal($rep, $titre, $mois, $annee, $fileImput) {
    try {
        # Enregistrement du fichier PDF.
        $newName = preg_replace('/\W/', '', $titre) . '-' . time() . '.pdf'; # time() => aucun doublon imaginable.
        move_uploaded_file(
            $_FILES[$fileImput]['tmp_name'],
            $rep . $newName
        );
    } catch (Exception $e) {
        MdlLogAdmin('ERROR', 'Erreur lors de l\'enregistrement d\'un journal : ' . $e->getMessage());
        ajouterMessage(501, 'Erreur lors de l\'enregistrement d\'un journal');
        return;
    }

    # Enregistrement des données dans la BDD SQL.
    requeteSQL(
        "
        INSERT INTO
            website_journaux
        VALUES
            (
                0,
                :titreJournal,
                :dateJournal,
                :pdfJournal
            )
        ",
        array(
            [':titreJournal', $titre, 'STR'],
            [':dateJournal', $annee . '-' . $mois . '-' . '01', 'STR'],
            [':pdfJournal', $newName, 'STR']
        ),
        0,
        201,
        'Le journal "' . $titre . '" a été ajouté avec succès !'
    );
    MdlLogAdmin('OK', 'Ajout du journal "' . $titre . '".');
}

function MdlSupprimerJournal($rep, $id) {
    try {
        # Suppression du journal
        $pdf = requeteSQL(
            "
            SELECT
                pdfJournal AS pdf
            FROM
                website_journaux
            WHERE
                idJournal=:idJournal
            ",
            array(
                [':idJournal', $id, 'INT']
            ),
            1
        )['pdf'];
        unlink($rep . $pdf);
    } catch (Exception $e) {
        MdlLogAdmin('ERROR', 'Erreur lors de la suppression d\'un journal : ' . $e->getMessage());
        ajouterMessage(501, 'Erreur lors de la suppression d\'un journal');
        return;
    }

    # Suppression des données
    requeteSQL(
        "
        DELETE FROM
            website_journaux
        WHERE
            idJournal=:idJournal
        ",
        array(
            [':idJournal', $id, 'INT']
        ),
        0,
        201,
        'Le journal a été supprimé avec succès !'
    );
    MdlLogAdmin('OK', 'Suppression d\'un journal (ID : ' . $id . ').');
}

########################################################################################################################
# Liens Pratiques                                                                                                      #
########################################################################################################################
function MdlLiensPratiquesTous() {
    ajouterRetourModele(
        'liensPratiques',
        requeteSQL(
            "
            SELECT
                idLien AS id,
                titreLien AS titre,
                urlLien AS url
            FROM
                website_liens
            ORDER BY
                idLien
            "
        )
    );
}

function MdlAjouterLienPratique($titre, $url) {
    requeteSQL(
        "
        INSERT INTO
            website_liens
        VALUES
            (
                0,
                :titreLien,
                :urlLien
            )
        ",
        array(
            [':titreLien', $titre, 'STR'],
            [':urlLien', $url, 'STR']
        ),
        0,
        201,
        'Le lien "' . $titre . '" vers "' . $url . '" a été ajouté avec succès !'
    );
    MdlLogAdmin('OK', 'Ajout du lien "' . $titre . '" vers "' . $url . '".');
}

function MdlSupprimerLienPratique($id) {
    requeteSQL(
        "
        DELETE FROM
            website_liens
        WHERE
              idLien=:idLien
        ",
        array(
            [':idLien', $id, 'INT']
        ),
        0,
        201,
        'Le lien a été supprimé avec succès !'
    );
    MdlLogAdmin('OK', 'Suppression d\'un lien (ID : ' . $id . ').');
}
