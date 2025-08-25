<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginMouvementsMouvement extends CommonGLPI {

   static function getTypeName($nb = 0) {
      return __('Mouvements', 'Mouvements');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (in_array($item->getType(), ['Computer','Printer','Monitor','Peripheral'])) {
         return self::getTypeName();
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::renderItemTab($item);
      return true;
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
	/*
      // ---- Affichage du menu de filtre ----
      echo "<form method='get' action=''>";
      echo "<input type='hidden' name='id' value='".$item->getID()."'>";
      echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
      echo "<input type='hidden' name='tab' value='PluginMouvementsMouvement\$1'>";
      echo "<label for='Mouvement_filter'>".__('Filtrer par type :','Mouvements')."</label> ";
      echo "<select name='Mouvement_filter' id='Mouvement_filter' onchange='this.form.submit()'>";
      $opts = [
         ''            => __('--- Tous ---','Mouvements'),
         'lieu'        => __('Lieu','Mouvements'),
         'statut'      => __('Statut','Mouvements'),
         'user' => __('Utilisateur','Mouvements'),
         'etat'        => __('Etat','Mouvements')
      ];
      foreach ($opts as $val => $label) {
         $sel = ($Mouvement_filter === $val) ? "selected" : "";
         echo "<option value='$val'>$label</option>";
      }
      echo "</select>";
      echo "</form><br>";*/
	  
//echo "Mouvement filter: ";
//var_dump($Mouvement_filter);
      // ---- Construction de la condition SQL ----
   /*   $extraWhere  = "l.items_id = $items_id";
      if ($Mouvement_filter == 'lieu') {
          $extraWhere .= " AND l.id_search_option = 3";
      } else if ($Mouvement_filter == 'statut') {
         $extraWhere .= " AND l.id_search_option = 31";
      } else if ($Mouvement_filter == 'user') {
         $extraWhere .= " AND l.id_search_option = 70";
      } else if ($Mouvement_filter == 'etat') {
         $extraWhere .= " AND l.id_search_option = 76670";
      }else if (empty($Mouvement_filter)) {
		$extraWhere .= " AND l.id_search_option IN (3,31,70,76670)";
	}*/

      //$sql = self::buildGlobalSQL($extraWhere, 200);
      $res = $DB->query($sql);

      if ($DB->numrows($res) == 0) {
         echo "<div class='m-2'>" . __('Aucun mouvement trouvé', 'Mouvements') . "</div>";
         return;
      }

      // ---- Tableau des résultats ----
	  // ---- Champ de filtre côté client ----
echo '<div style="text-align:center; font-size:20px; font-weight:bold;">MOUVEMENTS</div>';


echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">';
echo '  <div style="display:flex; align-items:center; gap:10px;">';
echo '    <label for="tableFilter" style="font-weight:bold;">Filtrer le tableau :</label>';
echo '    <input type="text" id="tableFilter" placeholder="Tapez pour filtrer..." style="max-width:200px;">';
echo '  </div>';
echo '  <button id="exportExcel" class="vsubmit">' . __('Exporter vers Excel','Mouvements') . '</button>';
echo '</div>';

      echo '<div class="spaced">';
      echo '<table id="MouvementsTable" class="tab_cadre_fixehov">';
      echo '<tr>'
         . '<th>' . __('Type mouvement','Mouvements') . '</th>'
         . '<th>' . __('Date','Mouvements') . '</th>'
         . '<th>' . __('Ancienne valeur','Mouvements') . '</th>'
         . '<th>' . __('Nouvelle valeur','Mouvements') . '</th>'
         . '<th>' . __('Utilisateur à cet instant','Mouvements') . '</th>'
         . '<th>' . __('Lieu à cet instant','Mouvements') . '</th>'
         . '<th>' . __('Statut à cet instant','Mouvements') . '</th>'
         . '<th>' . __('État à cet instant','Mouvements') . '</th>'
         . '<th>' . __('Modificateur','Mouvements') . '</th>'
		 . '<th>' . __('N°Inv','Mouvements') . '</th>'
         . '</tr>';

      while ($row = $DB->fetchAssoc($res)) {
         echo '<tr>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['type_mouvement'] ?? '')) . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Date_mouvement'] ?? '')) . '</td>';	
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['ancienne_valeur'] ?? '')) . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['nouvelle_valeur'] ?? '')) . '</td>';	
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Utilisateur_a_cet_instant'] ?? '')) . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Lieu_a_cet_instant'] ?? '')) . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Statut_a_cet_instant'] ?? '')) . '</td>';
			echo  '<td>' . htmlspecialchars(self::cleanValue($row['Etat_a_cet_instant'] ?? '')) . '</td>';		
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
	  

   }



   // ================== SQL builder ==================
   private static function buildGlobalSQL($currentType, $currentId, $limit = 1000) {
//var_dump($currentType);	   
	   
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
         WHEN 76670 THEN 'Etat'
         ELSE 'Autre'
      END AS type_mouvement,
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
         ORDER BY l4.date_mod DESC LIMIT 1) AS Statut_a_cet_instant,
      (SELECT l5.new_value FROM glpi_logs l5
         WHERE l5.itemtype = '".$currentType."' AND l5.items_id = c.id AND l5.id_search_option = 76670 AND l5.date_mod <= l.date_mod
         ORDER BY l5.date_mod DESC LIMIT 1) AS Etat_a_cet_instant
   FROM glpi_logs l
   JOIN " . $m['table'] . " c ON (l.items_id = c.id)
   LEFT JOIN " . $m['typetable'] . " ct ON (c.".$m['typecol']." = ct.id)
   LEFT JOIN " . $m['modeltable'] . " cm ON (c.".$m['modelcol']." = cm.id)
   LEFT JOIN glpi_users u ON (c.users_id = u.id)
   LEFT JOIN glpi_groups g ON (u.groups_id = g.id)
   WHERE l.itemtype = '".$currentType."' 
     AND l.items_id = $currentId
     AND l.id_search_option IN (3,31,70,76670)
   ORDER BY l.date_mod DESC
   LIMIT " . (int)$limit;

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
