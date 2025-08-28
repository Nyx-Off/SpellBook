<?php
// pages/spell_form.php
session_start();
require_once '../includes/functions.php';

$spellManager = new SpellManager();
$tagManager = new TagManager();

$spellId = $_GET['id'] ?? null;
$spell = null;
$isEdit = false;

// Si ID fourni, récupérer le sort existant
if ($spellId) {
    $spell = $spellManager->getSpellById($spellId);
    if ($spell) {
        $isEdit = true;
    } else {
        setNotification("Sort introuvable", "error");
        header('Location: spells.php');
        exit;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Gestion de l'upload d'image
        $imageUrl = null;
        if ($isEdit) {
            $imageUrl = $spell['image_url']; // Garder l'image existante par défaut
        }
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $newImageUrl = uploadImage($_FILES['image']);
                if ($newImageUrl) {
                    // Supprimer l'ancienne image si elle existe
                    if ($isEdit && $spell['image_url'] && file_exists('../' . $spell['image_url'])) {
                        unlink('../' . $spell['image_url']);
                    }
                    $imageUrl = $newImageUrl;
                }
            } catch (Exception $e) {
                throw new Exception("Erreur upload image : " . $e->getMessage());
            }
        }
        
        $data = [
            'name' => sanitize($_POST['name']),
            'description' => sanitize($_POST['description']),
            'image_url' => $imageUrl,
            'color' => sanitize($_POST['color']),
            'tags' => $_POST['tags'] ?? []
        ];
        
        // Validations
        if (empty($data['name'])) {
            throw new Exception("Le nom du sort est requis");
        }
        
        if (empty($data['description'])) {
            throw new Exception("La description est requise");
        }
        
        if (!isValidColor($data['color'])) {
            throw new Exception("Couleur invalide");
        }
        
        if ($isEdit) {
            $spellManager->updateSpell($spellId, $data);
            setNotification("Sort modifié avec succès !", "success");
        } else {
            $spellId = $spellManager->createSpell($data);
            setNotification("Sort créé avec succès !", "success");
        }
        
        header('Location: spells.php');
        exit;
        
    } catch (Exception $e) {
        setNotification($e->getMessage(), "error");
    }
}

$tags = $tagManager->getAllTags();

// Configuration pour le header
$pageTitle = ($isEdit ? "Modifier" : "Nouveau") . " Sort - Grimoire";
$basePath = '../';
$cssPath = '../assets/css/style.css';
$jsPath = '../assets/js/app.js';
$headerIcon = $isEdit ? '✏️' : '✨';

