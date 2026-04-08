<?php

namespace App\Modules\Post\Services;

use App\Modules\Core\Services\MediaService;
use App\Modules\Post\Enums\PostStatusEnum;
use App\Modules\Post\Exports\PostsExport;
use App\Modules\Post\Imports\PostsImport;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PostService
{
    public function __construct(private MediaService $mediaService) {}

    public function stats(array $filters): array
    {
        $base = Post::filter($filters);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', PostStatusEnum::Published->value)->count(),
            'inactive' => (clone $base)->where('status', '!=', PostStatusEnum::Published->value)->count(),
        ];
    }

    public function index(array $filters, int $limit)
    {
        return Post::with('categories')
            ->filter($filters)
            ->paginate($limit);
    }

    public function show(Post $post): Post
    {
        return $post->load(['categories', 'media']);
    }

    public function store(array $validated, array $images = []): Post
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($validated, $images, &$storedFiles) {
                $data = collect($validated)->except(['images', 'category_ids'])->all();
                $data['organization_id'] = $this->resolveCurrentOrganizationId();
                $post = Post::create($data);

                $this->syncPostCategories($post, $validated);
                $this->savePostAttachments($post, $images, $storedFiles);

                return $post->load(['categories', 'media']);
            });
        } catch (\Throwable $exception) {
            $this->cleanupStoredMediaFiles($storedFiles);
            throw $exception;
        }
    }

    public function update(Post $post, array $validated, array $images = []): Post
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($post, $validated, $images, &$storedFiles) {
                $data = collect($validated)->except(['images', 'remove_attachment_ids', 'category_ids'])->all();
                $post->update($data);

                if (array_key_exists('category_ids', $validated)) {
                    $this->syncPostCategories($post, $validated);
                }

                if (! empty($validated['remove_attachment_ids'])) {
                    $this->mediaService->removeByIds($post, $validated['remove_attachment_ids'], 'post-attachments');
                }

                $this->savePostAttachments($post, $images, $storedFiles);

                return $post->load(['categories', 'media']);
            });
        } catch (\Throwable $exception) {
            $this->cleanupStoredMediaFiles($storedFiles);
            throw $exception;
        }
    }

    public function destroy(Post $post): void
    {
        $post->delete();
    }

    public function bulkDestroy(array $ids): void
    {
        Post::query()
            ->where('organization_id', $this->resolveCurrentOrganizationId())
            ->whereIn('id', $ids)
            ->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status): void
    {
        Post::query()
            ->where('organization_id', $this->resolveCurrentOrganizationId())
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    public function export(array $filters): BinaryFileResponse
    {
        return Excel::download(new PostsExport($filters), 'posts.xlsx');
    }

    public function import($file): void
    {
        Excel::import(new PostsImport($this->resolveCurrentOrganizationId()), $file);
    }

    public function changeStatus(Post $post, string $status): Post
    {
        $post->update(['status' => $status]);

        return $post->load(['categories', 'media']);
    }

    public function incrementView(Post $post): int
    {
        $post->increment('view_count');

        return (int) $post->fresh()->view_count;
    }

    private function syncPostCategories(Post $post, array $validated): void
    {
        $ids = $validated['category_ids'] ?? [];
        $post->categories()->sync($ids);
    }

    private function savePostAttachments(Post $post, array $files, array &$storedFiles): void
    {
        $uploaded = $this->mediaService->uploadMany($post, $files, 'post-attachments', [
            'disk' => 'public',
        ]);

        $storedFiles = array_merge($storedFiles, $uploaded);
    }

    private function cleanupStoredMediaFiles(array $storedFiles): void
    {
        $this->mediaService->cleanupStoredFiles($storedFiles);
    }

    private function resolveCurrentOrganizationId(): int
    {
        $organizationId = function_exists('getPermissionsTeamId') ? getPermissionsTeamId() : null;

        if (! is_numeric($organizationId) || (int) $organizationId <= 0) {
            throw new ModelNotFoundException('Không xác định được tổ chức làm việc hiện tại.');
        }

        return (int) $organizationId;
    }

}
