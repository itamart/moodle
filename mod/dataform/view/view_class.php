<?php  // $Id$

/**
 * A template for a standard display of dataform entries and base class for specialized display templates
 * (see view/<view type>/view.class.php)
 *
 * Parameters used:
 * param1 - unused
 * param2 - unused
 * param3 - unused
 * param4 - unused
 * param5 - unused
 * param6 - unused
 * param7 - unused
 * param8 - unused
 * param9 - unused
 * param10 - unused
 */

class dataform_view_base {

    protected $type = 'unknown';      // Subclasses must override the type with their name

    public $view = NULL;            // The view object itself, if we know it
    public $editor = 0;

    protected $df = NULL;           // The dataform object that this view belongs to
    protected $fields = array();
    protected $entries = null;
    protected $filter = null;

    protected $baseurl = '';
    protected $availableentries = 0;

    protected $iconwidth = 16;        // Width of the icon for this viewtype
    protected $iconheight = 16;       // Width of the icon for this viewtype

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

        $this->baseurl = 'view.php?d='. $this->df->id(). '&amp;view='. $this->view->id;

        // get filterid: view's filter first
        if (!$filterid = $this->view->filter) {
            if ($filterid = optional_param('filter', 0, PARAM_INT)) {
                $this->baseurl .= '&amp;filter='. $filterid;
            }
        }

        $this->filter = $this->df->get_filter_from_id($filterid);

        // get specific entry id, if requested
        $this->filter->rid = optional_param('rid', 0, PARAM_INT);

        // add view specific perpage
        if ($this->view->perpage) {
            $this->filter->perpage = $this->view->perpage;
        }

        // add view specific groupby
        if ($this->view->groupby) {
            $this->filter->groupby = $this->view->groupby;
        }

        $this->filter->page = optional_param('page', 0, PARAM_INT);

