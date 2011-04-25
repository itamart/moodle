<?php
require_once($CFG->dirroot. '/mod/dataform/view_form.php');

class mod_dataform_view_entries_form extends mod_dataform_view_form {

    function mod_dataform_view_entries_form($view) {
        parent::mod_dataform_view_form($view);
    }

    function view_settings() {
        $mform =& $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'filterhdr', get_string('view:filter', 'dataform'));

        // filter
        if (!$filtersmenu = $this->_view->filters_menu()) {
            $filtersmenu = array(0 => get_string('filtersnonedefined', 'dataform'));
        } else {
           $filtersmenu = array_merge(array(0 => 'Choose...'), $filtersmenu);
        }
        $mform->addElement('select', 'param1', get_string('view:filter', 'dataform'), $filtersmenu);
        $mform->setHelpButton('param1', array('view:filter', get_string('view:filter', 'dataform'), 'dataform'));
        $mform->setDefault('param1', 0);

        // entries per page
        $perpageoptions = array(0=>'Choose...',1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                            20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
        $mform->addElement('select', 'param2', get_string('view:perpage', 'dataform'), $perpageoptions);
        $mform->setHelpButton('param2', array('view:perpage', get_string('view:perpage', 'dataform'), 'dataform'));
        $mform->setDefault('param2', 10);
                            
        // group by
        $mform->addElement('select', 'param3', get_string('view:groupby', 'dataform'), array_merge(array(0 => 'Choose...'), $this->_view->get_fields(array(0), null, true)));
        $mform->setHelpButton('param3', array('view:groupby', get_string('view:groupby', 'dataform'), 'dataform'));

//-------------------------------------------------------------------------------
        // reset to default and switch editor buttons
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'resetdefaultbutton', get_string('view:resettodefault', 'dataform'));
        $mform->registerNoSubmitButton('resetdefaultbutton');

//        if (can_use_html_editor()) {
            $switcheditorlabel = 'view:editor'. ($this->_view->editor ? 'disable' : 'enable');
            $mform->registerNoSubmitButton('switcheditorbutton');
            $buttonarray[] = &$mform->createElement('submit', 'switcheditorbutton', get_string($switcheditorlabel, 'dataform'));
//        }

        $mform->addGroup($buttonarray, 'resetswitchbuttonar', '', array(' '), false);
        $mform->closeHeaderBefore('resetswitchbuttonar');
    }
        

    function view_definition() {
        $mform =& $this->_form;

        $generaltags = $this->_view->general_tags();
        $fieldtags = $this->_view->field_tags();

//-------------------------------------------------------------------------------
        // list header
        $mform->addElement('header', 'listheaderhdr', get_string('view:listheader', 'dataform'));

        $editoroptions = array('canUseHtmlEditor'=> $this->_view->can_use_html_editor(), 'rows'  => 5, 'cols'  => 65);        
        $mform->addElement('htmleditor', 'param4', '', $editoroptions);
        $mform->setType('param4', PARAM_RAW);
        //$mform->setHelpButton('param4', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        $listheaderatags=array();
        $listheaderatags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('view:availabletags','dataform'). '</label></div>');
        $listheaderatags[] = &$mform->createElement('html', '<div class="felement fselect">'. choose_from_menu_nested($generaltags, 'listheadertags', '', 'choose', 'insert_field_tags(this, editor_param4);this.selectedIndex=0;', 0, true). '</div>');
        $mform->addGroup($listheaderatags, 'listheaderatags', '', array(' '), false);

//-------------------------------------------------------------------------------
        // repeated entry
        $mform->addElement('header', 'listbodyhdr', get_string('view:listbody', 'dataform'));

        $editoroptions = array('canUseHtmlEditor'=> $this->_view->can_use_html_editor(), 'rows'  => 10, 'cols'  => 65);        
        $mform->addElement('htmleditor', 'param5', '', $editoroptions);
        $mform->setType('param5', PARAM_RAW);
        //$mform->setHelpButton('param5', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        $listbodyatags=array();
        $listbodyatags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('view:availabletags','dataform'). '</label></div>');
        $listbodyatags[] = &$mform->createElement('html', '<div class="felement fselect">'. choose_from_menu_nested($fieldtags, 'listbodytags', '', 'choose', 'insert_field_tags(this, editor_param5);this.selectedIndex=0;', 0, true). '</div>');
        $mform->addGroup($listbodyatags, 'listbodyatags', '', array(' '), false);

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'listfooterhdr', get_string('view:listfooter', 'dataform'));

        // list footer
        $editoroptions = array('canUseHtmlEditor'=> $this->_view->can_use_html_editor(), 'rows'  => 5, 'cols'  => 65);        
        $mform->addElement('htmleditor', 'param6', '', $editoroptions);
        $mform->setType('param6', PARAM_RAW);
        //$mform->setHelpButton('param6', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        $listfooteratags=array();
        $listfooteratags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('view:availabletags','dataform'). '</label></div>');
        $listfooteratags[] = &$mform->createElement('html', '<div class="felement fselect">'. choose_from_menu_nested($generaltags, 'listfootertags', '', 'choose', 'insert_field_tags(this, editor_param6);this.selectedIndex=0;', 0, true). '</div>');
        $mform->addGroup($listfooteratags, 'listfooteratags', '', array(' '), false);
    }
    
}
?>
