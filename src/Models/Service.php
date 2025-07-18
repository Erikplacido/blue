<?php
namespace Src\Models;

use PDO;
use Src\Database\Connection;

class Service {
    protected PDO $db;

    public function __construct() {
        // Corrigido: obtém o PDO corretamente a partir da instância da conexão
        $this->db = Connection::getInstance()->getPDO();
    }

    public function getBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM services WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getInclusions(int $serviceId): array {
        $stmt = $this->db->prepare("SELECT * FROM service_inclusions WHERE service_id = ?");
        $stmt->execute([$serviceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExtras(int $serviceId): array {
        $stmt = $this->db->prepare("SELECT * FROM service_extras WHERE service_id = ?");
        $stmt->execute([$serviceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
