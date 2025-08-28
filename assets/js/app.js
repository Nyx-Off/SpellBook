// assets/js/app.js - Version corrigée avec suppression fonctionnelle

// Variables globales
let currentEditingSpell = null;

// Fonction pour déterminer le bon chemin d'API
function getApiPath(endpoint) {
    const currentPath = window.location.pathname;
    const isInPages = currentPath.includes('/pages/');
    const basePath = isInPages ? '../' : '';
    return basePath + 'api/' + endpoint;
}

// Fonction pour ouvrir le modal de sort
function openSpellModal() {
    const modal = document.getElementById('spellModal');
    if (modal) {
        modal.style.display = 'block';
        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) modalTitle.textContent = 'Nouveau Sort';
        
        const form = document.getElementById('spellForm');
        if (form) form.reset();
        
        currentEditingSpell = null;
        
        // Décocher tous les tags
        const checkboxes = document.querySelectorAll('#tagCheckboxes input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        // Réinitialiser la prévisualisation d'image
        const imagePreview = document.getElementById('imagePreview');
        if (imagePreview) {
            imagePreview.innerHTML = '';
        }
    }
}

// Fonction pour fermer le modal de sort
function closeSpellModal() {
    const modal = document.getElementById('spellModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Fonction pour modifier un sort
async function editSpell(spellId) {
    if (!spellId || isNaN(spellId)) {
        showNotification('ID de sort invalide', 'error');
        return;
    }

    try {
        const response = await fetch(getApiPath('spells.php') + `?id=${spellId}`);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const spell = await response.json();
        
        if (spell.error) {
            showNotification('Erreur : ' + spell.error, 'error');
            return;
        }
        
        // Remplir le formulaire
        const modalTitle = document.getElementById('modalTitle');
        const spellIdInput = document.getElementById('spellId');
        const spellName = document.getElementById('spellName');
        const spellDescription = document.getElementById('spellDescription');
        const spellColor = document.getElementById('spellColor');
        
        if (modalTitle) modalTitle.textContent = 'Modifier le Sort';
        if (spellIdInput) spellIdInput.value = spell.id;
        if (spellName) spellName.value = spell.name;
        if (spellDescription) spellDescription.value = spell.description;
        if (spellColor) spellColor.value = spell.color;
        
        // Cocher les tags appropriés
        const checkboxes = document.querySelectorAll('#tagCheckboxes input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        if (spell.tag_ids) {
            const tagIds = spell.tag_ids.split(',');
            tagIds.forEach(tagId => {
                const checkbox = document.querySelector(`#tagCheckboxes input[value="${tagId}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        currentEditingSpell = spellId;
        
        const modal = document.getElementById('spellModal');
        if (modal) modal.style.display = 'block';
        
    } catch (error) {
        console.error('Erreur lors du chargement du sort:', error);
        showNotification('Erreur lors du chargement du sort', 'error');
    }
}

// Fonction pour supprimer un sort - CORRIGÉE
async function deleteSpell(spellId) {
    if (!spellId || isNaN(spellId)) {
        showNotification('ID de sort invalide', 'error');
        return;
    }

    if (!confirm('Êtes-vous sûr de vouloir supprimer ce sort de votre grimoire ?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('spell_id', spellId);
        
        const response = await fetch(getApiPath('spells.php'), {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Sort supprimé avec succès !', 'success');
            
            // Recharger la page après 1 seconde
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Erreur : ' + (result.error || 'Erreur inconnue'), 'error');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        showNotification('Erreur lors de la suppression du sort', 'error');
    }
}

// Fonction pour supprimer un tag - AJOUTÉE
async function deleteTag(tagId) {
    if (!tagId || isNaN(tagId)) {
        showNotification('ID de tag invalide', 'error');
        return;
    }

    if (!confirm('Êtes-vous sûr de vouloir supprimer cette école de magie ?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('tag_id', tagId);
        
        const response = await fetch(getApiPath('tags.php'), {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('École de magie supprimée avec succès !', 'success');
            
            // Recharger la page après 1 seconde
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Erreur : ' + (result.error || 'Erreur inconnue'), 'error');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        showNotification('Erreur lors de la suppression du tag', 'error');
    }
}

// Fonction pour filtrer les sorts par tag
function filterSpells(tagId) {
    const currentPath = window.location.pathname;
    const isInPages = currentPath.includes('/pages/');
    const basePath = isInPages ? '../index.php' : 'index.php';
    
    if (tagId === null) {
        window.location.href = basePath;
    } else {
        window.location.href = `${basePath}?tag=${tagId}`;
    }
}

// Gestion du formulaire de sort
document.addEventListener('DOMContentLoaded', function() {
    const spellForm = document.getElementById('spellForm');
    
    if (spellForm) {
        spellForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            if (currentEditingSpell) {
                formData.append('action', 'update');
                formData.set('spell_id', currentEditingSpell);
            } else {
                formData.append('action', 'create');
            }
            
            try {
                const response = await fetch(getApiPath('spells.php'), {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const action = currentEditingSpell ? 'modifié' : 'créé';
                    showNotification(`Sort ${action} avec succès !`, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Erreur : ' + (result.error || 'Erreur inconnue'), 'error');
                }
            } catch (error) {
                console.error('Erreur lors de la sauvegarde:', error);
                showNotification('Erreur lors de la sauvegarde', 'error');
            }
        });
    }
    
    // Fermer les modals en cliquant en dehors
    window.onclick = function(event) {
        const spellModal = document.getElementById('spellModal');
        const statsModal = document.getElementById('statsModal');
        
        if (event.target === spellModal && spellModal) {
            closeSpellModal();
        }
        if (event.target === statsModal && statsModal) {
            closeStatsModal();
        }
    }
    
    // Prévisualisation de l'image
    const imageInput = document.getElementById('spellImage');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Vérifier la taille du fichier (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    showNotification('Le fichier est trop volumineux. Taille maximum : 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('imagePreview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.id = 'imagePreview';
                        imageInput.parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `
                        <img src="${e.target.result}" 
                             alt="Aperçu" 
                             style="max-width: 200px; max-height: 200px; border-radius: 5px; margin-top: 10px; display: block; box-shadow: 0 4px 8px rgba(0,0,0,0.3);">
                        <p style="margin-top: 5px; font-style: italic; color: var(--dark-wood);">Nouvelle image sélectionnée</p>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                const preview = document.getElementById('imagePreview');
                if (preview) {
                    preview.innerHTML = '';
                }
            }
        });
    }
    
    // Animation d'apparition des cartes
    const cards = document.querySelectorAll('.spell-card, .tag-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.6s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Effet de particules magiques sur le header
    createMagicalParticles();
    
    // Enregistrer la dernière visite
    try {
        localStorage.setItem('lastVisit', new Date().toLocaleString('fr-FR'));
        
        // Afficher la dernière visite dans le footer si l'élément existe
        const lastVisitElement = document.getElementById('lastVisit');
        if (lastVisitElement) {
            const lastVisit = localStorage.getItem('lastVisit');
            if (lastVisit) {
                lastVisitElement.textContent = lastVisit;
            }
        }
    } catch (e) {
        // localStorage non disponible
        console.log('LocalStorage non disponible');
    }
});

// Fonction pour créer des particules magiques
function createMagicalParticles() {
    const header = document.querySelector('.header');
    if (!header) return;
    
    // Créer des particules initiales
    for (let i = 0; i < 10; i++) {
        setTimeout(() => {
            createParticle(header);
        }, i * 200);
    }
    
    // Recréer des particules périodiquement
    setInterval(() => {
        if (Math.random() < 0.3) { // 30% de chance de créer une particule
            createParticle(header);
        }
    }, 1000);
}

function createParticle(container) {
    const particle = document.createElement('div');
    particle.style.cssText = `
        position: absolute;
        width: 4px;
        height: 4px;
        background: radial-gradient(circle, #FFD700, #FFA500);
        border-radius: 50%;
        pointer-events: none;
        z-index: 10;
        box-shadow: 0 0 10px #FFD700;
    `;
    
    // Position aléatoire
    particle.style.left = Math.random() * 100 + '%';
    particle.style.top = Math.random() * 100 + '%';
    
    container.appendChild(particle);
    
    // Animation
    const animation = particle.animate([
        {
            transform: 'translateY(0px) scale(0)',
            opacity: 0
        },
        {
            transform: 'translateY(-20px) scale(1)',
            opacity: 1
        },
        {
            transform: 'translateY(-40px) scale(0)',
            opacity: 0
        }
    ], {
        duration: 2000,
        easing: 'ease-out'
    });
    
    animation.addEventListener('finish', () => {
        particle.remove();
    });
}

// Fonction pour afficher les statistiques
function showStats() {
    const statsModal = document.getElementById('statsModal');
    if (statsModal) {
        statsModal.style.display = 'block';
        loadStats();
    }
}

function closeStatsModal() {
    const statsModal = document.getElementById('statsModal');
    if (statsModal) {
        statsModal.style.display = 'none';
    }
}

// Charger les statistiques via AJAX
async function loadStats() {
    try {
        const response = await fetch(getApiPath('stats.php'));
        const stats = await response.json();
        
        if (!stats.error) {
            const totalSpellsEl = document.getElementById('totalSpells');
            const totalTagsEl = document.getElementById('totalTags');
            const favoriteSchoolEl = document.getElementById('favoriteSchool');
            const lastSpellEl = document.getElementById('lastSpell');
            
            if (totalSpellsEl) totalSpellsEl.textContent = stats.totalSpells || 0;
            if (totalTagsEl) totalTagsEl.textContent = stats.totalTags || 0;
            if (favoriteSchoolEl) favoriteSchoolEl.textContent = stats.favoriteSchool || 'Aucune';
            if (lastSpellEl) lastSpellEl.textContent = stats.lastSpell || 'Aucun';
            
            // Affichage de la répartition par école
            if (stats.schoolDistribution) {
                displaySchoolDistribution(stats.schoolDistribution);
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
        showNotification('Erreur lors du chargement des statistiques', 'error');
    }
}

function displaySchoolDistribution(distribution) {
    const container = document.getElementById('schoolDistribution');
    if (!container) return;
    
    let html = '<h3>📈 Répartition par École</h3><div class="distribution-bars">';
    
    const total = distribution.reduce((sum, item) => sum + item.count, 0);
    
    distribution.forEach(item => {
        const percentage = total > 0 ? Math.round((item.count / total) * 100) : 0;
        html += `
            <div class="distribution-bar">
                <span class="school-name">${item.name}</span>
                <div class="bar-container">
                    <div class="bar-fill" style="width: ${percentage}%; background-color: ${item.color}"></div>
                    <span class="bar-text">${item.count} (${percentage}%)</span>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Gestion des notifications - AMÉLIORÉE
function showNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
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
        z-index: 1001;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        max-width: 400px;
        word-wrap: break-word;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        cursor: pointer;
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
    const timeout = setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, type === 'error' ? 7000 : 4000); // Plus long pour les erreurs
    
    // Permettre la fermeture au clic
    notification.addEventListener('click', () => {
        clearTimeout(timeout);
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    });
}

// Fonctions pour les tags (pour les pages de gestion des tags)
function openTagModal() {
    const modal = document.getElementById('tagModal');
    if (modal) {
        modal.style.display = 'block';
        const modalTitle = document.getElementById('tagModalTitle');
        if (modalTitle) modalTitle.textContent = 'Nouvelle École de Magie';
        
        const actionInput = document.getElementById('tagAction');
        if (actionInput) actionInput.value = 'create';
        
        const form = document.getElementById('tagForm');
        if (form) form.reset();
    }
}

function closeTagModal() {
    const modal = document.getElementById('tagModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function editTag(id, name, color) {
    const modal = document.getElementById('tagModal');
    const modalTitle = document.getElementById('tagModalTitle');
    const actionInput = document.getElementById('tagAction');
    const idInput = document.getElementById('tagId');
    const nameInput = document.getElementById('tagName');
    const colorInput = document.getElementById('tagColor');
    
    if (modal) modal.style.display = 'block';
    if (modalTitle) modalTitle.textContent = "Modifier l'École de Magie";
    if (actionInput) actionInput.value = 'update';
    if (idInput) idInput.value = id;
    if (nameInput) nameInput.value = name;
    if (colorInput) colorInput.value = color;
}

// Export des fonctions globales pour compatibilité
window.openSpellModal = openSpellModal;
window.closeSpellModal = closeSpellModal;
window.editSpell = editSpell;
window.deleteSpell = deleteSpell;
window.deleteTag = deleteTag;
window.filterSpells = filterSpells;
window.showStats = showStats;
window.closeStatsModal = closeStatsModal;
window.showNotification = showNotification;
window.openTagModal = openTagModal;
window.closeTagModal = closeTagModal;
window.editTag = editTag;