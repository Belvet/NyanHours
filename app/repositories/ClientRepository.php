<?php
declare(strict_types=1);

final class ClientRepository
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query(
            'SELECT id, name, color, active, created_at, updated_at FROM clients ORDER BY active DESC, name ASC'
        )->fetchAll();
    }

    public function allActive(): array
    {
        return $this->pdo->query(
            'SELECT id, name, color FROM clients WHERE active = 1 ORDER BY name ASC'
        )->fetchAll();
    }

    public function forTimesheet(int $userId, string $weekStart, string $weekEnd): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT c.id, c.name, c.color, c.active
             FROM clients c
             LEFT JOIN time_entries te ON te.client_id = c.id AND te.user_id = :user_id
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
            'SELECT id, name, color, active, created_at, updated_at FROM clients WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $client = $statement->fetch();
        return $client === false ? null : $client;
    }

    public function findByName(string $name): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, color, active FROM clients WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        $client = $statement->fetch();
        return $client === false ? null : $client;
    }

    public function create(string $name, string $color): int
    {
        $statement = $this->pdo->prepare('INSERT INTO clients (name, color, active) VALUES (:name, :color, 1)');
        $statement->execute(['name' => $name, 'color' => $color]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $color): void
    {
        $statement = $this->pdo->prepare('UPDATE clients SET name = :name, color = :color WHERE id = :id');
        $statement->execute(['id' => $id, 'name' => $name, 'color' => $color]);
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->pdo->prepare('UPDATE clients SET active = :active WHERE id = :id');
        $statement->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }
}
