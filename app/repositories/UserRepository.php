<?php
declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, hourly_rate, active FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    public function findActiveById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, role, hourly_rate FROM users WHERE id = :id AND active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, role, hourly_rate, active, created_at FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    public function all(): array
    {
        return $this->pdo->query(
            "SELECT id, name, email, role, hourly_rate, active, created_at
             FROM users ORDER BY active DESC, name ASC"
        )->fetchAll();
    }

    public function create(string $name, string $email, string $password, string $role, float $hourlyRate): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, hourly_rate, active)
             VALUES (:name, :email, :password_hash, :role, :hourly_rate, 1)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'hourly_rate' => $hourlyRate,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $email, string $role, float $hourlyRate, ?string $password): void
    {
        $sql = 'UPDATE users SET name = :name, email = :email, role = :role, hourly_rate = :hourly_rate';
        $parameters = ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role, 'hourly_rate' => $hourlyRate];
        if ($password !== null) {
            $sql .= ', password_hash = :password_hash';
            $parameters['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET active = :active WHERE id = :id');
        $statement->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1")->fetchColumn();
    }
}
