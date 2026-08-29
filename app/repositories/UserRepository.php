<?php
declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByUsername(string $username): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, username, password_hash, role, hourly_rate, active FROM nh_users WHERE username = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    public function findActiveById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, username, role, hourly_rate FROM nh_users WHERE id = :id AND active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, username, role, hourly_rate, active, created_at FROM nh_users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    public function all(): array
    {
        return $this->pdo->query(
            "SELECT id, name, username, role, hourly_rate, active, created_at
             FROM nh_users ORDER BY active DESC, name ASC"
        )->fetchAll();
    }

    public function create(string $name, string $username, string $password, string $role, float $hourlyRate): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO nh_users (name, username, password_hash, role, hourly_rate, active)
             VALUES (:name, :username, :password_hash, :role, :hourly_rate, 1)'
        );
        $statement->execute([
            'name' => $name,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'hourly_rate' => $hourlyRate,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $username, string $role, float $hourlyRate, ?string $password): void
    {
        $sql = 'UPDATE nh_users SET name = :name, username = :username, role = :role, hourly_rate = :hourly_rate';
        $parameters = ['id' => $id, 'name' => $name, 'username' => $username, 'role' => $role, 'hourly_rate' => $hourlyRate];
        if ($password !== null) {
            $sql .= ', password_hash = :password_hash';
            $parameters['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        if ($hourlyRate > 0) {
            $backfill = $this->pdo->prepare(
                'UPDATE nh_time_entries SET user_hourly_rate = :hourly_rate
                 WHERE user_id = :id AND user_hourly_rate = 0'
            );
            $backfill->execute(['id'=>$id,'hourly_rate'=>$hourlyRate]);
        }
    }

    public function setActive(int $id, bool $active): void
    {
        $statement = $this->pdo->prepare('UPDATE nh_users SET active = :active WHERE id = :id');
        $statement->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM nh_users WHERE role = 'admin' AND active = 1")->fetchColumn();
    }

    public function countActiveOwners(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM nh_users WHERE role = 'owner' AND active = 1")->fetchColumn();
    }

    public function changeOwnPassword(int $id, string $currentPassword, string $newPassword): bool
    {
        $statement = $this->pdo->prepare('SELECT password_hash FROM nh_users WHERE id = :id AND active = 1');
        $statement->execute(['id'=>$id]);
        $hash = $statement->fetchColumn();
        if (!is_string($hash) || !password_verify($currentPassword, $hash)) return false;
        $update = $this->pdo->prepare('UPDATE nh_users SET password_hash = :hash WHERE id = :id');
        $update->execute(['hash'=>password_hash($newPassword, PASSWORD_DEFAULT),'id'=>$id]);
        return true;
    }

    public function transferOwnership(int $currentOwnerId, int $newOwnerId): void
    {
        if ($currentOwnerId === $newOwnerId) throw new DomainException('La cuenta seleccionada ya es OWNER.');
        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare('SELECT id, role, active FROM nh_users WHERE id IN (:current_owner, :new_owner) FOR UPDATE');
            $lock->execute(['current_owner'=>$currentOwnerId,'new_owner'=>$newOwnerId]);
            $users = [];
            foreach ($lock->fetchAll() as $user) $users[(int)$user['id']] = $user;
            if (($users[$currentOwnerId]['role'] ?? null) !== 'owner') throw new DomainException('La cuenta actual ya no es OWNER.');
            if (!isset($users[$newOwnerId]) || !(bool)$users[$newOwnerId]['active']) throw new DomainException('El nuevo OWNER debe ser un usuario activo.');
            $demote = $this->pdo->prepare("UPDATE nh_users SET role='admin', hourly_rate=0 WHERE id=:id AND role='owner'");
            $demote->execute(['id'=>$currentOwnerId]);
            $promote = $this->pdo->prepare("UPDATE nh_users SET role='owner', hourly_rate=0 WHERE id=:id");
            $promote->execute(['id'=>$newOwnerId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
