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

// Some constants
define ('DATAFORM_MAX_ENTRIES', 50);
define ('DATAFORM_CAP_EXPORT', 'mod/dataform:viewalluserpresets');

// Users having assigned the default role "Non-editing teacher" 
// can export database records
// Using the mod/dataform capability "viewalluserpresets" for 
// Moodle 1.9.x, so no change in the role system is required.
// In Moodle >= 2, new roles may be introduced and used instead. 

require_once('field_class.php');
require_once('view_class.php');

/**
 * Dataform class
 */
class dataform {

    public $cm = NULL;       // The course module
    public $course = NULL;   // The course record
    public $data = NULL;     // The dataform record
    public $context = NULL;  // 

    protected $fields = array();
    protected $views = array();
    protected $filters = array();
    protected $records = array();

    // built in fields
    protected $builtinfields = array(
            0   => array('type' => '_entry', 'name' => 'entry'),
            // searchable builtin fields
            -1  => array('type' => '_time', 'name' => 'timecreated'),
            -2  => array('type' => '_time', 'name' => 'timemodified'),
            -3  => array('type' => '_user', 'name' => 'firstname'),
            -4  => array('type' => '_user', 'name' => 'lastname')
            // TODO: 
            // -5  => array('type' => '_checkbox', 'name' => 'approved')
            //-6  => array('type' => '_comment', 'name' => 'comments'),
            //-7  => array('type' => '_rating', 'name' => 'ratings')
        );
    
    protected $groupmode = 0;
    protected $currentgroup = 0;    // current group id

    protected $show = true;
    
    /**
     * constructor
     */
    public function dataform($d = 0, $id = 0, $rid = 0) {
        if ($d) {
            if (! $this->data = get_record('dataform', 'id', $d)) {
                error('Dataform ID is incorrect');
            }
            if (! $this->course = get_record('course', 'id', $this->data->course)) {
                error('Course is misconfigured');
            }
            if (! $this->cm = get_coursemodule_from_instance('dataform', $this->id(), $this->course->id)) {
                error('Course Module ID was incorrect');
            }
            //$record = NULL;
        } else if ($id) {
            if (! $this->cm = get_coursemodule_from_id('dataform', $id)) {
                error('Course Module ID was incorrect');
            }
            if (! $this->course = get_record('course', 'id', $this->cm->course)) {
                error('Course is misconfigured');
            }
            if (! $this->data = get_record('dataform', 'id', $this->cm->instance)) {
                error('Course module is incorrect');
            }
            //$record = NULL;

        } else if ($rid) {
            if (! $record = get_record('dataform_records', 'id', $rid)) {
                error('Record ID is incorrect');
            }
            if (! $this->data = get_record('dataform', 'id', $record->dataid)) {
                error('Dataform ID is incorrect');
            }
            if (! $this->course = get_record('course', 'id', $this->data->course)) {
                error('Course is misconfigured');
            }
            if (! $this->cm = get_coursemodule_from_instance('dataform', $this->id(), $this->course->id)) {
                error('Course Module ID was incorrect');
            }
            $this->records[] = $record;
        }

        $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
    }
    
    /**
     * 
     */
    public function id() {
        return $this->data->id;
    }

    /**
     * 
     */
    public function name() {
        return $this->data->name;
    }

    /**
     * 
     */
    public function get_ready_to_browse() {
        global $CFG;
        
        // Define some default values for browsing
        // If we have an empty Dataform then redirect because this page is useless without data
        if (!$this->fields = get_records('dataform_fields','dataid', $this->id())) {
            // If no fields we need to add some fields 
            if (has_capability('mod/dataform:managetemplates', $this->context)) {
                redirect($CFG->wwwroot.'/mod/dataform/fields.php?d='. $this->id());
            }
        }
        if ((!$this->views = get_records('dataform_views','dataid', $this->id())) or empty($this->data->defaultview)) {
            // Add some views or set a default view 
            if (has_capability('mod/dataform:managetemplates', $this->context)) {
                redirect($CFG->wwwroot.'/mod/dataform/views.php?d='.$this->id());
            }
        }

        //if dataform activity closed don't let students in
        if (!has_capability('mod/dataform:manageentries', $this->context)) {
            $timenow = time();
            if (!empty($this->data->timeavailable) and $this->data->timeavailable > $timenow) {
                $noshow = 'notopenyet';
                $whenshow = $this->data->timeavailable;
                $this->print_header_simple();
                print_box(get_string($noshow, 'dataform', userdate($whenshow)) );
                print_footer($this->course);
                exit;
            }
        }
        
        $this->currentgroup = groups_get_activity_group($this->cm);
        $this->groupmode = groups_get_activity_groupmode($this->cm);
        
        add_to_log($this->course->id, 'dataform', 'view', 'view.php?id='. $this->cm->id, $this->id(), $this->cm->id);
    }

    /**
     * 
     */
    public function print_header_simple() {
        global $CFG, $USER, $displaynoticegood, $displaynoticebad;

        $navigation = build_navigation('', $this->cm);
        print_header($this->name(), '', $navigation,
                '', '', true, update_module_button($this->cm->id, $this->course->id, get_string('modulename', 'dataform')),
                navmenu($this->course, $this->cm));
        print_heading(format_string($this->name()));

        // TODO: should this part be here or outside printed after the tabs?
        // Print any notices
        if (!empty($displaynoticegood)) {
            notify($displaynoticegood, 'notifysuccess');    // good (usually green)
        } else if (!empty($displaynoticebad)) {
            notify($displaynoticebad);                     // bad (usuually red)
        }
    }

    // TODO: view should decide whether to print dataform-intro and dataform-rss
    /**
     * 
     */
    public function print_rsslink() {
        // Link to the RSS feed
        if (!empty($CFG->enablerssfeeds) && !empty($CFG->dataform_enablerssfeeds) && $this->data->rssarticles > 0) {
            echo '<div style="float:right;">';
            rss_print_link($this->course->id, $USER->id, 'dataform', $this->id(), get_string('rsstype'));
            echo '</div>';
            echo '<div style="clear:both;"></div>';
        }
    }
    
    /**
     * 
     */
    public function print_intro() {
        // TODO: make intro stickily closable
        // display the intro only when there are on pages: if ($this->data->intro and empty($page)) {
        if ($this->data->intro) {
            $options = new object();
            $options->noclean = true;
            print_box(format_text($this->data->intro, FORMAT_MOODLE, $options), 'generalbox', 'intro');
        }
    }

    /**
     * 
     */
    public function print_blocks(&$PAGE, $side) {
        global $CFG;
        
        if (!empty($CFG->showblocksonmodpages)) {
            // Compute blocks
            $pageblocks = blocks_setup($PAGE);
            $blocks_preferred_width = bounded_number(180, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]), 210);

