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
use Glpi\DBAL\QueryExpression;
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginMouvementsMouvement extends CommonGLPI {

   // *** AJOUT: Définir le rightname pour utiliser les droits du plugin ***
   static $rightname = 'plugin_mouvements';

   /**
    * Vérifie si l'utilisateur peut créer un mouvement
    */
   static function canCreate(): bool {
      return Session::haveRight(self::$rightname, CREATE);
   }

   /**
    * Vérifie si l'utilisateur peut voir les mouvements
    */
   static function canView(): bool {
      return Session::haveRight(self::$rightname, READ);
   }

   /**
    * Vérifie si l'utilisateur peut modifier un mouvement
    */
   function canUpdateItem(): bool {
      return Session::haveRight(self::$rightname, UPDATE);
   }

   /**
    * Vérifie si l'utilisateur peut supprimer un mouvement
    */
   function canDeleteItem(): bool {
      return Session::haveRight(self::$rightname, DELETE);
   }

   /**
    * Vérifie si l'utilisateur peut purger un mouvement
    */
   function canPurgeItem(): bool {
      return Session::haveRight(self::$rightname, PURGE);
   }
   // *** FIN DES AJOUTS DE DROITS ***

   static function getTypeName($nb = 0) {
      return __('Mouvements', 'mouvements');
   }
   
   
   
   
function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
    // *** AJOUT: Vérification des droits ***
    if (!self::canView()) {
        return '';
    }
    // *** FIN AJOUT ***
    
    // Afficher le tab "Mouvements" pour les équipements
    if (in_array($item->getType(), ['Computer','Printer','Monitor','Peripheral'])) {
        return self::getTypeName();
    }
    
    // NOUVEAU : Afficher le tab "Historique Équipements" pour les utilisateurs
    if ($item->getType() === 'User') {
        return __('Mouvements', 'mouvements');
    }
    
    return '';
}

static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
    // *** AJOUT: Vérification des droits ***
    if (!self::canView()) {
        return false;
    }
    // *** FIN AJOUT ***
    
    // Affichage pour les équipements
    if ($item->getType() === 'Computer' || $item->getType() === 'Printer' 
        || $item->getType() === 'Monitor' || $item->getType() === 'Peripheral') {
        self::renderItemTab($item);
        return true;
    }
    
    // NOUVEAU : Affichage pour les utilisateurs
    if ($item->getType() === 'User') {
        self::showEquipmentHistoryForUser($item);
        return true;
    }
    
    return false;
}

