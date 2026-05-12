<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Présentation du module de gestion des événements CareMeal.">
  <title>Module Gestion Événements - CareMeal</title>
  <style>
    :root {
      --bg: #0f172a;
      --panel: #111827;
      --card: #1e293b;
      --text: #e5e7eb;
      --muted: #94a3b8;
      --accent: #fe5516;
      --accent-soft: rgba(254, 85, 22, 0.15);
      --border: rgba(148, 163, 184, 0.25);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, sans-serif;
      background: radial-gradient(circle at 20% 10%, #1f2937, var(--bg));
      color: var(--text);
      line-height: 1.6;
    }
    .wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 24px;
    }
    .top {
      position: sticky;
      top: 0;
      z-index: 20;
      background: rgba(15, 23, 42, 0.92);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid var(--border);
      margin: -24px -24px 24px;
      padding: 14px 24px;
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
    }
    .top a {
      color: var(--text);
      text-decoration: none;
      padding: 8px 12px;
      border-radius: 10px;
      border: 1px solid transparent;
      font-size: 14px;
    }
    .top a:hover {
      border-color: var(--accent);
      background: var(--accent-soft);
    }
    .hero {
      background: linear-gradient(135deg, #1f2937, #111827);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 28px;
      margin-bottom: 20px;
    }
    h1, h2, h3 { margin: 0 0 10px; }
    h1 { font-size: 30px; }
    h2 { font-size: 22px; margin-top: 24px; }
    h3 { font-size: 18px; color: #f8fafc; }
    .muted { color: var(--muted); }
    .card {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 18px;
      margin-top: 12px;
    }
    .chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
    .chip {
      font-size: 13px;
      color: #fed7aa;
      background: var(--accent-soft);
      border: 1px solid rgba(254, 85, 22, 0.35);
      border-radius: 999px;
      padding: 5px 10px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 14px;
    }
    th, td {
      border: 1px solid var(--border);
      padding: 9px;
      text-align: left;
      vertical-align: top;
    }
    th { background: #0b1220; }
    code, pre {
      background: #0b1220;
      border: 1px solid var(--border);
      border-radius: 10px;
      color: #d1d5db;
      font-family: Consolas, monospace;
    }
    pre { padding: 12px; overflow: auto; }
    .ok { color: #86efac; font-weight: 700; }
    .no { color: #fca5a5; font-weight: 700; }
    .accent { color: var(--accent); }
  </style>
</head>
<body>
  <div class="wrap">
    <nav class="top">
      <a href="#intro">Introduction</a>
      <a href="#droits">Droits</a>
      <a href="#mcd">MCD / MLD</a>
      <a href="#donnees">Données</a>
      <a href="#sql">SQL</a>
      <a href="#workflow">Workflow</a>
      <a href="#oral">Oral</a>
    </nav>

    <section class="hero" id="intro">
      <h1>Module Gestion Événements</h1>
      <p class="muted">Entités principales: <strong>Événement</strong> et <strong>Participation</strong></p>
      <p>Ce module permet aux partenaires et aux administrateurs d’organiser des événements anti-gaspillage. Les étudiants consultent les événements validés et s’inscrivent selon la capacité disponible.</p>
      <div class="chips">
        <span class="chip">Présentiel</span>
        <span class="chip">En ligne</span>
        <span class="chip">Validation admin</span>
        <span class="chip">Suivi des présences</span>
      </div>
    </section>

    <section id="droits">
      <h2>1. Matrice des Droits</h2>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Action</th>
              <th>Étudiant</th>
              <th>Partenaire</th>
              <th>Admin</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>Créer un événement</td><td class="no">Non</td><td class="ok">Oui (avec validation)</td><td class="ok">Oui (direct)</td></tr>
            <tr><td>Modifier un événement</td><td class="no">Non</td><td class="ok">Oui (les siens)</td><td class="ok">Oui (tous)</td></tr>
            <tr><td>Supprimer un événement</td><td class="no">Non</td><td class="no">Non</td><td class="ok">Oui (tous)</td></tr>
            <tr><td>Valider / rejeter</td><td class="no">Non</td><td class="no">Non</td><td class="ok">Oui</td></tr>
            <tr><td>S’inscrire</td><td class="ok">Oui</td><td class="no">Non</td><td class="no">Non</td></tr>
            <tr><td>Voir les participants</td><td class="no">Non</td><td class="ok">Oui (les siens)</td><td class="ok">Oui (tous)</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section id="mcd">
      <h2>2. Modélisation</h2>
      <div class="card">
        <h3>MCD simplifié</h3>
        <p><strong>Événement (1,1)</strong> contient <strong>Participation (0,n)</strong>.</p>
        <p class="muted">Un événement peut avoir plusieurs participations, une participation appartient à un seul événement.</p>
      </div>
      <div class="card">
        <h3>MLD</h3>
        <p><strong>EVENEMENT</strong>(id_evenement, titre, date_evenement, heure_debut, heure_fin, type_evenement, lieu, lien_online, capacite_max, statut, createur_type, createur_id, statut_validation)</p>
        <p><strong>PARTICIPATION</strong>(id_participation, evenement_id, etudiant_id, date_inscription, statut)</p>
      </div>
    </section>

    <section id="donnees">
      <h2>3. Données de Test</h2>
      <div class="card">
        <h3>Exemples d’événements</h3>
        <pre>1 | Journée Anti-Gaspi | Présentiel | Campus El Manar | Planifié
2 | Distribution Gratuite | Présentiel | Campus Manouba | Planifié
3 | Conférence Anti-Gaspi | En ligne | zoom.us/j/123456 | Terminé
4 | Atelier Cuisine | Présentiel | Campus La Marsa | En cours</pre>
      </div>
      <div class="card">
        <h3>Exemples de participations</h3>
        <pre>1 | événement 1 | ETU001 | 2026-04-10 | Inscrit
2 | événement 1 | ETU002 | 2026-04-11 | Inscrit
3 | événement 3 | ETU003 | 2026-03-05 | Présent</pre>
      </div>
    </section>

    <section id="sql">
      <h2>4. SQL Essentiel</h2>
      <div class="card">
<pre>CREATE TABLE EVENEMENT (
  id_evenement INT PRIMARY KEY AUTO_INCREMENT,
  titre VARCHAR(100) NOT NULL,
  date_evenement DATE NOT NULL,
  heure_debut TIME NOT NULL,
  heure_fin TIME NOT NULL,
  type_evenement VARCHAR(20) NOT NULL DEFAULT 'Présentiel',
  capacite_max INT NOT NULL,
  statut_validation VARCHAR(20) NOT NULL DEFAULT 'En attente'
);

CREATE TABLE PARTICIPATION (
  id_participation INT PRIMARY KEY AUTO_INCREMENT,
  evenement_id INT NOT NULL,
  etudiant_id INT NOT NULL,
  statut VARCHAR(20) NOT NULL DEFAULT 'Inscrit'
);</pre>
      </div>
    </section>

    <section id="workflow">
      <h2>5. Workflow Métier</h2>
      <div class="card">
        <p><span class="accent">Partenaire crée</span> → <span class="accent">En attente</span> → <span class="accent">Admin valide</span> → <span class="accent">Planifié</span> → <span class="accent">Inscriptions étudiantes</span> → <span class="accent">En cours</span> → <span class="accent">Terminé</span></p>
      </div>
      <div class="card">
        <ul>
          <li>Un étudiant ne peut pas s’inscrire deux fois au même événement.</li>
          <li>Inscription bloquée dès que la capacité maximale est atteinte.</li>
          <li>Les événements non validés restent invisibles pour les étudiants.</li>
        </ul>
      </div>
    </section>

    <section id="oral">
      <h2>6. Trame de Soutenance</h2>
      <div class="card">
        <p>Bonjour, je présente le module de gestion des événements de CareMeal. Ce module relie la création d’événements à la participation des étudiants avec un contrôle strict des statuts, des capacités et de la validation administrative.</p>
        <p>Notre objectif est double: sensibiliser à l’anti-gaspillage et garantir un suivi opérationnel fiable des inscriptions et des présences.</p>
      </div>
    </section>
  </div>
</body>
</html>