include '../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2 class="section-title">
            <?php echo $isEdit ? '✏️ Modifier le Sort' : '✨ Créer un Nouveau Sort'; ?>
        </h2>
        <p class="form-subtitle">
            <?php echo $isEdit ? 'Modifiez les détails de votre sort magique' : 'Ajoutez un nouveau sort à votre grimoire'; ?>
        </p>
    </div>
    
    <form id="spellForm" method="POST" enctype="multipart/form-data" class="spell-form">
        <!-- Informations de base -->
        <div class="form-section">
            <h3 class="form-section-title">📝 Informations de Base</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Nom du Sort</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-input" 
                           value="<?php echo $spell ? htmlspecialchars($spell['name']) : ''; ?>"
                           required 
                           placeholder="Ex: Boule de Feu">
                    <small class="form-help">Donnez un nom évocateur à votre sort</small>
                </div>
                
                <div class="form-group">
                    <label for="color" class="form-label">Couleur Mystique</label>
                    <div class="color-input-group">
                        <input type="color" 
                               id="color" 
                               name="color" 
                               class="color-picker"
                               value="<?php echo $spell ? $spell['color'] : '#8B4513'; ?>">
                        <input type="text" 
                               id="colorText" 
                               class="form-input color-text" 
                               value="<?php echo $spell ? $spell['color'] : '#8B4513'; ?>"
                               pattern="#[0-9A-Fa-f]{6}"
                               readonly>
                    </div>
                    <small class="form-help">Cette couleur représentera votre sort</small>
                </div>
            </div>
        </div>
        
        <!-- Description -->
        <div class="form-section">
            <h3 class="form-section-title">📜 Description</h3>
            
            <div class="form-group">
                <label for="description" class="form-label">Description Détaillée</label>
                <textarea id="description" 
                          name="description" 
                          class="form-textarea" 
                          required 
                          rows="6"
                          placeholder="Décrivez les effets, la portée, les composantes et les limitations de votre sort..."><?php echo $spell ? htmlspecialchars($spell['description']) : ''; ?></textarea>
                <div class="character-count">
                    <span id="charCount">0</span> caractères
                </div>
            </div>
        </div>
        
        <!-- Image -->
        <div class="form-section">
            <h3 class="form-section-title">🖼️ Illustration Mystique</h3>
            
            <div class="form-group">
                <label for="image" class="form-label">Image du Sort</label>
                <div class="image-upload-area">
                    <input type="file" 
                           id="image" 
                           name="image" 
                           class="form-input" 
                           accept="image/*">
                    
                    <?php if ($spell && $spell['image_url']): ?>
                    <div class="current-image">
                        <p class="form-help">Image actuelle :</p>
                        <img src="../<?php echo htmlspecialchars($spell['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($spell['name']); ?>"
                             class="current-image-preview">
                    </div>
                    <?php endif; ?>
                    
                    <div id="imagePreview" class="image-preview"></div>
                </div>
                <small class="form-help">Formats acceptés : JPG, PNG, GIF, WebP (max 5MB)</small>
            </div>
        </div>
        
        <!-- Écoles de magie -->
        <div class="form-section">
            <h3 class="form-section-title">🏫 Écoles de Magie</h3>
            
            <div class="form-group">
                <label class="form-label">Associer à des Écoles</label>
                <div class="tag-selection">
                    <?php 
                    $selectedTags = [];
                    if ($spell && $spell['tag_ids']) {
                        $selectedTags = explode(',', $spell['tag_ids']);
                    }
                    
                    foreach ($tags as $tag): ?>
                    <label class="tag-checkbox">
                        <input type="checkbox" 
                               name="tags[]" 
                               value="<?php echo $tag['id']; ?>"
                               <?php echo in_array($tag['id'], $selectedTags) ? 'checked' : ''; ?>>
                        <span class="tag-label" style="border-left: 4px solid <?php echo $tag['color']; ?>;">
                            <span class="tag-color" style="background-color: <?php echo $tag['color']; ?>"></span>
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <small class="form-help">Sélectionnez une ou plusieurs écoles de magie</small>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-success btn-large">
                <?php echo $isEdit ? '💾 Sauvegarder les Modifications' : '✨ Créer le Sort'; ?>
            </button>
            <a href="spells.php" class="btn btn-large">
                ❌ Annuler
            </a>
            <?php if ($isEdit): ?>
            <button type="button" class="btn btn-danger btn-large" onclick="confirmDelete()">
                🗑️ Supprimer le Sort
            </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
<!-- Formulaire caché pour supprimer -->
<form id="deleteForm" method="POST" action="../api/spells.php" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="spell_id" value="<?php echo $spellId; ?>">
</form>
<?php endif; ?>

<style>
/* Styles spécifiques au formulaire */
.form-container {
    max-width: 800px;
    margin: 0 auto;
    background: rgba(244, 228, 188, 0.95);
    border: 3px solid var(--bronze);
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.form-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--bronze);
}

.form-subtitle {
    color: var(--dark-wood);
    font-style: italic;
    margin-top: 10px;
}

.form-section {
    margin-bottom: 30px;
    padding: 20px;
    background: rgba(255,255,255,0.3);
    border-radius: 10px;
    border-left: 4px solid var(--bronze);
}

