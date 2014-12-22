<?php

namespace MSTS\PTSBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use MSTS\PTSBundle\Entity\PTSDatalog;
use MSTS\PTSBundle\Entity\PTSUser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SystemController extends Controller
{
    public function indexAction(Request $request)
    {
		// put request vars back into response
		// use ->query for get vars, ->request for post vars
		$sysnum = $request->query->get('sysnum', 0);
		$transct = $request->query->get('trct', '0');
		$refresh = $request->query->get('refr', NULL);
		$recno = $request->query->get('rno', 0);
        $gao = $request->query->get('gao', 0);
		//file_put_contents('request_dump.txt', print_r($request, true));
		//echo var_dump($request);
// ENSURE REQUEST IS FROM MAINSTREAM, OTHERWISE SEND 404 RESPONSE

		// check refresh to return user record updates
		$eUsers = new PTSDatalog();
		// $where['active'] = $active; //'Y'; // array('active' => 'Y');  // active users only
		//$where['sort'] = 'la';  // sort by status date
        //$where['sort'] = 'arn';  // sort by Active (N first) then Record Number THIS DOESN'T WORK AS NEEDED
        $where['sort'] = 'rn';  // sort by Record Number only
        $limit = 50;
		$where['numrows'] = $limit+1;  // limit number of records THIS VALUE LINKS WITH tcpServerCheck IN MAINSTREAM V4.23 and above
        $where['recnos'] = $recno;
        if ($gao > 0) $where['active'] = 'ay'; // active users only
        
		// $where['statusdate'] = '2013-10-25 00:00:00';  // at or after date
		$where['statusdate'] =  substr($refresh,0,4) . '-' . substr($refresh,4,2) . '-' . substr($refresh,6,2) . ' ';
		$where['statusdate'] .= substr($refresh,8,2) . ':' . substr($refresh,10,2) . ':' . substr($refresh,12,2);
		
		$eUserArray = $eUsers->getUserRecords($where);
		// file_put_contents('user_dump.txt', print_r($eUserArray, true));
		// use mktime(0, 0, 0, 7, 1, 2000) to create timestamp
		// query database for 1 page of user records since timestamp
		// return those records to the requester
		
		// build up the response string
		$userrecs = "";
		$lastdate = "";
		if (count($eUserArray) > 0) {
            if (count($eUserArray) > $limit) { 
                $more = TRUE;
                $bound = $limit;
            }
            else {
                $more = FALSE;
                $bound = count($eUserArray);
            }
            // echo var_dump($bound);
            $maxStatusDate = $eUserArray[0]['StatusDate'];
			for ($i=0; $i < $bound; $i++){
				if ($eUserArray[$i]['Active'] == 'Y') {
					$userrecs .= '+'; // active
				} else {
					$userrecs .= '-'; // not active
				}
				$userrecs .= $eUserArray[$i]['CardNum'] . ','; // card number
                //echo var_dump($eUserArray[$i]['StatusDate']);
                if ($eUserArray[$i]['StatusDate'] > $maxStatusDate) $maxStatusDate = $eUserArray[$i]['StatusDate'];
			}
			if ($more) $lastdate = date("YmdHis", strtotime($maxStatusDate));
            else $lastdate = date("YmdHis", strtotime($maxStatusDate)+1); // no more at this timestamp so move 1sec past
			$userrecs .= 'e' . $lastdate; // update timestamp of last record
            if ($more) $userrecs .= ',l' . $eUserArray[$bound-1]['RecNo']; // add last record number
            else $userrecs .= ',l0'; // add last record as 0 - no more
		}
		// $userrecs .= gettype($refresh);
		// $userrecs .= ' ' . $where['statusdate'];
        return new Response('<html><body>PTS ' . date("d-m-y H:i:s T") . '</body></html><!--sysnum['. $sysnum . ']refr[' . $refresh . ']users[' . $userrecs . ']time[' . date("YmdHisT") . ']-->');
		
        //return new Response('<html><body>Colombo Pneumatic Tube System ' . date("j-M-Y h:i:s A T") . '</body></html><!--sysnum['. $sysnum . ']refr[' . $refresh . ']users[' . $userrecs . ']-->');
		//return $this->render('MSTSPTSBundle:System:layout.html.twig');
    }
}

