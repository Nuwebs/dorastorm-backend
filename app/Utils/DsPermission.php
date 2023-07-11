<?php
namespace App\Utils;

enum DsPermission: string
{
    case USERS_CREATE = 'users-create';
    case USERS_READ = 'users-read';
    case USERS_UPDATE = 'users-update';
    case USERS_DELETE = 'users-delete';

    case POSTS_CREATE = 'posts-create';
    case POSTS_READ = 'posts-read';
    case POSTS_UPDATE = 'posts-update';
    case POSTS_DELETE = 'posts-delete';

    case ROLES_CREATE = 'roles-create';
    case ROLES_READ = 'roles-read';
    case ROLES_UPDATE = 'roles-update';
    case ROLES_DELETE = 'roles-delete';

    case QUOTATIONS_READ = 'quotations-read';
    case QUOTATIONS_DELETE = 'quotations-delete';

    case PROFILE_READ = 'profile-read';
    case PROFILE_UPDATE = 'profile-update';


    /**
     * Place here any other permission
     */
}
