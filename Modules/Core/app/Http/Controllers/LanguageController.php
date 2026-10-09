<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Job\Models\Language;
use Modules\Core\Http\Resources\LanguageResource;
use Exception;

class LanguageController extends Controller
{
    /**
     * Display a listing of active languages.
     */
    public function index(Request $request)
    {
        try {
            $languages = Language::active()
                ->orderBy('sort_order', 'asc')
                ->get();

            return $this->successResponse(
                'Languages retrieved successfully',
                LanguageResource::collection($languages)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
