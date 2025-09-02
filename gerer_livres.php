<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'] ?? 'lecteur';
$est_admin = ($role === 'admin');
$utilisateur_id = $_SESSION['utilisateur_id'];

try {
    // Récupération de tous les livres
    $stmt = $pdo->query("
        SELECT l.id, l.titre, l.isbn, l.nombre_exemplaires_disponibles,
               c.nom_categorie, CONCAT(a.prenom, ' ', a.nom) AS auteur
        FROM livres l
        LEFT JOIN categories c ON l.categorie_id = c.id
        LEFT JOIN auteurs a ON l.auteur_id = a.id
        ORDER BY l.titre ASC
    ");
    $livres = $stmt->fetchAll();

    // 🔹 Pour les lecteurs : masquer uniquement les livres qui deviendraient 0 après son emprunt
    if (!$est_admin) {
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

            // Masquer le livre seulement si l'utilisateur l'a déjà emprunté et qu'il ne reste aucun exemplaire après
            if ($emprunte_par_user && $nombre_disponibles <= 0) {
                return false;
            }
            return true;
        });
    }

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer les livres</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-200 min-h-screen">

<header class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center">
    <h1 class="text-xl font-bold">
        <?= $est_admin ? 'Gérer les livres' : 'Catalogue des livres' ?>
    </h1>
    <nav class="space-x-4">
        <a href="dashboard.php" class="hover:underline">🖥️Dashboard</a>
        <a href="logout.php" class="hover:underline">↩️Déconnexion</a>
    </nav>
</header>

<div class="max-w-6xl mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">📚 Liste des livres</h2>

    <input type="text" id="searchLivres" placeholder="Rechercher par titre, auteur ou catégorie..."
           class="mb-6 w-full px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400">

    <?php if (empty($livres)): ?>
        <p class="text-gray-600">Aucun livre enregistré.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table id="livresTable" class="min-w-full text-sm text-left text-gray-700">
                <thead class="bg-blue-100 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2">Titre</th>
                        <th class="px-4 py-2">Auteur</th>
                        <th class="px-4 py-2">ISBN</th>
                        <th class="px-4 py-2">Catégorie</th>
                        <th class="px-4 py-2">Disponibles</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($livres as $livre): ?>
                        <tr>
                            <td class="px-4 py-3"><?= htmlspecialchars($livre['titre']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($livre['auteur']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($livre['isbn']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($livre['nom_categorie'] ?? 'NULL') ?></td>
                            <td class="px-4 py-3"><?= (int)$livre['nombre_exemplaires_disponibles'] ?></td>
                            <td class="px-4 py-3 flex gap-2 flex-wrap">
                                <a href="books/view.php?id=<?= $livre['id'] ?>"
                                   class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1 rounded text-sm">
                                    🔍 Voir
                                </a>
                                <?php if ($est_admin): ?>
                                    <a href="books/edit.php?id=<?= $livre['id'] ?>"
                                       class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                                        ✏️ Modifier
                                    </a>
                                    <a href="books/delete.php?id=<?= $livre['id'] ?>"
                                       onclick="return confirm('Confirmer la suppression ?');"
                                       class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                        🗑️ Supprimer
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="mt-8">
        <a href="dashboard.php"
           class="inline-block bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
            ⬅️ <?= $est_admin ? "Tous des Livres" : "Mes Livres Empruntés" ?>
        </a>
    </div>
</div>

<script>
    const searchInput = document.getElementById("searchLivres");
    const table = document.getElementById("livresTable");
    if (searchInput && table) {
        searchInput.addEventListener("keyup", function () {
            const filter = searchInput.value.toLowerCase();
            const rows = table.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const titre = row.cells[0].textContent.toLowerCase();
                const auteur = row.cells[1].textContent.toLowerCase();
                const categorie = row.cells[3].textContent.toLowerCase();
                row.style.display =
                    (titre.includes(filter) || auteur.includes(filter) || categorie.includes(filter)) ? "" : "none";
            });
        });
    }
</script>

</body>
</html>
