<?php
// pages/tag_form.php
session_start();
require_once '../includes/functions.php';

$tagManager = new TagManager();

$tagId = $_GET['id'] ?? null;
$tag = null;
$isEdit = false;

// Si ID fourni, récupérer le tag existant
if ($tagId) {
    $tag = $tagManager->getTagById($tagId);
    if ($tag) {
        $isEdit = true;
    } else {
        setNotification("École de magie introuvable", "error");
        header('Location: tags.php');
        exit;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'name' => sanitize($_POST['name']),
            'color' => sanitize($_POST['color'])
        ];
        
        // Validations
        if (empty($data['name'])) {
            throw new Exception("Le nom de l'école de magie est requis");
        }
        
        if (strlen($data['name']) < 2 || strlen($data['name']) > 50) {
            throw new Exception("Le nom doit contenir entre 2 et 50 caractères");
        }
        
        if (!isValidColor($data['color'])) {
            throw new Exception("Couleur invalide");
        }
        
        if ($isEdit) {
            $tagManager->updateTag($tagId, $data);
            setNotification("École de magie modifiée avec succès !", "success");
        } else {
            $tagManager->createTag($data);
            setNotification("École de magie créée avec succès !", "success");
        }
        
        header('Location: tags.php');
        exit;
        
    } catch (Exception $e) {
        setNotification($e->getMessage(), "error");
    }
}

// Récupérer les couleurs suggérées pour les écoles de magie
$suggestedColors = [
    ['name' => 'Feu', 'color' => '#FF4500'],
    ['name' => 'Eau', 'color' => '#1E90FF'],
    ['name' => 'Terre', 'color' => '#8B4513'],
    ['name' => 'Air', 'color' => '#87CEEB'],
    ['name' => 'Lumière', 'color' => '#FFD700'],
    ['name' => 'Ténèbres', 'color' => '#4B0082'],
    ['name' => 'Guérison', 'color' => '#32CD32'],
    ['name' => 'Illusion', 'color' => '#9370DB'],
    ['name' => 'Invocation', 'color' => '#DC143C'],
    ['name' => 'Enchantement', 'color' => '#FF69B4'],
    ['name' => 'Divination', 'color' => '#9932CC'],
    ['name' => 'Transmutation', 'color' => '#20B2AA'],
    ['name' => 'Abjuration', 'color' => '#4169E1'],
    ['name' => 'Nécromancie', 'color' => '#2F4F4F'],
];

// Configuration pour le header
$pageTitle = ($isEdit ? "Modifier" : "Nouvelle") . " École de Magie - Grimoire";
$basePath = '../';
$cssPath = '../assets/css/style.css';
$jsPath = '../assets/js/app.js';
$headerIcon = $isEdit ? '✏️' : '🏫';

