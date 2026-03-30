<?php

namespace App\Modules\Core\Imports;

use App\Modules\Core\Enums\UserStatusEnum;
use App\Modules\Core\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $email = $row['email'] ?? null;
            $userName = $row['user_name'] ?? $row['user_name_'] ?? null;
            
            if (!$email && !$userName) {
                continue; 
            }

            $user = null;
            if (!empty($row['id'])) {
                $user = User::find($row['id']);
            }
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }
            if (!$user && $userName) {
                $user = User::where('user_name', $userName)->first();
            }

            $data = [
                'name' => $row['name'] ?? $row['name_'] ?? '',
                'status' => $row['status'] ?? UserStatusEnum::Active->value,
            ];

            if ($email) {
                $data['email'] = $email;
            }
            if ($userName) {
                $data['user_name'] = $userName;
            }

            if (!$user) {
                $data['password'] = Hash::make($row['password'] ?? 'password');
                User::create($data);
            } else {
                if (!empty($row['password'])) {
                    $data['password'] = Hash::make($row['password']);
                }
                $user->update($data);
            }
        }
    }
}
