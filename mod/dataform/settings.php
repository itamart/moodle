<?php  //$Id$

// enable rss feeds
if (empty($CFG->enablerssfeeds)) {
    $options = array(0 => get_string('rssglobaldisabled', 'admin'));
    $str = get_string('configenablerssfeeds', 'dataform').'<br />'.get_string('configenablerssfeedsdisabled2', 'admin');

} else {
    $options = array(0=>get_string('no'), 1=>get_string('yes'));
    $str = get_string('configenablerssfeeds', 'dataform');
}
$settings->add(new admin_setting_configselect('dataform_enablerssfeeds', get_string('enablerssfeeds', 'admin'),
                   $str, 0, $options));
                   
// max views
$options = array();
for ($i=1; $i<20; $i++) {
    $options[$i] = $i;
}
$settings->add(new admin_setting_configselect('dataform_maxviews', get_string('maxviews', 'dataform'),
                   get_string('configmaxviews', 'dataform'), 5, $options));
                   

?>
