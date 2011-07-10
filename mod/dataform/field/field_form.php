<?php
require_once $CFG->libdir.'/formslib.php';

class mod_dataform_field_form extends moodleform {
    protected $_field = null;

    function mod_dataform_field_form($field) {
        $this->_field = $field;
        parent::moodleform();
    }

    function definition() {

        $mform =& $this->_form;

        // hidden optional params
        $mform->addElement('hidden', 'd', $this->_field->field->dataid);
        $mform->setType('d', PARAM_INT);

        $mform->addElement('hidden', 'type', $this->_field->type());
        $mform->setType('type', PARAM_ALPHA);

        $mform->addElement('hidden', 'fid', $this->_field->id());
        $mform->setType('fid', PARAM_INT);

        $streditinga = $this->_field->id() ? get_string('fieldedit', 'dataform', $this->_field->name()) : get_string('fieldnew', 'dataform', ucfirst($this->_field->type()));
        $mform->addElement('html', '<h2 class="mdl-align">'.format_string($streditinga).'</h2>');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name and description
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'32'));
        $mform->addElement('text', 'description', get_string('description'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
            $mform->setType('description', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
            $mform->setType('description', PARAM_CLEAN);
        }

//-------------------------------------------------------------------------------
        $this->field_definition();

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(true);
    }

    function field_definition() {
    }    
    
    function validation($data) {
        $errors= array();
        
        if ($this->_field->df->name_exists('fields', $data['name'], $data['fid'])) {
            $errors['fieldinvalidname'] = get_string('fieldinvalidname','dataform');
        }

        return $errors;
    }

}
?>