<?php
// index.php
session_start();
require_once 'includes/functions.php';

$spellManager = new SpellManager();
$tagManager = new TagManager();

$tagFilter = $_GET['tag'] ?? null;
$limit = 6; // Limiter à 6 sorts sur la page d'accueil
$spells = $spellManager->getAllSpells($tagFilter, null, 'created_at', 'DESC', $limit);
$tags = $tagManager->getAllTags();

// Configuration pour le header
$pageTitle = "Accueil - Grimoire des Sorts";
$basePath = './';
$cssPath = 'assets/css/style.css';
$jsPath = 'assets/js/app.js';

include 'includes/header.php';
?>

<!-- Section d'accueil -->
<div class="welcome-section">
    <div class="welcome-card">
        <h2 class="section-title">Bienvenue dans votre Grimoire Magique</h2>
        <p class="welcome-text">
            Découvrez et organisez votre collection de sorts mystiques. 
            Explorez les différentes écoles de magie et créez votre propre bibliothèque arcanique.
        </p>
        <div class="quick-actions">
            <button class="btn btn-success" onclick="openSpellModal()">
                ✨ Ajouter un Sort
            </button>
            <a href="pages/spells.php" class="btn">
                📚 Voir tous les Sorts
            </a>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="filters">
    <h3 class="form-label">Filtrer par école de magie :</h3>
    <div class="filter-tags">
        <div class="filter-tag <?php echo !$tagFilter ? 'active' : ''; ?>" 
             style="background: var(--bronze)" 
             onclick="filterSpells(null)">
            Toutes les écoles
        </div>
        <?php foreach ($tags as $tag): ?>
        <div class="filter-tag <?php echo $tagFilter == $tag['id'] ? 'active' : ''; ?>" 
             style="background-color: <?php echo $tag['color']; ?>" 
             onclick="filterSpells(<?php echo $tag['id']; ?>)">
            <?php echo htmlspecialchars($tag['name']); ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Grille des sorts récents -->
<div class="section-header">
    <h2 class="section-title">📖 Sorts Récents</h2>
    <?php if (count($spells) >= $limit): ?>
    <a href="pages/spells.php" class="view-all-link">Voir tous les sorts →</a>
    <?php endif; ?>
</div>

<div class="spell-grid" id="spellGrid">
    <?php if (empty($spells)): ?>
        <div class="empty-state">
            <div class="empty-icon">📜</div>
            <h3 class="empty-title">Votre grimoire est vide</h3>
            <p class="empty-text">Commencez par ajouter votre premier sort magique !</p>
            <button class="btn btn-success btn-large" onclick="openSpellModal()">
                ✨ Créer mon premier sort
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($spells as $spell): ?>
        <div class="spell-card" style="border-left: 6px solid <?php echo $spell['color']; ?>">
            <?php if ($spell['image_url']): ?>
                <div class="spell-image-container">
                    <img src="<?php echo htmlspecialchars($spell['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($spell['name']); ?>"
                         class="spell-image">
                </div>
            <?php else: ?>
                <div class="spell-placeholder" style="background: linear-gradient(135deg, <?php echo $spell['color']; ?>22, <?php echo $spell['color']; ?>44);">
                    <span style="font-size: 3rem;">🔮</span>
                </div>
            <?php endif; ?>
            
            <h3 class="spell-title" style="color: <?php echo $spell['color']; ?>">
                <?php echo htmlspecialchars($spell['name']); ?>
            </h3>
            
            <p class="spell-description">
                <?php 
                $description = $spell['description'];
                echo nl2br(htmlspecialchars(strlen($description) > 120 ? 
                    substr($description, 0, 120) . '...' : $description)); 
                ?>
            </p>
            
            <?php if ($spell['tag_names']): ?>
            <div class="spell-tags">
                <?php 
                $tagNames = explode(',', $spell['tag_names']);
                $tagColors = explode(',', $spell['tag_colors']);
                for ($i = 0; $i < count($tagNames); $i++): ?>
                    <span class="tag-badge" style="background-color: <?php echo $tagColors[$i]; ?>">
                        <?php echo htmlspecialchars($tagNames[$i]); ?>
                    </span>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
            <div class="spell-actions">
                <button class="btn btn-sm" onclick="editSpell(<?php echo $spell['id']; ?>)">
                    ✏️ Modifier
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteSpell(<?php echo $spell['id']; ?>)">
                    🗑️ Supprimer
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal pour ajouter/modifier un sort -->
<div id="spellModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSpellModal()">&times;</span>
        <h2 id="modalTitle">Nouveau Sort</h2>
        
        <form id="spellForm" enctype="multipart/form-data">
            <input type="hidden" id="spellId" name="spell_id">
            
            <div class="form-group">
                <label for="spellName" class="form-label">Nom du sort :</label>
                <input type="text" id="spellName" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="spellDescription" class="form-label">Description :</label>
                <textarea id="spellDescription" name="description" class="form-textarea" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="spellImage" class="form-label">Image du sort :</label>
                <input type="file" id="spellImage" name="image" class="form-input" accept="image/*">
                <div id="imagePreview"></div>
            </div>
            
            <div class="form-group">
                <label for="spellColor" class="form-label">Couleur :</label>
                <input type="color" id="spellColor" name="color" class="color-picker" value="#8B4513">
            </div>
            
            <div class="form-group">
                <label class="form-label">Écoles de magie :</label>
                <div id="tagCheckboxes" class="tag-checkboxes">
                    <?php foreach ($tags as $tag): ?>
                    <label class="tag-checkbox">
                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>">
                        <span class="tag-label" style="border-left: 4px solid <?php echo $tag['color']; ?>;">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Sauvegarder</button>
                <button type="button" class="btn" onclick="closeSpellModal()">❌ Annuler</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Styles supplémentaires pour l'accueil */
.welcome-section {
    margin-bottom: 40px;
}

.welcome-card {
    background: 
        radial-gradient(circle at 20% 20%, rgba(244, 228, 188, 0.95) 0%, rgba(244, 228, 188, 0.9) 100%);
    border: 3px solid var(--bronze);
    border-radius: 15px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    color: var(--dark-wood);
}

.welcome-text {
    font-size: 1.2rem;
    line-height: 1.6;
    margin: 20px 0 30px;
}

.quick-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.view-all-link {
    color: var(--gold);
    text-decoration: none;
    font-weight: bold;
    transition: color 0.3s ease;
}

.view-all-link:hover {
    color: var(--bronze);
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: rgba(244, 228, 188, 0.1);
    border: 2px dashed var(--bronze);
    border-radius: 15px;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.7;
}

.empty-title {
    font-family: 'Cinzel', serif;
    font-size: 2rem;
    color: var(--bronze);
    margin-bottom: 15px;
}

.empty-text {
    font-size: 1.1rem;
    color: var(--light-wood);
    margin-bottom: 30px;
}

.spell-image-container {
    position: relative;
    margin-bottom: 15px;
    overflow: hidden;
    border-radius: 8px;
}

.spell-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.spell-card:hover .spell-image {
    transform: scale(1.05);
}

.spell-placeholder {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    margin-bottom: 15px;
}

.spell-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn-sm {
    padding: 8px 15px;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .quick-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .quick-actions .btn {
        width: 200px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>