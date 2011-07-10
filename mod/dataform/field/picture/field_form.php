<?php
require_once($CFG->dirroot. '/mod/dataform/field/field_form.php');

class mod_dataform_field_picture_form extends mod_dataform_field_form {

    function mod_dataform_field_picture_form($field) {
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
 
        // large width/height/units
        $largegrp=array();
        $largegrp[] = &$mform->createElement('text', 'param1', null, array('size'=>'8'));
        $largegrp[] = &$mform->createElement('text', 'param2', null, array('size'=>'8'));
        $mform->addGroup($largegrp, 'largegrp', get_string('fieldpicturelarge', 'dataform'), 'x', false);
        $mform->setType('param1', PARAM_INT);
        $mform->setType('param2', PARAM_INT);
        $mform->addGroupRule('largegrp', array('param1' => array(array(null, 'numeric', null, 'client'))));
        $mform->addGroupRule('largegrp', array('param2' => array(array(null, 'numeric', null, 'client'))));
        $mform->setDefault('param1', '');
        $mform->setDefault('param2', '');
        //$mform->setHelpButton('largegrp', array("fieldwidth", get_string('fieldwidth', 'dataform'), 'dataform'));
        
        // thumbnail width/height/units
        $thumbnailgrp=array();
        $thumbnailgrp[] = &$mform->createElement('text', 'param4', null, array('size'=>'8'));
        $thumbnailgrp[] = &$mform->createElement('text', 'param5', null, array('size'=>'8'));
        $mform->addGroup($thumbnailgrp, 'thumbnailgrp', get_string('fieldpicturethumbnail', 'dataform'), 'x', false);
        $mform->setType('param4', PARAM_INT);
        $mform->setType('param5', PARAM_INT);
        $mform->addGroupRule('thumbnailgrp', array('param4' => array(array(null, 'numeric', null, 'client'))));
        $mform->addGroupRule('thumbnailgrp', array('param5' => array(array(null, 'numeric', null, 'client'))));
        $mform->setDefault('param4', '');
        $mform->setDefault('param5', '');
         //$mform->setHelpButton('thumbnailgrp', array("fieldwidth", get_string('fieldwidth', 'dataform'), 'dataform'));

        // max file size
        $choices = get_max_upload_sizes($CFG->maxbytes, $this->_field->df->course->maxbytes);
        $mform->addElement('select', 'param3', get_string('maxsize', 'dataform'), $choices);
    }

}
?>