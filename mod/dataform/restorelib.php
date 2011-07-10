<?php // $Id
//This php script contains all the stuff to restore dataform mod
//-----------------------------------------------------------

//Backup data files because we've selected to backup user info
//and files are user info's level

//Return a content encoded to support interactivities linking. Every module
function dataform_restore_mods($mod, $restore) {

    global $CFG;
    $status = true;
    
    echo "restore dataform\n";

    $backup = backup_getid($restore->backup_unique_code, $mod->modtype, $mod->id);

    if ($backup) {
        //Now get completed xmlized object
        $info = $backup->info;
        
        // TODO
        // if necessary, write to restorelog and adjust date/time fields
        if ($restore->course_startdateoffset) {
            restore_log_date_changes('Dataform', $restore, $info['MOD']['#']['PRESET']['0']['#']['SETTINGS']['0']['#'], array('TIMEAVAILABLE', 'TIMEDUE'));
        }
        
        $params = new object();
        $params->mode = 'courserestore';
        $params->courseid = $restore->course_id;
        $params->backup_unique_code = $restore->backup_unique_code;
               
        // restore preset
        $modinfo = $info['MOD']['#'];       
        restore_dataform_preset($modinfo, $params);
        
        // restore user data if requested
        if ($params->dataformid and restore_userdata_selected($restore, 'dataform', $mod->id)) {
            restore_dataform_userdata($modinfo, $params);
        } else {
            $status = false;
        }
    } else {
        $status = false;
    }

    return $status;
}

/**
 *
 */
