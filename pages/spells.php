<?php
// pages/spells.php
session_start();
require_once '../includes/functions.php';

$spellManager = new SpellManager();
$tagManager = new TagManager();

// Paramètres de pagination et filtres
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 12;
$search = $_GET['search'] ?? '';
$tagFilter = $_GET['tag'] ?? null;
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';

$offset = ($page - 1) * $limit;

// Récupération des données
$spells = $spellManager->getAllSpells($tagFilter, $search, $sortBy, $sortOrder, $limit, $offset);
$totalSpells = $spellManager->countSpells($tagFilter, $search);
$totalPages = ceil($totalSpells / $limit);
$tags = $tagManager->getAllTags();

// Configuration pour le header
$pageTitle = "Tous les Sorts - Grimoire";
$basePath = '../';
$cssPath = '../assets/css/style.css';
$jsPath = '../assets/js/app.js';
$headerIcon = '📖';

include '../includes/header.php';
?>

<!-- Outils de recherche et filtrage -->
<div class="spell-tools">
    <div class="search-section">
        <h2 class="section-title">🔍 Recherche et Filtres</h2>
        <div class="tools-grid">
            <div class="tool-group">
                <label for="searchInput" class="form-label">Rechercher un sort :</label>
                <input type="text" id="searchInput" class="form-input" placeholder="Nom du sort..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="tool-group">
                <label for="sortSelect" class="form-label">Trier par :</label>
                <select id="sortSelect" class="form-select">
                    <option value="created_at,DESC" <?php echo ($sortBy == 'created_at' && $sortOrder == 'DESC') ? 'selected' : ''; ?>>Plus récents</option>
                    <option value="created_at,ASC" <?php echo ($sortBy == 'created_at' && $sortOrder == 'ASC') ? 'selected' : ''; ?>>Plus anciens</option>
                    <option value="name,ASC" <?php echo ($sortBy == 'name' && $sortOrder == 'ASC') ? 'selected' : ''; ?>>Nom A-Z</option>
                    <option value="name,DESC" <?php echo ($sortBy == 'name' && $sortOrder == 'DESC') ? 'selected' : ''; ?>>Nom Z-A</option>
                </select>
            </div>
            
            <div class="tool-group">
                <label for="limitSelect" class="form-label">Sorts par page :</label>
                <select id="limitSelect" class="form-select">
                    <option value="6" <?php echo $limit == 6 ? 'selected' : ''; ?>>6</option>
                    <option value="12" <?php echo $limit == 12 ? 'selected' : ''; ?>>12</option>
                    <option value="24" <?php echo $limit == 24 ? 'selected' : ''; ?>>24</option>
                    <option value="all">Tous</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Filtres par tags -->
    <div class="filters">
        <h3 class="form-label">Filtrer par école de magie :</h3>
        <div class="filter-tags">
            <div class="filter-tag <?php echo !$tagFilter ? 'active' : ''; ?>" 
                 style="background: var(--bronze)" 
                 onclick="applyFilters(null)">
                Toutes les écoles
            </div>
            <?php foreach ($tags as $tag): ?>
            <div class="filter-tag <?php echo $tagFilter == $tag['id'] ? 'active' : ''; ?>" 
                 style="background-color: <?php echo $tag['color']; ?>" 
                 onclick="applyFilters(<?php echo $tag['id']; ?>)">
                <?php echo htmlspecialchars($tag['name']); ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Résultats -->
<div class="results-info">
    <h2 class="section-title">
        📚 Résultats : <?php echo $totalSpells; ?> sort(s) trouvé(s)
        <?php if ($search): ?>
            pour "<?php echo htmlspecialchars($search); ?>"
        <?php endif; ?>
    </h2>
</div>

<!-- Grille des sorts -->
<div class="spell-grid" id="spellGrid">
    <?php if (empty($spells)): ?>
        <div class="empty-state">
            <h3 class="spell-title">🔮 Aucun sort trouvé</h3>
            <p>Aucun sort ne correspond à vos critères de recherche. Essayez de modifier vos filtres ou ajoutez de nouveaux sorts à votre grimoire !</p>
            <button class="btn btn-success" onclick="openSpellModal()">
                ✨ Ajouter un nouveau sort
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($spells as $spell): ?>
        <div class="spell-card" style="border-left: 6px solid <?php echo $spell['color']; ?>">
            <?php if ($spell['image_url']): ?>
                <div class="spell-image-container">
                    <img src="../<?php echo htmlspecialchars($spell['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($spell['name']); ?>"
                         class="spell-image"
                         loading="lazy">
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
                echo nl2br(htmlspecialchars(strlen($description) > 150 ? 
                    substr($description, 0, 150) . '...' : $description)); 
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
            
            <div class="spell-meta">
                <small class="text-muted">
                    Créé le <?php echo date('d/m/Y', strtotime($spell['created_at'])); ?>
                    <?php if ($spell['updated_at'] !== $spell['created_at']): ?>
                        • Modifié le <?php echo date('d/m/Y', strtotime($spell['updated_at'])); ?>
                    <?php endif; ?>
                </small>
            </div>
            
            <div class="spell-actions">
                <button class="btn btn-sm" onclick="viewSpellDetails(<?php echo $spell['id']; ?>)">
                    👁️ Voir
                </button>
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

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="#" class="btn btn-pagination" onclick="changePage(<?php echo $page - 1; ?>)">
            « Précédent
        </a>
    <?php endif; ?>
    
    <?php
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    
    if ($start > 1): ?>
        <a href="#" class="btn btn-pagination" onclick="changePage(1)">1</a>
        <?php if ($start > 2): ?>
            <span class="pagination-dots">...</span>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php for ($i = $start; $i <= $end; $i++): ?>
        <a href="#" class="btn btn-pagination <?php echo $i == $page ? 'active' : ''; ?>" 
           onclick="changePage(<?php echo $i; ?>)">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
    
    <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?>
            <span class="pagination-dots">...</span>
        <?php endif; ?>
        <a href="#" class="btn btn-pagination" onclick="changePage(<?php echo $totalPages; ?>)">
            <?php echo $totalPages; ?>
        </a>
    <?php endif; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="#" class="btn btn-pagination" onclick="changePage(<?php echo $page + 1; ?>)">
            Suivant »
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal pour les détails du sort -->
<div id="spellDetailsModal" class="modal">
    <div class="modal-content modal-large">
        <span class="close" onclick="closeSpellDetailsModal()">&times;</span>
        <div id="spellDetailsContent">
            <!-- Le contenu sera chargé dynamiquement -->
        </div>
    </div>
