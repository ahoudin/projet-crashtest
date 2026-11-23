/**
 * app.js – Logique du site CrashTest.
 *
 * Rôle : récupérer les marques et les voitures depuis l'API PHP, puis
 * afficher la grille de vignettes, les filtres, le sélecteur marque -> modèle,
 * la fiche détaillée (modale) et la page d'entrée « + de 18 ans ».
 */

// =============================================================================
// Configuration
// =============================================================================

// URL de l'API.
// - Site ouvert via http://localhost/site/ : chemin relatif (api/ et site/ côte à côte dans htdocs).
// - Fichier index.html ouvert directement (file://) : on appelle l'API de XAMPP sur localhost.
const API = location.protocol === 'file:'
  ? 'http://localhost/api/index.php'
  : new URL('../api/index.php', location.href).href;

// État de l'application : toutes les données et tous les filtres en cours.
// Chaque fois qu'un filtre change, on modifie cet objet puis on appelle render().
const state = {
  voitures: [],    // Liste complète des voitures reçue de l'API
  marques: [],     // Liste complète des marques reçue de l'API
  marque: '',      // Id de la marque choisie dans les onglets ('' = toutes)
  search: '',      // Texte tapé dans la barre de recherche
  sort: 'safe',    // Tri : 'safe' (plus sûres), 'recent' (plus récentes), 'az' (alphabétique)
  anneeMin: null,  // Filtre année minimale (null = pas de filtre)
  anneeMax: null,  // Filtre année maximale
  noteMin: 0,      // Note minimale (0 = toutes)
};

// Raccourci : $('#grid') équivaut à document.querySelector('#grid')
const $ = (sel) => document.querySelector(sel);

// =============================================================================
// Données et calculs
// =============================================================================

/**
 * Appelle l'API et renvoie la réponse JSON convertie en objet JavaScript.
 * @param {string} path Chemin de la ressource, ex : '/voitures'
 */
async function api(path) {
  const res = await fetch(API + path);                                    // Requête HTTP GET
  if (!res.ok) throw new Error(`Erreur API ${res.status} sur ${path}`);  // Code HTTP d'erreur
  return res.json();                                                      // Conversion JSON -> objet
}

/**
 * Calcule la note moyenne sur 5 d'une voiture à partir de ses trois chocs.
 * Renvoie null si la voiture n'a pas de crash-test.
 */
function note(v) {
  const c = v.crashtest;
  if (!c) return null;
  // Les notes sont stockées en texte ('5') : Number() les convertit en nombres
  const vals = [c.choc_frontal, c.choc_lateral, c.choc_arriere].map(Number);
  return vals.reduce((a, b) => a + b, 0) / vals.length; // Somme / 3
}

// Formate un nombre à la française : 4.7 -> « 4,7 », 5 -> « 5 »
const fmt = (n) => (Number.isInteger(n) ? String(n) : n.toFixed(1)).replace('.', ',');

/**
 * Échappe les caractères spéciaux HTML (< > & " ').
 * Empêche qu'un texte venant de la base soit interprété comme du code HTML (faille XSS).
 */
function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// =============================================================================
// Visuels (vignettes, étoiles, couleurs)
// =============================================================================

// Teintes de couleur (0-360°) attribuées à chaque marque pour le fond des vignettes
const BRAND_HUES = [210, 0, 260, 160, 30, 320];

/**
 * Renvoie le dégradé de fond d'une vignette.
 * La teinte dépend de la marque, et varie légèrement selon l'id de la voiture.
 */
function thumbBg(v) {
  const hue = BRAND_HUES[(v.marque.id - 1) % BRAND_HUES.length];
  const shift = (v.id * 37) % 40; // Petit décalage pour que les vignettes ne soient pas identiques
  return `linear-gradient(135deg, hsl(${hue + shift} 45% 22%), hsl(${hue} 30% 8%))`;
}

