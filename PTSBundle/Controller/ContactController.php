<?php

namespace MSTS\PTSBundle\Controller;

//use Symfony\Component\HttpFoundation\Response;
//use MSTS\PTSBundle\Entity\PTSDatalog;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ContactController extends Controller
{
    public function indexAction(Request $request)
    {
        //return new Response('<html><body>hello Diags I hope you are having a good day!</body></html>');
		return $this->render('MSTSPTSBundle:Contact:layout.html.twig');
    }
}

