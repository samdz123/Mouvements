<?php

function plugin_init_Mouvements() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['mouvements'] = true;

   // Déclare la classe pour ajouter un onglet
   $types = ['Computer','Printer','Monitor','Peripheral'];
   foreach ($types as $type) {
      CommonGLPI::registerStandardTab($type, 'PluginMouvementsMouvement');
   }

   // Page de configuration éventuelle (optionnelle)
   // $PLUGIN_HOOKS['config_page']['Mouvements'] = 'front/config.form.php';
}

/**
 * Métadonnées du plugin
 */
function plugin_version_Mouvements() {
   return [
      'name'           => __('Mouvements', 'Mouvements'),
      'version'        => '1.0.0',
      'author'         => 'Saad Meslem',
      'license'        => 'GPLv3+',
      'homepage'       => 'https://tonsite.example',
      'minGlpiVersion' => '10.0.0',
      'requirements'   => [
         'glpi' => [
            'min' => '10.0.0',
            'max' => '10.1.99'
         ]
      ]
   ];
}

/**
 * Installation
 */
function plugin_Mouvements_install() {
   return true;
}

/**
 * Désinstallation
 */
function plugin_Mouvements_uninstall() {
   return true;
}
