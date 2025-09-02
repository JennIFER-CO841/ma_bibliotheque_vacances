<?php
session_start(); 
require_once '../includes/config.php'; 

// Vérifie que l'utilisateur est connecté et a le rôle admin
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    exit("Accès refusé.");
}

// update statut de livre en retard si la date de retour est dépassée
$stmt_encours = $pdo->query("SELECT * FROM emprunts WHERE statut_emprunt = 'actif'");
$emprunts_en_cours = $stmt_encours->fetchAll();
// 🔹 Vérifie si l'emprunt est en retard
    $today = new DateTime();
    foreach ($emprunts_en_cours as &$emprunt) {
        $date_retour = new DateTime($emprunt['date_retour_prevue']);
        if ($date_retour < $today) {
                //$emprunt['statut_emprunt'] = 'en retard';
                //updateEmpruntStatus($emprunt['id'], 'en retard');
            $sql = "UPDATE emprunts SET statut_emprunt = :status WHERE id = :emprunt_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['status' => 'en retard', 'emprunt_id' => $emprunt['id']]);
            }
        }
        unset($emprunt);

// Requête SQL pour récupérer la liste des emprunts
$stmt = $pdo->query("
    SELECT e.*, 
           l.titre AS titre_livre, 
           u.nom_utilisateur
    FROM emprunts e
    JOIN livres l ON e.livre_id = l.id
    JOIN utilisateurs u ON e.utilisateur_id = u.id
    ORDER BY e.date_emprunt DESC
");
$emprunts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer les emprunts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-200 min-h-screen text-gray-800">

<!-- En-tête -->
<header class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center shadow">
    <h1 class="text-2xl font-semibold">📋 Gestion des emprunts</h1>
    <nav class="space-x-4">
        <a href="../index.php" class="hover:underline">🏠Accueil</a>
        <a href="../dashboard.php" class="hover:underline">🖥️Tableau de bord</a>
        <a href="../logout.php" class="hover:underline">↩️Déconnexion</a>
    </nav>
</header>

<!-- Contenu principal -->
<main class="max-w-6xl mx-auto mt-10 p-6 bg-white rounded-lg shadow">
    <h2 class="text-xl font-bold mb-4">Liste des emprunts</h2>

    <!-- 🔍 Barre de recherche -->
    <input type="text" id="searchEmprunts" 
           placeholder="Rechercher par livre, emprunteur ou statut..."
           class="mb-6 w-full px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400">

    <?php if (count($emprunts) === 0): ?>
        <p class="text-gray-600">Aucun emprunt trouvé.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table id="empruntsTable" class="min-w-full text-sm border border-gray-200 shadow">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="px-4 py-2">Livre</th>
                        <th class="px-4 py-2">Emprunteur</th>
                        <th class="px-4 py-2">Date d'emprunt</th>
                        <th class="px-4 py-2">Retour prévu</th>
                        <th class="px-4 py-2">Retour réel</th>
                        <th class="px-4 py-2">Statut</th>
                        <th class="px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php foreach ($emprunts as $emprunt): ?>
                        <tr class="border-t hover:bg-blue-50">
                            <td class="px-4 py-2"><?= htmlspecialchars($emprunt['titre_livre']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($emprunt['nom_utilisateur']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($emprunt['date_emprunt']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($emprunt['date_retour_prevue']) ?></td>
                            <td class="px-4 py-2">
                                <?= $emprunt['date_retour_reelle'] ? htmlspecialchars($emprunt['date_retour_reelle']) : '—' ?>
                            </td>
                            <td class="px-4 py-2 capitalize">
                                <?= htmlspecialchars($emprunt['statut_emprunt']) ?>
                            </td>
                            <td class="px-4 py-2">
                                <?php if ($emprunt['statut_emprunt'] === 'actif' OR $emprunt['statut_emprunt'] === 'en retard'): ?>
                                    <a href="return.php?emprunt_id=<?= $emprunt['id'] ?>"
                                       class="text-green-600 hover:underline"
                                       onclick="return confirm('Confirmer le retour de ce livre ?');">
                                        Retourner
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="mt-6">
        <a href="../dashboard.php" 
           class="inline-block bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700 transition">
            ⬅️ Retour au tableau de bord
        </a>
    </div>
</main>

<!-- Script de recherche -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById("searchEmprunts");
    const table = document.getElementById("empruntsTable");
    if (searchInput && table) {
        searchInput.addEventListener("keyup", function () {
            const filter = searchInput.value.toLowerCase();
            const rows = table.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const livre = row.cells[0].textContent.toLowerCase();
                const emprunteur = row.cells[1].textContent.toLowerCase();
                const statut = row.cells[5].textContent.toLowerCase();
                row.style.display = (livre.includes(filter) || emprunteur.includes(filter) || statut.includes(filter)) ? "" : "none";
            });
        });
    }
});
</script>

</body>
</html>
