<?php

namespace Airecogn\Integration;

final class AdminMenu
{
    public static function onBuildGlobalMenu(array &$globalMenu, array &$moduleMenu): void
    {
        global $USER;
        if (!is_object($USER) || !$USER->IsAdmin())
        {
            return;
        }

        $moduleMenu[] = [
            'parent_menu' => 'global_menu_services',
            'section' => 'airecogn',
            'sort' => 500,
            'text' => 'AI-распознавание звонков',
            'title' => 'Настройки AI-распознавания звонков',
            'url' => 'settings.php?lang=' . LANGUAGE_ID . '&mid=airecogn&mid_menu=1',
            'icon' => 'sys_menu_icon',
            'page_icon' => 'sys_page_icon',
            'items_id' => 'menu_airecogn',
        ];
    }
}

