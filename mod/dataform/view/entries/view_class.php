<?php  // $Id$
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

/**
 * A template for displaying dataform entries
 * Parameters used:
 * param1 - reserved (filter id)
 * param2 - reserved (max entries per page)
 * param3 - reserved (group by) 
 * param4 - list header section
 * param5 - repeated entry section 
 * param6 - unusedlist footer section 
 * param7 - unused
 * param8 - unused 
 * param9 - unused 
 * param10 - unused 
 */
class dataform_view_entries extends dataform_view_base {
    protected $type = 'entries';
    
    protected $filter = null;
    protected $entries = null;

    protected $baseurl = '';
    protected $foundentries = 0;
    
    /**
     * Constructor
     */
    public function dataform_view_entries($view = 0, $df = 0) {
        parent::dataform_view_base($view, $df);

        $this->baseurl = 'view.php?d='. $this->df->id(). '&amp;view='. $this->view->id;        
        
        // get filter: view's filter first
        $filterid = $this->view->param1 ? $this->view->param1 : optional_param('filter', 0, PARAM_INT);
        $this->filter = $this->df->get_filter_from_id($filterid);
        
        // TODO: should be done in the form
        if (!$this->view->param1) {
            if ($this->view->param2) {
                $this->filter->perpage = $this->view->param2;
            }
            if ($this->view->param3) {
                $this->filter->groupby = $this->view->param3;
            }
        }
        
        if (!$this->view->param1) {
            $this->baseurl .= '&amp;filter='. $filterid;
        }
        $this->filter->page = optional_param('page', 0, PARAM_INT);

        // TODO: should this be here?
        $this->set_groupby_per_page();

    }

    /**
     * 
     */
    public function get_form() {
        return new mod_dataform_view_entries_form(array($this));
    }

