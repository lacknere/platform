/**
 * @sw-package fundamentals@framework
 */
Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'settings',
    key: 'users_and_permissions',
    roles: {
        viewer: {
            privileges: [
                'user:read',
                'acl_role:read',
                'user_access_key:read',
                'media_default_folder:read',
                'app:read',
                'user_config:read',
                'user_config:create',
                'user_config:update',
                'currency:read',
                'system_config:read',
            ],
            dependencies: [],
        },
        editor: {
            privileges: [
                'user:update',
                'acl_role:update',
                'user_access_key:create',
                'user_access_key:update',
                'user_access_key:delete',
                'system_config:read',
                'system_config:create',
                'system_config:update',
                'system_config:delete',
            ],
            dependencies: [
                'users_and_permissions.viewer',
            ],
        },
        creator: {
            privileges: [
                'user:create',
                'acl_role:create',
            ],
            dependencies: [
                'users_and_permissions.viewer',
                'users_and_permissions.editor',
            ],
        },
        deleter: {
            privileges: [
                'user:delete',
                'acl_role:delete',
            ],
            dependencies: [
                'users_and_permissions.viewer',
            ],
        },
    },
});
