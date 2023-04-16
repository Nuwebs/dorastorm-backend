<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * The index method could receive query params. Those params are the following:
     * 
     * q: Use it for a simple search. The content inside that param will be used with a 
     * LIKE sql function for the title or content of the available posts.
     * 
     * mine: Will only show the current logged in user posts. (Ignoring private or visible)
     * 
     * p: Will show all private and visible posts avaiable if the user have the rights to see them.
     * 
     * t: Will look for posts containing, at least, one of the tags specified.
     * The format should be t=tag1,tag2,tag3, ..., tagn
     * 
     * e: Use it to exclude results. Works as the opposite of the q param.
     * 
     * You could use a combination of the params using the & operator in a single query. For example:
     * /posts?q=pitbull&t=dogs,products&e=cats
     * That query will look for posts that contain "pitbull" in the title or content, with
     * "dogs" or "products" tags, and excluding anything related to "cats".
     * 
     * return PostResource collection paginated.
     */
    public function index(Request $request)
    {
        // Check if the user is trying to get their own posts
        if ($request->filled('mine')) {
            if (!Auth::check()) {
                abort(401);
            }
            if (!$request->user()->can('create', Post::class)) {
                abort(403);
            }
            $query = Post::where('user_id', $request->user()->id);
            return $this->executeIndexQuery($request, $query);
        }

        // The index will always show visible posts
        $query = Post::where('visible', 1);
        // If the request has the p field filled, the index will only show visible and private posts
        $private = 0;
        if ($request->filled('p')) {
            if (!Auth::check()) {
                abort(401);
            }
            $private = 1;
        }
        $query->where('private', $private);
        return $this->executeIndexQuery($request, $query);
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

    private function executeIndexQuery(Request $request, $query)
    {
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($query) use ($q) {
                $query->where('title', 'LIKE', "%$q%")
                    ->orWhere('content', 'LIKE', "%$q%");
            });
        }
        if ($request->filled('t')) {
            $t = $request->input('t');
            if (strlen($t) > 0) {
                $tags = explode(',', $t);
                $query->withAnyTags($tags);
            }
        }
        if ($request->filled('e')) {
            $e = $request->input('e');
            if (strlen($e) > 0) {
                $exclude = str_replace(',', '|', $e);
                $query->where(function ($query) use ($exclude) {
                    $query->where('title', 'NOT RLIKE', "$exclude")
                        ->where('content', 'NOT RLIKE', "$exclude");
                });
            }
        }
        return PostResource::collection($query->orderBy('created_at', 'desc')->with(['user', 'tags'])->paginate(15));
    }
}