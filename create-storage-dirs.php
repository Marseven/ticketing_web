<?php
/**
 * Script de création des dossiers storage manquants
 * À exécuter une seule fois après déploiement : https://primea.ga/create-storage-dirs.php
 * Puis SUPPRIMER ce fichier pour des raisons de sécurité
 */

$dirs = [
    'storage/app/public/hero_banners',
    'storage/app/public/banners',
    'storage/app/public/events',
    'storage/app/public/avatars',
    'storage/app/public/venues',
];

$results = [];

// Créer le lien symbolique storage dans public/
$storageLink = __DIR__ . '/public/storage';
$storageTarget = __DIR__ . '/storage/app/public';

if (!file_exists($storageLink)) {
    if (symlink($storageTarget, $storageLink)) {
        $results[] = "✅ Lien symbolique créé: public/storage";
    } else {
        $results[] = "❌ Erreur création lien symbolique: public/storage";
    }
} else {
    if (is_link($storageLink)) {
        $results[] = "ℹ️ Lien symbolique existe déjà: public/storage";
    } else {
        $results[] = "⚠️ public/storage existe mais n'est pas un lien symbolique!";
    }
}

// Créer les dossiers
foreach ($dirs as $dir) {
    $fullPath = __DIR__ . '/' . $dir;

    if (!file_exists($fullPath)) {
        if (mkdir($fullPath, 0775, true)) {
            $results[] = "✅ Dossier créé: $dir";
        } else {
            $results[] = "❌ Erreur création: $dir";
        }
    } else {
        $results[] = "ℹ️ Dossier existe déjà: $dir";
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création des dossiers storage</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #272d63;
            margin-bottom: 20px;
        }
        .result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            font-family: monospace;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .result.warning {
            background: #fff3cd;
            color: #856404;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗂️ Création des dossiers storage</h1>

        <div class="results">
            <?php foreach ($results as $result): ?>
                <?php
                    $class = 'info';
                    if (strpos($result, '✅') !== false) $class = 'success';
                    if (strpos($result, '❌') !== false) $class = 'error';
                    if (strpos($result, '⚠️') !== false) $class = 'warning';
                ?>
                <div class="result <?php echo $class; ?>">
                    <?php echo $result; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="warning">
            <strong>⚠️ IMPORTANT:</strong><br>
            Une fois les dossiers créés, <strong>SUPPRIMEZ CE FICHIER</strong> pour des raisons de sécurité:<br>
            <code>rm create-storage-dirs.php</code>
        </div>
    </div>
</body>
</html>