            $columnside = ($side == BLOCK_POS_LEFT) ? 'left' : 'right';
            // If we have blocks, then print the left side here
            if ((blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $PAGE->user_is_editing())) {
                echo '<td style="width: '.$blocks_preferred_width.'px;" id="'. $columnside. '-column">';
                print_container_start();
                blocks_print_group($PAGE, $pageblocks, $side);
                print_container_end();
                echo '</td>';
            }
        }
    }

    /**
     * has a user reached the max number of entries?
     * if interval is set then required entries, max entrie etc. are relative to the current interval
     * output bool   
     */
    public function user_at_max_entries($perinterval = false) {
        if (!$this->data->maxentries or has_capability('mod/dataform:manageentries', $this->context)) {
            return false;
        } else {
            return ($this->user_num_entries($perinterval) >= $this->data->maxentries);
        }
    }

    /**
     *
     * output bool   
     */
    public function user_requires_entries($notify = false) {
        if (has_capability('mod/dataform:manageentries', $this->context)) {
            return false;
        } else {
            // Check the number of entries required against the number of entries already made
            $numentries = $this->user_num_entries();
            if ($this->data->requiredentries > 0 and $numentries < $this->data->requiredentries) {
                $entriesleft = $this->data->requiredentries - $numentries;
                if ($notify) {
                    notify(get_string('entrieslefttoadd', 'dataform', $entriesleft));
                }
            }

            // Check the number of entries required before to view other participant's entries against the number of entries already made (doesn't apply to teachers)
            if ($this->data->requiredentriestoview > 0 and $numentries < $this->data->requiredentriestoview) {
                $entrieslefttoview = $this->data->requiredentriestoview - $numentries;
                if ($notify) {
                    notify(get_string('entrieslefttoaddtoview', 'dataform', $entrieslefttoview));
                }
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * TODO: do we still need this function
     * given a record, returns true if the record belongs to the current user
     * input @param $record - record              
     * output bool       
     */
    public function user_is_entry_owner($userid){
        global $USER;
        if (empty($USER->id)) {
            return false;
        }

        if ($userid) {
            return ($userid == $USER->id);
        }

        return false;
    }


    /**
     * 
     */
    public function get_filter_from_id($filterid = 0) {
        if ($filterid == 0) {  // df default sort
            $filter = new object();
            $filter->id = 0; 
            $filter->dataid = $this->id(); 
            $filter->perpage = 0; 
            $filter->groupby = 0; 
            $filter->customsort = $this->data->defaultsort;
            $filter->customsearch = '';
            $filter->search = '';
        } else if ($filterid == -1) {  // user preferences
            $filter = new object();
            $filter->id = -1; 
            $filter->dataid = $this->id(); 
            $filter->perpage = get_user_preferences('dataform_'. $this->id(). '_perpage', 0); 
            $filter->groupby = get_user_preferences('dataform_'. $this->id(). '_groupby', 0); 
            $filter->customsort = trim(get_user_preferences('dataform_'. $this->id(). '_customsort', $this->data->defaultsort));
            $filter->customsearch = trim(get_user_preferences('dataform_'. $this->id(). '_customsearch', ''));
            $filter->search = (!$filter->customsearch ? trim(get_user_preferences('dataform_'. $this->id(). '_search', '')) : '');
        } else {
            $filter = get_record('dataform_filters', 'id', $filterid);
        }

        return $filter;
    }


    /**
     * 
     */
    public function get_entries($filter) {
        global $CFG;
        
        $entries = new object();
        $entries->max = 0;
        $entries->found = 0;
        $entries->entries = array();
        
        // $rid was specified, so try to show the entry
        if ($this->records) {
            $record = $this->records[0];
            $this->records = array();   // reset records list
            
            if (!$this->currentgroup or $record->groupid == $this->currentgroup or $record->groupid == 0) {
                // is there any reason not to show the requested entry?
                if ($this->data->approval and !$record->approved and $record->userid != $USER->id and !has_capability('mod/dataform:manageentries', $this->context)) {
                    print_error('notapproved', 'dataform');
                } else {
                    // OK, we can show this one
                    $this->records[$record->id] = $record;
                    $entries->max = $entries->found = 1;
                    $entries->entries = $this->records;
                }
            }

        } else {

            // get sort and search settings
            $perpage = $filter->perpage;
            $groupby = $filter->groupby;
            $customsort = trim($filter->customsort) ;
            $customsearch = trim($filter->customsearch);
            $simplesearch = !$customsearch ? trim($filter->search) : '';

            // get other options
            $ignorerequireentries = isset($filter->ignorerequireentries) ? $filter->ignorerequireentries : 0;
            $ignorerequireapproval = isset($filter->ignorerequireapproval) ? $filter->ignorerequireapproval : 0;

            $page = $filter->page; 
            
            $sortfields = array();
            if ($customsort) {
                $sorties = explode(',', $customsort);
                foreach ($sorties as $sorti) {
                    $tmparr = explode(' ', $sorti);
                    $sortfields[$tmparr[0]] = $tmparr[1];
                }
            }

            $searchfields = array();
            if ($customsearch) {
                $searchies = explode(',', $customsearch);
                foreach ($searchies as $searchi) {
                    $tmparr = explode('|||', $searchi);
                    $searchfields[$tmparr[0]] = $tmparr[1];
                }
            }

            // construct the sql
            $ilike = sql_ilike(); //Be case-insensitive

            // SORT settings
            $sortwhat = '';
            $sortcount = '';
            $sorttables = '';
            $sortwhere = '';
            $orderby = "r.timecreated ASC";
            
            if (!empty($sortfields)) {
                $orderby = array();
                foreach ($sortfields as $fieldid => $sortdir) {
                    $field = $this->get_field_from_id($fieldid);
                    if ($fieldid > 0) {
                        $sortcontent = sql_compare_text('c'. $fieldid. '.'. $field->get_sort_field());
                        $sortcontentfull = $field->get_sort_sql($sortcontent);
                    } else {
                        $sortcontentfull = $field->get_sort_sql();
                    }
                        
                    $orderby[] = $sortcontentfull. ' '. ($sortdir ? 'DESC' : 'ASC');
                    if ($fieldid > 0) {
                        $sortwhat .= ', '. $sortcontentfull;
                        $sortcount .= ($sortcount ? ', ' : ''). 'c'. $fieldid. '.recordid';
                        $sorttables .= ', '. $CFG->prefix.'dataform_content c'. $fieldid. ' ';
                        $sortwhere .= ' AND c'. $fieldid. '.recordid = r.id '.
                                    ' AND c'. $fieldid. '.fieldid = '. $fieldid;
                    }
                }
                $orderby = implode(', ', $orderby);
            }

            $what = ' DISTINCT r.id, r.approved, r.timecreated, r.timemodified, r.userid '.
                        ', u.firstname, u.lastname '. $sortwhat;
            $count = ' COUNT(DISTINCT c.recordid) ';
            $tables =   $CFG->prefix.'dataform_records r '.
                        ', '. $CFG->prefix.'dataform_content c '.
                        ', '. $CFG->prefix.'user u '.
                        $sorttables;
            $where =  'WHERE  c.recordid = r.id '.
                        ' AND r.dataid = '.$this->id().
                        ' AND r.userid = u.id '.
                        $sortwhere; 
            $sortorder = ' ORDER BY '.$orderby. ' ';


            // USER filtering
            $whereuser = '';
            if ($ignorerequireentries and $this->user_requires_entries(true)) {
                $whereuser = ' AND u.id = ' . $USER->id;
            }

            // GROUP filtering
            $wheregroup = '';
            if ($this->currentgroup) {
                $wheregroup = ' AND (r.groupid = '. $this->currentgroup. ' OR r.groupid = 0) ';
            }
            
            // APPROVE filtering
            $whereapprove = '';
            if ($this->data->approval and !has_capability('mod/dataform:approve', $this->context)) {
                if (isloggedin()) {
                    if ($ignorerequireapproval) {
                        $whereapprove = ' AND (r.approved=1 OR r.userid='.$USER->id.') ';
                    }
                } else {
                    $whereapprove = ' AND r.approved=1 ';
                }
            }
            
            // SEARCH filtering
            // get custom search from view, or from user preferences
            // view custom search overrides user preferences

            $searchtables   = '';
            $wheresearch    = '';

            if (!empty($searchfields)) {
                $sortfieldids = !empty($sortfields) ? array_keys($sortfields) : 0;
                foreach($searchfields as $fieldid => $val) {
                    if (!empty($sortfielddids) and !in_array($fieldid, $sortfielddids)) {
                        $searchtables .= ', '. $CFG->prefix. 'dataform_content c'. $fieldid. ' ';
                    }
                    $field = $this->get_field_from_id($fieldid);                
                    $wheresearch .= ' AND c'. $fieldid. '.recordid = r.id'.
                                    ' AND ('. $field->get_search_sql($val). ') ';
                }
            } else if ($simplesearch) {
                $searchtables .= ', '. $CFG->prefix. 'dataform_content cs ';
                $wheresearch = ' AND cs.recordid = r.id'.
                                ' AND (cs.content '. $ilike. ' \'%'. $simplesearch. '%\' OR u.firstname '. $ilike. ' \'%'. $simplesearch. '%\' OR u.lastname '. $ilike. ' \'%'. $simplesearch. '%\' ) ';
            }
               
            // To actually fetch the records
            $fromsql    = "FROM $tables $searchtables $where $whereuser $wheregroup $whereapprove $wheresearch";
            $sqlselect  = "SELECT $what $fromsql $sortorder";
            $sqlcount   = "SELECT $count $fromsql";   // Total number of records when searching
            $sqlmax     = "SELECT $count FROM $tables $where $whereuser $wheregroup $whereapprove"; // number of all records user may see

            // Work out the paging numbers and counts
            $searchcount = 0;
            if (empty($wheresearch)) {
                $maxcount = $searchcount = count_records_sql($sqlmax);
            } else {
                $maxcount = count_records_sql($sqlmax);
            }

            // there are records to return
            if ($maxcount) {
                $searchcount = count_records_sql($sqlcount);
                
                // Get the actual records
                if ($perpage == 0) {    // show what you got and disregard paging
                    $maxcount = $searchcount;
                    $this->records = get_records_sql($sqlselect);
                } else {
                    $this->records = get_records_sql($sqlselect, $page * $perpage, $perpage);
                }
                $entries->max = $maxcount;
                $entries->found = $searchcount;
                $entries->entries = $this->records;
            }
        }
        
        return $entries;
    }

    /**
     * 
     */
    public function process_entries($action, $rids, $confirm = 0) {
        global $CFG, $USER;

        $records = array();
        if ($rids) { // some records are specified for action
            $managerids = explode(',',$rids);
            foreach ($managerids as $rid) {
                if ($record = get_record('dataform_records', 'id', $rid)) {
                    // Must be from this dataform and owned by current user or user can manage entries
                    if ($record->dataid == $this->id()) {
                        if ($action == 'approve' and has_capability('mod/dataform:approve', $this->context)) {
                            $records[] = $record;
                        } else {
                            if ($this->user_is_entry_owner($record->userid)
                                    or has_capability('mod/dataform:manageentries', $this->context)) {
                                $records[] = $record;
                            }
                        }
                    }
                }
            }
        }
        
        $processedrids = array();
        $strnotify = '';

        if (empty($records) and $action != 'add' and $action != 'new') {
            notify(get_string('norecordsto'. $action,'dataform'), 'notifyfailure');
        } else {
            if ($confirm) {
                switch ($action) {
                    case 'new':
                        // adds a new empty entry (used by import and by add = 0)
                        if (!$this->user_can_manage_entry()) {
                            break;
                        }

                        $record = new object();
                        $record->userid = $USER->id;
                        $record->dataid = $this->id();
                        $record->groupid = $this->currentgroup;
                        $record->timecreated = $record->timemodified = time();
                        if (has_capability('mod/dataform:approve', $this->context)) {
                            $record->approved = 1;
                        } else {
                            $record->approved = 0;
                        }
                        $recordid = insert_record('dataform_records',$record);                       
                        $processedrids[] = $recordid;                       

                        // Insert new dataform_content fields with NULL contents
                        if (!$this->fields) {
                            $fields = get_records('dataform_fields', 'dataid', $this->id(), '', 'name, id, type');
                        } else {
                            $fields = $this->fields;
                        }
                        foreach ($fields as $field) {
                            $content = new object();
                            $content->recordid = $recordid;
                            $content->fieldid = $field->id;
                            if (!insert_record('dataform_content', $content)) {
                                print_error('cannotinsertrecord', '', '', $recordid);
                            }
                        }
                        
                        // no notifications
                        break;
                        
                    // add a new entry from form
                    case 'add':
                        if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/view.php')) {
                            // just in case the user opens two forms at the same time
                            if ($this->user_can_manage_entry()) {
                                //Empty form checking - you can't submit an empty form!
                                $emptyform = true;      // assume the worst

                                if (has_capability('mod/dataform:manageentries',$this->context)) {
                                    $emptyform = false; // allow teacher to add empty entries
                                } else {
                                    foreach ($forminput as $name => $value) {
                                        if (strpos($name, 'field_') !== false) {   // assuming only field names contain field_
                                            $namearr = explode('_', $name);  // Second one is the field id
                                            if (empty($field->field) || ($namearr[1] != $field->field->id)) {  // Try to reuse classes
                                                $field = $this->get_field_from_id($namearr[1]);
                                            }
                                            if ($field->notemptyfield($value, $name)) {
                                                $emptyform = false;
                                                break;             // if anything has content, this form is not empty, so stop now!
                                            }
                                        }
                                    }
                                }

                                // not a teacher cannot add an empty form
                                if ($emptyform) {
                                    notify(get_string('emptyaddform','dataform'));
                                } else {
                                    if ($processedrids = $this->process_entries('new', 0, true)
                                            and $recordid = $processedrids[0]) {
                                        //for each field in the add form, add it to the dataform_content.
                                        foreach ($forminput as $name => $value){
                                            if (strpos($name, 'field_') !== false) {   // assuming only field names contain field_
                                                $namearr = explode('_', $name);  // Second one is the field id
                                                if (empty($field->field) || ($namearr[1] != $field->field->id)) {  // Try to reuse classes
                                                    $field = $this->get_field_from_id($namearr[1]);
                                                }
                                                if ($field) {
                                                    $field->update_content($recordid, $value, $name);
                                                }
                                            }
                                        }
                                    }
                                    
                                    // TODO: if paging, set the page to where the newly added record will appear according to the sorting criteria
                                }
                            }
                        }
                        
                        $strnotify = 'recordsadded';
                        break;

                    case 'duplicate':
                        foreach ($records as $record) {
                            // can user add anymore entries?
                            if (!$this->user_can_manage_entry()) {
                                // TODO: notify something
                                break;
                            }

                            // Get content of record to duplicat
                            $contents = get_records('dataform_content', 'recordid', $record->id);
                            
                            // Add a duplicated record and content
                            $newrec = $record;
                            $newrec->userid = $USER->id;
                            $newrec->dataid = $this->id();
                            $newrec->groupid = $this->currentgroup;
                            $newrec->timecreated = $newrec->timemodified = time();
                            if (has_capability('mod/dataform:approve', $this->context)) {
                                $newrec->approved = 1;
                            } else {
                                $newrec->approved = 0;
                            }
                            $recordid = insert_record('dataform_records',$newrec);                       

                            foreach ($contents as $content) {
                                $newcontent = $content;
                                $newcontent->recordid = $recordid;
                                if (!insert_record('dataform_content', $newcontent)) {
                                    print_error('cannotinsertrecord', '', '', $recordid);
                                }
                            }
                            $processedrids[] = $recordid;
                        }

                        $strnotify = 'recordsduplicated';
                        break;

                    // TODO:
                    case 'update':
                        if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/view.php')) {
                            foreach ($records as $record) {
                                // TODO: do we need this check? (unmanagable entry should not be editable in the first place)
                                if (!$this->user_can_manage_entry($record)) {
                                    // TODO: notify something
                                    continue;
                                }

                                // reset approved flag after student edit
                                if (!has_capability('mod/dataform:approve', $this->context)) {
                                    $record->approved = 0;
                                }

                                $record->groupid = $this->currentgroup;
                                $record->timemodified = time();
                                update_record('dataform_records',$record);

                                /// Update all content
                                $field = NULL;
                                foreach ($forminput as $name => $value) {
                                    if (strpos($name, 'field_') !== false) {   // assuming only field names contain field_
                                        $namearr = explode('_', $name);  // Second one is the field id, last is rhe record id
                                        if (array_pop($namearr) == $record->id) {    
                                            if (empty($field->field) || ($namearr[1] != $field->field->id)) {  // Try to reuse classes
                                                $field = $this->get_field_from_id($namearr[1]);
                                            }
                                            if ($field) {
                                                $field->update_content($record->id, $value, $name);
                                            }
                                        }
                                    }
                                }
                                $processedrids[] = $record->id;
                            }
                        }  

                        $strnotify = 'recordsupdated';
                        break;
                        
                    case 'approve':
                        $newrecord = new object();
                        $newrecord->approved = 1;
                        foreach ($records as $record) {
                            if (!$record->approved and has_capability('mod/dataform:approve', $this->context)) {
                                $newrecord->id = $record->id;
                                update_record('dataform_records', $newrecord);
                                $processedrids[] = $record->id;
                            }
                        }

                        $strnotify = 'recordsapproved';
                        break;
                        
                    case 'delete':
                        foreach ($records as $record) {
                            if (!$this->user_can_manage_entry($record)) {
                                // TODO: notify something
                                continue;
                            }

                            if ($contents = get_records('dataform_content','recordid', $record->id)) {
                                foreach ($contents as $content) {  // Delete files or whatever else this field allows
                                    if ($field = $this->get_field_from_id($content->fieldid)) { // Might not be there
                                        $field->delete_content($content->recordid);
                                    }
                                }
                            }
                            delete_records('dataform_content','recordid', $record->id);
                            delete_records('dataform_records','id', $record->id);
                            $processedrids[] = $record->id;
                        }

                        $strnotify = 'recordsdeleted';
                        break;
                        
                    default:
                        break;
                }
                
                add_to_log($this->course->id, 'dataform', 'record '. $action, 'view.php?id='. $this->cm->id, $this->id(), $this->cm->id);
                if ($strnotify) {
                    $recordsprocessed = $processedrids ? count($processedrids) : 'No';
                    notify(get_string($strnotify, 'dataform', $recordsprocessed), 'notifysuccess');
                }
                return $processedrids;
            } else {
                // Print a confirmation page
                notice_yesno(get_string('recordsconfirm'. $action, 'dataform', count(explode(',', $rids))),
                        'view.php?d='.$this->id().'&amp;'. $action. '='.$rids.'&amp;confirm=1&amp;sesskey='.sesskey(),
                        'view.php?d='.$this->id());

                print_footer($this->course);
                exit;
            }
        }
    }

    /**
     * 
     */
    public function process_views($action, $vids, $confirm = 0) {

        $views = array();
        if ($vids) { // some views are specified for action
            $managevids = explode(',',$vids);
            foreach ($managevids as $vid) {
                if ($view = get_record('dataform_views', 'id', $vid)) {
                    // Must be from this dataform and owned by current user or user can manage entries
                    if ($view->dataid == $this->id() and has_capability('mod/dataform:manageentries', $this->context)) {
                        $views[] = $view;
                    }
                }
            }
        }
        
        $processedvids = array();
        $strnotify = '';

        if (empty($views)) {
            notify(get_string('noviewsto'. $action,'dataform'), 'notifyfailure');
        } else {
            if ($confirm) {
                switch ($action) {
                    case 'visible':
                        $updateview = new object();
                        foreach ($views as $view) {
                            if ($view->id == $this->data->defaultview) {
                                // TODO: notify something
                                continue;
                            } else {    
                                $updateview->id = $view->id;
                                $updateview->visible = (($view->visible + 1) % 3);  // hide = 0; (show) = 1; show = 2
                                update_record('dataform_views', $updateview);

                                $processedvids[] = $view->id;
                            }
                        }
                        
                        $strnotify = 'viewsupdated';
                        break;

                    case 'hide':
                        $updateview = new object();
                        $updateview->visible = 0;
                        foreach ($views as $view) {
                            if ($view->id == $this->data->defaultview) {
                                // TODO: notify something
                                continue;
                            } else {    
                                $updateview->id = $view->id;
                                update_record('dataform_views', $updateview);
                                $processedvids[] = $view->id;
                            }
                        }

                        $strnotify = 'viewsupdated';
                        break;

                    case 'filter':
                        $updateview = new object();
                        $filterid = optional_param('fid', 0, PARAM_INT);
                        foreach ($views as $view) {
                            if ($filterid != $view->param1) {
                                $updateview->id = $view->id;
                                $updateview->param1 = $filterid;
                                update_record('dataform_views', $updateview);
                                $processedvids[] = $view->id;
                            }
                        }

                        $strnotify = 'viewsupdated';
                        break;

                    case 'duplicate':
                        foreach ($views as $view) {
                            // TODO: check for limit

                            // set name
                            while ($this->name_exists('views', $view->name, $view->id)) {
                                $view->name = 'Copy of '. $view->name;
                            }
                            $viewid = insert_record('dataform_views',$view);                       

                            $processedvids[] = $viewid;
                        }
                            
                        $strnotify = 'viewsadded';
                        break;
                        
                    case 'delete':
                        foreach ($views as $view) {
                            // TODO: delete filters
                            //delete_records('dataform_filters','viewid', $view->id);
                            delete_records('dataform_views','id', $view->id);
                            $processedvids[] = $view->id;

                            // reset default view if needed
                            if ($view->id == $this->data->defaultview) {
                                $this->set_default_view();
                            }
                        }
                        $strnotify = 'viewsdeleted';
                        break;
                        
                    case 'default':
                        foreach ($views as $view) { // there should be only one
                            if ($view->visible != 2) {
                                $updateview = new object();
                                $updateview->id = $view->id;
                                $updateview->visible = 2;
                                update_record('dataform_views', $updateview);
                            }

                            $this->set_default_view($view->id);
                            // TODO: shouldn't produced this notification
                            $processedvids[] = $view->id;
                            break;
                        }
                        $strnotify = 'viewsupdated';
                        break;
                        
                    default:
                        break;
                }
                
                add_to_log($this->course->id, 'dataform', 'view '. $action, 'views.php?id='. $this->cm->id, $this->id(), $this->cm->id);
                if ($strnotify) {
                    $viewsprocessed = $processedvids ? count($processedvids) : 'No';
                    notify(get_string($strnotify, 'dataform', $viewsprocessed), 'notifysuccess');
                }
                return $processedvids;
            } else {
                // Print a confirmation page
                notice_yesno(get_string('viewsconfirm'. $action, 'dataform', count(explode(',', $vids))),
                        'views.php?d='.$this->id().'&amp;'. $action. '='.$vids.'&amp;confirm=1&amp;sesskey='.sesskey(),
                        'views.php?d='.$this->id());

                print_footer($this->course);
                exit;
            }
        }
    }

    /**
     * 
     */
    public function process_filters($action, $fids, $confirm = 0) {
        global $CFG;

        $filters = array();
        if ($fids) { // some filters are specified for action
            $managefids = explode(',',$fids);
            foreach ($managefids as $fid) {
                if ($filter = get_record('dataform_filters', 'id', $fid)) {
                    // Must be from this dataform and owned by current user or user can manage entries
                    if ($filter->dataid == $this->id() and has_capability('mod/dataform:manageentries', $this->context)) {
                        $filters[] = $filter;
                    }
                }
            }
        }
        
        $processedfids = array();
        $strnotify = '';

        if (empty($filters) and $action != 'add') {
            notify(get_string('nofiltersto'. $action, 'dataform'), 'notifyfailure');
        } else {
            if ($confirm) {
                switch ($action) {
                    case 'add':     // add new or update existing
                        if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/filters.php')) {
                            // Check for arrays and convert to a comma-delimited string
                            $this->convert_arrays_to_strings($forminput);
                            
                            // no point in submitting an empty filter - assume the worst
                            $emptyform = true;
                            
                            if (!empty($filters)) { // updating an existing filter
                                $filter = $filters[0];
                            } else {
                                $filter = new object();
                            }
                            
                            // TODO: required fields should be done by the moodle form
                            if ($forminput->name) {
                                $emptyform = false;
                            }
                            
                            $filter->dataid = $this->id();
                            $filter->name = $forminput->name;
                            $filter->description = $forminput->description;
                            $filter->perpage = $forminput->perpage;
                            $filter->groupby = $forminput->groupby;
                            $filter->search = '';
                            $filter->customsort = '';
                            $filter->customsearch = '';
                            
                            if (!empty($forminput->customsort) or !empty($forminput->customsearch)) {
                                // TODO: can this be optimized?
                                $fields = $this->get_fields();

                                // custom sort
                                if (!empty($forminput->customsort)) {
                                    $sortlist = array();
                                    foreach ($fields as $field) {
                                        if ($sortorder = $field->parse_sort()) {
                                            $sortlist[$sortorder[0]] = $field->field->id. ' '. $sortorder[1];
                                        }
                                    }

                                    if (!empty($sortlist)) {
                                        ksort($sortlist);
                                        $filter->customsort = implode(',', $sortlist);
                                        $emptyform = false;
                                    } else {
                                        $filter->customsort = '';
                                    }
                                    
                                }
                                // custom search
                                if (!empty($forminput->customsearch)) {
                                    $searchlist = array();
                                    foreach ($fields as $field) {
                                        $searchvalue = $field->parse_search();
                                        if ($searchvalue) {
                                            $searchlist[] = $field->field->id. ' '. $searchvalue;
                                        }
                                    }

                                    if (!empty($searchlist)) {
                                        $filter->customsearch = implode(',', $searchlist);
                                        $emptyform = false;
                                    }
                                    
                                    $filter->search = '';

                                // simple search
                                } else {
                                    $filter->customsearch = '';

                                    if (!empty($forminput->simplesearch)) {
                                        $filter->search = $forminput->simplesearch;
                                        $emptyform = false;
                                    }
                                }

                            } else {
                                // simple search
                                if (!empty($forminput->simplesearch)) {
                                    $filter->search = $forminput->simplesearch;
                                    $emptyform = false;
                                }
                            }

                            if ($emptyform) {
                                notify(get_string('emptyaddform','dataform'));

                            } else {
                                if (!empty($filter->id)) { // updating
                                    update_record('dataform_filters', $filter);
                                    $processedfids[] = $filter->id;
                                    $strnotify = 'filtersupdated';
                                } else {
                                    $filterid = insert_record('dataform_filters', $filter);                       
                                    $processedfids[] = $filterid; 
                                    $strnotify = 'filtersadded';
                                }
                            }
                        }

                        break;
                        
                    case 'duplicate':
                        if (!empty($filters)) {
                            foreach ($filters as $filter) {
                                // TODO: check for limit
                                // set new name
                                while ($this->name_exists('filters', $filter->name, $filter->id)) {
                                    $filter->name = 'Copy of '. $filter->name;
                                }
                                $filterid = insert_record('dataform_filters', $filter);                       

                                $processedfids[] = $filterid;
                            }
                        }
                        $strnotify = 'filtersadded';
                        break;
                        
                    case 'show':
                        $updatefilter = new object();
                        $updatefilter->visible = 1;
                        foreach ($filters as $filter) {
                            $updatefilter->id = $filter->id;
                            update_record('dataform_filters', $updatefilter);

                            $processedfids[] = $filter->id;
                        }
                        
                        $strnotify = 'filtersupdated';
                        break;

                    case 'hide':
                        $updatefilter = new object();
                        $updatefilter->visible = 0;
                        foreach ($filters as $filter) {
                            $updatefilter->id = $filter->id;
                            update_record('dataform_filters', $updatefilter);

                            $processedfids[] = $filter->id;
                        }

                        $strnotify = 'filtersupdated';
                        break;

                    case 'delete':
                        foreach ($filters as $filter) {
                            delete_records('dataform_filters','id', $filter->id);
                            $processedfids[] = $filter->id;
                        }
                        $strnotify = 'filtersdeleted';
                        break;
                        
                    default:
                        break;
                }
                
                add_to_log($this->course->id, 'dataform', 'filter '. $action, 'filters.php?id='. $this->cm->id, $this->id(), $this->cm->id);
                if (!empty($strnotify)) {
                    $filtersprocessed = $processedfids ? count($processedfids) : 'No';
                    notify(get_string($strnotify, 'dataform', $filtersprocessed), 'notifysuccess');
                }
                return $processedfids;
            } else {
                // Print a confirmation page
                notice_yesno(get_string('filtersconfirm'. $action, 'dataform', count(explode(',', $vids))),
                        'filters.php?d='.$this->id().'&amp;'. $action. '='.$vids.'&amp;confirm=1&amp;sesskey='.sesskey(),
                        'filters.php?d='.$this->id());

                print_footer($this->course);
                exit;
            }
        }
    }

    /**
     * 
     */
    public function filters_menu() {
        $menufilters = array();

        if ($filters = get_records('dataform_filters','dataid', $this->id(), 'name ASC', 'id, name, visible')) {
            foreach ($filters as $fid => $filter){
                if ($filter->visible or has_capability('mod/dataform:managetemplates', $this->context)) {
                    $menufilters[$fid] = $filter->name;
                }
            }
        }
        
        return $menufilters;
    }

    /**
     * 
     */
    public function process_fields($action, $fids, $confirm = 0) {
        global $CFG;

        $fields = array();
        if ($fids) { // some fields are specified for action
            $managefids = explode(',',$fids);
            foreach ($managefids as $fid) {
                if ($field = get_record('dataform_fields', 'id', $fid)) {
                    // Must be from this dataform and owned by current user or user can manage entries
                    if ($field->dataid == $this->id() and has_capability('mod/dataform:manageentries', $this->context)) {
                        $fields[] = $field;
                    }
                }
            }
        }
        
        $processedfids = array();
        $strnotify = '';

        if (empty($fields) and $action != 'add') {
            notify(get_string('nofieldsto'. $action,'dataform'), 'notifyfailure');
        } else {
            if ($confirm) {
                switch ($action) {
                    case 'add':     // add new 
                        if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/fields.php')) {
                            // Check for arrays and convert to a comma-delimited string
                            $this->convert_arrays_to_strings($forminput);

                            // Create a field object to collect and store the data safely
                            $field = $this->get_field($forminput->type);
                            $field->set_field($forminput);
                            $field->insert_field();
                        }
                        $strnotify = 'fieldsadded';
                        break;
                        
                    case 'update':     // update existing
                        if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/fields.php')) {
                            // Check for arrays and convert to a comma-delimited string
                            $this->convert_arrays_to_strings($forminput);

                            // Create a field object to collect and store the data safely
                            $field = $this->get_field($fields[0]);
                            $oldfieldname = $field->field->name;
                            $field->set_field($forminput);
                            $field->update_field();

                            // Update the views
                            if ($oldfieldname != $field->field->name) {
                                $this->replace_field_in_views($oldfieldname, $field->field->name);
                            }
                        }
                        $strnotify = 'fieldsupdated';
                        break;
                        
                    case 'duplicate':
                        foreach ($fields as $field) {
                            // set new name
                            while ($this->name_exists('fields', $field->name, $field->id)) {
                                $field->name .= '_1';
                            }
                            $fieldid = insert_record('dataform_fields',$field);                       
                            $processedfids[] = $fieldid;
                        }                            
                        $strnotify = 'fieldsadded';
                        break;
                        
                    case 'delete':
                        foreach ($fields as $fld) {
                            if ($field = $this->get_field($fld)) {
                                $field->delete_field();
                                $processedfids[] = $field->field->id;
                                // Update views
                                $this->replace_field_in_views($field->field->name, '');
                            }
                        }
                        $strnotify = 'fieldsdeleted';
                        break;
                        
                    default:
                        break;
                }
                
                add_to_log($this->course->id, 'dataform', 'field '. $action, 'fields.php?id='. $this->cm->id, $this->id(), $this->cm->id);
                if ($strnotify) {
                    $fieldsprocessed = $processedfids ? count($processedfids) : 'No';
                    notify(get_string($strnotify, 'dataform', $fieldsprocessed), 'notifysuccess');
                }
                return $processedfids;
            } else {
                // Print a confirmation page
                notice_yesno(get_string('fieldsconfirm'. $action, 'dataform', count(explode(',', $fids))),
                        'fields.php?d='.$this->id().'&amp;'. $action. '='.$fids.'&amp;confirm=1&amp;sesskey='.sesskey(),
                        'fields.php?d='.$this->id());

                print_footer($this->course);
                exit;
            }
        }
    }

    /**
     * 
     */
    public function display_filter_form($fid = 0) {
        global $CFG;
        
        if (!$fields = $this->get_fields()) {
            // TODO: print some notification here
            return;
        }

        // get filter
        if ($fid) {
            $filter = get_record('dataform_filters', 'id', $fid);
            $action = '&amp;update='. $fid;
        } else {
            $filter = NULL;
            $action = '&amp;add=1';
        }
        
        $customsort = !empty($filter->customsort);
        $customsearch = !empty($filter->customsearch);
        $perpage = array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                       20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
        
        $strname = '';

        $checked = $customsort ? ' checked="checked" ' : '';
        $strsort = '<input type="checkbox" id="customsortcb" name="customsort" value="1" '.$checked.' onchange="showHideAdv(\'customsort\', this.checked);" /><label for="customsortcb">'.get_string('customsort', 'dataform').'</label>';

        $checked = $customsearch ? ' checked="checked" ' : '';
        $disabled = $customsearch ? ' disabled="disabled" ' : '';
        $search = $customsearch ? '' : !empty($filter->search) ? $filter->search : '';
        $strsearch = '<input type="checkbox" id="customsearchcb" name="customsearch" value="1" '.$checked.' onchange="showHideAdv(\'customsearch\', this.checked);" /><label for="customsearchcb">'.get_string('customsearch', 'dataform').'</label>';

        // there are fields so display the form
        echo '<form id="filterform" enctype="multipart/form-data" action="', $CFG->wwwroot, '/mod/dataform/filters.php?d=', $this->id(), $action, '" method="post">',
            '<input type="hidden" name="d" value="', $this->id(), '" />',
            '<input type="hidden" name="sesskey" value="', sesskey(), '" />',
            '<br />',
            '<table cellpadding="5">',
                '<tr>',
                    '<td class="c0"><label for="name">', get_string('filtername', 'dataform'), '</label></td>',
                    '<td class="c1"><input class="fieldname" type="text" name="name" id="name" value="', ($fid ? p($filter->name) : ''), '" /></td>',
                '</tr>',
                '<tr>',
                    '<td class="c0"><label for="description">',get_string('filterdescription', 'dataform'), '</label></td>',
                    '<td class="c1"><input class="fielddescription" type="text" name="description" id="description" value="', ($fid ? p($filter->description) : ''), '" /></td>',
                '</tr>',
                '<tr>',
                    '<td class="c0"><label for="perpage">',get_string('filterperpage', 'dataform'), '</label></td>',
                    '<td class="c1">', choose_from_menu($perpage, 'perpage', ($fid ? $filter->perpage : 10), 'choose', '', 0, true, false, 0, 'perpage'), '</td>',
                '</tr>',
                '<tr>',
                    '<td class="c0"><label for="groupby">',get_string('filtergroupby', 'dataform'), '</label></td>',
                    '<td class="c1">', choose_from_menu($this->get_fields(array(0), true), 'groupby', ($fid ? $filter->groupby : 0), 'choose', '', 0, true, false, 0, 'groupby'), '</td>',
                '</tr>',
                '<tr>',
                    '<td class="c0"><label for="simplesearch">',get_string('search', 'dataform'), '</label></td>',
                    '<td class="c1"><input type="text" size="16" name="simplesearch" id="simplesearch"'. $disabled. ' value="'.s($search).'" /></td>',
                '</tr>',
                '<tr><td valign="top" colspan="2">';

        // three columns: field name, sorting options, search options        
        /// table headings

        $table->id = 'filteroptions';
        $table->head = array($strname, $strsort, $strsearch);
        $table->align = array('left','left','left');
        $table->wrap = array(true, true, true);

        $display = 'hidecustomoptions';      // row display (class name)
        $displaysort = 'none';  // sort options display (style=display:)
        $displaysearch = 'none';  // search options display (style=display:)

        $sortfields = array();
        $searchfields = array();
        
        // show custom options if requested
        if ($customsort or $customsearch) {
            $display = 'showcustomoptions';
            
            // parse filter sort settings
            if ($customsort) {
                $displaysort = 'inline';
                $sortis = explode(',', $filter->customsort);
                foreach ($sortis as $sorti) {
                    $sortfields[] = explode(' ', $sorti);
                }
            }
            
            // parse filter search settings
            if ($customsearch) {
                $displaysearch = 'inline';
                $searchis = explode(',', $filter->customsearch);
                foreach ($searchis as $serchi) {
                    // TODO: this is a really ridiculous delimiter
                    $searchfields[] = explode('|||', $serchi);
               }
            }
        }
            
        foreach ($fields as $fieldid => $field) {
            
            $sortorder = $sortdir = 0;
            // check if field participates in default sort
            if (!empty($sortfields)) {
                foreach ($sortfields as $index => $sortfield) {
                    if ($sortfield[0] == $fieldid) {
                        $sortorder = $index + 1;
                        $sortdir =  $sortfield[1];
                        break;
                    }
                }
            }
            
            $searchvalue = '';
            // check if field participates in search
            if (!empty($searchfields)) {
                foreach ($searchfields as $searchfield) {
                    if ($searchfield[0] == $fieldid) {
                        $searchvalue =  $searchfield[1];
                        break;
                    }
                }
            }
            
            $table->rowclass[] = $display;
            $table->data[] = array(
                // name
                $field->field->name,
                // sort options
                '<div name="customsortoption" style="display:'. $displaysort. ';">'. $field->display_sort($sortorder, $sortdir). '</div>',
                // search options
                '<div name="customsearchoption" style="display:'. $displaysearch. ';">'. $field->display_search($searchvalue). '</div>'
            );
        }
        
        print_table($table);
        echo '<br />',
            '</td></tr></table>',
            '<div class="mdl-align">',
            '<input type="submit" name="saveandview" value="', get_string('saveandview', 'dataform'), '" />',
            '</div>
            </form>
            <style type="text/css">
                .hidecustomoptions{display:none;}
                .showcustomoptions{display:row-table;}
            </style>
            <script type="text/javascript">
            //<![CDATA[
            <!-- Start
            // javascript for hiding/displaying advanced search form

            function showHideAdv(type, checked) {
                var rows = document.getElementById(\'filteroptions\').rows;
                for (var i=1;i<rows.length-1;i++) {
                    rows[i].className = rows[i].className.replace(\'hidecustomoptions\',\'showcustomoptions\');
                }
                var divs = document.getElementsByTagName(\'div\');
                for(var i=0; i<divs.length; i++) {
                    if(divs[i].getAttribute(\'name\') == type + \'option\') {
                        if (checked) {
                            divs[i].style.display = \'inline\';
                        } else {
                            divs[i].style.display = \'none\';
                        }
                    }
                }
                if (type == \'customsearch\') {
                    var simplesearch = document.getElementById(\'simplesearch\');
                    if (checked) {
                        simplesearch.value = \'\';
                        simplesearch.disabled = \'disabled\';
                    } else {
                        simplesearch.removeAttribute(\'disabled\');
                    }                        
                }
            }
            //  End -->
            //]]>
            </script>';
    }    

    /**
     * 
     */
    public function user_can_manage_entry($entry = 0) {
        global $USER;
        
        // teachers can always add entries
        if (has_capability('mod/dataform:manageentries',$this->context)) {
            return true;    
        // for others, it depends ...
        } else if (has_capability('mod/dataform:writeentry', $this->context)) {
            $timeavailable = $this->data->timeavailable;
            $timedue = $this->data->timedue;
            $allowlate = $this->data->allowlate;
            $now = time();

            // activity time frame
            if (!($now > $timeavailable and ($now < $timedue or ($now > $timedue and $allowlate)))) {
                return false;
            }

            // group access
            if (!(has_capability('moodle/site:accessallgroups', $this->context)
                        or !$this->groupmode
                        or ($this->currentgroup and groups_is_member($this->currentgroup))
                        or (!$this->currentgroup and $this->groupmode == VISIBLEGROUPS))) {
                return false;   // for members only
            }

            // managing a certain entry                        
            if ($entry) {
                // entry owner
                if (empty($USER->id) or $USER->id != $entry->id) {
                    return false;   // who are you anyway???
                }
                
                // ok owner, what's the time (limit)?
                if ($timelimit = $this->data->timelimit) {
                    $elapsed = $now - $entry->timecreated;
                    if ($elapsed > $timelimit) {
                        return false;    // too late ...
                    }
                }

                // phew, within time limit, but wait, are we still in the same interval?
                if ($timeinterval = $this->data->timeinterval) {
                    $elapsed = $now - $timeavailable;
                    $currentintervalstarted = (floor($elapsed / $timeinterval) * $timeinterval) + $timeavailable;
                    if ($entry->timecreated < $currentintervalstarted) {
                        return false;  // nop ...
                    }
                }

            // trying to add an entry    
            } else if ($this->user_at_max_entries(true)) {   
                return false;    // no more entries for you (come back next interval or so)
            }
            
            // if you got this far you probably deserve to do something ... go ahead
            return true;        
        }

        return false;
    }

    /**
     * given a field name                      
     * this function creates an instance of the particular subfield class   *
     */
    public function get_field_from_name($name){
        $field = get_record('dataform_fields', 'name', $name, 'dataid', $this->id());
        if ($field) {
            return $this->get_field($field);
        } else {
            return false;
        }
    }

    /**
     * given a field id                        
     * this function creates an instance of the particular subfield class
     */
    public function get_field_from_id($fieldid) {
        if ($fieldid > 0) { // user field
            $field = get_record('dataform_fields', 'id', $fieldid, 'dataid', $this->id());
        } else {        // builtin field so create the object
            $field = new object();
            $field->id = $fieldid;
            $field->dataid = $this->id();
            $field->type = $this->builtinfields[$fieldid]['type'];
            $field->name = $this->builtinfields[$fieldid]['name'];
            $field->description = '';
        }
        if ($field) {
            return $this->get_field($field);
        } else {
            return false;
        }
    }

    /**
     * returns a subclass field object given a record of the field
     * used to invoke plugin methods                   
     * input: $param $field record from db, or field type   
     */
    public function get_field($fld) {
        global $CFG;
        
        if ($fld) {
            if (is_object($fld)) {
                $type = $fld->type;
            } else {
                $type = $fld;
                $fld = 0;
            }
            require_once('field/'. $type. '/field_class.php');
            $fieldclass = 'dataform_field_'. $type;
            $field = new $fieldclass($fld, $this);
            return $field;
        }
    }

    /**
     * 
     */
    public function get_fields($exclude = null, $menu = false) {
        $fields = array();
        if ($this->get_fields_records()) {  // if no user fields, no fields at all 
            // get user fields
            foreach ($this->fields as $fieldid => $field) {
                if (empty($exclude) or !in_array($fieldid, $exclude)) {
                    if ($menu) {
                        $fields[$fieldid] = $field->name;
                    } else {
                        $fields[$fieldid] = $this->get_field($field);
                    }
                }
            }

            // get builtinfields
            foreach ($this->builtinfields as $fieldid => $field) {
                if (empty($exclude) or !in_array($fieldid, $exclude)) {
                    if ($menu) {
                        $fields[$fieldid]= $field['name'];
                    } else {
                        $fields[$fieldid]= $this->get_field_from_id($fieldid);
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * 
     */
    public function get_fields_records() {
        if (!$this->fields) {
            $this->fields = get_records('dataform_fields','dataid', $this->id());
        }
        return $this->fields;
    }

    /**
     * given a view name                      
     * this function creates an instance of the particular subtemplate class   *
     */
    public function get_view_from_name($name){
        $view = get_record('dataform_views', 'name', $name, 'dataid', $this->id());
        if ($view) {
            return $this->get_view($view);
        } else {
            return false;
        }
    }

    /**
     * given a template id                        
     * this function creates an instance of the particular subtemplate class   *
     */
    public function get_view_from_id($viewid) {
        // get view class
        if (!$viewid) {
            if (!$this->data->defaultview) {
                if (has_capability('mod/dataform:managetemplates', $this->context)) {
                    redirect($CFG->wwwroot.'/mod/dataform/views.php?d='.$this->id());
                } else {
                    // TODO: notify something
                }
            } else {
                $viewid = $this->data->defaultview;
            }
        }            
        $view = get_record('dataform_views', 'id', $viewid);
        if ($view) {
            return $this->get_view($view);
        } else {
            return false;
        }
    }

    /**
     * returns a view subclass object given a view record or view type
     * invoke plugin methods
     * input: $param $vt - mixed, view record or view type
     */
    public function get_view($vt) {
        global $CFG;
        
        if ($vt) {
            if (is_object($vt)) {
                $type = $vt->type;
            } else {
                $type = $vt;
                $vt = 0;
            }
            require_once('view/'. $type. '/view_class.php');
            $viewclass = 'dataform_view_'. $type;
            $view = new $viewclass($vt, $this);
            return $view;
        }
    }

    /**
     * 
     */
    public function set_default_view($viewid = 0) {
        $rec = new object();
        $rec->id = $this->id();
        $rec->defaultview = $viewid;
        if (!update_record('dataform', $rec)) {
            error('There was an error updating the database');
        }
        $this->data->defaultview = $viewid;
    }

    /**
     * 
     */
    public function preset_name($shortname, $path) {
        // We are looking inside the preset itself as a first choice, but also in normal data directory
        $string = get_string('modulename', 'datapreset_'.$shortname);

        if (substr($string, 0, 1) == '[') {
            return $shortname;
        } else {
            return $string;
        }
    }

    /**
     * 
     */
    public function is_directory_a_preset($directory) {
        $directory = rtrim($directory, '/\\') . '/';
        $status = file_exists($directory.'singletemplate.html') &&
                  file_exists($directory.'listtemplate.html') &&
                  file_exists($directory.'listtemplateheader.html') &&
                  file_exists($directory.'listtemplatefooter.html') &&
                  file_exists($directory.'addtemplate.html') &&
                  file_exists($directory.'rsstemplate.html') &&
                  file_exists($directory.'rsstitletemplate.html') &&
                  file_exists($directory.'csstemplate.css') &&
                  file_exists($directory.'jstemplate.js') &&
                  file_exists($directory.'preset.xml');
        return $status;
    }

    /**
     * Returns an array of all the available presets
     */
    public function get_available_presets($context) {
        global $CFG, $USER;
        $presets = array();
        if ($dirs = get_list_of_plugins('mod/dataform/preset')) {
            foreach ($dirs as $dir) {
                $fulldir = $CFG->dirroot.'/mod/dataform/preset/'.$dir;
                if ($this->is_directory_a_preset($fulldir)) {
                    $preset = new object;
                    $preset->path = $fulldir;
                    $preset->userid = 0;
                    $preset->shortname = $dir;
                    $preset->name = $this->preset_name($dir, $fulldir);
                    if (file_exists($fulldir.'/screenshot.jpg')) {
                        $preset->screenshot = $CFG->wwwroot.'/mod/dataform/preset/'.$dir.'/screenshot.jpg';
                    } else if (file_exists($fulldir.'/screenshot.png')) {
                        $preset->screenshot = $CFG->wwwroot.'/mod/dataform/preset/'.$dir.'/screenshot.png';
                    } else if (file_exists($fulldir.'/screenshot.gif')) {
                        $preset->screenshot = $CFG->wwwroot.'/mod/dataform/preset/'.$dir.'/screenshot.gif';
                    }
                    $presets[] = $preset;
                }
            }
        }

        if ($userids = get_list_of_plugins('dataform/preset', '', $CFG->dataroot)) {
            foreach ($userids as $userid) {
                $fulldir = $CFG->dataroot.'/dataform/preset/'.$userid;
                if ($userid == 0 || $USER->id == $userid || has_capability('mod/dataform:viewalluserpresets', $context)) {
                    if ($dirs = get_list_of_plugins('dataform/preset/'.$userid, '', $CFG->dataroot)) {
                        foreach ($dirs as $dir) {
                            $fulldir = $CFG->dataroot.'/dataform/preset/'.$userid.'/'.$dir;
                            if ($this->is_directory_a_preset($fulldir)) {
                                $preset = new object;
                                $preset->path = $fulldir;
                                $preset->userid = $userid;
                                $preset->shortname = $dir;
                                $preset->name = $this->preset_name($dir, $fulldir);
                                if (file_exists($fulldir.'/screenshot.jpg')) {
                                    $preset->screenshot = $CFG->wwwroot.'/mod/dataform/preset/'.$dir.'/screenshot.jpg';
                                } else if (file_exists($fulldir.'/screenshot.png')) {
                                    $preset->screenshot = $CFG->wwwroot.'/mod/dataform/preset/'.$dir.'/screenshot.png';
                                } else if (file_exists($fulldir.'/screenshot.gif')) {
                                    $preset->screenshot = $CFG->wwwroot.'/mod/dataform/preset/'.$dir.'/screenshot.gif';
                                }
                                $presets[] = $preset;
                            }
                        }
                    }
                }
            }
        }
        return $presets;
    }

    /**
     * Search for a field name and replaces it with another one in all the *
     * form templates. Set $newfieldname as '' if you want to delete the   *
     * field from the form.                   
     */
    public function replace_field_in_views($searchfieldname, $newfieldname) {
        if (!empty($newfieldname)) {
            $prestring = '[[';
            $poststring = ']]';
            $idpart = '#id';

        } else {
            $prestring = '';
            $poststring = '';
            $idpart = '';
        }
        // TODO:
        if ($this->views) {
            foreach ($this->views as $view) {
                $template = $this->get_template($view);
                $template->replace_field_in_view($searchfieldname, $newfieldname);
            }
        }
    }

    /**
     * given a type                        
     * this function creates an instance of the particular subtemplate class   *
     */
    /**
     * 
     */
    public function name_exists($where, $name, $id=0) {
        global $CFG;
        $dataid = $this->id();
        $LIKE = sql_ilike();
        if ($id) {
            return record_exists_sql("SELECT * from {$CFG->prefix}dataform_{$where} df
                                      WHERE df.name $LIKE '$name' AND df.dataid = $dataid
                                        AND ((df.id < $id) OR (df.id > $id))");
        } else {
            return record_exists_sql("SELECT * from {$CFG->prefix}dataform_{$where} df
                                      WHERE df.name $LIKE '$name' AND df.dataid = $dataid");
        }
    }

    /**
     * 
     */
    public function convert_arrays_to_strings(&$fieldinput) {
        foreach ($fieldinput as $key => $val) {
            if (is_array($val)) {
                $str = '';
                foreach ($val as $inner) {
                    $str .= $inner . ',';
                }
                $str = substr($str, 0, -1);
                $fieldinput->$key = $str;
            }
        }
    }

    /**
     * returns the number of entries already made by this user; defaults to all entries
     * @param global $CFG, $USER               
     * @param boolean $perinterval
     * output int   
     */
    protected function user_num_entries($perinterval = false) {
        global $USER, $CFG;
        
        $andwhereinterval = '';
        if ($timeinterval = $this->data->timeinterval and $perinterval) {
            $timeavailable = $this->data->timeavailable;
            $elapsed = time() - $timeavailable;
            $intervalstarttime = (floor($elapsed / $timeinterval) * $timeinterval) + $timeavailable;
            $intervalendtime = $intervalstarttime + $timeinterval;
            $andwhereinterval = ' AND timecreated >= '. $intervalstarttime. ' AND timecreated < '. $intervalendtime;
        }
            
        $sql = 'SELECT COUNT(*) FROM '. $CFG->prefix. 'dataform_records '.
                ' WHERE dataid = '. $this->id(). ' AND userid = '. $USER->id. $andwhereinterval;
        return count_records_sql($sql);
    }


}
 
/**
 * Preset importer
 */
class dataform_preset_importer {
    function dataform_PresetImporter($course, $cm, $data, $userid, $shortname) {
        global $CFG;
        $this->course = $course;
        $this->cm = $cm;
        $this->data = $data;
        $this->userid = $userid;
        $this->shortname = $shortname;
        $this->folder = $this->preset_path($course, $userid, $shortname);
    }

    protected function dataform_preset_path($course, $userid, $shortname) {
        global $USER, $CFG;
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        $userid = (int)$userid;
        if ($userid > 0 && ($userid == $USER->id || has_capability('mod/dataform:viewalluserpresets', $context))) {
            return $CFG->dataroot.'/dataform/preset/'.$userid.'/'.$shortname;
        } else if ($userid == 0) {
            return $CFG->dirroot.'/mod/dataform/preset/'.$shortname;
        } else if ($userid < 0) {
            return $CFG->dataroot.'/temp/dataform/'.-$userid.'/'.$shortname;
        }
        return 'Does it disturb you that this code will never run?';
    }

    protected function clean_preset($folder) {
        $status = @unlink($folder.'/singletemplate.html') &&
                  @unlink($folder.'/listtemplate.html') &&
                  @unlink($folder.'/listtemplateheader.html') &&
                  @unlink($folder.'/listtemplatefooter.html') &&
                  @unlink($folder.'/addtemplate.html') &&
                  @unlink($folder.'/rsstemplate.html') &&
                  @unlink($folder.'/rsstitletemplate.html') &&
                  @unlink($folder.'/csstemplate.css') &&
                  @unlink($folder.'/jstemplate.js') &&
                  @unlink($folder.'/preset.xml');

        // optional
        @unlink($folder.'/asearchtemplate.html');
        return $status;
    }

    protected function get_settings() {
        global $CFG;

        if (!$this->is_directory_a_preset($this->folder)) {
            error("$this->userid/$this->shortname Not a preset");
        }

        /* Grab XML */
        $presetxml = file_get_contents($this->folder.'/preset.xml');
        $parsedxml = xmlize($presetxml, 0);

        $allowed_settings = array('intro', 'comments', 'requiredentries', 'requiredentriestoview',
                                  'maxentries', 'rssarticles', 'approval', 'defaultsortdir', 'defaultsort');

        /* First, do settings. Put in user friendly array. */
        $settingsarray = $parsedxml['preset']['#']['settings'][0]['#'];
        $settings = new StdClass();

        foreach ($settingsarray as $setting => $value) {
            if (!is_array($value)) {
                continue;
            }
            if (!in_array($setting, $allowed_settings)) {
                // unsupported setting
                continue;
            }
            $settings->$setting = $value[0]['#'];
        }

        /* Now work out fields to user friendly array */
        $fieldsarray = $parsedxml['preset']['#']['field'];
        $fields = array();
        foreach ($fieldsarray as $field) {
            if (!is_array($field)) {
                continue;
            }
            $f = new StdClass();
            foreach ($field['#'] as $param => $value) {
                if (!is_array($value)) {
                    continue;
                }
                $f->$param = addslashes($value[0]['#']);
            }
            $f->dataid = $this->id();
            $f->type = clean_param($f->type, PARAM_ALPHA);
            $fields[] = $f;
        }
        /* Now add the HTML templates to the settings array so we can update d */
        $settings->singletemplate     = file_get_contents($this->folder."/singletemplate.html");
        $settings->listtemplate       = file_get_contents($this->folder."/listtemplate.html");
        $settings->listtemplateheader = file_get_contents($this->folder."/listtemplateheader.html");
        $settings->listtemplatefooter = file_get_contents($this->folder."/listtemplatefooter.html");
        $settings->addtemplate        = file_get_contents($this->folder."/addtemplate.html");
        $settings->rsstemplate        = file_get_contents($this->folder."/rsstemplate.html");
        $settings->rsstitletemplate   = file_get_contents($this->folder."/rsstitletemplate.html");
        $settings->csstemplate        = file_get_contents($this->folder."/csstemplate.css");
        $settings->jstemplate         = file_get_contents($this->folder."/jstemplate.js");

        //optional
        if (file_exists($this->folder."/asearchtemplate.html")) {
            $settings->asearchtemplate = file_get_contents($this->folder."/asearchtemplate.html");
        } else {
            $settings->asearchtemplate = NULL;
        }

        $settings->instance = $this->id();

        /* Now we look at the current structure (if any) to work out whether we need to clear db
           or save the data */
        if (!$currentfields = get_records('dataform_fields', 'dataid', $this->id())) {
            $currentfields = array();
        }

        return array($settings, $fields, $currentfields);
    }

    function import_options() {
        if (!confirm_sesskey()) {
            error("Sesskey Invalid");
        }
        $strblank = get_string('blank', 'dataform');
        $strcontinue = get_string('continue');
        $strwarning = get_string('mappingwarning', 'dataform');
        $strfieldmappings = get_string('fieldmappings', 'dataform');
        $strnew = get_string('new');
        $sesskey = sesskey();
        list($settings, $newfields,  $currentfields) = $this->get_settings();
        echo '<div class="presetmapping"><form action="preset.php" method="post">';
        echo '<div>';
        echo '<input type="hidden" name="action" value="finishimport" />';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<input type="hidden" name="d" value="'.$this->id().'" />';
        echo '<input type="hidden" name="fullname" value="'.$this->userid.'/'.$this->shortname.'" />';
        if (!empty($currentfields) && !empty($newfields)) {
            echo "<h3>$strfieldmappings ";
            helpbutton('fieldmappings', $strfieldmappings, 'dataform');
            echo '</h3><table>';
            foreach ($newfields as $nid => $newfield) {
                echo "<tr><td><label for=\"id_$newfield->name\">$newfield->name</label></td>";
                echo '<td><select name="field_'.$nid.'" id="id_'.$newfield->name.'">';
                $selected = false;
                foreach ($currentfields as $cid => $currentfield) {
                    if ($currentfield->type == $newfield->type) {
                        if ($currentfield->name == $newfield->name) {
                            echo '<option value="'.$cid.'" selected="selected">'.$currentfield->name.'</option>';
                            $selected=true;
                        }
                        else {
                            echo '<option value="'.$cid.'">'.$currentfield->name.'</option>';
                        }
                    }
                }
                if ($selected)
                    echo '<option value="-1">-</option>';
                else
                    echo '<option value="-1" selected="selected">-</option>';
                echo '</select></td></tr>';
            }
            echo '</table>';
            echo "<p>$strwarning</p>";
        } else if (empty($newfields)) {
            error("New preset has no defined fields!");
        }
        echo '<div class="overwritesettings"><label for="overwritesettings">'.get_string('overwritesettings', 'dataform');
        echo '<input id="overwritesettings" name="overwritesettings" type="checkbox" /></label></div>';
        echo '<input class="button" type="submit" value="'.$strcontinue.'" /></div></form></div>';
    }

    function import() {
        global $CFG;
        list($settings, $newfields, $currentfields) = $this->get_settings();
        $preservedfields = array();
        $overwritesettings = optional_param('overwritesettings', 0, PARAM_BOOL);
        /* Maps fields and makes new ones */
        if (!empty($newfields)) {
            /* We require an injective mapping, and need to know what to protect */
            foreach ($newfields as $nid => $newfield) {
                $cid = optional_param("field_$nid", -1, PARAM_INT);
                if ($cid == -1) continue;
                if (array_key_exists($cid, $preservedfields)) error("Not an injective map");
                else $preservedfields[$cid] = true;
            }
            foreach ($newfields as $nid => $newfield) {
                $cid = optional_param("field_$nid", -1, PARAM_INT);
                /* A mapping. Just need to change field params. Data kept. */
                if ($cid != -1 and isset($currentfields[$cid])) {
                    $fieldobject = dataform_get_field_from_id($currentfields[$cid]->id, $this->data);
                    foreach ($newfield as $param => $value) {
                        if ($param != "id") {
                            $fieldobject->field->$param = $value;
                        }
                    }
                    unset($fieldobject->field->similarfield);
                    $fieldobject->update_field();
                    unset($fieldobject);
                }
                /* Make a new field */
                else {
                    include_once("field/$newfield->type/field_class.php");
                    if (!isset($newfield->description)) {
                        $newfield->description = '';
                    }
                    $classname = 'dataform_field_'.$newfield->type;
                    $fieldclass = new $classname($newfield, $this->data);
                    $fieldclass->insert_field();
                    unset($fieldclass);
                }
            }
        }

        /* Get rid of all old unused data */
        if (!empty($preservedfields)) {
            foreach ($currentfields as $cid => $currentfield) {
                if (!array_key_exists($cid, $preservedfields)) {
                    /* Data not used anymore so wipe! */
                    print "Deleting field $currentfield->name<br />";
                    $id = $currentfield->id;
                    //Why delete existing data records and related comments/ratings??
/*
                    if ($content = get_records('dataform_content', 'fieldid', $id)) {
                        foreach ($content as $item) {
                            delete_records('dataform_ratings', 'recordid', $item->recordid);
                            delete_records('dataform_comments', 'recordid', $item->recordid);
                            delete_records('dataform_records', 'id', $item->recordid);
                        }
                    }*/
                    delete_records('dataform_content', 'fieldid', $id);
                    delete_records('dataform_fields', 'id', $id);
                }
            }
        }

    // handle special settings here
        if (!empty($settings->defaultsort)) {
            if (is_numeric($settings->defaultsort)) {
                // old broken value
                $settings->defaultsort = 0;
            } else {
                $settings->defaultsort = (int)get_field('dataform_fields', 'id', 'dataid', $this->id(), 'name', addslashes($settings->defaultsort));
            }
        } else {
            $settings->defaultsort = 0;
        }

        // do we want to overwrite all current database settings?
        if ($overwritesettings) {
            // all supported settings
            $overwrite = array_keys((array)$settings);
        } else {
            // only templates and sorting
            $overwrite = array('singletemplate', 'listtemplate', 'listtemplateheader', 'listtemplatefooter',
                               'addtemplate', 'rsstemplate', 'rsstitletemplate', 'csstemplate', 'jstemplate',
                               'asearchtemplate', 'defaultsortdir', 'defaultsort');
        }

        // now overwrite current data settings
        foreach ($this->data as $prop=>$unused) {
            if (in_array($prop, $overwrite)) {
                $this->data->$prop = $settings->$prop;
            }
        }

        dataform_update_instance(addslashes_object($this->data));
        if (strstr($this->folder, '/temp/')) {
        // Removes the temporary files
            $this->clean_preset($this->folder); 
        }
        return true;
    }
}



//------------------------------------------------------------
// DATAFORM FUNCTIONS WHICH ARE CALLED FROM OUTSIDE THE MODULE
//------------------------------------------------------------

/**
 * Adds an instance of a data              
 */
function dataform_add_instance($data) {
    global $CFG;
    if (empty($data->assessed)) {
        $data->assessed = 0;
    }

    $data->timemodified = time();
    if (! $data->id = insert_record('dataform', $data)) {
        return false;
    }

    $data = stripslashes_recursive($data);
    dataform_grade_item_update($data);
    return $data->id;
}

/**
 * updates an instance of a data           
 */
function dataform_update_instance($data) {
    global $CFG;
    $data->timemodified = time();
    $data->id = $data->instance;

    if (empty($data->assessed)) {
        $data->assessed = 0;
    }
    if (empty($data->notification)) {
        $data->notification = 0;
    }
    if (! update_record('dataform', $data)) {
        return false;
    }

    $data = stripslashes_recursive($data);
    dataform_grade_item_update($data);
    return true;
}

/**
 * deletes an instance of a data           
 */
function dataform_delete_instance($id) {

    global $CFG;
    if (!$df = new dataform($id)) {
        return false;
    }
    
    // Delete all the associated information
    // get all the records in this data
    $sql = 'SELECT c.* FROM '.$CFG->prefix.'dataform_records r LEFT JOIN '.
           $CFG->prefix.'dataform_content c ON c.recordid = r.id WHERE r.dataid = '.$id;

    if ($contents = get_records_sql($sql)) {
        foreach($contents as $content) {
            $field = get_record('dataform_fields','id',$content->fieldid);
            if ($g = $df->get_field($field)) {
                $g->delete_content_files($id, $content->recordid, $content->content);
            }
            //delete the content itself
            delete_records('dataform_content','id', $content->id);
        }
    }

    // delete all the records and fields
    delete_records('dataform_records', 'dataid', $id);
    delete_records('dataform_fields','dataid',$id);

    // Delete the instance itself
    $result = delete_records('dataform', 'id', $id);
    dataform_grade_item_delete($df->data);
    return $result;
}

/**
 * returns a summary of data activity of this user
 */
function dataform_user_outline($course, $user, $mod, $data) {
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'dataform', $data->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }

    if ($countrecords = count_records('dataform_records', 'dataid', $data->id, 'userid', $user->id)) {
        $result = new object();
        $result->info = get_string('numrecords', 'dataform', $countrecords);
        $lastrecord   = get_record_sql('SELECT id,timemodified FROM '.$CFG->prefix.'dataform_records
                                         WHERE dataid = '.$data->id.' AND userid = '.$user->id.'
                                      ORDER BY timemodified DESC', true);
        $result->time = $lastrecord->timemodified;
        if ($grade) {
            $result->info .= ', ' . get_string('grade') . ': ' . $grade->str_long_grade;
        }
        return $result;
    } else if ($grade) {
        $result = new object();
        $result->info = get_string('grade') . ': ' . $grade->str_long_grade;
        $result->time = $grade->dategraded;
        return $result;
    }
    return NULL;
}

/**
 * Prints all the records uploaded by this user
 */
function dataform_user_complete($course, $user, $mod, $data) {
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'dataform', $data->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo '<p>'.get_string('grade').': '.$grade->str_long_grade.'</p>';
        if ($grade->str_feedback) {
            echo '<p>'.get_string('feedback').': '.$grade->str_feedback.'</p>';
        }
    }
    if ($records = get_records_select('dataform_records', 'dataid = '.$data->id.' AND userid = '.$user->id,
                                                      'timemodified DESC')) {
        dataform_print_template('singletemplate', $records, $data);
    }
}

/**
 * Return grade for given user or all users.
 * @return array array of grades, false if none
 */
function dataform_get_user_grades($data, $userid=0) {
    global $CFG;
    $user = $userid ? "AND u.id = $userid" : "";
    $sql = "SELECT u.id, u.id AS userid, avg(drt.rating) AS rawgrade
              FROM {$CFG->prefix}user u, {$CFG->prefix}dataform_records dr,
                   {$CFG->prefix}dataform_ratings drt
             WHERE u.id = dr.userid AND dr.id = drt.recordid
                   AND drt.userid != u.id AND dr.dataid = $data->id
                   $user
          GROUP BY u.id";
    return get_records_sql($sql);
}

/**
 * Update grades by firing grade_updated event
 * @param object $data null means all databases
 * @param int $userid specific user only, 0 mean all
 */
function dataform_update_grades($data=null, $userid=0, $nullifnone=true) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if ($data != null) {
        if ($grades = dataform_get_user_grades($data, $userid)) {
            dataform_grade_item_update($data, $grades);
        } else if ($userid and $nullifnone) {
            $grade = new object();
            $grade->userid   = $userid;
            $grade->rawgrade = NULL;
            dataform_grade_item_update($data, $grade);
        } else {
            dataform_grade_item_update($data);
        }
    } else {
        $sql = "SELECT d.*, cm.idnumber as cmidnumber
                  FROM {$CFG->prefix}data d, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
                 WHERE m.name='dataform' AND m.id=cm.module AND cm.instance=d.id";
        if ($rs = get_recordset_sql($sql)) {
            while ($data = rs_fetch_next_record($rs)) {
                if ($data->assessed) {
                    dataform_update_grades($data, 0, false);
                } else {
                    dataform_grade_item_update($data);
                }
            }
            rs_close($rs);
        }
    }
}

/**
 * Update/create grade item for given data
 * @param object $data object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function dataform_grade_item_update($data, $grades=NULL) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }
    $params = array('itemname'=>$data->name, 'idnumber'=>$data->cmidnumber);
    if (!$data->assessed or $data->scale == 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;
    } else if ($data->scale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $data->scale;
        $params['grademin']  = 0;
    } else if ($data->scale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$data->scale;
    }
    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/dataform', $data->course, 'mod', 'dataform', $data->id, 0, $grades, $params);
}

/**
 * Delete grade item for given data
 * @param object $data object
 * @return object grade_item
 */
function dataform_grade_item_delete($data) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    return grade_update('mod/dataform', $data->course, 'mod', 'dataform', $data->id, 0, NULL, array('deleted'=>1));
}

/**
 * returns a list of participants of this database                      *
 */
function dataform_get_participants($dataid) {
// Returns the users with data in one data
// (users with records in dataform_records, dataform_comments and dataform_ratings)
    global $CFG;
    $records = get_records_sql("SELECT DISTINCT u.id, u.id
                                FROM {$CFG->prefix}user u,
                                     {$CFG->prefix}dataform_records r
                                WHERE r.dataid = '$dataid'
                                  AND u.id = r.userid");
    $comments = get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}dataform_records r,
                                      {$CFG->prefix}dataform_comments c
                                 WHERE r.dataid = '$dataid'
                                   AND u.id = r.userid
                                   AND r.id = c.recordid");
    $ratings = get_records_sql("SELECT DISTINCT u.id, u.id
                                FROM {$CFG->prefix}user u,
                                     {$CFG->prefix}dataform_records r,
                                     {$CFG->prefix}dataform_ratings a
                                WHERE r.dataid = '$dataid'
                                  AND u.id = r.userid
                                  AND r.id = a.recordid");
    $participants = array();
    if ($records) {
        foreach ($records as $record) {
            $participants[$record->id] = $record;
        }
    }
    if ($comments) {
        foreach ($comments as $comment) {
            $participants[$comment->id] = $comment;
        }
    }
    if ($ratings) {
        foreach ($ratings as $rating) {
            $participants[$rating->id] = $rating;
        }
    }
    return $participants;
}

// For Participantion Reports
/**
 */
function dataform_get_view_actions() {
    return array('view');
}

/**
 */
function dataform_get_post_actions() {
    return array('add','update','record delete');
}

/**
 * Converts a database (module instance) to use the Roles System
 * @param $data         - a data object with the same attributes as a record
 *                        from the data database table
 * @param $datamodid    - the id of the data module, from the modules table
 * @param $teacherroles - array of roles that have moodle/legacy:teacher
 * @param $studentroles - array of roles that have moodle/legacy:student
 * @param $guestroles   - array of roles that have moodle/legacy:guest
 * @param $cmid         - the course_module id for this data instance
 * @return boolean      - data module was converted or not
 */
function dataform_convert_to_roles($data, $teacherroles=array(), $studentroles=array(), $cmid=NULL) {
    global $CFG;
    if (!isset($data->participants) && !isset($data->assesspublic)
            && !isset($data->groupmode)) {
        // We assume that this database has already been converted to use the
        // Roles System. above fields get dropped the data module has been
        // upgraded to use Roles.
        return false;
    }
    if (empty($cmid)) {
        // We were not given the course_module id. Try to find it.
        if (!$cm = get_coursemodule_from_instance('dataform', $data->id)) {
            notify('Could not get the course module for the data');
            return false;
        } else {
            $cmid = $cm->id;
        }
    }
    $context = get_context_instance(CONTEXT_MODULE, $cmid);

    // $data->participants:
    // 1 - Only teachers can add entries
    // 3 - Teachers and students can add entries
    switch ($data->participants) {
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/dataform:writeentry', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/dataform:writeentry', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 3:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/dataform:writeentry', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/dataform:writeentry', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    // $data->assessed:
    // 2 - Only teachers can rate posts
    // 1 - Everyone can rate posts
    // 0 - No one can rate posts
    switch ($data->assessed) {
        case 0:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/dataform:rate', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/dataform:rate', CAP_PREVENT, $teacherrole->id, $context->id);
            }
            break;
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/dataform:rate', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/dataform:rate', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 2:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/dataform:rate', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/dataform:rate', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    // $data->assesspublic:
    // 0 - Students can only see their own ratings
    // 1 - Students can see everyone's ratings
    switch ($data->assesspublic) {
        case 0:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/dataform:viewrating', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/dataform:viewrating', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/dataform:viewrating', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/dataform:viewrating', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    if (empty($cm)) {
        $cm = get_record('course_modules', 'id', $cmid);
    }

    switch ($cm->groupmode) {
        case NOGROUPS:
            break;
        case SEPARATEGROUPS:
            foreach ($studentroles as $studentrole) {
                assign_capability('moodle/site:accessallgroups', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case VISIBLEGROUPS:
            foreach ($studentroles as $studentrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }
    return true;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the data.
 * @param $mform form passed by reference
 */
function dataform_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'dataheader', get_string('modulenameplural', 'dataform'));
    $mform->addElement('checkbox', 'reset_data', get_string('deleteallentries','dataform'));

    $mform->addElement('checkbox', 'reset_dataform_notenrolled', get_string('deletenotenrolled', 'dataform'));
    $mform->disabledIf('reset_dataform_notenrolled', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_dataform_ratings', get_string('deleteallratings'));
    $mform->disabledIf('reset_dataform_ratings', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_dataform_comments', get_string('deleteallcomments'));
    $mform->disabledIf('reset_dataform_comments', 'reset_data', 'checked');
}

/**
 * Course reset form defaults.
 */
function dataform_reset_course_form_defaults($course) {
    return array('reset_data'=>0, 'reset_dataform_ratings'=>1, 'reset_dataform_comments'=>1, 'reset_dataform_notenrolled'=>0);
}

/**
 * Removes all grades from gradebook
 * @param int $courseid
 * @param string optional type
 */
function dataform_reset_gradebook($courseid, $type='') {
    global $CFG;
    $sql = "SELECT d.*, cm.idnumber as cmidnumber, d.course as courseid
              FROM {$CFG->prefix}data d, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
             WHERE m.name='dataform' AND m.id=cm.module AND cm.instance=d.id AND d.course=$courseid";
    if ($datas = get_records_sql($sql)) {
        foreach ($datas as $data) {
            dataform_grade_item_update($data, 'reset');
        }
    }
}

/**
 * Actual implementation of the rest coures functionality, delete all the
 * data responses for course $data->courseid.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function dataform_reset_userdata($data) {
    global $CFG;
    require_once($CFG->libdir.'/filelib.php');
    $componentstr = get_string('modulenameplural', 'dataform');
    $status = array();
    $allrecordssql = "SELECT r.id
                        FROM {$CFG->prefix}dataform_records r
                             INNER JOIN {$CFG->prefix}data d ON r.dataid = d.id
                       WHERE d.course = {$data->courseid}";
    $alldatassql = "SELECT d.id
                      FROM {$CFG->prefix}data d
                     WHERE d.course={$data->courseid}";
    // delete entries if requested
    if (!empty($data->reset_data)) {
        delete_records_select('dataform_ratings', "recordid IN ($allrecordssql)");
        delete_records_select('dataform_comments', "recordid IN ($allrecordssql)");
        delete_records_select('dataform_content', "recordid IN ($allrecordssql)");
        delete_records_select('dataform_records', "dataid IN ($alldatassql)");
        if ($datas = get_records_sql($alldatassql)) {
            foreach ($datas as $dataid=>$unused) {
                fulldelete("$CFG->dataroot/$data->courseid/moddataform/dataform/$dataid");
            }
        }
        if (empty($data->reset_gradebook_grades)) {
            // remove all grades from gradebook
            dataform_reset_gradebook($data->courseid);
        }
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallentries', 'dataform'), 'error'=>false);
    }

    // remove entries by users not enrolled into course
    if (!empty($data->reset_dataform_notenrolled)) {
        $recordssql = "SELECT r.id, r.userid, r.dataid, u.id AS userexists, u.deleted AS userdeleted
                         FROM {$CFG->prefix}dataform_records r
                              INNER JOIN {$CFG->prefix}data d ON r.dataid = d.id
                              LEFT OUTER JOIN {$CFG->prefix}user u ON r.userid = u.id
                        WHERE d.course = {$data->courseid} AND r.userid > 0";
        $course_context = get_context_instance(CONTEXT_COURSE, $data->courseid);
        $notenrolled = array();
        $fields = array();
        if ($rs = get_recordset_sql($recordssql)) {
            while ($record = rs_fetch_next_record($rs)) {
                if (array_key_exists($record->userid, $notenrolled) or !$record->userexists or $record->userdeleted
                  or !has_capability('moodle/course:view', $course_context , $record->userid)) {
                    delete_records('dataform_ratings', 'recordid', $record->id);
                    delete_records('dataform_comments', 'recordid', $record->id);
                    delete_records('dataform_content', 'recordid', $record->id);
                    delete_records('dataform_records', 'id', $record->id);
                    // HACK: this is ugly - the recordid should be before the fieldid!
                    if (!array_key_exists($record->dataid, $fields)) {
                        if ($fs = get_records('dataform_fields', 'dataid', $record->dataid)) {
                            $fields[$record->dataid] = array_keys($fs);
                        } else {
                            $fields[$record->dataid] = array();
                        }
                    }
                    foreach($fields[$record->dataid] as $fieldid) {
                        fulldelete("$CFG->dataroot/$data->courseid/moddataform/dataform/$record->dataid/$fieldid/$record->id");
                    }
                    $notenrolled[$record->userid] = true;
                }
            }
            rs_close($rs);
            $status[] = array('component'=>$componentstr, 'item'=>get_string('deletenotenrolled', 'dataform'), 'error'=>false);
        }
    }

    // remove all ratings
    if (!empty($data->reset_dataform_ratings)) {
        delete_records_select('dataform_ratings', "recordid IN ($allrecordssql)");
        if (empty($data->reset_gradebook_grades)) {
            // remove all grades from gradebook
            dataform_reset_gradebook($data->courseid);
        }
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallratings'), 'error'=>false);
    }

    // remove all comments
    if (!empty($data->reset_dataform_comments)) {
        delete_records_select('dataform_comments', "recordid IN ($allrecordssql)");
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallcomments'), 'error'=>false);
    }

    // updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('dataform', array('timeavailablefrom', 'timeavailableto', 'timeviewfrom', 'timeviewto'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }
    return $status;
}

/**
 * Returns all other caps used in module
 */
function dataform_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames');
}


?>
