<?php
require_once($CFG->dirroot. '/mod/dataform/field/field_form.php');

class mod_dataform_field_text_form extends mod_dataform_field_form {

    function mod_dataform_field_text_form($field) {
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

        // field width
        $fieldwidthgrp=array();
        $fieldwidthgrp[] = &$mform->createElement('text', 'param2', null, array('size'=>'8'));
        $fieldwidthgrp[] = &$mform->createElement('select', 'param3', null, array('px' => 'px', 'em' => 'em', '%' => '%'));
        $mform->addGroup($fieldwidthgrp, 'fieldwidthgrp', get_string('fieldwidth', 'dataform'), array(' '), false);
        $mform->setType('param2', PARAM_INT);
        //$mform->addRule('param2', null, 'numeric', null, 'client');
        $mform->addGroupRule('fieldwidthgrp', array('param2' => array(array(null, 'numeric', null, 'client'))));        
        $mform->disabledIf('param3', 'param2', 'eq', '');
        //$mform->setHelpButton('fieldwidthgrp', array("fieldwidth", get_string('fieldwidth', 'dataform'), 'dataform'));
        $mform->setDefault('param2', '');
        $mform->setDefault('param3', 'px');
    }

}
?>