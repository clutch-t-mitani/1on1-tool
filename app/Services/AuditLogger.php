<?php

namespace App\Services;

use App\Models\AuditLog;

/**
 * 管理者による特権操作を audit_logs テーブルに記録する。
 */
final class AuditLogger
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function log(
        int $adminId,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $metadata = null,
    ): void {
        AuditLog::create([
            'admin_id'    => $adminId,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'metadata'    => $metadata,
        ]);
    }
}
