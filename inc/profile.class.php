<?php

/**
 * Classe de gestion des profils pour le plugin Mouvements
 * Compatible GLPI 10 et 11
 * CORRECTION FINALE: Ajout du hook pour sauvegarder les droits
 */
class PluginMouvementsProfile extends CommonDBTM {

   static $rightname = 'profile';

   /**
    * Retourne le nom du type
    */
   static function getTypeName($nb = 0) {
      return __('Mouvements', 'mouvements');
   }

   /**
    * Retourne les noms des onglets pour un élément
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         if ($item->getField('id')) {
            return self::createTabEntry(self::getTypeName());
         }
      }
      return '';
   }

   /**
    * Affiche le contenu de l'onglet
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         $profile_obj = new self();
         $profile_obj->showForProfile($item);
      }
      return true;
   }

   /**
    * Affiche le formulaire de configuration des droits pour un profil
    * CORRECTION: Passage de l'objet Profile complet
    */
   function showForProfile(Profile $profile) {
      global $DB;
      
      $profiles_id = $profile->getID();
      
      if (!Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
         return false;
      }
      
      $canedit = $profile->canEdit($profiles_id);
      
      echo "<div class='spaced'>";
      
      if ($canedit) {
         echo "<form method='post' action='" . $profile->getFormURL() . "'>";
      }
      
      $rights = self::getAllRights();
      
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>" . __('Permission du plugin Mouvements', 'mouvements') . "</th>";
      echo "</tr>";
      
      foreach ($rights as $right) {
         $this->displayRightRow($profile, $right, $canedit);
      }
      
      if ($canedit) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='hidden' name='id' value='$profiles_id'>";
         echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
         echo "</td></tr>";
      }
      
      echo "</table>";
      
      if ($canedit) {
         Html::closeForm();
      }
      
      echo "</div>";
      
      return true;
   }

   /**
    * Affiche une ligne de droit
    */
   function displayRightRow(Profile $profile, $right, $canedit = false) {
      global $DB;
      
      $profiles_id = $profile->getID();
      
      // Récupérer la valeur actuelle du droit
      $iterator = $DB->request([
         'FROM'  => 'glpi_profilerights',
         'WHERE' => [
            'profiles_id' => $profiles_id,
            'name'        => $right['field']
         ]
      ]);
      
      $current_value = 0;
      foreach ($iterator as $data) {
         $current_value = (int)$data['rights'];
      }
      
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $right['label'] . "</td>";
      echo "<td>";
      
      if ($canedit) {
         $values = [
            0  => __('No access'),
            READ  => __('Read')
         ];
         
         Dropdown::showFromArray(
            '_' . $right['field'], // CORRECTION: Préfixe avec '_' pour le traitement
            $values,
            [
               'value' => $current_value,
               'width' => '100%'
            ]
         );
      } else {
         $values_display = [
            0  => __('No access'),
            READ  => __('Read'),
            READ | UPDATE => __('Read') . ' + ' . __('Update'),
            ALLSTANDARDRIGHT => __('All')
         ];
         
         echo $values_display[$current_value] ?? __('No access');
      }
      
      echo "</td>";
      echo "</tr>";
   }

   /**
    * Retourne tous les droits du plugin
    */
   static function getAllRights() {
      $rights = [
         [
            'itemtype'  => 'PluginMouvementsMouvement',
            'label'     => __('Mouvements', 'mouvements'),
            'field'     => 'plugin_mouvements'
         ]
      ];
      return $rights;
   }

   /**
    * NOUVEAU: Hook appelé lors de la mise à jour d'un profil
    * Sauvegarde les droits du plugin dans glpi_profilerights
    */
   static function changeProfile() {
      global $DB;
      
      if (!isset($_POST['_plugin_mouvements'])) {
         return;
      }
      
      $profiles_id = (int)$_POST['id'];
      $rights_value = (int)$_POST['_plugin_mouvements'];
      
      // Vérifier si le droit existe déjà
      $iterator = $DB->request([
         'FROM'  => 'glpi_profilerights',
         'WHERE' => [
            'profiles_id' => $profiles_id,
            'name'        => 'plugin_mouvements'
         ]
      ]);
      
      if (count($iterator) > 0) {
         // Mettre à jour
         $DB->update('glpi_profilerights', [
            'rights' => $rights_value
         ], [
            'profiles_id' => $profiles_id,
            'name'        => 'plugin_mouvements'
         ]);
      } else {
         // Insérer
         $DB->insert('glpi_profilerights', [
            'profiles_id' => $profiles_id,
            'name'        => 'plugin_mouvements',
            'rights'      => $rights_value
         ]);
      }
   }

   /**
    * Installation des droits du plugin dans les profils
    */
   static function install(Migration $migration) {
      global $DB;

      $profiles = $DB->request(['FROM' => 'glpi_profiles']);
      
      foreach ($profiles as $profile) {
         $profiles_id = $profile['id'];
         
         // Vérifier si le droit existe déjà
         $iterator = $DB->request([
            'FROM'  => 'glpi_profilerights',
            'WHERE' => [
               'profiles_id' => $profiles_id,
               'name'        => 'plugin_mouvements'
            ]
         ]);
         
         if (count($iterator) == 0) {
            // Ajouter le droit avec tous les privilèges pour Super-Admin
            $rights = ($profile['name'] == 'Super-Admin') ? ALLSTANDARDRIGHT : 0;
            
            $DB->insert('glpi_profilerights', [
               'profiles_id' => $profiles_id,
               'name'        => 'plugin_mouvements',
               'rights'      => $rights
            ]);
         }
      }
   }

   /**
    * Désinstallation des droits du plugin
    */
   static function uninstall(Migration $migration) {
      global $DB;

      // Supprimer les droits du plugin de tous les profils
      $DB->delete('glpi_profilerights', [
         'name' => 'plugin_mouvements'
      ]);
   }

   /**
    * Initialisation du profil lors du changement de profil
    */
   static function initProfile() {
      global $DB;

      if (!isset($_SESSION['glpiactiveprofile']) || !isset($_SESSION['glpiactiveprofile']['id'])) {
         return false;
      }
      
      $profiles_id = $_SESSION['glpiactiveprofile']['id'];
      
      $iterator = $DB->request([
         'FROM'  => 'glpi_profilerights',
         'WHERE' => [
            'profiles_id' => $profiles_id,
            'name'        => 'plugin_mouvements'
         ]
      ]);
      
      foreach ($iterator as $data) {
         $_SESSION['glpiactiveprofile']['plugin_mouvements'] = $data['rights'];
      }
      
      return true;
   }

   /**
    * Créer les droits par défaut pour un nouveau profil
    */
   static function createProfileRights($profiles_id) {
      global $DB;

      $DB->insert('glpi_profilerights', [
         'profiles_id' => $profiles_id,
         'name'        => 'plugin_mouvements',
         'rights'      => 0
      ]);
   }
}
