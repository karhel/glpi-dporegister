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

return [
    'undef' => [
        __('Undefined', 'dporegister'),
        __('Select a Lawful Basis for this processing.', 'dporegister')
    ],
    'art6a' => [
        __('Article 6-a', 'dporegister'),
        __('The data subject has given consent to the processing of his or her personal data for one or more specific purposes.', 'dporegister')
    ],
    'art6b' => [
        __('Article 6-b', 'dporegister'),
        __('Processing is necessary for the performance of a contract to which the data subject is party or in order to take steps at the request of the data subject prior to entering into a contract.', 'dporegister')
    ],
    'art6c' => [
        __('Article 6-c', 'dporegister'),
        __('Processing is necessary for compliance with a legal obligation to which the controller is subject.', 'dporegister')
    ],
    'art6d' => [
        __('Article 6-d', 'dporegister'),
        __('Processing is necessary in order to protect the vital interests of the data subject or of another natural person.', 'dporegister')
    ],
    'art6e' => [
        __('Article 6-e', 'dporegister'),
        __('Processing is necessary for the performance of a task carried out in the public interest or in the exercise of official authority vested in the controller.', 'dporegister')
    ],
    'art6f' => [
        __('Article 6-f', 'dporegister'),
        __('Processing is necessary for the purposes of the legitimate interests pursued by the controller or by a third party, except where such interests are overridden by the interests or fundamental rights and freedoms of the data subject which require protection of personal data, in particular where the data subject is a child.', 'dporegister')
    ]
];
