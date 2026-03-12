<?php

namespace App\Support\Permissions;

class SettingPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'setting.page', 'group' => 'setting', 'label' => 'Access Settings page', 'type' => 'page'],
    ];

    /**
     * @return array<int, array{name:string,group:string,label:string,type:string}>
     */
    public static function allPermissionDefinitions(): array
    {
        return self::PAGE_PERMISSIONS;
    }
}