/** Renvoie le dessin SVG d'une silhouette de voiture (carrosserie, vitres, roues). */
function carSvg() {
  return `<svg viewBox="0 0 200 80" fill="none" aria-hidden="true">
    <!-- Carrosserie -->
    <path d="M10 58 L18 40 Q22 34 32 33 L62 31 L84 16 Q90 12 100 12 L138 12 Q148 12 154 18 L170 33 L184 37 Q192 40 192 48 L192 58 Z"
          fill="rgba(255,255,255,.12)" stroke="#fff" stroke-width="2.5" stroke-linejoin="round"/>
    <!-- Vitres -->
    <path d="M70 31 L88 18 Q92 16 98 16 L118 16 L118 31 Z M124 16 L138 16 Q144 16 148 20 L160 31 L124 31 Z" fill="rgba(255,144,0,.35)"/>
    <!-- Roues et jantes -->
    <circle cx="50" cy="60" r="13" fill="#111" stroke="#fff" stroke-width="2.5"/>
    <circle cx="155" cy="60" r="13" fill="#111" stroke="#fff" stroke-width="2.5"/>
    <circle cx="50" cy="60" r="4" fill="#ff9000"/><circle cx="155" cy="60" r="4" fill="#ff9000"/>
  </svg>`;
}

/** Renvoie 5 étoiles HTML, dont les n premières (arrondies) sont allumées en orange. */
function stars(n) {
  const full = Math.round(n);
  return '<span class="stars">' +
    Array.from({ length: 5 }, (_, i) => `<span class="${i < full ? 'on' : ''}">★</span>`).join('') +
    '</span>';
}

/** Classe CSS de couleur du pourcentage : vert (≥ 90 %), jaune (≥ 70 %), rouge sinon. */
function pctClass(p) {
  return p >= 90 ? '' : p >= 70 ? 'mid' : 'bad';
}

/**
 * Nombre de vues fictif mais toujours identique pour un même modèle (pour le style).
 * Calculé à partir de l'id et de l'année, puis formaté en « 497,4K » ou « 1,2M ».
 */
function views(v) {
  const n = ((v.id * 7919) % 9000 + 100) * (v.annee > 2015 ? 97 : 31);
  return n >= 1e6 ? fmt(Math.round(n / 1e5) / 10) + 'M' : n >= 1e3 ? fmt(Math.round(n / 100) / 10) + 'K' : n;
}

/**
 * Construit le HTML d'une vignette de voiture :
 * image + badges (année, 5★, note), titre, marque vérifiée, vues, pourcentage.
 */
function cardHtml(v) {
  const n = note(v);
  const pct = n === null ? null : Math.round((n / 5) * 100); // Note sur 5 -> pourcentage
  return `
    <article class="card" data-id="${v.id}" tabindex="0">
      <div class="thumb" style="background:${thumbBg(v)}">
        ${carSvg()}
        <span class="badge badge-year">${v.annee}</span>
        ${n === 5 ? '<span class="badge badge-hd">5★</span>' : ''}
        <span class="badge">${n === null ? 'N/A' : '★ ' + fmt(n)}</span>
      </div>
      <div class="card-title">${escapeHtml(v.marque.nom)} ${escapeHtml(v.modele)}</div>
      <div class="card-meta">
        <span class="channel">${escapeHtml(v.marque.nom)} <i>✔</i></span>
        <span>${views(v)} vues</span>
        ${pct === null ? '' : `<span class="pct ${pctClass(pct)}">👍 ${pct}%</span>`}
      </div>
    </article>`;
}

// =============================================================================
// Liste des voitures (filtres + tri + affichage)
// =============================================================================

/** Renvoie les voitures qui passent tous les filtres, triées selon le tri choisi. */
function filtered() {
  const q = state.search.toLowerCase();

  // 1) Filtrage : une voiture est gardée si elle respecte TOUS les filtres actifs
  const list = state.voitures.filter((v) => {
    if (state.marque && String(v.marque.id) !== state.marque) return false;                    // Onglet marque
    if (q && !`${v.marque.nom} ${v.modele}`.toLowerCase().includes(q)) return false;           // Recherche
    if (state.anneeMin && v.annee < state.anneeMin) return false;                              // Année min
    if (state.anneeMax && v.annee > state.anneeMax) return false;                              // Année max
    if (state.noteMin && (note(v) ?? 0) < state.noteMin) return false;                         // Note min
    return true;
  });

  // 2) Tri : une fonction de comparaison par type de tri
  const sorters = {
    // Meilleure note d'abord ; à note égale, la plus récente d'abord
    safe: (a, b) => (note(b) ?? -1) - (note(a) ?? -1) || b.annee - a.annee,
    // Année la plus récente d'abord
    recent: (a, b) => b.annee - a.annee,
    // Ordre alphabétique « Marque Modèle » (numeric : 208 avant 2008)
    az: (a, b) => `${a.marque.nom} ${a.modele}`.localeCompare(`${b.marque.nom} ${b.modele}`, 'fr', { numeric: true }),
  };
  return list.sort(sorters[state.sort]);
}

