<?php
// includes/header.php
if (!isset($pageTitle)) {
    $pageTitle = "Grimoire des Sorts";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="<?php echo isset($cssPath) ? $cssPath : '../assets/css/style.css'; ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo isset($basePath) ? $basePath : '../'; ?>assets/images/favicon.ico">
    <meta name="description" content="Grimoire personnel pour gérer vos sorts de JDR">
    <meta name="keywords" content="JDR, sorts, magie, grimoire, RPG">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>
                <?php if (isset($headerIcon)) echo $headerIcon; ?>
                📜 Grimoire des Sorts 🔮
                <?php if (isset($headerIcon)) echo $headerIcon; ?>
            </h1>
            <nav class="nav">
                <a href="<?php echo isset($basePath) ? $basePath : '../'; ?>index.php" 
                   class="nav-btn <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                    📖 Grimoire
                </a>
                <a href="#" onclick="openSpellModal()" class="nav-btn">
                    ✨ Nouveau Sort
                </a>
                <a href="<?php echo isset($basePath) ? $basePath : '../'; ?>pages/spells.php" 
                   class="nav-btn <?php echo (basename($_SERVER['PHP_SELF']) == 'spells.php') ? 'active' : ''; ?>">
                    🗂️ Tous les Sorts
                </a>
                <a href="<?php echo isset($basePath) ? $basePath : '../'; ?>pages/tags.php" 
                   class="nav-btn <?php echo (basename($_SERVER['PHP_SELF']) == 'tags.php') ? 'active' : ''; ?>">
                    🏷️ Écoles de Magie
                </a>
                <a href="#" onclick="showStats()" class="nav-btn">
                    📊 Statistiques
                </a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php 
        // Affichage des notifications
        if (function_exists('displayNotification')) {
            displayNotification(); 
        }
        ?>