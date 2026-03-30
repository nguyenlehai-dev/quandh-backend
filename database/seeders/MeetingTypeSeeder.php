<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Meeting\Models\MeetingType; // Wait, let's use App\Modules\Meeting\Models\MeetingType;

class MeetingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Họp giao ban lãnh đạo',
                'description' => 'Cuộc họp định kỳ của ban lãnh đạo để đánh giá tiến độ và định hướng công việc.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Họp giao ban phòng ban',
                'description' => 'Cuộc họp định kỳ của các phòng ban chức năng.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Họp chuyên đề',
                'description' => 'Cuộc họp tập trung thảo luận sâu về một chủ đề chuyên biệt.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Họp triển khai dự án',
                'description' => 'Cuộc họp khởi động hoặc đánh giá tiến độ các dự án đang triển khai.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sinh hoạt chi bộ / Đảng ủy',
                'description' => 'Cuộc họp nội bộ của tổ chức Đảng.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hội nghị sơ kết, tổng kết',
                'description' => 'Cuộc họp tổng kết hoạt động theo quý, bán niên hoặc thường niên.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tập huấn, bồi dưỡng',
                'description' => 'Chương trình đào tạo, hướng dẫn nghiệp vụ chuyên môn.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Họp Đại hội đồng cổ đông / HĐQT',
                'description' => 'Cuộc họp cấp cao với cơ quan ra quyết định lớn nhất.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // Ensure to remove old ones or just insert or ignore
        foreach ($types as $type) {
            \App\Modules\Meeting\Models\MeetingType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
