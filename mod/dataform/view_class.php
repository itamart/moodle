<?php  // $Id$
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2005 Moodle Pty Ltd    http://moodle.com                //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Base class for Dataform View Types
 * (see view/<view type>/view.class.php)
 */
class dataform_view_base { 

    protected $type = 'unknown';      // Subclasses must override the type with their name

    public $view = NULL;            // The view object itself, if we know it

    protected $df = NULL;           // The dataform object that this view belongs to
    protected $fields = array();
    
    protected $iconwidth = 16;        // Width of the icon for this viewtype
    protected $iconheight = 16;       // Width of the icon for this viewtype

    public $editor = 0;

    /**
     * Constructor   
     * View or dataform or both, each can be id or object
     */
    public function dataform_view_base($view=0, $df=0) {
        global $SESSION;
        
        if (empty($df)) {
            error('Programmer error: You must specify dataform id or object when defining a field class. ');
        } else if (is_object($df)) {
            $this->df = $df;
        } else {    // dataform id
            $this->df = new dataform($df);
        }
        
        if (!empty($view)) {
            if (is_object($view)) {
                $this->view = $view;  // Programmer knows what they are doing, we hope
            } else if (!$this->view = get_record('dataform_views','id',$view)) {
                error('Bad view ID encountered: '.$view);
            }
        }

        if (empty($this->view)) {         // We need to define some default values
            $this->set_view();
        }
        
        $this->editor = isset($SESSION->dataform_use_editor) ? $SESSION->dataform_use_editor : (can_use_html_editor() ? 1 : 0);
        $SESSION->dataform_use_editor = $this->editor;
    }

    /**
     * Set view 
     */
    protected function set_view($fromform = null) {
        $this->view = new object;
        $this->view->id = (isset($fromform) ? $fromform->vid : 0);
        $this->view->type   = $this->type;
        $this->view->dataid = $this->df->id();
        $this->view->name = (isset($fromform) ? trim($fromform->name) : 'New \''. $this->type. '\' view');
        $this->view->description = (isset($fromform) ? trim($fromform->description) : '');
        $this->view->visible = ((isset($fromform) and isset($fromform->visible)) ? $fromform->visible : 2);
        $this->view->section = ((isset($fromform) and isset($fromform->section)) ? $fromform->section : '');
        $this->view->sectionpos = ((isset($fromform) and isset($fromform->sectionpos)) ? $fromform->sectionpos : 0);
        for ($i=1; $i<=10; $i++) {
            if (isset($fromform) and isset($fromform->{'param'.$i})) {
                $this->view->{'param'.$i} = trim($fromform->{'param'.$i});
            } else {
                $this->view->{'param'.$i} = '';
            }
        }
        return true;
    }

    /**
     * Insert a new view into the database
     * $this->view is assumed set         
     */
    public function insert_view($fromform = NULL) {
        if (!empty($fromform)) {
            $this->set_view($fromform);
        }

        if (empty($this->view)) {
            notify('Programmer error: View has not been set yet!  See set_view()');
            return false;
        }
        if (!$this->view->id = insert_record('dataform_views',$this->view)){
            notify('Insertion of new view failed!');
            return false;
        }
        return true;
    }

    /**
     * Update a view in the database      
     * $this->view is assumed set         
     */
    public function update_view($fromform = NULL) {
        if (!empty($fromform)) {
            $this->set_view($fromform);
        }

        if (!update_record('dataform_views', $this->view)) {
            notify('updating view failed!');
            return false;
        }
        return true;
    }

    /**
     * Delete a view from the database    
     */
    public function delete_view() {
        if (!empty($this->view->id)) {
            delete_records('dataform_views', 'id', $this->view->id);
        }
        return true;
    }

    /**
     * 
     */
    public function get_form() {
    }

    /**
     * 
     */
    public function display_view($editentries = 0, $return = false) {
    }

