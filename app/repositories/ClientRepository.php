<?php
declare(strict_types=1);

final class ClientRepository
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query(
            'SELECT id, name, color, hourly_rate, active, created_at, updated_at FROM nh_clients ORDER BY active DESC, name ASC'
        )->fetchAll();
    }

    public function allActive(): array
    {
        return $this->pdo->query(
            'SELECT id, name, color, hourly_rate FROM nh_clients WHERE active = 1 ORDER BY name ASC'
        )->fetchAll();
    }

    public function forTimesheet(int $userId, string $weekStart, string $weekEnd): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT c.id, c.name, c.color, c.active
             FROM nh_clients c
             LEFT JOIN nh_time_entries te ON te.client_id = c.id AND te.user_id = :user_id
                AND te.work_date BETWEEN :week_start AND :week_end
             WHERE c.active = 1 OR te.id IS NOT NULL
             ORDER BY c.active DESC, c.name ASC'
        );
        $statement->execute(['user_id' => $userId, 'week_start' => $weekStart, 'week_end' => $weekEnd]);
        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, color, hourly_rate, active, created_at, updated_at FROM nh_clients WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $client = $statement->fetch();
        return $client === false ? null : $client;
    }

    public function findByName(string $name): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, color, hourly_rate, active FROM nh_clients WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $client = $statement->fetch();
        return $client === false ? null : $client;
    }

    public function create(string $name, string $color, float $hourlyRate): int
    {
        $statement = $this->pdo->prepare('INSERT INTO nh_clients (name, color, hourly_rate, active) VALUES (:name, :color, :hourly_rate, 1)');
        $statement->execute(['name' => $name, 'color' => $color, 'hourly_rate' => $hourlyRate]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $color, float $hourlyRate): void
    {
        $statement = $this->pdo->prepare('UPDATE nh_clients SET name = :name, color = :color, hourly_rate = :hourly_rate WHERE id = :id');
        $statement->execute(['id' => $id, 'name' => $name, 'color' => $color, 'hourly_rate' => $hourlyRate]);
        if ($hourlyRate > 0) {
            $backfill = $this->pdo->prepare(
                'UPDATE nh_time_entries SET client_hourly_rate = :hourly_rate
                 WHERE client_id = :id AND client_hourly_rate = 0'
            );
            $backfill->execute(['id'=>$id,'hourly_rate'=>$hourlyRate]);
        }
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->pdo->prepare('UPDATE nh_clients SET active = :active WHERE id = :id');
        $statement->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }

    public function deleteWithEntries(int $id): int
    {
        $this->pdo->beginTransaction();
        try {
            $entries = $this->pdo->prepare('DELETE FROM nh_time_entries WHERE client_id = :id');
            $entries->execute(['id'=>$id]);
            $deletedEntries = $entries->rowCount();
            $client = $this->pdo->prepare('DELETE FROM nh_clients WHERE id = :id');
            $client->execute(['id'=>$id]);
            if ($client->rowCount() !== 1) throw new DomainException('Cliente no encontrado.');
            $this->pdo->commit();
            return $deletedEntries;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
