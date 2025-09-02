<?php
session_start(); // Démarre la session pour utiliser les variables de session
require_once 'includes/config.php'; // Inclut la configuration de la base de données

// Vérifie que l'utilisateur est connecté et qu'il est admin
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    exit("⛔ Accès refusé."); // Empêche l'accès si l'utilisateur n'est pas un administrateur
}

// Récupère toutes les catégories depuis la base de données, triées par nom
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nom_categorie ASC");
$categories = $stmt->fetchAll(); // Met les résultats dans un tableau
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Catégories</title>
    <!-- Intègre Tailwind CSS pour le style rapide et réactif -->
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-200 min-h-screen font-sans">

<header class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center shadow-md">
    <h1 class="text-2xl font-bold">📚 Gestion des Catégories</h1>
    <!-- Barre de navigation principale -->
    <nav class="space-x-4">
        <a href="index.php" class="hover:underline">🏠 Accueil</a>
        <a href="dashboard.php" class="hover:underline">📊 Tableau de Bord</a>
        <a href="logout.php" class="hover:underline">🚪 Déconnexion</a>
    </nav>
</header>

<main class="max-w-5xl mx-auto mt-10 bg-white shadow-md rounded-lg p-8">
    <!-- En-tête du contenu principal avec titre et bouton d'ajout -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-blue-700">📂 Liste des Catégories</h2>
        <!-- Bouton pour accéder au formulaire d'ajout d'une nouvelle catégorie -->
        <a href="categories/add.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            ➕ Ajouter une catégorie
        </a>
    </div>

    <!-- 🔍 Barre de recherche -->
    <input type="text" id="searchCategories" placeholder="Rechercher une catégorie..."
           class="mb-6 w-full px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400">

    <!-- Tableau affichant la liste des catégories -->
    <table id="categoriesTable" class="w-full table-auto border-collapse">
        <thead class="bg-blue-100 text-blue-700">
            <tr>
                <th class="text-left px-4 py-2">Nom de la Catégorie</th>
                <th class="text-left px-4 py-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($categories) > 0): ?>
                <!-- Parcourt toutes les catégories récupérées -->
                <?php foreach ($categories as $categorie): ?>
                    <tr class="border-b hover:bg-blue-50">
                        <!-- Affiche le nom de la catégorie en toute sécurité -->
                        <td class="px-4 py-2"><?= htmlspecialchars($categorie['nom_categorie']) ?></td>

                        <!-- Actions possibles : modifier ou supprimer une catégorie -->
                        <td class="px-4 py-2 space-x-2">
                            <a href="categories/edit.php?id=<?= $categorie['id'] ?>"
                               class="bg-yellow-400 text-white px-3 py-1 rounded hover:bg-yellow-500 transition">
                                ✏️ Modifier
                            </a>
                            <a href="categories/delete.php?id=<?= $categorie['id'] ?>"
                               onclick="return confirm('❗ Cette action supprimera la catégorie. Continuer ?')"
                               class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition">
                                🗑️ Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Message affiché si aucune catégorie n'est trouvée -->
                <tr>
                    <td colspan="2" class="px-4 py-4 text-gray-600 italic">Aucune catégorie trouvée.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Bouton retour vers dashboard.php placé en bas de la page -->
    <div class="mt-8">
        <a href="dashboard.php"
           class="inline-block bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
            ⬅️ Retour au tableau de bord
        </a>
    </div>
</main>

<script>
// 🔍 Recherche dynamique des catégories
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById("searchCategories");
    const table = document.getElementById("categoriesTable");
    if (searchInput && table) {
        searchInput.addEventListener("keyup", function () {
            const filter = searchInput.value.toLowerCase();
            const rows = table.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const categorie = row.cells[0].textContent.toLowerCase();
                row.style.display = categorie.includes(filter) ? "" : "none";
            });
        });
    }
});
</script>

</body>
</html>
