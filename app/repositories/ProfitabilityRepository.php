<?php
declare(strict_types=1);

final class ProfitabilityRepository
{
    public function __construct(private PDO $pdo) {}

    public function between(string $dateFrom, string $dateTo): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id AS client_id, c.name AS client_name, c.color AS client_color,
                    SUM(te.duration_minutes * te.client_hourly_rate) / SUM(te.duration_minutes) AS client_hourly_rate,
                    u.id AS user_id, u.name AS user_name, te.is_owner_work,
                    SUM(te.duration_minutes * te.user_hourly_rate) / SUM(te.duration_minutes) AS user_hourly_rate,
                    SUM(te.duration_minutes) AS total_minutes,
                    SUM(te.duration_minutes * te.client_hourly_rate) / 60 AS billed_amount,
                    SUM(te.duration_minutes * te.user_hourly_rate) / 60 AS labor_cost
             FROM nh_time_entries te
             INNER JOIN nh_clients c ON c.id = te.client_id
             INNER JOIN nh_users u ON u.id = te.user_id
             WHERE te.work_date BETWEEN :date_from AND :date_to
             GROUP BY c.id, c.name, c.color, u.id, u.name, te.is_owner_work
             ORDER BY c.name ASC, u.name ASC'
        );
        $statement->execute(['date_from'=>$dateFrom,'date_to'=>$dateTo]);
        return $statement->fetchAll();
    }

    public function teamPaymentsBetween(string $dateFrom, string $dateTo): array
    {
        $statement = $this->pdo->prepare(
            "SELECT u.id AS user_id, u.name AS user_name,
                    SUM(te.duration_minutes) AS total_minutes,
                    SUM(te.duration_minutes * te.user_hourly_rate) / SUM(te.duration_minutes) AS hourly_rate,
                    SUM(te.duration_minutes * te.user_hourly_rate) / 60 AS total_pay
             FROM nh_time_entries te
             INNER JOIN nh_users u ON u.id = te.user_id
             WHERE te.work_date BETWEEN :date_from AND :date_to AND te.is_owner_work = 0
             GROUP BY u.id, u.name
             ORDER BY u.name ASC"
        );
        $statement->execute(['date_from'=>$dateFrom,'date_to'=>$dateTo]);
        return $statement->fetchAll();
    }
}
