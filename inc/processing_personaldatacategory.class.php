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

class PluginDporegisterProcessing_PersonalDataCategory extends CommonDBRelation
{
    static public $itemtype_1, $items_id_1, $itemtype_2, $items_id_2;

    public static function init()
    {
        self::$itemtype_1 = PluginDporegisterProcessing::class;
        self::$items_id_1 = PluginDporegisterProcessing::getForeignKeyField();

        self::$itemtype_2 = PluginDporegisterPersonalDataCategory::class;        
        self::$items_id_2 = PluginDporegisterPersonalDataCategory::getForeignKeyField();
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
                `".self::$items_id_1."` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugins_dporegister_processings (id)',
                `".self::$items_id_2."` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugins_dporegister_personaldatacategories (id)',
                `comment` varchar(250) NOT NULL default '',

                `retentionschedule_contract` tinyint(1) NOT NULL default '0',
                `retentionschedule_value` int(11) NOT NULL default '0',
                `retentionschedule_scale` char(1) NOT NULL default 'y',
                `retentionschedule_aftercontract` tinyint(1) NOT NULL default '0',

                `source` char(3) NULL,
                `destination` varchar(250) NULL,

                `location` varchar(250) NULL,

                `thirdcountriestransfert` tinyint(1) NOT NULL default '0',
                `thirdcountriestransfert_value` varchar(160) NOT NULL default '',
                
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
        return _n('Link Processing/Personal Data', 'Links Processing/Personal Data', $nb, 'dporegister');
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
        global $DB, $CFG_GLPI;

        $table = self::getTable();

        $processingId = $processing->fields['id'];
        $canedit = PluginDporegisterProcessing::canUpdate();
        $rand = mt_rand(1, mt_getrandmax());

        if ($canedit) {
            echo "<script type='text/javascript' >\n";
            echo "function viewAddPersonalDataCategory" . $processingId . "_$rand() {\n";
            $params = [
                'id' => -1,
                self::$items_id_1 => $processingId
            ];

            Ajax::updateItemJsCode(
                "viewpersonaldatacategory" . $processingId . "_$rand",
                "../ajax/processing_personaldatacategory_view_subitem.php",
                $params
            );

            echo "$('#viewAddPersonalDataCategory').hide();";

            echo "};";
            echo "</script>\n";

            echo "<div class='center firstbloc'>";
            echo "<div id='viewpersonaldatacategory" . $processingId . "_$rand'></div>";
            echo "<a class='vsubmit' id='viewAddPersonalDataCategory' href='javascript:viewAddPersonalDataCategory" . $processingId . "_$rand();'>" .
                __('Add a new Personal Data Category', 'dporegister') . "</a>";
            echo "</div>";
        }

        $query = "SELECT `" . PluginDporegisterPersonalDataCategory::getTable() . "`.*, `" . $table . "`.id AS 'IDD', 
        `" . $table . "`.source, `" . $table . "`.retentionschedule_value, `" . $table . "`.retentionschedule_scale,
        `" . $table . "`.destination, `" . $table . "`.comment, `" . $table . "`.retentionschedule_contract , 
        `" . $table . "`.retentionschedule_aftercontract, `" . $table . "`.location,
        `" . $table . "`.thirdcountriestransfert, `" . $table . "`.thirdcountriestransfert_value
        FROM `" . PluginDporegisterPersonalDataCategory::getTable() . "`";

        $query .= " LEFT JOIN `$table`
        ON (`" . PluginDporegisterPersonalDataCategory::getTable() . "`.`id`=`$table`.`".self::$items_id_2."`) ";

        $query .= " WHERE `$table`.".self::$items_id_1." = $processingId";

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
            $header_end .= "<th>" . __('Sensible', 'dporegister') . "</th>";
            $header_end .= "<th>" . __('Source', 'dporegister') . "</th>";
            $header_end .= "<th>" . __('Retention Schedule', 'dporegister') . "</th>";
            $header_end .= "<th>" . __('Destination', 'dporegister') . "</th>";
            $header_end .= "<th>" . __('Location') . "</th>";
            $header_end .= "<th>" . __('Third Countries transfert', 'dporegister') . "</th>";
            $header_end .= "<th>" . __('Comment') . "</th>";
            echo $header_begin . $header_top . $header_end . "</tr>";

            while ($data = $DB->fetch_assoc($result)) {

                echo "<tr class='tab_bg_1'>";

                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__class__, $data["IDD"]);
                    echo "</td>";
                }

