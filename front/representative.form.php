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

$representative = new PluginDporegisterRepresentative();

if (isset($_POST['add'])) {

    // Check CREATE ACL
    $representative->check(-1, CREATE, $_POST);

    // Do object add
    $representative->add($_POST);

    // Redirect to object form
    Html::back();

} else if (isset($_POST['update'])) {

    // Check UPDATE ACL
    $representative->check($_POST['id'], UPDATE, $_POST);

    // Do object update
    $representative->update($_POST);

    // Redirect to object form
    Html::back();
}

Html::back();