    /**
     * 
     */
    public function display_view($editentries = 0, $return = false) {
        global $CFG;
        
        // get the entries (object: max, found, entries)
        $entries = $this->df->get_entries($this->filter);

        if (!$entries->max) {
            notify(get_string('recordsnoneindataform','dataform'));
        } else {
            $this->entries = $entries->entries;
            $this->foundentries = $entries->found;

            // notify records subset and set paging
            if ($entries->found != $entries->max) {
                $strentriesfound = $entries->found. '/'. $entries->max;
                notify(get_string('recordsfound', 'dataform', $strentriesfound), 'notifysuccess');
            }
        }
        

        $patternoptions = array();

        $action = '';
        if ($editentries) {  // there should be something to edit
            if ($editentries == -1) {
                $action = '&amp;add=1';    // edit only a new entry
                $patternoptions['hidenewentry'] = 1;
            } else {    // edit requested entries
                $action = '&amp;update='. $editentries; // form to update requested entries
                $editentries = explode(',', $editentries);    // edit requested entries
            }
        }
       
        $listgroup = array();
        
        // Display a new entry to add
        if ($editentries == -1 and  $this->df->user_can_manage_entry(0)) {
            // new entry is its own group
            $listgroup['New entry'] = array($this->new_entry_text());
        }

        // Display the entries if any       
        if ($this->entries) {
            // prepare for groupby
            $groupbyid = $this->view->param3 ? $this->view->param3  : 0;
            $groupbyvalue = '';
            $grouptext = array();
            
            foreach ($this->entries as $entry) {   // Might be just one
                $newgroup = '';
                
                // May we edit this entry? (!$editable hides in _entry field the action tags)
                $editable = $this->df->user_can_manage_entry($entry);
                $editthisone = false;
                if ($editable and $editentries and $editentries != -1) {    // edit all authorized entries
                    $editthisone = in_array($entry->id, $editentries);
                }
                
                // Replacing tags
                $patterns = array();
                foreach ($this->get_fields() as $field) {
                    if ($fieldpatterns = $field->patterns($entry, $editthisone, $editable)) {
                        $patterns = array_merge_recursive($patterns, $fieldpatterns);
                        
                        // Are we grouping?
                        if ($groupbyid and $groupbyid == $field->field->id) {
                            // if editing get the pattern for browsing b/c we need to content
                            if ($editthisone) {
                                $fieldpatterns = $field->patterns($entry);
                            }
                            $fieldvalues = array_values($fieldpatterns['fields']);
                            $fieldvalue = count($fieldvalues) ? $fieldvalues[0] : '';
                            if ($fieldvalue != $groupbyvalue) {
                                $newgroup = $groupbyvalue;
                                $groupbyvalue = $fieldvalue;   // assuming here that the groupbyed field returns only one pattern
                            }
                        }
                        
                        // TODO: $replacement[] = highlight($search, $field->display_browse($entry->id, $view));
                    }
                }
                
                if ($newgroup) {
                    $listgroup[$newgroup] = $grouptext;
                    $grouptext = array();
                }
                
                $grouptext[] = $this->entry_text($patterns);
                
            }
            // collect remaining listbody text (all of it if no groupby)
            $listgroup[$groupbyvalue] = $grouptext;
        }

        // TODO: it is possible that $editthisone == true but there are no entries to edit (because of filtering)

        // replace view specific tags
        $this->replace_view_tags();

        if (!$return) {
            $this->print_before_form();
            
            // TODO: wrap
            $blockposition = $this->view->sectionpos;
            $float = ($blockposition == 1 ? 'style="float:right;"' : $blockposition == 2 ? 'style="float:left;"' : '');
            
            echo '<div ', $float, '>', 
                '<form enctype="multipart/form-data" id="viewform" action="view.php?d=', $this->df->id(), $action, '" method="post">',
                '<div>',
                '<input type="hidden" name="d" value="', $this->df->id(), '" />',
                '<input type="hidden" name="sesskey" value="', sesskey(), '" />',
                '<input type="hidden" name="view" value="', $this->view->id, '" />',
                '<input type="hidden" name="filter" value="', $this->filter->id, '" />',
                '</div>';

            if ($editentries) {
                echo '<div style="text-align:center">',
                    '<input type="submit" name="saveandview" value="', get_string('saveandview','dataform'), '" />',
                    '&nbsp;<input type="submit" name="cancel" value="', get_string('cancel'), '" onclick="javascript:history.go(-1)" />',
                    '</div>';
            }
            foreach ($listgroup as $name => $content) {
                if ($name) {
                    print_heading($name, '', 3, 'main');
                } else {
                    echo '<br />';
                }
            
                $this->display_section($content, $name);
                echo '<hr />';
            }

            if ($editentries) {
                echo '<div style="text-align:center">',
                    '<input type="submit" name="saveandview" value="', get_string('saveandview','dataform'), '" />',
                    '&nbsp;<input type="submit" name="cancel" value="', get_string('cancel'), '" onclick="javascript:history.go(-1)" />',
                    '</div>';
            }
        
            echo '</form></div>';

            // Print the stuff that need to come after the form fields.
            $this->print_after_form();
        }

        // TODO
        if ($return) {
        //    return $listheader. $listbody. $listfooter;
        }
    
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
        
        $this->view->param4 = '';
        $this->view->param5 = $str;
        $this->view->param6 = '';
    }

    /**
     * 
     */
    public function editors() {
        return array('section', 'param4', 'param5', 'param6');
    }

    /**
     * 
     */
    public function filter() {
        return $this->filter->id;
    }

    /**
     * 
     */
    public function filters_menu() {
        return $this->df->filters_menu();
    }

    /**
     * 
     */
    protected function replace_view_tags(){
        $patterns = $this->patterns();
        
        $this->view->section = $this->replace_tags($patterns, $this->view->section);
        $this->view->param4 = $this->replace_tags($patterns, $this->view->param4);
        $this->view->param6 = $this->replace_tags($patterns, $this->view->param6);
    }

