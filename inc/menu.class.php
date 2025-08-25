<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMouvementsMenu extends CommonGLPI {

   static function getMenuName() {
      return __('Mouvements équipements', 'Mouvements');
   }

   static function getMenuContent() {
      $menu = [];

      $menu['title'] = self::getMenuName();
      $menu['page']  = '/plugins/Mouvements/front/report.php';
      $menu['icon']  = 'ti ti-arrows-exchange'; // icône si dispo

      return $menu;
   }
}
