<?php

namespace App\Http\Controllers\Admin\Content;

use Exception;
use Illuminate\Http\Request;
use App\Services\BannerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\General\BannerRequest;
use App\Http\Resources\Admin\Settings\BannerResource;

class BannerController extends Controller
{
    protected $bannerService;
    public function __construct(BannerService $bannerService)
    {
        $this->bannerService = $bannerService;
    }
    public function all()
    {
        $banners = $this->bannerService->all();
        return $this->successResponse(__('fetch_success'), BannerResource::collection($banners)
    );
    }
    public function create(BannerRequest $request)
    {
        try{
            $banner = $this->bannerService->create($request->validated());
            return $this->successResponse(__('create_success'),new BannerResource($banner));
        }catch(Exception $e){
            return $this->exceptionResponse($e);
        }

    }
    public function update(BannerRequest $request,$id)
    {
        try{
            $banner = $this->bannerService->update($request->validated(),$id);
            return $this->successResponse(__('update_success'),new BannerResource($banner));
        }catch(Exception $e){
            return $this->exceptionResponse($e);
        }
    }

    public function delete($id)
    {
        try{
            $this->bannerService->delete($id);
            return $this->successResponse(__('delete_success'));
        }catch(Exception $e){
            return $this->exceptionResponse($e);
        }
    }
}
