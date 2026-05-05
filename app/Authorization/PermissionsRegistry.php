<?php

namespace App\Authorization;

final class PermissionsRegistry
{
    public const PANEL_ACCESS = 'panel.access';

    public const DEVICES_VIEW_ANY = 'devices.view_any';

    public const DEVICES_CREATE = 'devices.create';

    public const DEVICES_UPDATE = 'devices.update';

    public const DEVICES_DELETE = 'devices.delete';

    public const USERS_VIEW_ANY = 'users.view_any';

    public const USERS_CREATE = 'users.create';

    public const USERS_UPDATE = 'users.update';

    public const USERS_DELETE = 'users.delete';

    public const ROLES_MANAGE = 'roles.manage';

    public const SETTINGS_MANAGE = 'settings.manage';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PANEL_ACCESS,
            self::DEVICES_VIEW_ANY,
            self::DEVICES_CREATE,
            self::DEVICES_UPDATE,
            self::DEVICES_DELETE,
            self::USERS_VIEW_ANY,
            self::USERS_CREATE,
            self::USERS_UPDATE,
            self::USERS_DELETE,
            self::ROLES_MANAGE,
            self::SETTINGS_MANAGE,
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function roles(): array
    {
        return [
            'super-admin' => self::all(),
            'manager' => [
                self::PANEL_ACCESS,
                self::DEVICES_VIEW_ANY,
                self::DEVICES_CREATE,
                self::DEVICES_UPDATE,
                self::SETTINGS_MANAGE,
            ],
            'operator' => [
                self::PANEL_ACCESS,
                self::DEVICES_VIEW_ANY,
                self::DEVICES_CREATE,
                self::DEVICES_UPDATE,
            ],
            'viewer' => [
                self::PANEL_ACCESS,
                self::DEVICES_VIEW_ANY,
            ],
        ];
    }
}