</div>

<!-- Modal pour ajouter/modifier un sort (sera chargé par JavaScript) -->
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

<script>
// Variables globales
let currentPage = <?php echo $page; ?>;
let currentTag = <?php echo $tagFilter ?: 'null'; ?>;
let currentSearch = '<?php echo htmlspecialchars($search, ENT_QUOTES); ?>';

// Fonction pour appliquer les filtres
function applyFilters(tagId = null) {
    const urlParams = new URLSearchParams();
    
    const search = document.getElementById('searchInput').value;
    const sort = document.getElementById('sortSelect').value.split(',');
    const limit = document.getElementById('limitSelect').value;
    
    if (search) urlParams.set('search', search);
    if (tagId) urlParams.set('tag', tagId);
    if (sort[0] !== 'created_at' || sort[1] !== 'DESC') {
        urlParams.set('sort', sort[0]);
        urlParams.set('order', sort[1]);
    }
    if (limit !== '12') urlParams.set('limit', limit);
    
    urlParams.set('page', 1); // Reset to page 1
    
    window.location.href = 'spells.php?' + urlParams.toString();
}

// Fonction pour changer de page
function changePage(page) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    window.location.href = 'spells.php?' + urlParams.toString();
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Recherche en temps réel (avec délai)
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters(currentTag);
        }, 500);
    });
    
    // Changement de tri
    document.getElementById('sortSelect').addEventListener('change', function() {
        applyFilters(currentTag);
    });
    
    // Changement de limite
    document.getElementById('limitSelect').addEventListener('change', function() {
        applyFilters(currentTag);
    });
});

// Fonction pour voir les détails d'un sort
async function viewSpellDetails(spellId) {
    try {
        const response = await fetch(`../api/spells.php?id=${spellId}`);
        const spell = await response.json();
        
        if (spell.error) {
            alert('Erreur : ' + spell.error);
            return;
        }
        
        let html = `
            <div class="spell-details">
                <div class="spell-header">
                    <h2 style="color: ${spell.color}">${spell.name}</h2>
                    ${spell.image_url ? `<img src="../${spell.image_url}" alt="${spell.name}" class="spell-detail-image">` : ''}
                </div>
                
                <div class="spell-content">
                    <h3>📜 Description</h3>
                    <p class="spell-full-description">${spell.description.replace(/\n/g, '<br>')}</p>
                    
                    <h3>🏫 Écoles de Magie</h3>
                    <div class="spell-tags">
        `;
        
        if (spell.tag_ids) {
            const tagNames = spell.tag_names ? spell.tag_names.split(',') : [];
            const tagColors = spell.tag_colors ? spell.tag_colors.split(',') : [];
            for (let i = 0; i < tagNames.length; i++) {
                html += `<span class="tag-badge" style="background-color: ${tagColors[i]}">${tagNames[i]}</span>`;
            }
        } else {
            html += '<p class="text-muted">Aucune école assignée</p>';
        }
        
        html += `
                    </div>
                    
                    <div class="spell-metadata">
                        <h3>ℹ️ Informations</h3>
                        <p><strong>Créé le :</strong> ${new Date(spell.created_at).toLocaleDateString('fr-FR')}</p>
                        <p><strong>Modifié le :</strong> ${new Date(spell.updated_at).toLocaleDateString('fr-FR')}</p>
                        <p><strong>Couleur :</strong> <span class="color-swatch" style="background: ${spell.color}; display: inline-block; width: 20px; height: 20px; border-radius: 3px; vertical-align: middle;"></span> ${spell.color}</p>
                    </div>
                </div>
                
                <div class="spell-actions">
                    <button class="btn" onclick="editSpell(${spell.id}); closeSpellDetailsModal();">✏️ Modifier</button>
                    <button class="btn btn-danger" onclick="deleteSpell(${spell.id}); closeSpellDetailsModal();">🗑️ Supprimer</button>
                </div>
            </div>
        `;
        
        document.getElementById('spellDetailsContent').innerHTML = html;
        document.getElementById('spellDetailsModal').style.display = 'block';
        
    } catch (error) {
        alert('Erreur lors du chargement des détails du sort');
        console.error(error);
    }
}

function closeSpellDetailsModal() {
    document.getElementById('spellDetailsModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>