<?php // $Id$

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field_calculated extends dataform_field_base {
    var $type = 'calculated';

    function dataform_field_calculated($field=0, $df=0) {
        parent::dataform_field_base($field, $df);
    }

    /**
     *
     */
    public function calculate_content($recordid, $forminput = null) {
        $value = false;
        
        if ($recordid) {
            $negation = $this->field->param1;
            $operator = $this->field->param3;
            $operand1 = $operand2 = '';
            // operand 1
            if ($this->field->param2) {
                if (isset($forminput->{'field_'.$this->field->param2.'_'.$recordid})) {
                    $operand1 = trim($forminput->{'field_'.$this->field->param2.'_'.$recordid});
                } else {
                    $operand1 = trim(get_field('dataform_contents', 'content', 'fieldid', $this->field->param2, 'recordid', $recordid));
                }
                $operand1 = $operand1 == '' ? 0 : $operand1;
                
                // only if operand1 param and operator param, proceed to operand2
                if ($operator and $this->field->param4) {
                    if (isset($forminput->{'field_'.$this->field->param4.'_'.$recordid})) {
                        $operand2 = trim($forminput->{'field_'.$this->field->param4.'_'.$recordid});
                    } else {
                        $operand2 = trim(get_field('dataform_contents', 'content', 'fieldid', $this->field->param4, 'recordid', $recordid));
                    }
                    $operand2 = $operand2 == '' ? 0 : $operand2;

                    switch ($operator) {
                        case '+':
                            $value = $operand1 + $operand2;
                            break;
                        
                        case '-':
                            $value = $operand1 - $operand2;
                            break;
                        
                        case '*':
                            $value = $operand1 * $operand2;
                            break;
                        
                        case '/':
                            $value = $operand1 / $operand2;
                            break;
                        
                        case '%':
                            $value = $operand1 % $operand2;
                            break;
                    }
                } else {
                    $value = $operand1;
                }
                
                // add negation    
                if ($negation) {
                    $value = -$value;
                }
            }
        }

        return $value;
    }

    /**
     *
     */
    public function update_content($recordid, $value='', $name='') {
        if ($value !== false) {
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
        return false;
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
        $str = '<div style="display:none;">';
        $str .= '<input type="text" name="field_'. $this->field->id. '_'. $recordid. '" id="field_'. $this->field->id. '_'. $recordid. '" />';
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