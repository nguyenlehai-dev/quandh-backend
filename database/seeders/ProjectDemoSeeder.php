<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Gom dữ liệu demo nghiệp vụ để chạy trong flow seed chuẩn của dự án.
 *
 * Quy trình:
 * 1. Tạo dữ liệu meeting/category/document demo
 * 2. Bổ sung danh sách user phục vụ họp và gán ngẫu nhiên vào nhóm/cuộc họp
 */
class ProjectDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MeetingDummySeeder::class,
            AddMeetingUsersSeeder::class,
            OrganizationDemoSeeder::class,
            DocumentDemoSeeder::class,
        ]);
    }
}
