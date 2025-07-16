<?php
namespace Src\Models;

use PDO;
use Src\Database\Connection;

class Customer {
    protected PDO $db;

    public function __construct() {
        // Corrigido: pega o objeto PDO real da sua classe de conexão
        $this->db = Connection::getInstance()->getPDO();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $customer ?: null;
    }

public function updatePassword(int $id, string $hash): void {
    $stmt = $this->db->prepare("UPDATE customers SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $id]);
}

    public function findOrCreate(array $data): array {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$data['email']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) return $customer;

        $stmt = $this->db->prepare("
            INSERT INTO customers (first_name, last_name, email, phone, abn_or_tfn)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['abn_or_tfn']
        ]);

        $data['id'] = (int) $this->db->lastInsertId();
        return $data;
    }

    /**
     * Busca um cliente pelo e-mail.
     *
     * @param string $email
     * @return array|null   Dados do cliente ou null se não encontrado.
     */
    public function getByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $customer ?: null;
    }
}