<?php
require_once($CFG->dirroot. '/mod/dataform/view/view_form.php');

class mod_dataform_view_tabular_form extends mod_dataform_view_base_form {

    function mod_dataform_view_tabular_form($view) {
        parent::mod_dataform_view_base_form($view);
    }

    /**
     *
     */
    function view_definition() {

        $mform =& $this->_form;

        $generaltags = $this->_view->general_tags();
        $fieldtags = $this->_view->field_tags();

        // table header
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'listheaderhdr', get_string('view:listheader', 'dataform'));

        $editoroptions = array('canUseHtmlEditor'=> $this->_view->can_use_html_editor(), 'rows'  => 5, 'cols'  => 65);        
        $mform->addElement('htmleditor', 'param1', '', $editoroptions);
        $mform->setType('param1', PARAM_RAW);
        //$mform->setHelpButton('param1', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        $listheaderatags=array();
        $listheaderatags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('view:availabletags','dataform'). '</label></div>');
        $listheaderatags[] = &$mform->createElement('html', '<div class="felement fselect">'. choose_from_menu_nested($generaltags, 'listheadertags', '', 'choose', 'insert_field_tags(this, editor_param1);this.selectedIndex=0;', 0, true). '</div>');
        $mform->addGroup($listheaderatags, 'listheaderatags', '', array(' '), false);

        // alignment
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'listalignmenthdr', get_string('alignment', 'dataform'));

        $mform->addElement('textarea', 'param3', '', array('rows'  => 3, 'cols'  => 80));
        $mform->setType('param3', PARAM_NOTAGS);

        // repeated entry
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'listbodyhdr', get_string('view:listbody', 'dataform'));

        $editoroptions = array('canUseHtmlEditor'=> $this->_view->can_use_html_editor(), 'rows'  => 10, 'cols'  => 65);        
        $mform->addElement('htmleditor', 'param2', '', $editoroptions);
        $mform->setType('param2', PARAM_RAW);
        //$mform->setHelpButton('param2', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        $listbodyatags=array();
        $listbodyatags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('view:availabletags','dataform'). '</label></div>');
        $listbodyatags[] = &$mform->createElement('html', '<div class="felement fselect">'. choose_from_menu_nested($fieldtags, 'listbodytags', '', 'choose', 'insert_field_tags(this, editor_param2);this.selectedIndex=0;', 0, true). '</div>');
        $mform->addGroup($listbodyatags, 'listbodyatags', '', array(' '), false);

    }

}
?>