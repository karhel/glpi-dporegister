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

class PluginDporegisterPiaModel extends CommonDBTM
{
    static $rightname = 'plugin_dporegister_piamodel';

    // --------------------------------------------------------------------
    //  PLUGIN MANAGEMENT - DATABASE INITIALISATION
    // --------------------------------------------------------------------

    /**
     * Install or update PluginDporegisterPiaModel
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
        $processing = PluginDporegisterProcessing::getForeignKeyField();

        if (!TableExists($table)) {

            $query = "CREATE TABLE `$table` (
                `id` int(11) NOT NULL auto_increment,
                `$processing` int(11) NOT NULL COMMENT 'RELATION to plugin_dporegister_processings (id)',
                `date` datetime default NULL,
                `date_creation` datetime default NULL,
                `users_id_recipient` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',
                `date_mod` datetime default NULL,
                `users_id_lastupdater` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',
                `documents_id` int(11) default NULL COMMENT 'RELATION to glpi_documents (id)',
                `status` int(11) NOT NULL default '0',
                `comment` varchar(250) NOT NULL default '',

                PRIMARY KEY  (`id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

            $DB->query($query) or die("error creating $table " . $DB->error());
        }
    }

    /**
     * Uninstall PluginDporegisterPiaModel
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

        $query = "DELETE FROM `glpi_logs` WHERE `itemtype` = '" . __class__ . "'";
        $DB->query($query) or die("error purge logs table");
    }

    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

    //! @copydoc CommonGLPI::getTypeName($nb)
    static function getTypeName($nb = 0)
    {
        return _n('Privacy Impact Assessment', 'Privacy Impact Assessments', $nb, 'dporegister');
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

        $pID = $processing->fields['id'];
        $pFK = PluginDporegisterProcessing::getForeignKeyField();

        $piaObject = new self();
        $result = $piaObject->find("`$pFK` = $pID");

        $canedit = self::canUpdate();
        $rand = mt_rand();

        if ($result) {

            echo "<div class='center firstbloc'>";
            echo "<div id='viewpiamodel" . $pFK . "_$rand'></div>";
            
            echo "<a class='vsubmit' id='' href='javascript:'>" .
                __('Add a new PIA', 'dporegister') . "</a>";
            
            echo "</div>";

            $number = count($result);

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

            $header_end .= "<th>" . __('Creation date') . "</th>";
            $header_end .= "<th>" . __('By') . "</th>";
            $header_end .= "<th>" . __('Last update') . "</th>";
            $header_end .= "<th>" . __('Status', 'dporegister') . "</th>";
            $header_end .= "<th>" . __('Document') . "</th>";
            $header_end .= "<th>" . __('Comment') . "</th>";
            $header_end .= "<th>" . __('Actions') . "</th>";
            echo $header_begin . $header_top . $header_end . "</tr>";
            
            foreach($result as $data) {

                $document = null;
                if($data['documents_id']) {

                    $document = new Document();
                    $document->getFromDB($data['documents_id']);
                }

                echo "<tr class='tab_bg_1'>";

                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__class__, $data["id"]);
                    echo "</td>";
                }

                echo "<td class='center'>" . Html::convDateTime($data['date_creation']) . "</td>";
                echo "<td class='center'>" . getUserName($data["users_id_recipient"], false) . "</td>";
                echo "<td class='center'>" . Html::convDateTime($data['date_mod']) . "</td>";
                echo "<td class='center'>" . $data['status'] . "</td>";
                echo "<td class='center'>" . ($document ? $document->getDownloadLink() : '') . "</td>";
                echo "<td class='center'>" . HTML::resume_text($data['comment'], 100) . "</td>";
                echo "<td class='center'>";

                if ($canedit) {
                    echo "<a href='#' onClick=\"viewEditPiaModel" . $pFK . "_" . $data['id'] . "_$rand()\"
                         class='vsubmit'>";
                    echo __('Display or Edit', 'dporegister');
                    echo "</a>";

                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditPiaModel" . $pFK . "_" . $data['id'] . "_$rand() {\n";

                    $params = [
                        PluginDporegisterProcessing::getForeignKeyField() => $pFK,
                        'id' => $data["id"]
                    ];

                    Ajax::updateItemJsCode(
                        "viewpiamodel" . $pFK . "_$rand",
                        "../ajax/piamodel_view_subitem.php",
                        $params
                    );

                    echo "$('#viewAddPiaModel').show();";

                    echo "};";
                    echo "</script>\n";
                }

                echo "</td>";                
                echo "</tr>";
            }

            echo "</table>";

            if ($canedit && $number) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
            echo "test";
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
                            PluginDporegisterProcessing::getForeignKeyField() => $item->getID()
                        ]
                    );
                }

                return self::createTabEntry(self::getTypeName($nb), $nb);
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
        var_dump($options);

    }
}