<?php
require_once($CFG->dirroot. '/mod/dataform/field/field_form.php');

class mod_dataform_field_url_form extends mod_dataform_field_form {

    function mod_dataform_field_url_form($field) {
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
        $mform->addElement('checkbox', 'param1', get_string('fieldallowautolink', 'dataform'));

        // force link name
        $mform->addElement('text', 'param2', get_string('forcelinkname', 'dataform'), array('size'=>'32'));
        $mform->setType('param2', PARAM_NOTAGS);
    }

}
?>