/** Réaffiche la grille de vignettes et le titre de la liste selon l'état actuel. */
function render() {
  const list = filtered();
  $('#grid').innerHTML = list.map(cardHtml).join(''); // Une vignette par voiture
  $('#empty').hidden = list.length > 0;               // Message « aucun résultat » si liste vide

  // Titre : résultats de recherche, nom de la marque ou « Tous les modèles », + nombre
  const m = state.marques.find((x) => String(x.id) === state.marque);
  $('#listTitle').textContent =
    (state.search ? `Résultats pour « ${state.search} »` : m ? `Modèles ${m.nom_marque}` : 'Tous les modèles') +
    ` (${list.length})`;
}

/** Affiche les 4 statistiques du haut de page (nombre de modèles, marques, moyenne, 5 étoiles). */
function renderStats() {
  const notes = state.voitures.map(note).filter((n) => n !== null);   // Notes des voitures testées
  const avg = notes.reduce((a, b) => a + b, 0) / (notes.length || 1); // Moyenne (évite la division par 0)
  const top = notes.filter((n) => n === 5).length;                     // Nombre de 5/5
  $('#stats').innerHTML = [
    [state.voitures.length, 'Modèles testés'],
    [state.marques.length, 'Marques'],
    [fmt(avg) + ' / 5', 'Note moyenne'],
    [top, 'Modèles 5 étoiles'],
  ].map(([b, s]) => `<div class="stat"><b>${b}</b><span>${s}</span></div>`).join('');
}

// =============================================================================
// Fiche détaillée (fenêtre modale)
// =============================================================================

/** Texte de verdict affiché sous les barres, selon la note moyenne. */
function verdict(n) {
  if (n === null) return "Aucun crash-test n'est enregistré pour ce modèle.";
  if (n >= 5) return '🏆 Excellent : protection maximale sur les trois types de choc.';
  if (n >= 4) return '👍 Très bon niveau de sécurité, légèrement en retrait des meilleurs modèles.';
  if (n >= 3) return '⚠️ Sécurité correcte mais datée : les modèles récents protègent nettement mieux.';
  return '❌ Niveau de protection faible.';
}

/**
 * Ouvre la fiche détaillée d'une voiture : image, note, étoiles,
 * barres par type de choc, verdict et modèles similaires.
 * @param {number} id Identifiant de la voiture
 */
