<?php

namespace App\Rules;

use App\Models\Role;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserRoleRule implements ValidationRule
{
    protected $userRole;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Role $userRole)
    {
        $this->userRole = $userRole;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $role = Role::find($value);
        if ($this->userRole->id === $role->id)
            return;
        if (!(($role->hierarchy > $this->userRole->hierarchy) || $this->userRole->hierarchy === 0)) {
            $fail('The :attribute have a higher hierarchy than the allowed.');
        }
    }
}
