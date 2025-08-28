<?php
// api/tags.php
header('Content-Type: application/json');
session_start();
require_once '../includes/functions.php';

$tagManager = new TagManager();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Récupérer un tag spécifique
                $tag = $tagManager->getTagById($_GET['id']);
                if ($tag) {
                    echo json_encode($tag);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'École de magie non trouvée']);
                }
            } else {
                // Récupérer tous les tags
                $tags = $tagManager->getAllTags();
                echo json_encode($tags);
            }
            break;
            
        case 'POST':
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'create':
                    $data = [
                        'name' => sanitize($_POST['name']),
                        'color' => sanitize($_POST['color'])
                    ];
                    
                    // Validation
                    if (empty($data['name'])) {
                        echo json_encode(['error' => 'Le nom de l\'école de magie est requis']);
                        exit;
                    }
                    
                    if (strlen($data['name']) < 2 || strlen($data['name']) > 50) {
                        echo json_encode(['error' => 'Le nom doit contenir entre 2 et 50 caractères']);
                        exit;
                    }
                    
                    if (!isValidColor($data['color'])) {
                        echo json_encode(['error' => 'Couleur invalide']);
                        exit;
                    }
                    
                    // Vérifier si le nom existe déjà
                    $existingTags = $tagManager->getAllTags();
                    foreach ($existingTags as $existing) {
                        if (strtolower($existing['name']) === strtolower($data['name'])) {
                            echo json_encode(['error' => 'Une école avec ce nom existe déjà']);
                            exit;
                        }
                    }
                    
                    $result = $tagManager->createTag($data);
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'École de magie créée avec succès']);
                    } else {
                        echo json_encode(['error' => 'Erreur lors de la création']);
                    }
                    break;
                    
                case 'update':
                    $tagId = (int)$_POST['tag_id'];
                    
                    if (!$tagManager->getTagById($tagId)) {
                        echo json_encode(['error' => 'École de magie non trouvée']);
                        exit;
                    }
                    
                    $data = [
                        'name' => sanitize($_POST['name']),
                        'color' => sanitize($_POST['color'])
                    ];
                    
                    // Validation
                    if (empty($data['name'])) {
                        echo json_encode(['error' => 'Le nom de l\'école de magie est requis']);
                        exit;
                    }
                    
                    if (strlen($data['name']) < 2 || strlen($data['name']) > 50) {
                        echo json_encode(['error' => 'Le nom doit contenir entre 2 et 50 caractères']);
                        exit;
                    }
                    
                    if (!isValidColor($data['color'])) {
                        echo json_encode(['error' => 'Couleur invalide']);
                        exit;
                    }
                    
                    // Vérifier si le nom existe déjà (sauf pour le tag courant)
                    $existingTags = $tagManager->getAllTags();
                    foreach ($existingTags as $existing) {
                        if ($existing['id'] != $tagId && 
                            strtolower($existing['name']) === strtolower($data['name'])) {
                            echo json_encode(['error' => 'Une école avec ce nom existe déjà']);
                            exit;
                        }
                    }
                    
                    $result = $tagManager->updateTag($tagId, $data);
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'École de magie modifiée avec succès']);
                    } else {
                        echo json_encode(['error' => 'Erreur lors de la modification']);
                    }
                    break;
                    
                case 'delete':
                    $tagId = (int)$_POST['tag_id'];
                    
                    if (!$tagManager->getTagById($tagId)) {
                        echo json_encode(['error' => 'École de magie non trouvée']);
                        exit;
                    }
                    
                    // Vérifier s'il y a des sorts associés
                    $spellManager = new SpellManager();
                    $associatedSpells = $spellManager->getAllSpells($tagId);
                    
                    if (!empty($associatedSpells)) {
                        $count = count($associatedSpells);
                        echo json_encode([
                            'error' => "Impossible de supprimer cette école car elle est associée à {$count} sort(s). Retirez d'abord cette école des sorts concernés ou supprimez les sorts.",
                            'associated_spells_count' => $count
                        ]);
                        exit;
                    }
                    
                    $result = $tagManager->deleteTag($tagId);
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'École de magie supprimée avec succès']);
                    } else {
                        echo json_encode(['error' => 'Erreur lors de la suppression']);
                    }
                    break;
                    
                case 'force_delete':
                    // Suppression forcée (retire le tag de tous les sorts puis le supprime)
                    $tagId = (int)$_POST['tag_id'];
                    
                    if (!$tagManager->getTagById($tagId)) {
                        echo json_encode(['error' => 'École de magie non trouvée']);
                        exit;
                    }
                    
                    // Commencer une transaction
                    $db = getDB();
                    $db->beginTransaction();
                    
                    try {
                        // Supprimer toutes les associations spell_tags
                        $stmt = $db->prepare("DELETE FROM spell_tags WHERE tag_id = :tag_id");
                        $stmt->execute(['tag_id' => $tagId]);
                        
                        // Supprimer le tag
                        $result = $tagManager->deleteTag($tagId);
                        
                        if ($result) {
                            $db->commit();
                            echo json_encode(['success' => true, 'message' => 'École de magie et toutes ses associations supprimées avec succès']);
                        } else {
                            $db->rollBack();
                            echo json_encode(['error' => 'Erreur lors de la suppression forcée']);
                        }
                        
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
                    break;
                    
                case 'get_stats':
                    $tagId = (int)$_POST['tag_id'];
                    
                    if (!$tagManager->getTagById($tagId)) {
                        echo json_encode(['error' => 'École de magie non trouvée']);
                        exit;
                    }
                    
                    $spellManager = new SpellManager();
                    $associatedSpells = $spellManager->getAllSpells($tagId);
                    
                    $stats = [
                        'associated_spells_count' => count($associatedSpells),
                        'spells' => array_map(function($spell) {
                            return [
                                'id' => $spell['id'],
                                'name' => $spell['name'],
                                'created_at' => $spell['created_at']
                            ];
                        }, array_slice($associatedSpells, 0, 10)) // Limiter à 10 sorts pour l'API
                    ];
                    
                    echo json_encode(['success' => true, 'stats' => $stats]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action non reconnue']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>