function openModal(id) {
  const v = state.voitures.find((x) => x.id === id);
  if (!v) return; // Id inconnu : on ne fait rien
  const n = note(v);
  const c = v.crashtest;

  // En-tête : image, marque · année, titre
  $('#mHero').style.background = thumbBg(v);
  $('#mHero').innerHTML = carSvg();
  $('#mMeta').textContent = `${v.marque.nom} · ${v.annee}`;
  $('#mTitle').textContent = `${v.marque.nom} ${v.modele}`;

  // Grande note + étoiles
  $('#mScore').innerHTML = n === null
    ? '<span class="num">N/A</span>'
    : `<span class="num">${fmt(n)}<small>/5</small></span>${stars(n)}`;

  // Une barre par type de choc ; la largeur cible est stockée dans data-w pour l'animation
  const rows = c ? [['Choc frontal', c.choc_frontal], ['Choc latéral', c.choc_lateral], ['Choc arrière', c.choc_arriere]] : [];
  $('#mBars').innerHTML = rows.map(([label, val]) => `
    <div class="bar-row">
      <span>${label}</span>
      <div class="bar"><i data-w="${(Number(val) / 5) * 100}"></i></div>
      <b>${escapeHtml(val)}/5</b>
    </div>`).join('');
  $('#mVerdict').textContent = verdict(n);

  // Modèles similaires : on calcule une « distance » pour chaque autre voiture
  // (même marque = plus proche, note proche, année proche) et on garde les 4 plus proches
  const similar = state.voitures
    .filter((x) => x.id !== v.id)
    .map((x) => ({ x, d: (x.marque.id === v.marque.id ? 0 : 10) + Math.abs((note(x) ?? 0) - (n ?? 0)) + Math.abs(x.annee - v.annee) / 10 }))
    .sort((a, b) => a.d - b.d)
    .slice(0, 4)
    .map((o) => o.x);
  $('#mSimilar').innerHTML = similar.map(cardHtml).join('');

  // Affichage de la modale et blocage du défilement de la page derrière
  $('#modal').hidden = false;
  document.body.style.overflow = 'hidden';
  $('#modal .modal-box').scrollTop = 0;
  // Met l'adresse à jour (#voiture-12) pour pouvoir partager le lien de la fiche
  history.replaceState(null, '', `#voiture-${v.id}`);

  // Animation des barres : on attend que le navigateur ait affiché les barres à 0 %,
  // puis on applique leur vraie largeur (la transition CSS fait l'animation)
  requestAnimationFrame(() => requestAnimationFrame(() => {
    document.querySelectorAll('#mBars i').forEach((i) => { i.style.width = i.dataset.w + '%'; });
  }));
}

/** Ferme la fiche détaillée et retire le #voiture-… de l'adresse. */
function closeModal() {
  $('#modal').hidden = true;
  document.body.style.overflow = '';
  history.replaceState(null, '', location.pathname + location.search);
}

// =============================================================================
// Sélecteur « marque -> modèle »
// =============================================================================

/** Remplit la liste déroulante des marques. */
function fillFinderMarques() {
  $('#selMarque').innerHTML = '<option value="">— Choisir une marque —</option>' +
    state.marques.map((m) => `<option value="${m.id}">${escapeHtml(m.nom_marque)}</option>`).join('');
}

/** Remplit la liste des modèles de la marque choisie (appelée quand la marque change). */
function fillFinderModeles() {
  const id = $('#selMarque').value;
  const sel = $('#selModele');

  // Modèles de la marque choisie, triés par ordre alphabétique
  const modeles = state.voitures
    .filter((v) => String(v.marque.id) === id)
    .sort((a, b) => a.modele.localeCompare(b.modele, 'fr', { numeric: true }));

  sel.innerHTML = '<option value="">— Choisir un modèle —</option>' +
    modeles.map((v) => `<option value="${v.id}">${escapeHtml(v.modele)} (${v.annee})</option>`).join('');
  sel.disabled = !id;             // Liste des modèles inactive tant qu'aucune marque n'est choisie
  $('#btnVoir').disabled = true;  // Bouton inactif tant qu'aucun modèle n'est choisi
}

// =============================================================================
// Évènements (clics, saisies, clavier)
// =============================================================================

