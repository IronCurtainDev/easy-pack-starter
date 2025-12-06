<?php

namespace EasyPack\Models;

use Illuminate\Database\Eloquent\Model;

class PageContent extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'page_contents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'title',
        'content',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get page content by slug.
     *
     * @param string $slug
     * @return static|null
     */
    public static function getBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get formatted content (can be extended for additional processing).
     *
     * @return string
     */
    public function getFormattedContentAttribute(): string
    {
        return $this->content;
    }
}
