<?php

namespace Database\Seeders;

use App\Modules\Core\Models\User;
use App\Modules\Post\Models\Post;
use App\Modules\Post\Models\PostCategory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Thứ tự: Permission/Role/User hệ thống → nội dung chung → dữ liệu demo nghiệp vụ.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(OrganizationDemoSeeder::class);
        $this->call(AuthFlowDemoSeeder::class);
        $this->seedPostCategories();
        $this->seedPosts();
        $this->call(SettingSeeder::class);
        $this->call(ProjectDemoSeeder::class);
    }

    /**
     * Tạo danh mục tin tức dạng cây (parent_id): vài danh mục gốc, mỗi gốc có vài danh mục con.
     */
    protected function seedPostCategories(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        PostCategory::withoutEvents(function () use ($user) {
            $rootNames = ['Tin công nghệ', 'Tin thể thao', 'Tin kinh tế', 'Giải trí', 'Giáo dục'];
            foreach ($rootNames as $index => $name) {
                $slug = \Illuminate\Support\Str::slug($name);
                PostCategory::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'sort_order' => $index + 1,
                        'parent_id' => null,
                        'status' => 'active',
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]
                );
            }

            $roots = PostCategory::whereNull('parent_id')->orderBy('sort_order')->get();

            foreach ($roots as $root) {
                $childCount = rand(2, 3);
                for ($i = 0; $i < $childCount; $i++) {
                    $childSlug = \Illuminate\Support\Str::slug($root->name.' '.fake()->word()).'-'.uniqid();
                    PostCategory::updateOrCreate(
                        ['slug' => $childSlug],
                        [
                            'name' => $root->name.' - '.fake()->word(),
                            'sort_order' => $i + 1,
                            'parent_id' => $root->id,
                            'status' => 'active',
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                        ]
                    );
                }
            }
        });
    }

    /**
     * Tạo bài viết, gán ngẫu nhiên user và danh mục.
     */
    protected function seedPosts(): void
    {
        $users = User::all();
        $categories = PostCategory::all();

        if ($users->isEmpty()) {
            return;
        }

        Post::withoutEvents(function () use ($users, $categories) {
            Post::factory(20)
                ->sequence(
                    fn ($sequence) => [
                        'created_by' => $users->random()->id,
                        'updated_by' => $users->random()->id,
                    ]
                )
                ->create()
                ->each(function (Post $post) use ($categories) {
                    if ($categories->isNotEmpty()) {
                        $post->categories()->sync([$categories->random()->id]);
                    }
                });
        });
    }
}
