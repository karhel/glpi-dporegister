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

class PluginDporegisterProcessing_IndividualsCategory extends CommonDBRelation
{
    static public $itemtype_1, $items_id_1, $itemtype_2, $items_id_2;

    static function init()
    {
        self::$itemtype_1 = PluginDporegisterProcessing::class;
        self::$items_id_1 = PluginDporegisterProcessing::getForeignKeyField();

        self::$itemtype_2 = PluginDporegisterIndividualsCategory::class;
        self::$items_id_2 = PluginDporegisterIndividualsCategory::getForeignKeyField();
    }

    // --------------------------------------------------------------------
    //  PLUGIN MANAGEMENT - DATABASE INITIALISATION
    // --------------------------------------------------------------------

    /**
     * Install or update PluginDporegisterProcessing_IndividualsCategory
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

            $migration->displayMessage(sprintf(__("Installing %s"), $table));

            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL auto_increment,
                `" . self::$items_id_1 . "` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugins_dporegister_processings (id)',
                `" . self::$items_id_2 . "` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugins_dporegister_personaldatacategories (id)',

                PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $DB->query($query) or die("error creating $table " . $DB->error());
        }
    }

    /**
     * Uninstall PluginDporegisterPersonalDataCategory
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
            WHERE `itemtype` = '" . __CLASS__ . "' 
            OR `itemtype_link` = '" . self::$itemtype_1 . "' 
            OR `itemtype_link` = '" . self::$itemtype_2 . "'";
            
        $DB->query($query) or die ("error purge logs table");

        return true;
    }

    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

    //! @copydoc CommonGLPI::getTypeName($nb)
    static function getTypeName($nb = 0)
    {
        return _n('Link Processing/Individuals Category', 'Links Processing/Individuals Category', $nb, 'dporegister');
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
        $canedit = PluginDporegisterProcessing::canUpdate();
        $rand = mt_rand(1, mt_getrandmax());

        if ($canedit) {

            echo "<div class='firstbloc'>";
            echo "<form name='ticketitem_form' id='ticketitem_form' method='post'
                action='" . Toolbox::getItemTypeFormURL(__class__) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add a Category of Individuals', 'dporegister') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='center'>";

            PluginDporegisterIndividualsCategory::dropdown([
                'name' => self::$items_id_2
            ]);

            echo "&nbsp;<input type='hidden' name='" . self::$items_id_1 . "' value='$processingId' />";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
            echo "</td></tr>";

            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        $query = "SELECT `" . PluginDporegisterIndividualsCategory::getTable() . "`.*, `$table`.id AS 'IDD'
            FROM `" . PluginDporegisterIndividualsCategory::getTable() .
            "` LEFT JOIN `$table` ON `" . PluginDporegisterIndividualsCategory::getTable() . "`.id = `$table`." . self::$items_id_2 . "
            WHERE `$table`." . self::$items_id_1 . " = $processingId";

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

            $header_end .= "<th width='30%'>" . __('Name') . "</th>";
            $header_end .= "<th>" . __('Comment') . "</th>";
            echo $header_begin . $header_top . $header_end . "</tr>";

            while ($data = $DB->fetch_assoc($result)) {

                echo "<tr class='tab_bg_1'>";

                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__class__, $data["IDD"]);
                    echo "</td>";
                }

                echo "<td width='30%' class='center'>" . $data['name'] . "</td>";
                echo "<td class='center'>" . $data['comment'] . "</td>";

                echo "</tr>";
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
                        [
                            self::$items_id_1 => $item->getID()
                        ]
                    );
                }

                return self::createTabEntry(
                    PluginDporegisterIndividualsCategory::getTypeName($nb),
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
            'id' => 'individualscategory',
            'name' => PluginDporegisterIndividualsCategory::getTypeName(0)
        ];

        $tab[] = [
            'id' => '31',
            'table' => PluginDporegisterIndividualsCategory::getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'forcegroupby' => true,
            'massiveaction' => false,
            'datatype' => 'dropdown',
            'searchtype' => ['equals', 'notequals'],
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
PluginDporegisterProcessing_IndividualsCategory::init();
