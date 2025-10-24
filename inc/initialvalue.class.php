<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginMouvementsInitialValue extends CommonDBTM {

   static $table = 'glpi_plugin_mouvements_initialvalues';

   static function saveInitialValue($itemtype, $items_id, $field, $value) {
      global $DB;

      $crit = [
         'itemtype' => $itemtype,
         'items_id' => $items_id,
         'field'    => $field
      ];

      $res = $DB->request([
         'FROM'  => self::$table,
         'WHERE' => $crit
      ]);

      if (count($res) === 0) {
         $DB->insertOrDie(self::$table, [
            'itemtype'      => $itemtype,
            'items_id'      => $items_id,
            'field'         => $field,
            'initial_value' => $value
         ]);
      }
   }

   static function getInitialValue($itemtype, $items_id, $field) {
      global $DB;

      $res = $DB->request([
         'SELECT' => ['initial_value'],
         'FROM'   => self::$table,
         'WHERE'  => [
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'field'    => $field
         ]
      ])->current();

      return $res ? $res['initial_value'] : null;
   }
}
