<?php

class PluginMouvementsMenu extends CommonGLPI {

    static function getMenuName() {
        return __('Mouvements', 'mouvements');
    }

    static function getMenuContent() {
        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page']  = '/plugins/mouvements/front/reporting.php';  
        $menu['icon']  = 'fas fa-arrows-alt-h'; // Icône pour les mouvements
        
        return $menu;
    }
}