    /**
     * 
     */
    protected function display_section($content, $name = '', $return = false) {
        $listheader = $this->view->param4;
        $listfooter = $this->view->param6;
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
    protected function patterns($options = null, $parent = true) {
        global $CFG;

        // TODO: what about higher parents?
        // perhaps with $this->options
        if (!$parent or !$patterns = parent::patterns()) {  // assuming parent returns an array
            $patterns = array();
        }
        // the view_base class sets a views menu
        $patterns['menus']['##filtersmenu##'] = '';
        if (!empty($this->entries) and !$this->view->param1) {
            $patterns['menus']['##filtersmenu##'] = $this->print_filters_menu(true);
        }
        
        // user filtering preferences
        $patterns['userpref'] = array();
        $patterns['userpref']['##quicksearch##'] = '';
        $patterns['userpref']['##quickperpage##'] = '';
        $patterns['userpref']['##quickreset##'] = '';
        
        if (!empty($this->entries) and !$this->view->param1) {
            $patterns['userpref']['##quicksearch##'] = $this->print_quick_search(true);
            $patterns['userpref']['##quickperpage##'] = $this->print_quick_perpage(true);
            $patterns['userpref']['##quickreset##'] = $this->print_quick_reset(true);
        }

        $baseurl = $CFG->wwwroot. '/mod/dataform/view.php?d='. $this->df->id(). '&amp;view='. $this->view->id. '&amp;filter='. $this->filter->id;

        // new entry
        $patterns['newentry'] = array('##addnewentry##' => '');
        if (!isset($options['hidenewentry']) and !$this->df->user_at_max_entries(true)) {            // TODO: move to a view attribute so as to call the function only once
            $patterns['newentry']['##addnewentry##'] = '<a href="'. $baseurl. '&amp;new=1&amp;sesskey='. sesskey(). '">Add a new entry</a>';
        }
        
        // general actions
        if (!array_key_exists('generalactions', $patterns)) {
            $patterns['generalactions'] = array();
        }
        $patterns['generalactions']['##selectallnone##'] = '<input type="checkbox" '.
                                                        'onclick="inps=document.getElementsByTagName(\'input\');'.
                                                            'for (var i=0;i<inps.length;i++) {'.
                                                                'if (inps[i].type==\'checkbox\' && inps[i].name.search(\'selector_\')!=-1){'.
                                                                    'inps[i].checked=this.checked;'.
                                                                '}'.
                                                            '}" />';
        $patterns['generalactions']['##multiduplicate##'] = '<input type="submit" name="multiduplicate" value="'. get_string('multiduplicate', 'dataform'). '" />';
        $patterns['generalactions']['##multiedit##'] = '<input type="submit" name="multiedit" value="'. get_string('multiedit', 'dataform'). '" />';
        $patterns['generalactions']['##multidelete##'] = '<input type="submit" name="multidelete" value="'. get_string('multidelete', 'dataform'). '" />';
        if ($this->df->data->approval and has_capability('mod/dataform:approve', $this->df->context)) {
            $patterns['generalactions']['##multiapprove##'] = '<input type="submit" name="multiapprove"  value="'. get_string('multiapprove', 'dataform'). '" />';
        } else {
            $patterns['generalactions']['##multiapprove##'] = '';
        }
        
        // paging bar
        $pagingbarpatterns = $this->paging_bar_patterns();
        if (!array_key_exists('pagingbar', $patterns)) {
            $patterns = array_merge($patterns, $pagingbarpatterns);
        } else {
            $patterns['pagingbar'] = $pagingbarpatterns['pagingbar'];
        }

        return $patterns;
    }

    /**
     * 
     */
    protected function paging_bar_patterns() {
        
        $patterns = array('pagingbar' => array('##pagingbar##' => ''));
        
        if (isset($this->filter->pagenum)) {
            $patterns['pagingbar']['##pagingbar##'] = print_paging_bar($this->filter->pagenum,
                                                                        $this->filter->page,
                                                                        1,
                                                                        $this->baseurl. '&amp;',
                                                                        'page',
                                                                        '',
                                                                        true);
        } else if ($this->foundentries != count($this->entries)) {
            $patterns['pagingbar']['##pagingbar##'] = print_paging_bar($this->foundentries,
                                                                        $this->filter->page,
                                                                        $this->filter->perpage,
                                                                        $this->baseurl. '&amp;',
                                                                        'page',
                                                                        '',
                                                                        true);
        }
        
        return $patterns;
    }

    /**
     * 
     */
    protected function new_entry_text() {
        $patterns = array();
        foreach ($this->get_fields() as $field) {
            if ($fieldpatterns = $field->patterns(0, true, true)) {
                $patterns = array_merge_recursive($patterns, $fieldpatterns);
            }
        }
        // actual replacement of the tags
        $newtext = $this->replace_tags($patterns, $this->view->param5);
        
        return $newtext;
    }
        
    /**
     * 
     */
    protected function entry_text($patterns) {
        return $this->replace_tags($patterns, $this->view->param5);
    }

    /**
     * Just in case a view needs to print something before the whole form
     */
    protected function print_before_form() {
        $block = $this->view->section;
        $blockposition = $this->view->sectionpos;
        $float = '';
        if ($blockposition and $blockposition != 3) { // not at bottom
            $float = 'style="float:'. (($blockposition == 1) ? 'left' : 'right'). ';"';
        }
        echo '<div ', $float, '>', $block, '</div>';
    }

    /**
     * Just in case a view needs to print something after the whole form
     */
    protected function print_after_form() {
        $block = $this->view->section;
        $blockposition = $this->view->sectionpos;
        if ($blockposition == 3) { // not at bottom
            echo '<div>', $block, '</div>';
        }

        foreach ($this->get_fields() as $field) {
            $field->print_after_form();
        }

    }

    /**
     * 
     */
    protected function print_filters_menu($return = false) {
        global $CFG;

        if ($menufilters = $this->filters_menu()) {

            $menufilters[-1] = get_string('filteruserpref', 'dataform');
        
            $jumpbaseurl = $CFG->wwwroot. '/mod/dataform/view.php?d='. $this->df->id(). '&amp;sesskey='. sesskey(). '&amp;view='. $this->view->id;
            if (!$this->view->param1 and $this->filter->id) {
                $strcancelfilter = '&nbsp;'. get_string('filtercancelurl', 'dataform', $jumpbaseurl. '&amp;filter=0');
            } else {
                $strcancelfilter = '';
            }
            
            // Display the filter form jump list
            if ($return) {
                return '&nbsp;&nbsp;<label for="filterbrowse_jump">'. get_string('filtercurrent','dataform'). '</label>&nbsp;'.
                    popup_form($jumpbaseurl. '&amp;filter=', $menufilters, 'filterbrowse_jump', $this->filter->id, 'choose', '', '', true).
                    //helpbutton('filters', get_string('addafilter','dataform'), 'dataform').
                    $strcancelfilter;
            } else {
                echo '&nbsp;&nbsp;<label for="filterbrowse_jump">', get_string('filtercurrent','dataform'), '</label>&nbsp;';
                popup_form($jumpbaseurl. '&amp;filter=', $menufilters, 'filterbrowse_jump', $this->filter->id, 'choose');
                //helpbutton('filters', get_string('addafilter','dataform'), 'dataform').
                echo $strcancelfilter;
            }
        }
    }

    /**
     * 
     */
    protected function print_quick_search($return = false) {
        global $CFG;

        $jumpbaseurl = $CFG->wwwroot. '/mod/dataform/view.php?d='. $this->df->id(). '&amp;sesskey='. sesskey(). '&amp;view='. $this->view->id;
        if ($this->filter->id == -1 and $this->filter->search) {
            $searchvalue = $this->filter->search;
        } else {
            $searchvalue = '';
        }
        // TODO: use moodle forms
        // Display the quick search form
        if ($return) {
            $returnstr = '<form id="quicksearchform" class="popupform" action="'. $jumpbaseurl. '&amp;userpref=1&amp;filter=-1" method="post"><div>'.
                '<input type="hidden" name="d" value="'. $this->df->id(). '" />'.
                '<input type="hidden" name="sesskey" value="'. sesskey(). '" />'.

                '<label for="usersearch">'. get_string('search', ''). '</label>&nbsp;'.
                print_textfield ('usersearch', $searchvalue, get_string('quicksearch', 'dataform'), 20, 0, true).
                
                '<input type="submit" name="goquicksearch" value="Go" />'.
                '</div></form>';
                    
            return $returnstr;
        } else {
            echo '<form id="quicksearchform" class="popupform" action="', $jumpbaseurl, '&amp;userpref=1&amp;filter=-1" method="post"><div>',
                '<input type="hidden" name="d" value="', $this->df->id(), '" />',
                '<input type="hidden" name="sesskey" value="', sesskey(), '" />';

            echo '<label for="quicksearch">', get_string('search', ''), '</label>&nbsp;';
            print_textfield ('usersearch', $searchvalue, get_string('quicksearch', 'dataform'), 20);
                
            echo '<input type="submit" name="goquicksearch" value="Go" />',
                    '</div></form>';
        }
    }

    /**
     * 
     */
    protected function print_quick_perpage($return = false) {
        global $CFG;
       
        $jumpbaseurl = $CFG->wwwroot. '/mod/dataform/view.php?d='. $this->df->id(). '&amp;sesskey='. sesskey(). '&amp;view='. $this->view->id. '&amp;userpref=1&amp;filter=-1';
        if ($this->filter->id == -1 and $this->filter->perpage) {
            $perpagevalue = $this->filter->perpage;
        } else {
            $perpagevalue = 0;
        }
        
        // TODO: use moodle forms
        $perpage = array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
           20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);

        if ($return) {
            return '&nbsp;&nbsp;<label for="quickperpage_jump">'. get_string('filterperpage','dataform'). '</label>&nbsp;'.
                popup_form($jumpbaseurl. '&amp;userperpage=', $perpage, 'quickperpage_jump', $perpagevalue, 'choose', '', '', true);
                //helpbutton('filters', get_string('addafilter','dataform'), 'dataform').
        } else {
            echo '&nbsp;&nbsp;<label for="quickperpage_jump">', get_string('perpage','dataform'), '</label>&nbsp;';
            popup_form($jumpbaseurl. '&amp;userperpage=', $perpage, 'quickperpate_jump', $perpagevalue, 'choose');
            //helpbutton('filters', get_string('addafilter','dataform'), 'dataform').
        }
    }

