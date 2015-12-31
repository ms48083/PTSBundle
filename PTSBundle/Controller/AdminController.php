<?php

namespace MSTS\PTSBundle\Controller;

// use Symfony\Component\HttpFoundation\Response;
use MSTS\PTSBundle\Entity\PTSDatalog;
use MSTS\PTSBundle\Entity\PTSUser;
//use MSTS\PTSBundle\Entity\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class AdminController extends Controller
{
    public function indexAction(Request $request, $sort, $active)
    {
		// $pagin = new Paginator();
		
		$where = array();
	
		// query for the results
		$eUsers = new PTSDatalog();
		$where['active'] = $active; //'Y'; // array('active' => 'Y');  // active users only
		$where['sort'] = $sort;
		
		$eUserArray = $eUsers->getUserRecords($where);
		// echo var_dump($where);
        // return new Response('<html><body>hello I hope you are having a good day!</body></html>');
		return $this->render('MSTSPTSBundle:Admin:layout.html.twig', 
				array('eUsers' => $eUserArray, 'where' => $where) );
	
    }
    public function edituserAction(Request $request, $slug)
    {
		$where = array();
		$PTSUser = new PTSUser();

		// post or get?
		$dowhat = $request->getMethod();
		// echo var_dump($dowhat);
		if ($dowhat == 'GET')
		{  // first time here, edit this user or new user
			if ($slug > 0)
			{   // look up existing user
				$PTSUser->getUser($slug);
			} else
			{	// add new users
				$slug = 0;
			}
			$form = $this->doFormBuilder($PTSUser, $slug);
			return $this->render('MSTSPTSBundle:Admin:edituser2.html.twig', array('form' => $form->createView(), 'RecNo' => $slug) );
		}
		if ($dowhat == "POST")
		{  // save data to database, or save-new, or cancel
			//echo var_dump("Posting to database");
			// WHY DO I NEED TO DO THIS AGAIN?
			$form = $this->doFormBuilder($PTSUser, 0);
			if ($request->request->get('cancel'))
			{ // go back to admin page
				// add a flash message 
				$this->get('session')->getFlashBag()->add('notice', 'No changes made!');
				return $this->redirect($this->generateUrl('pts_admin'));
			}
			if ($request->request->get('save') || $request->request->get('saveadd'))
			{
				//echo var_dump("Save or SaveAdd");
				// get the new form variables
				$form->bind($request);
				//echo var_dump("Form is bound to request");
				if ($form->isValid())
				{	// $PTSUser is now bound??
					//echo var_dump("Data is Valid");
					if ($PTSUser->saveUser())
					{  // save ok or not ok
					}
					
					$this->get('session')->getFlashBag()->add('notice', 'Changes Saved!');
					// save or save+add
					if ($request->request->get('save')) {
						return $this->redirect($this->generateUrl('pts_admin'));
						//return new Response('<h1>Temporary Save Target</h1>');
					} elseif ($request->request->get('saveadd')) {
						return $this->redirect($this->generateUrl('pts_admin_eu', array('slug' => 0)));
					} else {
						return new Response('<h1>Submit type not detected</h1>');
					}
				} else {
					// form not validated, go back and try again
					//echo var_dump("Data is Not Valid");
					$this->get('session')->getFlashBag()->add('notice', 'Changes Not Saved');
					return $this->render('MSTSPTSBundle:Admin:edituser2.html.twig', array('form' => $form->createView(), 'RecNo' => $PTSUser->getRecNo()) );
				}

				return new Response('<h1>No post type detected</h1>');
			}
			return new Response('<h1>Submit type not detected</h1>');
		}
		return new Response('<h1>No request method detected</h1>');
    }

	private function doFormBuilder($PTSUser, $newUser) {

		return $this->createFormBuilder($PTSUser)
		->add('FName', 'text', array('label' => 'First Name', 'label_attr' => array('class' => 'form_label')))
		->add('LName', 'text', array('label' => 'Last Name', 'label_attr' => array('class' => 'form_label')))
		->add('IDNum', 'text', array('label' => 'Associate Number', 'label_attr' => array('class' => 'form_label')))
		// ->add('CardNum', 'text', array('label' => 'ID Card Number', 'label_attr' => array('class' => 'form_label'), 'read_only' => ($newUser!=0), 'disabled' => ($newUser!=0)))
		->add('CardNum', 'text', array('label' => 'ID Card Number', 'label_attr' => array('class' => 'form_label'), 'read_only' => ($newUser!=0)))
		->add('Active', 'choice', array('choices' => array('Y' => 'Yes', 'N' => 'No'), 'expanded'  => true))
		->add('RecNo', 'hidden')
		->getForm();
	}

    public function autouploadusersAction(Request $request)
    {   // controller to bulk update users from a file - automated process - anonymous from local host only
        // validate this request is coming from the local host
        
        if ($request->server->get('HTTP_HOST') == "localhost"
         && ($request->server->get('REMOTE_ADDR') == "127.0.0.1" || $request->server->get('REMOTE_ADDR') == "::1")
         && $request->getMethod() == "POST")
        {   //$message = "<h1>Request looks good!! Cool</h1><br>".var_dump($request);
            $message = "";
        } else
        {   $message = "Bad Request\n";
            return new Response($message);
            //return new Response(var_dump($request));
        }
        // return new Response($message."<br>".$request->server->get('HTTP_HOST')."<br>".$request->server->get('REMOTE_ADDR')."<br>".$request->getMethod());
        
        // do the upload
        $uuArray = $this->uploadUsers($request);
        $uuResult = $uuArray['message']."\n";        
        foreach ($uuArray['stats'] as $key => $value) {
            $uuResult .= $key.": ".$value."\n"; 
        }
        // also return total number of users 
        $eUsers = new PTSDatalog();
        $qry = ""; // no parameters yet
        $dbcount = $eUsers->countUserRecords($qry);
        $uuResult .= "Total Active: ".$dbcount[0];
        
        // and also return a daily transaction summary
        $transSummary = $eUsers->getTransCount(1);
        $uuResult .= "\n\nLog Summary (last 24 hours)\n";
        // var_dump($transSummary);
        // Record Keys "Status" and "Qty"
        foreach ($transSummary as $record) {
            foreach ($record as $key => $value) {
                if ($key == "Status") { $uuResult .= $eUsers->eventString($value)." ".$eUsers->statusString($value); }
                if ($key == "Qty") { $uuResult .= ": ".$value."\n"; }
            }
        }        
        return new Response($message.$uuResult);
        
    }
    public function uploadusersAction(Request $request)
    {   // controller to bulk update users from a file

		// post or get?
		$dowhat = $request->getMethod();
		// echo var_dump($dowhat);
		if ($dowhat == 'GET')
		{  // first time here, edit this user or new user
			//$form = $this->doUploadFormBuilder($PTSUser);
			//return $this->render('MSTSPTSBundle:Admin:uploadusers.html.twig', array('form' => $form->createView()) );
			return $this->render('MSTSPTSBundle:Admin:uploadusers.html.twig');
		}
		if ($dowhat == "POST")
		{  // upload new users
			if ($request->request->get('upload'))
			{
                //return new Response(var_dump($request));
                $uuArray = $this->uploadUsers($request);
                return $this->render('MSTSPTSBundle:Admin:uploadusers.html.twig', array('message' => $uuArray['message'], 'stats' => $uuArray['stats']));
			}
			return new Response('<h1>Submit type not detected</h1>');
		}
		return new Response('<h1>No request method detected</h1>');
    }

    private function uploadUsers(Request $request)
    {   // does the uploading work from the user-submitted or automated request
		$where = array();
		$PTSUser = new PTSUser();
    
        // initialize counters and message
        $message='Uploading...';
        $stats = array('Processed' => 0, 'Added' => 0, 'Updated' => 0, 'Skipped' => 0, 'Errors' => 0);
        // handle the uploaded file
        $myfile = $request->files->get('myfile');
        // print_r($myfile);
        if (($myfile instanceof UploadedFile) && ($myfile->getError()=='0'))
        {
            if ($myfile->getSize()<50000)
            {   // echo('its good');
                // validate file type extention
                $originalName = $myfile->getClientOriginalName();
                $nameArray=explode('.', $originalName);
                $fileExt=$nameArray[sizeof($nameArray)-1];
                $validExt = array('csv');
                if(in_array(strtolower($fileExt), $validExt))
                {  
                    // time to open the file and process it
                    $message='Successful upload of '.$originalName;
                    // move the file to the uploads directory
                    $locfile = $myfile->move('uploads');
                    $file = $locfile->openFile('r');
                    // setup entity
                    $eUsers = new PTSDatalog();
					// Open database connection to use escape string function
					$mysqli = new \mysqli("localhost", "pts_logger","colombopts", "pts_datalog");
					if ($mysqli->connect_errno) {
						echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
					}
					
                    // parse through the contents
                    // if header row throw away first row
                    if ($request->request->get('header')==TRUE) {$myline = $file->fgetcsv();}
                    // loop through all rows
                    while (!$file->eof()) {
                        $myline = $file->fgetcsv();
                        // must have 4 elements
                        if (count($myline)<4)
                        {  // incomplete line
                           // probably blank at end so just skip it
                        } else
                        {
                            $stats['Processed'] += 1;
                            // FORMAT IS EMP-ID, LAST-NAME, FIRST-NAME, CARD-NUM
                            $usrValues = array('CardNum' => filter_var($myline[3], FILTER_SANITIZE_NUMBER_INT),
                                                'LName' => $mysqli->real_escape_string($myline[1]),
                                                'FName' => $mysqli->real_escape_string($myline[2]),
                                                'IDNum' => $mysqli->real_escape_string($myline[0]),
                                                'Active'=> 'Y');
                            // validate the entries
                            // CardNum in range, is a number
                            // LName, FName, IDNum length are truncated
                            if (($usrValues['CardNum']>150000000000) && ($usrValues['CardNum']<=159999999999))
                            {
                                // check if exists
                                $where['cardnum'] = $usrValues['CardNum'];
                                $eUserArray = $eUsers->getUserRecords($where);   // this gets multi-record set, I only care about record 0
                                if (count($eUserArray)==0)
                                {  // not existing so add
                                    $usrValues['RecNo']=0;
                                    $stats['Added'] += 1;
                                    $eUsers->addUserRecord($usrValues);
                                } else
                                {   // check if identical
                                    if ( ($usrValues['LName']==$eUserArray[0]['LName']) &&
                                         ($usrValues['FName']==$eUserArray[0]['FName']) &&  
                                         ($usrValues['IDNum']==$eUserArray[0]['IDNum']))
                                    {   // identical so skip
                                        $stats['Skipped'] += 1;
                                    } else 
                                    {   // has changed so update
                                        $usrValues['RecNo']=$eUserArray[0]['RecNo'];
                                        $stats['Updated'] += 1;
                                        $eUsers->addUserRecord($usrValues);
                                    }
                                }
                            } else // invalid number
                            {   $stats['Errors'] += 1;
                            }
                        }
                    }
                    // done/closed
					$mysqli->close();
                } else 
                {   $message='Sorry!  Invalid extention type .'.$fileExt.', must be .csv';
                }
                
            } else
            {   $message='Sorry!  File size too large, must be less than 50KB';
            }
            
        } else
        {   $message='Sorry!  Invalid upload or file error';
        }
        
        // 
        // $this->get('session')->getFlashBag()->add('notice', 'Changes Not Saved');
        // return $this->render('MSTSPTSBundle:Admin:uploadusers.html.twig', array('message' => $message, 'stats' => $stats));
        return array('message' => $message, 'stats' => $stats);

    }
	private function doUploadFormBuilder($PTSUser) {

		return $this->createFormBuilder($PTSUser)
        //->add('attachment', 'file')
        ->add('file')
		->getForm();
	}
}


