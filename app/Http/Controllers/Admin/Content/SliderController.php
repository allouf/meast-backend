<?php

namespace App\Http\Controllers\Admin\Content;

use Exception;
use Illuminate\Http\Request;
use App\Services\SliderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\General\SliderRequest;
use App\Http\Requests\Admin\General\UpdateSliderRequest;
use App\Http\Resources\Admin\Settings\SliderResource;

class SliderController extends Controller
{
    protected $sliderService;
    public function __construct(SliderService $sliderService)
    {
        $this->sliderService = $sliderService;
    }
    public function all()
    {
        $sliders = $this->sliderService->all();
        return $this->successResponse(__('fetch_success'),SliderResource::collection($sliders)
        );
    }
    public function slidersByCategory($categoyId)
    {
        $sliders = $this->sliderService->getByCategory($categoyId);
        return $this->successResponse(__('fetch_success'),SliderResource::collection($sliders)
        );
    }

    public function create(SliderRequest $request)
    {
        try {
            $slider = $this->sliderService->create($request->validated());
            return $this->successResponse(__('create_success'),new SliderResource($slider));

        } catch (Exception $e){
            return $this->exceptionResponse($e);
        }
    }
    public function update(UpdateSliderRequest $request,$id)
    {
        try {
            $slider = $this->sliderService->update($request->validated(),$id);
            return $this->successResponse(__('update_success'),new SliderResource($slider));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
     }
     public function delete($id)
     {
        try {
            $this->sliderService->delete($id);
            return $this->successResponse(__('delete_success'));
        } catch (Exception $e){
            return $this->exceptionResponse($e);
        }
     }
}
