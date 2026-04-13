<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module Gestion des Événements - CareMeal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0D1B2A;
            --accent-color: #FE5516;
            --text-color: #E8D9BB;
            --panel-bg: rgba(232, 217, 187, 0.05);
            --border-color: rgba(232, 217, 187, 0.1);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'DM Sans', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            scroll-behavior: smooth;
        }

        h1, h2, h3, h4, h5, h6, .brand-font {
            font-family: 'Archivo Black', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .text-accent {
            color: var(--accent-color);
        }

        /* Nav */
        nav {
            background-color: rgba(13, 27, 42, 0.95);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            width: 100%;
            z-index: 1000;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            color: var(--accent-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            white-space: nowrap;
        }

        .nav-links a {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--accent-color);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        section {
            padding: 4rem 0;
            border-bottom: 1px dashed var(--border-color);
        }

        /* Hero */
        .hero {
            text-align: center;
            padding: 6rem 0;
        }

        .hero-emoji {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: var(--accent-color);
        }

        .hero .subtitle {
            font-size: 1.5rem;
            opacity: 0.8;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .chips {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .chip {
            background-color: var(--panel-bg);
            border: 1px solid var(--accent-color);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        /* Card panels */
        .panel {
            background-color: var(--panel-bg);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            background-color: var(--panel-bg);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: rgba(254, 85, 22, 0.1);
            color: var(--accent-color);
            font-family: 'Archivo Black', sans-serif;
            font-size: 0.9rem;
        }

        tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-right: 5px;
        }

        .badge-pk { background-color: rgba(254, 85, 22, 0.2); border: 1px solid var(--accent-color); color: #fff; }
        .badge-fk { background-color: rgba(66, 153, 225, 0.2); border: 1px solid #4299e1; color: #fff; }
        .badge-nn { background-color: rgba(72, 187, 120, 0.2); border: 1px solid #48bb78; color: #fff; }

        /* Code Block */
        .code-container {
            position: relative;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--accent-color);
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-weight: bold;
            transition: opacity 0.3s;
        }

        .copy-btn:hover {
            opacity: 0.9;
        }

        pre {
            margin: 0;
            padding: 2rem;
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
            color: #a9b7c6;
            line-height: 1.5;
        }

        code .keyword { color: #cc7832; font-weight: bold; }
        code .string { color: #6a8759; }
        code .type { color: #9876aa; }
        code .comment { color: #808080; font-style: italic; }

        /* Visual Diagrams */
        .diagram-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 3rem;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            border: 1px dashed var(--accent-color);
            font-family: 'Archivo Black', sans-serif;
            font-size: 1.2rem;
            text-align: center;
            flex-wrap: wrap;
        }

        .entity {
            background-color: var(--accent-color);
            color: var(--bg-color);
            padding: 1rem 2rem;
            border-radius: 8px;
        }

        .relation {
            color: var(--accent-color);
            position: relative;
        }

        .arrow {
            display: inline-block;
            margin: 0 10px;
        }

        /* List styling */
        .custom-list {
            list-style-type: none;
            padding: 0;
        }

        .custom-list li {
            position: relative;
            padding-left: 30px;
            margin-bottom: 15px;
        }

        .custom-list li::before {
            content: "→";
            position: absolute;
            left: 0;
            color: var(--accent-color);
            font-weight: bold;
        }

        /* Speech */
        .speech-box {
            background-color: rgba(254, 85, 22, 0.05);
            border-left: 4px solid var(--accent-color);
            padding: 2rem;
            font-size: 1.1rem;
            line-height: 1.8;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .nav-links { display: none; }
            .diagram-box { flex-direction: column; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="nav-container">
            <a href="#" class="logo brand-font">CAREMEAL <span style="font-size: 0.8rem; padding: 3px 8px; background: var(--accent-color); color: #fff; border-radius: 4px; margin-left: 10px;">ÉVÉNEMENTS</span></a>
            <div class="nav-links">
                <a href="#intro">Intro</a>
                <a href="#droits">Droits</a>
                <a href="#mcd-mld">MCD / MLD</a>
                <a href="#donnees">Données</a>
                <a href="#sql">SQL</a>
                <a href="#usecase">Cas d'Usage</a>
                <a href="#speech">Oral</a>
            </div>
        </div>
    </nav>

    <div class="container">
        
        <!-- HEADER & HERO -->
        <section class="hero" id="hero">
            <div class="hero-emoji">à°Ã... ¸â€Å"â€ ¦</div>
            <h1>MODULE GESTION ÉVÉNEMENTS</h1>
            <div class="subtitle">ENTITÉS : EVENEMENT + PARTICIPATION</div>
            <p style="margin-bottom: 2rem;">Conà§u par <strong>Aziz</strong> | Groupe <strong>Néoclix</strong></p>
            <div class="chips">
                <span class="chip">Présentiel</span>
                <span class="chip">En ligne</span>
                <span class="chip">Anti-gaspi</span>
                <span class="chip">Validation Admin</span>
            </div>
        </section>

        <!-- INTRODUCTION -->
        <section id="intro">
            <h2><span class="text-accent">01.</span> Introduction au Module</h2>
            <div class="panel">
                <p>Ce module permet aux <strong>Partenaires</strong> (restaurants, cantines universitaires) et aux <strong>Administrateurs</strong> d'organiser des événements liés à  la lutte contre le gaspillage alimentaire (distributions gratuites, ateliers, conférences). Les <strong>Étudiants</strong> peuvent consulter ces événements et s'y inscrire selon les places disponibles.</p>
                <br>
                <h3>Les 3 Acteurs Principaux :</h3>
                <ul class="custom-list">
                    <li><strong>Étudiant :</strong> Cherche des événements pour récupérer des paniers ou apprendre, et s'y inscrit.</li>
                    <li><strong>Partenaire :</strong> Propose des événements de distribution ou de sensibilisation (soumis à  validation).</li>
                    <li><strong>Administrateur :</strong> Modère la plateforme, valide les propositions et supervise la participation.</li>
                </ul>
            </div>
        </section>

        <!-- TABLEAU DES DROITS -->
        <section id="droits">
            <h2><span class="text-accent">02.</span> Matrice des Droits</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Étudiant</th>
                            <th>Partenaire</th>
                            <th>Administrateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Créer un événement</strong></td>
                            <td>âÃ...'</td>
                            <td>âÃ...“â€ ¦ (Avec validation)</td>
                            <td>âÃ...“â€ ¦ (Direct)</td>
                        </tr>
                        <tr>
                            <td><strong>Modifier un événement</strong></td>
                            <td>âÃ...'</td>
                            <td>âÃ...“â€ ¦ (Seulement les siens)</td>
                            <td>âÃ...“â€ ¦ (Tous)</td>
                        </tr>
                        <tr>
                            <td><strong>Supprimer un événement</strong></td>
                            <td>âÃ...'</td>
                            <td>âÃ...'</td>
                            <td>âÃ...“â€ ¦ (Tous)</td>
                        </tr>
                        <tr>
                            <td><strong>Valider / Rejeter</strong></td>
                            <td>âÃ...'</td>
                            <td>âÃ...'</td>
                            <td>âÃ...“â€ ¦</td>
                        </tr>
                        <tr>
                            <td><strong>S'inscrire</strong></td>
                            <td>âÃ...“â€ ¦</td>
                            <td>âÃ...'</td>
                            <td>âÃ...'</td>
                        </tr>
                        <tr>
                            <td><strong>Voir les participants</strong></td>
                            <td>âÃ...'</td>
                            <td>âÃ...“â€ ¦ (Son événement)</td>
                            <td>âÃ...“â€ ¦ (Tous)</td>
                        </tr>
                        <tr>
                            <td><strong>Marquer présent/absent</strong></td>
                            <td>âÃ...'</td>
                            <td>âÃ...“â€ ¦ (Son événement)</td>
                            <td>âÃ...“â€ ¦ (Tous)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- DICTIONNAIRE, MCD, MLD -->
        <section id="mcd-mld">
            <h2><span class="text-accent">03.</span> Modélisation (MCD / MLD)</h2>
            
            <div class="panel">
                <h3>Schéma MCD Visuel</h3>
                <div class="diagram-box">
                    <div class="entity">EVENEMENT</div>
                    <div class="relation">
                        <span class="arrow">(1,1)</span>
                        ââ€ ââ€Å¡ ¬ââ€ ââ€Å¡ ¬ contient ââ€ ââ€Å¡ ¬ââ€ ââ€Å¡ ¬
                        <span class="arrow">(0,n)</span>
                    </div>
                    <div class="entity">PARTICIPATION</div>
                </div>
            </div>

            <div class="panel">
                <h3>Modèle Logique de Données (MLD)</h3>
                <p>
                    <strong>EVENEMENT</strong> (<span class="text-accent">id_evenement</span>, titre, description, date_evenement, heure_debut, heure_fin, type_evenement, lieu, lien_online, capacite_max, statut, createur_type, createur_id, statut_validation)
                </p>
                <p>
                    <strong>PARTICIPATION</strong> (<span class="text-accent">id_participation</span>, <em>#evenement_id</em>, <em>#etudiant_id</em>, date_inscription, statut)
                </p>
            </div>

            <h3>Dictionnaire des données - EVENEMENT</h3>
            <div style="overflow-x: auto; margin-bottom: 2rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Champ</th>
                            <th>Type</th>
                            <th>Description / Contraintes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>id_evenement</td>
                            <td><span class="type">INT</span></td>
                            <td><span class="badge badge-pk">PK</span> AUTO_INCREMENT</td>
                        </tr>
                        <tr>
                            <td>titre</td>
                            <td><span class="type">VARCHAR(100)</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span></td>
                        </tr>
                        <tr>
                            <td>description</td>
                            <td><span class="type">TEXT</span></td>
                            <td>NULL autorisé</td>
                        </tr>
                        <tr>
                            <td>date_evenement</td>
                            <td><span class="type">DATE</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span></td>
                        </tr>
                        <tr>
                            <td>heure_debut / heure_fin</td>
                            <td><span class="type">TIME</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span></td>
                        </tr>
                        <tr>
                            <td>type_evenement</td>
                            <td><span class="type">VARCHAR(20)</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span> DEFAULT 'Présentiel' (Présentiel / En ligne)</td>
                        </tr>
                        <tr>
                            <td>lieu / lien_online</td>
                            <td><span class="type">VARCHAR</span></td>
                            <td>Lieu si présentiel, lien si en ligne</td>
                        </tr>
                        <tr>
                            <td>capacite_max</td>
                            <td><span class="type">INT</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span></td>
                        </tr>
                        <tr>
                            <td>statut</td>
                            <td><span class="type">VARCHAR(20)</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span> DEFAULT 'Planifié' (Planifié/En cours/Terminé/Annulé)</td>
                        </tr>
                        <tr>
                            <td>createur_type / id</td>
                            <td><span class="type">VARCHAR</span> / <span class="type">INT</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span> (Admin ou Partenaire)</td>
                        </tr>
                        <tr>
                            <td>statut_validation</td>
                            <td><span class="type">VARCHAR(20)</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span> DEFAULT 'En attente' (En attente/Validé/Rejeté)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Dictionnaire des données - PARTICIPATION</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Champ</th>
                            <th>Type</th>
                            <th>Description / Contraintes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>id_participation</td>
                            <td><span class="type">INT</span></td>
                            <td><span class="badge badge-pk">PK</span> AUTO_INCREMENT</td>
                        </tr>
                        <tr>
                            <td>evenement_id</td>
                            <td><span class="type">INT</span></td>
                            <td><span class="badge badge-fk">FK</span> FOREIGN KEY vers EVENEMENT</td>
                        </tr>
                        <tr>
                            <td>etudiant_id</td>
                            <td><span class="type">INT</span></td>
                            <td><span class="badge badge-fk">FK</span> FOREIGN KEY vers ETUDIANT</td>
                        </tr>
                        <tr>
                            <td>date_inscription</td>
                            <td><span class="type">DATE</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span></td>
                        </tr>
                        <tr>
                            <td>statut</td>
                            <td><span class="type">VARCHAR(20)</span></td>
                            <td><span class="badge badge-nn">NOT NULL</span> DEFAULT 'Inscrit' (Inscrit/Présent/Absent/Annulé)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- JEU DE TEST & JOINTURE -->
        <section id="donnees">
            <h2><span class="text-accent">04.</span> Données de Test & Résultats</h2>
            
            <div class="panel">
                <h3>Table EVENEMENT</h3>
                <code><pre style="padding: 1rem; margin: 0; font-size: 0.9rem;">
1 | Journée Anti-Gaspi à°Ã... ¸Ã...'± | Présentiel | Campus El Manar | capacité 100 | Planifié | Admin | Validé
2 | Distribution Gratuite à°Ã... ¸± | Présentiel | Campus Manouba | capacité 50 | Planifié | Partenaire | Validé
3 | Conférence Anti-Gaspi à°Ã... ¸Ã... ½â€Å" | En ligne | zoom.us/j/123456 | capacité 200 | Terminé | Admin | Validé
4 | Atelier Cuisine à°Ã... ¸³ | Présentiel | Campus La Marsa | capacité 30 | En cours | Partenaire | En attente
                </pre></code>
            </div>

            <div class="panel">
                <h3>Table PARTICIPATION</h3>
                <code><pre style="padding: 1rem; margin: 0; font-size: 0.9rem;">
1 | événement 1 | ETU001 | 2026-04-10 | Inscrit
2 | événement 1 | ETU002 | 2026-04-11 | Inscrit
3 | événement 2 | ETU001 | 2026-04-12 | Inscrit
4 | événement 3 | ETU003 | 2026-03-05 | Présent
5 | événement 3 | ETU002 | 2026-03-04 | Absent
6 | événement 1 | ETU003 | 2026-04-13 | Inscrit
                </pre></code>
            </div>

            <h3>Résultat de la Jointure Principale</h3>
            <p style="margin-bottom: 10px; font-family: monospace; color: #a9b7c6; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 4px;">
                SELECT e.titre, e.type_evenement, p.etudiant_id, p.statut FROM EVENEMENT e JOIN PARTICIPATION p ON e.id_evenement = p.evenement_id
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Titre Événement</th>
                        <th>Type</th>
                        <th>ID Étudiant</th>
                        <th>Statut Participation</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Journée Anti-Gaspi à°Ã... ¸Ã...'±</td><td>Présentiel</td><td>ETU001</td><td>Inscrit</td></tr>
                    <tr><td>Journée Anti-Gaspi à°Ã... ¸Ã...'±</td><td>Présentiel</td><td>ETU002</td><td>Inscrit</td></tr>
                    <tr><td>Journée Anti-Gaspi à°Ã... ¸Ã...'±</td><td>Présentiel</td><td>ETU003</td><td>Inscrit</td></tr>
                    <tr><td>Distribution Gratuite à°Ã... ¸±</td><td>Présentiel</td><td>ETU001</td><td>Inscrit</td></tr>
                    <tr><td>Conférence Anti-Gaspi à°Ã... ¸Ã... ½â€Å"</td><td>En ligne</td><td>ETU003</td><td>Présent</td></tr>
                    <tr><td>Conférence Anti-Gaspi à°Ã... ¸Ã... ½â€Å"</td><td>En ligne</td><td>ETU002</td><td>Absent</td></tr>
                </tbody>
            </table>
        </section>

        <!-- CODE SQL -->
        <section id="sql">
            <h2><span class="text-accent">05.</span> Code SQL Complet</h2>
            <div class="code-container">
                <button class="copy-btn" onclick="copyCode()">Copier le code</button>
<pre><code id="sqlCode"><span class="comment">-- ==========================================
-- CREATION DES TABLES
-- ==========================================</span>

<span class="keyword">CREATE TABLE</span> EVENEMENT (
    id_evenement <span class="type">INT PRIMARY KEY AUTO_INCREMENT</span>,
    titre <span class="type">VARCHAR(100) NOT NULL</span>,
    description <span class="type">TEXT</span>,
    date_evenement <span class="type">DATE NOT NULL</span>,
    heure_debut <span class="type">TIME NOT NULL</span>,
    heure_fin <span class="type">TIME NOT NULL</span>,
    type_evenement <span class="type">VARCHAR(20) NOT NULL DEFAULT</span> <span class="string">'Présentiel'</span>,
    lieu <span class="type">VARCHAR(150)</span>,
    lien_online <span class="type">VARCHAR(255)</span>,
    capacite_max <span class="type">INT NOT NULL</span>,
    statut <span class="type">VARCHAR(20) NOT NULL DEFAULT</span> <span class="string">'Planifié'</span>,
    createur_type <span class="type">VARCHAR(20) NOT NULL</span>,
    createur_id <span class="type">INT NOT NULL</span>,
    statut_validation <span class="type">VARCHAR(20) NOT NULL DEFAULT</span> <span class="string">'En attente'</span>
);

<span class="keyword">CREATE TABLE</span> PARTICIPATION (
    id_participation <span class="type">INT PRIMARY KEY AUTO_INCREMENT</span>,
    evenement_id <span class="type">INT NOT NULL</span>,
    etudiant_id <span class="type">INT NOT NULL</span>,
    date_inscription <span class="type">DATE NOT NULL</span>,
    statut <span class="type">VARCHAR(20) NOT NULL DEFAULT</span> <span class="string">'Inscrit'</span>,
    <span class="keyword">FOREIGN KEY</span> (evenement_id) <span class="keyword">REFERENCES</span> EVENEMENT(id_evenement)
);

<span class="comment">-- ==========================================
-- INSERTION DES DONNEES (TEST)
-- ==========================================</span>

<span class="keyword">INSERT INTO</span> EVENEMENT (titre, date_evenement, heure_debut, heure_fin, type_evenement, lieu, lien_online, capacite_max, statut, createur_type, createur_id, statut_validation) <span class="keyword">VALUES</span>
(<span class="string">'Journée Anti-Gaspi à°Ã... ¸Ã...'±'</span>, <span class="string">'2026-05-10'</span>, <span class="string">'09:00:00'</span>, <span class="string">'12:00:00'</span>, <span class="string">'Présentiel'</span>, <span class="string">'Campus El Manar'</span>, NULL, 100, <span class="string">'Planifié'</span>, <span class="string">'Admin'</span>, 1, <span class="string">'Validé'</span>),
(<span class="string">'Distribution Gratuite à°Ã... ¸±'</span>, <span class="string">'2026-05-12'</span>, <span class="string">'12:00:00'</span>, <span class="string">'14:00:00'</span>, <span class="string">'Présentiel'</span>, <span class="string">'Campus Manouba'</span>, NULL, 50, <span class="string">'Planifié'</span>, <span class="string">'Partenaire'</span>, 5, <span class="string">'Validé'</span>),
(<span class="string">'Conférence Anti-Gaspi à°Ã... ¸Ã... ½â€Å"'</span>, <span class="string">'2026-03-01'</span>, <span class="string">'18:00:00'</span>, <span class="string">'20:00:00'</span>, <span class="string">'En ligne'</span>, NULL, <span class="string">'zoom.us/j/123456'</span>, 200, <span class="string">'Terminé'</span>, <span class="string">'Admin'</span>, 1, <span class="string">'Validé'</span>),
(<span class="string">'Atelier Cuisine à°Ã... ¸³'</span>, <span class="string">'2026-04-08'</span>, <span class="string">'10:00:00'</span>, <span class="string">'13:00:00'</span>, <span class="string">'Présentiel'</span>, <span class="string">'Campus La Marsa'</span>, NULL, 30, <span class="string">'En cours'</span>, <span class="string">'Partenaire'</span>, 7, <span class="string">'En attente'</span>);

<span class="keyword">INSERT INTO</span> PARTICIPATION (evenement_id, etudiant_id, date_inscription, statut) <span class="keyword">VALUES</span>
(1, 101, <span class="string">'2026-04-10'</span>, <span class="string">'Inscrit'</span>),
(1, 102, <span class="string">'2026-04-11'</span>, <span class="string">'Inscrit'</span>),
(2, 101, <span class="string">'2026-04-12'</span>, <span class="string">'Inscrit'</span>),
(3, 103, <span class="string">'2026-03-05'</span>, <span class="string">'Présent'</span>),
(3, 102, <span class="string">'2026-03-04'</span>, <span class="string">'Absent'</span>),
(1, 103, <span class="string">'2026-04-13'</span>, <span class="string">'Inscrit'</span>);

<span class="comment">-- ==========================================
-- 5 REQUETES UTILES
-- ==========================================</span>

<span class="comment">-- 1. Événements validés</span>
<span class="keyword">SELECT</span> * <span class="keyword">FROM</span> EVENEMENT <span class="keyword">WHERE</span> statut_validation = <span class="string">'Validé'</span>;

<span class="comment">-- 2. Événements en attente de validation</span>
<span class="keyword">SELECT</span> * <span class="keyword">FROM</span> EVENEMENT <span class="keyword">WHERE</span> statut_validation = <span class="string">'En attente'</span>;

<span class="comment">-- 3. Jointure participants par événement</span>
<span class="keyword">SELECT</span> e.titre, p.etudiant_id, p.statut, p.date_inscription
<span class="keyword">FROM</span> EVENEMENT e
<span class="keyword">JOIN</span> PARTICIPATION p <span class="keyword">ON</span> e.id_evenement = p.evenement_id;

<span class="comment">-- 4. Nombre de participants (Inscrits ou Présents) par événement</span>
<span class="keyword">SELECT</span> e.titre, <span class="keyword">COUNT</span>(p.id_participation) <span class="keyword">AS</span> nb_participants
<span class="keyword">FROM</span> EVENEMENT e
<span class="keyword">LEFT JOIN</span> PARTICIPATION p <span class="keyword">ON</span> e.id_evenement = p.evenement_id 
<span class="keyword">WHERE</span> p.statut <span class="keyword">IN</span> (<span class="string">'Inscrit'</span>, <span class="string">'Présent'</span>)
<span class="keyword">GROUP BY</span> e.id_evenement, e.titre;

<span class="comment">-- 5. Événements créés par les partenaires</span>
<span class="keyword">SELECT</span> * <span class="keyword">FROM</span> EVENEMENT <span class="keyword">WHERE</span> createur_type = <span class="string">'Partenaire'</span>;
</code></pre>
            </div>
        </section>

        <!-- DIAGRAMMES & REGLES -->
        <section id="usecase">
            <h2><span class="text-accent">06.</span> Logique & Cas d'Utilisation</h2>
            
            <div class="panel">
                <h3>Diagramme de Cas d'Utilisation (Description)</h3>
                <ul class="custom-list">
                    <li><strong>Étudiant :</strong> S'inscrire à  un événement, Consulter les événements, Annuler son inscription.</li>
                    <li><strong>Partenaire :</strong> Créer un événement (<em>&lt;&lt;include&gt;&gt; Attendre validation Admin</em>), Modifier son événement, Consulter la liste de ses participants.</li>
                    <li><strong>Admin :</strong> Valider / Rejeter un événement d'un partenaire, Supprimer n'importe quel événement, Gérer les participants (Marquer présences).</li>
                </ul>
            </div>

            <div class="panel">
                <h3>Cycle de Vie d'un Événement (Workflow)</h3>
                <div class="diagram-box" style="font-size: 0.9rem; padding: 1.5rem;">
                    Partenaire crée âÃ... ¾â€  <span class="text-accent">En attente</span> âÃ... ¾â€  Admin valide âÃ... ¾â€  <span class="text-accent">Planifié</span> âÃ... ¾â€  Étudiants s'inscrivent âÃ... ¾â€  <span class="text-accent">En cours</span> âÃ... ¾â€  Présences âÃ... ¾â€  <span class="text-accent">Terminé</span>
                </div>
            </div>

            <div class="panel">
                <h3>Règles de Sécurité & Gestion</h3>
                <ul class="custom-list">
                    <li>Un étudiant <strong>ne peut pas s'inscrire deux fois</strong> au même événement.</li>
                    <li>L'inscription est techniquement bloquée (bouton désactivé) si <strong>capacite_max</strong> est atteinte.</li>
                    <li>Les événements avec le statut <strong>'En attente'</strong> ou <strong>'Rejeté'</strong> sont invisibles pour les étudiants.</li>
                    <li>Un partenaire accède <strong>exclusivement</strong> à  la liste et aux données de ses propres événements.</li>
                    <li>En cas d'annulation (<span class="text-accent">Statut : Annulé</span>), une notification automatique est générée pour les inscrits.</li>
                </ul>
            </div>
        </section>

        <!-- DISCOURS ORAL -->
        <section id="speech">
            <h2><span class="text-accent">07.</span> Discours Oral (Soutenance)</h2>
            <div class="speech-box">
                ÃÆ’â€Å¡« Bonjour, je suis <span class="text-accent">Aziz</span>, et je m'occupe du <span class="text-accent">Module Gestion des Événements</span>. Ce module est le cÃÆ’...â€Å"ur de la sensibilisation dans CareMeal.<br><br>
                Notre base repose sur 2 entités liées par une relation de type un-à -plusieurs : la table <span class="text-accent">ÉVÉNEMENT</span> qui stocke les détails des distributions ou ateliers, et la table <span class="text-accent">PARTICIPATION</span> qui trace les inscriptions des étudiants.<br><br>
                Lorsqu'un <span class="text-accent">Partenaire</span> crée un événement, celui-ci passe automatiquement en statut <span class="text-accent">"En attente"</span>. Ce n'est qu'après la <span class="text-accent">Validation</span> par l'Administrateur qu'il devient visible pour les étudiants. <br><br>
                Cà´té sécurité, le système contrà´le drastiquement la <span class="text-accent">capacité maximale</span> et empêche les doublons d'inscription. Notre gestion rigoureuse des statuts (planifié, en cours, terminé) nous permet aussi de tracer exactement les présences étudiantes. ÃÆ’â€Å¡»
            </div>
        </section>

    </div>

    <!-- Script pour le bouton copier (simplifié pour copier tout le contenu brut) -->
    <script>
        function copyCode() {
            const codeContent = `-- ==========================================
-- CREATION DES TABLES
-- ==========================================

CREATE TABLE EVENEMENT (
    id_evenement INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(100) NOT NULL,
    description TEXT,
    date_evenement DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    type_evenement VARCHAR(20) NOT NULL DEFAULT 'Présentiel',
    lieu VARCHAR(150),
    lien_online VARCHAR(255),
    capacite_max INT NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'Planifié',
    createur_type VARCHAR(20) NOT NULL,
    createur_id INT NOT NULL,
    statut_validation VARCHAR(20) NOT NULL DEFAULT 'En attente'
);

CREATE TABLE PARTICIPATION (
    id_participation INT PRIMARY KEY AUTO_INCREMENT,
    evenement_id INT NOT NULL,
    etudiant_id INT NOT NULL,
    date_inscription DATE NOT NULL,
    statut VARCHAR(20) NOT NULL DEFAULT 'Inscrit',
    FOREIGN KEY (evenement_id) REFERENCES EVENEMENT(id_evenement)
);

-- ==========================================
-- INSERTION DES DONNEES (TEST)
-- ==========================================

INSERT INTO EVENEMENT (titre, date_evenement, heure_debut, heure_fin, type_evenement, lieu, lien_online, capacite_max, statut, createur_type, createur_id, statut_validation) VALUES
('Journée Anti-Gaspi à°Ã... ¸Ã...'±', '2026-05-10', '09:00:00', '12:00:00', 'Présentiel', 'Campus El Manar', NULL, 100, 'Planifié', 'Admin', 1, 'Validé'),
('Distribution Gratuite à°Ã... ¸±', '2026-05-12', '12:00:00', '14:00:00', 'Présentiel', 'Campus Manouba', NULL, 50, 'Planifié', 'Partenaire', 5, 'Validé'),
('Conférence Anti-Gaspi à°Ã... ¸Ã... ½â€Å"', '2026-03-01', '18:00:00', '20:00:00', 'En ligne', NULL, 'zoom.us/j/123456', 200, 'Terminé', 'Admin', 1, 'Validé'),
('Atelier Cuisine à°Ã... ¸³', '2026-04-08', '10:00:00', '13:00:00', 'Présentiel', 'Campus La Marsa', NULL, 30, 'En cours', 'Partenaire', 7, 'En attente');

INSERT INTO PARTICIPATION (evenement_id, etudiant_id, date_inscription, statut) VALUES
(1, 101, '2026-04-10', 'Inscrit'),
(1, 102, '2026-04-11', 'Inscrit'),
(2, 101, '2026-04-12', 'Inscrit'),
(3, 103, '2026-03-05', 'Présent'),
(3, 102, '2026-03-04', 'Absent'),
(1, 103, '2026-04-13', 'Inscrit');

-- ==========================================
-- 5 REQUETES UTILES
-- ==========================================

SELECT * FROM EVENEMENT WHERE statut_validation = 'Validé';
SELECT * FROM EVENEMENT WHERE statut_validation = 'En attente';
SELECT e.titre, p.etudiant_id, p.statut, p.date_inscription FROM EVENEMENT e JOIN PARTICIPATION p ON e.id_evenement = p.evenement_id;
SELECT e.titre, COUNT(p.id_participation) AS nb_participants FROM EVENEMENT e LEFT JOIN PARTICIPATION p ON e.id_evenement = p.evenement_id WHERE p.statut IN ('Inscrit', 'Présent') GROUP BY e.id_evenement, e.titre;
SELECT * FROM EVENEMENT WHERE createur_type = 'Partenaire';`;

            navigator.clipboard.writeText(codeContent).then(() => {
                const btn = document.querySelector('.copy-btn');
                btn.textContent = "Copié !";
                setTimeout(() => {
                    btn.textContent = "Copier le code";
                }, 2000);
            });
        }
    </script>
</body>
</html>

