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

if (strpos($_SERVER['PHP_SELF'], 
    "processing_securitymesure_view_subitem.php")) {

    $AJAX_INCLUDE = 1;

    include("../../../inc/includes.php");
    Plugin::load('dporegister', true);

    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

if (array_key_exists(PluginDporegisterProcessing::getForeignKeyField(), $_POST)) {

    // Show the inputs form with the specified ID
    $item = new PluginDporegisterProcessing_SecurityMesure();
    $item->showForm($_POST['id'], $_POST);

} else echo '';

Html::ajaxFooter();