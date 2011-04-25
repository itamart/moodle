<?php
require_once $CFG->libdir.'/formslib.php';

class mod_dataform_view_form extends moodleform {
    protected $_view = null;

    function mod_dataform_view_form($view) {
        $this->_view = $view[0];
        parent::moodleform();
    }

    function definition() {

        $mform =& $this->_form;

        // hidden optional params
        $mform->addElement('hidden', 'd', $this->_view->view->dataid);
        $mform->setType('d', PARAM_INT);

        $mform->addElement('hidden', 'type', $this->_view->type());
        $mform->setType('type', PARAM_ALPHA);

        $mform->addElement('hidden', 'vid', $this->_view->view->id);
        $mform->setType('vid', PARAM_INT);

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name and description
        $mform->addElement('text', 'name', get_string('name'));
        $mform->addElement('text', 'description', get_string('description'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
            $mform->setType('description', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
            $mform->setType('description', PARAM_CLEAN);
        }

         // visibility
        $visibilityoptions = array(0=>'disabled',1=>'enabled',2=>'visible');
        $mform->addElement('select', 'visible', get_string('view:visibility', 'dataform'), $visibilityoptions);
        $mform->setHelpButton('visible', array('view:visibility', get_string('view:visibility', 'dataform'), 'dataform'));
        $mform->setDefault('visible', 2);

//-------------------------------------------------------------------------------
        $this->view_settings();

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'sectionhdr', get_string('view:section', 'dataform'));

        // section position
        $sectionposoptions = array(0 => 'top', 1 => 'left', 2 => 'right', 3 => 'bottom');
        $mform->addElement('select', 'sectionpos', get_string('view:sectionpos', 'dataform'), $sectionposoptions);
        $mform->setHelpButton('sectionpos', array('view:sectionpos', get_string('view:sectionpos', 'dataform'), 'dataform'));
        $mform->setDefault('sectionpos', 0);
        // section
        $editoroptions = array('canUseHtmlEditor'=> $this->_view->can_use_html_editor(), 'rows'  => 5, 'cols'  => 65);        
        $mform->addElement('htmleditor', 'section', '', $editoroptions);
        $mform->setType('section', PARAM_RAW);
        //$mform->setHelpButton('section', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

        $generaltags = $this->_view->general_tags();
        $sectionatags=array();
        $sectionatags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('view:availabletags','dataform'). '</label></div>');
        $sectionatags[] = &$mform->createElement('html', '<div class="felement fselect">'. choose_from_menu_nested($generaltags, 'sectiontags', '', 'choose', 'insert_field_tags(this, editor_section);this.selectedIndex=0;', 0, true). '</div>');
        $mform->addGroup($sectionatags, 'sectionatags', '', array(' '), false);

//-------------------------------------------------------------------------------
        $this->view_definition();

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(true, get_string('saveview', 'dataform'));
    }

    function view_settings() {
    }
    
    function view_definition() {
    }    
    
    function validation($data) {
        $errors= array();
        
        if ($this->_view->name_exists($data['name'], $data['vid'])) {
            $errors['invalidviewname'] = get_string('invalidviewname','dataform');
        }

        return $errors;
    }

}
?>
