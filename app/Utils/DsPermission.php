<?php
namespace App\Utils;

enum DsPermission: string
{
    case USERS_CREATE = 'users-create';
    case USERS_READ = 'users-read';
    case USERS_UPDATE = 'users-update';
    case USERS_DELETE = 'users-delete';

    case ROLES_CREATE = 'roles-create';
    case ROLES_READ = 'roles-read';
    case ROLES_UPDATE = 'roles-update';
    case ROLES_DELETE = 'roles-delete';

    /**
     * Place here any other permission
     */
}
