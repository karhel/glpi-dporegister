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

include("../../../inc/includes.php");
Plugin::load('dporegister', true);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$processing = new PluginDporegisterProcessing();

if (isset($_POST["add"])) {

    // Check CREATE ACL
    $processing->check(-1, CREATE, $_POST);

    // Do object add
    $processing->add($_POST);
    
    // Redirect to object form
    Html::back();

} else if (isset($_POST['update'])) {
    
    // Check UPDATE ACL
    $processing->check($_POST['id'], UPDATE);

    // Do object update
    $processing->update($_POST);
    
    // Redirect to object form
    Html::back();

} else if (isset($_POST['delete'])) { // Put in trash

    // Check DELETE ACL
    $processing->check($_POST['id'], DELETE);

    // Do object delete (trash)
    $processing->delete($_POST);

    // Redirect to objects list
    $processing->redirectToList();

} else if (isset($_POST['purge'])) {

    // Check PURGE ACL
    $processing->check($_POST['id'], PURGE);

    // Do permanently delete
    $processing->purge($_POST);    

    // Redirect to objects list
    $processing->redirectToList();

} else {

    // Display the objects list

    Html::header(
        __('DPO Register', 'dporegister'),
        $_SERVER['PHP_SELF'],
        'management',
        'pluginDporegisterProcessing'
    );

    $processing->display(['id' => $_GET["id"]]);

    Html::footer();
}