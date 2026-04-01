<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Str;

/**
 * Chuyển đổi permission Spatie (resource.action) sang định dạng CASL abilities.
 *
 * Mỗi permission Laravel tương ứng một đối tượng CASL riêng, không gộp chung.
 * Spatie action được map sang CASL action chuẩn (index/show → read, store → create, ...),
 * resource name được chuyển sang dạng số ít PascalCase (users → User, meetings → Meeting).
 *
 * Format: [{ "action": "read", "subject": "User" }, { "action": "create", "subject": "User" }, ...]
 */
class CaslAbilityConverter
{
    /**
     * Map Spatie action → CASL action chuẩn.
     * Các action không có trong map sẽ được giữ nguyên.
     */
    protected static array $actionMap = [
        'index'   => 'read',
        'show'    => 'read',
        'store'   => 'create',
        'update'  => 'update',
        'destroy' => 'delete',
    ];

    /**
     * Subject alias: dùng khi tên tự động sinh ra không khớp với FE.
     * Key = resource name gốc trong Spatie, Value = subject mà FE mong đợi.
     */
    protected static array $subjectAlias = [
        'log-activities' => 'ActivityLog',
        'settings'       => 'SystemSetting',
    ];

    /**
     * Chuyển danh sách permission Spatie sang abilities theo chuẩn CASL.
     *
     * Ví dụ: ["users.index", "users.show", "meetings.store"]
     * →  [
     *       { action: "read",   subject: "User" },
     *       { action: "read",   subject: "User" },
     *       { action: "create", subject: "Meeting" },
     *    ]
     *
     * Kết quả được deduplicate (không trùng action+subject).
     *
     * @param  array<string>  $permissions
     * @return array<array{action: string, subject: string}>
     */
    public static function toCaslAbilities(array $permissions): array
    {
        $seen = [];
        $abilities = [];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || ! str_contains($permission, '.')) {
                continue;
            }

            [$resource, $spatieAction] = explode('.', $permission, 2);

            $action = self::$actionMap[$spatieAction] ?? $spatieAction;
            $subject = self::resourceToSubject($resource);

            // Deduplicate (index + show cùng map thành read)
            $key = "{$action}|{$subject}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $abilities[] = [
                'action' => $action,
                'subject' => $subject,
            ];
        }

        return $abilities;
    }

    /**
     * Chuyển resource name (lowercase, có thể số nhiều, có thể kebab-case)
     * thành PascalCase số ít.
     *
     * Ưu tiên dùng alias nếu có, nếu không thì tự sinh từ Str::singular.
     * Ví dụ: "users" → "User", "log-activities" → "ActivityLog", "settings" → "SystemSetting"
     */
    protected static function resourceToSubject(string $resource): string
    {
        if (isset(self::$subjectAlias[$resource])) {
            return self::$subjectAlias[$resource];
        }

        return collect(explode('-', $resource))
            ->map(fn (string $part) => ucfirst(Str::singular(strtolower($part))))
            ->implode('');
    }
}
