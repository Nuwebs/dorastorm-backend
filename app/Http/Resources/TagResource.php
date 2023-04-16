<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    /**
     * Returns only the name of the tag. If you need to return more data about the tag
     * you may change the return type to an array with the information you need.
     *
     * @return string
     */
    public function toArray(Request $request): string
    {
        return $this->name;
    }
}