function restore_dataform_preset($modinfo, &$params) {
    if ($modinfo['PRESET'] and $preset = $modinfo['PRESET']['#']) {

        $mode = ($params and isset($params->mode) ? $params->mode : '');
        // $objids[$oldid]=$newid arrays for recording and adjusting interdependencies between object
        $params->fieldids = array();
        $params->viewids = array();
        $params->filterids = array();
    
        // collect settings
        if ($settings = $preset['SETTINGS']['0']['#']) {
            $dataform = new object();
            foreach ($settings as $key => $setting) {
                if ($key != 'ENTRY') {  // we process the enry params below
                    $dataform->{strtolower($key)} = backup_todb($setting['0']['#']);
                }
            }
        
            if ($mode ==  'courserestore') {
                // adjust settings
                $dataform->course = $params->courseid;

                // We have to recode the scale field if it's <0 (positive is a grade, not a scale)
                if ($dataform->scale < 0) {
                    $scale = backup_getid($params->backup_unique_code, 'scale', abs($dataform->scale));
                    if ($scale) {
                        $dataform->scale = -($scale->new_id);
                    }
                }
                    
                $params->sourcedataformid = $dataform->id;
                // create the new dataform
                $params->destdataformid = insert_record('dataform', $dataform);

                // show progress
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<li>".get_string("modulename","dataform")." \"".format_string(stripslashes($dataform->name),true)."\"</li>";
                }

                if ($params->destdataformid) {
                    // update backup_ids
                    backup_putid($params->backup_unique_code,$mod->modtype,$mod->id, $params->destdataformid);
                }

            // activity restore
            } else {
                if (isset($params->destdataformid)) {
                    $dataform->id = $params->destdataformid;
                    update_record('dataform', $dataform);
                } else {    // shouldn't happen
                    // set $params->destdataformid to 0 so as to abort the restore
                    $params->destdataformid = 0;
                }
            }
            
            // dataform entry params
        }

        if ($params->destdataformid) {
            
            // restore fields
            if ($preset['FIELDS']['0']['#']) {
                $fields = $preset['FIELDS']['0']['#']['FIELD'];
                foreach ($fields as $i => $arr) {
                
                    // collect field info
                    $field_info = $arr['#'];
                    foreach ($field_info as $key => $value) {
                        $field->{strtolower($key)} = backup_todb($value['0']['#']);
                    }
                    
                    // adjust field dataid
                    $field->dataid = $params->destdataformid;
                    
                    // insert field to database
                    $fieldoldid = $field->id;
                    if ($fieldnewid = insert_record("dataform_fields", $field)) {

                        // register old => new field id 
                        $params->fieldids[$fieldoldid] = $fieldnewid;
                        
                        if ($mode == 'courserestore') {
                            show_progress($i);
                            // update backup_ids
                            $status = backup_putid($params->backup_unique_code, "dataform_fields", $fieldoldid, $fieldnewid);
                        }
                        
                    } else {
                        // TODO: should we break?
                        $status = false;
                    }
                }                
            }

            // restore filters (before views because views require filter ids)
            if ($preset['FILTERS']['0']['#']) {
                $filters = $preset['FILTERS']['0']['#']['FILTER'];                
                foreach ($filters as $i => $arr) {

                    // collect fitler info                    
                    $filter_info = $arr['#'];
                    foreach ($filter_info as $key => $value) {
                        $filter->{strtolower($key)} = backup_todb($value['0']['#']);
                    }
                        
                    // adjust filter dataid
                    $filter->dataid = $params->destdataformid;

                    // adjust groupby field id
                    if ($filter->groupby > 0) {   // groupby user field
                        $filter->groupby = $params->fieldids[$view->groupby];
                    }
                        
                    // adjust customsort field ids
                    if ($filter->customsort) {
                        $customsort = unserialize($filter->customsort);
                        $updatedcustomsort = array();
                        foreach ($customsort as $sortfield => $sortdir) {
                            if ($sortfield > 0) {
                                $updatedcustomsort[$params->fieldids[$sortfield]] = $sortdir;
                            } else {
                                $updatedcustomsort[$sortfield] = $sortdir;
                            }
                        }
                        $filter->customsort = serialize($updatedcustomsort);
                    }
                        
                    // adjust customsearch field ids
                    if ($filter->customsearch) {
                        $searchfields = unserialize($filter->customsearch);
                        $updatedsearchoptions = array();
                        foreach ($searchfields as $searchfield => $options) {
                            if ($searchfield > 0) {
                                $updatedsearchfields[$params->fieldids[$searchfield]] = $options;
                            } else {
                                $updatedsearchfields[$searchfield] = $options;
                            }
                        }
                        $filter->customsearch = serialize($updatedsearchfields);
                    }

                    // insert filter to database
                    $filteroldid = $filter->id;
                    if ($filternewid = insert_record("dataform_filters", $filter)) {

                        // old id -> new id
                        $filterids[$filteroldid] = $filternewid;
                        
                        if ($mode == 'courserestore') {
                            show_progress($i);
                            // update backup_ids
                            $status = backup_putid($params->backup_unique_code, "dataform_filters", $filteroldid, $filternewid);
                        }
                        
                    } else {
                        // TODO: should we break?
                        $status = false;
                    }
                }               
            }

            // restore views
            if ($preset['VIEWS']['0']['#']) {
                $views = $preset['VIEWS']['0']['#']['VIEW'];                
                foreach ($views as $i => $arr) {

                    // collect view info
                    $view_info = $arr['#'];
                    foreach ($view_info as $key => $value) {
                        $view->{strtolower($key)} = backup_todb($value['0']['#']);
                    }
                        
                    // adjust view dataid
                    $view->dataid = $params->destdataformid;

                    // adjust view groupby field id
                    if ($view->groupby > 0) {   // groupby user field
                        $view->groupby = $params->fieldids[$view->groupby];
                    }
                    // adjust view filter id
                    if ($view->filter) {
                        $view->filter = $filterids[$view->filter];
                    }

                    // insert view to database
                    $viewoldid = $view->id;
                    if ($viewnewid = insert_record("dataform_views", $view)) {

                        // old id -> new id
                        $viewids[$viewoldid] = $viewnewid;

                        if ($mode == 'courserestore') {
                            show_progress($i);
                            // update backup_ids
                            $status = backup_putid($params->backup_unique_code, "dataform_views", $viewoldid, $viewnewid);
                        }
                        
                    } else {
                        // TODO: should we break?
                        $status = false;
                    }
                }                
            }

            // adjust dataform referenced settings
            if ($dataform->defaultview or $dataform->defaultsort
                    or $dataform->singleedit or $dataform->singleview) {
                $updatedf = new object();
                $updatedf->id = $params->destdataformid;
                
                // default view
                if ($dataform->defaultview) {
                    $updatedf->defaultview = $viewids[$dataform->defaultview];
                }
                
                // default sort
                if ($dataform->defaultsort) {
                    $defaultsort = unserialize($dataform->defaultsort);
                    $updateddefaultsort = array();
                    foreach ($defaultsort as $sortfield => $sortdir) {
                        if ($sortfield > 0) {
                            $updateddefaultsort[$params->fieldids[$sortfield]] = $sortdir;
                        } else {
                            $updateddefaultsort[$sortfield] = $sortdir;
                        }
                    }
                    $updatedf->defaultsort = serialize($updateddefaultsort);
                }

                // single edit view
                if ($dataform->singleedit) {
                    $updatedf->singleedit = $viewids[$dataform->singleedit];
                }

                // single view view
                if ($dataform->singleview) {
                    $updatedf->singleview = $viewids[$dataform->singleview];
                }
                
                update_record('dataform', $updatedf);
            }
        }
    }
}

