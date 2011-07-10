<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_number extends dataform_field_base {
    public $type = 'number';

    function dataform_field_number($field = 0, $df = 0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     *
     */
    public function update_content($recordid, $value='', $name='') {
        $content = new object;
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $value = trim($value);
        if (strlen($value) > 0) {
            $content->content = floatval($value);
        } else {
            $content->content = null;
        }
        if ($oldcontent = get_record('dataform_contents','fieldid', $this->field->id, 'recordid', $recordid)) {
            $content->id = $oldcontent->id;
            return update_record('dataform_contents', $content);
        } else {
            return insert_record('dataform_contents', $content);
        }
    }

    /**
     *
     */
    public function get_compare_text() {
        return sql_compare_text("c{$this->field->id}.content");
    }

    /**
     *
     */
    public function get_sort_sql($fieldname) {
        global $CFG;
        switch ($CFG->dbfamily) {
            case 'mysql':
                // string in an arithmetic operation is converted to a floating-point number
                return '('.$fieldname.'+0.0)';
            case 'postgres':
                // cast for PG
                return 'CAST('.$fieldname.' AS REAL)';
            default:
                // the rest, just the field name. TODO: Analyse behaviour under MSSQL and Oracle
                return $fieldname;
        }
    }

    /**
     *
     */
    protected function display_edit($recordid = 0) {
        if ($recordid){
            $content = get_field('dataform_contents', 'content', 'fieldid', $this->field->id, 'recordid', $recordid);
        } else {
            $content = '';
        }

        // beware get_field returns false for new, empty records MDL-18567
        if ($content === false) {
            $content = '';
        }

        if (!empty($this->field->param2)) {
            $width = ' style="width:'. s($this->field->param2). s($this->field->param3). ';" ';
        } else {
            $width = '';
        }
        
        if (!empty($this->field->param4)) {
            $class = ' class="'. s($this->field->param4). '" ';
        } else {
            $class = '';
        }
        
        $str = '<div title="'.s($this->field->description).'">';
        // param1
        $str .= '<input type="text" name="field_'. $this->field->id. '_'. $recordid. '" id="field_'. $this->field->id. '_'. $recordid. '" '. $class. $width. 'value="'.s($content).'" />';
        $str .= '</divn>';
        
        return $str;
    }

    /**
     *
     */
    protected function display_browse($recordid) {
        if ($content = get_record('dataform_contents', 'fieldid', $this->field->id, 'recordid', $recordid)) {
            if (strlen($content->content) < 1) {
                return false;
            }
            $number = $content->content;
            $decimals = trim($this->field->param1);
            // only apply number formatting if param1 contains an integer number >= 0:
            if (preg_match("/^\d+$/", $decimals)) {
                $decimals = $decimals * 1;
                // removes leading zeros (eg. '007' -> '7'; '00' -> '0')
                $str = format_float($number, $decimals, true);
                // For debugging only:
#                $str .= " ($decimals)";
            } else {
                $str = $number;
            }
            return $str;
        }
        return false;
    }

}

?>