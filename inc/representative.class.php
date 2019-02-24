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

        if (!$DB->tableExists($table)) {

            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL auto_increment,
                `entities_id` int(11) COMMENT 'RELATION to glpi_entities (id)',
                `users_id_representative` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',
                `users_id_dpo` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',
                `corporatename` varchar(250) default NULL,
                
                PRIMARY KEY  (`id`),
                UNIQUE `entities_id` (`entities_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

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

        if ($DB->tableExists($table)) {
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
            // Entity page
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
                // Entity page
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
        global $HEADER_LOADED;
        $HEADER_LOADED = true;

        $colsize1 = '13%';
        $colsize2 = '29%';
        $colsize3 = '13%';
        $colsize4 = '45%';

        if ($ID >= 0) {

            $this->getFromDBByCrit(['entities_id' => $ID]);
        }        

        $canUpdate = self::canUpdate() || ($this->fields['id'] <= 0 && self::canCreate());

        if(!isset($this->fields['id'])) { $this->fields['id'] = -1; }
        if($this->fields['id'] <= 0 && !$canUpdate) {

            // No GDPR informations found
            $message = "No GDPR informations found";
            echo sprintf("<br><br><span class='b'>%1\$s</span></div>", $message);
        }

        $showUserLink = 0;
        if (Session::haveRight('user', READ)) {
            $showUserLink = 1;
        }

        $options['canedit'] = $canUpdate;
        $options['formtitle'] = __("Manage entity's informations", "dporegister");

        $this->initForm($this->fields['id'], $options);
        $this->showFormHeader($options);  

        echo "<tr class='tab_bg_2'><td width='$colsize1'>";
        echo __("Legal Representative", 'dporegister');
        echo "</td><td width='$colsize2'>";

        if ($canUpdate) {

            User::dropdown([
                'right' => "all",
                'name' => 'users_id_representative',
                'value' => array_key_exists('users_id_representative', $this->fields) ? $this->fields["users_id_representative"] : null
            ]);

        } else {
            echo getUserName($this->fields["users_id_representative"], $showUserLink);
        }

        echo "</td><td width='$colsize1'>";
        echo __("Data Protection Officer", 'dporegister');
        echo "</td><td  width='$colsize2'>";

        if ($canUpdate) {

            User::dropdown([
                'right' => "all",
                'name' => 'users_id_dpo',
                'value' => array_key_exists('users_id_dpo', $this->fields) ? $this->fields["users_id_dpo"] : null
            ]);

        } else {
            echo getUserName($this->fields["users_id_dpo"], $showUserLink);
        }

        echo "</td></tr>";

        echo "</td><td width='$colsize3'>";
        echo __("Corporate Name", 'dporegister');
        echo "</td><td colspan='3' width='$colsize4'>";
        if($this->fields['id'] <= 0) { $this->fields["corporatename"] = ''; }
        $corporateName = Html::cleanInputText($this->fields["corporatename"]);  
        if ($canUpdate) {
            echo sprintf(
                "<input type='text' style='width:98%%' maxlength=250 name='corporatename' required value=\"%1\$s\"/>",
                $corporateName
            );
        } else {
            if (empty($this->fields["corporatename"])) {
                echo __('Without Corporate Name', 'dporegister');
            } else {
                echo Toolbox::getHtmlToDisplay($corporateName);
            }            
        }

        echo "</td></tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td class='center' colspan='4'>";
        echo sprintf("<input type='hidden' name='entities_id' value=%1\$s>", $ID);
        echo "</td></tr>";

        $this->showFormButtons($options);

        if($this->fields['id'] > 0) {

            $rand = mt_rand(1, mt_getrandmax());
            $funcName = "viewEntityRegister${ID}_$rand";
            $htmlTargetId = "register${ID}_$rand";
            $ajaxUrl = "../plugins/dporegister/ajax/processing_pdf.php?entities_id=$ID";

            $script = "function $funcName() {
                $('#$htmlTargetId').append(
                    \"<iframe id='pdf-output' width='100%' height='500px' src='$ajaxUrl'></iframe>\"
                );
                $('#viewEntityRegister').hide();
            }";

            echo "<div>";
            echo HTML::scriptBlock($script);

            echo "<a class='vsubmit' id='viewEntityRegister' href='javascript:$funcName();'>";
            echo __('View the entity\'s processings register', 'dporegister') . "</a>";
            echo "</div>";

            echo "<div class='tab_cadre_fixe' id='$htmlTargetId'></div>";
        }
    }

    public function post_updateItem($history = 1)
    {        
        
        if(in_array('users_id_representative', $this->updates)) {
    
            $this->updateAllProcessingsUserRepresentative(
                $this->fields['users_id_representative'],
                $this->oldvalues['users_id_representative'],
                $this->fields['entities_id']
            );
        }

        if(in_array('users_id_dpo', $this->updates)) {
    
            $this->updateAllProcessingsDPO(
                $this->fields['users_id_dpo'],
                $this->oldvalues['users_id_dpo'],
                $this->fields['entities_id']
            );
        }
    }

    private function updateAllProcessingsUserRepresentative($newvalue, $oldvalue, $entityid)
    {
        global $DB;

        $type = PluginDporegisterCommonProcessingActor::LEGAL_REPRESENTATIVE;
        $query = $this->createQueryForUpdateAllProcessingsUsers(
            $newvalue, $oldvalue, $entityid, $type
        );
            
        $DB->query($query) or die("updating legal representatives in existing processings " . $DB->error());
    }

    private function updateAllProcessingsDPO($newvalue, $oldvalue, $entityid)
    {
        global $DB;

        $type = PluginDporegisterCommonProcessingActor::DPO;
        $query = $this->createQueryForUpdateAllProcessingsUsers(
            $newvalue, $oldvalue, $entityid, $type
        );
            
        $DB->query($query) or die("updating dpo in existing processings " . $DB->error());
    }

    private function createQueryForUpdateAllProcessingsUsers($newvalue, $oldvalue, $entityid, $type)
    {
        $processings_usersTable = PluginDporegisterProcessing_User::getTable();
        $processingsTable = PluginDporegisterProcessing::getTable();

        return "UPDATE `$processings_usersTable` U
            INNER JOIN `$processingsTable` P ON U.`plugin_dporegister_processings_id` = P.`id`
            SET `users_id` = '$newvalue'
            WHERE `users_id` = '$oldvalue'
                AND `type` = '$type'
                AND P.`entities_id` = '$entityid';";
    }

    /**
     * Retrive the Search options for Entities
     * 
     * @return Array Options
     */
    public static function getSearchOptionsRepresentatives()
    {
        $options = [];
        
        $options[5501] = [
            'id' => '5501',
            'table' => 'glpi_users',
            'field' => 'name',
            'linkfield' => 'users_id_representative',
            'name' => __("Legal Representative", 'dporegister'),
            'massiveaction' => false,
            'datatype' => 'dropdown',
            'joinparams' => [
                'beforejoin' => [
                    'table' => self::getTable(),
                    'joinparams' => [
                        'jointype' => 'child'
                    ]
                ]
            ]
        ];

        $options[5502] = [
            'id' => '5502',
            'table' => 'glpi_users',
            'field' => 'name',
            'linkfield' => 'users_id_dpo',
            'name' => __("Data Protection Officer", 'dporegister'),
            'massiveaction' => false,
            'datatype' => 'dropdown',
            'joinparams' => [
                'beforejoin' => [
                    'table' => self::getTable(),
                    'joinparams' => [
                        'jointype' => 'child'
                    ]
                ]
            ]
        ];

        $options[5503] = [
            'id' => '5503',
            'table' => self::getTable(),
            'field' => 'corporatename',
            'name' => __("Corporate Name", 'dporegister'),
            'massiveaction' => false
        ];

        return $options;
    }
}
