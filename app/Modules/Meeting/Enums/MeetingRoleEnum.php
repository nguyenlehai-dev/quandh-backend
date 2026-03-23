<?php

namespace App\Modules\Meeting\Enums;

/**
 * Vai trò trong cuộc họp.
 */
enum MeetingRoleEnum: string
{
    case Chair = 'chair';
    case Secretary = 'secretary';
    case Delegate = 'delegate';

    /** Danh sách giá trị để validate. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Rule validation: in:chair,secretary,delegate */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    /** Nhãn tiếng Việt. */
    public function label(): string
    {
        return match ($this) {
            self::Chair => 'Chủ trì',
            self::Secretary => 'Thư ký',
            self::Delegate => 'Đại biểu',
        };
    }
}
