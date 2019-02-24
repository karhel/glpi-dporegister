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

define('K_PATH_IMAGES', GLPI_ROOT . '/plugins/dporegister/pics/');

class PluginDporegisterSimplePDF
{
    const LEFT = 'L';
    const CENTER = 'C';
    const RIGHT = 'R';

    protected $width;
    protected $height;

    protected $marginTop = 25;
    protected $marginLeft = 10;
    protected $marginHeader = 10;
    protected $marginFooter = 10;

    protected $font = 'helvetica';
    protected $fontsize = 8;

    protected $pdf;

    protected $entity;

    public function __construct()
    {
        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $this->pdf->setHeaderFont([$this->font, 'B', 8]);
        $this->pdf->setFooterFont([$this->font, 'B', 8]);

        //set margins
        $this->pdf->SetMargins(
            $this->marginLeft, // left
            $this->marginTop, // top
            -1 // right
        );

        $this->pdf->SetAutoPageBreak(true, 15);

        $this->pdf->SetFont($this->font, '', $this->fontsize);

        $this->pdf->SetHeaderMargin($this->marginHeader);
        $this->pdf->SetFooterMargin($this->marginFooter);

        $this->width = $this->pdf->getPageWidth() - (2 * $this->marginLeft);
        $this->height = $this->pdf->getPageHeight() - (2 * $this->marginTop);
    }

    // --------------------------------------------------------------------
    //  GLPI PLUGIN COMMON
    // --------------------------------------------------------------------

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
    static function showForProcessing($item)
    {
        global $CFG_GLPI;

        $nb = countElementsInTable(
            PluginDporegisterRepresentative::getTable(),
            ['entities_id' => $item->fields['entities_id']]
        );

        if ($nb < 1) {

            Html::nullHeader(__('Access denied'), '');            

            echo "<div class='center'><br><br>";
            echo Html::image($CFG_GLPI["root_doc"] . "/pics/warning.png", ['alt' => __('Warning')]);
            echo "<br><br><span class='b'>".__('No information found for the Entity of the Processing', 'dporegister')."</span></div>";
            
            exit();
        }

        echo "<div class='tab_cadre_fixe' id='tabsbody'>";
        echo "<iframe id='pdf-output' width='100%' height='500px' 
            src='../ajax/processing_pdf.php?processings_id=" . $item->fields['id'] . "'></iframe>";
        echo "</div>";
    }

    //! @copydoc CommonGLPI::getTabNameForItem($item, $withtemplate)
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // Check item type
        switch ($item->getType()) {

            case PluginDporegisterProcessing::class:

                return __('Generate PDF', 'dporegister');
        }

