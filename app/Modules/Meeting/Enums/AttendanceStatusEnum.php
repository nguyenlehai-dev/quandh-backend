<?php

namespace App\Modules\Meeting\Enums;

/**
 * Trạng thái điểm danh.
 */
enum AttendanceStatusEnum: string
{
    case Pending = 'pending';
    case Present = 'present';
    case Absent = 'absent';

    /** Danh sách giá trị để validate. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Rule validation: in:pending,present,absent */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    /** Nhãn tiếng Việt. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chưa đến',
            self::Present => 'Đã đến',
            self::Absent => 'Vắng mặt',
        };
    }
}
