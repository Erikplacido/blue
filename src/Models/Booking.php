<?php
namespace Src\Models;

use PDO;
use PDOException;
use Src\Database\Connection;

class Booking {
    protected PDO $db;

    public function __construct() {
        $this->db = Connection::getInstance()->getPDO();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        return $booking ?: null;
    }

    /**
     * Cria uma nova booking e salva também as preferências
     *
     * @param array $data  Todos os campos de booking + 'preferences' => [field_id => '1', ...]
     * @return array       Mesmo $data, agora com ['id'] adicionado
     * @throws PDOException
     */
    public function create(array $data): array {
        // valores padrão
        $data['start_time']          = $data['start_time']          ?? '00:00';
        $data['end_time']            = $data['end_time']            ?? '23:59';
        $data['recurrence_interval'] = $data['recurrence_interval'] ?? null;
        // Inicializa remaining_executions igual ao contrato
        $data['remaining_executions'] = $data['contract_length'];

        try {
            $this->db->beginTransaction();

            // 1) Insere a booking
            $stmt = $this->db->prepare("
                INSERT INTO bookings
                    (customer_id, service_id, recurrence, execution_date,
                     start_time, end_time, recurrence_interval,
                     address, postcode, latitude, longitude, coupon_code, points_used,
                     total_price, status, contract_length, remaining_executions, num_days)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['customer_id'],
                $data['service_id'],
                $data['recurrence'],
                $data['execution_date'],
                $data['start_time'],
                $data['end_time'],
                $data['recurrence_interval'],
                $data['address'],
                $data['postcode'],
                $data['latitude'],
                $data['longitude'],
                $data['coupon_code'] ?? null,
                $data['points_used'] ?? 0,
                $data['total_price'],
                $data['status'],
                $data['contract_length'],
                $data['remaining_executions'],
                $data['num_days'],
            ]);

            // 2) Recupera o ID gerado
            $bookingId = (int) $this->db->lastInsertId();
            $data['id'] = $bookingId;

            // 3) Salva cada preferência
            if (!empty($data['preferences']) && is_array($data['preferences'])) {
                foreach ($data['preferences'] as $fieldId => $value) {
                    $this->addPreference($bookingId, (int)$fieldId, $value);
                }
            }

            $this->db->commit();
            return $data;

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Insere uma linha em booking_preferences
     *
     * @param int    $bookingId
     * @param int    $fieldId
     * @param mixed  $value      geralmente '1'
     */
    public function addPreference(int $bookingId, int $fieldId, $value): void {
        $stmt = $this->db->prepare("
            INSERT INTO booking_preferences
                (booking_id, preference_field_id, value)
            VALUES
                (?, ?, ?)
        ");
        $stmt->execute([$bookingId, $fieldId, $value]);
    }

    /**
     * Insere uma linha em booking_inclusions
     *
     * @param int $bookingId
     * @param int $inclusionId
     * @param int $quantity
     */
    public function addInclusion(int $bookingId, int $inclusionId, int $quantity): void {
        $stmt = $this->db->prepare("
            INSERT INTO booking_inclusions
                (booking_id, inclusion_id, quantity)
            VALUES
                (?, ?, ?)
        ");
        $stmt->execute([$bookingId, $inclusionId, $quantity]);
    }

    /**
     * Insere uma linha em booking_extras
     *
     * @param int $bookingId
     * @param int $extraId
     * @param int $quantity
     */
    public function addExtra(int $bookingId, int $extraId, int $quantity): void {
        $stmt = $this->db->prepare("
            INSERT INTO booking_extras
                (booking_id, extra_id, quantity)
            VALUES
                (?, ?, ?)
        ");
        $stmt->execute([$bookingId, $extraId, $quantity]);
    }

    public function markAsPaid(int $bookingId): void {
        $this->updateStatus($bookingId, 'paid');
    }

    public function scheduleNextRecurring(int $bookingId): void {
        $stmt = $this->db->prepare("
            UPDATE bookings 
               SET next_execution = DATE_ADD(execution_date, INTERVAL 30 DAY),
                   updated_at    = NOW()
             WHERE id = ?
        ");
        $stmt->execute([$bookingId]);
    }

    public function markAsFailed(int $bookingId): void {
        $this->updateStatus($bookingId, 'failed');
    }

    /**
     * Retorna a primeira reserva do cliente com base na data de execução
     *
     * @param int $customerId
     * @return array|null
     */
    public function getFirstByCustomer(int $customerId): ?array {
        $stmt = $this->db->prepare("
            SELECT *
              FROM bookings
             WHERE customer_id = :customer_id
             ORDER BY execution_date ASC
             LIMIT 1
        ");
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Calcula o total levando em conta:
     *  - service base_price
     *  - inclusões (included_qty)
     *  - extras (extra_qty)
     *  - preferências desmarcadas com extra_fee > 0
     *
     * @param array $formData  includes ['included_qty'], ['extra_qty'], ['preferences']
     * @param array $service   inclui ['id'], ['base_price']
     * @return float
     */
    public static function calculateTotal(array $formData, array $service): float {
        $pdo   = Connection::getInstance()->getPDO();
        $total = (float) ($service['base_price'] ?? 0);

        // Inclusões (service_inclusions)
        if (!empty($formData['included_qty']) && is_array($formData['included_qty'])) {
            $ids = array_map('intval', array_keys($formData['included_qty']));
            if (count($ids) > 0) {
                $ph     = implode(',', array_fill(0, count($ids), '?'));
                $params = array_merge([(int)$service['id']], $ids);
                $stmt   = $pdo->prepare("
                    SELECT id, price
                      FROM service_inclusions
                     WHERE service_id = ?
                       AND id IN ($ph)
                ");
                $stmt->execute($params);
                $prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                foreach ($formData['included_qty'] as $inclusionId => $qty) {
                    $qty   = max(0, (int)$qty);
                    $price = $prices[$inclusionId] ?? 0.0;
                    $total += $price * $qty;
                }
            }
        }

        // Extras (service_extras)
        if (!empty($formData['extra_qty']) && is_array($formData['extra_qty'])) {
            $ids = array_map('intval', array_keys($formData['extra_qty']));
            if (count($ids) > 0) {
                $ph     = implode(',', array_fill(0, count($ids), '?'));
                $params = array_merge([(int)$service['id']], $ids);
                $stmt   = $pdo->prepare("
                    SELECT id, price
                      FROM service_extras
                     WHERE service_id = ?
                       AND id IN ($ph)
                ");
                $stmt->execute($params);
                $prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                foreach ($formData['extra_qty'] as $extraId => $qty) {
                    $qty   = max(0, (int)$qty);
                    $price = $prices[$extraId] ?? 0.0;
                    $total += $price * $qty;
                }
            }
        }

        // Preferências (extra_fee)
        if (!empty($formData['preferences']) && is_array($formData['preferences'])) {
            $stmt = $pdo->query("SELECT id, extra_fee FROM preference_fields WHERE extra_fee > 0");
            $fees = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($fees as $fieldId => $fee) {
                if (empty($formData['preferences'][$fieldId])) {
                    $total += (float)$fee;
                }
            }
        }

        return round($total, 2);
    }

    /**
     * Retorna todas as reservas do cliente
     */
    public function getAllByCustomer(int $customerId): array {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza o stripe_subscription_id da reserva
     *
     * @param int    $bookingId
     * @param string $subscriptionId
     * @return bool
     */
    public function updateSubscriptionId(int $bookingId, string $subscriptionId): bool
    {
        $sql = "
            UPDATE bookings
               SET stripe_subscription_id = ?,
                   updated_at = NOW()
             WHERE id = ?
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $subscriptionId,
            $bookingId,
        ]);
    }

    /**
     * Atualiza o status da reserva (e updated_at)
     *
     * @param int    $bookingId
     * @param string $status       ex: 'pending', 'paid', 'scheduled', 'completed', 'failed'
     * @return bool
     */
    public function updateStatus(int $bookingId, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE bookings 
               SET status = ?, 
                   updated_at = NOW()
             WHERE id = ?
        ");
        return $stmt->execute([$status, $bookingId]);
    }

    /**
     * Retorna o número de execuções restantes
     *
     * @param int $bookingId
     * @return int
     */
    public function getRemainingExecutions(int $bookingId): int
    {
        $stmt = $this->db->prepare("
            SELECT remaining_executions 
              FROM bookings 
             WHERE id = ?
        ");
        $stmt->execute([$bookingId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int)$val : 0;
    }

    /**
     * Atualiza recurrence_status usando stripe_subscription_id
     *
     * @param string $subscriptionId
     * @param string $status       'active', 'paused' ou 'cancelled'
     * @return bool
     */
    public function updateRecurrenceStatusBySubscription(string $subscriptionId, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE bookings
               SET recurrence_status = ?, 
                   updated_at        = NOW()
             WHERE stripe_subscription_id = ?
        ");
        return $stmt->execute([$status, $subscriptionId]);
    }

    /**
     * Salva o valor da multa de quebra de contrato
     *
     * @param int   $bookingId
     * @param float $breakFee
     * @return bool
     */
    public function updateBreakFee(int $bookingId, float $breakFee): bool
    {
        $sql = "
            UPDATE bookings
               SET break_fee = ?, 
                   updated_at = NOW()
             WHERE id = ?
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$breakFee, $bookingId]);
    }

    /**
     * Atualiza o campo recurrence_status da booking
     *
     * @param int    $bookingId
     * @param string $status      'active', 'paused' ou 'cancelled'
     * @return bool               true se executou sem erros
     */
    public function updateRecurrenceStatus(int $bookingId, string $status): bool
    {
        if ($status === 'cancelled') {
            // Ao cancelar, zera também o intervalo de recorrência
            $stmt = $this->db->prepare("
                UPDATE bookings
                   SET recurrence_status   = ?,
                       recurrence_interval = NULL,
                       updated_at          = NOW()
                 WHERE id = ?
            ");
            return $stmt->execute([$status, $bookingId]);
        }

        // Para demais estados, só atualiza o status
        $stmt = $this->db->prepare("
            UPDATE bookings
               SET recurrence_status = ?, 
                   updated_at        = NOW()
             WHERE id = ?
        ");
        return $stmt->execute([$status, $bookingId]);
    }

    /**
     * Cancela a recorrência da reserva e marca recurrence_status como 'cancelled'
     *
     * @param int $bookingId
     */
    public function cancelRecurringBooking(int $bookingId): void {
        // limpa dados de recorrência
        $stmt = $this->db->prepare("
            UPDATE bookings
               SET recurrence = NULL,
                   recurrence_interval = NULL,
                   updated_at          = NOW()
             WHERE id = ?
        ");
        $stmt->execute([$bookingId]);

        // marca recurrence_status como cancelled
        $this->updateRecurrenceStatus($bookingId, 'cancelled');
    }

    /**
     * Decrementa o número de execuções restantes após cada pagamento
     *
     * @param int $bookingId
     * @return bool
     */
    public function decrementRemainingExecutions(int $bookingId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE bookings
               SET remaining_executions = GREATEST(remaining_executions - 1, 0),
                   updated_at            = NOW()
             WHERE id = ?
        ");
        return $stmt->execute([$bookingId]);
    }
}
