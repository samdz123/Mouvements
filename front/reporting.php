<?php

/*Plugin Mouvements for GLPI
Copyright (C) 2025 Saad Meslem

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

include ('../../../inc/includes.php');
use Glpi\DBAL\QueryExpression;
Html::header(__('Mouvements', 'mouvements'), $_SERVER['PHP_SELF'], "tools", "PluginMouvementReport");

// Préparer les listes pour les dropdowns
global $DB;

// Utilisateurs
$users = [];
foreach ($DB->request([
    'FROM'   => 'glpi_users',
    'FIELDS' => ['id','name'],
    'ORDER'  => 'name'
]) as $row) {
    $users[(int)$row['id']] = $row['name'];
}

// Lieux
$locations = [];
foreach ($DB->request([
    'FROM'   => 'glpi_locations',
    'FIELDS' => ['id','name'],
    'ORDER'  => 'name'
]) as $row) {
    $locations[$row['id']] = $row['name'];
}

// Statuts
$states = [];
foreach ($DB->request([
    'FROM'   => 'glpi_states',
    'FIELDS' => ['id','name'],
    'ORDER'  => 'name'
]) as $row) {
    $states[(int)$row['id']] = $row['name'];
}

// Inventaire (otherserial)
$inventories = [];

if (class_exists('\\Glpi\\DBAL\\QueryExpression')) {
	//glpi 11 
// GLPI 11
   $unionSQL = "
      SELECT otherserial AS inv FROM glpi_computers   WHERE otherserial != ''
      UNION
      SELECT otherserial AS inv FROM glpi_monitors    WHERE otherserial != ''
      UNION
      SELECT otherserial AS inv FROM glpi_printers    WHERE otherserial != ''
      UNION
      SELECT otherserial AS inv FROM glpi_peripherals WHERE otherserial != ''
   ";

   $req = [
      'SELECT' => ['t.inv'],
      'FROM'   => new \Glpi\DBAL\QueryExpression("($unionSQL) AS t"),
      'ORDER'  => ['t.inv ASC']
   ];

   foreach ($DB->request($req) as $row) {
      $inventories[$row['inv']] = $row['inv'];
   }
}else{
/////////glpi 10 
$sql = "
   SELECT otherserial AS inv FROM glpi_computers   WHERE otherserial != ''
   UNION
   SELECT otherserial AS inv FROM glpi_monitors    WHERE otherserial != ''
   UNION
   SELECT otherserial AS inv FROM glpi_printers    WHERE otherserial != ''
   UNION
   SELECT otherserial AS inv FROM glpi_peripherals WHERE otherserial != ''
   ORDER BY inv ASC
";

$res = $DB->query($sql);
while ($row = $DB->fetchAssoc($res)) {
   $inventories[$row['inv']] = $row['inv'];
}
}
//////////////

// Utilitaires d'affichage
function mov_h($s): string {
    $v = (string)($s ?? '');

    // Décoder les entités HTML stockées en DB
    $v = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Nettoyage des balises
    $v = Toolbox::stripTags($v);

    // Supprimer les (nombre) en fin de chaîne
    while (preg_match('/[\s\x{00A0}]*\(\d+\)[\s\x{00A0}]*$/u', $v)) {
        $v = preg_replace('/[\s\x{00A0}]*\(\d+\)[\s\x{00A0}]*$/u', '', $v);
    }

    // Trim espaces normaux et insécables
    $v = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $v);

    // Encodage final sécurisé pour l’affichage
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Formulaire
echo "<div class='center'>";
echo "<form method='GET' action=''>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __('Filtres des mouvements', 'mouvements') . "</th></tr>";

// ---- Dates par défaut : les 3 derniers mois ----
$default_start = date('Y-m-d', strtotime('-3 months'));
$default_end   = date('Y-m-d');

$date_debut = $_GET['date_debut'] ?? $default_start;
$date_fin   = $_GET['date_fin'] ?? $default_end;

// date debut
echo "<tr class='tab_bg_1'><td>" . __('Date début', 'mouvements') . "</td>";
echo "<td><input type='date' name='date_debut' value='" . mov_h($date_debut) . "'></td></tr>";

// date fin
echo "<tr class='tab_bg_1'><td>" . __('Date fin', 'mouvements') . "</td>";
echo "<td><input type='date' name='date_fin' value='" . mov_h($date_fin) . "'></td></tr>";

// type
$types = [
   '' => __('Tous les types', 'mouvements'),
   'user'      => __('Par utilisateur', 'mouvements'),
   'inventory' => __('Par inventaire', 'mouvements'),
   'location'  => __('Par lieu', 'mouvements'),
   'status'    => __('Par statut', 'mouvements')
];
echo "<tr class='tab_bg_1'><td>" . __('Clé de mouvement', 'mouvements') . "</td><td>";
Dropdown::showFromArray('type', $types, ['value' => $_GET['type'] ?? '']);
echo "</td></tr>";

// champs dynamiques
if (!empty($_GET['type'])) {
    // Utilisateur
if ($_GET['type'] == 'user') {
   echo "<tr class='tab_bg_1'><td>" . __('Utilisateur', 'mouvements') . "</td><td>";
   Dropdown::showFromArray('user_id', $users, [
      'value' => $_GET['user_id'] ?? 0,
      'display_emptychoice' => true
   ]);
   echo "</td></tr>";
}

// Lieu
if ($_GET['type'] == 'location') {
   echo "<tr class='tab_bg_1'><td>" . __('Lieu', 'Mouvements') . "</td><td>";
   Dropdown::showFromArray('location', $locations, [
      'value' => $_GET['location'] ?? 0,
      'display_emptychoice' => true
   ]);
   echo "</td></tr>";
}

// Statut
if ($_GET['type'] == 'status') {
   echo "<tr class='tab_bg_1'><td>" . __('Statut', 'mouvements') . "</td><td>";
   Dropdown::showFromArray('status', $states, [
      'value' => $_GET['status'] ?? 0,
      'display_emptychoice' => true
   ]);
   echo "</td></tr>";
}

// Inventaire
if ($_GET['type'] == 'inventory') {
   echo "<tr class='tab_bg_1'><td>" . __('Numéro inventaire', 'mouvements') . "</td><td>";
   Dropdown::showFromArray('inventory', $inventories, [
      'value' => $_GET['inventory'] ?? 0,
      'display_emptychoice' => true
   ]);
   echo "</td></tr>";
}
}

echo "<tr class='tab_bg_2 center'><td colspan='2'>";
echo "<input type='submit' class='submit' value='" . __('Générer', 'mouvements') . "'>";
echo "</td></tr>";

echo "</table>";
echo "</form>";
echo "</div>";

// Si formulaire soumis (ou si l'utilisateur veut tout voir, on peut aussi n'exiger rien)
$params = $_GET ?? [];


// ---- Dates par défaut : les 3 derniers mois ----
$default_start = date('Y-m-d', strtotime('-3 months'));
$default_end   = date('Y-m-d');

// Si aucune date n'est passée, utiliser les valeurs par défaut
$params['date_debut'] = $params['date_debut'] ?? $default_start;
$params['date_fin']   = $params['date_fin'] ?? $default_end;

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
	  
	    // Récupérer le libellé du statut sélectionné (pour comparaison dans les logs)
		// $states est déjà construit plus haut (id => name)
		$filterStateName = '';
		if ($filter_st && isset($states[$filter_st])) {
		$filterStateName = $states[$filter_st];
		}
		// Récupérer le libellé du lieu sélectionné (pour comparaison dans les logs)
		// $location est déjà construit plus haut (id => name)
		$filterLocName = '';
		if ($filter_loc && isset($locations[$filter_loc])) {
		$filterLocName = $locations[$filter_loc];
		}


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
            $where[] = "(
		(l.id_search_option = 70 AND (l.new_value LIKE '%{$filter_user}%' OR l.old_value LIKE '%{$filter_user}%'))
		)";
         } elseif ($filter_type === 'inventory' && $filter_inv !== '') {
            $inv = $filter_inv;
            //$where[] = "(c.otherserial LIKE '%{$inv}%')";
			$where[] = "(c.otherserial = '{$inv}')";
			
         } elseif ($filter_type === 'location' && $filter_loc) {
			$where[] = 	"(l.id_search_option = 3 AND (l.new_value LIKE '%{$filter_loc}%' OR l.old_value LIKE '%{$filter_loc}%'))";
	
		 } elseif($filter_type === 'status' && $filter_st) {
		
		$where[] = 	"(l.id_search_option = 31 AND (l.new_value LIKE '%{$filter_st}%' OR l.old_value LIKE '%{$filter_st}%'))";
		}

         $whereSql = implode(' AND ', $where);


//////////////

if (class_exists('\\Glpi\\DBAL\\QueryExpression')) {
	//glpi 11 
   $req = [
    'SELECT' => [
        new QueryExpression("'".$DB->escape($itype)."' AS Type_equipement"),
        'c.otherserial AS Inventaire',
        'c.serial AS Serial',
        'c.name AS Nom',
        'l.date_mod AS Date_modif',
        new QueryExpression("DATE_FORMAT(l.date_mod, '%d/%m/%Y %H:%i') AS Date_mouvement"),
        'l.id_search_option AS type_mouvement_code',
        'l.old_value AS ancienne_valeur',
        'l.new_value AS nouvelle_valeur',
        'l.user_name AS Modificateur',
		new QueryExpression("COALESCE(
   (SELECT l2.new_value FROM glpi_logs l2
      WHERE l2.itemtype = '".$DB->escape($itype)."'
        AND l2.items_id = c.id
        AND l2.id_search_option = 70
        AND l2.date_mod <= l.date_mod
      ORDER BY l2.date_mod DESC LIMIT 1),
   (SELECT iv.initial_value FROM glpi_plugin_mouvements_initialvalues iv
      WHERE iv.itemtype = '".$DB->escape($itype)."'
        AND iv.items_id = c.id
        AND iv.field = 'user'
      LIMIT 1),
   u.name
) AS Utilisateur_a_cet_instant"),

new QueryExpression("COALESCE(
   (SELECT l3.new_value FROM glpi_logs l3
      WHERE l3.itemtype = '".$DB->escape($itype)."'
        AND l3.items_id = c.id
        AND l3.id_search_option = 3
        AND l3.date_mod <= l.date_mod
      ORDER BY l3.date_mod DESC LIMIT 1),
   (SELECT iv.initial_value FROM glpi_plugin_mouvements_initialvalues iv
      WHERE iv.itemtype = '".$DB->escape($itype)."'
        AND iv.items_id = c.id
        AND iv.field = 'location'
      LIMIT 1),
   loc.name
) AS Lieu_a_cet_instant"),

new QueryExpression("COALESCE(
   (SELECT l4.new_value FROM glpi_logs l4
      WHERE l4.itemtype = '".$DB->escape($itype)."'
        AND l4.items_id = c.id
        AND l4.id_search_option = 31
        AND l4.date_mod <= l.date_mod
      ORDER BY l4.date_mod DESC LIMIT 1),
   (SELECT iv.initial_value FROM glpi_plugin_mouvements_initialvalues iv
      WHERE iv.itemtype = '".$DB->escape($itype)."'
        AND iv.items_id = c.id
        AND iv.field = 'status'
      LIMIT 1),
   st.name
) AS Statut_a_cet_instant"),
    ],
    'FROM'  => 'glpi_logs AS l',
    'JOIN'  => [
        $m['table'].' AS c' => ['FKEY' => ['l'=>'items_id','c'=>'id']],
        $m['typetable'].' AS ct' => ['FKEY' => ['c' => $m['typecol'], 'ct' => 'id']],
        $m['modeltable'].' AS cm'=> ['FKEY' => ['c' => $m['modelcol'], 'cm' => 'id']],
    ],
    'LEFT JOIN' => [
        'glpi_users AS u'  => ['FKEY' => ['c' => 'users_id', 'u' => 'id']],
        'glpi_groups AS g' => ['FKEY' => ['u' => 'groups_id', 'g' => 'id']],
		'glpi_locations AS loc' => ['FKEY' => ['c' => 'locations_id', 'loc' => 'id']],
		'glpi_states AS st' => ['FKEY' => ['c' => 'states_id', 'st' => 'id']],
    ],
	'WHERE' => [ new QueryExpression($whereSql) ],
    'ORDER' => ['l.date_mod DESC']
];
} else {
   // GLPI 10
   $req = "
            SELECT
               '".$DB->escape($itype)."' AS Type_equipement,
               c.otherserial AS Inventaire,
			   c.serial As Serial,
               c.name AS Nom,
               c.serial AS Serial,
			   l.date_mod AS Date_modif,
               (DATE_FORMAT(l.date_mod, '%d/%m/%Y %H:%i')) AS Date_mouvement,
               CASE l.id_search_option
                 WHEN 3  THEN 'Lieu'
                 WHEN 31 THEN 'Statut'
                 WHEN 70 THEN 'Utilisateur'
                 ELSE 'Autre'
               END AS type_mouvement,
			   l.id_search_option AS type_mouvement_code,
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
			ORDER by l.date_mod DESC
         ";
      
}

foreach ($DB->request($req) as $row) {
    $results[] = $row;
}
		
		}

// Debug : voir si la requête renvoie quelque chose
if (empty($results)) {
	
   echo '<div class="m-2">' . __('Aucun mouvement trouvé dans la base','mouvements') . '</div>';
   Html::footer();
   exit;
}
echo "<hr style='margin:5px 0; border:0; border-top:1px solid #000;'>";
// Tableau de résultats
echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">';
echo '  <div style="display:flex; align-items:center; gap:10px;">';
echo '    <label for="tableFilter" style="font-weight:bold;">' . __('Filtrer le tableau :','mouvements') . '</label>';
echo '    <input type="text" id="tableFilter" placeholder="' . __('Tapez pour filtrer...','mouvements') . '" style="max-width:200px;">';
echo '  </div>';
echo '  <button id="exportExcel" class="vsubmit">' . __('Exporter vers Excel','mouvements') . '</button>';
echo '</div>';

echo '<div class="spaced">';
echo '<table id="MouvementsTable" class="tab_cadre_fixehov">';
echo '<tr>'
   . '<th>' . __('Type équipement','mouvements') . '</th>'
   . '<th>' . __('N°Inv','mouvements') . '</th>'
   . '<th>' . __('N°S','mouvements') . '</th>'
   . '<th>' . __('Nom','mouvements') . '</th>'
   . '<th>' . __('Type mouvement','mouvements') . '</th>'
   . '<th>' . __('Date','mouvements') . '</th>'
   . '<th>' . __('Ancienne valeur','mouvements') . '</th>'
   . '<th>' . __('Nouvelle valeur','mouvements') . '</th>'
   . '<th>' . __('Utilisateur@instant','mouvements') . '</th>'
   . '<th>' . __('Lieu@instant','mouvements') . '</th>'
   . '<th>' . __('Statut@instant','mouvements') . '</th>'
   . '<th>' . __('Modificateur','mouvements') . '</th>'
   . '</tr>';

foreach ($results as $row) {
	$typeMap = [
   3  => __('Lieu', 'mouvements'),
   31 => __('Statut', 'mouvements'),
   70 => __('Utilisateur', 'mouvements')
];
	$typeMat = [
   'Computer'  => __('Ordinateur', 'mouvements'),
   'Printer' => __('Imprimante', 'mouvements'),
   'Monitor' => __('Ecran', 'mouvements'),
   'Peripheral' => __('Périphérique', 'mouvements') 
];

$label = $typeMap[$row['type_mouvement_code']] ?? __('Autre', 'mouvements');
$label2 = $typeMat[$row['Type_equipement']] ?? ($row['Type_equipement_lib'] ?? __('Autre', 'mouvements'));
    echo '<tr>';
    echo '<td>' . mov_h($label2) . '</td>';
    echo '<td>' . mov_h($row['Inventaire'] ?? '') . '</td>';
	echo '<td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' 
   . mov_h($row['Serial'] ?? '') 
   . '</td>';
    echo '<td>' . mov_h($row['Nom'] ?? '') . '</td>';
    echo '<td>' . mov_h($label) . '</td>';
    echo '<td>' . mov_h($row['Date_mouvement'] ?? '') . '</td>';
    echo '<td>' . mov_h(($row['ancienne_valeur'] ?? '')) . '</td>';
    echo '<td>' . mov_h(($row['nouvelle_valeur'] ?? '')) . '</td>';
    echo '<td>' . mov_h(($row['Utilisateur_a_cet_instant'] ?? '')) . '</td>';
    echo '<td>' . mov_h(($row['Lieu_a_cet_instant'] ?? '')) . '</td>';
    echo '<td>' . mov_h(($row['Statut_a_cet_instant'] ?? '')) . '</td>';
    echo '<td>' . mov_h($row['Modificateur'] ?? '') . '</td>';
    echo '</tr>';
	

}
function cleanValue($value): string {
    // Force en string
    $v = (string)($value ?? '');

    // Retire balises éventuelles et entités HTML
    $v = Toolbox::stripTags($v);
    $v = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Trim (inclut espace normal et NBSP)
    $v = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $v);

    // Retire toutes les "(nombre)" en fin de chaîne (gère aussi NBSP)
    while (preg_match('/[\s\x{00A0}]*\(\d+\)[\s\x{00A0}]*$/u', $v)) {
        $v = preg_replace('/[\s\x{00A0}]*\(\d+\)[\s\x{00A0}]*$/u', '', $v);
    }

    // Protection XSS
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

echo '</table>';
echo '</div>';

Html::footer();


// ---- Script JavaScript pour export Excel
      echo "
      <script src='https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js'></script>
      <script>
         
         // --- Export vers Excel ---
         document.getElementById('exportExcel').addEventListener('click', function() {
            let table = document.getElementById('MouvementsTable');
            let wb = XLSX.utils.table_to_book(table, {sheet: 'Mouvements'});
            XLSX.writeFile(wb, 'mouvements.xlsx');
         });
      </script>
      ";
	  
 
// ---- Script JavaScript pour filtrer le tableau ----
echo '<script>
const input = document.getElementById("tableFilter");
const table = document.querySelector(".tab_cadre_fixehov");

input.addEventListener("keyup", function() {
    const filter = input.value.toLowerCase();
    const rows = table.getElementsByTagName("tr");

    // Commence à 1 pour ignorer l\'en-tête
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName("td");
        let match = false;
        for (let j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().includes(filter)) {
                match = true;
                break;
            }
        }
        rows[i].style.display = match ? "" : "none";
    }
});
</script>';  