<?php

namespace App\Modules\Meeting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Core\Resources\PublicOptionResource;
use App\Modules\Document\Requests\ImportCatalogRequest;
use App\Modules\Meeting\Requests\BulkDestroyMeetingCatalogRequest;
use App\Modules\Meeting\Requests\BulkUpdateStatusMeetingCatalogRequest;
use App\Modules\Meeting\Requests\ChangeStatusMeetingCatalogRequest;
use App\Modules\Meeting\Requests\StoreMeetingCatalogRequest;
use App\Modules\Meeting\Requests\UpdateMeetingCatalogRequest;
use App\Modules\Meeting\Resources\MeetingCatalogCollection;
use App\Modules\Meeting\Resources\MeetingCatalogResource;
use App\Modules\Meeting\Services\MeetingCatalogService;
abstract class BaseMeetingCatalogController extends Controller
{
    public function __construct(protected MeetingCatalogService $catalogService) {}

    abstract protected function modelClass(): string;

    abstract protected function successLabel(): string;

    abstract protected function fileName(): string;

    public function public(FilterRequest $request)
    {
        $items = $this->catalogService->publicCatalog($this->modelClass(), $request->all());

        return $this->successCollection(new MeetingCatalogCollection($items));
    }

    public function publicOptions(FilterRequest $request)
    {
        $items = $this->catalogService->publicOptions($this->modelClass(), $request->all());

        return $this->successCollection(PublicOptionResource::collection($items));
    }

    public function stats(FilterRequest $request)
    {
        return $this->success($this->catalogService->stats($this->modelClass(), $request->all()));
    }

    public function index(FilterRequest $request)
    {
        $items = $this->catalogService->index($this->modelClass(), $request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(new MeetingCatalogCollection($items));
    }

    public function show($id)
    {
        $model = $this->catalogService->resolve($this->modelClass(), $id);

        return $this->successResource(new MeetingCatalogResource($this->catalogService->show($model)));
    }

    public function store(StoreMeetingCatalogRequest $request)
    {
        $item = $this->catalogService->store($this->modelClass(), $request->validated());

        return $this->successResource(new MeetingCatalogResource($item), 'Tạo '.$this->successLabel().' thành công!', 201);
    }

    public function update(UpdateMeetingCatalogRequest $request, $id)
    {
        $model = $this->catalogService->resolve($this->modelClass(), $id);
        $item = $this->catalogService->update($model, $request->validated());

        return $this->successResource(new MeetingCatalogResource($item), 'Cập nhật '.$this->successLabel().' thành công!');
    }

    public function destroy($id)
    {
        $model = $this->catalogService->resolve($this->modelClass(), $id);
        $this->catalogService->destroy($model);

        return $this->success(null, 'Xóa '.$this->successLabel().' thành công!');
    }

    public function bulkDestroy(BulkDestroyMeetingCatalogRequest $request)
    {
        $this->catalogService->bulkDestroy($this->modelClass(), $request->ids);

        return $this->success(null, 'Xóa hàng loạt thành công!');
    }

    public function bulkUpdateStatus(BulkUpdateStatusMeetingCatalogRequest $request)
    {
        $this->catalogService->bulkUpdateStatus($this->modelClass(), $request->ids, $request->status);

        return $this->success(null, 'Cập nhật trạng thái hàng loạt thành công!');
    }

    public function changeStatus(ChangeStatusMeetingCatalogRequest $request, $id)
    {
        $model = $this->catalogService->resolve($this->modelClass(), $id);
        $item = $this->catalogService->changeStatus($model, $request->status);

        return $this->successResource(new MeetingCatalogResource($item), 'Đổi trạng thái thành công!');
    }

    public function export(FilterRequest $request)
    {
        return $this->catalogService->export($this->modelClass(), $request->all(), $this->fileName());
    }

    public function import(ImportCatalogRequest $request)
    {
        $this->catalogService->import($this->modelClass(), $request->file('file'));

        return $this->success(null, 'Import dữ liệu thành công.');
    }
}
