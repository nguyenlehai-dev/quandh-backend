<?php

namespace App\Modules\Meeting\Enums;

/**
 * Trạng thái cuộc họp.
 */
enum MeetingStatusEnum: string
{
    case Draft = 'draft';
    case Active = 'active';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /** Danh sách giá trị để validate. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Rule validation: in:draft,active,in_progress,completed */
    public static function rule(): string
    {
        return 'in:'.implode(',', self::values());
    }

    /** Nhãn tiếng Việt. */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Active => 'Kích hoạt',
            self::InProgress => 'Đang họp',
            self::Completed => 'Kết thúc',
        };
    }
}