        return '';
    }

    // --------------------------------------------------------------------
    //  SPECIFICS FOR THE CURRENT OBJECT CLASS
    // --------------------------------------------------------------------

    /**
     * 
     */
    public function generateProcessing($processingId)
    {
        $processing = new PluginDporegisterProcessing();
        $processing->getFromDB($processingId);

        $this->setEntity($processing->fields['entities_id']);

        $this->addPageForProcessing($processing);
    }

    /**
     * 
     */
    public function generateEntity($id)
    {
        $this->setEntity($id);

        $processings = (new PluginDporegisterProcessing())
            ->find("entities_id = " . $this->entity->fields['entities_id']);

        $this->addCoverPage($processings);

        foreach ($processings as $p) {
            $processing = new PluginDporegisterProcessing();
            $processing->getFromDB($p['id']);

            $this->addPageForProcessing($processing);
        }
    }

    /**
     * 
     */
    public function showPdf()
    {
        //header("Content-type:application/pdf");
        $this->pdf->Output('glpi.pdf', 'I');
    }

    /**
     * 
     */
    protected function setEntity($id)
    {
        $this->entity = new PluginDporegisterRepresentative();
        $this->entity->getFromDBByCrit(['entities_id' => $id]);

        $this->setHeader(
            __('Processings Register', 'dporegister'),
            $this->entity->fields['corporatename']
        );
    }

    /**
     * 
     */
    protected function addCoverPage($processings)
    {
        $this->pdf->addPage('P', 'A4');

        $this->addPageTitle(
            "<h1>" .
                __('Processings Register', 'dporegister') .
                '<br/>' .
                $this->entity->fields['corporatename'] .
                "</h1>"
        );

        $datas = [];

        $user = new User();
        $user->getFromDB($this->entity->fields['users_id_representative']);

        $email = new UserEmail();
        $email->getFromDBByCrit(['users_id' => $user->fields['id'], 'is_default' => 1]);

        $location = new Location();
        $location->getFromDB($user->fields['locations_id']);

        $datas[] = [
            'section' =>
                '<h3>' .
                __('Legal Representative') .
                '</h3>',

            'value' =>

                '<ul><li>'.__('Surname').': <b>' . $user->getField('realname') .
                '</b>; '.__('First name').': <b>' . $user->getField('firstname') .
                '</b></li><li>'.__('Address').': <b>' . $location->getField('address') .
                '</b></li><li>'.__('Postal code').'/'.__('Town').': <b>' . $location->getField('postcode') . ' ' . $location->getField('town') . ' ' . $location->getField('state') . ' '  . $location->getField('country') .
                '</b></li><li>'.__('Phone').': <b>' . $user->getField('phone') .
                '</b></li><li>'.__('Email').': <b>' . $email->getField('email') .
                '</b></li></ul>'
        ];

        $user = new User();
        $user->getFromDB($this->entity->fields['users_id_dpo']);

        $email = new UserEmail();
        $email->getFromDBByCrit(['users_id' => $user->fields['id'], ' is_default' => 1]);

        $location = new Location();
        $location->getFromDB($user->fields['locations_id']);

        $datas[] = [
            'section' =>
                '<h3>' .
                __('Data Protection Officer') .
                '</h3>',

            'value' =>

                '<ul><li>'.__('Surname').': <b>' . $user->getField('realname') .
                '</b>; '.__('First name').': <b>' . $user->getField('firstname') .
                '</b></li><li>'.__('Address').': <b>' . $location->getField('address') .
                '</b></li><li>'.__('Postal code').'/'.__('Town').': <b>' . $location->getField('postcode') . ' ' . $location->getField('town') . ' ' . $location->getField('state') . ' '  . $location->getField('country') .
                '</b></li><li>'.__('Phone').': <b>' . $user->getField('phone') .
                '</b></li><li>'.__('Email').': <b>' . $email->getField('email') .
                '</b></li></ul>'
        ];

        foreach ($datas as $d) {

            $this->write2ColsRow(

                $d['section'], // First column
                [
                    'fillcolor' => [175, 175, 175],
                    'fill' => 1,
                    'linebefore' => 4,
                    'border' => 1,
                    'cellwidth' => 50,
                    'align' => Self::RIGHT
                ],

                $d['value'], // Snd column
                [
                    'border' => 1
                ]
            );
        }

        if ($processings) {

            $this->writeInternal(
                '<h2>' .
                    __('Activities of the organization involving the processing of personal data', 'dporegister') .
                    '<br/><small><i>' .
                    sprintf(
                    __('Below is the list of activities for which %s deals with personal data', 'dporegister'),
                    $this->entity->fields['corporatename']
                ) .
                    '</i></small></h2>',
                [
                    'linebefore' => 8
                ]
            );

            $tbl = '<table border="1" cellpadding="3" cellspacing="0">';
            $tbl .= '<thead><tr>
                <th width="20%" style="background-color:#323232;color:#FFF;"><h3>' . __('Activities', 'dporegister') . '</h3></th>
                <th width="80%" style="background-color:#323232;color:#FFF;"><h3>' . __('Description of the activity', 'dporegister') . '</h3></th></tr></thead><tbody>';

            for ($i = 1; $i <= count($processings); $i++) {

                $tbl .= "<tr>
                    <td width=\"20%\">" . __('Activity', 'dporegister') . " #" . $i . "</td>
                    <td width=\"80%\">" . $processings[$i]['name'] . "</td>
                    </tr>";
            }

            $tbl .= '</tbody></table>';

            $this->writeHtml($tbl);
        }

        // reset pointer to the last page
        $this->pdf->lastPage();
    }

    /**
     * 
     */
    protected function setHeader($headerTitle, $headerString)
    {
        $this->pdf->resetHeaderTemplate();
        $this->pdf->SetHeaderData('register_logo2.png', 15, $headerTitle, $headerString);

        $this->pdf->SetTitle($headerTitle);
        $this->pdf->SetY($this->pdf->GetY() + $this->pdf->getLastH());
    }

    /**
     * 
     */
    protected function addPageTitle($html)
    {
        $this->writeInternal(
            $html,

            [
                'fillcolor' => [50, 50, 50],
                'fill' => 1,
                'textcolor' => [255, 255, 255],
                'align' => Self::CENTER
            ]
        );
    }

    /**
     * 
     */
    protected function addPageForProcessing(PluginDporegisterProcessing $processing)
    {
        $this->pdf->addPage('P', 'A4');

        $this->addPageTitle(
            "<h1><small>" .
                __("Register Sheet for Processing", 'dporegister') .
                " :</small><br/>" .
                $processing->fields['name'] .
                "</h1>"
        );

        // GLOBAL INFORMATIONS ABOUT THE PROCESSING ===========================

        $datas = [];

        $datas[] = [
            'section' =>
                '<h3>' .
                __('Created on', 'dporegister') .
                '</h3>',

            'value' => $processing->fields['date_creation']
        ];

        $datas[] = [
            'section' =>
                '<h3>' .
                __('Last update on', 'dporegister') .
                '</h3>',

            'value' => $processing->fields['date_mod']
        ];

        $processingSoftwares = (new PluginDporegisterProcessing_Software())
            ->find(PluginDporegisterProcessing::getForeignKeyField() . ' = ' . $processing->fields['id']);

        $sotfwareString = '';
        foreach ($processingSoftwares as $ps) {
            $software = new Software();
            $software->getFromDB($ps['softwares_id']);

            $sotfwareString .= $software->fields['name'] . '; ';
        }

        $datas[] = [
            'section' =>
                '<h3>' .
                __('Software') .
                '</h3>',

            'value' => $sotfwareString
        ];

        // Get specifics Legal Representative
        $lr = $processing->getActors(PluginDporegisterCommonProcessingActor::LEGAL_REPRESENTATIVE, true);
        if($lr) {

            $value = "";

            $value .= "<ul>";
            foreach($lr as $u) { 
                $value .= "<li>";
                $user = new User();
                $user->getFromDB($u['users_id']);
                $value .= __('Surname').': <b>' . $user->getField('realname') .
                             '</b>; '.__('First name').': <b>' . $user->getField('firstname');
                $value .= "</li>";
            }
            $value .= "</ul>";


            $datas[] = [
                'section' =>
                    '<h3>' .
                    __('Additional Legal Representative', 'dporegister') .
                    '</h3>',

                'value' => $value
            ];
        }

        // Get specifics DPO
        $dpo = $processing->getActors(PluginDporegisterCommonProcessingActor::DPO, true);
        if($dpo) {

            $value = "";

            $value .= "<ul>";
            foreach($dpo as $u) { 
                $value .= "<li>";
                $user = new User();
                $user->getFromDB($u['users_id']);
                $value .= __('Surname').': <b>' . $user->getField('realname') .
                             '</b>; '.__('First name').': <b>' . $user->getField('firstname');
                $value .= "</li>";
            }
            $value .= "</ul>";

            $datas[] = [
                'section' =>
                    '<h3>' .
                    __('Additional DPO', 'dporegister') .
                    '</h3>',

                'value' => $value
            ];
        }

        // Get Joint Controller
        $jc = $processing->getActors(PluginDporegisterCommonProcessingActor::JOINT_CONTROLLER);
        $jc_suppliers = $processing->getSuppliers(PluginDporegisterCommonProcessingActor::JOINT_CONTROLLER);

        if($jc || $jc_suppliers) {

            $value = "";

            $value .= "<ul>";
            foreach($jc as $u) { 
                $value .= "<li>";
                $user = new User();
                $user->getFromDB($u['users_id']);

                $value .= __('Surname').': <b>' . $user->getField('realname') .
                        '</b>; '.__('First name').': <b>' . $user->getField('firstname');
                $value .= "</li>"; 
            }
            $value .= "</ul>";
            
            $value .= "<ul>";
            foreach($jc_suppliers as $s) { 
                $value .= "<li>"; 
                $supplier = new Supplier();
                $supplier->getFromDB($s['suppliers_id']);

                $value .= __('Name').': <b>' . $supplier->getField('name') .
                        '</b>; '.__('Phone').': <b>' . $supplier->getField('phonenumber') ;

                $value .= "</li>";
            }
            $value .= "</ul>";

            $datas[] = [
                'section' =>
                    '<h3>' .
                    __('Joint Controller', 'dporegister') .
                    '</h3>',
    
                'value' => $value
            ];      
        }

        foreach ($datas as $d) {

            $this->write2ColsRow(

                $d['section'], // First column
                [
                    'fillcolor' => [175, 175, 175],
                    'fill' => 1,
                    'linebefore' => 4,
                    'border' => 1,
                    'cellwidth' => 50,
                    'align' => Self::RIGHT
                ],

                $d['value'], // Snd column
                [
                    'border' => 1
                ]
            );
        }

        // PURPOSE ============================================================

        $this->writeInternal(
            '<h2>' .
                __('Purpose of processing', 'dporegister') .
                '</h2>',

            [
                'linebefore' => 8
            ]
        );

        $this->writeInternal(
            $processing->fields['purpose'],
            [
                'border' => 1
            ]
        );

        // LAWFUL BASIS =======================================================

        $this->writeInternal(
            '<h2>' .
                __('LawfulBasis', 'dporegister') .
                '</h2>',
            [
                'linebefore' => 8
            ]
        );

        $lawfulbasis = new PluginDporegisterLawfulBasisModel();
        $lawfulbasis->getFromDB($processing->fields[PluginDporegisterLawfulBasisModel::getForeignKeyField()]);

        $this->writeInternal(
            '<b><small>' . $lawfulbasis->fields['name'] . '</small></b>&nbsp;' .
                $lawfulbasis->fields['content'],
            [
                'border' => 1
            ]
        );

        // CATEGORIES OF INDIVIDUALS ==========================================

        $this->writeInternal(
            '<h2>' .
                PluginDporegisterIndividualsCategory::getTypeName(2) .
                '</h2>',

            [
                'linebefore' => 8
            ]
        );

        $processingIndividualsCategories = (new PluginDporegisterProcessing_IndividualsCategory())
            ->find(PluginDporegisterProcessing::getForeignKeyField() . ' = ' . $processing->fields['id']);

        $tbl = '<table border="1" cellpadding="3" cellspacing="0">';
        $tbl .= '<thead><tr>
            <th width="20%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Category') . '</h4></th>
            <th width="80%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Comment') . '</h4></th>
        </tr></thead><tbody>';

        foreach ($processingIndividualsCategories as $pic) {

            $item = new PluginDporegisterIndividualsCategory();
            $item->getFromDB($pic[PluginDporegisterIndividualsCategory::getForeignKeyField()]);

            $tbl .= "<tr>
                <td width=\"20%\">" . $item->fields['name'] . "</td>
                <td width=\"80%\">" . $item->fields['comment'] . "</td>
            </tr>";
        }

        $tbl .= '</tbody></table>';

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->writeHTML($tbl, true, false, false, true, '');

        // reset pointer to the last page
        $this->pdf->lastPage();

        // SECURITY MESURES ===================================================

        $this->writeInternal(
            '<h2>' .
                PluginDporegisterSecurityMesure::getTypeName(2) .
                '</h2>',
            [
                'linebefore' => 8
            ]
        );

        $processingSecurityMesures = (new PluginDporegisterProcessing_SecurityMesure())
            ->find(PluginDporegisterProcessing::getForeignKeyField() . ' = ' . $processing->fields['id']);

        $tbl = '<table border="1" cellpadding="3" cellspacing="0">';
        $tbl .= '<thead><tr>
            <th width="20%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Name') . '</h4></th>
            <th width="50%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Description') . '</h4></th>
            <th width="30%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Comment') . '</h4></th>
        </tr></thead><tbody>';

        foreach ($processingSecurityMesures as $pic) {

            $item = new PluginDporegisterSecurityMesure();
            $item->getFromDB($pic[PluginDporegisterSecurityMesure::getForeignKeyField()]);

            $tbl .= "<tr>
                <td width=\"20%\">" . $item->fields['name'] . "</td>
                <td width=\"50%\">" . $pic['description'] . "</td>
                <td width=\"30%\">" . $item->fields['comment'] . "</td>
            </tr>";
        }

        $tbl .= '</tbody></table>';

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->writeHTML($tbl, true, false, false, true, '');

        // reset pointer to the last page
        $this->pdf->lastPage();

        // PERSONAL DATA CATEGORIES ===========================================

        $this->pdf->addPage('L', 'A4');

        $this->writeInternal(
            '<h2>' .
                PluginDporegisterPersonalDataCategory::getTypeName(2) .
                '</h2>',

            [
                'linebefore' => 0
            ]
        );

        $processingPersonalDataCategories = (new PluginDporegisterProcessing_PersonalDataCategory())
            ->find(PluginDporegisterProcessing::getForeignKeyField() . ' = ' . $processing->fields['id']);

        $tbl = '<table border="1" cellpadding="3" cellspacing="0">';
        $tbl .= '<thead><tr>' .
            '<th width="15%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Complete Name') . '</h4></th>' .
            '<th width="8%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Sensible', 'dporegister') . '</h4></th>' .
            '<th width="8%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Source', 'dporegister') . '</h4></th>' .
            '<th width="25%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Retention Schedule', 'dporegister') . '</h4></th>' .
            '<th width="8%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Destination', 'dporegister') . '</h4></th>' .
            '<th width="8%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Location') . '</h4></th>' .
            '<th width="8%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Third Countries transfert', 'dporegister') . '</h4></th>' .
            '<th width="20%" style="background-color:#AFAFAF;color:#FFF;"><h4>' . __('Comment') . '</h4></th>' .
            '</tr></thead><tbody>';

        foreach ($processingPersonalDataCategories as $ppdc) {

            $item = new PluginDporegisterPersonalDataCategory();
            $item->getFromDB($ppdc[PluginDporegisterPersonalDataCategory::getForeignKeyField()]);

            $tbl .= '<tr>
                <td width="15%">' . $item->fields['completename'] . '</td>
                <td width="8%">' . ($item->fields['is_sensible'] == 1 ? __('Yes') : __('No')) . '</td>
                <td width="8%">' . PluginDporegisterProcessing_PersonalDataCategory::getSources($ppdc['source']) . '</td>
                <td width="25%">' . PluginDporegisterProcessing_PersonalDataCategory::showRetentionSchedule($ppdc, false) . '</td>
                <td width="8%">' . $ppdc['destination'] . '</td>
                <td width="8%">' . $ppdc['location'] . '</td>
                <td width="8%">' . PluginDporegisterProcessing_PersonalDataCategory::showThirdCountriesTransfert($ppdc, false) . '</td>
                <td width="20%">' . nl2br($ppdc['comment']) . '</td>
            </tr>';
        }

        $tbl .= '</tbody></table>';

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->writeHTML($tbl, true, false, false, true, '');

        // reset pointer to the last page
        $this->pdf->lastPage();
    }

    protected function writeHtml($html, $params = [], $endline = true)
    {
        $options = [
            'fillcolor' => [255, 255, 255],
            'textcolor' => [0, 0, 0],
            'linebefore' => 0,
            'lineafter' => 0,
            'ln' => true,
            'fill' => false,
            'reseth' => false,
            'align' => Self::LEFT,
            'autopadding' => true
        ];

        foreach ($params as $key => $value) {
            $options[$key] = $value;
        }

        $this->pdf->SetFillColor($options['fillcolor'][0], $options['fillcolor'][1], $options['fillcolor'][2]);
        $this->pdf->SetTextColor($options['textcolor'][0], $options['textcolor'][1], $options['textcolor'][2]);

        if ($options['linebefore'] > 0) $this->pdf->Ln($options['linebefore']);

        $this->pdf->writeHTML($html, $options['ln'], $options['fill'], $options['reseth'], $options['autopadding'], $options['align']);

        if ($endline) {

            if ($options['lineafter'] > 0) $this->pdf->Ln($options['lineafter']);
            $this->pdf->SetY($this->pdf->GetY() + $this->pdf->getLastH());
        }
    }

    protected function writeInternal($html, $params = [], $endline = true)
    {
        $options = [
            'fillcolor' => [255, 255, 255],
            'textcolor' => [0, 0, 0],
            'cellpading' => 1,
            'linebefore' => 0,
            'lineafter' => 0,
            'cellwidth' => 0,
            'cellheight' => 1,
            'xoffset' => '',
            'yoffset' => '',
            'border' => 0,
            'ln' => 0,
            'fill' => false,
            'reseth' => true,
            'align' => Self::LEFT,
            'autopadding' => true
        ];

        foreach ($params as $key => $value) {
            $options[$key] = $value;
        }

        $this->pdf->SetFillColor($options['fillcolor'][0], $options['fillcolor'][1], $options['fillcolor'][2]);
        $this->pdf->SetTextColor($options['textcolor'][0], $options['textcolor'][1], $options['textcolor'][2]);
        $this->pdf->SetCellPadding($options['cellpading']);

        if ($options['linebefore'] > 0) $this->pdf->Ln($options['linebefore']);

        $this->pdf->writeHTMLCell(
            $options['cellwidth'],
            $options['cellheight'],
            $options['xoffset'],
            $options['yoffset'],
            $html,
            $options['border'],
            $options['ln'],
            $options['fill'],
            $options['reseth'],
            $options['align'],
            $options['autopadding']
        );

        if ($endline) {

            if ($options['lineafter'] > 0) $this->pdf->Ln($options['lineafter']);
            $this->pdf->SetY($this->pdf->GetY() + $this->pdf->getLastH());
        }
    }

    protected function write2ColsRow($col1Html, $col1Params = [], $col2Html, $col2Params = [])
    {
        $height = 0;

        $this->pdf->startTransaction();
        $this->writeInternal($col1Html, $col1Params, false);

        $height = ($height < $this->pdf->getLastH() ? $this->pdf->getLastH() : $height);

        $this->writeInternal($col2Html, $col2Params);

        $height = ($height < $this->pdf->getLastH() ? $this->pdf->getLastH() : $height);

        $this->pdf = $this->pdf->rollbackTransaction();

        $col1Params['cellheight'] = $height;
        $col2Params['cellheight'] = $height;

        $this->writeInternal($col1Html, $col1Params, false);
        $this->writeInternal($col2Html, $col2Params);
    }
}