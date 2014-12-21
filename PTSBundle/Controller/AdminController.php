<?php

namespace MSTS\PTSBundle\Controller;

// use Symfony\Component\HttpFoundation\Response;
use MSTS\PTSBundle\Entity\PTSDatalog;
use MSTS\PTSBundle\Entity\PTSUser;
//use MSTS\PTSBundle\Entity\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
}

