<?php
require_once $CFG->libdir.'/formslib.php';

class mod_dataform_view_base_form extends moodleform {
    protected $_view = null;

    function mod_dataform_view_base_form($view) {
        $this->_view = $view;
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

        // general
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

        // view common settings
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'filterhdr', get_string('view:filter', 'dataform'));

        // filter
        if (!$filtersmenu = $this->_view->filters_menu()) {
            $filtersmenu = array(0 => get_string('filtersnonedefined', 'dataform'));
        } else {
           $filtersmenu = array(0 => 'Choose...') + $filtersmenu;
        }
        $mform->addElement('select', 'filter', get_string('view:filter', 'dataform'), $filtersmenu);
        $mform->setHelpButton('filter', array('view:filter', get_string('view:filter', 'dataform'), 'dataform'));
        $mform->setDefault('filter', 0);

        // entries per page
        $perpageoptions = array(0=>'Choose...',1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                            20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
        $mform->addElement('select', 'perpage', get_string('view:perpage', 'dataform'), $perpageoptions);
        $mform->setHelpButton('perpage', array('view:perpage', get_string('view:perpage', 'dataform'), 'dataform'));
        $mform->setDefault('perpage', 10);
                            
        // group by
        $mform->addElement('select', 'groupby', get_string('view:groupby', 'dataform'), array(0 => 'Choose...') + $this->_view->get_fields(array(-1), true));
        $mform->setHelpButton('groupby', array('view:groupby', get_string('view:groupby', 'dataform'), 'dataform'));

        // reset to default and switch editor buttons
        //-------------------------------------------------------------------------------
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'resetdefaultbutton', get_string('view:resettodefault', 'dataform'));
        $mform->registerNoSubmitButton('resetdefaultbutton');

        //if (can_use_html_editor()) {
            $switcheditorlabel = 'editor'. ($this->_view->editor ? 'disable' : 'enable');
            $mform->registerNoSubmitButton('switcheditorbutton');
            $buttonarray[] = &$mform->createElement('submit', 'switcheditorbutton', get_string($switcheditorlabel, 'dataform'));
        //}

        $mform->addGroup($buttonarray, 'resetswitchbuttonar', '', array(' '), false);
        $mform->closeHeaderBefore('resetswitchbuttonar');

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

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true);
    }

    /**
     *
     */
    function view_definition() {
    }

    /**
     *
     */
    function validation($data) {
        $errors= array();
        
        if ($this->_view->name_exists($data['name'], $data['vid'])) {
            $errors['invalidviewname'] = get_string('invalidviewname','dataform');
        }

        return $errors;
    }

}
?>