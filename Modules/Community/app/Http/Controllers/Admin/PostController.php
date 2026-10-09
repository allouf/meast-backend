<?php

namespace Modules\Community\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\BaseResponse;
use Exception;
use Illuminate\Http\Request;
use Modules\Community\Http\Requests\Admin\PostRequest;
use Modules\Community\Http\Resources\Admin\PostDetailsResource;
use Modules\Community\Http\Resources\Admin\PostResource;
use Modules\Community\Services\PostService;

class PostController extends Controller
{
    use BaseResponse;

    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Get all posts with admin filters
     */
    public function all()
    {
        $posts = $this->postService->getAllPosts();

        return $this->successResponse(
            __('fetch_success'),
            [
                'posts' => PostResource::collection($posts['data']),
                'meta' => $posts['meta']
            ]
        );
    }

    /**
     * Get post by ID
     */
    public function view($id)
    {
        $post = $this->postService->getPostById($id);

        if (!$post) {
            return $this->errorResponse(__('not_found'), 404);
        }

        return $this->successResponse(
            __('fetch_success'),
            new PostDetailsResource($post)
        );
    }
    public function update(PostRequest $request, $id)
    {
        $post = $this->postService->updatePost($id, $request->validated());

        if (!$post) {
            return $this->errorResponse(__('not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(__('update_success'), new PostResource($post));
    }

    public function delete($id)
    {
        $post = $this->postService->getPostById($id);

        if (!$post) {
            return $this->errorResponse(__('not_found'), 404);
        }

        $deleted = $this->postService->deletePost($id);

        return $this->successResponse(
            __('delete_success'),
            null
        );
    }

    /**
     * Toggle post status
     */
    public function toggleStatus($id)
    {
        $post = $this->postService->getPostById($id);

        if (!$post) {
            return $this->errorResponse(__('not_found'), 404);
        }

        $newStatus = $post->status === 'active' ? 'inactive' : 'active';
        $this->postService->updatePost($id, ['status' => $newStatus]);

        return $this->successResponse(__('update_success'));
    }


    public function toggleFeatured($id)
    {
        $post = $this->postService->getPostById($id);

        if (!$post) {
            return $this->errorResponse(__('not_found'), 404);
        }

        $newFeatured = !$post->is_featured;
        $this->postService->updatePost($id, ['is_featured' => $newFeatured]);

        return $this->successResponse(__('update_success'));
    }

    public function filter(Request $request)
    {
        try {
            $posts = $this->postService->filter($request);
            return $this->successResponse(__('fetch_success'), [
                'posts' => PostResource::collection($posts['data']),
                'meta' => $posts['meta'],
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
