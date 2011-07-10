<?php  // $Id$

require_once($CFG->dirroot. '/mod/dataform/view/view_class.php');

/**
 * A template for a standard display of dataform entries and base class for specialized display templates
 * (see view/<view type>/view.class.php)
 *
 * Parameters used:
 * param1 - list header section
 * param2 - repeated entry section
 * param3 - list footer section
 * param4 - unused
 * param5 - unused
 * param6 - unused
 * param7 - unused
 * param8 - unused
 * param9 - unused
 * param10 - unused
 */

class dataform_view_standard extends dataform_view_base {

    protected $type = 'standard';
    
    /**
     * Constructor
     */
    public function dataform_view_standard($view=0, $df=0) {
        parent::dataform_view_base($view, $df);
    }

    /**
     * Returns a fieldset of view options
     */
    public function generate_default_view() {
        // get all the fields for that database
        if (!$fields = $this->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

        $str = '<div class="defaultview">';
        $str .= '<table cellpadding="5">';

        foreach ($fields as $field) {
            if ($field->field->id > 0) {
                $str .= '<tr><td valign="top" align="right">'. $field->field->name. ':</td>'.
                        '<td valign="top">[['. $field->field->name. ']]</td></tr>';
            }
        }
        $str .= '<tr><td align="center" colspan="2">##edit##  ##more##  ##delete##  ##approve##</td></tr>'.
                '</table>'.
                '</div>';

        // set views and filters menus and quick search
        $this->view->section = '<div class="mdl-align">
                                ##viewsmenu##&nbsp;&nbsp;##filtersmenu##
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                ##quicksearch##&nbsp;&nbsp;##quickperpage##&nbsp;&nbsp;##quickreset##
                                <br /><br />##addnewentry##
                                <br /><br />##pagingbar##
                                <div>';

        $this->view->param1 = '';
        $this->view->param2 = $str;
        $this->view->param3 = '';
    }

    /**
     *
     */
    public function editors() {
        return array('section', 'param1', 'param2', 'param3');
    }

    /**
     *
     */
    protected function replace_view_tags(){
        $patterns = $this->patterns();

        $this->view->section = $this->replace_tags($patterns, $this->view->section);
        $this->view->param1 = $this->replace_tags($patterns, $this->view->param1);
        $this->view->param3 = $this->replace_tags($patterns, $this->view->param3);
    }

    /**
     *
     */
    protected function display_section($content, $name = '', $return = false) {
        $listheader = $this->view->param1;
        $listfooter = $this->view->param3;
        $listbody = implode("\n", $content);

        if (!$return) {
            echo '<div class="entriesview">', $listheader, $listbody, $listfooter, '</div>';
        } else {
            return '<div class="entriesview">'. $listheader. $listbody. $listfooter. '</div>';
        }
    }

    /**
     *
     */
    protected function entry_text($patterns) {
        return $this->replace_tags($patterns, $this->view->param2);
    }
}
?>