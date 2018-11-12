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

class PluginDporegisterProcessing_PiaModel extends CommonDBRelation
{
    static public $itemtype_1, $items_id_1, $itemtype_2, $items_id_2;

    public static function init()
    {
        self::$itemtype_1 = PluginDporegisterProcessing::class;
        self::$items_id_1 = PluginDporegisterProcessing::getForeignKeyField();

        self::$itemtype_2 = PluginDporegisterPiaModel::class;        
        self::$items_id_2 = PluginDporegisterPiaModel::getForeignKeyField();
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
        if (!TableExists($table)) {

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

        if (TableExists($table)) {
            $query = "DROP TABLE `$table`";
            $DB->query($query) or die("error deleting $table " . $DB->error());
        }

        $query = "DELETE FROM `glpi_logs` WHERE `itemtype` = '" . __class__ . "'";
        $DB->query($query) or die("error purge logs table");
    }

    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

    //! @copydoc CommonGLPI::getTypeName($nb)
    static function getTypeName($nb = 0)
    {
        return _n('Link Processing/PIA', 'Links Processing/PIA', $nb, 'dporegister');
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
    static function showForProcessing(PluginDporegisterProcessing $processing)
    {
        global $DB, $CFG_GLPI;
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

                return self::createTabEntry(PluginDporegisterPiaModel::getTypeName($nb), $nb);
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
     * Show the current (or new) object formulaire
     * 
     * @param Integer $ID
     * @param Array $options
     */
    function showForm($ID, $options = [])
    {
        //$processingId = $options[];

    }
}

// Emulate static constructor
PluginDporegisterProcessing_PiaModel::init();