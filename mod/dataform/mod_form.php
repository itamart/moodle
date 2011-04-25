<?php
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_dataform_mod_form extends moodleform_mod {

    function definition() {

        global $CFG;
        $mform =& $this->_form;

        $ynoptions = array(0 => get_string('no'), 1 => get_string('yes'));
        
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        // intro
        $mform->addElement('htmleditor', 'intro', get_string('intro', 'dataform'));
        $mform->setType('intro', PARAM_RAW);
            //$mform->addRule('intro', null, 'required', null, 'client');
        $mform->setHelpButton('intro', array('writing', 'questions', 'richtext'), false, 'editorhelpbutton');

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'timinghdr', get_string('timing', 'form'));

        // time available
        $mform->addElement('date_time_selector', 'timeavailable', get_string('df:timeavailable', 'dataform'), array('optional'=>true));
        $mform->setHelpButton('timeavailable', array('timeavailable', get_string('df:timeavailable', 'dataform'), 'dataform'));

        // time due
        $mform->addElement('date_time_selector', 'timedue', get_string('df:timedue', 'dataform'), array('optional'=>true));
        $mform->setHelpButton('timedue', array('timedue', get_string('df:timedue', 'dataform'), 'dataform'));
        $mform->disabledIf('timedue', 'interval', 'gt', 0);

        // interval between required entries
        $intervaloptions = array();
        $intervaloptions[0] = get_string('none');
        $intervaloptions[1800] = get_string('numminutes', '', 30);
        $intervaloptions[3600] = get_string('numminutes', '', 60);
        $intervaloptions[43200] = get_string('numhours', '', 12);
        $intervaloptions[86400] = get_string('numhours', '', 24);
        for($i=2; $i<=30; $i++) {
             $seconds = $i*86400;
             $intervaloptions[$seconds] = get_string('numdays', '', $i);
        }

        $mform->addElement('select', 'timeinterval', get_string('df:timeinterval', 'dataform'), $intervaloptions);
        $mform->setHelpButton('timeinterval', array('timeinterval', get_string('df:timeinterval', 'dataform'), 'dataform'));
        $mform->setDefault('timeinterval', 0);
        $mform->disabledIf('timeinterval', 'timeavailable[off]', 'checked');
        $mform->disabledIf('timeinterval', 'timedue[off]');

        $numintervalsoptions = array();
        for ($i = 1; $i <= 100; $i++) {
            $numintervalsoptions[$i] = $i;
        }
        $mform->addElement('select', 'intervalcount', get_string('df:intervalcount', 'dataform'), $numintervalsoptions);
        $mform->setHelpButton('intervalcount', array('intervalcount', get_string('df:intervalcount', 'dataform'), 'dataform'));
        $mform->setDefault('intervalcount', 1);
        $mform->disabledIf('intervalcount', 'timeavailable[off]', 'checked');
        $mform->disabledIf('intervalcount', 'timedue[off]');
        $mform->disabledIf('intervalcount', 'timeinterval', 'eq', 0);
        
                
        // allow late
        $mform->addElement('checkbox', 'allowlate', get_string('df:lateallow', 'dataform') , get_string('df:lateuse', 'dataform'));


//-------------------------------------------------------------------------------
        $mform->addElement('header', 'entrieshdr', get_string('entries', 'dataform'));
        
        // required entries
        $countoptions = array(0=>get_string('none'))+
                        (array_combine(range(1, DATAFORM_MAX_ENTRIES),//keys
                                        range(1, DATAFORM_MAX_ENTRIES)));//values
        $mform->addElement('select', 'requiredentries', get_string('requiredentries', 'dataform'), $countoptions);
        $mform->setHelpButton('requiredentries', array('requiredentries', get_string('requiredentries', 'dataform'), 'dataform'));

        // required entries to view
        $mform->addElement('select', 'requiredentriestoview', get_string('requiredentriestoview', 'dataform'), $countoptions);
        $mform->setHelpButton('requiredentriestoview', array('requiredentriestoview', get_string('requiredentriestoview', 'dataform'), 'dataform'));

        // max entries
        $mform->addElement('select', 'maxentries', get_string('maxentries', 'dataform'), $countoptions);
        $mform->setHelpButton('maxentries', array('maxentries', get_string('maxentries', 'dataform'), 'dataform'));

        // time limit to manage an entry
        $timelimitgrp=array();
        $timelimitgrp[] = &$mform->createElement('text', 'timelimit');
        $timelimitgrp[] = &$mform->createElement('checkbox', 'timelimitenable', '', get_string('enable'));
        $mform->addGroup($timelimitgrp, 'timelimitgrp', get_string('df:timelimit', 'dataform'), array(' '), false);
        $mform->setType('timelimit', PARAM_TEXT);
        $timelimitgrprules = array();
        $timelimitgrprules['timelimit'][] = array(null, 'numeric', null, 'client');
        $mform->addGroupRule('timelimitgrp', $timelimitgrprules);
        $mform->disabledIf('timelimitgrp', 'timelimitenable');
        $mform->setHelpButton('timelimitgrp', array("timelimit", get_string('entrytimer', 'dataform'), 'dataform'));
        $mform->setDefault('timelimit', '');
        $mform->setDefault('timelimitenable', false);

        // approval
        $mform->addElement('select', 'approval', get_string('requireapproval', 'dataform'), $ynoptions);
        $mform->setHelpButton('approval', array('requireapproval', get_string('requireapproval', 'dataform'), 'dataform'));

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'commentshdr', get_string('comments', 'dataform'));

        // comments
        $mform->addElement('select', 'comments', get_string('comments', 'dataform'), $ynoptions);
        $mform->setHelpButton('comments', array('comments', get_string('allowcomments', 'dataform'), 'dataform'));

        // rss articles
        if($CFG->enablerssfeeds && $CFG->dataform_enablerssfeeds){
            $mform->addElement('select', 'rssarticles', get_string('numberrssarticles', 'dataform') , $countoptions);
        }

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'gradeshdr', get_string('grades', 'grades'));

        // rating
        $mform->addElement('checkbox', 'assessed', get_string('df:ratingsallow', 'dataform') , get_string('df:ratingsuse', 'dataform'));
        $mform->addElement('modgrade', 'scale', get_string('grade'), false);
        $mform->disabledIf('scale', 'assessed');


        $this->standard_coursemodule_elements(array('groups'=>true, 'groupings'=>true, 'groupmembersonly'=>true));

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values){
        if (empty($default_values['scale'])){
            $default_values['assessed'] = 0;
        }
        if (!empty($default_values['timeinterval'])){
            $default_values['timedue'] = $default_values['timeinterval'] * $default_values['intervalcount'];
        }
    }

}
?>