/** Branche tous les écouteurs d'évènements de la page. */
function bindEvents() {
  // Clic sur un onglet de marque : on active l'onglet et on filtre la grille
  $('#brandTabs').addEventListener('click', (e) => {
    const tab = e.target.closest('.tab');
    if (!tab) return;
    document.querySelectorAll('.tab').forEach((t) => t.classList.toggle('active', t === tab));
    state.marque = tab.dataset.marque;
    render();
  });

  // Barre de recherche : filtrage instantané à chaque lettre tapée
  $('#searchForm').addEventListener('submit', (e) => e.preventDefault()); // Empêche le rechargement de la page
  $('#searchInput').addEventListener('input', (e) => { state.search = e.target.value.trim(); render(); });

  // Boutons de tri
  document.querySelector('.sort').addEventListener('click', (e) => {
    const chip = e.target.closest('.chip');
    if (!chip) return;
    document.querySelectorAll('.chip').forEach((c) => c.classList.toggle('active', c === chip));
    state.sort = chip.dataset.sort;
    render();
  });

  // Filtres année min / max et note minimale
  $('#anneeMin').addEventListener('input', (e) => { state.anneeMin = Number(e.target.value) || null; render(); });
  $('#anneeMax').addEventListener('input', (e) => { state.anneeMax = Number(e.target.value) || null; render(); });
  $('#noteMin').addEventListener('change', (e) => { state.noteMin = Number(e.target.value); render(); });

  // Sélecteur : marque -> remplit les modèles ; modèle -> active le bouton ; bouton -> ouvre la fiche
  $('#selMarque').addEventListener('change', fillFinderModeles);
  $('#selModele').addEventListener('change', (e) => { $('#btnVoir').disabled = !e.target.value; });
  $('#btnVoir').addEventListener('click', () => openModal(Number($('#selModele').value)));

  // Clic sur n'importe quelle vignette (grille principale ou « similaires ») -> ouvre sa fiche
  document.addEventListener('click', (e) => {
    const card = e.target.closest('.card');
    if (card) openModal(Number(card.dataset.id));
  });

  // Clavier : Échap ferme la fiche ; Entrée sur une vignette sélectionnée l'ouvre
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !$('#modal').hidden) closeModal();
    if (e.key === 'Enter' && e.target.classList?.contains('card')) openModal(Number(e.target.dataset.id));
  });

  // Fermeture de la fiche : bouton ✕ ou clic sur le fond sombre autour
  $('#modalClose').addEventListener('click', closeModal);
  $('#modal').addEventListener('click', (e) => { if (e.target.id === 'modal') closeModal(); });

  // Clic sur le logo : retour à l'accueil (onglet Accueil, recherche vidée, haut de page)
  $('#logo').addEventListener('click', (e) => {
    e.preventDefault();
    $('#brandTabs .tab').click(); // Simule un clic sur le premier onglet « Accueil »
    $('#searchInput').value = '';
    state.search = '';
    render();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

// =============================================================================
// Page d'entrée « Avez-vous plus de 18 ans ? »
// =============================================================================

// Affichée à chaque ouverture / rechargement du site (aucune mémorisation).
// La classe « gated » sur <body> affiche la page d'entrée et bloque le défilement.
function initAgeGate() {
  $('#ageYes').focus(); // Le bouton « Entrer » est sélectionné : Entrée au clavier suffit
  // « J'ai 18 ans ou plus » : on retire la classe, la page d'entrée disparaît
  $('#ageYes').addEventListener('click', () => document.body.classList.remove('gated'));
  // « J'ai moins de 18 ans » : on quitte le site vers Google
  $('#ageNo').addEventListener('click', () => { location.href = 'https://www.google.com/'; });
}

// =============================================================================
// Démarrage
// =============================================================================

/** Point d'entrée : initialise la page, charge les données puis affiche tout. */
async function init() {
  initAgeGate();
  bindEvents();

  // Chargement des marques et des voitures en parallèle (Promise.all)
  try {
    [state.marques, state.voitures] = await Promise.all([api('/marques'), api('/voitures')]);
  } catch (err) {
    // API injoignable (XAMPP arrêté, mauvais chemin…) : message d'erreur à l'écran
    $('#error').hidden = false;
    $('#error').textContent = `Impossible de contacter l'API (${err.message}). Vérifie qu'Apache et MySQL sont lancés dans XAMPP, puis ouvre le site via http://localhost/site/. Adresse appelée : ${API}`;
    return;
  }

  // Un onglet par marque, ajouté après l'onglet « Accueil »
  $('#brandTabs').insertAdjacentHTML('beforeend',
    state.marques.map((m) => `<button class="tab" data-marque="${m.id}">${escapeHtml(m.nom_marque)}</button>`).join(''));
  fillFinderMarques();
  renderStats();
  render();

  // Si l'adresse contient #voiture-12, on ouvre directement la fiche de cette voiture
  const m = location.hash.match(/^#voiture-(\d+)$/);
  if (m) openModal(Number(m[1]));
}

// Lancement de l'application
init();
