<?php
// includes/functions.php
require_once __DIR__ . '/../config/database.php';

// Fonction pour sécuriser les données
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Fonction pour valider une couleur hexadécimale
function isValidColor($color) {
    return preg_match('/^#[a-f0-9]{6}$/i', $color);
}

// Fonction pour uploader une image
function uploadImage($file, $targetDir = 'assets/images/spells/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Type de fichier non autorisé");
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        throw new Exception("Fichier trop volumineux");
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('spell_') . '.' . $extension;
    $targetPath = $targetDir . $fileName;
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }
    
    throw new Exception("Erreur lors de l'upload");
}

// Classe pour gérer les sorts
class SpellManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllSpells($tagFilter = null, $search = null, $sortBy = 'created_at', $sortOrder = 'DESC', $limit = null, $offset = 0) {
        $sql = "SELECT s.*, GROUP_CONCAT(t.name) as tag_names, 
                       GROUP_CONCAT(t.color) as tag_colors, 
                       GROUP_CONCAT(t.id) as tag_ids
                FROM spells s
                LEFT JOIN spell_tags st ON s.id = st.spell_id
                LEFT JOIN tags t ON st.tag_id = t.id";
        
        $params = [];
        $conditions = [];
        
        if ($tagFilter) {
            $conditions[] = "s.id IN (
                SELECT DISTINCT spell_id FROM spell_tags 
                WHERE tag_id = :tag_filter
            )";
            $params['tag_filter'] = $tagFilter;
        }
        
        if ($search) {
            $conditions[] = "(s.name LIKE :search OR s.description LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " GROUP BY s.id";
        
        // Tri
        $allowedSorts = ['created_at', 'updated_at', 'name'];
        $allowedOrders = ['ASC', 'DESC'];
        
        if (in_array($sortBy, $allowedSorts) && in_array($sortOrder, $allowedOrders)) {
            $sql .= " ORDER BY s.{$sortBy} {$sortOrder}";
        } else {
            $sql .= " ORDER BY s.created_at DESC";
        }
        
        // Pagination
        if ($limit !== null && $limit !== 'all') {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params['limit'] = (int)$limit;
            $params['offset'] = (int)$offset;
        }
        
        $stmt = $this->db->prepare($sql);
        
        // Bind des paramètres avec les bons types
        foreach ($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function countSpells($tagFilter = null, $search = null) {
        $sql = "SELECT COUNT(DISTINCT s.id) as total
                FROM spells s
                LEFT JOIN spell_tags st ON s.id = st.spell_id
                LEFT JOIN tags t ON st.tag_id = t.id";
        
        $params = [];
        $conditions = [];
        
        if ($tagFilter) {
            $conditions[] = "s.id IN (
                SELECT DISTINCT spell_id FROM spell_tags 
                WHERE tag_id = :tag_filter
            )";
            $params['tag_filter'] = $tagFilter;
        }
        
        if ($search) {
            $conditions[] = "(s.name LIKE :search OR s.description LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return (int)$result['total'];
    }
    
    public function getSpellById($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, GROUP_CONCAT(t.id) as tag_ids,
                   GROUP_CONCAT(t.name) as tag_names,
                   GROUP_CONCAT(t.color) as tag_colors
            FROM spells s
            LEFT JOIN spell_tags st ON s.id = st.spell_id
            LEFT JOIN tags t ON st.tag_id = t.id
            WHERE s.id = :id
            GROUP BY s.id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    public function createSpell($data) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO spells (name, description, image_url, color) 
                VALUES (:name, :description, :image_url, :color)
            ");
            $stmt->execute([
                'name' => $data['name'],
                'description' => $data['description'],
                'image_url' => $data['image_url'],
                'color' => $data['color']
            ]);
            
            $spellId = $this->db->lastInsertId();
            
            if (!empty($data['tags'])) {
                $this->updateSpellTags($spellId, $data['tags']);
            }
            
            $this->db->commit();
            return $spellId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function updateSpell($id, $data) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE spells 
                SET name = :name, description = :description, 
                    image_url = :image_url, color = :color,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'],
                'image_url' => $data['image_url'],
                'color' => $data['color']
            ]);
            
            $this->updateSpellTags($id, $data['tags'] ?? []);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function deleteSpell($id) {
        $stmt = $this->db->prepare("DELETE FROM spells WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    private function updateSpellTags($spellId, $tagIds) {
        // Supprimer les anciens tags
        $stmt = $this->db->prepare("DELETE FROM spell_tags WHERE spell_id = :spell_id");
        $stmt->execute(['spell_id' => $spellId]);
        
        // Ajouter les nouveaux tags
        if (!empty($tagIds)) {
            $stmt = $this->db->prepare("INSERT INTO spell_tags (spell_id, tag_id) VALUES (:spell_id, :tag_id)");
            foreach ($tagIds as $tagId) {
                $stmt->execute(['spell_id' => $spellId, 'tag_id' => $tagId]);
            }
        }
    }
}

// Classe pour gérer les tags
class TagManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllTags() {
        $stmt = $this->db->prepare("SELECT * FROM tags ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getTagById($id) {
        $stmt = $this->db->prepare("SELECT * FROM tags WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    public function createTag($data) {
        $stmt = $this->db->prepare("INSERT INTO tags (name, color) VALUES (:name, :color)");
        return $stmt->execute([
            'name' => $data['name'],
            'color' => $data['color']
        ]);
    }
    
    public function updateTag($id, $data) {
        $stmt = $this->db->prepare("UPDATE tags SET name = :name, color = :color WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'color' => $data['color']
        ]);
    }
    
    public function deleteTag($id) {
        $stmt = $this->db->prepare("DELETE FROM tags WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

// Fonction pour afficher les notifications
function displayNotification() {
    if (isset($_SESSION['notification'])) {
        $type = $_SESSION['notification']['type'];
        $message = $_SESSION['notification']['message'];
        echo "<div class='notification notification-{$type} show' style='transform: translateX(0);'>{$message}</div>";
        echo "<script>
            setTimeout(function() {
                var notification = document.querySelector('.notification');
                if (notification) {
                    notification.style.transform = 'translateX(400px)';
                    setTimeout(function() { 
                        notification.remove(); 
                    }, 300);
                }
            }, 3000);
        </script>";
        unset($_SESSION['notification']);
    }
}

// Fonction pour définir une notification
function setNotification($message, $type = 'info') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}
?>