        // TODO: should this be here?
        $this->set_groupby_per_page();
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
        $this->view->perpage = ((isset($fromform) and isset($fromform->perpage)) ? $fromform->perpage : 0);
        $this->view->groupby = ((isset($fromform) and isset($fromform->groupby)) ? $fromform->groupby : 0);
        $this->view->filter = ((isset($fromform) and isset($fromform->filter)) ? $fromform->filter : 0);
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
     * TODO
     */
    public function get_fields($exclude = null, $menu = false) {
        return $this->df->get_fields($exclude, $menu);
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
     * Subclass may need to override
     */
    public function replace_field_in_view($searchfieldname, $newfieldname) {
        $patterns = array('[['.$searchfieldname.']]','[['.$searchfieldname.'#id]]');
        if (!$newfieldname) {
            $replacements = '';
        } else {
            $replacements = array('[['.$newfieldname.']]','[['.$newfieldname.'#id]]');
        }

        $this->view->param2 = str_ireplace($patterns, $replacements, $this->view->param2);
        $this->update_view();
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
        // add entry tags
        return $this->select_tags($patterns);
    }

    /**
     * check the multple existence any tag in a view
     * should be redefined in sub-classes
     * output bool true-valid, false-invalid
     */
    public function tags_check($template) {
        $tagsok = true; // let's be optimistic
        foreach ($this->df->get_fields() as $field) { // only user fields
            if ($field->id() > 0) {
                $pattern="/\[\[".$field->name()."\]\]/i";
                if (preg_match_all($pattern, $template, $dummy) > 1) {
                    $tagsok = false;
                    notify ('[['.$field->name().']] - '.get_string('multipletags','dataform'));
                }
            }
        }
        // else return true
        return $tagsok;
    }

    /**
     *
     */
    public function get_form() {
        global $CFG;

        require_once($CFG->dirroot. '/mod/dataform/view/'. $this->type. '/view_form.php');
        $formclass = 'mod_dataform_view_'. $this->type. '_form';
        return new $formclass($this);
    }

    /**
     *
     */
    public function display_view($editentries = 0, $return = false) {
        global $CFG;

        // get the entries (object: max, found, entries)
        // $this->filter may be adjusted when a specific entry is requested
        $entries = $this->df->get_entries($this->filter);

        if (!$entries->max) {
            notify(get_string('entriesfound', 'dataform', get_string('no')));
        } else {
            $this->entries = $entries->entries;
            $this->availableentries = $entries->found;

            // notify records subset if filtered
            if ($entries->found != $entries->max and $this->filter->id) {
                $strentriesfound = $entries->found. '/'. $entries->max;
                notify(get_string('entriesfound', 'dataform', $strentriesfound), 'notifysuccess');
            }
        }


        $patternoptions = array();

        $action = '';
        if ($editentries) {  // there should be something to edit
            if ($editentries == -1) {
                $action = 'add';    // edit only a new entry
                $actionvalue = '1';
                $patternoptions['hidenewentry'] = 1;
            } else {    // edit requested entries
                $action = 'update'; // form to update requested entries
                $actionvalue = $editentries; // form to update requested entries
                $editentries = explode(',', $editentries);    // edit requested entries
            }
        }

        $listgroup = array();

        // Display a new entry to add
        if ($editentries == -1 and  $this->df->user_can_manage_entry(0)) {
            // new entry is its own group
            $listgroup['New entry'] = array($this->new_entry_text());
        }

        // prepare list of textarea fields on page for enabling editors where needed
        $editorsonpage = array();

        $fields = $this->get_fields();

        // compile entries if any
        if ($this->entries) {
            // prepare for groupby
            $groupbyvalue = '';
            $grouptext = array();

            foreach ($this->entries as $entry) {   // Might be just one
                $newgroup = '';

                // May we edit this entry? (!$editable hides the entry action tags in _entry field )
                $editable = $this->df->user_can_manage_entry($entry);
                $editthisone = false;
                if ($editable and $editentries and $editentries != -1) {    // edit all authorized entries
                    $editthisone = in_array($entry->id, $editentries);
                }

                // Replacing tags
                $entry->baseurl = $this->baseurl;
                $patterns = array();
                foreach ($fields as $field) {
                    if ($fieldpatterns = $field->patterns($entry, $editthisone, $editable)) {
                        $patterns = array_merge_recursive($patterns, $fieldpatterns);

                        // in case this field may require editor after form
                        if ($editthisone and $field->type == 'textarea' and $field->is_editor()) {
                            if (!isset($editorsonpage[$entry->id])) {
                                $editorsonpage[$entry->id] = array();
                            }
                            $editorsonpage[$entry->id][] = $field->id();
                        }

                        // Are we grouping?
                        if ($this->filter->groupby and $this->filter->groupby == $field->field->id) {
                            // if editing get the pattern for browsing b/c we need the content
                            if ($editthisone) {
                                $fieldpatterns = $field->patterns($entry);
                            }
                            $fieldvalues = current($fieldpatterns);
                            $fieldvalue = count($fieldvalues) ? current($fieldvalues) : '';
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

            if ($this->filter->page) {
                $page = '&amp;page='. $this->filter->page;
            } else {
                $page = '';
            }
            echo '<div ', $float, '>',
                '<form enctype="multipart/form-data" id="viewform" action="', $this->baseurl, $page, '" method="post">',
                '<input type="hidden" name="d" value="', $this->df->id(), '" />',
                '<input type="hidden" name="sesskey" value="', sesskey(), '" />',
                '<input type="hidden" name="filter" value="', $this->filter->id, '" />',
                '<input type="hidden" name="rid" value="', $this->filter->rid, '" />';
            if ($action) {
                echo '<input type="hidden" name="', $action, '" value="', $actionvalue, '" />';
            }

            if ($editentries) {
                echo '<div style="text-align:center">',
                    '<input type="submit" name="saveandview" value="', get_string('saveandview','dataform'), '" />',
                    '&nbsp;<input type="submit" name="cancel" value="', get_string('cancel'), '" />',
                    '</div>';
            }
            foreach ($listgroup as $name => $content) {
                if ($name) {
                    print_heading($name, '', 3, 'main');
                } else {
                    echo '<br />';
                }

                $this->display_section($content, $name);
                echo '<br />';
            }

            if ($editentries) {
                echo '<div style="text-align:center">',
                    '<input type="submit" name="saveandview" value="', get_string('saveandview','dataform'), '" />',
                    '&nbsp;<input type="submit" name="cancel" value="', get_string('cancel'), '" />',
                    '</div>';
            }

            echo '</form></div>';

            // after the form stuff
            // use html editors
            foreach ($editorsonpage as $entryid => $fieldids) {
                foreach ($fieldids as $fieldid) {
                    $fields[$fieldid]->use_html_editor($entryid);
                }
            }
            // view specific (plus fields)
            $this->print_after_form();
        }

        // TODO
        if ($return) {
        //    return $listheader. $listbody. $listfooter;
        }

    }

    /**
     * 
     */
    public function generate_default_view() {
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
                $groupname = get_string($group, 'dataform');
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
     *
     */
    protected function replace_view_tags(){
        $patterns = $this->patterns();

        $this->view->section = $this->replace_tags($patterns, $this->view->section);
    }

    /**
     *
     */
    protected function display_section($content, $name = '', $return = false) {
    }

    /**
     *
     */
    protected function patterns($options = null) {
        global $CFG;

        $patterns = array();

        // default menu patterns
        $patterns['menus'] = array();
        $patterns['menus']['##viewsmenu##'] = $this->print_views_menu(true);
        $patterns['menus']['##filtersmenu##'] = '';

        // default user filtering patterns
        $patterns['userpref'] = array();
        $patterns['userpref']['##quicksearch##'] = '';
        $patterns['userpref']['##quickperpage##'] = '';
        $patterns['userpref']['##quickreset##'] = '';

        // views with designated filters do not allow user filtering
        if (!$this->view->filter and (!empty($this->entries) or $this->filter->id)) {

            $patterns['menus']['##filtersmenu##'] = $this->print_filters_menu(true);

            $patterns['userpref']['##quicksearch##'] = $this->print_quick_search(true);
            $patterns['userpref']['##quickperpage##'] = $this->print_quick_perpage(true);
            $patterns['userpref']['##quickreset##'] = $this->print_quick_reset(true);
        }

        // new entry
        $patterns['newentry'] = array('##addnewentry##' => '');
        if (!isset($options['hidenewentry']) and !$this->df->user_at_max_entries(true)) {            // TODO: move to a view attribute so as to call the function only once
            $patterns['newentry']['##addnewentry##'] = '<a href="'. $this->baseurl. '&amp;new=1&amp;sesskey='. sesskey(). '">Add a new entry</a>';
        }

        // paging bar
        if ($pagingbarpatterns = $this->paging_bar_patterns()) {;
            $patterns = array_merge_recursive($patterns, $pagingbarpatterns);
        }

        // activity grading
        if ($this->df->data->rating and  has_capability('mod/dataform:managetemplates', $this->df->context)) {
            if ($rating = $this->df->get_field('_rating') and $comment = $this->df->get_field('_comment')) {
                $patterns = array_merge_recursive($patterns, $rating->activity_patterns());
                $patterns = array_merge_recursive($patterns, $comment->activity_patterns());
            }
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
        $patterns['generalactions']['##multiedit:icon##'] = '<button type="submit" name="multiedit"><img src="'. $CFG->pixpath. '/t/edit.gif" class="icon" alt="'. get_string('multiedit', 'dataform'). '" title="'. get_string('multiedit', 'dataform'). '" /></button>';
        $patterns['generalactions']['##multidelete##'] = '<input type="submit" name="multidelete" value="'. get_string('multidelete', 'dataform'). '" />';
        $patterns['generalactions']['##multidelete:icon##'] = '<button type="submit" name="multidelete"><img src="'. $CFG->pixpath. '/t/delete.gif" class="icon" alt="'. get_string('multidelete', 'dataform'). '" title="'. get_string('multidelete', 'dataform'). '" /></button>';
        if ($this->df->data->approval and has_capability('mod/dataform:approve', $this->df->context)) {
            $patterns['generalactions']['##multiapprove##'] = '<input type="submit" name="multiapprove"  value="'. get_string('multiapprove', 'dataform'). '" />';
            $patterns['generalactions']['##multiapprove:icon##'] = '<button type="submit" name="multiapprove"><img src="'. $CFG->pixpath. '/i/tick_green_big.gif" class="icon" alt="'. get_string('multiapprove', 'dataform'). '" title="'. get_string('multiapprove', 'dataform'). '" /></button>';
        } else {
            $patterns['generalactions']['##multiapprove##'] = '';
        }

        return $patterns;
    }

    /**
     *
     */
    protected function paging_bar_patterns() {

        $patterns = array('pagingbar' => array('##pagingbar##' => ''));

        // typical entry 'more' request. If not single view show return to list instead of paging bar
        if ($this->filter->rid and $this->filter->perpage != 1) {
            $page = $this->filter->page ? '&amp;page='.$this->filter->page : '';
            $patterns['pagingbar']['##pagingbar##'] = '<a href="'. $this->baseurl. $page. '">'.get_string('viewreturntolist', 'dataform').'</a>';

        // typical groupby, one group per page case. show paging bar as per number of groups
        } else if (isset($this->filter->pagenum)) {
            $patterns['pagingbar']['##pagingbar##'] = print_paging_bar($this->filter->pagenum,
                                                                        $this->filter->page,
                                                                        1,
                                                                        $this->baseurl. '&amp;',
                                                                        'page',
                                                                        '',
                                                                        true);
        // standard paging bar case
        } else if ($this->availableentries != count($this->entries)) {
            $patterns['pagingbar']['##pagingbar##'] = print_paging_bar($this->availableentries,
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
        $newtext = $this->replace_tags($patterns, $this->view->param2);

        return $newtext;
    }

    /**
     *
     */
    protected function entry_text($patterns) {
        return '';
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
            if ($this->filter->id) {
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
            $insort = false;
            $sortdir = 0; // TODO: asc order is arbitrary here and should be determined differently
            $sortfields = array();
            if ($this->filter->customsort) {
                $sortfields = unserialize($this->filter->customsort);
                if ($insort = in_array($fieldid, array_keys($sortfields))) {
                    $sortdir = $sortfields[$fieldid];
                    unset($sortfields[$fieldid]);
                }
            }
            $sortfields = array($fieldid => $sortdir) + $sortfields;
            $this->filter->customsort = serialize($sortfields);

            // set the search criterion for each page
            // that is, get an array of distinct current content of the groupby field
            $field = $this->df->get_field_from_id($fieldid);
            if ($groupbyvalues = $field->get_distinct_content($sortdir)) {
                if ($this->filter->page < count($groupbyvalues)) {
                    $val = $groupbyvalues[$this->filter->page];
                    $searchfields = array();
                    if ($this->filter->customsearch) {
                        $searchfields = unserialize($this->filter->customsearch);
                    }
                    $search = array('', '', $val);
                    if (!isset($searchfields[$fieldid]['AND'])) {
                        $searchfields[$fieldid]['AND'] = array($search);
                    } else {
                        array_unshift($searchfields[$fieldid]['AND'], $search);
                    }
                    $this->filter->customsearch = serialize($searchfields);
                    $this->filter->pagenum = count($groupbyvalues);
                }
            }
        }
    }
}
?>