                echo "<td class='center'" . ($canedit ?
                    "style='cursor:pointer' onClick=\"viewEditPersonalDataCategory" . $processingId . "_" . $data['IDD'] . "_$rand()\"><a"
                    : '')
                    . ">" . $data['completename'] . ($canedit ? '</a>' : '');

                if ($canedit) {
                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditPersonalDataCategory" . $processingId . "_" . $data['IDD'] . "_$rand() {\n";

                    $params = [
                        self::$items_id_1 => $processingId,
                        'id' => $data["IDD"]
                    ];

                    Ajax::updateItemJsCode(
                        "viewpersonaldatacategory" . $processingId . "_$rand",
                        "../ajax/processing_personaldatacategory_view_subitem.php",
                        $params
                    );

                    echo "$('#viewAddPersonalDataCategory').show();";

                    echo "};";
                    echo "</script>\n";
                }

                echo "</td>";
                
                echo "<td class='center'>" . ($data['is_sensible'] == 1 ? __('Yes') : __('No')) . "</td>";
                echo "<td class='center'>" . self::getSources($data['source']) . "</td>";
                echo "<td class='center'>" . self::showRetentionSchedule($data, false) . "</td>";
                echo "<td class='center'>" . $data['destination'] . "</td>";
                echo "<td class='center'>" . $data['location'] . "</td>";
                echo "<td class='center'>" . self::showThirdCountriesTransfert($data, false) . "</td>";
                echo "<td class='left'>" . HTML::resume_text($data['comment'], 100) . "</td>";

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
                    PluginDporegisterPersonalDataCategory::getTypeName($nb),
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
     * Show the current (or new) object formulaire
     * 
     * @param Integer $ID
     * @param Array $options
     */
    function showForm($ID, $options = [])
    {
        $processingId = $options[self::$items_id_1];

        $this->getFromDB($ID);

        $colsize1 = '13';
        $colsize2 = '29';
        $colsize3 = '13';
        $colsize4 = '45';

        if (!PluginDporegisterProcessing::canView()) {
            return false;
        }

        $canedit = PluginDporegisterProcessing::canUpdate();

        echo "<div class='firstbloc'>";
        echo "<form name='ticketitem_form' id='ticketitem_form' method='post'
            action='" . Toolbox::getItemTypeFormURL(__class__) . "'>";

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='headerRow'>";
        echo "<th colspan='4'>" . (($ID < 1) ? __('Add a Personal Data', 'dporegister') : __('Edit a Personal Data', 'dporegister')) .
            "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='left' width='$colsize1%'><label>" . __('Personal Data Category', 'dporegister') . "</label></td><td width='$colsize2%'>";
        PluginDporegisterPersonalDataCategory::dropdown([
            'name' => self::$items_id_2,
            'value' => (array_key_exists(self::$items_id_2, $this->fields) ? $this->fields[self::$items_id_2] : '')
        ]);
        echo "<td class='left' width='$colsize1%'><label>" . __('Comment') . "</label></td><td width='$colsize2%'>";
        echo "<textarea style='width:98%' maxlength=250 name='comment' rows='3'>" 
            . (array_key_exists('comment', $this->fields) ? $this->fields['comment'] : '')
            . "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='left' width='$colsize1%'><label>" . __('Source', 'dporegister') . "</label></td><td width='$colsize2%'>";
        $params = [];
        if (array_key_exists('source', $this->fields)) {
            $params['value'] = $this->fields['source'];
        }
        self::dropdownSources('source', $params);
        echo "<td class='left' width='$colsize1%'><label>" . __('Destination', 'dporegister') . "</label></td><td width='$colsize2%'>";
        echo "<input type='text' style='width:98%' maxlength=250 name='destination' required='required' value='" . (array_key_exists('destination', $this->fields) ? $this->fields['destination'] : '') . "'>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='left' width='$colsize1%'><label>" . __('Retention Schedule', 'dporegister') . "</label></td><td width='$colsize2%'>";

        echo "<input type='checkbox' name='retentionschedule_contract' 
            id='retentionschedule_contract' onclick='if (this.checked) this.value=1; else this.value=0;' "
            . (array_key_exists('retentionschedule_contract', $this->fields) && $this->fields['retentionschedule_contract'] == 1 ? "checked='checked' value='1'" : '')
            . ">" . __('Duration of the contract', 'dporegister');

        $params = ['checked' => '__VALUE__'];
        Ajax::updateItemOnEvent(
            'retentionschedule_contract',
            'retentionschedule',
            '../ajax/processing_personaldatacategory_update_retentionschedule.php',
            $params
        );

        echo "<div id='retentionschedule'>";
        if ($ID < 1 || array_key_exists('retentionschedule_contract', $this->fields) && $this->fields['retentionschedule_contract'] != 1) {

            self::showRetentionScheduleInputs($this->fields);
        }
        echo "</div>";

        echo "<td class='left' width='$colsize1%'><label>" . __('Location') . "</label></td><td width='$colsize2%'>";
        echo "<input type='text' style='width:98%' maxlength=250 name='location' required='required' value='" . (array_key_exists('location', $this->fields) ? $this->fields['location'] : '') . "'>";
        echo "</td></tr>";

        echo "<tr><td class='left' width='$colsize1%'><label>" . __('Third Countries transfert', 'dporegister') . "</label></td><td width='$colsize2%'>";
        echo "<input type='checkbox' name='thirdcountriestransfert' 
            id='thirdcountriestransfert' onclick='if (this.checked) this.value=1; else this.value=0;' "
            . (array_key_exists('thirdcountriestransfert', $this->fields) && $this->fields['thirdcountriestransfert'] == 1 ? "checked='checked' value='1'" : '')
            . ">&nbsp;";

        $params = [
            'checked' => '__VALUE__'
        ];

        if(array_key_exists('thirdcountriestransfert_value', $this->fields)) {
            $params['thirdcountriestransfert_value'] = $this->fields['thirdcountriestransfert_value'];
        }

        Ajax::updateItemOnEvent(
            'thirdcountriestransfert',
            'thirdcountriestransfert_div',
            '../ajax/processing_personaldatacategory_update_thirdcountriestransfert.php',
            $params
        );

        echo "<div id='thirdcountriestransfert_div'>";
        if (array_key_exists('thirdcountriestransfert', $this->fields) && $this->fields['thirdcountriestransfert'] == 1) {

            self::showThirdCountriesTransfertInputs($this->fields);
        }
        echo "</div></td>";
        echo "</tr>";

        echo "<tr><td class='center' colspan='4'>";
        echo "<input type='hidden' name='".self::$items_id_1."' value='$processingId' />";

        if ($ID > 0) {
            echo "<input type='hidden' name='id' value='$ID' />";
        }

        echo "<input type='submit' name='" . ($ID < 1 ? 'add' : 'update') . "' value=\"" .
            _sx('button', ($ID < 1 ? 'Add' : 'Update')) . "\" class='submit'>";
        echo "</td></tr>";

        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    /**
     * 
     */
    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $tab[] = [
            'id' => 'personaldatacategory',
            'name' => PluginDporegisterPersonalDataCategory::getTypeName(0)
        ];

        $tab[] = [
            'id' => '21',
            'table' => PluginDporegisterPersonalDataCategory::getTable(),
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

        $tab[] = [
            'id' => '22',
            'table' => PluginDporegisterPersonalDataCategory::getTable(),
            'field' => 'is_sensible',
            'name' => __('Sensible', 'dporegister'),
            'forcegroupby' => true,
            'massiveaction' => false,
            'datatype' => 'bool',
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

    // --------------------------------------------------------------------
    //  SPECIFICS FOR THE CURRENT OBJECT CLASS
    // --------------------------------------------------------------------

    /**
     * Get Sources possibilities or the full name of the specified index
     * 
     * @param String $index
     * 
     * @return String|Array
     */
    static function getSources($index = null)
    {
        $options = [
            'dir' => __('Direct', 'dporegister'),
            'ind' => __('Indirect', 'dporegister')
        ];

        if ($index && array_key_exists($index, $options)) {
            return $options[$index];
        }

        return $options;
    }

    /**
     * Show (or retreive) the dropdown about the sources
     * 
     * @param String $name Name for the form input
     * @param Array $options
     * 
     * @return String
     * @see self::getSources
     */
    static function dropdownSources($name, $options = [])
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
        if (is_array($params['toadd']) && count($params['toadd'])) {
            $items = $params['toadd'];
        }

        $items += self::getSources();

        return Dropdown::showFromArray($name, $items, $params);
    }

    /**
     * Get Retention schedule scales or the full name of the
     * specified index
     * 
     * @param String $index
     * 
     * @return String|Array
     */
    static function getRetentionScheduleScales($index = null, $nb = 1)
    {
        $options = [
            'y' => _n('Year', 'Years', $nb, 'dporegister'),
            'm' => _n('Month', 'Months', $nb, 'dporegister'),
            'd' => _n('Day', 'Days', $nb, 'dporegister'),
            'h' => _n('Hour', 'Hours', $nb, 'dporegister')
        ];

        if ($index && array_key_exists($index, $options)) {
            return $options[$index];
        }

        return $options;
    }

    /**
     * Show (or retreive) the dropdown about the Retention schedule scales
     * 
     * @param String $name Name for the form input
     * @param Array $options
     * 
     * @return String
     * @see self::getRetentionScheduleScales
     */
    static function dropdownRetentionScheduleScales($name, $options = [])
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
        if (is_array($params['toadd']) && count($params['toadd'])) {
            $items = $params['toadd'];
        }

        $items += self::getRetentionScheduleScales();

        return Dropdown::showFromArray($name, $items, $params);
    }

    /**
     * Display (or retreive) the full string of the Retention Schedule Scale
     * 
     * @param Array $data
     * @param Boolean $display
     * 
     * @return String
     */
    static function showRetentionSchedule($data, $display = true)
    {
        $string = ($data['retentionschedule_contract'] != '1'
            ? ($data['retentionschedule_value'] . "&nbsp;" . self::getRetentionScheduleScales($data['retentionschedule_scale'], $data['retentionschedule_value'])
                . ($data['retentionschedule_aftercontract'] == 1 ? '&nbsp;' . __('after the end of the contract', 'dporegister') : ''))
            : __('Duration of the contract', 'dporegister'));

        if ($display) {
            echo $string;
        } else {
            return $string;
        }
    }

    /**
     * Display (or retreive) the full string of the Third Countries Transfert
     * 
     * @param Array $data
     * @param Boolean $display
     * 
     * @return String
     */
    static function showThirdCountriesTransfert($data, $display = true)
    {
        $string = ($data['thirdcountriestransfert'] == '1'
            ? $data['thirdcountriestransfert_value']
            : __('No'));

        if ($display) {
            echo $string;
        } else {
            return $string;
        }
    }

    // --------------------------------------------------------------------
    //  SPECIFICS AJAX CALL
    // --------------------------------------------------------------------

    /**
     * Show the Retention Schedule Scale HTML inputs
     * 
     * @param Array $data
     */
    static function showRetentionScheduleInputs($data = [])
    {
        echo "<input type='number' name='retentionschedule_value' " . (array_key_exists('retentionschedule_value', $data) ? "value='" . $data['retentionschedule_value'] . "'" : '') . ">&nbsp;";

        $params = [];
        if (array_key_exists('retentionschedule_scale', $data)) {
            $params['value'] = $data['retentionschedule_scale'];
        }

        self::dropdownRetentionScheduleScales('retentionschedule_scale', $params);

        echo "<br/><input type='checkbox' name='retentionschedule_aftercontract' 
            id='retentionschedule_aftercontract' onclick='if (this.checked) this.value=1; else this.value=0;' "
            . (array_key_exists('retentionschedule_aftercontract', $data) && $data['retentionschedule_aftercontract'] == 1 ? "checked='checked' value='1'" : '')
            . ">" . __('After the end of the contract', 'dporegister');
    }

    /**
     * Show the Third Countries Transfert HTML inputs
     * 
     * @param Array $data
     */
    static function showThirdCountriesTransfertInputs($data = [])
    {
        echo "<input type='text' style='width:75%' maxlength=160 name='thirdcountriestransfert_value' required='required' " .
            (
                array_key_exists('thirdcountriestransfert_value', $data) 
                ? "value='" . $data['thirdcountriestransfert_value'] . "'" 
                : ''
            ) . ">";
    }
}

// Emulate static constructor
PluginDporegisterProcessing_PersonalDataCategory::init();
