<?php
namespace Src\Models;

use PDO;
use Src\Database\Connection;

class Address {
    protected PDO $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getPDO();
    }

    // Lista todos os endereços do cliente
    public function getAllByCustomer(int $customerId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM customer_addresses
            WHERE customer_id = ?
            ORDER BY label
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Busca um único endereço
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM customer_addresses WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Cria um novo
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO customer_addresses
              (customer_id,label,address_line1,address_line2,city,state,postcode,latitude,longitude)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['customer_id'],
            $data['label'],
            $data['address_line1'],
            $data['address_line2'] ?? null,
            $data['city'],
            $data['state'] ?? null,
            $data['postcode'],
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    // Atualiza existente
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE customer_addresses
            SET label=?,address_line1=?,address_line2=?,city=?,state=?,postcode=?,latitude=?,longitude=?
            WHERE id=?
        ");
        return $stmt->execute([
            $data['label'],
            $data['address_line1'],
            $data['address_line2'] ?? null,
            $data['city'],
            $data['state'] ?? null,
            $data['postcode'],
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $id
        ]);
    }

    // Excluir
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM customer_addresses WHERE id = ?");
        return $stmt->execute([$id]);
    }
}