<?php
session_start(); 
require_once 'includes/config.php';

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['utilisateur_id'];
$user_role = $_SESSION['role'];

try {
    function updateEmpruntStatus($emprunt_id, $status) {
        global $pdo;
        $sql = "UPDATE emprunts SET statut_emprunt = :status WHERE id = :emprunt_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['status' => $status, 'emprunt_id' => $emprunt_id]);
    }

    $livres = [];       // Pour stocker tous les livres (si admin)
    $mes_emprunts = []; // Pour stocker les emprunts du lecteur

    // --- ADMIN : tous les livres ---
    if ($user_role === 'admin') {
        $stmt_livres = $pdo->query("
            SELECT l.titre, l.isbn, l.nombre_exemplaires_disponibles, c.nom_categorie
            FROM livres l
            LEFT JOIN categories c ON l.categorie_id = c.id
            ORDER BY l.titre
        ");
        $livres = $stmt_livres->fetchAll();
    }

    // --- LECTEUR : ses emprunts ---
    if ($user_role === 'lecteur') {
        $stmt_emprunts = $pdo->prepare("
            SELECT e.id AS emprunt_id, l.titre, l.isbn, e.date_emprunt, 
                   e.date_retour_prevue, e.statut_emprunt
            FROM emprunts e
            JOIN livres l ON e.livre_id = l.id
            WHERE e.utilisateur_id = :user_id AND (e.statut_emprunt = 'actif' OR e.statut_emprunt = 'en retard')
            ORDER BY e.date_emprunt DESC
        ");
        $stmt_emprunts->execute(['user_id' => $user_id]);
        $mes_emprunts = $stmt_emprunts->fetchAll();

        // 🔹 Vérifie si l'emprunt est en retard
        $today = new DateTime();
        foreach ($mes_emprunts as &$emprunt) {
            $date_retour = new DateTime($emprunt['date_retour_prevue']);
            if ($date_retour < $today) {
                $emprunt['statut_emprunt'] = 'en retard';
                updateEmpruntStatus($emprunt['emprunt_id'], 'en retard');
            }
        }
        unset($emprunt);
    }

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Tableau de Bord</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-200 min-h-screen">

<header class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center shadow">
    <h1 class="text-xl font-semibold">Mon Tableau de Bord</h1>
    <nav class="space-x-4">
        <span class="font-medium">👤Bienvenue <?= htmlspecialchars($_SESSION['nom_complet'] ?? $_SESSION['nom_utilisateur']) ?> !</span>
        <a href="index.php" class="hover:underline font-medium">🏠Accueil</a>
        <a href="logout.php" class="hover:underline font-medium">↩️Déconnexion</a>
    </nav>
</header>

<main class="max-w-6xl mx-auto mt-8 p-6 bg-white rounded-xl shadow">

    <!-- ADMIN -->
    <?php if ($user_role === 'admin'): ?>
        <div>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-700">📚 Tous les livres</h2>
                <a href="books/add.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">➕ Ajouter un livre</a>
            </div>
            <input type="text" id="searchAdmin" placeholder="Rechercher par titre ou catégorie..." class="mb-4 w-full px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400">

            <?php if (empty($livres)): ?>
                <p class="text-gray-600">Aucun livre enregistré.</p>
            <?php else: ?>
                <table id="adminTable" class="w-full text-left border border-gray-300 text-sm shadow-sm">
                    <thead class="bg-blue-100">
                        <tr>
                            <th class="p-2">Titre</th>
                            <th class="p-2">ISBN</th>
                            <th class="p-2">Catégorie</th>
                            <th class="p-2">Exemplaires disponibles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($livres as $livre): ?>
                            <tr class="border-t">
                                <td class="p-2"><?= htmlspecialchars($livre['titre']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($livre['isbn']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($livre['nom_categorie'] ?? 'NULL') ?></td>
                                <td class="p-2"><?= $livre['nombre_exemplaires_disponibles'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Boutons de gestion -->
        <div class="mt-10">
            <h2 class="text-2xl font-bold text-gray-700 mb-4">🔧 Gestion (Admin)</h2>
            <div class="flex flex-wrap gap-3">
                <a href="gerer_livres.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">📘 Gérer les livres</a>
                <a href="gerer_auteurs.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">👤 Gérer les auteurs</a>
                <a href="gerer_categories.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">📂 Gérer les catégories</a>
                <a href="gerer_utilisateurs.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">🧑‍🤝‍🧑 Gérer les utilisateurs</a>
                <a href="borrows/manage.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">📋 Gérer les emprunts</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- LECTEUR -->
    <?php if ($user_role === 'lecteur'): ?>
        <div class="mt-10">
            <h2 class="text-2xl font-bold text-gray-700 mb-4">📖 Mes livres empruntés</h2>
            <input type="text" id="searchLecteur" placeholder="Rechercher un livre par titre..." class="mb-4 w-full px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400">

            <?php if (empty($mes_emprunts)): ?>
                <p class="text-gray-600">Vous n'avez actuellement aucun livre emprunté.</p>
            <?php else: ?>
                <table id="lecteurTable" class="w-full text-left border border-gray-300 text-sm shadow-sm">
                    <thead class="bg-blue-100">
                        <tr>
                            <th class="p-2">Titre</th>
                            <th class="p-2">ISBN</th>
                            <th class="p-2">Date d'emprunt</th>
                            <th class="p-2">Retour prévu le</th>
                            <th class="p-2">Statut</th>
                            <th class="p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mes_emprunts as $emprunt): ?>
                            <tr class="border-t">
                                <td class="p-2"><?= htmlspecialchars($emprunt['titre']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($emprunt['isbn']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($emprunt['date_emprunt']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($emprunt['date_retour_prevue']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($emprunt['statut_emprunt']) ?></td>
                                <td class="p-2">
                                    <a href="borrows/return.php?emprunt_id=<?= $emprunt['emprunt_id'] ?>" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Retourner</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="mt-4">
                <a href="gerer_livres.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">📚 Voir tous les livres</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-10">
        <a href="index.php" class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700 transition">⬅️ Retour à l'accueil</a>
    </div>
</main>

<script>
    const searchAdmin = document.getElementById("searchAdmin");
    if (searchAdmin) {
        searchAdmin.addEventListener("keyup", function () {
            const filter = searchAdmin.value.toLowerCase();
            const rows = document.querySelectorAll("#adminTable tbody tr");
            rows.forEach(row => {
                const titre = row.cells[0].textContent.toLowerCase();
                const categorie = row.cells[2].textContent.toLowerCase();
                row.style.display = (titre.includes(filter) || categorie.includes(filter)) ? "" : "none";
            });
        });
    }

    const searchLecteur = document.getElementById("searchLecteur");
    if (searchLecteur) {
        searchLecteur.addEventListener("keyup", function () {
            const filter = searchLecteur.value.toLowerCase();
            const rows = document.querySelectorAll("#lecteurTable tbody tr");
            rows.forEach(row => {
                const titre = row.cells[0].textContent.toLowerCase();
                row.style.display = titre.includes(filter) ? "" : "none";
            });
        });
    }
</script>
</body>
</html>