.form-section-title {
    font-family: 'Cinzel', serif;
    color: var(--dark-wood);
    margin-bottom: 20px;
    font-size: 1.3rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.color-input-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.color-text {
    flex: 1;
    font-family: monospace;
}

.form-help {
    display: block;
    margin-top: 5px;
    color: var(--light-wood);
    font-size: 0.9em;
    font-style: italic;
}

.character-count {
    text-align: right;
    margin-top: 5px;
    font-size: 0.9em;
    color: var(--light-wood);
}

.image-upload-area {
    border: 2px dashed var(--bronze);
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
}

.image-upload-area:hover {
    border-color: var(--gold);
    background: rgba(255, 215, 0, 0.1);
}

.current-image-preview,
.image-preview img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 10px;
    margin-top: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.tag-selection {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.tag-checkbox {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.5);
}

.tag-checkbox:hover {
    background: rgba(255,255,255,0.8);
    transform: translateX(5px);
}

.tag-checkbox input[type="checkbox"] {
    margin-right: 10px;
    transform: scale(1.2);
}

.tag-label {
    display: flex;
    align-items: center;
    flex: 1;
    padding: 5px 10px;
    border-radius: 5px;
    background: rgba(255,255,255,0.7);
}

.tag-color {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid rgba(0,0,0,0.2);
}

.btn-large {
    padding: 15px 30px;
    font-size: 1.1em;
    margin: 0 10px;
}

.form-actions {
    text-align: center;
    padding-top: 20px;
    border-top: 2px solid var(--bronze);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .tag-selection {
        grid-template-columns: 1fr;
    }
    
    .btn-large {
        display: block;
        margin: 10px 0;
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Synchronisation du color picker avec le text input
    const colorPicker = document.getElementById('color');
    const colorText = document.getElementById('colorText');
    
    colorPicker.addEventListener('change', function() {
        colorText.value = this.value.toUpperCase();
        updateColorPreview(this.value);
    });
    
    colorText.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            colorPicker.value = this.value;
            updateColorPreview(this.value);
        }
    });
    
    // Compteur de caractères
    const description = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    
    function updateCharCount() {
        const count = description.value.length;
        charCount.textContent = count;
        
        if (count > 500) {
            charCount.style.color = '#dc143c';
        } else if (count > 300) {
            charCount.style.color = '#ff8c00';
        } else {
            charCount.style.color = '#8b5a2b';
        }
    }
    
    description.addEventListener('input', updateCharCount);
    updateCharCount();
    
    // Prévisualisation de l'image
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Vérifier la taille du fichier (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('Le fichier est trop volumineux. Taille maximum : 5MB');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.innerHTML = `
                    <img src="${e.target.result}" alt="Aperçu" class="image-preview">
                    <p><strong>Nouvelle image sélectionnée</strong></p>
                `;
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.innerHTML = '';
        }
    });
    
    // Animation des checkboxes
    const checkboxes = document.querySelectorAll('.tag-checkbox input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.closest('.tag-checkbox');
            if (this.checked) {
                label.style.background = 'rgba(255, 215, 0, 0.3)';
                label.style.borderLeft = '4px solid var(--gold)';
            } else {
                label.style.background = 'rgba(255,255,255,0.5)';
                label.style.borderLeft = 'none';
            }
        });
        
        // Appliquer le style initial si coché
        if (checkbox.checked) {
            const label = checkbox.closest('.tag-checkbox');
            label.style.background = 'rgba(255, 215, 0, 0.3)';
            label.style.borderLeft = '4px solid var(--gold)';
        }
    });
});

function updateColorPreview(color) {
    // Vous pouvez ajouter une prévisualisation de la couleur ici
    document.documentElement.style.setProperty('--preview-color', color);
}

function confirmDelete() {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement ce sort ? Cette action est irréversible.')) {
        document.getElementById('deleteForm').submit();
    }
}

// Validation du formulaire
document.getElementById('spellForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const description = document.getElementById('description').value.trim();
    const color = document.getElementById('color').value;
    
    if (!name) {
        e.preventDefault();
        alert('Le nom du sort est requis');
        document.getElementById('name').focus();
        return;
    }
    
    if (!description) {
        e.preventDefault();
        alert('La description du sort est requise');
        document.getElementById('description').focus();
        return;
    }
    
    if (!/^#[0-9A-Fa-f]{6}$/.test(color)) {
        e.preventDefault();
        alert('La couleur doit être au format hexadécimal (#RRGGBB)');
        document.getElementById('color').focus();
        return;
    }
});
</script>

<?php include '../includes/footer.php'; ?>