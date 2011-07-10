<?php
require_once($CFG->dirroot. '/mod/dataform/field/field_form.php');

class mod_dataform_field_textarea_form extends mod_dataform_field_form {

    function mod_dataform_field_textarea_form($field) {
        parent::mod_dataform_field_form($field);
    }

    /**
     *
     */
    function field_definition() {

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // auto link
        $mform->addElement('checkbox', 'param1', get_string('editorenable', 'dataform'));

        // field width
        $mform->addElement('text', 'param2', get_string('columns', 'dataform'), array('size'=>'8'));
        $mform->setType('param2', PARAM_INT);
        $mform->addRule('param2', null, 'numeric', null, 'client');
        $mform->setDefault('param2', 60);

        $mform->addElement('text', 'param3', get_string('rows', 'dataform'), array('size'=>'8'));
        $mform->setType('param3', PARAM_INT);
        $mform->addRule('param3', null, 'numeric', null, 'client');
        $mform->setDefault('param3', 35);
    }
}
?>