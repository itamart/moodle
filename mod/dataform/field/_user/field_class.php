<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field__user extends dataform_field_base {

    public $type = '_user';

    /**
     * 
     */
    public function dataform_field__user($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     * 
     */
    public function patterns($record = 0, $edit = false, $enabled = false) {
        global $CFG, $USER;
        
        $patterns = array('authorinfo' => array());

        if (!$record) { // new record (0)
            $record = new object();
            $record->userid = $USER->id;
            $record->username = $USER->username;
            $record->firstname = $USER->firstname;
            $record->lastname = $USER->lastname;
            $record->idnumber = $USER->idnumber;
            $record->picture = $USER->picture;
        }

        // no edit mode for this field
         switch ($this->field->internalname) {
            case 'name':
                $patterns['authorinfo']['##author:name##'] = '<a href="'. $CFG->wwwroot. '/user/view.php?id='. $record->userid.
                            '&amp;course='. $this->df->course->id.'">'. fullname($record). '</a>';
                break;

            case 'firstname':
                $patterns['authorinfo']['##author:firstname##'] = $record->firstname;
                break;

            case 'lastname':
                $patterns['authorinfo']['##author:lastname##'] = $record->lastname;
                break;

            case 'username':
                $patterns['authorinfo']['##author:username##'] = $record->username;
                break;

            case 'id':
                $patterns['authorinfo']['##author:id##'] = $record->userid;
                break;

            case 'idnumber':
                $patterns['authorinfo']['##author:idnumber##'] = $record->idnumber;
                break;

            // TODO: print_user_picture deprecated in 2.0
            case 'picture':
                $patterns['authorinfo']['##author:picture##'] = print_user_picture($record->userid, $this->df->course->id, get_field('user','picture','id',$record->userid), false, true);
                $patterns['authorinfo']['##author:picturelarge##'] = print_user_picture($record->userid, $this->df->course->id, get_field('user','picture','id',$record->userid), true, true);
                break;
        }
        
        return $patterns;
    }
    
    /**
     *
     */
    public function get_compare_text() {
        // the sort sql here returns the field's sql name
        return $this->get_sort_sql();
    }

    /**
     * 
     */
    public function get_sort_sql() {
        if ($this->field->internalname != 'picture') {
            if ($this->field->internalname == 'name') {
                $internalname = 'id';
            } else {
                $internalname = $this->field->internalname;
            }
            return 'u.'. $internalname;
        } else {
            return '';
        }
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        global $CFG;
        $contentfull = $this->get_sort_sql();
        $sql = 'SELECT DISTINCT '. $contentfull.
                        ' FROM '. $CFG->prefix. 'user u '. 
                        ' WHERE '. $contentfull. ' IS NOT NULL'.
                        ' ORDER BY '. $contentfull. ' '. ($sortdir ? 'DESC' : 'ASC');

        $distinctvalues = array();
        if ($options = get_records_sql($sql)) {
            if ($this->field->internalname == 'name') {
                $internalname = 'id';
            } else {
                $internalname = $this->field->internalname;
            }
            foreach ($options as $data) {
                $value = $data->{$internalname};
                if ($value === '') {
                    continue;
                }
                $distinctvalues[] = $value;
            }
        }
        return $distinctvalues;
    }

    /**
     * 
     */
    public function update_content($recordid, $value='', $name='') {
        return true;
    }

    /**
     *
     */
    public function export_text_supported() {
        if ($this->field->internalname == 'picture') {
            return false;
        } else {
            return true;
        }
    }

    /**
     *
     */
    public function export_text_value($entry) {
        if ($this->field->internalname != 'picture') {
            if ($this->field->internalname == 'name' or $this->field->internalname == 'id') {
                $internalname = 'userid';
            } else {
                $internalname = $this->field->internalname;
            }
            return $entry->{$internalname};
        } else {
            return '';
        }
    }

}
?>