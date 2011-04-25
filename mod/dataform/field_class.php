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
 * Base class for Dataform Field Types
 * (see field/<field type>/field.class.php)
 */
class dataform_field_base {

    public $type = 'unknown';  // Subclasses must override the type with their name
    public $df = NULL;       // The dataform object that this field belongs to
    public $field = NULL;      // The field object itself, if we know it

    public $iconwidth = 16;    // Width of the icon for this fieldtype
    public $iconheight = 16;   // Width of the icon for this fieldtype

    // Constructor function
    public function dataform_field_base($field = 0, $df = 0) {   // Field or dataform or both, field can be id or object, dataform id or df 
        if (empty($df)) {
            error('Programmer error: You must specify dataform id or object when defining a field class. ');
        } else if (is_object($df)) {
            $this->df = $df;
        } else {    // dataform id
            $this->df = new dataform($df);
        }
        
        if (!empty($field)) {
            if (is_object($field)) {
                $this->field = $field;  // Programmer knows what they are doing, we hope
            } else if (!$this->field = get_record('dataform_fields','id',$field)) {
                error('Bad field ID encountered: '.$field);
            }
        }
        if (empty($this->field)) {         // We need to define some default values
            $this->set_field();
        }
    }

    // Sets up a field object
    public function set_field($forminput = NULL) {
        $this->field = new object;
        $this->field->id = isset($forminput) ? $forminput->fid : 0;
        $this->field->type   = $this->type;
        $this->field->dataid = $this->df->id();
        $this->field->name = isset($forminput) ? trim($forminput->name) : '';
        $this->field->description = isset($forminput) ? trim($forminput->description) : '';
        for ($i=1; $i<=10; $i++) {
            if (isset($forminput) and isset($forminput->{'param'.$i})) {
                $this->field->{'param'.$i} = trim($forminput->{'param'.$i});
            } else {
                $this->field->{'param'.$i} = '';
            }
        }
        return true;
    }

    // Insert a new field in the database
    public function insert_field() {
        if (empty($this->field)) {
            notify('Programmer error: Field has not been defined yet!  See define_field()');
            return false;
        }

        if (!$this->field->id = insert_record('dataform_fields',$this->field)){
            notify('Insertion of new field failed!');
            return false;
        }
        return true;
    }

    // Update a field in the database
    public function update_field() {
        if (!update_record('dataform_fields', $this->field)) {
            notify('updating of new field failed!');
            return false;
        }
        return true;
    }

    // Update the content of one dataform field in the dataform_content table
    public function update_content($recordid, $value, $name='') {
        $content = new object();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $content->content = clean_param($value, PARAM_NOTAGS);

        if ($oldcontent = get_record('dataform_content','fieldid', $this->field->id, 'recordid', $recordid)) {
            $content->id = $oldcontent->id;
            return update_record('dataform_content', $content);
        } else {
            return insert_record('dataform_content', $content);
        }
    }

    // Delete a field completely
    public function delete_field() {
        if (!empty($this->field->id)) {
            delete_records('dataform_fields', 'id', $this->field->id);
            $this->delete_content();
        }
        return true;
    }

    // Delete all content associated with the field
    public function delete_content($recordid=0) {

        $this->delete_content_files($recordid);

        if ($recordid) {
            return delete_records('dataform_content', 'fieldid', $this->field->id, 'recordid', $recordid);
        } else {
            return delete_records('dataform_content', 'fieldid', $this->field->id);
        }
    }

    // Deletes any files associated with this field
    public function delete_content_files($recordid='') {
        global $CFG;

        require_once($CFG->libdir.'/filelib.php');

        $dir = $CFG->dataroot.'/'.$this->df->data->course.'/'.$CFG->moddata.'/dataform/'.$this->df->id().'/'.$this->field->id;
        if ($recordid) {
            $dir .= '/'.$recordid;
        }

        return fulldelete($dir);
    }

