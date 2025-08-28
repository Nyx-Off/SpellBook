<?php
// api/spells.php
header('Content-Type: application/json');
session_start();
require_once '../includes/functions.php';

$spellManager = new SpellManager();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Récupérer un sort spécifique
                $spell = $spellManager->getSpellById($_GET['id']);
                if ($spell) {
                    echo json_encode($spell);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Sort non trouvé']);
                }
            } else {
                // Récupérer tous les sorts
                $tagFilter = $_GET['tag'] ?? null;
                $spells = $spellManager->getAllSpells($tagFilter);
                echo json_encode($spells);
            }
            break;
            
        case 'POST':
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'create':
                    // Gestion de l'upload d'image
                    $imageUrl = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        try {
                            $imageUrl = uploadImage($_FILES['image']);
                        } catch (Exception $e) {
                            echo json_encode(['error' => $e->getMessage()]);
                            exit;
                        }
                    }
                    
                    $data = [
                        'name' => sanitize($_POST['name']),
                        'description' => sanitize($_POST['description']),
                        'image_url' => $imageUrl,
                        'color' => sanitize($_POST['color']),
                        'tags' => $_POST['tags'] ?? []
                    ];
                    
                    // Validation
                    if (empty($data['name'])) {
                        echo json_encode(['error' => 'Le nom du sort est requis']);
                        exit;
                    }
                    
                    if (empty($data['description'])) {
                        echo json_encode(['error' => 'La description est requise']);
                        exit;
                    }
                    
                    if (!isValidColor($data['color'])) {
                        echo json_encode(['error' => 'Couleur invalide']);
                        exit;
                    }
                    
                    $spellId = $spellManager->createSpell($data);
                    echo json_encode(['success' => true, 'id' => $spellId]);
                    break;
                    
                case 'update':
                    $spellId = (int)$_POST['spell_id'];
                    
                    // Récupérer le sort existant pour garder l'image actuelle si pas de nouvelle image
                    $existingSpell = $spellManager->getSpellById($spellId);
                    if (!$existingSpell) {
                        echo json_encode(['error' => 'Sort non trouvé']);
                        exit;
                    }
                    
                    // Gestion de l'upload d'image
                    $imageUrl = $existingSpell['image_url'];
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        try {
                            $newImageUrl = uploadImage($_FILES['image']);
                            if ($newImageUrl) {
                                // Supprimer l'ancienne image si elle existe
                                if ($imageUrl && file_exists($imageUrl)) {
                                    unlink($imageUrl);
                                }
                                $imageUrl = $newImageUrl;
                            }
                        } catch (Exception $e) {
                            echo json_encode(['error' => $e->getMessage()]);
                            exit;
                        }
                    }
                    
                    $data = [
                        'name' => sanitize($_POST['name']),
                        'description' => sanitize($_POST['description']),
                        'image_url' => $imageUrl,
                        'color' => sanitize($_POST['color']),
                        'tags' => $_POST['tags'] ?? []
                    ];
                    
                    // Validation
                    if (empty($data['name'])) {
                        echo json_encode(['error' => 'Le nom du sort est requis']);
                        exit;
                    }
                    
                    if (empty($data['description'])) {
                        echo json_encode(['error' => 'La description est requise']);
                        exit;
                    }
                    
                    if (!isValidColor($data['color'])) {
                        echo json_encode(['error' => 'Couleur invalide']);
                        exit;
                    }
                    
                    $spellManager->updateSpell($spellId, $data);
                    echo json_encode(['success' => true]);
                    break;
                    
                case 'delete':
                    $spellId = (int)$_POST['spell_id'];
                    
                    // Récupérer le sort pour supprimer l'image associée
                    $spell = $spellManager->getSpellById($spellId);
                    if ($spell && $spell['image_url'] && file_exists($spell['image_url'])) {
                        unlink($spell['image_url']);
                    }
                    
                    $result = $spellManager->deleteSpell($spellId);
                    if ($result) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['error' => 'Erreur lors de la suppression']);
                    }
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