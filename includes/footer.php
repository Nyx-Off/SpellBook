</main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>📜 À propos du Grimoire</h3>
                    <p>Ce grimoire magique vous permet de cataloguer et organiser tous vos sorts appris au cours de vos aventures. Que la magie soit avec vous !</p>
                </div>
                
                <div class="footer-section">
                    <h3>🎯 Fonctionnalités</h3>
                    <ul>
                        <li>✨ Gestion complète des sorts</li>
                        <li>🏷️ Classification par écoles de magie</li>
                        <li>🖼️ Upload d'images mystiques</li>
                        <li>🎨 Interface médiévale immersive</li>
                        <li>📱 Compatible mobile et desktop</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>🔮 Statistiques</h3>
                    <div id="footerStats">
                        <?php
                        if (class_exists('SpellManager') && class_exists('TagManager')) {
                            try {
                                $spellManager = new SpellManager();
                                $tagManager = new TagManager();
                                $totalSpells = count($spellManager->getAllSpells());
                                $totalTags = count($tagManager->getAllTags());
                                echo "<p>📖 {$totalSpells} sort(s) dans le grimoire</p>";
                                echo "<p>🏫 {$totalTags} école(s) de magie</p>";
                            } catch (Exception $e) {
                                echo "<p>📊 Statistiques indisponibles</p>";
                            }
                        }
                        ?>
                        <p>🕐 Dernière visite : <span id="lastVisit"></span></p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="magical-divider"></div>
                <p>&copy; <?php echo date('Y'); ?> Grimoire des Sorts. Créé avec ❤️ et un peu de magie ancienne.</p>
                <p class="version-info">Version 1.0 - "Codex Arcanum"</p>
            </div>
        </div>
    </footer>

    <!-- Modal pour les statistiques -->
    <div id="statsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeStatsModal()">&times;</span>
            <h2>📊 Statistiques du Grimoire</h2>
            <div id="statsContent">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>📚 Sorts Total</h3>
                        <span class="stat-number" id="totalSpells">-</span>
                    </div>
                    <div class="stat-card">
                        <h3>🏫 Écoles de Magie</h3>
                        <span class="stat-number" id="totalTags">-</span>
                    </div>
                    <div class="stat-card">
                        <h3>🔥 École Favorite</h3>
                        <span class="stat-text" id="favoriteSchool">-</span>
                    </div>
                    <div class="stat-card">
                        <h3>📅 Dernier Sort</h3>
                        <span class="stat-text" id="lastSpell">-</span>
                    </div>
                </div>
                <div id="schoolDistribution"></div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo isset($jsPath) ? $jsPath : '../assets/js/app.js'; ?>"></script>
    <script>
        // Enregistrer la dernière visite
        try {
            localStorage.setItem('lastVisit', new Date().toLocaleString('fr-FR'));
            
            // Afficher la dernière visite
            document.addEventListener('DOMContentLoaded', function() {
                const lastVisit = localStorage.getItem('lastVisit');
                if (lastVisit) {
                    const lastVisitElement = document.getElementById('lastVisit');
                    if (lastVisitElement) {
                        lastVisitElement.textContent = lastVisit;
                    }
                }
            });
        } catch (e) {
            // localStorage non disponible
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
                const apiPath = window.location.pathname.includes('/pages/') ? '../api/stats.php' : 'api/stats.php';
                const response = await fetch(apiPath);
                const stats = await response.json();
                
                if (!stats.error) {
                    document.getElementById('totalSpells').textContent = stats.totalSpells || 0;
                    document.getElementById('totalTags').textContent = stats.totalTags || 0;
                    document.getElementById('favoriteSchool').textContent = stats.favoriteSchool || 'Aucune';
                    document.getElementById('lastSpell').textContent = stats.lastSpell || 'Aucun';
                    
                    // Affichage de la répartition par école
                    if (stats.schoolDistribution) {
                        displaySchoolDistribution(stats.schoolDistribution);
                    }
                }
            } catch (error) {
                console.error('Erreur lors du chargement des statistiques:', error);
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
    </script>
</body>
</html>