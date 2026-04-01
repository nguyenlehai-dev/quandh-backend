<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AddMeetingUsersSeeder extends Seeder
{
    public function run()
    {
        $organizationId = DB::table('organizations')->value('id') ?: 1;
        $now = Carbon::now();
        $password = Hash::make('password');

        $usersParams = [
            // Hội đồng quản trị
            ['name' => 'Lê Minh Hải', 'position' => 'Chủ tịch HĐQT', 'prefix' => 'hdqt_lmhai'],
            ['name' => 'Nguyễn Thị Hoa', 'position' => 'Thành viên HĐQT', 'prefix' => 'hdqt_nthoa'],
            ['name' => 'Trần Văn Cường', 'position' => 'Trưởng Ban kiểm soát', 'prefix' => 'bks_tvcuong'],
            ['name' => 'Phạm Hoàng Long', 'position' => 'Cổ đông lớn', 'prefix' => 'cd_phlong'],
            
            // Ban giám đốc
            ['name' => 'Hoàng Ngọc Ánh', 'position' => 'Tổng Giám đốc', 'prefix' => 'bgd_hnanh'],
            ['name' => 'Đinh Tuấn Tài', 'position' => 'Phó Tổng Giám đốc', 'prefix' => 'bgd_dttai'],
            ['name' => 'Vũ Thị Xuân', 'position' => 'Giám đốc Tài chính', 'prefix' => 'bgd_vtxuan'],
            ['name' => 'Lý Anh Quân', 'position' => 'Giám đốc Nhân sự', 'prefix' => 'bgd_laquan'],

            // Trưởng/Phó các phòng ban
            ['name' => 'Bùi Đức Mạnh', 'position' => 'Trưởng phòng Kinh doanh', 'prefix' => 'tpb_bdmanh'],
            ['name' => 'Ngô Thanh Thư', 'position' => 'Trưởng phòng Kế toán', 'prefix' => 'tpb_ntthu'],
            ['name' => 'Đặng Văn Sơn', 'position' => 'Trưởng phòng IT', 'prefix' => 'tpb_dvson'],
            ['name' => 'Lê Phương Linh', 'position' => 'Trưởng phòng Hành chính', 'prefix' => 'tpb_lplinh'],

            // Ban quản lý dự án & Kỹ thuật
            ['name' => 'Vương Đình Đạt', 'position' => 'Giám đốc Dự án', 'prefix' => 'da_vddat'],
            ['name' => 'Phan Tấn Lộc', 'position' => 'Kỹ sư trưởng', 'prefix' => 'da_ptloc'],
            ['name' => 'Mai Quỳnh Trang', 'position' => 'Chuyên viên Phân tích', 'prefix' => 'da_mqtrang'],
            ['name' => 'Hồ Vĩnh Hoàng', 'position' => 'Trưởng nhóm Thi công', 'prefix' => 'da_hvhoang'],

            // Khách mời / Đối tác / Thư ký
            ['name' => 'Chu Hải Đăng', 'position' => 'Đối tác Tư vấn', 'prefix' => 'dt_chdang'],
            ['name' => 'Đào Bảo Trâm', 'position' => 'Thư ký cuộc họp', 'prefix' => 'tk_dbtram'],
            ['name' => 'Trịnh Quốc Khánh', 'position' => 'Cố vấn Pháp lý', 'prefix' => 'cv_tqkhanh'],
            ['name' => 'Tạ Quang Thắng', 'position' => 'Nhân viên', 'prefix' => 'nv_tqthang'],
        ];

        $newUsers = [];

        foreach ($usersParams as $u) {
            $email = $u['prefix'] . '@snvdn.com';
            
            // Check if user already exists
            $user = DB::table('users')->where('email', $email)->first();
            if (!$user) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $u['name'] . ' (' . $u['position'] . ')',
                    'email' => $email,
                    'user_name' => $u['prefix'],
                    'password' => $password,
                    'status' => 'active',
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Assign to organization via role
                $roleId = DB::table('roles')->value('id');
                if ($roleId) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $roleId,
                        'model_type' => \App\Modules\Core\Models\User::class,
                        'model_id' => $userId,
                        'organization_id' => $organizationId,
                    ]);
                }

                $newUsers[] = $userId;
            } else {
                $newUsers[] = $user->id;
            }
        }

        echo "Created " . count($newUsers) . " specific users relevant to meetings.\n";

        // Assign these new users randomly to existing attendee groups
        if (count($newUsers) > 0) {
            $groupIds = DB::table('m_attendee_groups')->pluck('id')->toArray();
            
            if (count($groupIds) > 0) {
                // Determine relevant groups roughly by matching the users to any existing groups
                foreach ($groupIds as $gId) {
                    // Pull a random subset of 3-7 users
                    shuffle($newUsers);
                    $subset = array_slice($newUsers, 0, rand(3, 7));
                    foreach ($subset as $uId) {
                        try {
                            DB::table('m_attendee_group_members')->insert([
                                'attendee_group_id' => $gId,
                                'user_id' => $uId,
                                'position' => 'Thành viên',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        } catch (\Exception $e) {
                            // ignore duplicate constraint exceptions if user was already in the group
                        }
                    }
                }
                echo "Assigned new users to attendee groups.\n";
            }

            // Also assign some to existing upcoming meetings as direct participants
            $meetingIds = DB::table('m_meetings')->where('status', '!=', 'completed')->pluck('id')->toArray();
            if (count($meetingIds) > 0) {
                foreach ($meetingIds as $mId) {
                    shuffle($newUsers);
                    $subset = array_slice($newUsers, 0, rand(2, 5));
                    foreach ($subset as $idx => $uId) {
                        try {
                            DB::table('m_participants')->insert([
                                'meeting_id' => $mId,
                                'user_id' => $uId,
                                'meeting_role' => $idx === 0 ? 'delegate' : 'secretary',
                                'attendance_status' => collect(['pending', 'present'])->random(),
                                'organization_id' => $organizationId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        } catch (\Exception $e) {
                            \Log::info("Could not add participant: " . $e->getMessage());
                        }
                    }
                }
                echo "Assigned new users to meetings.\n";
            }
        }
    }
}