    // Print the relevant form element to define the attributes for this field
    public function display_field_design() {
        global $CFG;

        if (empty($this->field->id)) {   // No field has been defined yet, try and make one
            $this->set_field();
            $action = 'add=1';
            $savebutton = get_string('add');
        } else {
            $action = 'update='. $this->field->id;
            $savebutton = get_string('savechanges');
        }

        echo '<form id="editfield" action="'.$CFG->wwwroot.'/mod/dataform/fields.php?'. $action. '" method="post">'."\n";
        echo '<input type="hidden" name="d" value="'. $this->field->dataid. '" />'."\n";
        echo '<input type="hidden" name="type" value="' .$this->field->type. '" />'."\n";
        echo '<input type="hidden" name="fid" value="' .$this->field->id. '" />'."\n";
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'"  />'."\n";

        print_simple_box_start('center','80%');

        print_heading($this->name());

        require_once($CFG->dirroot.'/mod/dataform/field/'.$this->type.'/mod.html');

        echo '<div class="mdl-align">';
        echo '<input type="submit" value="'.$savebutton.'" />'."\n";
        echo '<input type="submit" name="cancel" value="'.get_string('cancel').'" />'."\n";
        echo '</div>';

        echo '</form>';

        print_simple_box_end();
    }

    public function display_sort($order, $dir) {
        $orderoptions = array (1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);
        $diroptions = array(0 => get_string('ascending', 'dataform'),
                            1 => get_string('descending', 'dataform'));

        return 'Order: '. choose_from_menu($orderoptions, 'cso_'. $this->field->id, $order, 'choose' , '', 0 , true).
                '&nbsp;Direction: '. choose_from_menu($diroptions, 'csd_'. $this->field->id, $dir, '', '', 0, true);
    }
    
    public function parse_sort() {
        $sortorder = optional_param('cso_'.$this->field->id, 0, PARAM_INT);
        if ($sortorder) {
            return array($this->field->id, optional_param('csd_'.$this->field->id, 0, PARAM_INT));
        }
        return '';
    }
    
    // display the fields for editing or browsing
    // builtin fields must override
    public function patterns($record = 0, $edit = false, $editable = false) {
        $patterns = array('fields' => array());
        $recordid = $record ? $record->id : 0;
        
        if ($edit) {
            $patterns['fields']['[['. $this->field->name. ']]'] = $this->display_edit($recordid);
        } else { 
            $patterns['fields']['[['. $this->field->name. ']]'] = $this->display_browse($recordid);
        }
        
        return $patterns;
    }
            
    // Check if a field from an add form is empty
    public function notemptyfield($value, $name) {
        return !empty($value);
    }

    // Returns the sortable field for the content. By default, it's just content
    // but for some plugins, it could be content 1 - content4
    public function get_sort_field() {
        return 'content';
    }

    // Returns the SQL needed to refer to the column.  Some fields may need to CAST() etc.
    public function get_sort_sql($fieldname) {
        return $fieldname;
    }

    public function get_search_sql($value) {
        return " (c{$this->field->id}.fieldid = {$this->field->id} AND c{$this->field->id}.content = '$value') "; 
    }

    // Returns the name/type of the field
    public function name() {
        return get_string('name'.$this->type, 'dataform');
    }

    // Prints the respective type icon
    public function image() {
        global $CFG;

        $str = '<a href="fields.php?d='. $this->field->dataid. '&amp;fid='. $this->field->id.'&amp;mode=display&amp;sesskey='.sesskey().'">';
        $str .= '<img src="'.$CFG->modpixpath.'/dataform/field/'.$this->type.'/icon.gif" ';
        $str .= 'height="'.$this->iconheight.'" width="'.$this->iconwidth.'" alt="'.$this->type.'" title="'.$this->type.'" /></a>';
        return $str;
    }

    //  Per default, it is assumed that fields support text exporting. Override this (return false) on fields not supporting text exporting. 
    public function text_export_supported() {
        return true;
    }

    //  Per default, return the record's text value only from the "content" field. Override this in fields class if necesarry. 
    public function export_text_value($record) {
        if ($this->text_export_supported()) {
            return $record->content;
        }
    }

    // Print the form element when adding or editing an entry
    protected function display_edit($recordid = 0) {
    }

    // Print the content for browsing the entry
    protected function display_browse($recordid) {
        if ($content = get_record('dataform_content','fieldid', $this->field->id, 'recordid', $recordid)) {
            if (isset($content->content)) {
                $options = new object();
                if ($this->field->param1 == '1') {  // We are autolinking this field, so disable linking within us
                    //$content->content = '<span class="nolink">'.$content->content.'</span>';
                    //$content->content1 = FORMAT_HTML;
                    $options->filter=false;
                }
                $options->para = false;
                $str = format_text($content->content, $content->content1, $options);
            } else {
                $str = '';
            }
            return $str;
        }
        return false;
    }

    // Just in case a field needs to print something before the whole form
    function print_before_form() {
    }

    // Just in case a field needs to print something after the whole form
    function print_after_form() {
    }
}

?>