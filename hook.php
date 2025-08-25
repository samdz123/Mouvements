<?php
use PluginMouvements\Mouvement;

function plugin_Mouvements_postinit() {
   global $PLUGIN_HOOKS;

   // Types sur lesquels on veut voir apparaître l’onglet
   $types = ['Computer','Printer','Monitor','Peripheral'];

   foreach ($types as $type) {
      // Associe le type d'objet à la classe qui gère l'onglet
      CommonGLPI::registerStandardTab($type, 'PluginMouvementsMouvement');
   }
}
