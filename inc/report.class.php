<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginMouvementReport {

   /**
    * Retourne les mouvements Lieu / Statut / Utilisateur pour
    * Computer / Printer / Monitor / Peripheral.
    *
    * $params attend :
    *  - date_debut (YYYY-MM-DD)
    *  - date_fin   (YYYY-MM-DD)
    *  - type       ('user'|'inventory'|'location'|'status') OPTIONAL
    *  - user_id    (int) OPTIONAL
    *  - inventory  (string) OPTIONAL (search in serial/otherserial/name)
    *  - location   (int) OPTIONAL (locations.id)
    *  - status     (int) OPTIONAL (states id)
    */
   public static function getReport(array $params = []) {
      global $DB;

      $dateWhere = [];
      if (!empty($params['date_debut'])) {
         $d = $DB->escape($params['date_debut']);
         $dateWhere[] = "l.date_mod >= '{$d} 00:00:00'";
      }
      if (!empty($params['date_fin'])) {
         $d = $DB->escape($params['date_fin']);
         $dateWhere[] = "l.date_mod <= '{$d} 23:59:59'";
      }

      // We'll build UNION ALL over the 4 equipment tables but apply additional
      // filters per-block when the user requested a specific dimension.
      $blocks = [
         'Computer'   => ['table' => 'glpi_computers',   'typecol' => 'computertypes_id',   'modelcol' => 'computermodels_id',   'typetable' => 'glpi_computertypes',   'modeltable' => 'glpi_computermodels'],
         'Printer'    => ['table' => 'glpi_printers',    'typecol' => 'printertypes_id',    'modelcol' => 'printermodels_id',    'typetable' => 'glpi_printertypes',    'modeltable' => 'glpi_printermodels'],
         'Monitor'    => ['table' => 'glpi_monitors',    'typecol' => 'monitortypes_id',    'modelcol' => 'monitormodels_id',    'typetable' => 'glpi_monitortypes',    'modeltable' => 'glpi_monitormodels'],
         'Peripheral' => ['table' => 'glpi_peripherals', 'typecol' => 'peripheraltypes_id', 'modelcol' => 'peripheralmodels_id', 'typetable' => 'glpi_peripheraltypes', 'modeltable' => 'glpi_peripheralmodels'],
      ];

      $unions = [];

      // Prepare sanitized filter values
      $filter_type = $params['type'] ?? '';
      $filter_user = !empty($params['user_id']) ? (int)$params['user_id'] : 0;
      $filter_inv  = isset($params['inventory']) ? $DB->escape($params['inventory']) : '';
      $filter_loc  = !empty($params['location']) ? (int)$params['location'] : 0;
      $filter_st   = !empty($params['status']) ? (int)$params['status'] : 0;

      foreach ($blocks as $itype => $m) {
         // base WHERE for this block: only logs for this item type and movement types
         $where = [];
         $where[] = "l.itemtype = '".$DB->escape($itype)."'";
         $where[] = "l.id_search_option IN (3,31,70)"; // Lieu, Statut, Utilisateur
         if ($dateWhere) {
            $where = array_merge($where, $dateWhere);
         }

         // Apply higher-level filter semantics:
         // - if user asked type=user and provided user_id => match equipments whose current user = user_id
         // - if inventory provided => match serial/otherserial/name
         // - if location provided => match equipment.locations_id = id
         // - if status provided => match equipment.states_id = id
         // If no specific identifier provided, we don't filter equipments (apply to all)
         if ($filter_type === 'user' && $filter_user) {
            // many equipment tables have users_id (older GLPI) — use c.users_id if present.
            // We'll filter on c.users_id where available.
            $where[] = "c.users_id = {$filter_user}";
         } elseif ($filter_type === 'inventory' && $filter_inv !== '') {
            $inv = $filter_inv;
            $where[] = "(c.otherserial LIKE '%{$inv}%' OR c.serial LIKE '%{$inv}%' OR c.name LIKE '%{$inv}%')";
         } elseif ($filter_type === 'location' && $filter_loc) {
            // filter by equipment location (best-effort)
            $where[] = "(c.locations_id = {$filter_loc} OR c.location = {$filter_loc})";
         } elseif ($filter_type === 'status' && $filter_st) {
            // many equipment tables use states_id
            $where[] = "(c.states_id = {$filter_st})";
         }

         $whereSql = implode(' AND ', $where);

         $unions[] = "
            SELECT
               '".$DB->escape($itype)."' AS Type_equipement,
               c.otherserial AS Inventaire,
               c.name AS Nom,
               c.serial AS Serial,
               DATE_FORMAT(l.date_mod, '%Y-%m-%d %H:%i:%s') AS Date_mouvement,
               CASE l.id_search_option
                 WHEN 3  THEN 'Lieu'
                 WHEN 31 THEN 'Statut'
                 WHEN 70 THEN 'Utilisateur'
                 ELSE 'Autre'
               END AS type_mouvement,
               l.old_value AS ancienne_valeur,
               l.new_value AS nouvelle_valeur,
               (SELECT l2.new_value FROM glpi_logs l2
                  WHERE l2.itemtype = '".$DB->escape($itype)."' AND l2.items_id = c.id AND l2.id_search_option = 70 AND l2.date_mod <= l.date_mod
                  ORDER BY l2.date_mod DESC LIMIT 1) AS Utilisateur_a_cet_instant,
               (SELECT l3.new_value FROM glpi_logs l3
                  WHERE l3.itemtype = '".$DB->escape($itype)."' AND l3.items_id = c.id AND l3.id_search_option = 3 AND l3.date_mod <= l.date_mod
                  ORDER BY l3.date_mod DESC LIMIT 1) AS Lieu_a_cet_instant,
               (SELECT l4.new_value FROM glpi_logs l4
                  WHERE l4.itemtype = '".$DB->escape($itype)."' AND l4.items_id = c.id AND l4.id_search_option = 31 AND l4.date_mod <= l.date_mod
                  ORDER BY l4.date_mod DESC LIMIT 1) AS Statut_a_cet_instant,
               l.user_name AS Modificateur
            FROM glpi_logs l
            JOIN ".$m['table']." c ON (l.items_id = c.id)
            LEFT JOIN ".$m['typetable']." ct ON (c.".$m['typecol']." = ct.id)
            LEFT JOIN ".$m['modeltable']." cm ON (c.".$m['modelcol']." = cm.id)
            LEFT JOIN glpi_users u ON (c.users_id = u.id)
            LEFT JOIN glpi_groups g ON (u.groups_id = g.id)
            WHERE {$whereSql}
         ";
      }

      $sql = implode("\nUNION ALL\n", $unions) . "\nORDER BY Date_mouvement DESC\nLIMIT 2000";

      return $DB->query($sql);
   }
}
