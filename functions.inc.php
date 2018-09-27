<?php /* $Id */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

function queuemetrics_hookGet_config($engine) {
	global $ext;
	global $db;

	switch($engine) {
		case "asterisk":

			$ivr_logging = queuemetrics_get_details('ivr_logging');
			if ($ivr_logging['value'] == 'true') {

				//get all ivrs
				$ivrlist = ivr_get_details(); 
				if(is_array($ivrlist)) {

					foreach($ivrlist as $item) {
						//splice into ivr to set the ivr selection var and append if already defined
						$context = 'ivr-'.$item['id'];

						//get ivr selection
						$ivrentrieslist = ivr_get_entries($item['id']);
						if (is_array($ivrentrieslist)) {

							foreach($ivrentrieslist as $selection) {
								//splice into ivr selection
								$ext->splice($context, $selection['selection'], 'ivrsel-'.$selection['selection'], new ext_setvar("IVRSELECTION", '${EXTEN}|${IVR_CONTEXT}|'.$item['name']));
								$ext->splice($context, $selection['selection'], 'ivrsel-'.$selection['selection'], new ext_queuelog('NONE','${UNIQUEID}','NONE','INFO', 'IVRAPPEND|${IVRSELECTION}'));
							}
							$ext->splice($context, 'i','final', new ext_setvar("IVRSELECTION", 'i|${IVR_CONTEXT}|'.$item['name']));
							$ext->splice($context, 'i','final', new ext_queuelog('NONE','${UNIQUEID}','NONE','INFO', 'IVRAPPEND|${IVRSELECTION}'));
							$ext->splice($context, 't','final', new ext_setvar("IVRSELECTION", 't|${IVR_CONTEXT}|'.$item['name']));
							$ext->splice($context, 't','final', new ext_queuelog('NONE','${UNIQUEID}','NONE','INFO', 'IVRAPPEND|${IVRSELECTION}'));

						}
					}
				}
			}
		break;
	}
}

function queuemetrics_configpageinit($pagename) {
        global $currentcomponent;

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
	
        if($pagename == 'queuemetrics'){
                $currentcomponent->addprocessfunc('queuemetrics_configprocess');

    		return true;
        }
}

//process received arguments
function queuemetrics_configprocess(){
        if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'queuemetrics'){
                
		//get variables 
		$get_var = array('ivr_logging');
		
		foreach($get_var as $var){
                        $vars[$var] = isset($_REQUEST[$var])    ? $_REQUEST[$var]               : '';
                }

                $action = isset($_REQUEST['action'])    ? $_REQUEST['action']   : '';

                switch ($action) {
                        case 'save':
				queuemetrics_put_details($vars);
                                needreload();
                                redirect_standard_continue();
                        break;
                }
        }
}

function queuemetrics_put_details($options) {
	global $db;
	
	foreach ($options as $key => $item) {
		$data[] = array($key, $item); 
	}

	$sql = $db->prepare('REPLACE INTO queuemetrics_options (`keyword`, `value`) VALUES (?, ?)');
        $ret = $db->executeMultiple($sql, $data);
	
	if($db->IsError($ret)) {
        	die_freepbx($ret->getDebugInfo()."\n".$ret->getUserInfo()."\n".$db->last_query);
        }
	return TRUE;
}

function queuemetrics_get_details($keyword = '') {
	global $db;

        $sql = "SELECT * FROM queuemetrics_options";

	if (!empty($keyword)) {
		$sql .= " WHERE `keyword` = '" . $keyword . "'";        
	}

	$res = $db->getAll($sql, DB_FETCHMODE_ASSOC);
        if($db->IsError($res)) {
                die_freepbx($res->getDebugInfo());
        }

        return (isset($keyword) && $keyword != '') ? $res[0] : $res;	
}
?>
