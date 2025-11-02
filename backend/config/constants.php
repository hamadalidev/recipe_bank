<?php

return [
    'permissions' => [
        'admin' => [
            'list-recipes',
            'add-recipe',
            'edit-recipe',
            'delete-recipe',
            'manage-users',
            'manage-cuisine-types',
        ],
        'sub-admin' => [
            'list-recipes',
        ],
        'owner' => [
            'list-recipes',
            'add-recipe',
            'list-recipes',
            'add-recipe',
            'edit-recipe',
            'delete-recipe',
        ],
    ],
];