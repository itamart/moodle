<?php
require_once($CFG->dirroot. '/mod/dataform/field/field_form.php');

class mod_dataform_field_date_form extends mod_dataform_field_form {

    function mod_dataform_field_date_form($field) {
        parent::mod_dataform_field_form($field);
    }

    /**
     *
     */
    function field_definition() {

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
    }

}
?>