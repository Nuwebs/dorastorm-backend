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

    case MANAGE_API_KEYS_CREATE = 'manage-api-keys-create';
    case MANAGE_API_KEYS_READ = 'manage-api-keys-read';
    case MANAGE_API_KEYS_UPDATE = 'manage-api-keys-update';
    case MANAGE_API_KEYS_DELETE = 'manage-api-keys-delete';

    case API_KEYS_CREATE = 'api-keys-create';
    case API_KEYS_READ = 'api-keys-read';
    case API_KEYS_UPDATE = 'api-keys-update';
    case API_KEYS_DELETE = 'api-keys-delete';

    /**
     * Place here any other permission
     */
}
