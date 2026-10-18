<?php
namespace App\Services;

use App\Helpers\Database;

/**
 * ActivityLogger
 *
 * Records every significant action in the `activity_log` table.
 * Fails silently so it never breaks the main application.
 *
 * Usage:
 *   ActivityLogger::write($bookId, auth()['id'], 'invoice.created', 'Invoice', $id, 'Invoice #000123 created');
 */
class ActivityLogger
{
    // ─────────────────────────────────────────────────────────────────────────
    //  Write an activity log entry
    // ─────────────────────────────────────────────────────────────────────────
    public static function write(
        ?int    $bookId,
        ?int    $userId,
        string  $action,
        ?string $subjectType = null,
        ?int    $subjectId   = null,
        string  $description = '',
        ?array  $oldData     = null,
        ?array  $newData     = null
    ): void {
        try {
            Database::run(
                'INSERT INTO activity_log
                    (book_id, user_id, action, subject_type, subject_id,
                     description, old_data, new_data, ip_address, user_agent, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $bookId,
                    $userId,
                    $action,
                    $subjectType,
                    $subjectId,
                    $description,
                    $oldData !== null ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                    $newData !== null ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                    self::resolveIp(),
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                    now(),
                ]
            );
        } catch (\Throwable $e) {
            error_log('[ActivityLogger] ' . $e->getMessage());
        }
    }

    // Convenient instance-style call (some controllers may use this)
    public function log(
        ?int    $bookId,
        ?int    $userId,
        string  $action,
        ?string $subjectType = null,
        ?int    $subjectId   = null,
        string  $description = ''
    ): void {
        self::write($bookId, $userId, $action, $subjectType, $subjectId, $description);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Fetch recent log for the activity feed (book dashboard)
    // ─────────────────────────────────────────────────────────────────────────
    public static function recent(int $bookId, int $limit = 20): array
    {
        try {
            $rows = Database::query(
                "SELECT al.*, u.name AS user_name
                 FROM activity_log al
                 LEFT JOIN users u ON u.id = al.user_id
                 WHERE al.book_id = ?
                 ORDER BY al.created_at DESC
                 LIMIT ?",
                [$bookId, $limit]
            );
            return array_map([self::class, 'decorate'], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Map action string → Font Awesome icon + colour class
    // ─────────────────────────────────────────────────────────────────────────
    private static function decorate(array $row): array
    {
        $map = [
            'invoice.created'    => ['invoice',        'var(--blue)'],
            'invoice.updated'    => ['file-edit',            'var(--amber)'],
            'invoice.deleted'    => ['file-x',   'var(--red)'],
            'invoice.paid'       => ['check-circle',        'var(--green)'],
            'invoice.payment'    => ['banknote',          'var(--green)'],
            'due.created'        => ['hand-coins', 'var(--amber)'],
            'due.payment'        => ['banknote',     'var(--green)'],
            'due.updated'        => ['square-pen',                 'var(--amber)'],
            'due.cancelled'      => ['ban',                 'var(--text-muted)'],
            'due.deleted'        => ['trash',               'var(--red)'],
            'debt.created'       => ['file-minus',   'var(--red)'],
            'debt.payment'       => ['banknote',     'var(--green)'],
            'debt.updated'       => ['square-pen',                 'var(--amber)'],
            'debt.cancelled'     => ['ban',                 'var(--text-muted)'],
            'debt.deleted'       => ['trash',               'var(--red)'],
            'fund.in'            => ['circle-arrow-down',   'var(--green)'],
            'fund.out'           => ['circle-arrow-up',     'var(--red)'],
            'fund.updated'       => ['square-pen',                 'var(--amber)'],
            'fund.deleted'       => ['trash',               'var(--red)'],
            'expense.created'    => ['receipt',             'var(--amber)'],
            'expense.updated'    => ['square-pen',                 'var(--amber)'],
            'expense.deleted'    => ['trash',               'var(--red)'],
            'customer.created'   => ['user-plus',           'var(--blue)'],
            'customer.updated'   => ['user-edit',            'var(--amber)'],
            'customer.deleted'   => ['user-x',          'var(--red)'],
            'supplier.created'   => ['truck',               'var(--blue)'],
            'supplier.updated'   => ['truck',               'var(--amber)'],
            'supplier.deleted'   => ['truck',               'var(--red)'],
            'product.created'    => ['package',                 'var(--blue)'],
            'product.updated'    => ['package-open',            'var(--amber)'],
            'product.deleted'    => ['package-x',           'var(--red)'],
            'stock.adjusted'     => ['warehouse',           '#8b5cf6'],
            'return.created'     => ['rotate-ccw',         'var(--amber)'],
            'return.deleted'     => ['trash',               'var(--red)'],
            'coupon.created'     => ['ticket',              '#d4ec69'],
            'coupon.updated'     => ['ticket',              'var(--amber)'],
            'coupon.deleted'     => ['trash',               'var(--red)'],
            'employee.created'   => ['id-card',            'var(--blue)'],
            'employee.updated'   => ['id-card',            'var(--amber)'],
            'employee.deleted'   => ['id-card',            'var(--red)'],
            'privilege.created'  => ['star-filled',                'var(--green)'],
            'privilege.updated'  => ['star-filled',                'var(--amber)'],
            'privilege.deleted'  => ['star-filled',                'var(--red)'],
            'book.settings'      => ['sliders',             'var(--brand)'],
            'book.created'       => ['book',                'var(--green)'],
            'delivery.created'   => ['truck-fast',          'var(--blue)'],
            'delivery.updated'   => ['truck',               'var(--amber)'],
            'delivery.completed' => ['check',               'var(--green)'],
            'payment.recorded'   => ['banknote',          'var(--green)'],
            'auth.login'         => ['login',    'var(--green)'],
            'auth.logout'        => ['logout',  'var(--text-muted)'],
            'notification.sent'  => ['bell',                'var(--blue)'],
            'notification.read'  => ['bell-off',          'var(--text-muted)'],
        ];

        [$icon, $color] = $map[$row['action'] ?? ''] ?? ['circle-dot', 'var(--text-muted)'];
        $row['icon']       = $icon;
        $row['icon_color'] = $color;

        if (isset($row['old_data']) && is_string($row['old_data'])) {
            $row['old_data'] = json_decode($row['old_data'], true);
        }
        if (isset($row['new_data']) && is_string($row['new_data'])) {
            $row['new_data'] = json_decode($row['new_data'], true);
        }
        return $row;
    }

    private static function resolveIp(): string
    {
        foreach (['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                return trim(explode(',', $_SERVER[$k])[0]);
            }
        }
        return '0.0.0.0';
    }
}
