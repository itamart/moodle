<?php // $Id$
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999-onwards Moodle Pty Ltd  http://moodle.com          //
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

//2/19/07:  Advanced search of the date field is currently disabled because it does not track
// pre 1970 dates and does not handle blank entrys.  Advanced search functionality for this field
// type can be enabled once these issues are addressed in the core API.

class dataform_field__entry extends dataform_field_base {

    public $type = '_entry';
    
    /**
     * 
     */
    public function dataform_field__entry($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function display_search($value = 0) {
        return '';
    }
    
    /**
     * 
     */
    public function display_sort($order = 0, $dir = 0) {
        return '';
    }
    
    /**
     * 
     */
    public function patterns($record = 0, $edit = false, $enabled = false) {
        global $CFG;
        
        $patterns = array();
        if (!$record) { // new record (0) displays nothing
            $patterns['actions'] = array();
            $patterns['actions']['##edit##'] = '';
            $patterns['actions']['##delete##'] = '';
            $patterns['actions']['##approve##'] = '';
            $patterns['actions']['##select##'] = '';

            $patterns['reference'] = array();
            $patterns['reference']['##more##'] = '';
            $patterns['reference']['##moreurl##'] = '';

            $patterns['entryinfo'] = array();
            $patterns['entryinfo']['##entryid##'] = '';
            $patterns['entryinfo']['##approved##'] = '';

            $patterns['authorinfo'] = array();
            $patterns['authorinfo']['##author##'] = '';
            $patterns['authorinfo']['##author:id##'] = '';
            $patterns['authorinfo']['##author:picture##'] = '';
            $patterns['authorinfo']['##author:picturelarge##'] = '';

        } else {  // no edit mode for this field
            $patterns = array();
 
            // actions
            $patterns['actions'] = array();
            // TODO: should allow selecting for duplicating purposes
            $patterns['actions']['##select##'] = !$enabled ? '' : '<input type="checkbox" name="selector_'. $record->id. '" />';
            $patterns['actions']['##edit##'] = !$enabled ? '' : '<a href="'. $CFG->wwwroot. '/mod/dataform/view.php?d='. $this->df->id(). '&amp;editentries='. $record->id. '&amp;sesskey='. sesskey(). '"><img src="'. $CFG->pixpath. '/t/edit.gif" class="iconsmall" alt="'. get_string('edit', 'dataform'). '" title="'. get_string('edit', 'dataform'). '" /></a>';
            $patterns['actions']['##delete##'] = !$enabled ? '' : '<a href="'. $CFG->wwwroot. '/mod/dataform/view.php?d='. $this->df->id(). '&amp;delete='. $record->id. '&amp;sesskey='. sesskey(). '"><img src="'. $CFG->pixpath. '/t/delete.gif" class="iconsmall" alt="'. get_string('delete', 'dataform'). '" title="'. get_string('delete', 'dataform'). '" /></a>';

            // approve
            $approvable = $this->df->data->approval and has_capability('mod/dataform:approve', $this->df->context);
            $toapprove = $record->approved ? 'disapprove' : 'approve';
            $patterns['actions']['##approve##'] = !$approvable ? '' : '<a href="'. $CFG->wwwroot. '/mod/dataform/view.php?d='. $this->df->id(). '&amp;'. $toapprove. '='. $record->id. '&amp;sesskey='. sesskey(). '"><img src="'. $CFG->pixpath. '/i/'. $toapprove. '.gif" class="iconsmall" alt="'. get_string($toapprove, 'dataform'). '" title="'. get_string('approve', 'dataform'). '" /></a>';

            // reference
            $patterns['reference'] = array();
            $moreurl = $CFG->wwwroot. '/mod/dataform/view.php?rid='. $record->id;
            $patterns['reference']['##more##'] = '<a href="' . $moreurl . '"><img src="' . $CFG->pixpath . '/i/search.gif" class="iconsmall" alt="' . get_string('more', 'dataform') . '" title="' . get_string('more', 'dataform') . '" /></a>';
            $patterns['reference']['##moreurl##'] = $moreurl;

            // author info
            // TODO: print_user_picture deprecated in 2.0
            $patterns['authorinfo'] = array();
            $patterns['authorinfo']['##author##'] = '<a href="'. $CFG->wwwroot. '/user/view.php?id='. $record->userid.
                                '&amp;course='. $this->df->course->id.'">'. fullname($record). '</a>';            
            $patterns['authorinfo']['##author:id##'] = $record->userid;
            $patterns['authorinfo']['##author:picture##'] = print_user_picture($record->userid, $this->df->course->id, get_field('user','picture','id',$record->userid), false, true);
            $patterns['authorinfo']['##author:picturelarge##'] = print_user_picture($record->userid, $this->df->course->id, get_field('user','picture','id',$record->userid), true, true);

            // entry info
            $patterns['entryinfo'] = array();
            $patterns['entryinfo']['##entryid##'] = $record->id;
        }        
            
        return $patterns;
    }
            
    /**
     * 
     */
    public function get_search_sql($value) {
        return '';
    }

    /**
     * 
     */
    public function parse_search() {
        return '';
    }

    /**
     * 
     */
    public function parse_sort() {
        return '';
    }

    /**
     * 
     */
    public function update_content($recordid, $value, $name='') {
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return '';
    }

}
?>