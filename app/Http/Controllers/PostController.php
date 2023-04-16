<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    private array $validationRules = [
        'title' => 'required|string|min:5|max:190',
        'description' => 'required|string|min:5|max:300',
        'content' => 'required|string',
        'banner' => 'nullable|string|max:191',
        'tags' => 'nullable|array',
        'visible' => 'boolean',
        'private' => 'boolean'
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create', Post::class))
            abort(403);
        $data = $request->validate($this->validationRules);

        $newPost = Post::make($data);
        $newPost->private = $data['private'] ?? false;
        $newPost->user_id = $request->user()->id;
        $newPost->save();

        if ($request->filled('tags')) {
            $newPost->attachTags($data['tags']);
        }

        return response('', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        return new PostResource(Post::getPostWithChecks($slug));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $post = Post::findOrFail($id);
        if (!$request->user()->can('delete', $post))
            abort(403);
        $post->delete();
    }
}