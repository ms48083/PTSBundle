<?php

namespace MSTS\PTSBundle\Controller;

// use Symfony\Component\HttpFoundation\Response;
use MSTS\PTSBundle\Entity\PTSDatalog;
use MSTS\PTSBundle\Entity\Paginator;
use MakerLabs\PagerBundle\Pager;
use MakerLabs\PagerBundle\Adapter\ArrayAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class LogsController extends Controller
{
    public function indexAction(Request $request, $badge)
    {
		
		// use form items to build query AND re-fill form input fields
		$where = array();
		if ($badge > 0)
		{ $where['ReceiverID'] = $badge;
		} else {
		  $badge = $request->request->get('badge');
		  if (strlen($badge))
		  { $where['ReceiverID'] = $badge;
		  }
		}
		$sysnum = $request->request->get('sysnum');
		if (strlen($sysnum))
		{ $where['sysnum'] = intval($sysnum);
		}
		$eventnum = $request->request->get('eventnum');
		if (strlen($eventnum))
		{ $where['eventnum'] = intval($eventnum);
		}
		$datefrom = $request->request->get('datefrom');
		if (strlen($datefrom))
		{ $where['datefrom'] = $datefrom;
		}
		//$datethrough = $request->request->get('datethrough');
		//if (strlen($datethrough))
		//{ $where['datethrough'] = $datethrough;
		//}
		$sysnum = $request->request->get('srcsta');
		if (strlen($sysnum))
		{ $where['srcsta'] = $sysnum;
		}
		$sysnum = $request->request->get('deststa');
		if (strlen($sysnum))
		{ $where['deststa'] = $sysnum;
		}
		$numrows = $request->request->get('numrows');
		if ($numrows > 0)
		{ $where['numrows'] = $numrows;
		} else {
          $where['numrows'] = 100;
        }
		$pagenum = $request->request->get('pagenum');
		if ($pagenum > 0)
		{ $where['pagenum'] = $pagenum;
		}
        if ($request->request->has('nextpage')) 
        {  // apply date to get next set
           $pagekey = $request->request->get('pagekey');
           $where['pagekey'] = $pagekey;
           //echo $pagekey;
        } else {
            // reset page key and page number if not using nextpage button
            unset($where['pagekey']);
            $pagenum = 1;
            $where['pagenum'] = $pagenum;
        }


		// query for the results
		$eLog = new PTSDatalog();
		$eLogArray = $eLog->getAllResults($where);

		//$array = range(1, 100);
		//$adapter = new ArrayAdapter($eLogArray);
		//$pager = new Pager($adapter, array('page' => 5, 'limit' => 15));

        // increment pagenumber if we found more records
        if ($request->request->has('nextpage')) 
        {  if (count($eLogArray) > 0) 
           {  $where['pagenum'] = ++$pagenum;
           }
        }
        
		// echo var_dump($where);
        // return new Response('<html><body>hello '.$name.' I hope you are having a good day!</body></html>');
		//return $this->render('MSTSPTSBundle:Logs:layout.html.twig', array('eLog' => $adapter, 'where' => $where, 'pager' => $pager) );
		return $this->render('MSTSPTSBundle:Logs:layout.html.twig', array('eLog' => $eLogArray, 'where' => $where) );
    }
	private function getLogs()
	{
		// connect to database and return an EventLog array
	}
}

