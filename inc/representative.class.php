<?php
/*
 -------------------------------------------------------------------------
 DPO Register plugin for GLPI
 Copyright (C) 2018 by the DPO Register Development Team.

 https://github.com/karhel/glpi-dporegister
 -------------------------------------------------------------------------

 LICENSE

 This file is part of DPO Register.

 DPO Register is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 DPO Register is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with DPO Register. If not, see <http://www.gnu.org/licenses/>.

 --------------------------------------------------------------------------

  @package   dporegister
  @author    Karhel Tmarr
  @copyright Copyright (c) 2010-2013 Uninstall plugin team
  @license   GPLv3+
             http://www.gnu.org/licenses/gpl.txt
  @link      https://github.com/karhel/glpi-dporegister
  @since     2018
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginDporegisterRepresentative extends CommonDBTM
{
    static $rightname = 'plugin_dporegister_representatives';

    // --------------------------------------------------------------------
    //  PLUGIN MANAGEMENT - DATABASE INITIALISATION
    // --------------------------------------------------------------------

    /**
     * Install or update PluginDporegisterRepresentative
     *
     * @param Migration $migration Migration instance
     * @param string    $version   Plugin current version
     *
     * @return boolean
     */
    public static function install(Migration $migration, $version)
    {
        global $DB;
        $table = self::getTable();

        if (!TableExists($table)) {

            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL auto_increment,
                `entities_id` int(11) COMMENT 'RELATION to glpi_entities (id)',
                `users_id_representative` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',
                `users_id_dpo` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',
                `corporatename` varchar(250) default NULL,
                
                PRIMARY KEY  (`id`),
                UNIQUE `entities_id` (`entities_id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $DB->query($query) or die("error creating $table " . $DB->error());
        }
    }

    /**
     * Uninstall PluginDporegisterRepresentative
     *
     * @return boolean
     */
    public static function uninstall()
    {
        global $DB;
        $table = self::getTable();

        if (TableExists($table)) {
            $query = "DROP TABLE `$table`";
            $DB->query($query) or die("error deleting $table");
        }
    }

    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

    //! @copydoc CommonGLPI::getTypeName($nb)
    static function getTypeName($nb = 0)
    {
        return __('GDPR Informations', 'dporegister');
    }

    //! @copydoc CommonGLPI::displayTabContentForItem($item, $tabnum, $withtemplate)
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // Check ACL
        if (!$item->canView()) {
            return false;
        }

        // Check item type
        switch ($item->getType()) {

            case Entity::class:
            
                $representative = new self();
                $representative->showForm($item->fields['id']);
                break;
        }

        return true;
    }

    //! @copydoc CommonGLPI::getTabNameForItem($item, $withtemplate)
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (self::canView()) {

            switch ($item->getType()) {

                case Entity::class:

                    return self::getTypeName(2);
            }
        }

        return '';
    }
    
    /**
     * Show the current object formulaire
     * 
     * @param Integer $ID
     * @param Array $options
     */
    function showForm($ID, $options = array())
    {
        if ($ID >= 0) {
            $this->getFromDBByQuery("WHERE `entities_id` = $ID");
        }

        $target = $this->getFormURL();
        if (isset($options['target'])) {
            $target = $options['target'];
        }

        $canupdate = self::canUpdate();

        echo "<form action='" . $target . "' method='post'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='4' class='center b'>";
        echo __("Manage entity's representatives", "dporegister");
        echo "</th></tr>";

        echo "<tr class='tab_bg_2'><td width='13%'>";
        echo __("Legal Representative", 'dporegister');
        echo "</td><td width='29%'>";

        if ($canupdate) {

            User::dropdown([
                'right' => "all",
                'name' => 'users_id_representative',
                'value' => array_key_exists('users_id_representative', $this->fields) ? $this->fields["users_id_representative"] : null,
                'entity' => $ID
            ]);

        } else {
            echo getUserName($this->fields["users_id_recipient"], $showuserlink);
        }

        echo "</td><td width='13%'>";
        echo __("Data Protection Officer", 'dporegister');
        echo "</td><td  width='29%'>";

        if ($canupdate) {

            User::dropdown([
                'right' => "all",
                'name' => 'users_id_dpo',
                'value' => array_key_exists('users_id_dpo', $this->fields) ? $this->fields["users_id_dpo"] : null,
                'entity' => $ID
            ]);

        } else {
            echo getUserName($this->fields["users_id_recipient"], $showuserlink);
        }

        echo "</td></tr>";
        
        echo "<tr><th colspan='4' class='center b'>";
        echo __("Manage entity's informations", "dporegister");
        echo "</th></tr>";

        echo "<tr class='tab_bg_2'><td width='13%'>";
        echo __("Corporate Name", 'dporegister');
        echo "</td><td colspan='3'>";
        if ($canupdate) {
            echo "<input type='text' style='width:98%' maxlength=250 name='corporatename' required='required'" .
                " value=\"" . (array_key_exists('corporatename', $this->fields) ? Html::cleanInputText($this->fields["corporatename"]) : '') . "\">";
        } else {
            if (!array_key_exists('corporatename', $this->fields) || empty($this->fields["corporatename"])) {
                echo __('Without Corporate Name');
            } else {
                echo $this->fields["corporatename"];
            }
        }
        echo "</td></tr>";

        if ($canupdate) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center' colspan='4'>";

            if (!empty($this->fields)) {
                echo "<input type='hidden' name='id' value=" . $this->fields['id'] . ">";
            }

            echo "<input type='hidden' name='entities_id' value=" . $ID . ">";
            echo "<input type='submit' name='" . (!empty($this->fields) ? 'update' : 'add') . "' value='" . __('Update') . "' class='submit'>";
            echo "</td></tr>";
        }

        echo "</table>";
        Html::closeForm();


        if(PluginDporegisterProcessing::canView()) {

            $rand = mt_rand();

            echo "<script type='text/javascript' >\n";
            echo "function viewEntityRegister${ID}_$rand() {\n";

                echo "$('#register${ID}_$rand').append(\"";
                echo "<iframe id='pdf-output' width='100%' height='500px' src='../plugins/dporegister/ajax/processing_pdf.php?entities_id=$ID'></iframe>";
                echo "\");";

                echo "$('#viewEntityRegister').hide();";

            echo "};";
            echo "</script>\n";

            echo "<a class='vsubmit' id='viewEntityRegister' href='javascript:viewEntityRegister${ID}_$rand();'>" .
                __('View the entity\'s processings register', 'dporegister') . "</a>";
            echo "</div>";

            echo "<div class='tab_cadre_fixe' id='register${ID}_$rand'></div>";

        }
    }
}
