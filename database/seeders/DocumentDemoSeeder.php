<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $organizationIds = DB::table('organizations')->pluck('id', 'slug');
        $adminId = DB::table('users')->where('user_name', 'admin')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if (! $adminId) {
            return;
        }

        $issuingLevels = [
            ['name' => 'Cấp Thành phố', 'description' => 'Văn bản ban hành ở cấp điều hành thành phố.'],
            ['name' => 'Cấp Sở / Ban / Ngành', 'description' => 'Văn bản nghiệp vụ từ các sở, ban, ngành.'],
            ['name' => 'Cấp Đơn vị trực thuộc', 'description' => 'Văn bản nội bộ tại đơn vị thành viên.'],
        ];

        $issuingAgencies = [
            ['name' => 'UBND Thành phố', 'description' => 'Cơ quan điều hành chung cấp thành phố.'],
            ['name' => 'Sở Nội vụ', 'description' => 'Cơ quan chuyên môn phụ trách nội vụ.'],
            ['name' => 'Trung tâm Công nghệ', 'description' => 'Đơn vị triển khai hạ tầng và chuyển đổi số.'],
        ];

        $documentFields = [
            ['name' => 'Chuyển đổi số', 'description' => 'Nhóm văn bản về nền tảng số, dữ liệu và hệ thống.'],
            ['name' => 'Tổ chức cán bộ', 'description' => 'Nhóm văn bản về bộ máy, vị trí việc làm, nhân sự.'],
            ['name' => 'Cải cách hành chính', 'description' => 'Nhóm văn bản về quy trình, ISO, cải tiến thủ tục.'],
            ['name' => 'Tài chính công', 'description' => 'Nhóm văn bản về ngân sách, quyết toán, đầu tư công.'],
        ];

        $documentTypes = [
            ['name' => 'Quyết định hành chính', 'description' => 'Quyết định điều hành, phân công, phê duyệt.'],
            ['name' => 'Thông báo nội bộ', 'description' => 'Thông báo triển khai hoặc nhắc việc trong đơn vị.'],
            ['name' => 'Kế hoạch công tác', 'description' => 'Kế hoạch triển khai theo tuần, tháng hoặc chuyên đề.'],
            ['name' => 'Báo cáo tổng hợp', 'description' => 'Báo cáo tình hình thực hiện, tiến độ, tổng kết.'],
            ['name' => 'Tờ trình', 'description' => 'Tờ trình xin ý kiến hoặc đề xuất quyết định.'],
        ];

        foreach ($issuingLevels as $item) {
            $this->upsertSimpleMaster('document_issuing_levels', $item, $adminId, $now);
        }

        foreach ($issuingAgencies as $item) {
            $this->upsertSimpleMaster('document_issuing_agencies', $item, $adminId, $now);
        }

        foreach ($documentFields as $item) {
            $this->upsertSimpleMaster('document_fields', $item, $adminId, $now);
        }

        foreach ($documentTypes as $item) {
            $this->upsertDocumentType($item, $adminId, $now);
        }

        $signers = [
            [
                'name' => 'Nguyễn Hữu Phúc',
                'description' => 'Giám đốc Sở Nội vụ.',
                'organization_slug' => 'so-noi-vu',
            ],
            [
                'name' => 'Trần Minh An',
                'description' => 'Chánh Văn phòng UBND Thành phố.',
                'organization_slug' => 'ubnd-thanh-pho',
            ],
            [
                'name' => 'Lê Quang Hưng',
                'description' => 'Giám đốc Trung tâm Công nghệ.',
                'organization_slug' => 'trung-tam-cong-nghe',
            ],
        ];

        foreach ($signers as $item) {
            $organizationId = $organizationIds[$item['organization_slug']] ?? null;
            if (! $organizationId) {
                continue;
            }

            $this->upsertOrgScopedMaster(
                'document_signers',
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'organization_id' => (int) $organizationId,
                ],
                $adminId,
                $now,
            );
        }

        $levelIds = DB::table('document_issuing_levels')->pluck('id', 'name');
        $agencyIds = DB::table('document_issuing_agencies')->pluck('id', 'name');
        $fieldIds = DB::table('document_fields')->pluck('id', 'name');
        $typeIds = DB::table('document_types')->whereNull('meeting_type_id')->pluck('id', 'name');

        $signerIds = [];
        foreach (DB::table('document_signers')->select('id', 'name', 'organization_id')->get() as $signer) {
            $signerIds[$signer->name.'#'.$signer->organization_id] = $signer->id;
        }

        $documents = [
            [
                'so_ky_hieu' => 'QĐ-01/SNV-2026',
                'ten_van_ban' => 'Quyết định phê duyệt kế hoạch kiện toàn vị trí việc làm năm 2026',
                'noi_dung' => 'Phê duyệt kế hoạch rà soát, chuẩn hóa và kiện toàn vị trí việc làm theo lộ trình năm 2026.',
                'organization_slug' => 'so-noi-vu',
                'issuing_agency' => 'Sở Nội vụ',
                'issuing_level' => 'Cấp Sở / Ban / Ngành',
                'signer' => 'Nguyễn Hữu Phúc',
                'status' => 'active',
                'type_names' => ['Quyết định hành chính'],
                'field_names' => ['Tổ chức cán bộ'],
                'ngay_ban_hanh' => '2026-01-15',
                'ngay_xuat_ban' => '2026-01-16',
                'ngay_hieu_luc' => '2026-01-20',
                'ngay_het_hieu_luc' => null,
            ],
            [
                'so_ky_hieu' => 'KH-07/SNV-2026',
                'ten_van_ban' => 'Kế hoạch nâng cao chất lượng cải cách hành chính quý II/2026',
                'noi_dung' => 'Triển khai kiểm soát quy trình nội bộ, chuẩn hóa biểu mẫu và theo dõi KPI cải cách hành chính.',
                'organization_slug' => 'so-noi-vu',
                'issuing_agency' => 'Sở Nội vụ',
                'issuing_level' => 'Cấp Sở / Ban / Ngành',
                'signer' => 'Nguyễn Hữu Phúc',
                'status' => 'active',
                'type_names' => ['Kế hoạch công tác'],
                'field_names' => ['Cải cách hành chính'],
                'ngay_ban_hanh' => '2026-02-05',
                'ngay_xuat_ban' => '2026-02-05',
                'ngay_hieu_luc' => '2026-02-10',
                'ngay_het_hieu_luc' => '2026-06-30',
            ],
            [
                'so_ky_hieu' => 'TB-03/UBND-2026',
                'ten_van_ban' => 'Thông báo kết luận họp giao ban chuyển đổi số cấp thành phố',
                'noi_dung' => 'Thông báo phân công nhiệm vụ sau họp giao ban về nền tảng dữ liệu dùng chung và tích hợp dịch vụ công.',
                'organization_slug' => 'ubnd-thanh-pho',
                'issuing_agency' => 'UBND Thành phố',
                'issuing_level' => 'Cấp Thành phố',
                'signer' => 'Trần Minh An',
                'status' => 'active',
                'type_names' => ['Thông báo nội bộ'],
                'field_names' => ['Chuyển đổi số'],
                'ngay_ban_hanh' => '2026-03-01',
                'ngay_xuat_ban' => '2026-03-01',
                'ngay_hieu_luc' => '2026-03-02',
                'ngay_het_hieu_luc' => null,
            ],
            [
                'so_ky_hieu' => 'TT-11/UBND-2026',
                'ten_van_ban' => 'Tờ trình về phương án phân bổ ngân sách cho hạ tầng số',
                'noi_dung' => 'Đề xuất bố trí nguồn lực cho các hạng mục trung tâm dữ liệu, an toàn thông tin và nền tảng tích hợp.',
                'organization_slug' => 'ubnd-thanh-pho',
                'issuing_agency' => 'UBND Thành phố',
                'issuing_level' => 'Cấp Thành phố',
                'signer' => 'Trần Minh An',
                'status' => 'active',
                'type_names' => ['Tờ trình'],
                'field_names' => ['Chuyển đổi số', 'Tài chính công'],
                'ngay_ban_hanh' => '2026-03-12',
                'ngay_xuat_ban' => '2026-03-13',
                'ngay_hieu_luc' => '2026-03-15',
                'ngay_het_hieu_luc' => null,
            ],
            [
                'so_ky_hieu' => 'BC-09/TTCN-2026',
                'ten_van_ban' => 'Báo cáo tổng hợp tiến độ triển khai nền tảng tích hợp dữ liệu',
                'noi_dung' => 'Tổng hợp tiến độ tích hợp hệ thống, trạng thái API và các hạng mục hạ tầng phục vụ dùng chung.',
                'organization_slug' => 'trung-tam-cong-nghe',
                'issuing_agency' => 'Trung tâm Công nghệ',
                'issuing_level' => 'Cấp Đơn vị trực thuộc',
                'signer' => 'Lê Quang Hưng',
                'status' => 'active',
                'type_names' => ['Báo cáo tổng hợp'],
                'field_names' => ['Chuyển đổi số'],
                'ngay_ban_hanh' => '2026-03-20',
                'ngay_xuat_ban' => '2026-03-20',
                'ngay_hieu_luc' => '2026-03-21',
                'ngay_het_hieu_luc' => null,
            ],
            [
                'so_ky_hieu' => 'TB-14/TTCN-2026',
                'ten_van_ban' => 'Thông báo lịch bảo trì hệ thống và kế hoạch sao lưu dữ liệu',
                'noi_dung' => 'Thông báo kế hoạch bảo trì hạ tầng, cửa sổ downtime dự kiến và quy trình khôi phục dữ liệu.',
                'organization_slug' => 'trung-tam-cong-nghe',
                'issuing_agency' => 'Trung tâm Công nghệ',
                'issuing_level' => 'Cấp Đơn vị trực thuộc',
                'signer' => 'Lê Quang Hưng',
                'status' => 'inactive',
                'type_names' => ['Thông báo nội bộ'],
                'field_names' => ['Chuyển đổi số'],
                'ngay_ban_hanh' => '2026-03-25',
                'ngay_xuat_ban' => '2026-03-25',
                'ngay_hieu_luc' => '2026-03-26',
                'ngay_het_hieu_luc' => '2026-04-10',
            ],
        ];

        foreach ($documents as $item) {
            $organizationId = $organizationIds[$item['organization_slug']] ?? null;
            if (! $organizationId) {
                continue;
            }

            $existingDocumentId = DB::table('documents')->where('so_ky_hieu', $item['so_ky_hieu'])->value('id');
            $signerId = $signerIds[$item['signer'].'#'.$organizationId] ?? null;

            $payload = [
                'ten_van_ban' => $item['ten_van_ban'],
                'noi_dung' => $item['noi_dung'],
                'issuing_agency_id' => $agencyIds[$item['issuing_agency']] ?? null,
                'issuing_level_id' => $levelIds[$item['issuing_level']] ?? null,
                'signer_id' => $signerId,
                'ngay_ban_hanh' => $item['ngay_ban_hanh'],
                'ngay_xuat_ban' => $item['ngay_xuat_ban'],
                'ngay_hieu_luc' => $item['ngay_hieu_luc'],
                'ngay_het_hieu_luc' => $item['ngay_het_hieu_luc'],
                'status' => $item['status'],
                'organization_id' => (int) $organizationId,
                'created_by' => (int) $adminId,
                'updated_by' => (int) $adminId,
                'updated_at' => $now,
            ];

            if ($existingDocumentId) {
                DB::table('documents')->where('id', $existingDocumentId)->update($payload);
                $documentId = $existingDocumentId;
            } else {
                $documentId = DB::table('documents')->insertGetId([
                    'so_ky_hieu' => $item['so_ky_hieu'],
                    ...$payload,
                    'created_at' => $now,
                ]);
            }

            foreach ($item['type_names'] as $typeName) {
                $typeId = $typeIds[$typeName] ?? null;
                if (! $typeId) {
                    continue;
                }

                DB::table('document_document_type')->insertOrIgnore([
                    'document_id' => (int) $documentId,
                    'document_type_id' => (int) $typeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($item['field_names'] as $fieldName) {
                $fieldId = $fieldIds[$fieldName] ?? null;
                if (! $fieldId) {
                    continue;
                }

                DB::table('document_document_field')->insertOrIgnore([
                    'document_id' => (int) $documentId,
                    'document_field_id' => (int) $fieldId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    protected function upsertSimpleMaster(string $table, array $item, int $adminId, $now): void
    {
        $rowId = DB::table($table)->where('name', $item['name'])->value('id');

        $payload = [
            'description' => $item['description'],
            'status' => 'active',
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn($table, 'organization_id')) {
            $payload['organization_id'] = null;
        }

        if ($rowId) {
            DB::table($table)->where('id', $rowId)->update($payload);
        } else {
            DB::table($table)->insert([
                'name' => $item['name'],
                ...$payload,
                'created_at' => $now,
            ]);
        }
    }

    protected function upsertDocumentType(array $item, int $adminId, $now): void
    {
        $rowId = DB::table('document_types')->where('name', $item['name'])->whereNull('organization_id')->whereNull('meeting_type_id')->value('id');

        $payload = [
            'description' => $item['description'],
            'status' => 'active',
            'meeting_type_id' => null,
            'organization_id' => null,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'updated_at' => $now,
        ];

        if ($rowId) {
            DB::table('document_types')->where('id', $rowId)->update($payload);
        } else {
            DB::table('document_types')->insert([
                'name' => $item['name'],
                ...$payload,
                'created_at' => $now,
            ]);
        }
    }

    protected function upsertOrgScopedMaster(string $table, array $item, int $adminId, $now): void
    {
        $rowId = DB::table($table)
            ->where('name', $item['name'])
            ->where('organization_id', $item['organization_id'])
            ->value('id');

        $payload = [
            'description' => $item['description'],
            'status' => 'active',
            'organization_id' => (int) $item['organization_id'],
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'updated_at' => $now,
        ];

        if ($rowId) {
            DB::table($table)->where('id', $rowId)->update($payload);
        } else {
            DB::table($table)->insert([
                'name' => $item['name'],
                ...$payload,
                'created_at' => $now,
            ]);
        }
    }
}