function restore_dataform_userdata($modinfo, &$params) {
    if ($info['USERDATA'] and $userdata = $info['USERDATA']['0']['#']) {

        $mode = ($params and isset($params->mode) ? $params->mode : '');
        $dataformid = ($params and isset($params->dataformid) ? $params->dataformid : 0);
        $params->entrieids = array();
    
        // restore entries
        if ($userdata['ENTRIES']['0']['#']) {
            $entries = $userdata['ENTRIES']['0']['#']['ENTRIE'];
            foreach ($entries as $i => $arr) {
            
                // collect entrie info
                $entrie_info = $arr['#'];
                foreach ($entrie_info as $key => $value) {
                    $entrie->{strtolower($key)} = backup_todb($value['0']['#']);
                }
                
                // adjust entrie dataid
                $entrie->dataid = $dataformid;
                
                if ($mode = 'courserestore') {
                    // adjust entrie user id
                    if ($user = backup_getid($params->backup_unique_code,"user",$entrie->userid)) {
                        $entrie->userid = $user->new_id;
                    }
                    // adjust entrie group id
                    if ($group = restore_group_getid($restore, $entrie->groupid)) {
                        $entrie->groupid= $group->new_id;
                    }
                }

                // insert entrie to database
                $entrieoldid = $entrie->id;
                if ($entrienewid = insert_record("dataform_entries", $entrie)) {

                    // old id -> new id 
                    $entrieids[$entrieoldid] = $entrienewid;

                    if ($mode = 'courserestore') {
                        show_progress($i);
                        // update backup_ids
                        $status = backup_putid($params->backup_unique_code, "dataform_entries", $entrieoldid, $entrienewid);
                    }
                } else {
                    // TODO: should we break?
                    $status = false;
                }
            }                
        }

        // restore contents
        if ($userdata['CONTENTS']['0']['#']) {
            $contents = $userdata['CONTENTS']['0']['#']['CONTENT'];
            foreach ($contents as $i => $arr) {
            
                // collect content info
                $content_info = $arr['#'];
                foreach ($content_info as $key => $value) {
                    $content->{strtolower($key)} = backup_todb($value['0']['#']);
                }
                
                // adjust content fieldid
                $oldfieldid = $content->fieldid;
                $content->fieldid = $params->fieldids[$oldfieldid];
                
                // adjust content recordid
                $oldrecordid = $content->recordid;
                $content->recordid = $entrieids[$oldrecordid];
                
                // insert content to database
                $contentoldid = $content->id;
                if ($contentnewid = insert_record("dataform_contents", $content)) {

                    // update backup_ids
                    $status = $status and backup_putid($params->backup_unique_code, "dataform_contents", $contentoldid, $contentnewid);

                    show_progress($i);

                } else {
                    // TODO: should we break?
                    $status = false;
                }
            }                
        }

        // restore comments
        if ($userdata['COMMENTS']['0']['#']) {
            $comments = $userdata['COMMENTS']['0']['#']['COMMENT'];
            foreach ($comments as $i => $arr) {
            
                // collect comment info
                $comment_info = $arr['#'];
                foreach ($comment_info as $key => $value) {
                    $comment->{strtolower($key)} = backup_todb($value['0']['#']);
                }
                
                // adjust comment user id
                if ($mode = 'courserestore') {
                    if ($user = backup_getid($params->backup_unique_code,"user",$comment->userid)) {
                        $comment->userid = $user->new_id;
                    }
                }
                
                // adjust comment recordid
                $comment->recordid = $entrieids[$comment->recordid];
                
                // insert comment to database
                $commentoldid = $comment->id;
                if ($commentnewid = insert_record("dataform_comments", $comment)) {

                    if ($mode = 'courserestore') {
                        show_progress($i);
                        // update backup_ids
                        $status = backup_putid($params->backup_unique_code, "dataform_comments", $commentoldid, $commentnewid);
                    }
                    
                } else {
                    // TODO: should we break?
                    $status = false;
                }
            }                
        }

        // restore ratings
        if ($userdata['RATINGS']['0']['#']) {
            $ratings = $userdata['RATINGS']['0']['#']['RATING'];
            foreach ($ratings as $i => $arr) {
            
                // collect rating info
                $rating_info = $arr['#'];
                foreach ($rating_info as $key => $value) {
                    $rating->{strtolower($key)} = backup_todb($value['0']['#']);
                }
                
                // adjust rating user id
                if ($mode = 'courserestore') {
                    if ($user = backup_getid($params->backup_unique_code,"user",$rating->userid)) {
                        $rating->userid = $user->new_id;
                    }
                }
                
                // adjust rating recordid
                $rating->recordid = $entrieids[$rating->recordid];
                
                // insert rating to database
                $ratingoldid = $comment->id;
                if ($ratingnewid = insert_record("dataform_ratings", $rating)) {

                    if ($mode = 'courserestore') {
                        show_progress($i);
                        // update backup_ids
                        $status = backup_putid($params->backup_unique_code, "dataform_ratings", $ratingoldid, $ratingnewid);
                    }
                    
                } else {
                    // TODO: should we break?
                    $status = false;
                }
            }                
        }

        // TODO restore files               
        // check if there are files for the backedup dataform
        $temp_path = $CFG->dataroot."/temp/backup/".$params->backup_unique_code."/moddata/dataform/".$dataformoldid;
        if (check_dir_exists($temp_path)) {
            // check course directory in CFG->dataroot
            $dest_dir = $CFG->dataroot. "/". $params->courseid;
            $status = check_dir_exists($dest_dir, true);

            // check course's moddata directory
            if ($status) {
                $moddata_path = $dest_dir. "/". $CFG->moddata;
                $status = check_dir_exists($moddata_path, true);
            }

            // check dataform directory
            if ($status) {
                $dataform_path = $moddata_path."/dataform";
                $status = check_dir_exists($dataform_path, true);
            }

            // check this dataform instance directory
            if ($status) {
                $this_dataform_path = $dataform_path."/".$params->destdataformid;
                $status = check_dir_exists($this_dataform_path, true);
            }

            // get list of directories (names are old field ids)
            if ($status) {
                if ($fielddirs = list_directories($temp_path)) {
                    foreach ($fielddirs as $fielddir) {
                        // create a directory named new field id for the restored dataform
                        $this_field_path = $this_dataform_path."/".$params->fieldids[$fielddir];
                        $status = check_dir_exists($this_field_path, true);
                        
                        // get list of directories (names are old entrie ids)
                        if ($status) {
                            if ($entriedirs = list_directories($temp_path. "/". $fielddir)) {
                                foreach ($entriedirs as $entriedir) {                                
                                    // create a directory named new entrie id for the restored dataform
                                    $this_entrie_path = $this_field_path."/".$entrieids[$entriedir];
                                    $status = check_dir_exists($this_entrie_path, true);
                                    
                                    if ($status) {
                                        $status = @backup_copy_file($temp_path. "/". $fielddir. "/". $entriedir, $this_entrie_path);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}



/**
 * Returns a content decoded to support interactivities linking. Every module
 * should have its own. They are called automatically from
 * xxxx_decode_content_links_caller() function in each module
 * in the restore process.
 *
 * @param string $content the content to be decoded
 * @param object $restore the preferences used in restore
 * @return string the decoded string
 */
function dataform_decode_content_links($content,$restore) {

    global $CFG;

    $result = $content;

/// Link to the list of datas

    $searchstring='/\$@(DATAINDEX)\*([0-9]+)@\$/';
/// We look for it
    preg_match_all($searchstring,$content,$foundset);
/// If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
    /// print_object($foundset);                                     //Debug
    /// Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
        /// We get the needed variables here (course id)
            $rec = backup_getid($restore->backup_unique_code,"course",$old_id);
        /// Personalize the searchstring
            $searchstring='/\$@(DATAINDEX)\*('.$old_id.')@\$/';
        /// If it is a link to this course, update the link to its new location
            if($rec->new_id) {
            /// Now replace it
                $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/dataform/index.php?id='.$rec->new_id,$result);
            } else {
            /// It's a foreign link so leave it as original
                $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/dataform/index.php?id='.$old_id,$result);
            }
        }
    }

/// Link to data view by moduleid

    $searchstring='/\$@(DATAVIEWBYID)\*([0-9]+)@\$/';
/// We look for it
    preg_match_all($searchstring,$result,$foundset);
/// If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
    /// print_object($foundset);                                     //Debug
    /// Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
        /// We get the needed variables here (course_modules id)
            $rec = backup_getid($restore->backup_unique_code,"course_modules",$old_id);
        /// Personalize the searchstring
            $searchstring='/\$@(DATAVIEWBYID)\*('.$old_id.')@\$/';
        /// If it is a link to this course, update the link to its new location
            if($rec->new_id) {
            /// Now replace it
                $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/dataform/view.php?id='.$rec->new_id,$result);
            } else {
            /// It's a foreign link so leave it as original
                $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/dataform/view.php?id='.$old_id,$result);
            }
        }
    }

/// Link to data view by dataid

    $searchstring='/\$@(DATAVIEWBYD)\*([0-9]+)@\$/';
/// We look for it
    preg_match_all($searchstring,$result,$foundset);
/// If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
    /// print_object($foundset);                                     //Debug
    /// Iterate over foundset[2]. They are the old_ids
        foreach($foundset[2] as $old_id) {
        /// We get the needed variables here (data id)
            $rec = backup_getid($restore->backup_unique_code,"dataform",$old_id);
        /// Personalize the searchstring
            $searchstring='/\$@(DATAVIEWBYD)\*('.$old_id.')@\$/';
        /// If it is a link to this course, update the link to its new location
            if($rec->new_id) {
            /// Now replace it
                $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/dataform/view.php?d='.$rec->new_id,$result);
            } else {
            /// It's a foreign link so leave it as original
                $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/dataform/view.php?d='.$old_id,$result);
            }
        }
    }

/// Link to data record (element)

    $searchstring='/\$@(DATAVIEWRECORD)\*([0-9]+)\*([0-9]+)@\$/';
/// We look for it
    preg_match_all($searchstring,$result,$foundset);
/// If found, then we are going to look for its new id (in backup tables)
    if ($foundset[0]) {
    /// print_object($foundset);                                     //Debug
    /// Iterate over foundset[2] and foundset[3]. They are the old_ids
        foreach($foundset[2] as $key => $old_id) {
            $old_id2 = $foundset[3][$key];
        /// We get the needed variables here (data id and record id)
            $rec = backup_getid($restore->backup_unique_code,"dataform",$old_id);
            $rec2 = backup_getid($restore->backup_unique_code,'dataform_entries',$old_id2);
        /// Personalize the searchstring
            $searchstring='/\$@(DATAVIEWRECORD)\*('.$old_id.')\*('.$old_id2.')@\$/';
        /// If it is a link to this course, update the link to its new location
            if($rec->new_id && $rec2->new_id) {
            /// Now replace it
                $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/dataform/view.php?d='.$rec->new_id.'&amp;rid='.$rec2->new_id,$result);
            } else {
            /// It's a foreign link so leave it as original
                $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/dataform/view.php?d='.$old_id.'&amp;rid='.$old_id2,$result);
            }
        }
    }

    return $result;
}

/**
 * This function makes all the necessary calls to xxxx_decode_content_links()
 * function in each module, passing them the desired contents to be decoded
 * from backup format to destination site/course in order to mantain inter-activities
 * working in the backup/restore process. It's called from restore_decode_content_links()
 * function in restore process
 *
 * @param object $restore the preferences used in restore
 * @return boolean status of the execution
 */
function dataform_decode_content_links_caller($restore) {

    global $CFG;
    $status = true;

/// Process every DATA (intro, all HTML templates) in the course
/// Supported fields for main table:
    $supportedfields = array('intro','singletemplate','listtemplate',
        'listtemplateheader','addtemplate','rss','rsstitletemplate');
    if ($datas = get_records_sql ("SELECT d.id, ".implode(',',$supportedfields)."
                                  FROM {$CFG->prefix}data d
                                  WHERE d.course = $restore->course_id")) {
    /// Iterate over each data
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        foreach ($datas as $data) {
        /// Increment counter
            $i++;

        /// Make a new copy of the data object with nothing in, to use if
        /// changes are necessary (allows us to do update_record without
        /// worrying about every single field being included and needing
        /// slashes).
            $newdata = new stdClass;
            $newdata->id=$data->id;

        /// Loop through handling each supported field
            $changed = false;
            foreach($supportedfields as $field) {
                $result = restore_decode_content_links_worker($data->{$field},$restore);
                if ($result != $data->{$field}) {
                    $newdata->{$field} = addslashes($result);
                    $changed = true;
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($data->{$field}).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }
            }

        /// Update record if any field changed
            if($changed) {
                $status = update_record("dataform",$newdata);
            }

        /// Do some output
            if (($i+1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }

/// Process every COMMENT (content) in the course
    if ($comments = get_records_sql ("SELECT dc.id, dc.content
                                      FROM {$CFG->prefix}data d,
                                           {$CFG->prefix}dataform_entries dr,
                                           {$CFG->prefix}dataform_comments dc
                                      WHERE d.course = $restore->course_id
                                        AND dr.dataid = d.id
                                        AND dc.recordid = dr.id")) {
    /// Iterate over each dataform_comments->content
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        foreach ($comments as $comment) {
        /// Increment counter
            $i++;
            $content = $comment->content;
            $result = restore_decode_content_links_worker($content,$restore);
            if ($result != $content) {
            /// Update record
                $comment->content = addslashes($result);
                $status = update_record("dataform_comments",$comment);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                    }
                }
            }
        /// Do some output
            if (($i+1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }

/// Process every CONTENT (content, content1, content2, content3, content4) in the course
    if ($contents = get_records_sql ("SELECT dc.id, dc.content, dc.content1, dc.content2, dc.content3, dc.content4
                                      FROM {$CFG->prefix}data d,
                                           {$CFG->prefix}dataform_entries dr,
                                           {$CFG->prefix}dataform_contents dc
                                      WHERE d.course = $restore->course_id
                                        AND dr.dataid = d.id
                                        AND dc.recordid = dr.id")) {
    /// Iterate over each dataform_contents->content, content1, content2, content3 and content4
        $i = 0;   //Counter to send some output to the browser to avoid timeouts
        foreach ($contents as $cnt) {
        /// Increment counter
            $i++;
            $content = $cnt->content;
            $content1 = $cnt->content1;
            $content2 = $cnt->content2;
            $content3 = $cnt->content3;
            $content4 = $cnt->content4;
            $result = restore_decode_content_links_worker($content,$restore);
            $result1 = restore_decode_content_links_worker($content1,$restore);
            $result2 = restore_decode_content_links_worker($content2,$restore);
            $result3 = restore_decode_content_links_worker($content3,$restore);
            $result4 = restore_decode_content_links_worker($content4,$restore);
            if ($result != $content || $result1 != $content1 || $result2 != $content2 ||
                $result3 != $content3 || $result4 != $content4) {
            /// Unset fields to update only the necessary ones
                unset($cnt->content);
                unset($cnt->content1);
                unset($cnt->content2);
                unset($cnt->content3);
                unset($cnt->content4);
            /// Conditionally set the fields
                if ($result != $content) {
                    $cnt->content = addslashes($result);
                }
                if ($result1 != $content1) {
                    $cnt->content1 = addslashes($result1);
                }
                if ($result2 != $content2) {
                    $cnt->content2 = addslashes($result2);
                }
                if ($result3 != $content3) {
                    $cnt->content3 = addslashes($result3);
                }
                if ($result4 != $content4) {
                    $cnt->content4 = addslashes($result4);
                }
            /// Update record with the changed fields
                $status = update_record("dataform_contents",$cnt);
                if (debugging()) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                    }
                }
            }
        /// Do some output
            if (($i+1) % 5 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 100 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
    }

    return $status;
}

/**
 * 
 */
function show_progress($i) {
    if (($i+1) % 50 == 0) {
        if (!defined('RESTORE_SILENTLY')) {
            echo ".";
            if (($i+1) % 1000 == 0) {
                echo "<br />";
            }
        }
        backup_flush(300);
    }
}

?>