    /**
     * TODO   
     */
    public function get_fields($exclude = null, $include = null, $menu = false) {
        if (!$this->fields) {
            $this->fields = $this->df->get_fields();
        }
        
        if (!$exclude and !$include and !$menu) {
            return $this->fields;
        } else {
            $fields = array();
            foreach ($this->fields as $fieldid => $field) {
                if (($exclude and !in_array($fieldid, $exclude)) or ($include and in_array($fieldid, $include))) {
                    if ($menu) {
                        $fields[$fieldid] = get_string($field->field->name, 'dataform');
                    } else {
                        $fields[$fieldid] = $field;
                    }
                }
            }
            return $fields;
        }
    }

    /**
     * Generate a default view
     * Should be redefined in sub-classes
     */
    public function generate_default_view() {
    }

    /**
     * output null   
     */
    public function switch_editor() {
        $this->editor = $this->editor ? 0 : 1;
        $SESSION->dataform_use_editor = $this->editor;
    }

    /**
     * 
     */
    public function can_use_html_editor() {
        return ($this->editor and can_use_html_editor());
    }

    /**
     * 
     */
    public function editors() {
        return array('section');
    }

    /**
     * 
     */
    public function filter() {
        return false;
    }

    // TODO:
    /**
     * Subclass must override
     */
    public function replace_field_in_view($searchfieldname, $newfieldname) {
        // addslashes(str_ireplace('[['.$searchfieldname.']]', $prestring.$newfieldname.$poststring, $data->singleview));
        // $this->update_view();
    }

    /**
     * Returns the name/type of the view
     */
    public function name_exists($name, $viewid) {
        return $this->df->name_exists('views', $name, $viewid);
    }

    /**
     * Returns the type of the view
     */
    public function type() {
        return $this->type;
    }

    /**
     * Returns the name/type of the view
     */
    public function name() {
        return get_string('name'.$this->type, 'dataform');
    }

    /**
     * Prints the respective type icon
     */
    public function image() {
        global $CFG;

        $str = '<a href="views.php?d='.$this->df->id().'&amp;edit='.$this->view->id.'&amp;sesskey='.sesskey().'">';
        $str .= '<img src="'.$CFG->modpixpath.'/dataform/view/'.$this->type.'/icon.gif" ';
        $str .= 'height="'.$this->iconheight.'" width="'.$this->iconwidth.'" alt="'.$this->type.'" title="'.$this->type.'" /></a>';
        return $str;
    }

    /**
     * 
     */
    public function general_tags() {
        $patterns = $this->patterns();
        return $this->select_tags($patterns);
    }

    /**
     * 
     */
    public function field_tags() {
        $patterns = array();
        foreach ($this->get_fields() as $field) {
            if ($fieldpatterns = $field->patterns()) {
                $patterns = array_merge_recursive($patterns, $fieldpatterns);
            }
        }        
        return $this->select_tags($patterns);
    }

    /**
     * should be defined in subclasses
     */
    protected function patterns($options = null, $parent = true) {
        $patterns = array();
 
        $patterns['menus'] = array();
        $patterns['menus']['##viewsmenu##'] = $this->print_views_menu(true);

        return $patterns;
    }

    /**
     * Returns select menu of available view tags to display in
     * @param array     $patterns
     * @param boolean   $incex true to include in the returend set and false to exclude from 
     * @param array     $groups array of keys to include or exclude 
     * @param array     $groups array of keys to include or exclude
     * TODO: check why the exclude doesn't work
     */
    protected function select_tags($patterns, $include = false, $iegroups = NULL, $ietags = NULL) {
        $tags = array();
        
        if (!empty($patterns)) {
            $tmptags = $patterns;    
            // extract the desired items
            if (!empty($iegroups) or !empty($ietags)) {
                foreach ($tmptags as $group => $val) {
                    if (!empty($iegroups)) {
                        if (in_array($group, $iegroups) and !$include) {
                            unset($tmptags[$group]);
                        } else if (!in_array($group, $iegroups) and $include) {
                            unset($tmptags[$group]);
                        }
                    } else { // check if there are some tags for this group
                        $certaintags = array();
                        foreach (array_keys($val) as $tag) {
                            if (in_array($tag, $ietags) and !$include) {
                                unset($tmptags[$group][$tag]);
                            } else if (!in_array($tag, $ietags) and $include) {
                                unset($tmptags[$group][$tag]);
                            }
                        }
                    }
                }
            }
            // generate the tags list
            foreach ($tmptags as $group => $items) {
                $groupname = get_string('tags:'. $group, 'dataform');
                $tags[$groupname] = array();
                foreach (array_keys($items) as $tag) {
                    $tags[$groupname][$tag] = $tag;
                }
            }
        }
        return $tags;
    }
    
