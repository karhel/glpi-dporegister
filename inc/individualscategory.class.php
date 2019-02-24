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

class PluginDporegisterIndividualsCategory extends CommonDropdown
{
    static $rightname = 'plugin_dporegister_individualscategory';

    // --------------------------------------------------------------------
    //  PLUGIN MANAGEMENT - DATABASE INITIALISATION
    // --------------------------------------------------------------------

    /**
    * Install or update PluginDporegisterIndividualsCategory
    *
    * @param Migration $migration Migration instance
    * @param string    $version   Plugin current version
    *
    * @return boolean
    */
    public static function install(Migration $migration)
    {
        global $DB;
        $table = self::getTable();

        if (!$DB->tableExists($table)) {

            $migration->displayMessage(sprintf(__("Installing %s"), $table));

            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL auto_increment,
                `name` varchar(255) collate utf8_unicode_ci default NULL,
                `comment` text collate utf8_unicode_ci,
                `entities_id` int(11) NOT NULL default '0',
                `date_creation` datetime default NULL,
                `date_mod` datetime default NULL,
                
                PRIMARY KEY  (`id`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $DB->query($query) or die("error creating $table " . $DB->error());
        }

        return true;
    }

    /**
    * Uninstall PluginDporegisterIndividualsCategory
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

        // Purge logs table
        $query = "DELETE FROM `glpi_logs` WHERE `itemtype` = '" . __CLASS__ . "'";
        $DB->query($query) or die ("error purge logs table");

        return true;
    }
    
    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

    //! @copydoc CommonGLPI::getTypeName($nb)
    public static function getTypeName($nb = 0)
    {
        return _n('Category of Individuals', 'Categories of Individuals', $nb, 'dporegister');
    }
}