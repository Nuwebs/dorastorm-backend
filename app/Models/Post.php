<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Tags\HasTags;

class Post extends Model
{
    use HasFactory;
    use HasTags;
    use Sluggable;
    use SluggableScopeHelpers;

    protected $guarded = [
        'private'
    ];

    protected $casts = [
        'visible' => 'boolean',
        'private' => 'boolean'
    ];


    /**
     * @return BelongsTo<User, Post>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'id' => 'nan',
            'name' => 'ANON',
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    /**
     * Returns a Post model searching by its slug. Returns 404 if not found.
     *
     */
    public static function getPostWithChecks(string $slug): Post
    {
        $post = static::findBySlugOrFail($slug);

        if (!($post instanceof Post)) {
            abort(409, 'The slug is not unique.');
        }

        // The "visible" property is colliding with the visible model array
        $visible = (bool) $post->getAttribute('visible');
        if ($visible && !$post->private) {
            return $post;
        }
        $auth = Auth::check();
        // If the post is visible and it is private, the user must be authenticated
        if ($visible && $post->private && $auth) {
            return $post;
        }
        // If the post is not visible the user should not see it unless he is the owner of the post or have permissions of updating
        if (!$visible && $auth && Gate::allows('update', $post)) {
            return $post;
        }
        abort(403);
    }

}
