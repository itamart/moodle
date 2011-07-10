<?php  // $Id$

require_once($CFG->dirroot. '/mod/dataform/view/view_class.php');

/**
 * A template for displaying dataform entries in a tabular list
 * Parameters used:
 * param1 - list header section
 * param2 - repeated entry section
 * param3 - table cell alignment 
 * param4 - unused
 * param5 - unused 
 * param6 - unused
 * param7 - unused
 * param8 - unused 
 * param9 - unused 
 * param10 - unused 
 */
class dataform_view_tabular extends dataform_view_base {
    protected $type = 'tabular';
    
    /**
     * Constructor
     */
    public function dataform_view_tabular($view = 0, $df = 0) {
        parent::dataform_view_base($view, $df);
    }

    /**
     * 
     */
    public function generate_default_view() {
        // get all the fields for that database
        if (!$fields = $this->df->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }
        
        $header = array();
        $entries = array();
        $align = array();
        
        foreach ($fields as $field) {
            if ($field->field->id > 0) {
                $header[] = $field->field->name;
                $entries[] = '[['. $field->field->name. ']]';
                $align[] = 'left';
            }
        }
        $header[] = '';
        $header[] = '';
        $header[] = '';
        $header[] = '';
        $entries[] = '##more##';
        $entries[] = '##edit##';
        $entries[] = '##delete##';
        $entries[] = '##approve##';
        $align[] = 'center';
        $align[] = 'center';
        $align[] = 'center';
        $align[] = 'center';

        $this->view->param1 = implode(',', $header);
        $this->view->param2 = implode(',', $entries);
        $this->view->param3 = implode(',', $align);

        // set views and filters menus and quick search
        $this->view->section = '<div class="mdl-align">
                                ##viewsmenu##&nbsp;&nbsp;##filtersmenu##
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                ##quicksearch##&nbsp;&nbsp;##quickperpage##&nbsp;&nbsp;##quickreset##
                                <br /><br />##addnewentry##
                                <br /><br />##pagingbar##
                                <div>';
    }

    /**
     * 
     */
    public function editors() {
        return array('section', 'param1', 'param2');
    }

    /**
     * 
     */
    protected function display_section($content, $name = '', $return = false) {
        $table->head = explode(',', $this->view->param1);
        $table->align = explode(',', $this->view->param3);
        //$table->wrap = array(false, false, false, false, false, false, false, false);
        foreach ($content as $entry) {   
            $table->data[] = $entry;
        }
        
        if (!$return) {
            echo '<div class="entriesview">';
            print_table($table);
            echo '</div>';
        } else {
            return '<div class="entriesview">'. print_table($table, true). '</div>';
        }
    }

    /**
     * 
     */
    protected function entry_text($patterns) {
        return explode(',', $this->replace_tags($patterns, $this->view->param2));
    }
    
    /**
     * 
     */
    protected function new_entry_text() {
        return explode(',', parent::new_entry_text());
    }

    /**
     * 
     */
    protected function replace_view_tags(){
        $patterns = $this->patterns();
        
        $this->view->section = $this->replace_tags($patterns, $this->view->section);
        $this->view->param1 = $this->replace_tags($patterns, $this->view->param1);
    }
    
}
?>