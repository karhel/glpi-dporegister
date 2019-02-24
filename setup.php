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

define('PLUGIN_DPOREGISTER_VERSION', '1.4');
define('PLUGIN_DPOREGISTER_ROOT', __DIR__);

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_dporegister()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['dporegister'] = true;

   // Profile rights management
    Plugin::registerClass('PluginDporegisterProfile', array('addtabon' => array('Profile')));
    $PLUGIN_HOOKS['change_profile']['dporegister'] = ['PluginDporegisterProfile', 'initProfile'];

    Plugin::registerClass('PluginDporegisterProcessing');
    $PLUGIN_HOOKS["menu_toadd"]['dporegister'] = ['management' => 'PluginDporegisterProcessing'];

    Plugin::registerClass('PluginDporegisterRepresentative', array('addtabon' => array('Entity')));

    $PLUGIN_HOOKS['add_css']['dporegister'] = 'dporegister.css';
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_dporegister()
{
    return [
        'name' => __('DPO Register', 'dporegister'),
        'version' => PLUGIN_DPOREGISTER_VERSION,
        'author' => '<a href="https://github.com/karhel/glpi-dporegister">Karhel Tmarr</a>',
        'license' => 'GPLv3+',
        'homepage' => 'https://github.com/karhel/glpi-dporegister',
        'minGlpiVersion' => '9.3'
    ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_dporegister_check_prerequisites()
{
   // Strict version check (could be less strict, or could allow various version)
    if (version_compare(GLPI_VERSION, '9.4', 'lt')) {
        if (method_exists('Plugin', 'messageIncompatible')) {
            echo Plugin::messageIncompatible('core', '9.4');
        } else {
            echo "This plugin requires GLPI >= 9.4";
        }
        return false;
    }
    return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_dporegister_check_config($verbose = false)
{
    if (true) { // Your configuration check
        return true;
    }

    if ($verbose) {
        echo __('Installed / not configured');
    }
    return false;
}
