<?php  // $Id$

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
    protected $entries = array();
    
    // built in fields
    protected $builtinfields = array(
            -1   => array('type' => '_entry', 'name' => 'Entry', 'internalname' => ''),
            -2   => array('type' => '_approve', 'name' => 'Approved', 'internalname' => 'approved'),
            // searchable builtin fields
            -3  => array('type' => '_time', 'name' => 'Time created', 'internalname' => 'timecreated'),
            -4  => array('type' => '_time', 'name' => 'Time modified', 'internalname' => 'timemodified'),
            -5  => array('type' => '_user', 'name' => 'Author name', 'internalname' => 'name'),
            -6  => array('type' => '_user', 'name' => 'Author first name', 'internalname' => 'firstname'),
            -7  => array('type' => '_user', 'name' => 'Author last name', 'internalname' => 'lastname'),
            -8  => array('type' => '_user', 'name' => 'Author username', 'internalname' => 'username'),
            -9  => array('type' => '_user', 'name' => 'Author id', 'internalname' => 'id'),
            -10  => array('type' => '_user', 'name' => 'Author id number', 'internalname' => 'idnumber'),
            -11  => array('type' => '_user', 'name' => 'Author picture', 'internalname' => 'picture'),
            -12  => array('type' => '_comment', 'name' => 'Comments', 'internalname' => 'comments'),
            -13  => array('type' => '_rating', 'name' => 'Ratings', 'internalname' => 'ratings')
        );
    
    protected $locks = array(
            'approval'   => 1,
            'comments'   => 2,
            'ratings'   => 4
    );
    
    protected $groupmode = 0;
    protected $currentgroup = 0;    // current group id

    /**
     * constructor
     */
    public function dataform($d = 0, $id = 0, $autologinguest = false) {
        // initialize from dataform id
        if ($d) {
            if (!$this->data = get_record('dataform', 'id', $d)) {
                error('Dataform ID is incorrect');
            }
            if (!$this->course = get_record('course', 'id', $this->data->course)) {
                error('Course is misconfigured');
            }
            if (!$this->cm = get_coursemodule_from_instance('dataform', $this->id(), $this->course->id)) {
                error('Course Module ID was incorrect');
            }
        // initialize from course module id
        } else if ($id) {
            if (!$this->cm = get_coursemodule_from_id('dataform', $id)) {
                error('Course Module ID was incorrect');
            }
            if (!$this->course = get_record('course', 'id', $this->cm->course)) {
                error('Course is misconfigured');
            }
            if (!$this->data = get_record('dataform', 'id', $this->cm->instance)) {
                error('Course module is incorrect');
            }
        }

        // get context
        $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);

        // require login
        require_login($this->course->id, $autologinguest, $this->cm);

        // initialize the builtin fields
        foreach ($this->builtinfields as $fieldid => $fieldspec) {
            $field = new object();
            $field->id = $fieldid;
            $field->dataid = $this->id();
            $field->type = $fieldspec['type'];
            $field->name = $fieldspec['name'];
            $field->internalname = $fieldspec['internalname'];
            $field->description = '';
            $this->builtinfields[$fieldid] = $this->get_field($field);
        }
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
    public function locks($type) {
        if (array_key_exists($type, $this->locks)) {
            return $this->locks[$type];
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function get_ready_to_browse() {
        global $CFG;

        // Define some default values for browsing
        // If we have an empty Dataform then redirect because this page is useless without data
        if (has_capability('mod/dataform:managetemplates', $this->context)) {
            // If no fields we need to add some fields
            if (!$this->get_fields()) {
            //if (!record_exists('dataform_fields','dataid',$this->id())) {
                redirect($CFG->wwwroot.'/mod/dataform/fields.php?d='. $this->id());
            }
            // Add some views or set a default view
            if (!$this->get_views() or empty($this->data->defaultview)) {
            //if (!record_exists('dataform_views','dataid',$this->id())) {
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

/**********************************************************************************
 * FIELDS
 *********************************************************************************/

    /**
     * given a field id return the field object from $this->fields
     * Initializes $this->fields if necessary
     */
    public function get_field_from_id($fieldid) {
        if (!$this->fields) {
            $this->get_fields();
        }
        if ($this->fields) {
            return $this->fields[$fieldid];
        } else {
            return false;
        }
    }

    /**
     * returns a subclass field object given a record of the field
     * used to invoke plugin methods
     * input: $param $field record from db, or field type
     */
    public function get_field($key) {
        global $CFG;

        if ($key) {
            if (is_object($key)) {
                $type = $key->type;
            } else {
                $type = $key;
                $key = 0;
            }
            require_once('field/'. $type. '/field_class.php');
            $fieldclass = 'dataform_field_'. $type;
            $field = new $fieldclass($key, $this);
            return $field;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function get_fields($exclude = null, $menu = false, $forceget = false) {
        if (!$this->fields or $forceget) {
            if ($fields = get_records('dataform_fields','dataid', $this->id())) {
                $this->fields = array();
                // collate user fields
                foreach ($fields as $fieldid => $field) {
                    $this->fields[$fieldid] = $this->get_field($field);
                }

                // collate builtinfields only if there are user fields
                if ($this->fields) {
                    $this->fields = $this->fields + $this->builtinfields;
                }
            }
        }

        if ($this->fields) {
            if (empty($exclude) and !$menu) {
                return $this->fields;
            } else {
                $fields = array();
                foreach ($this->fields as $fieldid => $field) {
                    if (!empty($exclude) and in_array($fieldid, $exclude)) {
                        continue;
                    }
                    if ($menu) {
                        $fields[$fieldid]= $field->name();
                    } else {
                        $fields[$fieldid]= $field;
                    }
                }
                return $fields;
            }
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function get_builtin_fields($exclude = null, $menu = false) {
        if (empty($exclude) and !$menu) {
            return $this->builtinfields;
        } else {
            $fields = array();
            foreach ($this->builtinfields as $fieldid => $field) {
                if (!empty($exclude) and in_array($fieldid, $exclude)) {
                    continue;
                }
                if ($menu) {
                    $fields[$fieldid]= $field->name();
                } else {
                    $fields[$fieldid]= $field;
                }
            }
            return $fields;
        }
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

/**********************************************************************************
 * VIEWS
 *********************************************************************************/

    /**
     * given a template id
     * this function creates an instance of the particular subtemplate class   *
     */
    public function get_view_from_id($viewid) {
        // get view class
        if ($views = $this->get_views()) {
            if (!$viewid or !isset($views[$viewid])) {
                if (!$this->data->defaultview) {
                    if (has_capability('mod/dataform:managetemplates', $this->context)) {
                        redirect($CFG->wwwroot.'/mod/dataform/views.php?d='.$this->id());
                    } else {
                        // TODO: notify something
                        return false;
                    }
                } else {
                    $viewid = $this->data->defaultview;
                }
            }
            return $views[$viewid];
        }
        return false;
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
            require_once($CFG->dirroot. '/mod/dataform/view/'. $type. '/view_class.php');
            $viewclass = 'dataform_view_'. $type;
            $view = new $viewclass($vt, $this);
            return $view;
        }
    }

    /**
     *
     */
    public function get_views($exclude = null, $menu = false, $forceget = false) {
        if (!$this->views or $forceget) {
            if ($views = get_records('dataform_views','dataid', $this->id())) {
                $this->views = array();
                // collate user views
                foreach ($views as $viewid => $view) {
                    $this->views[$viewid] = $this->get_view($view);
                }
            }
        }

        if ($this->views) {
            if (empty($exclude) and !$menu) {
                return $this->views;
            } else {
                $views = array();
                foreach ($this->views as $viewid => $view) {
                    if (!empty($exclude) and in_array($viewid, $exclude)) {
                        continue;
                    }
                    if ($menu) {
                        $views[$viewid]= $view->view->name;
                    } else {
                        $views[$viewid]= $view;
                    }
                }
                return $views;
            }
        } else {
            return false;
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
     * Search for a field name and replaces it with another one in all the *
     * form templates. Set $newfieldname as '' if you want to delete the   *
     * field from the form.
     */
    public function replace_field_in_views($searchfieldname, $newfieldname) {
        foreach ($this->get_views() as $view) {
            $view->replace_field_in_view($searchfieldname, $newfieldname);
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
                            if ($filterid != $view->filter) {
                                $updateview->id = $view->id;
                                $updateview->filter = $filterid;
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

/**********************************************************************************
 * FILTERS
 *********************************************************************************/

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
            // TODO check that from this dataform
            $filter = get_record('dataform_filters', 'id', $filterid);
        }

        return $filter;
    }

    /**
     *
     */
    public function get_filter_from_form($formdata) {
        $filter = new object();
        $filter->id = $formdata->fid;
        $filter->dataid = $this->id();
        $filter->name = $formdata->name;
        $filter->description = $formdata->description;
        $filter->perpage = $formdata->perpage;
        $filter->groupby = $formdata->groupby;
        $filter->search = isset($formdata->search) ? $formdata->search : '';
        $filter->customsort = $this->get_sort_options_from_form($formdata);
        $filter->returntoform = false;
        $filter->customsearch = $this->get_search_options_from_form($formdata, $filter->returntoform);

        return $filter;
    }

    /**
     *
     */
    public function process_filters($action, $fids, $confirm = 0) {
        global $CFG;

        $filters = array();
        // TODO may need new roles
        if (has_capability('mod/dataform:manageentries', $this->context)) {
            // don't need record from database for filter form submission
            if ($fids) { // some filters are specified for action
                $managefids = explode(',',$fids);
                foreach ($managefids as $fid) {
                    if ($filter = $this->get_filter_from_id($fid)) {
                        $filters[] = $filter;
                    }
                }
            } else if ($action == 'update') {
                $filters[] = $this->get_filter_from_id();
            }
        }

        $processedfids = array();
        $strnotify = '';

        // TODO update should be roled
        if (empty($filters)) {
            notify(get_string('nofiltersto'. $action, 'dataform'), 'notifyfailure');
        } else {
            if ($confirm) {
                switch ($action) {
                    case 'update':     // add new or update existing
                        $filter = $filters[0];
                        require_once($CFG->dirroot. '/mod/dataform/filter_form.php');
                        $mform = new mod_dataform_filter_form($filter, $this);

                        if ($mform->is_cancelled()){
                            // clean up  customsearch if needed
                            if ($filter->id and $filter->customsearch) {
                                $needupdate = false;
                                $searchfields = unserialize($filter->customsearch);
                                foreach ($searchfields as $fieldid => $searchfield) {
                                    if ($searchfield) { // there are some andor options
                                        foreach ($searchfield as $andorskey => $andors) {
                                            foreach ($andors as $optionkey => $option) {
                                                list(, , $value) = $option;
                                                if (!$value) {
                                                    $needupdate = true;
                                                    unset($andors[$optionkey]);
                                                }
                                            }
                                            // if all options removed, remove this andors
                                            if (!$andors) {
                                                unset($searchfield[$andorskey]);
                                            }
                                        }
                                        // if all andors removed, remove this searchfield
                                        if (!$searchfield) {
                                            unset($searchfields[$fieldid]);
                                        }
                                    } else {
                                        unset($searchfields[$fieldid]);
                                    }
                                }
                                if ($needupdate) {
                                    $updatefilter = new object();
                                    $updatefilter->id = $filter->id;
                                    if ($searchfields) {
                                        $updatefilter->customsearch = serialize($searchfields);
                                    } else {
                                        $updatefilter->customsearch = '';
                                    }
                                    update_record('dataform_filters', $updatefilter);
                                }
                            }

                        // process validated
                        } else if ($formdata = $mform->get_data()) {
                            $filter = $this->get_filter_from_form($formdata);
                            if ($filter->id) {
                                update_record('dataform_filters', $filter);
                                $processedfids[] = $filter->id;
                                $strnotify = 'filtersupdated';
                            } else {
                                $filter->id = insert_record('dataform_filters', $filter, true);
                                $processedfids[] = $filter->id;
                                $strnotify = 'filtersadded';
                            }
                            // return to form if need to add search criteria
                            if ($filter->returntoform) {
                                $this->display_filter_form($filter);
                                print_footer($this->course);
                                die;
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
                notice_yesno(get_string('filtersconfirm'. $action, 'dataform', count(explode(',', $fids))),
                        'filters.php?d='.$this->id().'&amp;'. $action. '='.$fids.'&amp;confirm=1&amp;sesskey='.sesskey(),
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
    public function display_filter_form($filter) {
        global $CFG;

        require_once($CFG->dirroot. '/mod/dataform/filter_form.php');
        $mform = new mod_dataform_filter_form($filter, $this);

        //$mform->data_preprocessing($filter);
        $mform->set_data($filter);
        $mform->display();
    }

    /**
     *
     */
    function get_sort_options_from_form($formdata) {
        $sortfields = array();
        $i = 0;
        while (isset($formdata->{"sortfield$i"})) {
            if ($sortfieldid = $formdata->{"sortfield$i"}) {
                $sortfields[$sortfieldid] = $formdata->{"sortdir$i"};
            }
            $i++;
        }
        // TODO should we add the groupby field to the customsort now?
        if ($sortfields) {
            return serialize($sortfields);
        } else {
            return '';
        }
    }

    /**
     *
     */
    function get_search_options_from_form($formdata, &$returntoform) {
        if ($fields = $this->get_fields()) {
            $searchfields = array();
            $i = 0;
            while (isset($formdata->{"searchandor$i"})) {
                // check if trying to define a search criterion
                if ($searchandor = $formdata->{"searchandor$i"}) {
                    if ($searchfieldid = $formdata->{"searchfield$i"}) {
                        if (!isset($searchfields[$searchfieldid])) {
                            $searchfields[$searchfieldid] = array();
                        }
                        if (!isset($searchfields[$searchfieldid][$searchandor])) {
                            $searchfields[$searchfieldid][$searchandor] = array();
                        }
                        $parsedvalue = $fields[$searchfieldid]->parse_search($formdata, $i);
                        if ($parsedvalue === false) {
                            $returntoform = true; // the search criteria fields need to be added
                        }
                        
                        $not = isset($formdata->{"searchnot$i"}) ? 'NOT' : '';
                        $operator = isset($formdata->{"searchoperator$i"}) ? $formdata->{"searchoperator$i"} : '';
                        $searchvalue = array($not, $operator, $parsedvalue);

                        $searchfields[$searchfieldid][$searchandor][] = $searchvalue;
                    }
                }
                $i++;
            }
        }

        if ($searchfields) {
            return serialize($searchfields);
        } else {
            return '';
        }
    }

/**********************************************************************************
 * ENTRIES
 *********************************************************************************/

    /**
     *
     */
    public function get_entries(&$filter = null) {
        global $CFG, $USER;

        $fields = $this->get_fields();

        // get sort and search settings
        $perpage = isset($filter->perpage) ? $filter->perpage : 0;
        $groupby = isset($filter->groupby) ? $filter->groupby : 0;
        $customsort = isset($filter->customsort) ? trim($filter->customsort) : '';
        $customsearch = isset($filter->customsearch) ? trim($filter->customsearch) : '';
        $simplesearch = isset($filter->search) ? trim($filter->search) : '';

        // get other options
        $ignorerequireentries = isset($filter->ignorerequireentries) ? $filter->ignorerequireentries : 0;
        $ignorerequireapproval = isset($filter->ignorerequireapproval) ? $filter->ignorerequireapproval : 0;

        // construct the sql
        $ilike = sql_ilike(); //Be case-insensitive


        // SORT settings
        $sortwhere = array();
        $sortwhat = array();
        //$sortcount = '';
        $orderby = array("r.timecreated ASC");

        $sortfields = array();
        if ($customsort) {
            $sortfields = unserialize($customsort);

            $orderby = array();
            foreach ($sortfields as $fieldid => $sortdir) {
                $field = $fields[$fieldid];
                if ($fieldid > 0) {
                    $sortcontent = sql_compare_text('c'. $fieldid. '.'. $field->get_sort_field());
                    $sortcontentfull = $field->get_sort_sql($sortcontent);
                } else {
                    $sortcontentfull = $field->get_sort_sql();
                }

                $orderby[] = $sortcontentfull. ' '. ($sortdir ? 'DESC' : 'ASC');
                if ($fieldid > 0) {
                    $sortwhere[] = $fieldid;
                    $sortwhat[] = $sortcontentfull;
                    //$sortcount .= ($sortcount ? ', ' : ''). 'c'. $fieldid. '.recordid';
                }
            }
        }

        // SEARCH settings
        $searchtables = array();
        $searchwhere = array();

        if ($customsearch) {
            $searchfields = unserialize($customsearch);

            $whereand = array();
            $whereor = array();
            foreach($searchfields as $fieldid => $searchfield) {
                // if we got this far there must be some actual search values
                if ($fieldid > 0) { // the following is only for user fields
                    // add only tables which where not already added for sorting
                    if (empty($sortwhere) or !in_array($fieldid, $sortwhere)) {
                        $searchtables[] = $fieldid;
                    }
                }

                $field = $fields[$fieldid];

                // add AND search clauses
                if (!empty($searchfield['AND'])) {
                    //$strwhere .= ' AND '. implode(' AND ', array_map("$field->get_search_sql", $searchfield['AND'])). ' ';
                    foreach ($searchfield['AND'] as $option) {
                        $whereand[] = $field->get_search_sql($option);
                    }
                }

                // add OR search clause
                if (!empty($searchfield['OR'])) {
                    //$strwhere .= ' AND ('. implode(' OR ', array_map("$field->get_search_sql", $searchfield['OR'])). ') ';
                    foreach ($searchfield['OR'] as $option) {
                        $whereor[] = $field->get_search_sql($option);
                    }
                }
            }

            if ($searchtables) {
                $fieldsearch = array();
                foreach ($searchtables as $fieldid) {
                    $fieldsearch[] = ' c'. $fieldid. '.recordid = r.id AND c'. $fieldid. '.fieldid = '. $fieldid. ' ';
                }            
                $searchwhere[] = implode(' AND ', $fieldsearch);
            }
            if ($whereand) {
                $searchwhere[] = implode(' AND ', $whereand);
            }
            if ($whereor) {
                $searchwhere[] = '('. implode(' OR ', $whereor). ')';
            }
        } else if ($simplesearch) {
            $searchtables[] = 's';
            $searchwhere[] = 'cs.recordid = r.id'.
                            ' AND (cs.content '. $ilike. ' \'%'. $simplesearch. '%\' OR u.firstname '. $ilike. ' \'%'. $simplesearch. '%\' OR u.lastname '. $ilike. ' \'%'. $simplesearch. '%\' ) ';
        }
        
        
        $what = ' DISTINCT r.id, r.approved, r.timecreated, r.timemodified, r.userid '.
                    ', u.idnumber, u.firstname, u.lastname, u.username, u.picture '.
                    ($sortwhat ? ', '. implode(', ', $sortwhat) : '');
        $count = ' COUNT(DISTINCT c.recordid) ';
        $tables =   $CFG->prefix.'dataform_entries r '.
                    ', '. $CFG->prefix.'dataform_contents c '.
                    ', '. $CFG->prefix.'user u '.
                    ($sortwhere ? ', '. $CFG->prefix. 'dataform_contents c'. implode(', '. $CFG->prefix.'dataform_contents c', $sorttables) : '');
        $searchtables = $searchtables ? ', '. $CFG->prefix. 'dataform_contents c'. implode(', '. $CFG->prefix.'dataform_contents c', $searchtables) : '';
        $where =  'WHERE  c.recordid = r.id '.
                    ' AND r.dataid = '.$this->id().
                    ' AND r.userid = u.id ';

        if ($sortwhere) {
            foreach ($sortwhere as $fieldid) {
                $where .= ' AND c'. $fieldid. '.recordid = r.id AND c'. $fieldid. '.fieldid = '. $fieldid;
            }
        }

        $searchwhere = $searchwhere ? ' AND '. implode(' AND ', $searchwhere) : '';
        $sortorder = ' ORDER BY '. implode(', ', $orderby). ' ';
        
        // USER filtering
        $whereuser = '';
        if (!$ignorerequireentries and $this->user_requires_entries(true)) {
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

        // To actually fetch the records
        $fromsql    = "FROM $tables $searchtables $where $whereuser $wheregroup $whereapprove $searchwhere";
        $sqlselect  = "SELECT $what $fromsql $sortorder";
        $sqlmax     = "SELECT $count FROM $tables $where $whereuser $wheregroup $whereapprove"; // number of all records user may see
        $sqlcount   = "SELECT $count $fromsql";   // Total number of records when searching

        //echo $fromsql;

        if (empty($searchwhere)) {
            $maxcount = $searchcount = count_records_sql($sqlmax);
        } else {
            if ($maxcount = count_records_sql($sqlmax)) {
                $searchcount = count_records_sql($sqlcount);
            } else {
                $searchcount = 0;
            }
        }

        $page = isset($filter->page) ? $filter->page : 0;
        $entries = new object();
        $entries->max = $maxcount;
        $entries->found = $searchcount;
        $entries->entries = null;

        if ($searchcount) {
            // if a specific entry requested (rid)
            if (isset($filter->rid) and $filter->rid) {
                $thisrid = " AND r.id = $filter->rid ";
                $sqlselect = "SELECT $what, ($sqlcount AND r.id <= $filter->rid $sortorder) AS ridposition $fromsql $thisrid $sortorder";
                if ($this->entries = get_records_sql($sqlselect)) {
                    // there should be only one
                    $filter->page = (current($this->entries)->ridposition - 1);
                }

            // get subset perpage
            } else if ($perpage) {
                $this->entries = get_records_sql($sqlselect, $page * $perpage, $perpage);

            // get everything
            } else {
                $this->entries = get_records_sql($sqlselect);
            }

            $entries->entries = $this->entries;
        }
        return $entries;
    }

    /**
     *
     */
    public function update_entry($entry, $attributes = null) {
        global $USER;

        // update existing entry
        if ($entry) {        
            if ($this->user_can_manage_entry($entry)) { // just in case the user opens two forms at the same time
                if (isset($attributes)) { // update from import
                    if (isset($attributes['Author id'])) $entry->userid = $attributes['Author id'];
                    if (isset($attributes['Group id'])) $entry->groupid = $attributes['Group id'];
                    if (isset($attributes['Time created'])) $entry->timecreated = $attributes['Time created'];
                    if (isset($attributes['Time modified'])) $entry->timemodified = $attributes['Time modified'];
                    if (isset($attributes['Approved'])) $entry->approved = $attributes['Approved'];
                } else {
                    // reset approved flag after student edit
                    if (!has_capability('mod/dataform:approve', $this->context)) {
                        $entry->approved = 0;
                    }
                    $entry->timemodified = time();
                }
                return update_record('dataform_entries',$entry);
            }

        // add new entry
        } else {
            if ($this->user_can_manage_entry()) { // just in case the user opens two forms at the same time
                $entry = new object();
                $entry->userid = isset($attributes['Author id']) ? $attributes['Author id'] : $USER->id;
                $entry->dataid = $this->id();
                $entry->groupid = isset($attributes['Group id']) ? $attributes['Group id'] : $this->currentgroup;
                $entry->timecreated = isset($attributes['Time created']) ? $attributes['Time created'] : time();
                $entry->timemodified = isset($attributes['Time modified']) ? $attributes['Time modified'] : time();
                $entry->approved = isset($attributes['Approved']) ? $attributes['Approved'] : 0;
                $entryid = insert_record('dataform_entries', $entry);
                return $entryid;
            }
        }

        return false;
    }

    /**
     *
     */
    public function process_entries($action, $rids, $confirm = 0) {
        global $CFG, $USER;

        $entries = array();
        if ($rids) { // some entries are specified for action
            $managerids = explode(',',$rids);
            foreach ($managerids as $rid) {
                if ($entrie = get_record('dataform_entries', 'id', $rid)) {
                    // Must be from this dataform and owned by current user or user can manage entries
                    if ($entrie->dataid == $this->id()) {
                        if (($action == 'approve' or $action == 'disapprove') and has_capability('mod/dataform:approve', $this->context)) {
                            $entries[] = $entrie;
                        } else {
                            if ($this->user_is_entry_owner($entrie->userid)
                                    or has_capability('mod/dataform:manageentries', $this->context)) {
                                $entries[] = $entrie;
                            }
                        }
                    }
                }
            }
        }

        $processedrids = array();
        $strnotify = '';

        if (empty($entries) and $action != 'add') {
            notify(get_string('noentriesto'. $action,'dataform'), 'notifyfailure');
        } else {
            if ($confirm) {
                switch ($action) {
                    // add a new entry from form
                    case 'add':
                        if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/view.php')) {
                            $fields = $this->get_fields();

                            //Empty form checking
                            $emptyform = true;      // assume the worst

                            if (has_capability('mod/dataform:manageentries',$this->context)) {
                                $emptyform = false; // allow teacher to add empty entries
                            } else {
                                foreach ($forminput as $name => $value) {
                                    if (strpos($name, 'field_') !== false) {   // assuming only field names contain field_
                                        $namearr = explode('_', $name);  // Second one is the field id
                                        if (array_key_exists($namearr[1], $fields)) {
                                            if ($fields[$namearr[1]]->notemptyfield($value, $name)) {
                                                $emptyform = false; // if anything has content, this form is not empty, so stop now!
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            // not a teacher cannot add an empty form
                            if ($emptyform) {
                                notify(get_string('emptyaddform','dataform'));
                            } else {
                                // add an entry record
                                if ($entryid = $this->update_entry(0)) {
                                    $processedrids[] = $entryid;
                                    //for each field in the add form, add it to the dataform_contents.
                                    $calculations = array();
                                    foreach ($forminput as $name => $value){
                                        if (strpos($name, 'field_') !== false) {   // assuming only field names contain field_
                                            $namearr = explode('_', $name);
                                            $fieldid = $namearr[1]; // Second one is the field id
                                            if (array_key_exists($fieldid, $fields)) {
                                                if ($fields[$fieldid]->field->type == 'calculated') {
                                                    $calculations[$fieldid] = $fields[$fieldid];
                                                } else {
                                                    $fields[$fieldid]->update_content($entryid, $value, $name);
                                                }
                                            }
                                        }
                                    }
                                    // TODO currently does not support calculations on calculated fields
                                    foreach ($calculations as $calculated) {
                                        $value = $calculated->calculate_content($entryid, $forminput);
                                        $calculated->update_content($entryid, $value);
                                    }
                                }
                                // TODO: if paging, set the page to where the newly added entrie will appear according to the sorting criteria
                            }
                        }

                        $strnotify = 'entriesadded';
                        break;

                    // TODO:
                    case 'update':
                        if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/view.php')) {
                            foreach ($entries as $entrie) {
                                if ($this->update_entry($entrie)) {
                                    /// Update all content
                                    $fields = $this->get_fields();
                                    $calculations = array();
                                    foreach ($forminput as $name => $value){
                                        if (strpos($name, 'field_') !== false) {   // assuming only field names contain field_
                                            $namearr = explode('_', $name);
                                            if ($namearr[2] == $entrie->id) {  // Third one is the entrie id
                                                $fieldid = $namearr[1]; // Second one is the field id
                                                if (array_key_exists($fieldid, $fields)) {
                                                    if ($fields[$fieldid]->field->type == 'calculated') {
                                                        $calculations[$fieldid] = $fields[$fieldid];
                                                    } else {
                                                        $fields[$fieldid]->update_content($entrie->id, $value, $name);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    // TODO currently does not support calculations on calculated fields
                                    foreach ($calculations as $calculated) {
                                        $value = $calculated->calculate_content($entrie->id, $forminput);
                                        $calculated->update_content($entrie->id, $value);
                                    }
                                    $processedrids[] = $entrie->id;
                                }
                            }
                        }

                        $strnotify = 'entriesupdated';
                        break;

                    case 'duplicate':
                        foreach ($entries as $entrie) {
                            // can user add anymore entries?
                            if (!$this->user_can_manage_entry()) {
                                // TODO: notify something
                                break;
                            }

                            // Get content of entrie to duplicat
                            $contents = get_records('dataform_contents', 'recordid', $entrie->id);

                            // Add a duplicated entrie and content
                            $newrec = $entrie;
                            $newrec->userid = $USER->id;
                            $newrec->dataid = $this->id();
                            $newrec->groupid = $this->currentgroup;
                            $newrec->timecreated = $newrec->timemodified = time();

                            if ($this->data->approval and !has_capability('mod/dataform:approve', $this->context)) {
                                $newrec->approved = 0;
                            }
                            $entrieid = insert_record('dataform_entries',$newrec);

                            foreach ($contents as $content) {
                                $newcontent = $content;
                                $newcontent->recordid = $entrieid;
                                if (!insert_record('dataform_contents', $newcontent)) {
                                    print_error('cannotinsertrecord', '', '', $entrieid);
                                }
                            }
                            $processedrids[] = $entrieid;
                        }

                        $strnotify = 'entriesduplicated';
                        break;

                    case 'approve':
                        $newentrie = new object();
                        $newentrie->approved = 1;
                        foreach ($entries as $entrie) {
                            if (!$entrie->approved and has_capability('mod/dataform:approve', $this->context)) {
                                $newentrie->id = $entrie->id;
                                update_record('dataform_entries', $newentrie);
                                $processedrids[] = $entrie->id;
                            }
                        }

                        $strnotify = 'entriesapproved';
                        break;

                    case 'disapprove':
                        $newentrie = new object();
                        $newentrie->approved = 0;
                        foreach ($entries as $entrie) {
                            if ($entrie->approved and has_capability('mod/dataform:approve', $this->context)) {
                                $newentrie->id = $entrie->id;
                                update_record('dataform_entries', $newentrie);
                                $processedrids[] = $entrie->id;
                            }
                        }

                        $strnotify = 'entriesdisapproved';
                        break;

                    case 'delete':
                        foreach ($entries as $entrie) {
                            if (!$this->user_can_manage_entry($entrie)) {
                                // TODO: notify something
                                continue;
                            }

                            if ($contents = get_records('dataform_contents','recordid', $entrie->id)) {
                                foreach ($contents as $content) {  // Delete files or whatever else this field allows
                                    if ($field = $this->get_field_from_id($content->fieldid)) { // Might not be there
                                        $field->delete_content($content->recordid);
                                    }
                                }
                            }
                            delete_records('dataform_contents','recordid', $entrie->id);
                            delete_records('dataform_entries','id', $entrie->id);
                            $processedrids[] = $entrie->id;
                        }

                        $strnotify = 'entriesdeleted';
                        break;

                    default:
                        break;
                }

                add_to_log($this->course->id, 'dataform', 'entrie '. $action, 'view.php?id='. $this->cm->id, $this->id(), $this->cm->id);
                if ($strnotify) {
                    $entriesprocessed = $processedrids ? count($processedrids) : 'No';
                    notify(get_string($strnotify, 'dataform', $entriesprocessed), 'notifysuccess');
                }
                return $processedrids;
            } else {
                // Print a confirmation page
                notice_yesno(get_string('entriesconfirm'. $action, 'dataform', count(explode(',', $rids))),
                        'view.php?d='.$this->id().'&amp;'. $action. '='.$rids.'&amp;confirm=1&amp;sesskey='.sesskey(),
                        'view.php?d='.$this->id());

                print_footer($this->course);
                exit;
            }
        }
    }

/**********************************************************************************
 * USER
 *********************************************************************************/

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
            if ($this->data->entriesrequired > 0 and $numentries < $this->data->entriesrequired) {
                $entriesleft = $this->data->entriesrequired - $numentries;
                if ($notify) {
                    notify(get_string('entrieslefttoadd', 'dataform', $entriesleft));
                }
            }

            // Check the number of entries required before to view other participant's entries against the number of entries already made (doesn't apply to teachers)
            if ($this->data->entriestoview > 0 and $numentries < $this->data->entriestoview) {
                $entrieslefttoview = $this->data->entriestoview - $numentries;
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
    public function user_can_manage_entry($entry = 0) {
        global $USER;

        // teachers can always manage entries
        if (has_capability('mod/dataform:manageentries',$this->context)) {
            return true;
        // for others, it depends ...
        } else if (has_capability('mod/dataform:writeentry', $this->context)) {
            $timeavailable = $this->data->timeavailable;
            $timedue = $this->data->timedue;
            $allowlate = $this->data->allowlate;
            $now = time();

            // activity time frame
            if ($timeavailable and !($now >= $timeavailable)
                    or ($timedue and (!($now < $timedue) or !$allowlate))) {
                return false;
            }

            // group access
            if ($this->groupmode
                        and !(has_capability('moodle/site:accessallgroups', $this->context)
                        and (($this->currentgroup and !groups_is_member($this->currentgroup))
                                or (!$this->currentgroup and $this->groupmode == VISIBLEGROUPS)))) {
                return false;   // for members only
            }

            // managing a certain entry
            if ($entry) {
                // entry owner
                if (empty($USER->id) or $USER->id != $entry->userid) {
                    return false;   // who are you anyway???
                }

                // ok owner, what's the time (limit)?
                if ($timelimitsec = ($this->data->timelimit * 60)) {
                    $elapsed = $now - $entry->timecreated;
                    if ($elapsed > $timelimitsec) {
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

                // same interval but the entrie may be locked ...
                if ($locks = $this->data->locks) {
                    if (($locks & $this->locks['approval']) and $entry->approved) {
                        return false;
                    }
                    if (($locks & $this->locks['comments']) and count_records('dataform_comments', 'recordid', $entry->id)) {
                        return false;
                    }
                    if (($locks & $this->locks['ratings']) and count_records('dataform_ratings', 'recordid', $entry->id)) {
                        return false;
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

        $sql = 'SELECT COUNT(*) FROM '. $CFG->prefix. 'dataform_entries '.
                ' WHERE dataid = '. $this->id(). ' AND userid = '. $USER->id. $andwhereinterval;
        return count_records_sql($sql);
    }


/**********************************************************************************
 * PRESETS
 *********************************************************************************/

    /**
     * Returns an array of the course local presets from the course files
     */
    public function get_local_presets() {
        global $CFG, $USER;
        $presets = array();

        $presetspath = $this->course->id. '/moddata/dataform/presets/';
        if (is_dir($CFG->dataroot. '/'. $presetspath)) { // may not exist yet
            if ($handle = opendir($CFG->dataroot. '/'. $presetspath)) {
                while (false !== ($presetfile = readdir($handle))) {
                    if ($presetfile != "." && $presetfile != "..") {
                        $preset = new object;
                        //$preset->path = $presetspath. $presetfile;
                        //$preset->userid = 0;
                        $preset->name = str_replace('.zip', '', $presetfile);
                        $preset->screenshot = '';
                        //foreach (array('jpg', 'png', 'gif') as $imagetype) {
                        //    if (file_exists($fulldir.'/screenshot.'. $imagetype)) {
                        //        $preset->screenshot = $fulldir.'/screenshot.'. $imagetype;
                        //        break;
                        //    }
                        //}
                        $presets[] = $preset;
                    }
                }
                closedir($handle);
            }
        }

        return $presets;
    }

    /**
     * Returns an array of the shared presets (in moodledata) the user is allowed to access
     */
    public function get_shared_presets() {
        global $CFG, $USER;
        $presets = array();
        $presetspath = 'dataform/presets/';
        if (is_dir($CFG->dataroot. '/'. $presetspath)) { // may not exist yet
            if ($presetdirs = scandir($CFG->dataroot. '/'. $presetspath)) {
                foreach ($presetdirs as $presetdir) {
                    $fulldir = $presetspath. $presetdir;
                    if ($presetdir == '.' or $presetdir == '..' or !is_dir($CFG->dataroot. '/'. $fulldir)) {
                        continue;
                    }
                    if ($presetdir == 0 or $presetdir == $USER->id or has_capability('mod/dataform:viewalluserpresets', $this->context)) {
                        if ($handle = opendir($CFG->dataroot. '/'. $fulldir)) {
                            while (false !== ($presetfile = readdir($handle))) {
                                if ($presetfile != "." && $presetfile != "..") {
                                    $preset = new object;
                                    //$preset->path = $presetspath. $presetdir. '/'. $presetfile;
                                    $preset->userid = $presetdir;
                                    //$preset->shortname = $dir;
                                    $preset->name = str_replace('.zip', '', $presetfile);
                                    $preset->screenshot = '';
                                    foreach (array('jpg', 'png', 'gif') as $imagetype) {
                                        if (file_exists($fulldir.'/screenshot.'. $imagetype)) {
                                            $preset->screenshot = $fulldir.'/screenshot.'. $imagetype;
                                            break;
                                        }
                                    }
                                    $presets[] = $preset;
                                }
                            }
                            closedir($handle);
                        }
                    }
                }
            }
        }
        return $presets;
    }

    /**
     *
     */
    public function apply_preset($presetpath, $fieldmapping = false) {
        global $CFG;

        require_once($CFG->libdir.'/uploadlib.php');
        require_once($CFG->libdir.'/xmlize.php');
        require_once('restorelib.php');

        // unzip the preset
        make_upload_directory($presetpath);
        unzip_file("$CFG->dataroot/$presetpath.zip", "$CFG->dataroot/$presetpath", false);

        // get content of preset file and delete the unzipped dir
        $presetxml = file_get_contents("$CFG->dataroot/$presetpath/preset.xml");
        unlink("$CFG->dataroot/$presetpath/preset.xml");
        rmdir("$CFG->dataroot/$presetpath");

        // try to apply the prese
        if ($parsedxml = xmlize($presetxml)) {
            // get current user fields
            $currentfields = $this->get_fields();
            // delete records of current fields views and filters
            delete_records('dataform_fields','dataid',$this->id());
            delete_records('dataform_views','dataid',$this->id());
            delete_records('dataform_filters','dataid',$this->id());

            // restore preset from array
            $params = new object();
            $params->courseid = $this->course->id;
            $params->destdataformid = $this->id();
            restore_dataform_preset($parsedxml, $params);

            // at this stage new fields, views, filters should be created
            // old fields if any and there content should still exit for mapping

            if (!empty($currentfields)) {
                // get new fields
                $newfields = $this->get_fields(null, false, true);
                // mapping preferences form
                if ($fieldmapping and !empty($newfields)) {
                    $strblank = get_string('blank', 'dataform');
                    $strcontinue = get_string('continue');
                    $strwarning = get_string('mappingwarning', 'dataform');
                    $strfieldmappings = get_string('fieldmappings', 'dataform');
                    $strnew = get_string('new');

                    echo '<div style="text-align:center"><form action="presets.php?d='. $this->id(). '" method="post">';
                    echo '<fieldset class="invisiblefieldset">';
                    echo '<input type="hidden" name="map" value="1" />';
                    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

                    echo "<h3>$strfieldmappings ";
                    //echo helpbutton('fieldmappings', '', 'dataform');
                    echo '</h3><table cellpadding="5">';

                    foreach ($currentfields as $cid => $currentfield) {
                        if ($cid > 0) {
                            echo '<tr><td><label for="id_'.$currentfield->name().'">'.$currentfield->name().'</label></td>';
                            echo '<td><select name="field_'.$cid.'_'.$currentfield->type().'" id="id_'.$currentfield->name().'">';

                            $selected = false;
                            foreach ($newfields as $nid => $newfield) {
                                if ($nid > 0) {
                                    if ($newfield->type() == $currentfield->type()) {
                                        if ($newfield->name() == $currentfield->name()) {
                                            echo '<option value="'.$nid.'" selected="selected">'.$newfield->name().'</option>';
                                            $selected=true;
                                        }
                                        else {
                                            echo '<option value="'.$nid.'">'.$newfield->name().'</option>';
                                        }
                                    }
                                }
                            }

                            if ($selected)
                                echo '<option value="-1">-</option>';
                            else
                                echo '<option value="-1" selected="selected">-</option>';
                            echo '</select></td></tr>';
                        }
                    }
                    echo '</table>';
                    echo "<p>$strwarning</p>";

                    echo '<input type="submit" value="'.$strcontinue.'" /></fieldset></form></div>';
                } else {
                    foreach ($currentfields as $field) {
                        $field->delete_content();
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function share_preset($presetfile) {
        global $CFG, $USER;

        $presetspath = "dataform/presets/$USER->id";
        if (!is_dir("$CFG->dataroot/$presetspath")) { // may not exist yet
            make_upload_directory($presetspath);
        }

        copy("$CFG->dataroot/$presetfile.zip", "$CFG->dataroot/$presetspath/".  basename($presetfile). '.zip');
    }

    /**
     *
     */
    public function delete_preset($presetfile) {
        global $CFG;

        file_exists("$CFG->dataroot/$presetfile.zip") and unlink("$CFG->dataroot/$presetfile.zip");
    }

/**********************************************************************************
 * BACKUP / RESTORE
 *********************************************************************************/

    /**
     *
     */
    public function backup($type = 'complete') {
        global $CFG;

        $filelist = array("$type.xml");

        if ($type == 'preset') {
            $destdir = 'presets';
        } else {
            $destdir = 'backups';
            //$filelist[] = 'userfiles.zip';
        }

        $filename = clean_filename($this->data->name) . '-'. $type. '-' . gmdate("Ymd_Hi");
        $filepath = $this->course->id. '/moddata/dataform/'. $destdir;
        $backuptempdir = $filepath. '/'. $filename;
        make_upload_directory($backuptempdir);
        $backuptempdir = "$CFG->dataroot/$backuptempdir";

        // assemble backup xml file
        require_once('backuplib.php');
        $xmlfile = fopen("$backuptempdir/$type.xml", 'w');
        fwrite ($xmlfile, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");

        if ($type == 'preset') {
            backup_dataform_preset($xmlfile, $this->data);
        } else if ($type == 'userdata') {
            backup_dataform_userdata($xmlfile, $this->data);
        } else if ($type == 'complete') {
            fwrite ($xmlfile,start_tag("DATAFORM",0,true));
            backup_dataform_preset($xmlfile, $this->data, null, 1);
            backup_dataform_userdata($xmlfile, $this->data, null, 1);
            fwrite ($xmlfile,end_tag("DATAFORM",0,true));
        }

        fclose($xmlfile);

        // TODO assemble user files


        foreach ($filelist as $key => $file) {
            $filelist[$key] = $backuptempdir . '/' . $filelist[$key];
        }

        $backupfile = "$CFG->dataroot/$filepath/$filename.zip";
        file_exists($backupfile) and unlink($backupfile);
        $status = zip_files($filelist, $backupfile);
        // ToDo: status check
        foreach ($filelist as $file) {
            @unlink($file);
        }
        rmdir($backuptempdir);

        // Return the full path to the exported preset file:
        return $backupfile;
    }

    /**
     *
     */
    public function restore($type, $filepath) {
        global $CFG;

        require_once($CFG->libdir.'/uploadlib.php');
        require_once($CFG->libdir.'/xmlize.php');
        require_once('restorelib.php');
        
        $filelist = array("$type.xml");

        if ($type == 'preset') {
            $destdir = 'presets';
        } else {
            $destdir = 'backups';
            //$filelist[] = 'userfiles.zip';
        }

        

        // unzip the preset
        make_upload_directory($presetpath);
        unzip_file("$CFG->dataroot/$presetpath.zip", "$CFG->dataroot/$presetpath", false);

        // get content of preset file and delete the unzipped dir
        $presetxml = file_get_contents("$CFG->dataroot/$presetpath/preset.xml");
        unlink("$CFG->dataroot/$presetpath/preset.xml");
        rmdir("$CFG->dataroot/$presetpath");

        // try to apply the prese
        if ($parsedxml = xmlize($presetxml)) {
            // get current user fields
            $currentfields = $this->get_fields();
            // delete records of current fields views and filters
            delete_records('dataform_fields','dataid',$this->id());
            delete_records('dataform_views','dataid',$this->id());
            delete_records('dataform_filters','dataid',$this->id());

            // restore preset from array
            $params = new object();
            $params->courseid = $this->course->id;
            $params->destdataformid = $this->id();
            restore_dataform_preset($parsedxml, $params);

            // at this stage new fields, views, filters should be created
            // old fields if any and there content should still exit for mapping

            if (!empty($currentfields)) {
                // get new fields
                $newfields = $this->get_fields(null, false, true);
                // mapping preferences form
                if ($fieldmapping and !empty($newfields)) {
                    $strblank = get_string('blank', 'dataform');
                    $strcontinue = get_string('continue');
                    $strwarning = get_string('mappingwarning', 'dataform');
                    $strfieldmappings = get_string('fieldmappings', 'dataform');
                    $strnew = get_string('new');

                    echo '<div style="text-align:center"><form action="presets.php?d='. $this->id(). '" method="post">';
                    echo '<fieldset class="invisiblefieldset">';
                    echo '<input type="hidden" name="map" value="1" />';
                    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

                    echo "<h3>$strfieldmappings ";
                    //echo helpbutton('fieldmappings', '', 'dataform');
                    echo '</h3><table cellpadding="5">';

                    foreach ($currentfields as $cid => $currentfield) {
                        if ($cid > 0) {
                            echo '<tr><td><label for="id_'.$currentfield->name().'">'.$currentfield->name().'</label></td>';
                            echo '<td><select name="field_'.$cid.'_'.$currentfield->type().'" id="id_'.$currentfield->name().'">';

                            $selected = false;
                            foreach ($newfields as $nid => $newfield) {
                                if ($nid > 0) {
                                    if ($newfield->type() == $currentfield->type()) {
                                        if ($newfield->name() == $currentfield->name()) {
                                            echo '<option value="'.$nid.'" selected="selected">'.$newfield->name().'</option>';
                                            $selected=true;
                                        }
                                        else {
                                            echo '<option value="'.$nid.'">'.$newfield->name().'</option>';
                                        }
                                    }
                                }
                            }

                            if ($selected)
                                echo '<option value="-1">-</option>';
                            else
                                echo '<option value="-1" selected="selected">-</option>';
                            echo '</select></td></tr>';
                        }
                    }
                    echo '</table>';
                    echo "<p>$strwarning</p>";

                    echo '<input type="submit" value="'.$strcontinue.'" /></fieldset></form></div>';
                } else {
                    foreach ($currentfields as $field) {
                        $field->delete_content();
                    }
                }
            }
        }
        global $CFG;

        $filelist = array("$type.xml");

        if ($type == 'preset') {
            $destdir = 'presets';
        } else {
            $destdir = 'backups';
            //$filelist[] = 'userfiles.zip';
        }

        $filename = clean_filename($this->data->name) . '-'. $type. '-' . gmdate("Ymd_Hi");
        $filepath = $this->course->id. '/moddata/dataform/'. $destdir;
        $backuptempdir = $filepath. '/'. $filename;
        make_upload_directory($backuptempdir);
        $backuptempdir = "$CFG->dataroot/$backuptempdir";

        // assemble backup xml file
        require_once('backuplib.php');
        $xmlfile = fopen("$backuptempdir/$type.xml", 'w');
        fwrite ($xmlfile, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");

        if ($type == 'preset') {
            backup_dataform_preset($xmlfile, $this->data);
        } else if ($type == 'userdata') {
            backup_dataform_userdata($xmlfile, $this->data);
        } else if ($type == 'complete') {
            fwrite ($xmlfile,start_tag("DATAFORM",0,true));
            backup_dataform_preset($xmlfile, $this->data, null, 1);
            backup_dataform_userdata($xmlfile, $this->data, null, 1);
            fwrite ($xmlfile,end_tag("DATAFORM",0,true));
        }

        fclose($xmlfile);

        // assemble user files


        foreach ($filelist as $key => $file) {
            $filelist[$key] = $backuptempdir . '/' . $filelist[$key];
        }

        $backupfile = "$CFG->dataroot/$filepath/$filename.zip";
        file_exists($backupfile) and unlink($backupfile);
        $status = zip_files($filelist, $backupfile);
        // ToDo: status check
        foreach ($filelist as $file) {
            unlink($file);
        }
        rmdir($backuptempdir);

        // Return the full path to the exported preset file:
        return $backupfile;
    }

/**********************************************************************************
 * COMMENTS
 *********************************************************************************/

    /**
     *
     */
    public function add_comment() {
        global $CFG, $USER;

        //if ($forminput = data_submitted($CFG->wwwroot.'/mod/dataform/view.php')) {
        if ($content = optional_param('newcomment', '', PARAM_NOTAGS) and $recordid = optional_param('rid', 0, PARAM_INT)) {
            $newcomment = new object();
            $newcomment->userid   = $USER->id;
            $newcomment->created  = time();
            $newcomment->modified = time();
            $newcomment->content  = $content;
            $newcomment->recordid = $recordid;
            insert_record('dataform_comments',$newcomment);
        }
    }

    /**
     *
     */
    public function delete_comment($cid) {
        delete_records('dataform_comments','id',$cid);
    }

/**********************************************************************************
 * UTILITY
 *********************************************************************************/

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

}


/**********************************************************************************
 * backup and restore utility functions
 *********************************************************************************/

    /**
     * Return the xml start tag
     */
    function start_tag($tag,$level=0,$endline=false,$attributes=null) {
        if ($endline) {
           $endchar = "\n";
        } else {
           $endchar = "";
        }
        $attrstring = '';
        if (!empty($attributes) && is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $attrstring .= " ".xml_tag_safe_content($key)."=\"".
                    xml_tag_safe_content($value)."\"";
            }
        }
        return str_repeat(" ",$level*2)."<".strtoupper($tag).$attrstring.">".$endchar;
    }

    /**
     * Return the xml end tag
     */
    function end_tag($tag,$level=0,$endline=true) {
        if ($endline) {
           $endchar = "\n";
        } else {
           $endchar = "";
        }
        return str_repeat(" ",$level*2)."</".strtoupper($tag).">".$endchar;
    }

    /**
     * Return the start tag, the contents and the end tag
     */
    function full_tag($tag,$level=0,$endline=true,$content,$attributes=null) {

        global $CFG;
        //Here we encode absolute links
        // MDL-10770
        if (is_null($content)) {
            $content = '$@NULL@$';
        //} else {
        //    $content = backup_encode_absolute_links($content);
        }
        $st = start_tag($tag,$level,$endline,$attributes);

        $co = xml_tag_safe_content($content);

        $et = end_tag($tag,0,true);

        return $st.$co.$et;
    }

    /**
     *
     */
    function xml_tag_safe_content($content) {
        global $CFG;
        //If enabled, we strip all the control chars (\x0-\x1f) from the text but tabs (\x9),
        //newlines (\xa) and returns (\xd). The delete control char (\x7f) is also included.
        //because they are forbiden in XML 1.0 specs. The expression below seems to be
        //UTF-8 safe too because it simply ignores the rest of characters.
        $content = preg_replace("/[\x-\x8\xb-\xc\xe-\x1f\x7f]/is","",$content);
        $content = preg_replace("/\r\n|\r/", "\n", htmlspecialchars($content));
        return $content;
    }

    /**
     *
     */
    function backup_todb ($data, $addslashes=true) {
        // MDL-10770
        if ($data === '$@NULL@$') {
            return null;
        } else {
            if ($addslashes) {
                $data = addslashes($data);
            }
            return $data;
        }
    }
?>