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

class PluginDporegisterProcessing_Software extends CommonDBRelation
{
    static public $itemtype_1, $items_id_1, $itemtype_2, $items_id_2;

    public static function init()
    {
        self::$itemtype_1 = PluginDporegisterProcessing::class;
        self::$items_id_1 = PluginDporegisterProcessing::getForeignKeyField();

        self::$itemtype_2 = Software::class;
        self::$items_id_2 = Software::getForeignKeyField();
    }

    // --------------------------------------------------------------------
    //  PLUGIN MANAGEMENT - DATABASE INITIALISATION
    // --------------------------------------------------------------------

    /**
     * Install or update PluginDporegisterProcessing_PersonalDataCategory
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
                `" . self::$items_id_1 . "` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugins_dporegister_processings (id)',
                `" . self::$items_id_2 . "` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_softwares (id)',
                
                PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $DB->query($query) or die("error creating $table " . $DB->error());
        }
    }

    /**
     * Uninstall PluginDporegisterProcessing_PersonalDataCategory
     *
     * @return boolean
     */
    public static function uninstall()
    {
        global $DB;
        $table = self::getTable();

        if ($DB->tableExists($table)) {
            $query = "DROP TABLE `$table`";
            $DB->query($query) or die("error deleting $table " . $DB->error());
        }

        // Purge the logs table of the entries about the current class
        $query = "DELETE FROM `glpi_logs`
            WHERE `itemtype` = '" . __class__ . "' 
            OR `itemtype_link` = '" . self::$itemtype_1 . "'";

        $DB->query($query) or die("error purge logs table");

        return true;
    }

    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

    //! @copydoc CommonGLPI::getTypeName($nb)
    static function getTypeName($nb = 0)
    {
        return _n('Link Processing/Software', 'Links Processing/Software', $nb, 'dporegister');
    }

    //! @copydoc CommonGLPI::displayTabContentForItem($item, $tabnum, $withtemplate)
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // Check ACL
        if (!Software::canView() || !$item->canView()) {
            return false;
        }

        // Check item type
        switch ($item->getType()) {

            case PluginDporegisterProcessing::class:

                self::showForProcessing($item);
                break;
        }

        return true;
    }

    /**
     * Show the tab content for the Processing Object
     * 
     * @param   PluginDporegisterProcessing $processing
     * 
     * @return  void
     */
    static function showForProcessing($processing)
    {
        global $DB;

        $table = self::getTable();

        $processingId = $processing->fields['id'];

        if (!PluginDporegisterProcessing::canView()) {
            return false;
        }

        $canedit = PluginDporegisterProcessing::canUpdate();
        $rand = mt_rand(1, mt_getrandmax());

        if ($canedit) {

            echo "<div class='firstbloc'>";
            echo "<form name='ticketitem_form' id='ticketitem_form' method='post'
                action='" . Toolbox::getItemTypeFormURL(__class__) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add a software', 'dporegister') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='center'>";

            Software::dropdown([
                'name' => self::$items_id_2
            ]);

            echo "&nbsp;<input type='hidden' name='" . self::$items_id_1 . "' value='$processingId' />";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
            echo "</td></tr>";

            echo "</table>";
            Html::closeForm();
            echo "</div>";

        }

        $query = "SELECT DISTINCT(" . self::$items_id_2 . ")
                FROM `$table`
                WHERE `$table`.`" . self::$items_id_1 . "` = '$processingId'";

        $result = $DB->query($query);

        if ($result) {
            $number = $DB->numrows($result);

            echo "<div class='spaced'>";
            if ($canedit && $number) {
                Html::openMassiveActionsForm('mass' . __class__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __class__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";

            $header_begin = "<tr>";
            $header_top = '';
            $header_bottom = '';
            $header_end = '';
            if ($canedit && $number) {

                $header_top .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __class__ . $rand);
                $header_top .= "</th>";
                $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __class__ . $rand);
                $header_bottom .= "</th>";
            }

            $header_end .= "<th>" . __('Name') . "</th>";
            $header_end .= "<th>" . __('Entity') . "</th>";
            $header_end .= "<th>" . __('Publisher') . "</th>";
            $header_end .= "<th>" . __('Category') . "</th>";
            echo $header_begin . $header_top . $header_end . "</tr>";

            for ($i = 0; $i < $number; $i++) {

                $softwareId = $DB->result($result, $i, "softwares_id");

                $query = "SELECT `" . Software::getTable() . "`.*, `$table`.id AS IDD, `glpi_entities`.id AS entity
                            FROM " . Software::getTable();

                $query .= " LEFT JOIN `glpi_entities`
                                 ON (`" . Software::getTable() . "`.`entities_id`=`glpi_entities`.`id`) ";

                $query .= " LEFT JOIN `$table`
                                ON (`" . Software::getTable() . "`.`id`=`$table`.`" . self::$items_id_2 . "`) ";

                $query .= " WHERE `" . Software::getTable() . "`.`id` = $softwareId";

                $result_linked = $DB->query($query);
                $nb = $DB->numrows($result_linked);

                for ($prem = true; $data = $DB->fetch_assoc($result_linked); $prem = false) {
                    $link = Software::getFormURLWithID($data['id']);
                    $linkname = $data["name"];

                    if ($_SESSION["glpiis_ids_visible"]
                        || empty($data["name"])) {
                        $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
                    }
                    $name = "<a href=\"" . $link . "\">" . $linkname . "</a>";


                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__class__, $data["IDD"]);
                        echo "</td>";
                    }

                    echo "<td class='center" . (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                    echo ">" . $name . "</td>";

                    echo "<td class='center'>";
                    echo Dropdown::getDropdownName(Entity::getTable(), $data['entity']) . "</td>";

                    echo "<td class='center'>";
                    echo Dropdown::getDropdownName(Manufacturer::getTable(), $data['manufacturers_id']) . "</td>";

                    echo "<td class='center'>";
                    echo Dropdown::getDropdownName(SoftwareCategory::getTable(), $data['softwarecategories_id']) . "</td>";

                    echo "</tr>";
                }

            }

            echo "</table>";

            if ($canedit && $number) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
            echo "</div>";
        }
    }

    //! @copydoc CommonGLPI::getTabNameForItem($item, $withtemplate)
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {

            case PluginDporegisterProcessing::class:

                $nb = 0;

                if ($_SESSION['glpishow_count_on_tabs']) {

                    $nb = countElementsInTable(
                        self::getTable(),
                        [self::$items_id_1 => $item->getID()]
                    );
                }

                return self::createTabEntry(
                    Software::getTypeName($nb),
                    $nb
                );
        }

        return '';
    }

    //! @copydoc CommonDBTM::getForbiddenStandardMassiveAction()
    function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';

        return $forbidden;
    }

    /**
     * 
     */
    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $tab[] = [
            'id' => 'software',
            'name' => Software::getTypeName(0)
        ];

        $tab[] = [
            'id' => '51',
            'table' => Software::getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'forcegroupby' => true,
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

        return $tab;
    }
}

// Emulate static constructor
PluginDporegisterProcessing_Software::init();
