<?php
// pages/tags.php
session_start();
require_once '../includes/functions.php';

$tagManager = new TagManager();
$tags = $tagManager->getAllTags();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
                $data = [
                    'name' => sanitize($_POST['name']),
                    'color' => sanitize($_POST['color'])
                ];
                
                if (empty($data['name'])) {
                    throw new Exception("Le nom du tag est requis");
                }
                
                if (!isValidColor($data['color'])) {
                    throw new Exception("Couleur invalide");
                }
                
                $tagManager->createTag($data);
                setNotification("Tag créé avec succès", "success");
                break;
                
            case 'update':
                $id = (int)$_POST['tag_id'];
                $data = [
                    'name' => sanitize($_POST['name']),
                    'color' => sanitize($_POST['color'])
                ];
                
                if (empty($data['name'])) {
                    throw new Exception("Le nom du tag est requis");
                }
                
                if (!isValidColor($data['color'])) {
                    throw new Exception("Couleur invalide");
                }
                
                $tagManager->updateTag($id, $data);
                setNotification("Tag modifié avec succès", "success");
                break;
                
            case 'delete':
                $id = (int)$_POST['tag_id'];
                $tagManager->deleteTag($id);
                setNotification("Tag supprimé avec succès", "success");
                break;
        }
        
        header('Location: tags.php');
        exit;
        
    } catch (Exception $e) {
        setNotification($e->getMessage(), "error");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tags - Grimoire</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>🏷️ Gestion des Écoles de Magie</h1>
            <nav class="nav">
                <a href="../index.php" class="nav-btn">Retour au Grimoire</a>
                <a href="#" onclick="openTagModal()" class="nav-btn">Nouvelle École</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php displayNotification(); ?>

        <div class="tag-grid">
            <?php foreach ($tags as $tag): ?>
            <div class="tag-card" style="border-left: 6px solid <?php echo $tag['color']; ?>">
                <h3 class="tag-title" style="color: <?php echo $tag['color']; ?>">
                    <?php echo htmlspecialchars($tag['name']); ?>
                </h3>
                
                <div class="tag-badge" style="background-color: <?php echo $tag['color']; ?>; display: inline-block; margin: 10px 0;">
                    Aperçu du tag
                </div>
                
                <div style="margin-top: 15px;">
                    <button class="btn" onclick="editTag(<?php echo $tag['id']; ?>, '<?php echo htmlspecialchars($tag['name'], ENT_QUOTES); ?>', '<?php echo $tag['color']; ?>')">
                        Modifier
                    </button>
                    <button class="btn btn-danger" onclick="deleteTag(<?php echo $tag['id']; ?>)">
                        Supprimer
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal pour ajouter/modifier un tag -->
    <div id="tagModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeTagModal()">&times;</span>
            <h2 id="tagModalTitle">Nouvelle École de Magie</h2>
            
            <form id="tagForm" method="POST">
                <input type="hidden" id="tagAction" name="action" value="create">
                <input type="hidden" id="tagId" name="tag_id">
                
                <div class="form-group">
                    <label for="tagName" class="form-label">Nom de l'école :</label>
                    <input type="text" id="tagName" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="tagColor" class="form-label">Couleur :</label>
                    <input type="color" id="tagColor" name="color" class="color-picker" value="#654321">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-success">Sauvegarder</button>
                    <button type="button" class="btn" onclick="closeTagModal()">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulaire caché pour supprimer -->
    <form id="deleteTagForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="deleteTagId" name="tag_id">
    </form>

    <script>
        function openTagModal() {
            document.getElementById('tagModal').style.display = 'block';
            document.getElementById('tagModalTitle').textContent = 'Nouvelle École de Magie';
            document.getElementById('tagAction').value = 'create';
            document.getElementById('tagForm').reset();
        }

        function closeTagModal() {
            document.getElementById('tagModal').style.display = 'none';
        }

        function editTag(id, name, color) {
            document.getElementById('tagModal').style.display = 'block';
            document.getElementById('tagModalTitle').textContent = 'Modifier l\'École de Magie';
            document.getElementById('tagAction').value = 'update';
            document.getElementById('tagId').value = id;
            document.getElementById('tagName').value = name;
            document.getElementById('tagColor').value = color;
        }

        function deleteTag(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette école de magie ?')) {
                document.getElementById('deleteTagId').value = id;
                document.getElementById('deleteTagForm').submit();
            }
        }

        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('tagModal');
            if (event.target === modal) {
                closeTagModal();
            }
        }
    </script>
</body>
</html>