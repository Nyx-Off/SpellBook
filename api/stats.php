<?php
// api/stats.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

try {
    $spellManager = new SpellManager();
    $tagManager = new TagManager();
    
    // Statistiques de base
    $allSpells = $spellManager->getAllSpells();
    $allTags = $tagManager->getAllTags();
    
    $totalSpells = count($allSpells);
    $totalTags = count($allTags);
    
    // École favorite (celle avec le plus de sorts)
    $schoolCounts = [];
    foreach ($allSpells as $spell) {
        if ($spell['tag_names']) {
            $tags = explode(',', $spell['tag_names']);
            foreach ($tags as $tag) {
                $tag = trim($tag);
                $schoolCounts[$tag] = ($schoolCounts[$tag] ?? 0) + 1;
            }
        }
    }
    
    $favoriteSchool = 'Aucune';
    $maxCount = 0;
    foreach ($schoolCounts as $school => $count) {
        if ($count > $maxCount) {
            $maxCount = $count;
            $favoriteSchool = $school;
        }
    }
    
    // Dernier sort créé
    $lastSpell = 'Aucun';
    if (!empty($allSpells)) {
        $lastSpellData = $allSpells[0]; // Les sorts sont triés par date de création DESC
        $lastSpell = $lastSpellData['name'];
    }
    
    // Répartition par école
    $schoolDistribution = [];
    foreach ($allTags as $tag) {
        $count = 0;
        foreach ($allSpells as $spell) {
            if ($spell['tag_ids']) {
                $tagIds = explode(',', $spell['tag_ids']);
                if (in_array($tag['id'], $tagIds)) {
                    $count++;
                }
            }
        }
        
        $schoolDistribution[] = [
            'name' => $tag['name'],
            'color' => $tag['color'],
            'count' => $count
        ];
    }
    
    // Trier par nombre de sorts décroissant
    usort($schoolDistribution, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    // Statistiques temporelles
    $spellsByMonth = [];
    $spellsByWeekday = ['Lun' => 0, 'Mar' => 0, 'Mer' => 0, 'Jeu' => 0, 'Ven' => 0, 'Sam' => 0, 'Dim' => 0];
    
    foreach ($allSpells as $spell) {
        $date = new DateTime($spell['created_at']);
        $month = $date->format('Y-m');
        $weekday = $date->format('w'); // 0 = dimanche, 1 = lundi, etc.
        
        $spellsByMonth[$month] = ($spellsByMonth[$month] ?? 0) + 1;
        
        $weekdays = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        $spellsByWeekday[$weekdays[$weekday]]++;
    }
    
    // Garder seulement les 12 derniers mois
    ksort($spellsByMonth);
    $spellsByMonth = array_slice($spellsByMonth, -12, 12, true);
    
    // Statistiques sur les couleurs
    $colorStats = [];
    foreach ($allSpells as $spell) {
        $color = $spell['color'];
        $colorStats[$color] = ($colorStats[$color] ?? 0) + 1;
    }
    arsort($colorStats);
    
    // Top 5 des couleurs
    $topColors = array_slice($colorStats, 0, 5, true);
    
    // Sorts récents (5 derniers)
    $recentSpells = array_slice($allSpells, 0, 5);
    $recentSpellsFormatted = array_map(function($spell) {
        return [
            'id' => $spell['id'],
            'name' => $spell['name'],
            'color' => $spell['color'],
            'created_at' => date('d/m/Y', strtotime($spell['created_at'])),
            'tags' => $spell['tag_names'] ? explode(',', $spell['tag_names']) : []
        ];
    }, $recentSpells);
    
    // Calculs avancés
    $averageSpellsPerSchool = $totalTags > 0 ? round($totalSpells / $totalTags, 1) : 0;
    $schoolsWithoutSpells = 0;
    foreach ($schoolDistribution as $school) {
        if ($school['count'] == 0) {
            $schoolsWithoutSpells++;
        }
    }
    
    // Temps depuis le dernier sort
    $daysSinceLastSpell = 0;
    if (!empty($allSpells)) {
        $lastSpellDate = new DateTime($allSpells[0]['created_at']);
        $now = new DateTime();
        $daysSinceLastSpell = $now->diff($lastSpellDate)->days;
    }
    
    // Réponse JSON
    $stats = [
        'totalSpells' => $totalSpells,
        'totalTags' => $totalTags,
        'favoriteSchool' => $favoriteSchool,
        'lastSpell' => $lastSpell,
        'schoolDistribution' => $schoolDistribution,
        'spellsByMonth' => $spellsByMonth,
        'spellsByWeekday' => $spellsByWeekday,
        'topColors' => $topColors,
        'recentSpells' => $recentSpellsFormatted,
        'averageSpellsPerSchool' => $averageSpellsPerSchool,
        'schoolsWithoutSpells' => $schoolsWithoutSpells,
        'daysSinceLastSpell' => $daysSinceLastSpell,
        'generatedAt' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>