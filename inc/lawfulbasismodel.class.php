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

class PluginDporegisterLawfulBasisModel extends CommonDropdown
{
    static $rightname = 'plugin_dporegister_lawfulbasismodel';

    // --------------------------------------------------------------------
    //  PLUGIN MANAGEMENT - DATABASE INITIALISATION
    // --------------------------------------------------------------------

    /**
     * Install or update PluginDporegisterLawfulbasis
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
                `name` varchar(255) collate utf8_unicode_ci default NULL,
                `content` varchar(1024) collate utf8_unicode_ci default NULL,
                `comment` text collate utf8_unicode_ci,
                `is_gdpr` tinyint(1) NOT NULL default 0,
                `entities_id` int(11) NOT NULL default '0',
                `is_recursive` tinyint(1) NOT NULL default '1',
                `date_creation` datetime default NULL,
                `date_mod` datetime default NULL,
                
                PRIMARY KEY  (`id`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $DB->query($query) or die("error creating $table " . $DB->error());
        }

        // Check doesn't contain any GDPR lawfulbasis
        if (!countElementsInTable($table, ['is_gdpr = 1'])) {

            $gdprValues = require(PLUGIN_DPOREGISTER_ROOT . '/data/lawfulbasismodel.php');
            self::insertGDPRValuesInDatabase($gdprValues);
        }

        // Check old version for migrations/upgrade
        PluginDporegisterProcessing::checkLawfulbasisField();
    }

    /**
     * Uninstall PluginDporegisterLawfulbasis
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

        $query = "DELETE FROM `glpi_logs` WHERE `itemtype` = '" . __class__ . "'";
        $DB->query($query) or die("error purge logs table");
    }

    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

    //! @copydoc CommonDBTM::canUpdateItem()
    function canUpdateItem()
    {

        // If it's from GDPR, prevent update
        if ($this->fields['is_gdpr']) return false;

        return parent::canUpdateItem();
    }

    //! @copydoc CommonDBTM::canDeleteItem()
    function canDeleteItem()
    {

        // If it's from GDPR, prevent delete
        if ($this->fields['is_gdpr']) return false;

        return parent::canDeleteItem();
    }

    //! @copydoc CommonDBTM::canPurgeItem()
    function canPurgeItem()
    {

        // If it's from GDPR, prevent purge
        if ($this->fields['is_gdpr']) return false;

        return parent::canPurgeItem();
    }

    //! @copydoc CommonGLPI::getTypeName($nb)
    public static function getTypeName($nb = 0)
    {
        return _n('LawfulBasis', 'LawfulBasises', $nb, 'dporegister');
    }

    //! @copydoc CommonDropdown::getAdditionalFields()
    public function getAdditionalFields()
    {
        return [
            [
                'name' => 'content',
                'label' => __('Content'),
                'type' => 'textarea',
                'rows' => 6
            ]
        ];
    }


    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $tab[] = [
            'id' => 'lawfulbasis',
            'name' => self::getTypeName(0)
        ];

        $tab[] = [
            'id' => '7',
            'table' => self::getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'searchtype' => ['equals', 'notequals'],
            'datatype' => 'dropdown',
            'massiveaction' => true
        ];

        return $tab;
    }

    // --------------------------------------------------------------------
    //  SPECIFICS FOR THE CURRENT OBJECT CLASS
    // --------------------------------------------------------------------

    protected static function insertGDPRValuesInDatabase($gdprValues)
    {
        foreach ($gdprValues as $values) {

            // Test if lawfulbasis already exists
            if (!countElementsInTable(
                self::getTable(),
                ['name' => $values[0]]
            )) {

                // Add the object in the database
                $object = new self();
                $object->add([
                    'name' => $values[0],
                    'content' => addslashes($values[1]),
                    'is_gdpr' => true,
                ]);
            }
        }
    }
}
