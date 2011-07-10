<?php
require_once($CFG->dirroot. '/mod/dataform/field/field_form.php');

class mod_dataform_field_file_form extends mod_dataform_field_form {

    function mod_dataform_field_file_form($field) {
        parent::mod_dataform_field_form($field);
    }

    /**
     *
     */
    function field_definition() {
        global $CFG;

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // TODO change strings to 'file icon width/height'
        // file icon width
        $mform->addElement('text', 'param1', get_string('fieldwidth', 'dataform'), array('size'=>'8'));
        $mform->setType('param1', PARAM_INT);
        $mform->addRule('param1', null, 'numeric', null, 'client');
        $mform->setDefault('param1', '');

        // file icon height
        $mform->addElement('text', 'param2', get_string('fieldheight', 'dataform'), array('size'=>'8'));
        $mform->setType('param2', PARAM_INT);
        $mform->addRule('param2', null, 'numeric', null, 'client');
        $mform->setDefault('param2', '');

        // max file size
        $choices = get_max_upload_sizes($CFG->maxbytes, $this->_field->df->course->maxbytes);
        $mform->addElement('select', 'param3', get_string('maxsize', 'dataform'), $choices);
    }

}
?>