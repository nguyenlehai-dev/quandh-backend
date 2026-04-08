<?php

namespace App\Modules\Meeting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Requests\BulkDestroyRequest;
use App\Modules\Meeting\Requests\BulkUpdateCatalogStatusRequest;
use App\Modules\Meeting\Requests\ChangeCatalogStatusRequest;
use App\Modules\Meeting\Requests\StoreCatalogRequest;
use App\Modules\Meeting\Requests\UpdateCatalogRequest;
use App\Modules\Meeting\Resources\CatalogCollection;
use App\Modules\Meeting\Resources\CatalogResource;
use App\Modules\Meeting\Services\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private CatalogService $catalogService) {}

    public function publicList(Request $request)
    {
        return $this->success($this->catalogService->publicList($this->resource($request), $request->all()));
    }

    public function publicOptions(Request $request)
    {
        return $this->success($this->catalogService->publicOptions($this->resource($request), $request->all()));
    }

    public function stats(Request $request)
    {
        return $this->success($this->catalogService->stats($this->resource($request), $request->all()));
    }

    public function index(Request $request)
    {
        $items = $this->catalogService->index($this->resource($request), $request->all(), (int) $request->input('limit', 10));

        return $this->successCollection(new CatalogCollection($items));
    }

    public function show(Request $request, int $id)
    {
        return $this->successResource(new CatalogResource($this->catalogService->show($this->resource($request), $id)));
    }

    public function store(StoreCatalogRequest $request)
    {
        $resource = $this->resource($request);
        $validated = $request->validated();
        $members = $validated['members'] ?? [];
        unset($validated['members']);

        $item = $this->catalogService->store($resource, $validated, $members);

        return $this->successResource(new CatalogResource($item), 'Tạo '.$this->catalogService->label($resource).' thành công!', 201);
    }

    public function update(UpdateCatalogRequest $request, int $id)
    {
        $resource = $this->resource($request);
        $validated = $request->validated();
        $members = array_key_exists('members', $validated) ? $validated['members'] : null;
        unset($validated['members']);

        $item = $this->catalogService->update($resource, $id, $validated, $members);

        return $this->successResource(new CatalogResource($item), 'Cập nhật '.$this->catalogService->label($resource).' thành công!');
    }

    public function destroy(Request $request, int $id)
    {
        $resource = $this->resource($request);
        $this->catalogService->destroy($resource, $id);

        return $this->success(null, 'Xóa '.$this->catalogService->label($resource).' thành công!');
    }

    public function bulkDestroy(BulkDestroyRequest $request)
    {
        $this->catalogService->bulkDestroy($this->resource($request), $request->validated('ids'));

        return $this->success(null, 'Xóa hàng loạt thành công!');
    }

    public function bulkUpdateStatus(BulkUpdateCatalogStatusRequest $request)
    {
        $this->catalogService->bulkUpdateStatus($this->resource($request), $request->validated('ids'), $request->validated('status'));

        return $this->success(null, 'Cập nhật trạng thái hàng loạt thành công!');
    }

    public function changeStatus(ChangeCatalogStatusRequest $request, int $id)
    {
        $item = $this->catalogService->changeStatus($this->resource($request), $id, $request->validated('status'));

        return $this->successResource(new CatalogResource($item), 'Đổi trạng thái thành công!');
    }

    protected function resource(Request $request): string
    {
        return (string) $request->route('resource');
    }
}