    /**
     * $patterns array of arrays of pattern replacement pairs
     */
    protected function replace_tags($patterns, $subject) {
        // TODO check what's going on with $patterns here
        $tags = array();
        $replacements = array();
        foreach ($patterns as $pattern) {
            foreach ($pattern as $tag => $val) {
                $tags[] = $tag;
                $replacements[] = $val;
            }
        }
            
        $newsubject = str_ireplace($tags, $replacements, $subject);

        return $newsubject;
    }


    /**
     * Check if a view from an add form is empty
     */
    protected function notemptyview($value, $name) {
        return !empty($value);
    }

    /**
     * Just in case a view needs to print something before the whole form
     */
    protected function print_before_form() {
    }

    /**
     * Just in case a view needs to print something after the whole form
     */
    protected function print_after_form() {
    }

    /**
     * check the multple existence any tag in a view
     * should be redefined in sub-classes
     * output bool true-valid, false-invalid
     */
    function tags_check($template) {
        $tagsok = true; // let's be optimistic
        foreach ($this->df->get_fields_records() as $field) { // only user fields
            $pattern="/\[\[".$field->name."\]\]/i";
            if (preg_match_all($pattern, $template, $dummy) > 1) {
                $tagsok = false;
                notify ('[['.$field->name.']] - '.get_string('multipletags','dataform'));
            }
        }
        // else return true
        return $tagsok;
    }

    /**
     * 
     */
    protected function print_views_menu($return = false) {
        global $CFG;
        
        if (!$views = get_records('dataform_views','dataid', $this->df->id(), 'type ASC', 'id, type, name, visible')) {
            return;
        }
        
        $menuviews = array();
        
        // get first the visible views
        foreach ($views as $vid => $view){
            if ($view->visible > 1) {   // show to user
                $menuviews[$vid] = $view->name;
            }
        }

        // add the half and non visible views
        if (has_capability('mod/dataform:managetemplates', $this->df->context)) {
            foreach ($views as $vid => $view){
                if ($view->visible < 2) {
                    $enclose = $view->visible ? '(' : '-';
                    $declose = $view->visible ? ')' : '-';
                    $menuviews[$vid] = $enclose. $view->name. $declose;
                }
            }
        }

        // $this->filter must be defined in the subclass
        $baseurl = $CFG->wwwroot. '/mod/dataform/view.php?d='. $this->df->id(). '&amp;sesskey='. sesskey(). '&amp;filter='. $this->filter->id;

        // Display the view form jump list
        if ($return) {
            return '&nbsp;&nbsp;<label for="viewbrowse_jump">'. get_string('viewcurrent','dataform'). ':</label>&nbsp;'.
                popup_form($baseurl. '&amp;view=', $menuviews, 'viewbrowse_jump', $this->view->id, 'choose', '', '', true);
                //helpbutton('views', get_string('addaview','dataform'), 'dataform');
        } else {
            echo '&nbsp;&nbsp;<label for="viewbrowse_jump">', get_string('viewcurrent','dataform'), ':</label>&nbsp;';
            popup_form($baseurl. '&amp;view=', $menuviews, 'viewbrowse_jump', $this->view->id, 'choose');
            //helpbutton('views', get_string('addaview','dataform'), 'dataform');
        }
    }

}

?>