<?php
session_start(); 
require_once '../includes/config.php';

// Vérifie que l'utilisateur est connecté, sinon redirige vers l'accueil
if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: ../index.php");
    exit;
}

$message = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $isbn = trim($_POST['isbn']);
    $annee = $_POST['annee'];
    $categorie_id = $_POST['categorie_id'];
    $resume = trim($_POST['resume']);
    $exemplaires = (int)$_POST['exemplaires'];
    $auteur_id = $_POST['auteur_id']; // On récupère directement l’ID choisi

    if ($titre && $isbn && $annee && $categorie_id && $resume && $exemplaires && $auteur_id) {
        try {
            // Insertion du livre
            $stmt = $pdo->prepare("
                INSERT INTO livres (
                    titre, isbn, annee_publication, resume, 
                    nombre_exemplaires_total, nombre_exemplaires_disponibles, 
                    auteur_id, categorie_id, utilisateur_id
                ) VALUES (
                    :titre, :isbn, :annee, :resume, 
                    :nb_total, :nb_dispo, :auteur_id, :categorie_id, :utilisateur_id
                )
            ");
            $stmt->execute([
                ':titre' => $titre,
                ':isbn' => $isbn,
                ':annee' => $annee,
                ':resume' => $resume,
                ':nb_total' => $exemplaires,
                ':nb_dispo' => $exemplaires,
                ':auteur_id' => $auteur_id,
                ':categorie_id' => $categorie_id,
                ':utilisateur_id' => $_SESSION['utilisateur_id']
            ]);

            $message = "Livre ajouté avec succès.";
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    } else {
        $message = "Tous les champs sont obligatoires.";
    }
}

// Récupération des catégories
$stmt = $pdo->query("SELECT id, nom_categorie FROM categories ORDER BY nom_categorie ASC");
$categories = $stmt->fetchAll();

// Récupération des auteurs
$stmt = $pdo->query("SELECT id, nom, prenom FROM auteurs ORDER BY nom ASC, prenom ASC");
$auteurs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Livre</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-200 min-h-screen">

<div class="max-w-2xl mx-auto mt-12 bg-white p-8 rounded-xl shadow-md">
    
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Ajouter un nouveau livre</h1>

    <?php if ($message): ?>
        <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 rounded">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

        <div>
            <label for="titre" class="block font-medium text-gray-700">Titre</label>
            <input type="text" name="titre" required class="w-full mt-1 px-4 py-2 border rounded shadow-sm">
        </div>

        <div>
            <label for="isbn" class="block font-medium text-gray-700">ISBN</label>
            <input type="text" name="isbn" required class="w-full mt-1 px-4 py-2 border rounded shadow-sm">
        </div>

        <div>
            <label for="annee" class="block font-medium text-gray-700">Année de publication</label>
            <input type="number" name="annee" min="1000" max="<?= date('Y') ?>" required class="w-full mt-1 px-4 py-2 border rounded shadow-sm">
        </div>

        <!-- Liste déroulante Catégorie -->
        <div>
            <label for="categorie_id" class="block font-medium text-gray-700">Catégorie</label>
            <select name="categorie_id" required class="w-full mt-1 px-4 py-2 border rounded shadow-sm">
                <option value="">-- Choisissez une catégorie --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom_categorie']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="resume" class="block font-medium text-gray-700">Résumé</label>
            <textarea name="resume" rows="4" required class="w-full mt-1 px-4 py-2 border rounded shadow-sm"></textarea>
        </div>

        <div>
            <label for="exemplaires" class="block font-medium text-gray-700">Nombre d'exemplaires</label>
            <input type="number" name="exemplaires" min="1" required class="w-full mt-1 px-4 py-2 border rounded shadow-sm">
        </div>

        <!-- Liste déroulante Auteur -->
        <div>
            <label for="auteur_id" class="block font-medium text-gray-700">Auteur</label>
            <select name="auteur_id" required class="w-full mt-1 px-4 py-2 border rounded shadow-sm">
                <option value="">-- Choisissez un auteur --</option>
                <?php foreach ($auteurs as $auteur): ?>
                    <option value="<?= $auteur['id'] ?>">
                        <?= htmlspecialchars($auteur['prenom'] . ' ' . $auteur['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">
            Ajouter le livre
        </button>
    </form>

    <div class="mt-6">
        <a href="../dashboard.php" class="text-blue-600 hover:underline">← Retour au tableau de bord</a>
    </div>
</div>

</body>
</html>
