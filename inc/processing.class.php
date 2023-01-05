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

class PluginDporegisterProcessing extends CommonDBTM
{
    static $rightname = 'plugin_dporegister_processing';
    public $dohistory = true;
    protected $usenotepad = true;

    public $userlinkclass = 'PluginDporegisterProcessing_User';
    public $supplierlinkclass = 'PluginDporegisterProcessing_Supplier';

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

        if (!$DB->tableExists($table)) {

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

            // `users_id_jointcontroller` int(11) default NULL COMMENT 'RELATION to glpi_users (id)',

            $DB->query($query) or die("error creating $table " . $DB->error());

            // Insert default display preferences for Processing objects
            $query = "INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES
                ('" . __CLASS__ . "', 1, 1, 0),
                ('" . __CLASS__ . "', 2, 2, 0),
                ('" . __CLASS__ . "', 3, 3, 0),
                ('" . __CLASS__ . "', 4, 4, 0),
                ('" . __CLASS__ . "', 5, 5, 0),
                ('" . __CLASS__ . "', 7, 7, 0),
                ('" . __CLASS__ . "', 8, 8, 0),
                ('" . __CLASS__ . "', 9, 9, 0)";

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

        if ($DB->tableExists($table)) {

            $query = "DROP TABLE `$table`";
            $DB->query($query) or die("error deleting $table " . $DB->error());
        }

        // Purge display preferences table
        $query = "DELETE FROM `glpi_displaypreferences` WHERE `itemtype` = '" . __CLASS__ . "'";
        $DB->query($query) or die('error purge display preferences table' . $DB->error());

        // Purge logs table
        $query = "DELETE FROM `glpi_logs` WHERE `itemtype` = '" . __CLASS__ . "'";
        $DB->query($query) or die('error purge logs table' . $DB->error());

        // Delete links with documents
        $query = "DELETE FROM `glpi_documents_items` WHERE `itemtype` = '" . __CLASS__ . "'";
        $DB->query($query) or die('error purge documents_items table' . $DB->error());

        // Delete notes associated to processings
        $query = "DELETE FROM `glpi_notepads` WHERE `itemtype` = '" . __CLASS__ . "'";
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
                return CommonITILObject::getStatusIcon($values[$field])
                    . '&nbsp;' . CommonITILObject::getStatus($values[$field]);

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
    //! @copydoc CommonITILObject::prepareInputForUpdate($input)
    function prepareInputForUpdate($input) {

        // Add document if needed
        $this->getFromDB($input["id"]); // entities_id field required
  
        if (isset($input["document"]) && ($input["document"] > 0)) {
           $doc = new Document();
           if ($doc->getFromDB($input["document"])) {
              $docitem = new Document_Item();
              if ($docitem->add(['documents_id' => $input["document"],
                                      'itemtype'     => $this->getType(),
                                      'items_id'     => $input["id"]])) {
                 // Force date_mod of tracking
                 $input["date_mod"]     = $_SESSION["glpi_currenttime"];
                 $input['_doc_added'][] = $doc->fields["name"];
              }
           }
           unset($input["document"]);
        }
  
        if (isset($input["date"]) && empty($input["date"])) {
           unset($input["date"]);
        }
  
        if (isset($input["closedate"]) && empty($input["closedate"])) {
           unset($input["closedate"]);
        }
  
        if (isset($input["solvedate"]) && empty($input["solvedate"])) {
           unset($input["solvedate"]);
        }
  
        // "do not compute" flag set by business rules for "takeintoaccount_delay_stat" field
        $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($input);
  
        if (isset($input['_itil_requester'])) {
           if (isset($input['_itil_requester']['_type'])) {
              $input['_itil_requester'] = [
                 'type'                            => CommonITILActor::REQUESTER,
                 $this->getForeignKeyField()       => $input['id'],
                 '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                 '_from_object'                    => true,
              ] + $input['_itil_requester'];
  
              switch ($input['_itil_requester']['_type']) {
                 case "user" :
                    if (isset($input['_itil_requester']['use_notification'])
                        && is_array($input['_itil_requester']['use_notification'])) {
                       $input['_itil_requester']['use_notification'] = $input['_itil_requester']['use_notification'][0];
                    }
                    if (isset($input['_itil_requester']['alternative_email'])
                        && is_array($input['_itil_requester']['alternative_email'])) {
                       $input['_itil_requester']['alternative_email'] = $input['_itil_requester']['alternative_email'][0];
                    }
  
                    if (!empty($this->userlinkclass)) {
                       if (isset($input['_itil_requester']['alternative_email'])
                           && $input['_itil_requester']['alternative_email']
                           && !NotificationMailing::isUserAddressValid($input['_itil_requester']['alternative_email'])) {
  
                          $input['_itil_requester']['alternative_email'] = '';
                          Session::addMessageAfterRedirect(__('Invalid email address'), false, ERROR);
                       }
  
                       if ((isset($input['_itil_requester']['alternative_email'])
                            && $input['_itil_requester']['alternative_email'])
                           || ($input['_itil_requester']['users_id'] > 0)) {
  
                          $useractors = new $this->userlinkclass();
                          if (isset($input['_auto_update'])
                              || $useractors->can(-1, CREATE, $input['_itil_requester'])) {
                             $useractors->add($input['_itil_requester']);
                             $input['_forcenotif']                     = true;
                          }
                       }
                    }
                    break;
  
                 case "group" :
                    if (!empty($this->grouplinkclass)
                        && ($input['_itil_requester']['groups_id'] > 0)) {
                       $groupactors = new $this->grouplinkclass();
                       if (isset($input['_auto_update'])
                           || $groupactors->can(-1, CREATE, $input['_itil_requester'])) {
                          $groupactors->add($input['_itil_requester']);
                          $input['_forcenotif']                     = true;
                       }
                    }
                    break;
              }
           }
        }
  
        if (isset($input['_itil_observer'])) {
           if (isset($input['_itil_observer']['_type'])) {
              $input['_itil_observer'] = [
                 'type'                            => CommonITILActor::OBSERVER,
                 $this->getForeignKeyField()       => $input['id'],
                 '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                 '_from_object'                    => true,
              ] + $input['_itil_observer'];
  
              switch ($input['_itil_observer']['_type']) {
                 case "user" :
                    if (isset($input['_itil_observer']['use_notification'])
                        && is_array($input['_itil_observer']['use_notification'])) {
                       $input['_itil_observer']['use_notification'] = $input['_itil_observer']['use_notification'][0];
                    }
                    if (isset($input['_itil_observer']['alternative_email'])
                        && is_array($input['_itil_observer']['alternative_email'])) {
                       $input['_itil_observer']['alternative_email'] = $input['_itil_observer']['alternative_email'][0];
                    }
  
                    if (!empty($this->userlinkclass)) {
                       if (isset($input['_itil_observer']['alternative_email'])
                           && $input['_itil_observer']['alternative_email']
                           && !NotificationMailing::isUserAddressValid($input['_itil_observer']['alternative_email'])) {
  
                          $input['_itil_observer']['alternative_email'] = '';
                          Session::addMessageAfterRedirect(__('Invalid email address'), false, ERROR);
                       }
                       if ((isset($input['_itil_observer']['alternative_email'])
                            && $input['_itil_observer']['alternative_email'])
                           || ($input['_itil_observer']['users_id'] > 0)) {
                          $useractors = new $this->userlinkclass();
                          if (isset($input['_auto_update'])
                             || $useractors->can(-1, CREATE, $input['_itil_observer'])) {
                             $useractors->add($input['_itil_observer']);
                             $input['_forcenotif']                    = true;
                          }
                       }
                    }
                    break;
  
                 case "group" :
                    if (!empty($this->grouplinkclass)
                         && ($input['_itil_observer']['groups_id'] > 0)) {
                       $groupactors = new $this->grouplinkclass();
                       if (isset($input['_auto_update'])
                           || $groupactors->can(-1, CREATE, $input['_itil_observer'])) {
                          $groupactors->add($input['_itil_observer']);
                          $input['_forcenotif']                    = true;
                       }
                    }
                    break;
              }
           }
        }
  
        if (isset($input['_itil_assign'])) {
           if (isset($input['_itil_assign']['_type'])) {
              $input['_itil_assign'] = [
                 'type'                            => CommonITILActor::ASSIGN,
                 $this->getForeignKeyField()       => $input['id'],
                 '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                 '_from_object'                    => true,
              ] + $input['_itil_assign'];
  
              if (isset($input['_itil_assign']['use_notification'])
                    && is_array($input['_itil_assign']['use_notification'])) {
                 $input['_itil_assign']['use_notification'] = $input['_itil_assign']['use_notification'][0];
              }
              if (isset($input['_itil_assign']['alternative_email'])
                    && is_array($input['_itil_assign']['alternative_email'])) {
                 $input['_itil_assign']['alternative_email'] = $input['_itil_assign']['alternative_email'][0];
              }
  
              switch ($input['_itil_assign']['_type']) {
                 case "user" :
                    if (!empty($this->userlinkclass)
                        && ((isset($input['_itil_assign']['alternative_email'])
                             && $input['_itil_assign']['alternative_email'])
                            || $input['_itil_assign']['users_id'] > 0)) {
                       $useractors = new $this->userlinkclass();
                       if (isset($input['_auto_update'])
                           || $useractors->can(-1, CREATE, $input['_itil_assign'])) {
                          $useractors->add($input['_itil_assign']);
                          $input['_forcenotif']                  = true;
                          if (((!isset($input['status'])
                               && in_array($this->fields['status'], $this->getNewStatusArray()))
                              || (isset($input['status'])
                                  && in_array($input['status'], $this->getNewStatusArray())))
                              && !$this->isStatusComputationBlocked($input)) {
                             if (in_array(self::ASSIGNED, array_keys($this->getAllStatusArray()))) {
                                $input['status'] = self::ASSIGNED;
                             }
                          }
                       }
                    }
                    break;
  
                 case "group" :
                    if (!empty($this->grouplinkclass)
                        && ($input['_itil_assign']['groups_id'] > 0)) {
                       $groupactors = new $this->grouplinkclass();
  
                       if (isset($input['_auto_update'])
                           || $groupactors->can(-1, CREATE, $input['_itil_assign'])) {
                          $groupactors->add($input['_itil_assign']);
                          $input['_forcenotif']                  = true;
                          if (((!isset($input['status'])
                               && (in_array($this->fields['status'], $this->getNewStatusArray())))
                              || (isset($input['status'])
                                  && (in_array($input['status'], $this->getNewStatusArray()))))
                              && !$this->isStatusComputationBlocked($input)) {
                             if (in_array(self::ASSIGNED, array_keys($this->getAllStatusArray()))) {
                                $input['status'] = self::ASSIGNED;
                             }
                          }
                       }
                    }
                    break;
  
                 case "supplier" :
                    if (!empty($this->supplierlinkclass)
                        && ((isset($input['_itil_assign']['alternative_email'])
                             && $input['_itil_assign']['alternative_email'])
                            || $input['_itil_assign']['suppliers_id'] > 0)) {
                       $supplieractors = new $this->supplierlinkclass();
                       if (isset($input['_auto_update'])
                           || $supplieractors->can(-1, CREATE, $input['_itil_assign'])) {
                          $supplieractors->add($input['_itil_assign']);
                          $input['_forcenotif']                  = true;
                          if (((!isset($input['status'])
                               && (in_array($this->fields['status'], $this->getNewStatusArray())))
                              || (isset($input['status'])
                                  && (in_array($input['status'], $this->getNewStatusArray()))))
                              && !$this->isStatusComputationBlocked($input)) {
                             if (in_array(self::ASSIGNED, array_keys($this->getAllStatusArray()))) {
                                $input['status'] = self::ASSIGNED;
                             }
  
                          }
                       }
                    }
                    break;
              }
           }
        }
  
        $this->addAdditionalActors($input);
  
        // set last updater if interactive user
        if (!Session::isCron()) {
           $input['users_id_lastupdater'] = Session::getLoginUserID();
        }
  
        $solvedclosed = array_merge(
           $this->getSolvedStatusArray(),
           $this->getClosedStatusArray()
        );
  
        if (isset($input["status"])
            && !in_array($input["status"], $solvedclosed)) {
           $input['solvedate'] = 'NULL';
        }
  
        if (isset($input["status"]) && !in_array($input["status"], $this->getClosedStatusArray())) {
           $input['closedate'] = 'NULL';
        }
  
        // Setting a solution type means the ticket is solved
        if (isset($input["solutiontypes_id"])
            && (!isset($input['status']) || !in_array($input["status"], $solvedclosed))) {
           $solution = new ITILSolution();
           $soltype = new SolutionType();
           $soltype->getFromDB($input['solutiontypes_id']);
           $solution->add([
              'itemtype'           => $this->getType(),
              'items_id'           => $this->getID(),
              'solutiontypes_id'   => $input['solutiontypes_id'],
              'content'            => 'Solved using type ' . $soltype->getName()
           ]);
        }
  
        return $input;
     }
     //! @copydoc CommonITILObject::addAdditionalActors($input)
     private function addAdditionalActors($input) {

        $useractors = null;
        // Add user groups linked to ITIL objects
        if (!empty($this->userlinkclass)) {
           $useractors = new $this->userlinkclass();
        }
        $groupactors = null;
        if (!empty($this->grouplinkclass)) {
           $groupactors = new $this->grouplinkclass();
        }
        $supplieractors = null;
        if (!empty($this->supplierlinkclass)) {
           $supplieractors = new $this->supplierlinkclass();
        }
  
        // "do not compute" flag set by business rules for "takeintoaccount_delay_stat" field
        $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($input);
  
        // Additional groups actors
        if (!is_null($groupactors)) {
           $group_input = [
              $groupactors->getItilObjectForeignKey() => $this->fields['id'],
              '_do_not_compute_takeintoaccount'       => $do_not_compute_takeintoaccount,
              '_from_object'                          => true,
           ];
  
           // Requesters
           if (isset($input['_additional_groups_requesters'])
               && is_array($input['_additional_groups_requesters'])
               && count($input['_additional_groups_requesters'])) {
              foreach ($input['_additional_groups_requesters'] as $tmp) {
                 if ($tmp > 0) {
                    $groupactors->add(
                       [
                          'type'      => CommonITILActor::REQUESTER,
                          'groups_id' => $tmp,
                       ] + $group_input
                    );
                 }
              }
           }
  
           // Observers
           if (isset($input['_additional_groups_observers'])
               && is_array($input['_additional_groups_observers'])
               && count($input['_additional_groups_observers'])) {
              foreach ($input['_additional_groups_observers'] as $tmp) {
                 if ($tmp > 0) {
                    $groupactors->add(
                       [
                          'type'      => CommonITILActor::OBSERVER,
                          'groups_id' => $tmp,
                       ] + $group_input
                    );
                 }
              }
           }
  
           // Assigns
           if (isset($input['_additional_groups_assigns'])
               && is_array($input['_additional_groups_assigns'])
               && count($input['_additional_groups_assigns'])) {
              foreach ($input['_additional_groups_assigns'] as $tmp) {
                 if ($tmp > 0) {
                    $groupactors->add(
                       [
                          'type'      => CommonITILActor::ASSIGN,
                          'groups_id' => $tmp,
                       ] + $group_input
                    );
                 }
              }
           }
        }
  
        // Additional suppliers actors
        if (!is_null($supplieractors)) {
           $supplier_input = [
              $supplieractors->getItilObjectForeignKey() => $this->fields['id'],
              '_do_not_compute_takeintoaccount'          => $do_not_compute_takeintoaccount,
              '_from_object'                             => true,
           ];
  
           // Assigns
           if (isset($input['_additional_suppliers_assigns'])
               && is_array($input['_additional_suppliers_assigns'])
               && count($input['_additional_suppliers_assigns'])) {
  
              $input2 = [
                 'type' => CommonITILActor::ASSIGN,
              ] + $supplier_input;
  
              foreach ($input["_additional_suppliers_assigns"] as $tmp) {
                 if (isset($tmp['suppliers_id'])) {
                    foreach ($tmp as $key => $val) {
                       $input2[$key] = $val;
                    }
                    $supplieractors->add($input2);
                 }
              }
           }
        }
  
        // Additional actors : using default notification parameters
        if (!is_null($useractors)) {
           $user_input = [
              $useractors->getItilObjectForeignKey() => $this->fields['id'],
              '_do_not_compute_takeintoaccount'      => $do_not_compute_takeintoaccount,
              '_from_object'                         => true,
           ];
  
           // Observers : for mailcollector
           if (isset($input["_additional_observers"])
               && is_array($input["_additional_observers"])
               && count($input["_additional_observers"])) {
  
              $input2 = [
                 'type' => CommonITILActor::OBSERVER,
              ] + $user_input;
  
              foreach ($input["_additional_observers"] as $tmp) {
                 if (isset($tmp['users_id'])) {
                    foreach ($tmp as $key => $val) {
                       $input2[$key] = $val;
                    }
                    $useractors->add($input2);
                 }
              }
           }
  
           if (isset($input["_additional_assigns"])
               && is_array($input["_additional_assigns"])
               && count($input["_additional_assigns"])) {
  
              $input2 = [
                 'type' => CommonITILActor::ASSIGN,
              ] + $user_input;
  
              foreach ($input["_additional_assigns"] as $tmp) {
                 if (isset($tmp['users_id'])) {
                    foreach ($tmp as $key => $val) {
                       $input2[$key] = $val;
                    }
                    $useractors->add($input2);
                 }
              }
           }
           if (isset($input["_additional_requesters"])
               && is_array($input["_additional_requesters"])
               && count($input["_additional_requesters"])) {
  
              $input2 = [
                 'type' => CommonITILActor::REQUESTER,
              ] + $user_input;
  
              foreach ($input["_additional_requesters"] as $tmp) {
                 if (isset($tmp['users_id'])) {
                    foreach ($tmp as $key => $val) {
                       $input2[$key] = $val;
                    }
                    $useractors->add($input2);
                 }
              }
           }
        }
     }

    //! @copydoc HTML::setSimpleTextContent($content)
    static function setSimpleTextContent($content) {

        $content = Html::entity_decode_deep($content);
        $content = self::convertImageToTag($content);
  
        // If is html content
        if ($content != strip_tags($content)) {
           $content = Toolbox::getHtmlToDisplay($content);
        }
  
        return $content;
     }

     //! @copydoc Toolbox::convertImageToTag($content_html, $force_update = false)
     static function convertImageToTag($content_html, $force_update = false) {

        if (!empty($content_html)) {
           preg_match_all("/alt\s*=\s*['|\"](.+?)['|\"]/", $content_html, $matches, PREG_PATTERN_ORDER);
           if (isset($matches[1]) && count($matches[1])) {
              // Get all image src
              foreach ($matches[1] as $src) {
                 // Set tag if image matches
                 $content_html = preg_replace(["/<img.*alt=['|\"]".$src."['|\"][^>]*\>/", "/<object.*alt=['|\"]".$src."['|\"][^>]*\>/"], Document::getImageTag($src), $content_html);
              }
           }
  
           return $content_html;
        }
     }

      //! @copydoc CommonITILObject::showUsersAssociated($type, $canedit, array $options = [])
      function showUsersAssociated($type, $canedit, array $options = []) {
        global $CFG_GLPI;
  
        $showuserlink = 0;
        if (User::canView()) {
           $showuserlink = 2;
        }
        $usericon  = self::getActorIcon('user', $type);
        $user      = new User();
        $linkuser  = new $this->userlinkclass();
  
        $itemtype  = $this->getType();
        $typename  = CommonITILObject::getActorFieldNameType($type);
  
        $candelete = true;
        $mandatory = '';
        // For ticket templates : mandatories
        if (($itemtype == 'Ticket')
            && isset($options['_tickettemplate'])) {
           $mandatory = $options['_tickettemplate']->getMandatoryMark("_users_id_".$typename);
           if ($options['_tickettemplate']->isMandatoryField("_users_id_".$typename)
               && isset($this->users[$type]) && (count($this->users[$type])==1)) {
              $candelete = false;
           }
        }
  
        if (isset($this->users[$type]) && count($this->users[$type])) {
           foreach ($this->users[$type] as $d) {
              echo "<div class='actor_row'>";
              $k = $d['users_id'];
  
              echo "$mandatory$usericon&nbsp;";
  
              if ($k) {
                 $userdata = getUserName($k, 2);
              } else {
                 $email         = $d['alternative_email'];
                 $userdata      = "<a href='mailto:$email'>$email</a>";
              }
  
              if ($k) {
                 $param = ['display' => false];
                 if ($showuserlink) {
                    $param['link'] = $userdata["link"];
                 }
                 echo $userdata['name']."&nbsp;".Html::showToolTip($userdata["comment"], $param);
              } else {
                 echo $userdata;
              }
  
              if ($CFG_GLPI['notifications_mailing']) {
                 $text = __('Email followup')."&nbsp;".Dropdown::getYesNo($d['use_notification']).
                         '<br>';
  
                 if ($d['use_notification']) {
                    $uemail = $d['alternative_email'];
                    if (empty($uemail) && $user->getFromDB($d['users_id'])) {
                       $uemail = $user->getDefaultEmail();
                    }
                    $text .= sprintf(__('%1$s: %2$s'), __('Email'), $uemail);
                    if (!NotificationMailing::isUserAddressValid($uemail)) {
                       $text .= "&nbsp;<span class='red'>".__('Invalid email address')."</span>";
                    }
                 }
  
                 if ($canedit
                     || ($d['users_id'] == Session::getLoginUserID())) {
                    $opt      = ['awesome-class' => 'fa-envelope',
                                      'popup' => $linkuser->getFormURLWithID($d['id'])];
                    echo "&nbsp;";
                    Html::showToolTip($text, $opt);
                 }
              }
  
              if ($canedit && $candelete) {
                 Html::showSimpleForm($linkuser->getFormURL(), 'delete',
                                      _x('button', 'Delete permanently'),
                                      ['id' => $d['id']],
                                      'fa-times-circle');
              }
              echo "</div>";
           }
        }
     }

      //! @copydoc CommonITILObject::getActorIcon($user_group, $type)
      static function getActorIcon($user_group, $type) {
        global $CFG_GLPI;
  
        switch ($user_group) {
           case 'user' :
              $icontitle = __s('User').' - '.$type; // should never be used
              switch ($type) {
                 case CommonITILActor::REQUESTER :
                    $icontitle = __s('Requester user');
                    break;
  
                 case CommonITILActor::OBSERVER :
                    $icontitle = __s('Watcher user');
                    break;
  
                 case CommonITILActor::ASSIGN :
                    $icontitle = __s('Technician');
                    break;
              }
              return "<i class='fas fa-user' title='$icontitle'></i><span class='sr-only'>$icontitle</span>";
  
           case 'group' :
              $icontitle = __('Group');
              switch ($type) {
                 case CommonITILActor::REQUESTER :
                    $icontitle = __s('Requester group');
                    break;
  
                 case CommonITILActor::OBSERVER :
                    $icontitle = __s('Watcher group');
                    break;
  
                 case CommonITILActor::ASSIGN :
                    $icontitle = __s('Group in charge of the ticket');
                    break;
              }
  
              return "<i class='fas fa-users' title='$icontitle'></i>" .
                  "<span class='sr-only'>$icontitle</span>";
  
           case 'supplier' :
              $icontitle = __('Supplier');
              return  "<img src='".$CFG_GLPI['root_doc']."/pics/supplier.png'
                        alt=\"$icontitle\" title=\"$icontitle\">";
  
        }
        return '';
  
     }

     //! @copydoc CommonITILObject::showSuppliersAssociated($type, $canedit, array $options = [])
     function showSuppliersAssociated($type, $canedit, array $options = []) {
        global $CFG_GLPI;
  
        $showsupplierlink = 0;
        if (Session::haveRight('contact_enterprise', READ)) {
           $showsupplierlink = 2;
        }
  
        $suppliericon = self::getActorIcon('supplier', $type);
        $supplier     = new Supplier();
        $linksupplier = new $this->supplierlinkclass();
  
        $itemtype     = $this->getType();
        $typename     = self::getActorFieldNameType($type);
  
        $candelete    = true;
        $mandatory    = '';
        // For ticket templates : mandatories
        if (($itemtype == 'Ticket')
            && isset($options['_tickettemplate'])) {
           $mandatory = $options['_tickettemplate']->getMandatoryMark("_suppliers_id_".$typename);
           if ($options['_tickettemplate']->isMandatoryField("_suppliers_id_".$typename)
               && isset($this->suppliers[$type]) && (count($this->suppliers[$type])==1)) {
              $candelete = false;
           }
        }
  
        if (isset($this->suppliers[$type]) && count($this->suppliers[$type])) {
           foreach ($this->suppliers[$type] as $d) {
              echo "<div class='actor_row'>";
              $suppliers_id = $d['suppliers_id'];
  
              echo "$mandatory$suppliericon&nbsp;";
  
              $email = $d['alternative_email'];
              if ($suppliers_id) {
                 if ($supplier->getFromDB($suppliers_id)) {
                    echo $supplier->getLink(['comments' => $showsupplierlink]);
                    echo "&nbsp;";
  
                    $tmpname = Dropdown::getDropdownName($supplier->getTable(), $suppliers_id, 1);
                    Html::showToolTip($tmpname['comment']);
  
                    if (empty($email)) {
                       $email = $supplier->fields['email'];
                    }
                 }
              } else {
                 echo "<a href='mailto:$email'>$email</a>";
              }
  
              if ($CFG_GLPI['notifications_mailing']) {
                 $text = __('Email followup')
                    . "&nbsp;" . Dropdown::getYesNo($d['use_notification'])
                    . '<br />';
  
                 if ($d['use_notification']) {
                    $text .= sprintf(__('%1$s: %2$s'), __('Email'), $email);
                 }
                 if ($canedit) {
                    $opt = ['awesome-class' => 'fa-envelope',
                            'popup' => $linksupplier->getFormURLWithID($d['id'])];
                    Html::showToolTip($text, $opt);
                 }
              }
  
              if ($canedit && $candelete) {
                 Html::showSimpleForm($linksupplier->getFormURL(), 'delete',
                                      _x('button', 'Delete permanently'),
                                      ['id' => $d['id']],
                                      'fa-times-circle');
              }
  
              echo '</div>';
           }
        }
     }

    static function getDefaultValues($entity = 0) {
        return [];
    }


    /**
    * @see CommonGLPI::getAdditionalMenuLinks()
    *
    * @since 9.5.0
    **/
    static function getAdditionalMenuLinks() {
        return [];
    }

    /**
    * @see CommonGLPI::getAdditionalMenuOptions()
    *
    * @since 0.85
    **/
    static function getAdditionalMenuOptions() {
        if (static::canView()) {
           return [
              'dporegister' => [
                 'title' => PluginDporegisterProcessing::getTypeName(Session::getPluralNumber()),
                 'page'  => PluginDporegisterProcessing::getSearchURL(false),
                 'links' => [
                    'add'    => '/front/processing.form.php',
                    'search' => '/front/processing.php',
                 ]
              ]
           ];
        }
        return false;
     }

    static function getIcon() {
       return "fas fa-user-tie";
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
        $options['_suppliers_id_assign_notif']['use_notification'] = true;

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

            echo "<th width='$colsize3'>" . __('Last Update') . "</th>";
            echo "<td width='$colsize4'>";
            echo sprintf(
                __('%1$s %2$s %3$s'),
                Html::convDateTime($this->fields["date_mod"]),
                __('By'),
                getUserName($this->fields["users_id_lastupdater"], $showuserlink)
            );

            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<th width='$colsize1'>" . __('Title') . "</th>";
        echo "<td colspan='3' width='100%'>";
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
        echo "<th width='$colsize1'>" . __('Purpose', 'dporegister') . "</th>";
        echo "<td colspan='3' width='100%'>";
        $purpose = self::setSimpleTextContent($this->fields["purpose"]);
        if ($canUpdate) {
            echo sprintf(
                "<textarea style='width:98%%' name='purpose' required maxlength='250' rows='3'>%1\$s</textarea>",
                $purpose
            );
        } else {
            echo Toolbox::getHtmlToDisplay($purpose);
        }

        echo "</td></tr>";
        echo "</table>";

        // Processing Actors
        $this->showActorsPartForm($ID, $options);

        echo "<table class='tab_cadre_fixe' id='mainformtable2'>";

        echo "<tr></tr>";

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
        echo "<th width='$colsize3' rowspan='4' style='vertical-align:top;'>" . __('LawfulBasis', 'dporegister') . "</th>";
        echo "<td width='$colsize4' rowspan='4' style='vertical-align:top;'>";

        if (!$ID || $this->fields['plugin_dporegister_lawfulbasismodels_id'] <= 0) {

            $undefined = new PluginDporegisterLawfulBasisModel();
            $undefined->getFromDBByCrit(['name' => 'Undefined']);

            if ($undefined->fields)
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

        if ($lawfulbasis->fields)
            echo "<div id='lawfulbasis'>" . $lawfulbasis->fields['content'] . "</div>";
        else
            echo "<div id='lawfulbasis'>&nbsp;</div>";

        echo "</td></tr>";

        if (!$ID) {

            echo "<tr class='tab_bg_1'>";
            echo "<th width='$colsize1'>" . __('Entity') . "</th>";
            echo "<td width='$colsize2'>";
            Entity::dropdown([
                'name' => 'entities_id',
                'value' => $this->fields['entities_id']
            ]);
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<th width='$colsize1'>" . __('Status') . "</th>";
        echo "<td width='$colsize2'>";
        CommonITILObject::dropdownStatus([
            'value' => $this->fields["status"]
        ]);
        echo "</td></tr>";

        if ($ID) {

            echo "<tr><th width='$colsize1'>" . __('Compliant', 'dporegister') . "</th>";
            echo "<td width='$colsize2'>";
            Dropdown::showYesNo('is_compliant', $this->fields['is_compliant']);
            echo "</td></tr>";
        }

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

    function post_addItem()
    {
        //var_dump($this->input); die;


        return parent::post_addItem();
    }

    //! @copydoc CommonGLPI::defineTabs($options)
    public function defineTabs($options = array())
    {
        $ong = array();

        $this->addDefaultFormTab($ong)
            ->addStandardTab(__CLASS__, $ong, $options)

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

        if ($this->getType() == PluginDporegisterProcessing::class) {
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


    /**
     * Show (or retreive) the dropdown about the lawful basisses
     * 
     * @param String $name Name for the form input
     * @param Array $options
     * 
     * @return String
     * @see self::getLawfulBasisses
     */
    public static function dropdownLawfulBasis($name, $options = [])
    {
        if (array_key_exists('canupdate', $options) && !$options['canupdate']) {
            echo self::getLawfulBasisses()[$options['value']];

            return true;
        }

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

        $items += self::getLawfulBasisses();

        return Dropdown::showFromArray($name, $items, $params);
    }

        /**
     * Get the lawful basis list
     * 
     * @param Boolean $WithMetaForSearch
     * 
     * @return Array
     */
    public static function getLawfulBasisses($withmetaforsearch = false)
    {
        $options = [
            'undef' => __('Undefined', 'dporegister'),
            'art6a' => __('Article 6-a', 'dporegister'),
            'art6b' => __('Article 6-b', 'dporegister'),
            'art6c' => __('Article 6-c', 'dporegister'),
            'art6d' => __('Article 6-d', 'dporegister'),
            'art6e' => __('Article 6-e', 'dporegister'),
            'art6f' => __('Article 6-f', 'dporegister')
        ];

        if ($withmetaforsearch) {

            $options['all'] = __('All');
        }

        return $options;
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

        if (!$DB->fieldExists($table, $lawfulbasisForeignKey)) {

            $query = "ALTER TABLE `$table` ADD `$lawfulbasisForeignKey` int(11) NOT NULL default '0' COMMENT 'RELATION to $lawfulbasisTable (id)';";
            $DB->query($query) or die("error altering $table to add the new lawfulbasis column " . $DB->error());
        }

        if ($DB->fieldExists($table, 'lawfulbasis')) {

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

    static function checkUsersFields()
    {
        global $DB;
        $table = self::getTable();

        // Check if users_id_jointcontroller field exist
        if ($DB->fieldExists($table, 'users_id_jointcontroller')) {

            $processings = (new PluginDporegisterProcessing())->find();

            foreach ($processings as $resultSet) {

                $processingId = $resultSet['id'];
                $entity = $resultSet['entities_id'];

                $default = new PluginDporegisterRepresentative();
                $default->getFromDBByCrit(['entities_id' => $entity]);

                // Check processing exists in $processingUsersTable
                if (!countElementsInTable(
                    PluginDporegisterProcessing_User::getTable(),
                    [self::getForeignKeyField() => $processingId]
                )) {

                    $pu = new PluginDporegisterProcessing_User();

                    // default Legal Representative - users_id_representative
                    $pu->add([
                        self::getForeignKeyField() => $processingId,
                        User::getForeignKeyField() => $default->fields['users_id_representative'],
                        'type' => PluginDporegisterCommonProcessingActor::LEGAL_REPRESENTATIVE
                    ]);

                    // default DPO - users_id_dpo
                    $pu->add([
                        self::getForeignKeyField() => $processingId,
                        User::getForeignKeyField() => $default->fields['users_id_dpo'],
                        'type' => PluginDporegisterCommonProcessingActor::DPO
                    ]);

                    if ($resultSet['users_id_jointcontroller'] != null) {

                        // add current Joint controller
                        $pu->add([
                            self::getForeignKeyField() => $processingId,
                            User::getForeignKeyField() => $resultSet['users_id_jointcontroller'],
                            'type' => PluginDporegisterCommonProcessingActor::JOINT_CONTROLLER
                        ]);
                    }
                }
            }

            $query = "ALTER TABLE `$table` DROP `users_id_jointcontroller`";
            $DB->query($query) or die("error altering $table to remove the old users_id_jointcontroller column " . $DB->error());
        }

        return true;
    }

    public function showActorsPartForm($ID, array $options)
    {
        $options = array_merge($options, [
            '_users_id_requester' => 0,
            '_users_id_assign' => 0,
            '_users_id_observer' => 0,
            '_suppliers_id_assign' => 0,
            'entity_restrict' => $_SESSION["glpiactive_entity"]
        ]);

        $canUpdate = $options['canedit'];

        echo "<div class='tab_actors tab_cadre_fixe' id='mainformtable5'>";
        echo "<div class='responsive_hidden actor_title' width='13%'>" . __('Actor') . "</div>";

        // ====== Legal Representative BLOC ======
        //
        //
        $rand_legalrep = -1;
        $candeletelegalrep = false;

        echo "<span class='actor-bloc'>";
        echo "<div class='actor-head'>";
        echo __("Legal Representative", 'dporegister');

        if ($ID && $canUpdate) {

            $rand_legalrep = mt_rand(1, mt_getrandmax());

            echo "&nbsp;";
            echo "<span class='fa fa-plus pointer' title=\"" . __s('Add') . "\"
                onClick=\"" . Html::jsShow("itilactor$rand_legalrep") . "\"
                ><span class='sr-only'>" . __s('Add') . "</span></span>";

            $candeletelegalrep = true;
        }

        echo "</div>"; // end .actor-head

        echo "<div class='actor-content'>";
        if ($rand_legalrep >= 0) {
            $this->showActorAddForm(
                PluginDporegisterCommonProcessingActor::LEGAL_REPRESENTATIVE,
                $rand_legalrep,
                $this->fields['entities_id'],
                $candeletelegalrep,
                false
            );
        }

        if (!$ID) {

            if ($canUpdate) {
                $this->showActorAddFormOnCreate(
                    PluginDporegisterCommonProcessingActor::LEGAL_REPRESENTATIVE,
                    $options
                );
            }
        } else {

            $this->showUsersAssociated(
                PluginDporegisterCommonProcessingActor::LEGAL_REPRESENTATIVE,
                true,
                $options
            );
        }

        echo "</div>"; // end .actor-content
        echo "</span>"; // end .actor-bloc

        // ====== DPO BLOC ======
        //
        //
        $rand_dpo = -1;
        $candeletedpo = false;

        echo "<span class='actor-bloc'>";
        echo "<div class='actor-head'>";
        echo __("DPO", 'dporegister');

        if ($ID && $canUpdate) {

            $rand_dpo = mt_rand(1, mt_getrandmax());

            echo "&nbsp;";
            echo "<span class='fa fa-plus pointer' title=\"" . __s('Add') . "\"
                onClick=\"" . Html::jsShow("itilactor$rand_dpo") . "\"
                ><span class='sr-only'>" . __s('Add') . "</span></span>";

            $candeletedpo = true;
        }

        echo "</div>"; // end .actor-head

        echo "<div class='actor-content'>";
        if ($rand_dpo >= 0) {
            $this->showActorAddForm(
                PluginDporegisterCommonProcessingActor::DPO,
                $rand_dpo,
                $this->fields['entities_id'],
                $candeletedpo,
                false
            );
        }

        if (!$ID) {

            if ($canUpdate) {
                $this->showActorAddFormOnCreate(
                    PluginDporegisterCommonProcessingActor::DPO,
                    $options
                );
            }
        } else {

            $this->showUsersAssociated(
                PluginDporegisterCommonProcessingActor::DPO,
                true,
                $options
            );
        }

        echo "</div>"; // end .actor-content
        echo "</span>"; // end .actor-bloc

        // ====== Joint Controller BLOC ======
        //
        //
        $rand_jointcontroller = -1;
        $candeletejointcontroller = false;

        echo "<span class='actor-bloc'>";
        echo "<div class='actor-head'>";
        echo __("Joint Controller", 'dporegister');

        if ($ID && $canUpdate) {

            $rand_jointcontroller = mt_rand(1, mt_getrandmax());

            echo "&nbsp;";
            echo "<span class='fa fa-plus pointer' title=\"" . __s('Add') . "\"
                onClick=\"" . Html::jsShow("itilactor$rand_jointcontroller") . "\"
                ><span class='sr-only'>" . __s('Add') . "</span></span>";

            $candeletejointcontroller = true;
        }

        echo "</div>"; // end .actor-head

        echo "<div class='actor-content'>";
        if ($rand_jointcontroller >= 0) {

            $this->showActorAddForm(
                PluginDporegisterCommonProcessingActor::JOINT_CONTROLLER,
                $rand_jointcontroller,
                $this->fields['entities_id'],
                $candeletejointcontroller,
                false,
                true
            );
        }

        if (!$ID) {

            if ($canUpdate) {
                $this->showActorAddFormOnCreate(
                    PluginDporegisterCommonProcessingActor::JOINT_CONTROLLER,
                    $options
                );
            }
        } else {

            $this->showUsersAssociated(
                PluginDporegisterCommonProcessingActor::JOINT_CONTROLLER,
                true,
                $options
            );
        }

        // Assign Suppliers to Joint Controller
        if (!$ID) {
            if ($canUpdate) {

                echo '<hr>';
                $this->showSupplierAddFormOnCreate($options);
            } else { // predefined value

                if (
                    isset($options["_suppliers_id_assign"])
                    && $options["_suppliers_id_assign"]
                ) {

                    echo self::getActorIcon('supplier', PluginDporegisterCommonProcessingActor::JOINT_CONTROLLER) . "&nbsp;";
                    echo Dropdown::getDropdownName("glpi_suppliers", $options["_suppliers_id_assign"]);
                    echo "<input type='hidden' name='_suppliers_id_assign' value=\"" .
                        $options["_suppliers_id_assign"] . "\">";

                    echo '<hr>';
                }
            }
        } else {

            $this->showSuppliersAssociated(PluginDporegisterCommonProcessingActor::JOINT_CONTROLLER, $candeletejointcontroller, $options);
        }

        echo "</div>"; // end .actor-content
        echo "</span>"; // end .actor-bloc
    
        echo "</div>";
    }

    function showActorAddForm(
        $type,
        $rand_type,
        $entities_id,
        $is_hidden = [],
        $withgroup = true,
        $withsupplier = false,
        $inobject = true
    ) {
        $withgroup = false;

        global $CFG_GLPI;

        $types = ['user'  => __('User')];

        if ($withgroup) {
            $types['group'] = __('Group');
        }

        if (
            $withsupplier
            && ($type == CommonITILActor::ASSIGN)
        ) {
            $types['supplier'] = __('Supplier');
        }

        $typename = CommonITILObject::getActorFieldNameType($type);

        echo "<div ".($inobject?"style='display:none'":'')." id='itilactor$rand_type' class='actor-dropdown'>";
        $rand   = Dropdown::showFromArray("_itil_".$typename."[_type]", $types,
                                        ['display_emptychoice' => true]);

        $params = ['type' => '__VALUE__',
        'actortype'       => $typename,
        'itemtype'        => $this->getType(),
        'entity_restrict' => $entities_id];
    
        Ajax::updateItemOnSelectEvent("dropdown__itil_".$typename."[_type]$rand",
                                        "showitilactor".$typename."_$rand",
                                        "../ajax/processing_actors_dropdown.php",
                                        $params);

        echo "<span id='showitilactor".$typename."_$rand' class='actor-dropdown'>&nbsp;</span>";

        if ($inobject) {
            echo "<hr>";
        }
        
        echo "</div>";
    }

    function showActorAddFormOnCreate($type, array $options)
    {
        global $CFG_GLPI;

        $typename = CommonITILObject::getActorFieldNameType($type);
        $itemtype = $this->getType();

        $actor_name = '_users_id_'.$typename;

        $rand = mt_rand();

        echo self::getActorIcon('user', $type);
        echo "&nbsp;";

        if ($options["_users_id_".$typename] == 0 && !isset($_REQUEST["_users_id_$typename"]) && !isset($this->input["_users_id_$typename"])) {
            $options["_users_id_".$typename] = $this->getDefaultActor($type);
        }

        if (!isset($options["_right"])) {
            $right = $this->getDefaultActorRightSearch($type);
        } else {
            $right = $options["_right"];
        }

        $params = ['name'        => $actor_name,
                    'value'       => $options["_users_id_".$typename],
                    'right'       => $right,
                    'rand'        => $rand,
                    'entity'      => (isset($options['entities_id'])
                                    ? $options['entities_id']: $options['entity_restrict'])];

        User::dropdown($params);
    }

    function getDefaultActorRightSearch($type)
    {
        return "all";
    }

    /**
     * Get Default actor when creating the object
     *
     * @param integer $type type to search (see constants)
     *
     * @return boolean
     **/
    function getDefaultActor($type)
    {
        $default = new PluginDporegisterRepresentative();
        $default->getFromDBByCrit(["entities_id" => $this->fields['entities_id']]);

        if (isset($default->fields['id'])) {
            switch ($type) {
                case PluginDporegisterCommonProcessingActor::LEGAL_REPRESENTATIVE:
                    {
                        return $default->fields['users_id_representative'];
                        break;
                    }
                case PluginDporegisterCommonProcessingActor::DPO:
                    {
                        return $default->fields['users_id_dpo'];
                        break;
                    }
            }
        }

        return 0;
    }

    function getActors($type, $specifics = false)
    {
        $where = [];

        $where[] = self::getForeignKeyField() . " = " . $this->fields['id'];
        $where[] = "type = '$type'";

        if($specifics) {

            $entity = new PluginDporegisterRepresentative();
            $entity->getFromDBByCrit(['entities_id' => $this->fields['entities_id']]);

            if($entity) {

                $userid = "";

                if($type === PluginDporegisterCommonProcessingActor::LEGAL_REPRESENTATIVE) {

                    $userid = $entity->fields["users_id_representative"];

                } elseif($type === PluginDporegisterCommonProcessingActor::DPO) {

                    $userid = $entity->fields["users_id_representative"];
                }

                if($userid != "") {

                    $field = "users_id";
                    $where[] = "NOT $field = " . $userid;
                }
            }
        }

        $actors = (new PluginDporegisterProcessing_User())->find($where);
        return $actors;
    }

    function getSuppliers($type)
    {
        $where = [];

        $where[] = self::getForeignKeyField() . " = " . $this->fields['id'];
        $where[] = "type = '$type'";

        $actors = (new PluginDporegisterProcessing_Supplier())->find($where);
        return $actors;
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
}

