<?php
declare(strict_types=1);

final class TimeEntryRepository
{
    public function __construct(private PDO $pdo) {}

    public function forUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT te.id, te.work_date, te.description,
                    c.name AS client_name, c.color AS client_color,
                    te.duration_minutes AS total_minutes
             FROM nh_time_entries te
             INNER JOIN nh_clients c ON c.id = te.client_id
             WHERE te.user_id = :user_id
             ORDER BY te.work_date DESC, te.id DESC"
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function forUserBetween(int $userId, string $dateFrom, string $dateTo): array
    {
        $statement = $this->pdo->prepare(
            "SELECT te.id, te.work_date, te.description,
                    c.name AS client_name, c.color AS client_color,
                    te.duration_minutes AS total_minutes
             FROM nh_time_entries te
             INNER JOIN nh_clients c ON c.id = te.client_id
             WHERE te.user_id = :user_id AND te.work_date BETWEEN :date_from AND :date_to
             ORDER BY te.work_date DESC, te.id DESC"
        );
        $statement->execute(['user_id' => $userId, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
        return $statement->fetchAll();
    }

    public function totalMinutesForUser(int $userId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(duration_minutes), 0) FROM nh_time_entries WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn();
    }

    public function earningsForUserBetween(int $userId, string $dateFrom, string $dateTo): array
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(duration_minutes), 0) AS total_minutes,
                    COALESCE(SUM(duration_minutes * user_hourly_rate) / 60, 0) AS total_earnings
             FROM nh_time_entries
             WHERE user_id = :user_id AND work_date BETWEEN :date_from AND :date_to'
        );
        $statement->execute(['user_id' => $userId, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
        return $statement->fetch() ?: ['total_minutes' => 0, 'total_earnings' => 0];
    }

    public function summaryByUserAndClient(): array
    {
        return $this->pdo->query(
            "SELECT u.id AS user_id, u.name AS user_name, c.id AS client_id, c.name AS client_name, c.color AS client_color,
                    COUNT(te.id) AS entry_count,
                    SUM(te.duration_minutes) AS total_minutes
             FROM nh_time_entries te
             INNER JOIN nh_users u ON u.id = te.user_id
             INNER JOIN nh_clients c ON c.id = te.client_id
             GROUP BY u.id, u.name, c.id, c.name, c.color
             ORDER BY u.name ASC, c.name ASC"
        )->fetchAll();
    }

    public function reportByActivity(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = '';
        $parameters = [];
        if ($dateFrom !== null && $dateTo !== null) {
            $where = ' WHERE te.work_date BETWEEN :date_from AND :date_to';
            $parameters = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        }
        $statement = $this->pdo->prepare(
            "SELECT u.id AS user_id, u.name AS user_name, c.id AS client_id, c.name AS client_name,
                    c.color AS client_color, COALESCE(NULLIF(TRIM(te.description), ''), 'No detallado') AS activity,
                    te.work_date, SUM(te.duration_minutes) AS total_minutes
             FROM nh_time_entries te
             INNER JOIN nh_users u ON u.id = te.user_id
             INNER JOIN nh_clients c ON c.id = te.client_id
             $where
             GROUP BY u.id, u.name, c.id, c.name, c.color, te.work_date,
                      COALESCE(NULLIF(TRIM(te.description), ''), 'No detallado')
             ORDER BY c.name, u.name, te.work_date DESC, activity"
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function reportForClient(int $clientId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = 'te.client_id = :client_id';
        $parameters = ['client_id' => $clientId];
        if ($dateFrom !== null && $dateTo !== null) {
            $where .= ' AND te.work_date BETWEEN :date_from AND :date_to';
            $parameters['date_from'] = $dateFrom;
            $parameters['date_to'] = $dateTo;
        }
        $statement = $this->pdo->prepare(
            "SELECT te.work_date,
                    COALESCE(NULLIF(TRIM(te.description), ''), 'No detallado') AS activity,
                    SUM(te.duration_minutes) AS total_minutes
             FROM nh_time_entries te
             WHERE $where
             GROUP BY te.work_date, COALESCE(NULLIF(TRIM(te.description), ''), 'No detallado')
             ORDER BY te.work_date ASC, activity ASC"
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function forWeek(int $userId, string $weekStart, string $weekEnd): array
    {
        $statement = $this->pdo->prepare(
            'SELECT client_id, work_date, SUM(duration_minutes) AS duration_minutes FROM nh_time_entries
             WHERE user_id = :user_id AND work_date BETWEEN :week_start AND :week_end'
             . ' GROUP BY client_id, work_date'
        );
        $statement->execute(['user_id' => $userId, 'week_start' => $weekStart, 'week_end' => $weekEnd]);
        return $statement->fetchAll();
    }

    public function forClientPeriod(int $clientId, string $periodStart, string $periodEnd): array
    {
        $statement = $this->pdo->prepare(
            'SELECT user_id, work_date, SUM(duration_minutes) AS duration_minutes
             FROM nh_time_entries
             WHERE client_id = :client_id AND work_date BETWEEN :period_start AND :period_end
             GROUP BY user_id, work_date'
        );
        $statement->execute([
            'client_id' => $clientId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
        return $statement->fetchAll();
    }

    public function setTimesheetTotal(int $userId, int $clientId, string $date, int $desiredMinutes): void
    {
        $trackerStatement = $this->pdo->prepare(
            "SELECT COALESCE(SUM(duration_minutes), 0) FROM nh_time_entries
             WHERE user_id = :user_id AND client_id = :client_id AND work_date = :work_date AND source = 'tracker'"
        );
        $params = ['user_id' => $userId, 'client_id' => $clientId, 'work_date' => $date];
        $trackerStatement->execute($params);
        $trackerMinutes = (int) $trackerStatement->fetchColumn();
        if ($desiredMinutes < $trackerMinutes) {
            throw new DomainException('Los eventos detallados de ese día ya suman ' . formatMinutes($trackerMinutes) . '. Editalos desde Time Tracker.');
        }
        $bucketMinutes = $desiredMinutes - $trackerMinutes;
        $find = $this->pdo->prepare(
            "SELECT id FROM nh_time_entries WHERE user_id = :user_id AND client_id = :client_id
             AND work_date = :work_date AND source = 'timesheet' ORDER BY id"
        );
        $find->execute($params);
        $ids = array_map('intval', $find->fetchAll(PDO::FETCH_COLUMN));
        if ($bucketMinutes === 0) {
            if ($ids !== []) $this->deleteIds($ids, $userId);
            return;
        }
        if ($ids === []) {
            $insert = $this->pdo->prepare(
                "INSERT INTO nh_time_entries (user_id, client_id, work_date, duration_minutes, client_hourly_rate, user_hourly_rate, is_owner_work, source)
                 SELECT :user_id, :client_id, :work_date, :duration_minutes, c.hourly_rate,
                        CASE WHEN u.role = 'owner' THEN 0 ELSE u.hourly_rate END, (u.role = 'owner'), 'timesheet'
                 FROM nh_users u INNER JOIN nh_clients c ON c.id = :client_id_lookup
                 WHERE u.id = :user_id_lookup"
            );
            $insert->execute($params + ['duration_minutes'=>$bucketMinutes,'client_id_lookup'=>$clientId,'user_id_lookup'=>$userId]);
            return;
        }
        $update = $this->pdo->prepare('UPDATE nh_time_entries SET duration_minutes = :minutes WHERE id = :id AND user_id = :user_id');
        $update->execute(['minutes' => $bucketMinutes, 'id' => $ids[0], 'user_id' => $userId]);
        if (count($ids) > 1) $this->deleteIds(array_slice($ids, 1), $userId);
    }

    private function deleteIds(array $ids, int $userId): void
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->pdo->prepare("DELETE FROM nh_time_entries WHERE user_id = ? AND id IN ($placeholders)");
        $statement->execute([$userId, ...$ids]);
    }

    public function createTrackerEvent(int $userId, int $clientId, string $date, int $minutes, string $description): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO nh_time_entries (user_id, client_id, work_date, duration_minutes, client_hourly_rate, user_hourly_rate, is_owner_work, source, description)
             SELECT :user_id, :client_id, :work_date, :duration_minutes, c.hourly_rate,
                    CASE WHEN u.role = 'owner' THEN 0 ELSE u.hourly_rate END, (u.role = 'owner'), 'tracker', :description
             FROM nh_users u INNER JOIN nh_clients c ON c.id = :client_id_lookup
             WHERE u.id = :user_id_lookup"
        );
        $statement->execute(['user_id'=>$userId,'client_id'=>$clientId,'work_date'=>$date,'duration_minutes'=>$minutes,'description'=>$description,'client_id_lookup'=>$clientId,'user_id_lookup'=>$userId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function trackerEvents(int $userId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT te.id, te.work_date, te.duration_minutes, te.description, te.source, c.name AS client_name, c.color AS client_color
             FROM nh_time_entries te INNER JOIN nh_clients c ON c.id = te.client_id
             WHERE te.user_id = :user_id ORDER BY te.work_date DESC, te.id DESC"
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function findOwned(int $id, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, client_id, work_date, duration_minutes, description, source FROM nh_time_entries WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute(['id'=>$id,'user_id'=>$userId]);
        $entry = $statement->fetch();
        return $entry === false ? null : $entry;
    }

    public function updateOwned(int $id, int $userId, int $clientId, string $date, int $minutes, string $description): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE nh_time_entries te
             INNER JOIN nh_users u ON u.id = te.user_id
             INNER JOIN nh_clients c ON c.id = :client_id_lookup
             SET te.client_id=:client_id, te.work_date=:work_date, te.duration_minutes=:minutes,
                 te.description=:description, te.source='tracker', te.client_hourly_rate=c.hourly_rate,
                 te.user_hourly_rate=CASE WHEN te.is_owner_work=1 THEN 0 ELSE te.user_hourly_rate END
             WHERE te.id=:id AND te.user_id=:user_id"
        );
        $statement->execute(['client_id_lookup'=>$clientId,'client_id'=>$clientId,'work_date'=>$date,'minutes'=>$minutes,'description'=>$description,'id'=>$id,'user_id'=>$userId]);
    }

    public function deleteOwned(int $id, int $userId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM nh_time_entries WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id'=>$id,'user_id'=>$userId]);
    }

    public function updateDescriptionOwned(int $id, int $userId, string $description): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE nh_time_entries SET description = :description, source = 'tracker'
             WHERE id = :id AND user_id = :user_id"
        );
        $statement->execute(['description'=>$description,'id'=>$id,'user_id'=>$userId]);
    }

    public function updateActivityGroupByAdmin(
        int $userId,
        int $clientId,
        string $date,
        string $originalActivity,
        string $newActivity
    ): int {
        $statement = $this->pdo->prepare(
            "UPDATE nh_time_entries
             SET description = :new_activity, source = 'tracker'
             WHERE user_id = :user_id AND client_id = :client_id AND work_date = :work_date
               AND COALESCE(NULLIF(TRIM(description), ''), 'No detallado') = :original_activity"
        );
        $statement->execute([
            'new_activity' => $newActivity,
            'user_id' => $userId,
            'client_id' => $clientId,
            'work_date' => $date,
            'original_activity' => $originalActivity,
        ]);
        return $statement->rowCount();
    }

    public function updateDurationOwned(int $id, int $userId, int $minutes): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE nh_time_entries SET duration_minutes = :minutes, source = 'tracker'
             WHERE id = :id AND user_id = :user_id"
        );
        $statement->execute(['minutes'=>$minutes,'id'=>$id,'user_id'=>$userId]);
    }

    public function isPeriodClosed(string $date): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT EXISTS(SELECT 1 FROM nh_closed_periods WHERE year = :year AND month = :month)'
        );
        $statement->execute(['year' => (int) substr($date, 0, 4), 'month' => (int) substr($date, 5, 2)]);
        return (bool) $statement->fetchColumn();
    }
}
