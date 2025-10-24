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

function plugin_init_mouvements() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['mouvements'] = true;

   
   // Ajout de l'onglet "Mouvements" sur les fiches équipements
   $types = ['Computer','Printer','Monitor','Peripheral'];
   foreach ($types as $type) {
      CommonGLPI::registerStandardTab($type, 'PluginMouvementsMouvement');
   }
   
         if (Session::getLoginUserID()) {
			$PLUGIN_HOOKS['menu_toadd']['mouvements'] = ['tools' => 'PluginMouvementsMenu'];
		}
		
   Plugin::registerClass('PluginMouvementsMouvement');
   Plugin::registerClass('PluginMouvementsInitialValue');
}

/**
 * Métadonnées du plugin
 */
function plugin_version_mouvements() {
   return [
      'name'           => __('Mouvements', 'mouvements'),
      'version'        => '1.2.0',
      'author'         => 'Saad Meslem',
      'license'        => 'GPLv3+',
      'homepage'       => "'https://github.com/samdz123/Mouvements'",
      'requirements'   => [
         'glpi' => [
            'min' => '10.0.0',
            'max' => '11.1.99'
         ]
      ]
   ];
}

function plugin_mouvements_install() {
   global $DB;

   $migration = new Migration(100); // version interne du plugin

   if (!$DB->tableExists('glpi_plugin_mouvements_initialvalues')) {
      $query = "CREATE TABLE `glpi_plugin_mouvements_initialvalues` (
         `id` INT AUTO_INCREMENT PRIMARY KEY,
         `itemtype` VARCHAR(100) NOT NULL,
         `items_id` INT NOT NULL,
         `field` VARCHAR(50) NOT NULL,
         `initial_value` VARCHAR(255) NOT NULL,
         `date_recorded` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
         UNIQUE KEY `uniq_initial` (`itemtype`, `items_id`, `field`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

      // Utiliser runSql pour éviter l'erreur
      $migration->addPostQuery($query);
   }

   $migration->executeMigration();

   return true;
}


function plugin_mouvements_uninstall() {
   global $DB;

   $migration = new Migration(100);

   if ($DB->tableExists('glpi_plugin_mouvements_initialvalues')) {
      $migration->dropTable('glpi_plugin_mouvements_initialvalues');
   }

   $migration->executeMigration();

   return true;
}

/**
 * Hook pour ajouter les droits du plugin.
 *
 * @param array $rights
 * @return array
 */
