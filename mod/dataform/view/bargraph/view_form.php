<?php
require_once($CFG->dirroot. '/mod/dataform/view/entries/view_form.php');

class mod_dataform_view_bargraph_form extends mod_dataform_view_entries_form {

    function mod_dataform_view_bargraph_form($view) {
        parent::mod_dataform_view_entries_form($view);
    }

    function view_definition() {

        $mform =& $this->_form;

        $fieldtags = $this->_view->field_tags();

//-------------------------------------------------------------------------------
        // list header
        $mform->addElement('header', 'listheaderhdr', get_string('view:listheader', 'dataform'));

        $editoroptions = array('canUseHtmlEditor'=> $this->_view->can_use_html_editor(), 'rows'  => 5, 'cols'  => 65);        
        $mform->addElement('htmleditor', 'param4', '', $editoroptions);
        $mform->setType('param4', PARAM_RAW);
        //$mform->setHelpButton('param3', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        $listheaderatags=array();
        $listheaderatags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('view:availabletags','dataform'). '</label></div>');
        $listheaderatags[] = &$mform->createElement('html', '<div class="felement fselect">'. choose_from_menu_nested($fieldtags, 'listheadertags', '', 'choose', 'insert_field_tags(this, editor_param4);this.selectedIndex=0;', 0, true). '</div>');
        $mform->addGroup($listheaderatags, 'listheaderatags', '', array(' '), false);

//-------------------------------------------------------------------------------
        // repeated entry
        $mform->addElement('header', 'listbodyhdr', get_string('view:listbody', 'dataform'));

        $editoroptions = array('canUseHtmlEditor'=> $this->_view->can_use_html_editor(), 'rows'  => 10, 'cols'  => 65);        
        $mform->addElement('htmleditor', 'param5', '', $editoroptions);
        $mform->setType('param5', PARAM_RAW);
        //$mform->setHelpButton('param4', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        $listbodyatags=array();
        $listbodyatags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('view:availabletags','dataform'). '</label></div>');
        $listbodyatags[] = &$mform->createElement('html', '<div class="felement fselect">'. choose_from_menu_nested($fieldtags, 'listbodytags', '', 'choose', 'insert_field_tags(this, editor_param5);this.selectedIndex=0;', 0, true). '</div>');
        $mform->addGroup($listbodyatags, 'listbodyatags', '', array(' '), false);

    }

    
}
?>
