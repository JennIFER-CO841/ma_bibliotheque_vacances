<?php 
session_start();
require_once 'includes/config.php';

$role = $_SESSION['role'] ?? 'lecteur';
$est_admin = ($role === 'admin');
$utilisateur_id = $_SESSION['utilisateur_id'] ?? null;

// --- Pagination des livres ---
$livres_par_page = 5; 
$page_actuelle = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$debut = ($page_actuelle - 1) * $livres_par_page;

// Récupération de tous les livres (admin voit tout)
$stmt = $pdo->query("
    SELECT l.id, l.titre, l.nombre_exemplaires_disponibles, 
           a.nom AS auteur_nom, a.prenom AS auteur_prenom, c.nom_categorie
    FROM livres l
    LEFT JOIN auteurs a ON l.auteur_id = a.id
    LEFT JOIN categories c ON l.categorie_id = c.id
    ORDER BY l.titre ASC
");
$livres = $stmt->fetchAll();

// 🔹 Filtrage pour les lecteurs : masquer les livres qui deviendraient 0 après son emprunt
if (!$est_admin && $utilisateur_id) {
    // Récupération des livres déjà empruntés par l'utilisateur
    $stmt_emprunts = $pdo->prepare("
        SELECT livre_id 
        FROM emprunts 
        WHERE utilisateur_id = :uid
    ");
    $stmt_emprunts->execute(['uid' => $utilisateur_id]);
    $empruntes = $stmt_emprunts->fetchAll(PDO::FETCH_COLUMN);

    $livres = array_filter($livres, function($livre) use ($empruntes) {
        $nombre_disponibles = (int)$livre['nombre_exemplaires_disponibles'];
        $emprunte_par_user = in_array($livre['id'], $empruntes);

        // Masquer seulement si l'utilisateur l'a déjà emprunté et qu'il ne reste aucun exemplaire après
        if ($emprunte_par_user && $nombre_disponibles <= 0) {
            return false;
        }
        return true;
    });
}

// Pagination manuelle
$total_livres = count($livres);
$total_pages = ceil($total_livres / $livres_par_page);
$livres = array_slice($livres, $debut, $livres_par_page);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bibliothèque de Vacances</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-200 min-h-screen">

<header class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center flex-wrap">
    <h1 class="text-xl font-bold">📚 Ma Bibliothèque de Vacances</h1>
    <nav class="space-x-4">
        <?php if (isset($_SESSION['utilisateur_id'])): ?>            
            <a href="dashboard.php" class="hover:underline">🖥️ Tableau de Bord</a>
            <a href="logout.php" class="hover:underline">↩️ Déconnexion</a>
        <?php else: ?>
            <a href="login.php" class="hover:underline">🔑 Connexion</a>
            <a href="register.php" class="hover:underline">🆕 Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main class="max-w-5xl mx-auto bg-white mt-10 p-8 rounded-lg shadow-lg">
    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center justify-center">
        <input type="text" id="searchInput" placeholder="🔍 Rechercher un livre ou un auteur..."
               class="w-full sm:w-2/3 px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
    </div>

    <h2 class="text-2xl font-bold mb-4">📖 Livres disponibles</h2>

    <?php if (empty($livres)): ?>
        <p class="text-center text-gray-600">Aucun livre trouvé.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table id="livresTable" class="w-full table-auto border border-gray-200">
                <thead class="bg-blue-100">
                <tr>
                    <th class="px-4 py-3 text-left">Titre</th>
                    <th class="px-4 py-3 text-left">Auteur</th>
                    <th class="px-4 py-3 text-left">Catégorie</th>
                    <th class="px-4 py-3 text-left">Exemplaires</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($livres as $livre): ?>
                    <tr class="border-t">
                        <td class="px-4 py-3"><?= htmlspecialchars($livre['titre']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($livre['auteur_prenom'] . ' ' . $livre['auteur_nom']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($livre['nom_categorie'] ?? 'NULL') ?></td>
                        <td class="px-4 py-3"><?= intval($livre['nombre_exemplaires_disponibles']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center mt-6 space-x-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>"
                       class="px-4 py-2 rounded-md text-white <?= $i === $page_actuelle ? 'bg-blue-700' : 'bg-blue-500 hover:bg-blue-600' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#livresTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
