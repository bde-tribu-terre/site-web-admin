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

function genererSiteMap($arrayLiens, $destination) {
    $sitemap = simplexml_load_string(<<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
</urlset>
EOT
    );
    if (count($arrayLiens) == 0) {
        $newURI = $sitemap->addChild('url');
        $newURI->addChild('loc', 'https://bde-tribu-terre.fr');
        $newURI->addChild('lastmod', date('Y-m-d'));
    } else {
        foreach ($arrayLiens as $lien) {
            $newURI = $sitemap->addChild('url');
            $newURI->addChild('loc', $lien);
            $newURI->addChild('lastmod', date('Y-m-d\TH:i:sP'));
        }
    }
    $sitemap->asXML($destination);
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

function MdlInfosMembre($id) {
    ajouterRetourModele(
        'membre',
        requeteSQL(
            "
            SELECT
                idMembre AS id,
                loginMembre AS login,
                prenomMembre AS prenom,
                nomMembre AS nom
            FROM
                website_membres
            WHERE
                idMembre=:idMembre
            ",
            array(
                [':idMembre', $id, 'INT']
            ),
            1
        )
    );
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

function MdlLogApi($status, $message) {
    MdlLog('API', $status, $message);
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
        $newName = preg_replace('/[\W]/', '', $titre) . '-' . time() . '.pdf'; # time() => aucun doublon imaginable.
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
    MdlReloadSitemapJournaux();
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
    MdlReloadSitemapJournaux();
    MdlLogAdmin('OK', 'Suppression d\'un journal (ID : ' . $id . ').');
}

function MdlReloadSitemapJournaux() {
    $journaux = requeteSQL(
        "
            SELECT
                pdfJournal AS pdf
            FROM
                website_journaux
            ORDER BY
                dateJournal
                DESC
            "
    );

    $arrayJournaux = array();
    foreach ($journaux as $journal) {
        array_push($arrayJournaux, 'https://bde-tribu-terre.fr/' . $journal['pdf']);
    }

    genererSiteMap($arrayJournaux, RACINE . '../default/journaux/sitemap-journaux.xml');
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

########################################################################################################################
# Parrainage                                                                                                           #
########################################################################################################################
function MdlRecupBinomesParrainages($email) {
    ajouterRetourModele(
        'parrainage',
        requeteSQL(
            "
                SELECT
                    p0.email AS p0email,
                    p0.nom AS p0nom,
                    p0.parrain AS p0parrain,
                    p1.email AS p1email,
                    p1.nom AS p1nom,
                    p1.parrain AS p1parrain,
                    p2.email AS p2email,
                    p2.nom AS p2nom,
                    p2.parrain AS p2parrain,
                    p0.groupe AS groupe
                FROM
                    website_parrainage p0
                        JOIN
                    website_parrainage p1
                        JOIN
                    website_parrainage p2
                        ON
                            p1.email = p0.binome1
                                AND
                            p2.email = p0.binome2
                WHERE
                      p0.email = :email
                ",
            array(
                [':email', $email, 'STR']
            ),
            1
        )
    );
}

########################################################################################################################
# Salles (API)                                                                                                         #
########################################################################################################################
function MdlRechercherSalle($nomSalle): void {
    ajouterRetourModele(
        'salles',
        requeteSQL(
            "
                SELECT
                    api_universite_salles.id AS id,
                    api_universite_salles.nom AS nom,
                    api_universite_groupes_salles.nom AS nomGroupe,
                    api_universite_batiments.id AS idBatiment,
                    api_universite_batiments.libelleLong AS nomBatiment,
                    api_universite_groupes_batiments.id AS codeComposante,
                    api_universite_groupes_batiments.titre AS titreComposante
                FROM
                    api_universite_salles
                        JOIN
                    api_universite_groupes_salles
                        ON
                            api_universite_groupes_salles.id = api_universite_salles.idGroupe
                        JOIN
                    api_universite_batiments
                        ON
                            api_universite_batiments.id = api_universite_groupes_salles.idBatiment
                        JOIN
                    api_universite_groupes_batiments
                        ON
                            api_universite_groupes_batiments.id = api_universite_batiments.idGroupe
                WHERE
                    LOWER(api_universite_salles.nom)
                        LIKE
                    :nomSalle
                ",
            array(
                [':nomSalle', '%' . $nomSalle . '%', 'STR']
            )
        )
    );
}

function MdlApiGetBatiments(): void {
    $prepare = getConnect()->prepare(
        "
        SELECT
            api_universite_batiments.id AS id,
            legende,
            titre,
            couleurR,
            couleurG,
            couleurB,
            libelleCourt,
            libelleLong,
            idGroupe
        FROM
            api_universite_groupes_batiments
                JOIN
            api_universite_batiments
                ON
                    api_universite_groupes_batiments.id = api_universite_batiments.idGroupe;
        "
    );
    $prepare->execute();
    $batiments = $prepare->fetchAll();
    $prepare->closeCursor();

    $batimentsJson = [];
    foreach ($batiments as $batiment) {
        if (!isset($batimentsJson[$batiment['idGroupe']])) {
            $batimentsJson[$batiment['idGroupe']] = [
                'legende' => html_entity_decode($batiment['legende'], ENT_QUOTES),
                'titre' => html_entity_decode($batiment['titre'], ENT_QUOTES),
                'couleur' => '#' . str_pad(
                    dechex(
                        $batiment['couleurR'] * 256 * 256 +
                        $batiment['couleurG'] * 256 +
                        $batiment['couleurB']
                    ),
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
                'batiments' => []
            ];
        }
        array_push(
            $batimentsJson[$batiment['idGroupe']]['batiments'],
            [
                'id' => $batiment['id'],
                'libelle_court' => html_entity_decode($batiment['libelleCourt'], ENT_QUOTES),
                'libelle_long' => html_entity_decode($batiment['libelleLong'], ENT_QUOTES)
            ]
        );
    }

    ajouterRetourModele(
        'batiments',
        $batimentsJson
    );
}

function MdlApiGetSalles($idBatiment): void {
    $prepare = getConnect()->prepare(
        "
        SELECT
            api_universite_groupes_salles.nom AS nomGroupe,
            idGroupe, api_universite_salles.nom AS nom
        FROM
            api_universite_groupes_salles
                JOIN
            api_universite_salles
                ON
                    api_universite_groupes_salles.id = api_universite_salles.idGroupe
        WHERE idBatiment=:idBatiment;
        "
    );
    $prepare->bindValue(':idBatiment', $idBatiment, PDO::PARAM_INT);
    $prepare->execute();
    $salles = $prepare->fetchAll();
    $prepare->closeCursor();

    $groupes = [];
    foreach ($salles as $salle) {
        if (!isset($groupes[$salle['idGroupe']])) {
            $groupes[$salle['idGroupe']] = [
                'nom' => html_entity_decode($salle['nomGroupe'], ENT_QUOTES),
                'salles' => []
            ];
        }
        array_push(
            $groupes[$salle['idGroupe']]['salles'], html_entity_decode($salle['nom'], ENT_QUOTES));
    }

    $groupesJson = [];

    foreach ($groupes as $groupe) {
        array_push($groupesJson, $groupe);
    }

    ajouterRetourModele(
        'salles',
        $groupesJson
    );
}

function MdlApiGetGeoJson($idBatiment): void {
    $prepare = getConnect()->prepare(
        "
        SELECT
            carved,
            idBatiment,
            idPolygon,
            c1,
            c2
        FROM
            api_universite_polygons
                JOIN
            api_universite_coordonnees
                ON
                    api_universite_polygons.id = api_universite_coordonnees.idPolygon
        WHERE idBatiment=:idBatiment;
        "
    );
    $prepare->bindValue(':idBatiment', $idBatiment, PDO::PARAM_INT);
    $prepare->execute();
    $coordinates = $prepare->fetchAll();
    $prepare->closeCursor();

    $polygons = [];
    foreach ($coordinates as $coordinate) {
        if (!isset($polygons[$coordinate['idPolygon']])) {
            $polygons[$coordinate['idPolygon']] = [
                'carved' => $coordinate['carved'],
                'coords' => []
            ];
        }
        array_push(
            $polygons[$coordinate['idPolygon']]['coords'],
            [
                floatval($coordinate['c1']),
                floatval($coordinate['c2'])
            ]
        );
    }

    $geoJson = [
        'type' => 'MultiPolygon',
        'coordinates' => [[],[]]
    ];

    foreach ($polygons as $polygon) {
        array_push($geoJson['coordinates'][$polygon['carved']], $polygon['coords']);
    }

    ajouterRetourModele(
        'geoJson',
        $geoJson
    );
}
