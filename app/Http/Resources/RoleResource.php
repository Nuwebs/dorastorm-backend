<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rolePermissions = [];
        foreach ($this->permissions as $permission){
            array_push($rolePermissions, $permission->name);
        }
        return [
            'id' => $this->id,
            'hierarchy' => $this->hierarchy,
            'name' => $this->display_name,
            'description' => $this->description,
            'permissions' => $rolePermissions,
            'created_at' => $this->created_at,
            'modified_at' => $this->updated_at,
        ];
    }
}
