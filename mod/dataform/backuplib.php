<?php //Id:$

//This php script contains all the stuff to backup dataform mod
//-----------------------------------------------------------

/**
 * Return a content encoded to support interactivities linking. Every module
 */
function dataform_backup_mods($bf,$preferences) {
    global $CFG;

    $status = true;

    // iterate
    if ($datas = get_records('dataform','course',$preferences->backup_course,"id")) {
        foreach ($datas as $data) {
            $backup_mod_selected = backup_mod_selected($preferences, 'dataform', $data->id);

            if ($backup_mod_selected) {
                $status = dataform_backup_one_mod($bf,$preferences,$data);
                // backup files happens in backup_one_mod now too.
            }
        }
    }
    return $status;
}

/**
 *
 */
function dataform_check_backup_mods_instances($instance,$backup_unique_code) {
    $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
    $info[$instance->id.'0'][1] = '';
    if (!empty($instance->userdata)) {
        // any other needed stuff
    }
    return $info;
}

/**
 *
 */
function dataform_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
    if (!empty($instances) && is_array($instances) && count($instances)) {
        $info = array();
        foreach ($instances as $id => $instance) {
            $info += dataform_check_backup_mods_instances($instance,$backup_unique_code);
        }
        return $info;
    }

    // otherwise continue as normal
    //First the course data
    $info[0][0] = get_string("modulenameplural","dataform");
    if ($ids = dataform_ids ($course)) {
        $info[0][1] = count($ids);
    } else {
        $info[0][1] = 0;
    }

    //Now, if requested, the user_data
    if ($user_data) {
        // any other needed stuff
    }
    return $info;

}

/**
 *
 */
function dataform_backup_one_mod($bf,$preferences,$data) {
    global $CFG;

    if (is_numeric($data)) { // backwards compatibility
        $data = get_record('dataform','id',$data);
    }

    $status = true;

    fwrite ($bf,start_tag("MOD",3,true));
    fwrite ($bf,full_tag("MODTYPE",4,false,"dataform"));
    fwrite ($bf,full_tag("ID",4,false,$data->id));

    // print dataform preset
    $status = $status and backup_dataform_preset($bf, $data, $preferences, 4);

    // print dataform userdata
    if (backup_userdata_selected($preferences,'dataform',$data->id)) {
        $status = $status and backup_dataform_userdata($bf, $data, $preferences, 4);
    }
    
    fwrite ($bf,end_tag("MOD",3,true));
    
    // backup files
    $status = $status and backup_dataform_files_instance($bf, $preferences, $data->id);    //recursive copy
    
    return $status;
}




/**
 *
 */
function backup_dataform_preset($bf, $data, $preferences = null, $level = 0){
    $status = true;

    fwrite ($bf,start_tag("PRESET",$level,true));

    // settings
    fwrite ($bf,start_tag("SETTINGS",$level+1,true));
    foreach ($data as $attribute => $value) {
        if ($attribute != 'id') {
            fwrite($bf,full_tag(strtoupper($attribute),$level+2,false,$value));
        }
    }
    fwrite ($bf,end_tag("SETTINGS",$level+1,true));
    // fields
    $status = $status and backup_dataform_elements($bf,$preferences,'field','dataid',array($data->id),$level+1);
    // views
    $status = $status and backup_dataform_elements($bf,$preferences,'view','dataid',array($data->id),$level+1);
    // filters
    $status = $status and backup_dataform_elements($bf,$preferences,'filter','dataid',array($data->id),$level+1);

    fwrite ($bf,end_tag("PRESET",$level,true));
    
    return $status;
}

/**
 *
 */
function backup_dataform_userdata($bf, $data, $preferences = null, $level = 0){
    $status = true;

    fwrite ($bf,start_tag("USERDATA",$level,true));

    // entries
    $status = backup_dataform_elements($bf, $preferences,'entrie','dataid',array($data->id),$level+1);
    
    // TODO
    $entries = get_records('dataform_entries','dataid',$data->id,'','id');
    $entrieids = array_keys($entries);
    //$entrieids = array();
    //foreach ($entries as $eid => $obj) {
    //    $entrieids[$eid] = $eid;
    //}
    
    if ($entrieids) {
        // content
        $status = $status and backup_dataform_elements($bf,$preferences,'content','recordid',$entrieids,$level+1);
        // comments
        $status = $status and backup_dataform_elements($bf,$preferences,'comment','recordid',$entrieids,$level+1);
        // ratings
        $status = $status and backup_dataform_elements($bf,$preferences,'rating','recordid',$entrieids,$level+1);
    }

    fwrite($bf,end_tag("USERDATA",$level,true));
    
    return $status;
}