    /**
     * 
     */
    protected function print_quick_reset($return = false) {
        global $CFG;
       
        $jumpbaseurl = $CFG->wwwroot. '/mod/dataform/view.php?d='. $this->df->id(). '&amp;sesskey='. sesskey(). '&amp;view='. $this->view->id. '&amp;userpref=-1&amp;filter=0';
        if ($this->filter->id == -1) {
            $strresetfilter = '&nbsp;'. get_string('filterreseturl', 'dataform', $jumpbaseurl);
        } else {
            $strresetfilter = '';
        }
        
        if ($return) {
            return '&nbsp;&nbsp;'. $strresetfilter;
        } else {
            echo '&nbsp;&nbsp;'. $strresetfilter;
        }
    }

    /**
     * 
     */
    protected function set_groupby_per_page() {
        global $CFG;
        
        // group per page
        if (($fieldid = $this->filter->groupby) and ($this->filter->perpage == 0)) {
            // set sorting to begin with this field
            $foundinsort = false;
            // TODO: asc order is arbitrary here and should be determined differently
            $sortdir = 0;
            if ($this->filter->customsort) {
                $sortfields = explode(',', $this->filter->customsort);
                foreach ($sortfields as $sortfield) {
                    $tmparr = explode(' ', $sortfield);
                    if ($foundinsort = ($fieldid == $tmparr[0])) {
                        unset($sortfield);
                        array_unshift($sortfields, implode(' ', $tmparr));
                        $sortdir = $tmparr[1];
                        break;
                    }
                }
            }
            // set the search criterion
            $field = $this->df->get_field_from_id($fieldid);

            if ($fieldid > 0) {
                $groupbycontent = sql_compare_text('c'. $fieldid. '.'. $field->get_sort_field());
                $groupbycontentfull = $field->get_sort_sql($groupbycontent);
            } else {
                $groupbycontentfull = $field->get_sort_sql();
            }

            $groupbysql = 'SELECT DISTINCT '. $groupbycontentfull.
                            ' FROM '. $CFG->prefix. 'dataform_content c'. $fieldid. 
                            ' WHERE c'. $fieldid. '.fieldid='. $fieldid. ' AND '. $groupbycontentfull. ' IS NOT NULL'.
                            ' ORDER BY '. $groupbycontentfull. ' '. ($sortdir ? 'DESC' : 'ASC');
                            
            $groupbyvalues = array();
            if ($groupbyoptions = get_records_sql($groupbysql)) {
                foreach ($groupbyoptions as $data) {
                    $value = $data->content;
                    if ($value === '') {
                        continue;
                    }
                    $groupbyvalues[] = $value;
                }
            }

            if ($this->filter->page < count($groupbyvalues)) {
                $val = $groupbyvalues[$this->filter->page];
                $this->filter->customsearch = $fieldid. '|||'. $val;
                if ($foundinsort) {
                    $this->filter->customsort = implode(',', $sortfields);
                } else if ($this->filter->customsort) {
                    $this->filter->customsort = $fieldid. ' '. $sortdir. ','. $this->filter->customsort;
                } else {
                    $this->filter->customsort = $fieldid. ' '. $sortdir;
                }
                $this->filter->pagenum = count($groupbyvalues);
            }
        }
    }    
    
}
?>