include '../includes/header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2 class="section-title">
            <?php echo $isEdit ? '✏️ Modifier l\'École de Magie' : '🏫 Créer une Nouvelle École de Magie'; ?>
        </h2>
        <p class="form-subtitle">
            <?php echo $isEdit ? 'Modifiez les détails de cette école mystique' : 'Ajoutez une nouvelle école de magie à votre grimoire'; ?>
        </p>
    </div>
    
    <form id="tagForm" method="POST" class="tag-form">
        <!-- Informations de base -->
        <div class="form-section">
            <h3 class="form-section-title">📝 Informations de Base</h3>
            
            <div class="form-group">
                <label for="name" class="form-label">Nom de l'École</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       class="form-input" 
                       value="<?php echo $tag ? htmlspecialchars($tag['name']) : ''; ?>"
                       required 
                       maxlength="50"
                       placeholder="Ex: École de Pyromanie">
                <small class="form-help">Donnez un nom unique à cette école de magie (2-50 caractères)</small>
                <div class="character-count">
                    <span id="nameCharCount">0</span> / 50 caractères
                </div>
            </div>
        </div>
        
        <!-- Couleur -->
        <div class="form-section">
            <h3 class="form-section-title">🎨 Couleur Représentative</h3>
            
            <div class="form-group">
                <label for="color" class="form-label">Couleur de l'École</label>
                <div class="color-selection">
                    <div class="color-input-group">
                        <input type="color" 
                               id="color" 
                               name="color" 
                               class="color-picker"
                               value="<?php echo $tag ? $tag['color'] : '#654321'; ?>">
                        <input type="text" 
                               id="colorText" 
                               class="form-input color-text" 
                               value="<?php echo $tag ? $tag['color'] : '#654321'; ?>"
                               pattern="#[0-9A-Fa-f]{6}"
                               maxlength="7">
                        <div class="color-preview" id="colorPreview"></div>
                    </div>
                    
                    <small class="form-help">Cette couleur sera utilisée pour identifier l'école dans tout le grimoire</small>
                </div>
            </div>
            
            <!-- Couleurs suggérées -->
            <div class="form-group">
                <label class="form-label">Couleurs Suggérées</label>
                <div class="suggested-colors">
                    <?php foreach ($suggestedColors as $suggested): ?>
                    <div class="suggested-color-item" 
                         style="background-color: <?php echo $suggested['color']; ?>"
                         title="<?php echo htmlspecialchars($suggested['name']); ?> - <?php echo $suggested['color']; ?>"
                         onclick="selectSuggestedColor('<?php echo $suggested['color']; ?>', '<?php echo htmlspecialchars($suggested['name']); ?>')">
                        <span class="suggested-color-name"><?php echo htmlspecialchars($suggested['name']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <small class="form-help">Cliquez sur une couleur pour l'appliquer rapidement</small>
            </div>
        </div>
        
        <!-- Aperçu -->
        <div class="form-section">
            <h3 class="form-section-title">👁️ Aperçu</h3>
            
            <div class="tag-preview">
                <h4>Aperçu du Tag :</h4>
                <div class="preview-examples">
                    <span class="tag-badge preview-tag" id="previewBadge">
                        <span id="previewText"><?php echo $tag ? htmlspecialchars($tag['name']) : 'Nom de l\'École'; ?></span>
                    </span>
                    
                    <div class="preview-card" id="previewCard">
                        <div class="card-header">
                            <h5 id="previewCardTitle">Sort d'Exemple</h5>
                        </div>
                        <p>Voici comment apparaîtrait un sort associé à cette école de magie.</p>
                        <div class="spell-tags">
                            <span class="tag-badge preview-tag-small" id="previewBadgeSmall">
                                <span id="previewTextSmall"><?php echo $tag ? htmlspecialchars($tag['name']) : 'Nom de l\'École'; ?></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistiques (pour modification) -->
        <?php if ($isEdit): ?>
        <div class="form-section">
            <h3 class="form-section-title">📊 Statistiques</h3>
            
            <div class="stats-info">
                <?php
                try {
                    $spellManager = new SpellManager();
                    $spells = $spellManager->getAllSpells($tagId);
                    $spellCount = count($spells);
                    
                    echo "<div class='stat-item'>";
                    echo "<strong>📚 Sorts associés :</strong> {$spellCount}";
                    echo "</div>";
                    
                    if ($spellCount > 0) {
                        echo "<div class='stat-item'>";
                        echo "<strong>📅 Créé le :</strong> " . date('d/m/Y à H:i', strtotime($tag['created_at']));
                        echo "</div>";
                        
                        echo "<div class='associated-spells'>";
                        echo "<h5>Sorts associés à cette école :</h5>";
                        echo "<ul class='spell-list'>";
                        foreach (array_slice($spells, 0, 5) as $spell) {
                            echo "<li>🔮 " . htmlspecialchars($spell['name']) . "</li>";
                        }
                        if ($spellCount > 5) {
                            echo "<li><em>... et " . ($spellCount - 5) . " autre(s)</em></li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    }
                    
                    if ($spellCount > 0) {
                        echo "<div class='warning-section'>";
                        echo "<p class='warning-text'>⚠️ <strong>Attention :</strong> Si vous supprimez cette école, elle sera retirée de tous les sorts associés.</p>";
                        echo "</div>";
                    }
                } catch (Exception $e) {
                    echo "<p class='text-muted'>Statistiques indisponibles</p>";
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-success btn-large">
                <?php echo $isEdit ? '💾 Sauvegarder les Modifications' : '🏫 Créer l\'École'; ?>
            </button>
            <a href="tags.php" class="btn btn-large">
                ❌ Annuler
            </a>
            <?php if ($isEdit): ?>
            <button type="button" class="btn btn-danger btn-large" onclick="confirmDelete()">
                🗑️ Supprimer l'École
            </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
<!-- Formulaire caché pour supprimer -->
<form id="deleteForm" method="POST" action="../api/tags.php" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="tag_id" value="<?php echo $tagId; ?>">
</form>
<?php endif; ?>

<style>
/* Styles spécifiques au formulaire de tag */
.color-selection {
    margin-bottom: 20px;
}

.color-input-group {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-bottom: 15px;
}

.color-preview {
    width: 60px;
    height: 40px;
    border-radius: 8px;
    border: 3px solid var(--bronze);
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.suggested-colors {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.suggested-color-item {
    padding: 12px 8px;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
    font-weight: bold;
    font-size: 0.9em;
}

.suggested-color-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    border-color: var(--gold);
}

.suggested-color-name {
    display: block;
}

.tag-preview {
    background: rgba(255,255,255,0.5);
    border-radius: 10px;
    padding: 20px;
    border: 2px dashed var(--bronze);
}

.preview-examples {
    display: flex;
    flex-direction: column;
    gap: 20px;
    align-items: center;
}

.preview-tag {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    border: 2px solid rgba(0,0,0,0.2);
    font-size: 1.1em;
    transition: all 0.3s ease;
}

.preview-tag-small {
    padding: 5px 10px;
    border-radius: 15px;
    font-weight: bold;
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    border: 2px solid rgba(0,0,0,0.2);
    font-size: 0.9em;
}

.preview-card {
    background: rgba(244, 228, 188, 0.9);
    border: 3px solid var(--bronze);
    border-radius: 10px;
    padding: 20px;
    max-width: 300px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    color: var(--dark-wood);
}

.preview-card .card-header {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--bronze);
}

.stats-info {
    background: rgba(255,255,255,0.5);
    border-radius: 10px;
    padding: 20px;
}

.stat-item {
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(139, 90, 43, 0.3);
}

.associated-spells {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid var(--bronze);
}

.spell-list {
    list-style: none;
    padding: 0;
}

.spell-list li {
    padding: 5px 0;
    border-left: 3px solid var(--bronze);
    padding-left: 15px;
    margin-bottom: 5px;
}

.warning-section {
    margin-top: 20px;
    padding: 15px;
    background: rgba(220, 20, 60, 0.1);
    border: 2px solid #dc143c;
    border-radius: 8px;
}

.warning-text {
    color: #dc143c;
    margin: 0;
}

@media (max-width: 768px) {
    .color-input-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .suggested-colors {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
    
    .preview-examples {
        align-items: stretch;
    }
    
    .preview-card {
        max-width: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const nameInput = document.getElementById('name');
    const colorPicker = document.getElementById('color');
    const colorText = document.getElementById('colorText');
    const colorPreview = document.getElementById('colorPreview');
    const nameCharCount = document.getElementById('nameCharCount');
    
    // Éléments d'aperçu
    const previewBadge = document.getElementById('previewBadge');
    const previewText = document.getElementById('previewText');
    const previewBadgeSmall = document.getElementById('previewBadgeSmall');
    const previewTextSmall = document.getElementById('previewTextSmall');
    const previewCard = document.getElementById('previewCard');
    const previewCardTitle = document.getElementById('previewCardTitle');
    
    // Initialisation
    updatePreview();
    updateCharCount();
    
    // Synchronisation du color picker avec le text input
    colorPicker.addEventListener('change', function() {
        colorText.value = this.value.toUpperCase();
        updatePreview();
    });
    
    colorText.addEventListener('input', function() {
        let value = this.value;
        if (!value.startsWith('#')) {
            value = '#' + value;
            this.value = value;
        }
        
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            colorPicker.value = value;
            updatePreview();
        }
    });
    
    // Mise à jour du nom
    nameInput.addEventListener('input', function() {
        updatePreview();
        updateCharCount();
    });
    
    function updatePreview() {
        const name = nameInput.value || 'Nom de l\'École';
        const color = colorPicker.value;
        
        // Mise à jour du texte
        previewText.textContent = name;
        previewTextSmall.textContent = name;
        
        // Mise à jour des couleurs
        previewBadge.style.backgroundColor = color;
        previewBadgeSmall.style.backgroundColor = color;
        colorPreview.style.backgroundColor = color;
        previewCard.style.borderLeftColor = color;
        previewCardTitle.style.color = color;
        
        // Effet de brillance sur l'aperçu
        previewBadge.style.boxShadow = `0 0 15px ${color}40`;
    }
    
    function updateCharCount() {
        const count = nameInput.value.length;
        nameCharCount.textContent = count;
        
        if (count > 45) {
            nameCharCount.style.color = '#dc143c';
        } else if (count > 35) {
            nameCharCount.style.color = '#ff8c00';
        } else {
            nameCharCount.style.color = '#8b5a2b';
        }
    }
    
    // Animation des couleurs suggérées
    const suggestedColors = document.querySelectorAll('.suggested-color-item');
    suggestedColors.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.classList.add('cascade-animation');
    });
});

function selectSuggestedColor(color, name) {
    document.getElementById('color').value = color;
    document.getElementById('colorText').value = color.toUpperCase();
    
    // Optionnellement, suggérer le nom si le champ est vide
    const nameInput = document.getElementById('name');
    if (!nameInput.value.trim()) {
        nameInput.value = name;
    }
    
    // Mettre à jour l'aperçu
    const event = new Event('input');
    nameInput.dispatchEvent(event);
    
    // Effet visuel
    const colorPreview = document.getElementById('colorPreview');
    colorPreview.style.transform = 'scale(1.2)';
    setTimeout(() => {
        colorPreview.style.transform = 'scale(1)';
    }, 200);
    
    showNotification(`Couleur ${name} sélectionnée !`, 'success');
}

function confirmDelete() {
    const name = document.getElementById('name').value;
    if (confirm(`Êtes-vous sûr de vouloir supprimer définitivement l'école "${name}" ?\n\nCette action retirera cette école de tous les sorts associés et ne peut pas être annulée.`)) {
        document.getElementById('deleteForm').submit();
    }
}

// Validation du formulaire
document.getElementById('tagForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const color = document.getElementById('color').value;
    
    if (!name) {
        e.preventDefault();
        alert('Le nom de l\'école de magie est requis');
        document.getElementById('name').focus();
        return;
    }
    
    if (name.length < 2) {
        e.preventDefault();
        alert('Le nom doit contenir au moins 2 caractères');
        document.getElementById('name').focus();
        return;
    }
    
    if (name.length > 50) {
        e.preventDefault();
        alert('Le nom ne peut pas dépasser 50 caractères');
        document.getElementById('name').focus();
        return;
    }
    
    if (!/^#[0-9A-Fa-f]{6}$/.test(color)) {
        e.preventDefault();
        alert('La couleur doit être au format hexadécimal (#RRGGBB)');
        document.getElementById('color').focus();
        return;
    }
});

// Fonction pour afficher les notifications (copie de app.js)
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 1000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
    `;
    
    switch (type) {
        case 'success':
            notification.style.background = 'linear-gradient(135deg, #228b22, #32cd32)';
            break;
        case 'error':
            notification.style.background = 'linear-gradient(135deg, #8b0000, #dc143c)';
            break;
        default:
            notification.style.background = 'linear-gradient(135deg, #4682b4, #87ceeb)';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Animation d'apparition
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Suppression automatique
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>

<?php include '../includes/footer.php'; ?>