/**
 *
 */
function backup_dataform_elements($bf, $preferences, $what, $pkname, $pks, $level){
    $status = true;
    
    // start elements tag
    fwrite($bf,start_tag(strtoupper($what).'S',$level,true));

    foreach ($pks as $pk) {
        if ($items = get_records("dataform_{$what}s", $pkname, $pk)) {

            // iterate over each item
            foreach ($items as $item) {
                // start item tag
                fwrite($bf,start_tag(strtoupper($what),$level+1,true));
                
                // write attribute tags
                foreach ($item as $attribute => $value) {
                    fwrite($bf,full_tag(strtoupper($attribute),$level+2,false,$value));
                }

                // end item tag
                fwrite($bf,end_tag(strtoupper($what),$level+1,true));
            }
        }
    }
    
    // end elements tag
    fwrite($bf,end_tag(strtoupper($what).'S',$level,true));

    return $status;
}

/**
 *
 */
function backup_dataform_files($bf, $preferences) {

    global $CFG;
    $status = true;

    //First we check to moddata exists and create it as necessary in temp/backup/$backup_code  dir
    $status = check_and_create_moddata_dir($preferences->backup_unique_code);

    //Now copy the data dir only if it exists !! Thanks to Daniel Miksik.
    if ($status) {
        if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/dataform")) {
            $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/dataform",
                                               $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/dataform");
        }
    }

    return $status;
}

/**
 *
 */
function backup_dataform_files_instance($bf, $preferences,$instanceid) {

    global $CFG;
    $status = true;

    //First we check to moddata exists and create it as necessary in temp/backup/$backup_code  dir
    $status = $status and check_and_create_moddata_dir($preferences->backup_unique_code);
    $status = $status and check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/dataform/",true);

    //Now copy the dataform dir only if it exists !! Thanks to Daniel Miksik.
    if ($status) {
        if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/dataform/".$instanceid)) {
            $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/dataform/".$instanceid,
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/dataform/".$instanceid);
        }
    }
    return $status;
}

/**
 *
 */
function backup_dataform_file_instance($bf, $preferences,$instanceid) {

    global $CFG;
    $status = true;

    //First we check to moddata exists and create it as necessary in temp/backup/$backup_code  dir
    $status = check_and_create_moddata_dir($preferences->backup_unique_code);
    $status = check_dir_exists($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/dataform/",true);

    //Now copy the data dir only if it exists !! Thanks to Daniel Miksik.
    if ($status) {
        if (is_dir($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/dataform/".$instanceid)) {
            $status = backup_copy_file($CFG->dataroot."/".$preferences->backup_course."/".$CFG->moddata."/dataform/".$instanceid,
                                           $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/moddata/dataform/".$instanceid);
        }
    }
    return $status;
}

/**
 * Returns a content encoded to support interactivities linking. Every module
 * should have its own. They are called automatically from the backup procedure.
 *
 * @param string $content content to be encoded
 * @param object $preferences backup preferences in use
 * @return string the content encoded
 */
function dataform_encode_content_links ($content, $preferences) {

    global $CFG;

    $base = preg_quote($CFG->wwwroot,"/");

/// Link to one "record" of the dataform
    $search="/(".$base."\/mod\/dataform\/view.php\?d\=)([0-9]+)\&rid\=([0-9]+)/";
    $result= preg_replace($search,'$@DATAFORMVIEWRECORD*$2*$3@$',$content);

/// Link to the list of dataforms
    $search="/(".$base."\/mod\/dataform\/index.php\?id\=)([0-9]+)/";
    $result= preg_replace($search,'$@DATAFORMINDEX*$2@$',$result);

/// Link to dataform view by moduleid
    $search="/(".$base."\/mod\/dataform\/view.php\?id\=)([0-9]+)/";
    $result= preg_replace($search,'$@DATAFORMVIEWBYID*$2@$',$result);

/// Link to dataform view by dataformid
    $search="/(".$base."\/mod\/dataform\/view.php\?d\=)([0-9]+)/";
    $result= preg_replace($search,'$@DATAFORMVIEWBYD*$2@$',$result);

    return $result;
}

/**
 *
 */
function dataform_ids($course) {
    // stub function, return number of modules
    return 1;
}
