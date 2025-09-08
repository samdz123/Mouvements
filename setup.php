<?php

function plugin_init_mouvements() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['mouvements'] = true;

   
   // Ajout de l'onglet "Mouvements" sur les fiches équipements
   $types = ['Computer','Printer','Monitor','Peripheral'];
   foreach ($types as $type) {
      CommonGLPI::registerStandardTab($type, 'PluginMouvementsMouvement');
   }
   
         if (Session::getCurrentInterface()) {
         
            $PLUGIN_HOOKS['menu_toadd']['mouvements'] = ['tools' => 'PluginMouvementsMenu'];
      }
   
}

/**
 * Métadonnées du plugin
 */
function plugin_version_mouvements() {
   return [
      'name'           => __('Mouvements', 'mouvements'),
      'version'        => '1.0.0',
      'author'         => 'Saad Meslem',
      'license'        => 'GPLv3+',
      'homepage'       => 'https://tonsite.example',
      'requirements'   => [
         'glpi' => [
            'min' => '10.0.0',
            'max' => '10.1.99'
         ]
      ]
   ];
}

function plugin_mouvements_install() {
   return true;
}

function plugin_mouvements_uninstall() {
   return true;
}

/**
 * Hook pour ajouter les droits du plugin.
 *
 * @param array $rights
 * @return array
 */
