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

class PluginDporegisterProcessing extends CommonITILObject
{
    static $rightname = 'plugin_dporegister_processing';
    public $dohistory = true;
    protected $usenotepad = true;

    const STATUS_MATRIX_FIELD = 'processing_status';

    const READ = 1;
    const UPDATE = 2;
    const CREATE = 4;
    const DELETE = 8;
    const PURGE = 16;
    const READNOTE = 32;
    const UPDATENOTE = 64;

    // --------------------------------------------------------------------
    //  PLUGIN MANAGEMENT - DATABASE INITIALISATION
    // --------------------------------------------------------------------

    /**
     * Install or update PluginDporegisterProcessing
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

            $lawfulbasisTable = PluginDporegisterLawfulBasisModel::getTable();
            $lawfulbasisForeignKey = PluginDporegisterLawfulBasisModel::getForeignKeyField();

            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL auto_increment,
                `date` datetime default NULL,
                `date_creation` datetime default NULL,
                `users_id_recipient` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',
                `date_mod` datetime default NULL,
                `users_id_lastupdater` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',
                `entities_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_entities (id)',
                `is_recursive` tinyint(1) NOT NULL default '0',
                `is_deleted` tinyint(1) NOT NULL default '0',

                `users_id_jointcontroller` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',
                `standard` varchar(250) default NULL, 
                `$lawfulbasisForeignKey` int(11) default NULL COMMENT 'RELATION to $lawfulbasisTable (id)',              

                `name` varchar(255) collate utf8_unicode_ci default NULL,
                `purpose` varchar(255) collate utf8_unicode_ci default NULL,
                `status` int(11) NOT NULL default '1' COMMENT 'Default status to INCOMING',

                `is_compliant` tinyint(1) NOT NULL default '0',
                `pia_required` tinyint(1) NOT NULL default '0',
                `pia_status` int(11) NOT NULL default '0',
                
                PRIMARY KEY  (`id`),
                KEY `name` (`name`),
                KEY `status` (`status`),
                KEY `is_compliant` (`is_compliant`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $DB->query($query) or die("error creating $table " . $DB->error());

            // Insert default display preferences for Processing objects
            $query = "INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES
                ('" . __class__ . "', 1, 1, 0),
                ('" . __class__ . "', 2, 2, 0),
                ('" . __class__ . "', 3, 3, 0),
                ('" . __class__ . "', 4, 4, 0),
                ('" . __class__ . "', 5, 5, 0),
                ('" . __class__ . "', 7, 7, 0),
                ('" . __class__ . "', 8, 8, 0),
                ('" . __class__ . "', 9, 9, 0)";

            $DB->query($query) or die("populating display preferences " . $DB->error());
        }

        return true;
    }

    /**
     * Uninstall PluginDporegisterProcessing
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

        // Purge display preferences table
        $query = "DELETE FROM `glpi_displaypreferences` WHERE `itemtype` = '" . __class__ . "'";
        $DB->query($query) or die('error purge display preferences table' . $DB->error());

        // Purge logs table
        $query = "DELETE FROM `glpi_logs` WHERE `itemtype` = '" . __class__ . "'";
        $DB->query($query) or die('error purge logs table' . $DB->error());

        // Delete links with documents
        $query = "DELETE FROM `glpi_documents_items` WHERE `itemtype` = '" . __class__ . "'";
        $DB->query($query) or die('error purge documents_items table' . $DB->error());

        // Delete notes associated to processings
        $query = "DELETE FROM `glpi_notepads` WHERE `itemtype` = '" . __class__ . "'";
        $DB->query($query) or die('error purge notepads table' . $DB->error());

        return true;
    }

    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

    //! @copydoc CommonGLPI::getTypeName($nb)
    public static function getTypeName($nb = 0)
    {
        return _n('Processing', 'Processings', $nb, 'dporegister');
    }

    //! @copydoc CommonDBTM::getSpecificValueToDisplay($field, $values, $options)
    static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {

            case 'status':
                return self::getStatusIcon($values[$field])
                    . '&nbsp;' . self::getStatus($values[$field]);

            case 'pia_status':
                $piaStatus = self::getPiastatus();
                return $piaStatus[$values[$field]];
        }
    }

    //! @copydoc CommonITILObject::getAllStatusArray($withmetaforsearch) 
    static function getAllStatusArray($withmetaforsearch = false)
    {
        $tab = [
            self::INCOMING => _x('status', 'New'),
            self::QUALIFICATION => __('Qualification'),
            self::EVALUATION => __('Evaluation'),
            self::APPROVAL => __('Approval'),
            self::ACCEPTED => _x('status', 'Accepted'),
        ];

        if ($withmetaforsearch) {

            $tab['all'] = __('All');
        }

        return $tab;
    }

    /**
     * Show the current (or new) object formulaire
     * 
     * @param Integer $ID
     * @param Array $options
     */
    public function showForm($ID, $options = array())
    {
        $colsize1 = '13%';
        $colsize2 = '29%';
        $colsize3 = '13%';
        $colsize4 = '45%';

        $canUpdate = self::canUpdate() || (self::canCreate() && !$ID);

        $showUserLink = 0;
        if (Session::haveRight('user', READ)) {
            $showuserlink = 1;
        }

        $options['canedit'] = $canUpdate;

        if ($ID) {

            $options['formtitle'] = sprintf(
                _('%1$s - ID %2$d'),
                $this->getTypeName(1),
                $ID
            );
        }

        $options['formfooter'] = false;

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        if ($ID) { // Only on a existing processing

            echo "<tr class='tab_bg_1'>";
            echo "<th width='$colsize1'>" . __('Opening Date') . "</th>";
            echo "<td width='$colsize2'>";
            echo sprintf(
                __('%1$s %2$s %3$s'),
                Html::convDateTime($this->fields["date"]),
                __('By'),
                getUserName($this->fields["users_id_recipient"], $showuserlink)
            );

            echo "</td>";

            echo "<th width='$colsize1'>" . __('Last Update') . "</th>";
            echo "<td width='$colsize2'>";
            echo sprintf(
                __('%1$s %2$s %3$s'),
                Html::convDateTime($this->fields["date_mod"]),
                __('By'),
                getUserName($this->fields["users_id_lastupdater"], $showuserlink)
            );

            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<th width='$colsize3'>" . __('Title') . "</th>";
        echo "<td colspan='3' width='$colsize4'>";
        $title = Html::cleanInputText($this->fields["name"]);
        if ($canUpdate) {
            echo sprintf(
                "<input type='text' style='width:98%%' maxlength=250 name='name' required value=\"%1\$s\"/>",
                $title
            );
        } else {
            echo Toolbox::getHtmlToDisplay($title);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th width='$colsize3'>" . __('Purpose', 'dporegister') . "</th>";
        echo "<td colspan='3' width='$colsize4'>";
        $purpose = Html::setSimpleTextContent($this->fields["purpose"]);
        if ($canUpdate) {
            echo sprintf(
                "<textarea style='width:98%%' name='purpose' required maxlength='250' rows='3'>%1\$s</textarea>",
                $purpose
            );
        } else {
            echo Toolbox::getHtmlToDisplay($purpose);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th width='$colsize1'>" . __('Standard', 'dporegister') . "</th>";
        echo "<td width='$colsize2'>";
        $standard = Html::cleanInputText($this->fields["standard"]);
        if ($canUpdate) {
            echo sprintf(
                "<input type='text' style='width:95%%' maxlength=250 name='standard' value=\"%1\$s\"/>",
                $standard
            );
        } else {
            if (empty($this->fields["standard"])) {
                echo __('Without standard', 'dporegister');
            } else {
                echo $standard;
            }
        }
        echo "</td>";
        echo "<th width='$colsize3'>" . __('Joint Controller', 'dporegister') . "</th>";
        echo "<td colspan='3' width='$colsize4'>";
        if ($canUpdate) {
            User::dropdown([
                'name' => 'users_id_jointcontroller',
                'value' => $this->fields["users_id_jointcontroller"],
                'entity' => $this->fields["entities_id"],
                'right' => 'all'
            ]);
        } else {
            echo getUserName($this->fields["users_id_jointcontroller"], $showuserlink);
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th width='$colsize1'>" . __('Entity') . "</th>";
        echo "<td width='$colsize2'>";
        Entity::dropdown([
            'name' => 'entities_id',
            'value' => $this->fields['entities_id']
        ]);
        echo "</td>";

        echo "<th width='$colsize1' rowspan='4' style='vertical-align:top;'>" . __('LawfulBasis', 'dporegister') . "</th>";
        echo "<td width='$colsize2' rowspan='4' style='vertical-align:top;'>";

        if (!$ID || $this->fields['plugin_dporegister_lawfulbasismodels_id'] <= 0) {

            $undefined = new PluginDporegisterLawfulBasisModel();
            $undefined->getFromDBByQuery("WHERE `name` = 'Undefined'");

            $this->fields['plugin_dporegister_lawfulbasismodels_id'] = $undefined->fields['id'];
        }

        $opt = [
            'name' => 'plugin_dporegister_lawfulbasismodels_id',
            'value' => $this->fields['plugin_dporegister_lawfulbasismodels_id'],
            'canupdate' => $canUpdate
        ];

        $rand = PluginDporegisterLawfulBasisModel::dropdown($opt);

        if ($canUpdate) {

            $params = [
                'plugin_dporegister_lawfulbasismodels_id' => '__VALUE__'
            ];

            Ajax::updateItemOnSelectEvent(
                "dropdown_plugin_dporegister_lawfulbasismodels_id$rand",
                "lawfulbasis",
                "../ajax/processing_lawfulbasis_dropdown.php",
                $params
            );
        }

        $lawfulbasis = new PluginDporegisterLawfulBasisModel();
        $lawfulbasis->getFromDB($this->fields['plugin_dporegister_lawfulbasismodels_id']);

        echo "<div id='lawfulbasis'>" . $lawfulbasis->fields['content'] . "</div>";

        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<th width='$colsize1'>" . __('Status') . "</th>";
        echo "<td width='$colsize2'>";
        self::dropdownStatus([
            'value' => $this->fields["status"]
        ]);
        echo "</td></tr>";

        echo "<tr><th width='$colsize1'>" . __('Compliant', 'dporegister') . "</th>";
        echo "<td width='$colsize2'>";
        Dropdown::showYesNo('is_compliant', $this->fields['is_compliant']);
        echo "</td></tr>";

        echo "<tr><th width='$colsize1%'>" . __('PIA Required', 'dporegister') . "</th>";

        echo "<td width='$colsize2%'>";
        $rand = Dropdown::showYesNo('pia_required', $this->fields['pia_required']);
        $params = [
            'pia_required' => '__VALUE__',
            'pia_status' => $this->fields['pia_status']
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_pia_required$rand",
            "pia_status_div",
            "../ajax/processing_pia_required_dropdown.php",
            $params
        );

        $opt = ['value' => $this->fields['pia_status']];
        echo "<div id='pia_status_div'>";
        if ($this->fields['pia_required']) {
            self::dropdownPiaStatus('pia_status', $opt);
        }
        echo "</div>";
        echo "</td></tr>";

        $this->showFormButtons($options);
    }

    //! @copydoc CommonGLPI::defineTabs($options)
    public function defineTabs($options = array())
    {
        $ong = array();

        $this->addDefaultFormTab($ong)
            ->addStandardTab(__class__, $ong, $options)

            ->addStandardTab('PluginDporegisterProcessing_IndividualsCategory', $ong, $options)
            ->addStandardTab('PluginDporegisterProcessing_Software', $ong, $options)
            ->addStandardTab('PluginDporegisterProcessing_PersonalDataCategory', $ong, $options)
            ->addStandardTab('PluginDporegisterProcessing_SecurityMesure', $ong, $options)

            ->addStandardTab('Document_Item', $ong, $options)
            ->addStandardTab('Notepad', $ong, $options)

            ->addStandardTab('PluginDporegisterSimplePDF', $ong, $options)

            ->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    //! @copydoc CommonDBTM::rawSearchOptions()
    function rawSearchOptions()
    {
        global $CFG_GLPI;

        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => __('Characteristics')
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'massiveaction' => false,
            'datatype' => 'number'
        ];

        $tab[] = [
            'id' => '2',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'datatype' => 'itemlink',
            'searchtype' => 'contains',
            'massiveaction' => false
        ];

        $newtab = [
            'id' => '4',
            'table' => $this->getTable(),
            'field' => 'purpose',
            'name' => __('Purpose', 'dporegister'),
            'massiveaction' => false,
            'searchtype' => ['equals', 'notequals'],
        ];

        if ($this->getType() == PluginDporegisterProcessing::class
            && $CFG_GLPI["use_rich_text"]) {
            $newtab['htmltext'] = true;
        }

        $tab[] = $newtab;

        $tab[] = [
            'id' => '3',
            'table' => $this->getTable(),
            'field' => 'status',
            'name' => __('Status'),
            'searchtype' => 'equals',
            'datatype' => 'specific',
            'massiveaction' => true
        ];

        $tab[] = [
            'id' => '5',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'name' => __('Entity'),
            'datatype' => 'dropdown',
            'massiveaction' => true
        ];

        $tab[] = [
            'id' => '6',
            'table' => $this->getTable(),
            'field' => 'date',
            'name' => __('Opening date'),
            'datatype' => 'datetime',
            'massiveaction' => false
        ];

        $tab[] = [
            'id' => '8',
            'table' => $this->getTable(),
            'field' => 'pia_required',
            'name' => __('PIA Required', 'dporegister'),
            'datatype' => 'bool',
            'massiveaction' => true
        ];

        $tab[] = [
            'id' => '9',
            'table' => $this->getTable(),
            'field' => 'pia_status',
            'name' => __('PIA Status', 'dporegister'),
            'searchtype' => ['equals', 'notequals'],
            'massiveaction' => true
        ];

        $tab = array_merge(
            $tab,
            PluginDporegisterLawfulBasisModel::rawSearchOptionsToAdd()
        );

        $tab = array_merge(
            $tab,
            PluginDporegisterProcessing_PersonalDataCategory::rawSearchOptionsToAdd()
        );

        $tab = array_merge(
            $tab,
            PluginDporegisterProcessing_IndividualsCategory::rawSearchOptionsToAdd()
        );

        $tab = array_merge(
            $tab,
            PluginDporegisterProcessing_SecurityMesure::rawSearchOptionsToAdd()
        );

        $tab = array_merge(
            $tab,
            PluginDporegisterProcessing_Software::rawSearchOptionsToAdd()
        );

        return $tab;
    }

    //! @copydoc CommonDBTM::getValueToSelect($field_id_or_search_options, $name, $value, $options)
    function getValueToSelect(
        $field_id_or_search_options,
        $name = '',
        $values = '',
        $options = array()
    ) {
        switch ($field_id_or_search_options['table'] . '.' . $field_id_or_search_options['field']) {

            case $this->getTable() . '.lawfulbasis':
                $options['display'] = false;
                return self::dropdownLawfulBasis($name, $options);

            case $this->getTable() . '.pia_status':
                $options['display'] = false;
                return self::dropdownPiaStatus($name, $options);

            default:
                return parent::getValueToSelect($field_id_or_search_options, $name, $values, $options);
        }
    }

    // --------------------------------------------------------------------
    //  SPECIFICS FOR THE CURRENT OBJECT CLASS
    // --------------------------------------------------------------------

    static function checkLawfulbasisField()
    {
        global $DB;
        $table = self::getTable();

        $lawfulbasisTable = PluginDporegisterLawfulBasisModel::getTable();
        $lawfulbasisForeignKey = PluginDporegisterLawfulBasisModel::getForeignKeyField();

        if (!FieldExists($table, $lawfulbasisForeignKey)) {

            $query = "ALTER TABLE `$table` ADD `$lawfulbasisForeignKey` int(11) NOT NULL default '0' COMMENT 'RELATION to $lawfulbasisTable (id)';";
            $DB->query($query) or die("error altering $table to add the new lawfulbasis column " . $DB->error());
        }

        if (FieldExists($table, 'lawfulbasis')) {

            $processings = (new PluginDporegisterProcessing())->find();
            foreach ($processings as $resultSet) {

                $ID = $resultSet['id'];
                $name = PluginDporegisterLawfulBasisModel::$gdprValue[$resultSet['lawfulbasis']];

                $query = "UPDATE `$table` SET `$lawfulbasisForeignKey` = (
                        SELECT id FROM `$lawfulbasisTable` WHERE `name` = $name )
                        WHERE id = $ID;";

                $DB->query($query) or die("error updating $table ($ID) with the new lawfulbasis model $name " . $DB->error());
            }

            $query = "ALTER TABLE `$table` DROP `lawfulbasis`";
            $DB->query($query) or die("error altering $table to remove the old lawfulbasis column " . $DB->error());
        }

        return true;
    }

    /**
     * Get PIA Status list
     * 
     * @param Boolean $WithMetaForSearch
     * 
     * @return Array
     */
    static function getPiastatus($withmetaforsearch = false)
    {
        $options = [
            __('N/A'),
            __('To do'),
            __('Qualification'),
            __('Approval'),
            __('Pending'),
            __('Closed')
        ];

        if ($withmetaforsearch) {

            $options['all'] = __('All');
        }

        return $options;
    }

    /**
     * Show (or retreive) the dropdown about the PIA Status
     * 
     * @param String $name Name for the form input
     * @param Array $options
     * 
     * @return String
     * @see self::getPiastatus
     */
    static function dropdownPiaStatus($name, $options = [])
    {
        $params['value'] = 0;
        $params['toadd'] = [];
        $params['on_change'] = '';
        $params['display'] = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $items = [];
        if (count($params['toadd']) > 0) {
            $items = $params['toadd'];
        }

        $items += self::getPiaStatus();

        return Dropdown::showFromArray($name, $items, $params);
    }

    // --------------------------------------------------------------------
    //  SPECIFICS AJAX CALL
    // --------------------------------------------------------------------

    /**
     * Show the Lawful Basis full name of the current processing
     * 
     * @return String
     */
    public function getLawfulBasis()
    {
        return self::getLawfulBasises()[$this->fields['lawfulbasis']];
    }

    /**
     * Show the lawful basis full description of the current processing
     * 
     * @return String
     */
    public function getLawfulBasisDescription()
    {
        return self::showLawfulBasis($this->fields['lawfulbasis']);
    }
}