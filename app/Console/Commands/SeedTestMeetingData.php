<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingDocument;
use App\Modules\Meeting\Models\MeetingVoting;
use App\Modules\Meeting\Models\MeetingConclusion;
use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\Auth;

class SeedTestMeetingData extends Command
{
    protected $signature = 'test:seed-meeting';
    protected $description = 'Tạo dữ liệu siêu tốc để test luồng đồng bộ';

    public function handle()
    {
        $admin = User::first();
        if (!$admin) {
            $this->error('Không có user nào, tạo user trước!');
            return;
        }
        Auth::login($admin);

        // Tạo Cuộc họp
        $meeting = Meeting::create([
            'title' => 'Họp Tổng Kết Công Tác Tháng 4/2026',
            'description' => 'Cuộc họp quan trọng đánh giá toàn bộ hoạt động kinh doanh',
            'location' => 'Phòng họp Tầng 12 - Tòa nhà A',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHours(2),
            'status' => 'draft',
            'organization_id' => 1,
            'creator_id' => $admin->id,
            'editor_id' => $admin->id,
        ]);

        $this->info("Đã tạo cuộc họp: " . $meeting->title);

        // Tạo Tài liệu
        MeetingDocument::create([
            'meeting_id' => $meeting->id,
            'title' => 'Báo cáo doanh thu Quý 1 năm 2026',
            'description' => 'Số liệu lấy từ phòng kế toán',
            'creator_id' => $admin->id,
            'editor_id' => $admin->id,
        ]);

        // Tạo Biểu quyết
        MeetingVoting::create([
            'meeting_id' => $meeting->id,
            'title' => 'Thông qua dự thảo chia cổ tức',
            'type' => 'public',
            'status' => 'pending',
        ]);

        // Tạo Kết luận
        MeetingConclusion::create([
            'meeting_id' => $meeting->id,
            'title' => 'Chốt phương án kinh doanh tháng tới',
            'content' => 'Thống nhất đẩy mạnh 200% doanh số mảng dịch vụ trực tuyến.',
            'creator_id' => $admin->id,
            'editor_id' => $admin->id,
        ]);

        $this->info("Đã chèn Tài liệu, Biểu quyết và Kết luận thành công vào Cuộc họp ID: {$meeting->id}");
    }
}