static function showEquipmentHistoryForUser(CommonGLPI $user): void {
    global $DB;
    
    if (!$user || $user->getType() !== 'User') {
        return;
    }
    
    $user_id = (int)$user->fields['id'];
    $user_name = htmlspecialchars($user->fields['name'] ?? '', ENT_QUOTES, 'UTF-8');

    //echo "<div class='center'>";
    //echo "<h2>" . __('Historique des équipements utilisés par l’utilisateur <<', 'mouvements') . "  " . $user_name . " >> " . "</h2>";
    
    // Requête SQL pour récupérer l'historique des équipements
    $query = [
        'SELECT' => [
            'l.id',
            'l.date_mod',
            'l.itemtype',
            'l.items_id',
            'l.old_value',
            'l.new_value',
            'l.user_name',
            'c.name AS computer_name',
            'c.otherserial AS computer_serial',
            'p.name AS printer_name',
            'p.otherserial AS printer_serial',
            'm.name AS monitor_name',
            'm.otherserial AS monitor_serial',
            'pe.name AS peripheral_name',
            'pe.otherserial AS peripheral_serial'
        ],
        'FROM' => 'glpi_logs AS l',
        'LEFT JOIN' => [
            'glpi_computers AS c' => [
                'ON' => [
                    'l' => 'items_id',
                    'c' => 'id',
                    ['AND' => ['l.itemtype' => 'Computer']]
                ]
            ],
            'glpi_printers AS p' => [
                'ON' => [
                    'l' => 'items_id',
                    'p' => 'id',
                    ['AND' => ['l.itemtype' => 'Printer']]
                ]
            ],
            'glpi_monitors AS m' => [
                'ON' => [
                    'l' => 'items_id',
                    'm' => 'id',
                    ['AND' => ['l.itemtype' => 'Monitor']]
                ]
            ],
            'glpi_peripherals AS pe' => [
                'ON' => [
                    'l' => 'items_id',
                    'pe' => 'id',
                    ['AND' => ['l.itemtype' => 'Peripheral']]
                ]
            ]
        ],
'WHERE' => [
    'AND' => [
        'l.itemtype' => ['Computer', 'Printer', 'Monitor', 'Peripheral'],
		'l.id_search_option' => ['70'],
        'OR' => [
            
			['l.new_value' => ['LIKE', '%' . $user_name . '%']],
            ['l.old_value' => ['LIKE', '%' . $user_name . '%']]
        ]
    ]
],
        'ORDER' => ['l.date_mod DESC']
    ];

    //try {
        $result = $DB->request($query);

        if ($result->count() === 0) {
            echo "<p class='center'>" . __('Aucun historique trouvé', 'mouvements') . "</p>";
            echo "</div>";
            return;
        }
        
        // Affichage du tableau
		
// ---- Titre et Filtre ----
echo '<div style="text-align:center; font-size:20px; font-weight:bold;">' . __('MOUVEMENT', 'mouvements') . '</div>';
echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">';
echo '  <div style="display:flex; align-items:center; gap:10px;">';
echo '    <label for="tableFilterUser" style="font-weight:bold;">' . __('Filtrer le tableau :','mouvements') . '</label>';
echo '    <input type="text" id="tableFilterUser" placeholder="' . __('Tapez pour filtrer...','mouvements') . '" style="max-width:200px;">';
echo '  </div>';
echo '  <button id="UserexportExcel" class="vsubmit">' . __('Exporter vers Excel','mouvements') . '</button>';
echo '</div>';

echo '<div class="spaced">';
echo '<table id="UserMouvementsTable" class="tab_cadre_fixehov">';
echo "<tr>";
echo "<th>" . __('Type équipement', 'mouvements') . "</th>";
echo "<th>" . __('Nom', 'mouvements') . "</th>";
echo "<th>" . __('N°Inv', 'mouvements') . "</th>";
echo "<th>" . __('N°S', 'mouvements') . "</th>";
echo "<th>" . __('Date', 'mouvements') . "</th>";
echo "<th>" . __('Ancienne valeur', 'mouvements') . "</th>";
echo "<th>" . __('Nouvelle valeur', 'mouvements') . "</th>";
echo "<th>" . __('Modifié par', 'mouvements') . "</th>";
echo "</tr>";

foreach ($result as $row) {
    $itemtype = $row['itemtype'];
    $equipment_name = '';
    $serial = '';
    $inventaire = '';
    
    // Récupération du nom et du numéro de série selon le type
    switch ($itemtype) {
        case 'Computer':
            $equipment_name = htmlspecialchars($row['computer_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $serial = htmlspecialchars($row['computer_serial'] ?? '', ENT_QUOTES, 'UTF-8');
            $type_label = __('Ordinateur', 'mouvements');
            break;
        case 'Printer':
            $equipment_name = htmlspecialchars($row['printer_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $serial = htmlspecialchars($row['printer_serial'] ?? '', ENT_QUOTES, 'UTF-8');
            $type_label = __('Imprimante', 'mouvements');
            break;
        case 'Monitor':
            $equipment_name = htmlspecialchars($row['monitor_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $serial = htmlspecialchars($row['monitor_serial'] ?? '', ENT_QUOTES, 'UTF-8');
            $type_label = __('Moniteur', 'mouvements');
            break;
        case 'Peripheral':
            $equipment_name = htmlspecialchars($row['peripheral_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $serial = htmlspecialchars($row['peripheral_serial'] ?? '', ENT_QUOTES, 'UTF-8');
            $type_label = __('Périphérique', 'mouvements');
            break;
        default:
            $type_label = htmlspecialchars($itemtype, ENT_QUOTES, 'UTF-8');
    }
    
    $old_value = htmlspecialchars(self::cleanValue($row['old_value'] ?? ''), ENT_QUOTES, 'UTF-8');
    $new_value = htmlspecialchars(self::cleanValue($row['new_value'] ?? ''), ENT_QUOTES, 'UTF-8');
    $date_mod = htmlspecialchars(self::cleanValue($row['date_mod'] ?? ''), ENT_QUOTES, 'UTF-8');

    $user_mod = htmlspecialchars(self::cleanValue($row['user_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    echo "<tr>";
    echo "<td>" . $type_label . "</td>";
    echo "<td>" . $equipment_name . "</td>";
    echo "<td>" . $inventaire . "</td>";
    echo "<td>" . $serial . "</td>";
    echo "<td>" . $date_mod . "</td>";
    echo "<td>" . $old_value . "</td>";
    echo "<td>" . $new_value . "</td>";
    echo "<td>" . $user_mod . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// ---- Script JavaScript pour filtrer le tableau User equipement ----
echo '<script>
(function() {
    const input = document.getElementById("tableFilterUser");
    const table = document.getElementById("UserMouvementsTable");
    
    if (!input || !table) return; // Vérification de sécurité
    
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
})();
</script>';


 // ---- Script JavaScript pour export Excel user assets movements
      echo "
      <script src='https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js'></script>
      <script>
         
         // --- Export vers Excel ---
         document.getElementById('UserexportExcel').addEventListener('click', function() {
            let table = document.getElementById('UserMouvementsTable');
            let wb = XLSX.utils.table_to_book(table, {sheet: 'Mouvements'});
            XLSX.writeFile(wb, 'mouvements.xlsx');
         });
      </script>
      ";
}
   public static function renderItemTab($item) {
      global $DB;

      $itemtype = $item->getType();
      $items_id = (int)$item->getID();
	  $sql = self::buildGlobalSQL($itemtype, $items_id, 200);

	  // ---- Récupération du filtre courant ----
   $Mouvement_filter = $_GET['Mouvement_filter'] ?? '';

      // ---- Gestion du filtre en session ----
      if (isset($_GET['Mouvement_filter'])) {
    $Mouvement_filter = $_GET['Mouvement_filter'];
    $_SESSION['plugin_Mouvements_filter'] = $Mouvement_filter;
	} else {
    $Mouvement_filter = $_SESSION['plugin_Mouvements_filter'] ?? '';
	}
	
      $iterator = $DB->request($sql);


      if (count($iterator) == 0) {
         echo "<div class='m-2'>" . __('Aucun mouvement trouvé', 'mouvements') . "</div>";
         return;
      }


      // ---- Tableau des résultats ----
	  // ---- Champ de filtre côté client ----
echo '<div style="text-align:center; font-size:20px; font-weight:bold;">' . __('MOUVEMENT', 'mouvements') . '</div>';
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
         . '<th>' . __('Type mouvement','mouvements') . '</th>'
         . '<th>' . __('Date','mouvements') . '</th>'
         . '<th>' . __('Ancienne valeur','mouvements') . '</th>'
         . '<th>' . __('Nouvelle valeur','mouvements') . '</th>'
         . '<th>' . __('Utilisateur@instant','mouvements') . '</th>'
         . '<th>' . __('Lieu@instant','mouvements') . '</th>'
		 . '<th>' . __('Statut@instant','mouvements') . '</th>'
         . '<th>' . __('Modificateur','mouvements') . '</th>'
		 . '<th>' . __('N°Inv','mouvements') . '</th>'
         . '</tr>';

	  


      foreach ($iterator as $row) {
		  
		  $typeMap2 = [
   3  => __('Lieu', 'mouvements'),
   31 => __('Statut', 'mouvements'),
   70 => __('Utilisateur', 'mouvements')
];

$label = $typeMap2[$row['type_mouvement_code']] ?? __('Autre', 'mouvements');
         echo '<tr>';
			echo  '<td>' . self::cleanValue($label ?? '') . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Date_mouvement'] ?? '')) . '</td>';	
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['ancienne_valeur'] ?? '')) . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['nouvelle_valeur'] ?? '')) . '</td>';	
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Utilisateur_a_cet_instant'] ?? '')) . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Lieu_a_cet_instant'] ?? '')) . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Statut_a_cet_instant'] ?? '')) . '</td>';		
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Modificateur'] ?? '')) . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Inventaire'] ?? '')) . '</td>';		
            echo  '</tr>';
      }

// … votre code PHP pour afficher le tableau …

echo '</table></div>'; // fin du tableau


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



// ---- Script JavaScript pour export Excel movement
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
	 

   }
   

   // ================== SQL builder ==================
   private static function buildGlobalSQL($currentType, $currentId, $limit = 1000) {   
	   global $DB;
   $blocks = [
      'Computer'   => ['table' => 'glpi_computers',   'typecol' => 'computertypes_id',   'modelcol' => 'computermodels_id',   'typetable' => 'glpi_computertypes',   'modeltable' => 'glpi_computermodels'],
      'Printer'    => ['table' => 'glpi_printers',    'typecol' => 'printertypes_id',    'modelcol' => 'printermodels_id',    'typetable' => 'glpi_printertypes',    'modeltable' => 'glpi_printermodels'],
      'Monitor'    => ['table' => 'glpi_monitors',    'typecol' => 'monitortypes_id',    'modelcol' => 'monitormodels_id',    'typetable' => 'glpi_monitortypes',    'modeltable' => 'glpi_monitormodels'],
      'Peripheral' => ['table' => 'glpi_peripherals', 'typecol' => 'peripheraltypes_id', 'modelcol' => 'peripheralmodels_id', 'typetable' => 'glpi_peripheraltypes', 'modeltable' => 'glpi_peripheralmodels']
   ];

   if (!isset($blocks[$currentType])) {
      return '';
   }

   $m = $blocks[$currentType];

$initUser = PluginMouvementsInitialValue::getInitialValue($currentType, $currentId, 'user');
if (!$initUser) {
   // valeur actuelle jointe depuis glpi_users
   $user = $DB->request([
      'SELECT' => ['u.name'],
      'FROM'   => $m['table'].' AS c',
      'LEFT JOIN' => [
         'glpi_users AS u' => ['FKEY' => ['c' => 'users_id', 'u' => 'id']]
      ],
      'WHERE'  => ['c.id' => $currentId]
   ])->current();

   $username = $user ? $user['name'] : '';

   if ($username) {
      PluginMouvementsInitialValue::saveInitialValue($currentType, $currentId, 'user', $username);
      $initUser = $username;
   }
}

$initLocation = PluginMouvementsInitialValue::getInitialValue($currentType, $currentId, 'location');
if (!$initLocation) {
   $loc = $DB->request([
      'SELECT' => ['loc.name'],
      'FROM'   => $m['table'].' AS c',
      'LEFT JOIN' => [
         'glpi_locations AS loc' => ['FKEY' => ['c' => 'locations_id', 'loc' => 'id']]
      ],
      'WHERE'  => ['c.id' => $currentId]
   ])->current();

   $locname = $loc ? $loc['name'] : '';

   if ($locname) {
      PluginMouvementsInitialValue::saveInitialValue($currentType, $currentId, 'location', $locname);
      $initLocation = $locname;
   }
}

$initState = PluginMouvementsInitialValue::getInitialValue($currentType, $currentId, 'status');
if (!$initState) {
   $st = $DB->request([
      'SELECT' => ['st.name'],
      'FROM'   => $m['table'].' AS c',
      'LEFT JOIN' => [
         'glpi_states AS st' => ['FKEY' => ['c' => 'states_id', 'st' => 'id']]
      ],
      'WHERE'  => ['c.id' => $currentId]
   ])->current();

   $stname = $st ? $st['name'] : '';

   if ($stname) {
      PluginMouvementsInitialValue::saveInitialValue($currentType, $currentId, 'status', $stname);
      $initState = $stname;
   }
}
if (class_exists('\\Glpi\\DBAL\\QueryExpression')) {
	//glpi 11 
$sql = [
    'SELECT' => [
        new QueryExpression("'".$currentType."' AS Type_equipement"),
        'c.otherserial AS Inventaire',
        'c.name AS Nom',
        'c.serial AS Serial',
        new QueryExpression("DATE_FORMAT(l.date_mod, '%d/%m/%Y %H:%i') AS Date_mouvement"),
        new QueryExpression("CASE l.id_search_option
             WHEN 3 THEN 'Lieu'
             WHEN 31 THEN 'Statut'
             WHEN 70 THEN 'Utilisateur'
             ELSE 'Autre'
         END AS type_mouvement"),
        'l.id_search_option AS type_mouvement_code',
        'l.old_value AS ancienne_valeur',
        'l.new_value AS nouvelle_valeur',
        'g.name AS Structure_Utilisateur_actuel',
        'ct.name AS soustype_equipement',
        'cm.name AS Model',
        'u.name AS Utilisateur_actuel',
        'l.user_name AS Modificateur',
        'l.id AS log_id',

        // Sous-requêtes (inchangées, mais protégées par QueryExpression)
		            // fallback intelligent sur valeurs initiales
            new QueryExpression("COALESCE(
               (SELECT l2.new_value FROM glpi_logs l2
                  WHERE l2.itemtype = '".$DB->escape($currentType)."'
                    AND l2.items_id = c.id
                    AND l2.id_search_option = 70
                    AND l2.date_mod <= l.date_mod
                  ORDER BY l2.date_mod DESC LIMIT 1),
               '".$DB->escape($initUser)."'
            ) AS Utilisateur_a_cet_instant"),

            new QueryExpression("COALESCE(
               (SELECT l3.new_value FROM glpi_logs l3
                  WHERE l3.itemtype = '".$DB->escape($currentType)."'
                    AND l3.items_id = c.id
                    AND l3.id_search_option = 3
                    AND l3.date_mod <= l.date_mod
                  ORDER BY l3.date_mod DESC LIMIT 1),
               '".$DB->escape($initLocation)."'
            ) AS Lieu_a_cet_instant"),

            new QueryExpression("COALESCE(
               (SELECT l4.new_value FROM glpi_logs l4
                  WHERE l4.itemtype = '".$DB->escape($currentType)."'
                    AND l4.items_id = c.id
                    AND l4.id_search_option = 31
                    AND l4.date_mod <= l.date_mod
                  ORDER BY l4.date_mod DESC LIMIT 1),
               '".$DB->escape($initState)."'
            ) AS Statut_a_cet_instant"),
    ],
    'FROM' => ['glpi_logs AS l'],
    'JOIN' => [
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
    'WHERE' => [
        ['l.itemtype' => $currentType],
        ['l.items_id' => $currentId],
        ['l.id_search_option' => [3, 31, 70]],
    ],
    'ORDER' => ['l.date_mod DESC'],
    'LIMIT' => (int)$limit
];
}else {
	// GLPI 10
	$sql = "
   SELECT
      '".$currentType."' AS Type_equipement,
      c.otherserial AS Inventaire,
      c.name AS Nom,
      c.serial AS Serial,
	  (DATE_FORMAT(l.date_mod, '%d/%m/%Y %H:%i')) AS Date_mouvement,
      CASE l.id_search_option
         WHEN 3 THEN 'Lieu'
         WHEN 31 THEN 'Statut'
         WHEN 70 THEN 'Utilisateur'
         ELSE 'Autre'
      END AS type_mouvement,
	  l.id_search_option AS type_mouvement_code,
      l.old_value AS ancienne_valeur,
      l.new_value AS nouvelle_valeur,
      g.name AS Structure_Utilisateur_actuel,
      ct.name AS soustype_equipement,
      cm.name AS Model,
      u.name AS Utilisateur_actuel,
      l.user_name AS Modificateur,
      l.id AS log_id,
      -- valeurs à cet instant
      (SELECT l2.new_value FROM glpi_logs l2
         WHERE l2.itemtype = '".$currentType."' AND l2.items_id = c.id AND l2.id_search_option = 70 AND l2.date_mod <= l.date_mod
         ORDER BY l2.date_mod DESC LIMIT 1) AS Utilisateur_a_cet_instant,
      (SELECT l3.new_value FROM glpi_logs l3
         WHERE l3.itemtype = '".$currentType."' AND l3.items_id = c.id AND l3.id_search_option = 3 AND l3.date_mod <= l.date_mod
         ORDER BY l3.date_mod DESC LIMIT 1) AS Lieu_a_cet_instant,
      (SELECT l4.new_value FROM glpi_logs l4
         WHERE l4.itemtype = '".$currentType."' AND l4.items_id = c.id AND l4.id_search_option = 31 AND l4.date_mod <= l.date_mod
         ORDER BY l4.date_mod DESC LIMIT 1) AS Statut_a_cet_instant
   FROM glpi_logs l
   JOIN " . $m['table'] . " c ON (l.items_id = c.id)
   LEFT JOIN " . $m['typetable'] . " ct ON (c.".$m['typecol']." = ct.id)
   LEFT JOIN " . $m['modeltable'] . " cm ON (c.".$m['modelcol']." = cm.id)
   LEFT JOIN glpi_users u ON (c.users_id = u.id)
   LEFT JOIN glpi_groups g ON (u.groups_id = g.id)
   WHERE l.itemtype = '".$currentType."' 
     AND l.items_id = $currentId
     AND l.id_search_option IN (3,31,70)
   ORDER BY l.date_mod DESC
   LIMIT " . (int)$limit;

}
return $sql;
}

   // Helper : nettoie "Libellé (123)" => "Libellé"
private static function cleanValue($value): string {
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

    return $v;
}

}
