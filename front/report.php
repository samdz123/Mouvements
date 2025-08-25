<?php
include ('../../../inc/includes.php');

Session::checkRight("config", READ);

global $DB;

echo '<h2>Mouvements équipements</h2>';

$sql = "
   SELECT l.itemtype, l.items_id, l.date_mod, l.id_search_option, l.old_value, l.new_value, l.user_name
   FROM glpi_logs l
   WHERE l.itemtype IN ('Computer','Printer','Monitor','Peripheral')
     AND l.id_search_option IN (3,31,70,76670)
   ORDER BY l.date_mod DESC
   LIMIT 200
";
$res = $DB->query($sql);

echo '<table class="tab_cadre_fixehov">';
echo '<tr>'
   . '<th>Type</th>'
   . '<th>ID</th>'
   . '<th>Date</th>'
   . '<th>Mouvement</th>'
   . '<th>Ancienne valeur</th>'
   . '<th>Nouvelle valeur</th>'
   . '<th>Modificateur</th>'
   . '</tr>';

if ($DB->numrows($res) == 0) {
   echo '<tr><td colspan="7"><i>Aucun mouvement enregistré</i></td></tr>';
} else {
   while ($row = $DB->fetchAssoc($res)) {
      echo '<tr>'
         . '<td>' . htmlspecialchars($row['itemtype']) . '</td>'
         . '<td>' . (int)$row['items_id'] . '</td>'
         . '<td>' . htmlspecialchars($row['date_mod']) . '</td>'
         . '<td>' . htmlspecialchars(\PluginMouvements\Mouvement::labelForOption($row['id_search_option'])) . '</td>'
         . '<td>' . htmlspecialchars($row['old_value'] ?? '') . '</td>'
         . '<td>' . htmlspecialchars($row['new_value'] ?? '') . '</td>'
         . '<td>' . htmlspecialchars($row['user_name'] ?? '') . '</td>'
         . '</tr>';
   }
}
echo '</table>';
