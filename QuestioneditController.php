<?php

namespace Testeditor\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Testeditor\Model\Questions;         
use Testeditor\Form\QuestionsForm;   
use Zend\Db\Sql\Select;
use Zend\Validator\Db\NoRecordExists;
use Zend\Session\Container;
use Zend\I18n\Translator\Translator;
use Zend\Db\Sql\Expression;
use DateTime;
use SimpleXMLElement;
use DOMDocument;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use SplFileInfo;
//use Zend\Mvc\I18n\Translator;


/**
 * QuestioneditController
 *
 * @author Piotr Buchta
 *
 * @version 1.1
 *
 */
class QuestioneditController extends AbstractActionController {
	/**
	 * The default action - show the home page
	 */
	protected $testeditTable;
				
	public function indexAction()
	{  
		$request = $this->getRequest();
		if ($request->isPost()) {
		    //***************************************************************************************************
		    $request_button = $request->getPost('return');	 
		    if ($request_button != NULL) {	
    			$container = new Container('testeditor');
    			$order_by = $container->order_by;
    			$order = $container->order;
    			$page = $container->page;
    			$il_wierszy = $container->il_wierszy;
    
    			return $this->redirect()->toRoute('testeditor', array(
    					'action' => 'index','order_by' => $order_by,'order'=>$order,'page'=>$page,'il-wierszy'=>$il_wierszy
			    ));
		    }
		    //$request_button = $request->getPost('deletemark');
		    //if ($request_button != NULL) {
		        //***************************************************************************************************
		        //$container = new Container('questionedit');
		        //$order_by = $container->order_by;
		        //$order = $container->order;
		        //$page = $container->page;
		        //$il_wierszy = $container->il_wierszy;
		        //$id_testu = $container->numer_testu;
		        //***************************************************************************************************
		        //$this->gettesteditTable()->deleteQuestionsMark($id_testu);
		        //return $this->redirect()->toRoute('questions', array(
		            //'action' => 'index','id' => '1','numer_testu' => $id_testu,'order_by' => $order_by,'order'=>$order,'page'=>$page,'il-wierszy'=>$il_wierszy
		        //));
		    //}
		    //***************************************************************************************************
		    $request_button = $request->getPost('exportcsv');
		    $id_testu = (int)$request->getPost('numer_testu');
		    if ($request_button != NULL) {
		       $this->export_questions($id_testu);
		    }
		    //***************************************************************************************************
		    $dir = $request->getPost('dir');
		    if ($dir != NULL) {
		        $root = 'C:\Program Files (x86)\Zend\ZendServer\data\apps\http\__default__\0\Probamoduloww\1.0.0_55\public';
		        //throw new \Zend\File\Transfer\Exception\InvalidArgumentException(var_dump($dir));
		        if( file_exists($root . $dir) ) {
		            $files = scandir($root . $dir);
		            natcasesort($files);
		            if( count($files) > 2 ) { /* The 2 accounts for . and .. */
		                $content = "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
		                // All dirs
		                foreach( $files as $file ) {
		                    if( file_exists($root . $dir . $file) && $file != '.' && $file != '..' && is_dir($root . $dir . $file) ) {
		                        $content .= "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($dir . $file) . "/\">" . htmlentities($file) . "</a></li>";
		                    }
		                }
		                // All files
		                foreach( $files as $file ) {
		                    if( file_exists($root . $dir . $file) && $file != '.' && $file != '..' && !is_dir($root . $dir . $file) ) {
		                        $ext = preg_replace('/^.*\./', '', $file);
		                        $content .= "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($dir . $file) . "\">" . htmlentities($file) . "</a></li>";
		                    }
		                }
		               
		                $content .= "</ul>";
		                $response = $this->getResponse();
		                $response->setContent($content);
		                return $response;
		            }
		        }
		    }  
		}
		//*******************************************************************************************************
		$id_testu = (int) $this->params()->fromRoute('numer_testu', 0);  
		//$id_testu = (int) $this->params()->fromQuery('numer_testu', 0);
		if (!$id_testu) {
		    //throw new \Zend\File\Transfer\Exception\InvalidArgumentException(var_dump($id_testu));
			//***************************************************************************************************
			$container = new Container('testeditor');
			$order_by = $container->order_by;
			$order = $container->order;
			$page = $container->page;
			$il_wierszy = $container->il_wierszy;
			//***************************************************************************************************
			return $this->redirect()->toRoute('testeditor', array(
					'action' => 'index','order_by' => $order_by,'order'=>$order,'page'=>$page,'il-wierszy'=>$il_wierszy
			));
		}
		$nazwa_testu = $this->gettesteditTable()->getTest($id_testu)->nazwa_testu;
		$select = new Select('pytania_testowe');
		$select->where(array('numer_testu'=>$id_testu));
		$order_by = $this->params()->fromRoute('order_by') ?
		$this->params()->fromRoute('order_by') : 'nr_pyt';
		$order = $this->params()->fromRoute('order') ?
		$this->params()->fromRoute('order') : Select::ORDER_ASCENDING;
		$page = $this->params()->fromRoute('page') ? (int) $this->params()->fromRoute('page') : 1;
        //*******************************************************************************************************
			// grab  paginator from the tableQuestionsGateway
			$paginator = $this->gettesteditTable()->fetchAllQuestions($id_testu, true, $select->order($order_by . ' ' . $order));
			// set the current page to what has been passed in query string, or to 1 if none set
			$paginator-> setCurrentPageNumber($page);
			$paginator->setItemCountPerPage((int) $this->params()->fromQuery('il_wierszy', 10));
			//***************************************************************************************************
			$container = new Container('questionedit');
			$container->order_by = $order_by;
			$container->order = $order;
			$container->page = $page;
			$container->il_wierszy = $paginator->getItemCountPerPage();
			$container->numer_testu = $id_testu;
			//***************************************************************************************************
			$this->layout('layout/testeditor');
			$this->setstylevariable();
			$viewModel = new ViewModel(array(
					'paginator' => $paginator, 
					'page' => $page,
					'order_by' => $order_by,
					'order' => $order,
					'numer_testu' => $id_testu,
					'nazwa_testu' => $nazwa_testu,
			));
		return $viewModel;
	}
	
    public function addAction()
    {  
    	 $form = new QuestionsForm();
         $form->get('submit')->setValue('Dodaj pytanie');
         //$numer_testu = $this->params()->fromRoute('numer_testu');
         $numer_testu = $this->params()->fromQuery('numer_testu');
         $form->get('numer_testu')->setValue((int)$numer_testu);
         //****************************************************************************************************
         $container = new Container('questionedit');
         $order_by = $container->order_by;
         $order = $container->order;
         $page = $container->page;
         $il_wierszy = $container->il_wierszy;
         //$numer_testu = $container->numer_testu;
         //****************************************************************************************************
         $request = $this->getRequest();
         if ($request->isPost()) {
         	 //************************************************************************************************
         	 $request_button = $request->getPost('submit');
         	 if ($request_button != NULL){
	             $testedit = new Questions();
	             //**************adding validator to numer_pyt*************************************************
	             $numer_testu = (int) $this->params()->fromQuery('numer_testu', 0);
	             $select = new Select();
	             $select->from('pytania_testowe')
	             ->where->equalTo('nr_pyt', $request->getPost('nr_pyt'))
				 ->where->equalTo('numer_testu', $numer_testu);
	             $validator = new NoRecordExists($select);		
	             $validator->setAdapter(\Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter());
	             $testedit->getInputFilter()->get('nr_pyt')->getValidatorChain()->attach($validator);
	             //********************************************************************************************
	             $form->setInputFilter($testedit->getInputFilter());
	             $form->setData($request->getPost());
	             if ($form->isValid()) {
	                 $testedit->exchangeArray($form->getData());
	                 $this->getTesteditTable()->saveQuestion($testedit);
	                 // Redirect to list of questions   
	    			return $this->redirect()->toRoute('questions', array( 'action' => 'index','id' => '1',
	    					'numer_testu'=>$numer_testu,'order_by' => $order_by,'order' => $order, 'page'=> $page,),
	    					array(	'query' => array( 'il_wierszy' => $il_wierszy)));
	             }
         	 }
         	 //************************************************************************************************
         	 $request_button = $request->getPost('selectimage');
         	 if ($request_button != NULL){
   			 //*************************************************************************************************
    			$container = new Container('tests');
    			$container->questionformrequest = $request;
    			//*********************************************************************************************
    			return $this->redirect()->toRoute('wyborgrafiki',
    			array('action'=>'getImagefromimageupload'),array('query' =>(array(
							//'id_pyt'=>$id,
							//'numer_testu'=>$numer_testu,
    						//'request'=>$request,
    						//'id_image' => $id_image,
							//'id_question'=>$this->id_question,
							))));
    	    			
         	 }//end of submitt request
         	 //************************************************************************************************
         	 $request_button = $request->getPost('return');
         	 if ($request_button != NULL){
         	 	//$numer_testu = $this->params()->fromRoute('numer_testu');
         	 	$numer_testu = $this->params()->fromQuery('numer_testu');
         	 	return $this->redirect()->toRoute('questions', array( 'action' => 'index','id' => '1',
	    					'numer_testu'=>$numer_testu,'order_by' => $order_by,'order' => $order, 'page'=> $page,),
	    					array(	'query' => array( 'il_wierszy' => $il_wierszy)));
         	 }//end of submit returns
         }
         //creating form for add view**************************************************************************
         $this->layout('layout/testeditor');
         $this->setstylevariable();
         return array('form' => $form,
         				'id' => '',
    					'id_image' => '',
         				'numer_testu' => $numer_testu,
         );
    }
    //***************************************************************************************************************************
    //***************************************************************************************************************************
    
    public function editAction()
    {
    	$request = $this->getRequest();
    	//********************************************************************************************
    	$container = new Container('questionedit');
    	$order_by = $container->order_by;
    	$order = $container->order;
    	$page = $container->page;
    	$il_wierszy = $container->il_wierszy;
    	//$numer_testu = $container->numer_testu;
    	$numer_testu = (int) $this->params()->fromQuery('numer_testu');
    	//********************************************************************************************
    	if ($request->isPost()) {
    		$request_button = $request->getPost('submit');
    		if ($request_button != NULL){
	    		$form  = new QuestionsForm();
	    		$form->get('submit')->setAttribute('value', 'Zapisz pytanie');
	    		$testedit = new Questions();
	    		//**************adding validator to numer_pyt*****************************************
	    			$id_question = (int) $this->params()->fromQuery('id');
		    		$question_record = $this->gettesteditTable()->getQuestion($id_question);
	    			if ($request->getPost('nr_pyt')!=$question_record->nr_pyt) {
				    		$select = new Select();
				    		$select->from('pytania_testowe')
				    		->where->equalTo('nr_pyt', $request->getPost('nr_pyt'))
				    		->where->equalTo('numer_testu', $numer_testu);
			            $validator = new NoRecordExists($select);		
			            $validator->setAdapter(\Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter());
			            //********************************
			            $translate = new Translator();
			            $translate->addTranslationFile(
			            		'phpArray',
			            		'./module/Application/language/Zend_Validate.php',
			            		'default',
			            		'pl_PL'
			            );
			            //Zend\Validator\AbstractValidator::setDefaultTranslator($translator);
			            //********************************
			            //$validator->setTranslator($translate);
			            //throw new \Zend\File\Transfer\Exception\InvalidArgumentException(var_dump($translate));
			            //$validator->setDefaultTranslator($translate);
			            //$validator->setOptions(array('translator' =>$translate));
			            //$validator->setMessage('Taki numer pytania już istnieje');
			            $testedit->getInputFilter()->get('nr_pyt')->getValidatorChain()->attach($validator);
			          //throw new \Zend\File\Transfer\Exception\InvalidArgumentException(var_dump($validator->isValid($request->getPost('nr_pyt'))));  
	    			}
		    	//************************************************************************************
		    	
	    		$form->setInputFilter($testedit->getInputFilter());
	    		$form->setData($request->getPost());
	
	    		if ($form->isValid()) {
	    			$testedit->exchangeArray($form->getData());
	    			$this->gettesteditTable()->saveQuestion($testedit);
	    			// Redirect to list of questions
	    			//$numer_testu = $this->params()->fromRoute('numer_testu');
	    			$numer_testu = $this->params()->fromQuery('numer_testu');
	    			return $this->redirect()->toRoute('questions', array( 'action' => 'index','id' => '1',
	    					'numer_testu'=>$numer_testu,'order_by' => $order_by,'order' => $order, 'page'=> $page,),
	    					array(	'query' => array( 'il_wierszy' => $il_wierszy)));
	    		}
	    		else {//form is not valid we wre sending form from line 186 
	    			//************fetchning image from Uploadimage*********************************************
	    			$image = $question_record->image;
	    			$uploadTable = $this->getServiceLocator()->get('UploadImagTable');
	    			$uploadimage = $uploadTable->getUploadfilename($image);
	    			if ($uploadimage == NULL) {$id_image = "";} else {$id_image = $uploadimage->id;}
	    			$id = (int) $this->params()->fromQuery('id', 0);
	    			//************setting data passed to form**************************************************
	    			$this->layout('layout/testeditor');
	    			$this->setstylevariable();
	    			$viewmodel = new ViewModel(array(
	    					'id' => $id,
	    					'numer_testu' => $numer_testu,
	    					'form' => $form,
	    					'id_image' => $id_image,
	    			));
	    			return $viewmodel;
	    		}
	    		
    		}//end of submit request
    		//*************************************************************************************************
    		$request_button = $request->getPost('selectimage');
    		if ($request_button != NULL){
    			//************************************************
    			$container = new Container('tests');
    			$container->questionformrequest = $request;
    			//************************************************
    			return $this->redirect()->toRoute('wyborgrafiki',
    			array('action'=>'getImagefromimageupload'),array('query' =>(array(
							//'id_pyt'=>$id,
							//'numer_testu'=>$numer_testu,
    						//'request'=>$request,
    						//'id_image' => $id_image,
							//'id_question'=>$this->id_question,
							))));
    	    			
    		}//end of submitt request***************************************************************************
    		$request_button = $request->getPost('return');
    		if ($request_button != NULL){
    				//$numer_testu = $this->params()->fromRoute('numer_testu');
    				$numer_testu = $this->params()->fromQuery('numer_testu');
    				return $this->redirect()->toRoute('questions', array( 'action' => 'index','id' => '1',
	    					'numer_testu'=>$numer_testu,'order_by' => $order_by,'order' => $order, 'page'=> $page,),
	    					array(	'query' => array( 'il_wierszy' => $il_wierszy)));
    		}//end of submit return
    	} //end of every posts
   
    	//************************ displaying question form ****************************************************
    	//$id = (int) $this->params()->fromRoute('id', 0);
    	$id = (int) $this->params()->fromQuery('id', 0);
    	//$numer_testu = (int) $this->params()->fromRoute('numer_testu', 0);
    	$numer_testu = (int) $this->params()->fromQuery('numer_testu', 0);
    	if (!$id) {
    		return $this->redirect()->toRoute('questions', array(
    				'action' => 'add',
	         		array(	'query' => array('id'=>'1','numer_testu' => $numer_testu))
    		));
    	}
    	
    	if (!$numer_testu) {
    		return $this->redirect()->toRoute('questions', array( 'action' => 'index','id' => '1',
	    					'numer_testu'=>$numer_testu,'order_by' => $order_by,'order' => $order, 'page'=> $page,),
	    					array(	'query' => array( 'il_wierszy' => $il_wierszy)));
    	}
    	// Get the actual record of test question with the specified id.  An exception is thrown
    	// if it cannot be found, in which case go to the index page.
	    try {
	    	$questionrecord = $this->gettesteditTable()->getQuestion($id);
	    }
	    catch (\Exception $ex) {
	    	return $this->redirect()->toRoute('testeditor', array('questions' => 'index'));
	    }
	
    	$form  = new QuestionsForm();
    	$form->bind($questionrecord);
    	$form->get('submit')->setAttribute('value', 'Zapisz pytanie');
    	//************fetchning image from Uploadimage*********************************************
    	$image = $questionrecord->image;
    	$uploadTable = $this->getServiceLocator()->get('UploadImagTable');
    	$uploadimage = $uploadTable->getUploadfilename($image);
    	if ($uploadimage == NULL) {$id_image = "";} else {$id_image = $uploadimage->id;}	
        //************setting data passed to form**************************************************
    	$this->layout('layout/testeditor');
    	$this->setstylevariable();
        $viewmodel = new ViewModel(array(
    			'id' => $id,
        		'numer_testu' => $numer_testu,
    			'form' => $form,
    			'id_image' => $id_image,
    	));
    	return $viewmodel;
    } 
    //*************************************************************************************************************************
    //*************************************************************************************************************************  
     
    public function deleteoldAction() //old wersion without jquery
    {  
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$del = $request->getPost('del', 'Nie');
    		$id = (int) $request->getPost('id');
    		$numer_testu = (int) $request->getPost('numer_testu');
    		//*************************************************************************
    		if ($del == 'Tak') {
    			
    			$this->gettesteditTable()->deleteQuestion($id);
    		}
    		//*************************************************************************
    		$container = new Container('questionedit');
    		$order_by = $container->order_by;
    		$order = $container->order;
    		$page = $container->page;
    		$il_wierszy = $container->il_wierszy;
    		//$numer_testu = $container->numer_testu;
    		// Redirect to list of questions
    		return $this->redirect()->toRoute('questions',array('action'=>'index','id'=>'1','numer_testu' => $numer_testu,
    											'order_by'=>$order_by,'order'=>$order,'page'=>$page),
    											array('query' => array('il_wierszy'=>$il_wierszy)));
    	}
    
    	//$id = (int) $this->params()->fromRoute('id', 0);
    	$id = (int) $this->params()->fromQuery('id', 0);
    	$numer_testu  = (int) $this->params()->fromQuery('numer_testu', 0);
    	if ($id==0 && $numer_testu!=0) {
    		return $this->redirect()->toRoute('questions',array('action'=>'index'),
    				array(	'query' => array('numer_testu' => $numer_testu)));
    	}
    	if ($id==0 && $numer_testu==0) {
    		return $this->redirect()->toRoute('testeditor');
    	}
    	
    	$this->layout('layout/testeditor');
    	$this->setstylevariable();
    	return array(
    			//'id'    => $id,
    			'question_record' => $this->gettesteditTable()->getQuestion($id)
    	);
    }
    
    //***********************************************************************************************************************
    //***********************************************************************************************************************
    
    public function deleteAction()
    {
        $id = (int) $this->params()->fromQuery('id', 0);

            //*************************************************************************
            if (!$id==0) {
                 
                $this->gettesteditTable()->deleteQuestion($id);
            }
            //*************************************************************************
            $container = new Container('questionedit');
            $order_by = $container->order_by;
            $order = $container->order;
            $page = $container->page;
            $il_wierszy = $container->il_wierszy;
            $numer_testu = $container->numer_testu;
            // Redirect to list of questions
            return $this->redirect()->toRoute('questions',array('action'=>'index','id'=>'1','numer_testu' => $numer_testu,
                'order_by'=>$order_by,'order'=>$order,'page'=>$page),
                array('query' => array('il_wierszy'=>$il_wierszy)));
        
    }
    //***********************************************************************************************************************
    //***********************************************************************************************************************
    
    public function deleteMarkAction()
    {
        //***************************************************************************************************
        $container = new Container('questionedit');
        $order_by = $container->order_by;
        $order = $container->order;
        $page = $container->page;
        $il_wierszy = $container->il_wierszy;
        $id_testu = $container->numer_testu;
        //***************************************************************************************************
        $this->gettesteditTable()->deleteQuestionsMark($id_testu);
        return $this->redirect()->toRoute('questions', array(
            'action' => 'index','id' => '1','numer_testu' => $id_testu,'order_by' => $order_by,'order'=>$order,'page'=>$page,'il-wierszy'=>$il_wierszy
        ));
      
    }
    
    public function gettesteditTable()
    {
    	if (!$this->testeditTable) {
    		$sm = $this->getServiceLocator();
    		$this->testeditTable = $sm->get('Testeditor\Model\TesteditTable');
    	}
    	return $this->testeditTable;
    }
    
    //***********************************************************************************************************************
    //***********************************************************************************************************************    

    public function showImageAction()
    {
    	$uploadId = $this->params()->fromRoute('id');
    	//$uploadId = $this->params()->fromQuery('id');
    	$uploadTable = $this->getServiceLocator()->get('UploadImagTable');
    	$upload = $uploadTable->getUpload($uploadId);
    	// Creating path *********************************************************
    	$uploadPath = realpath(__DIR__.'/../../../../..'.'/data/uploads_images');
    	//if ($this->params()->fromRoute('subaction') == 'thumb')
    	//{
    		$filename = $uploadPath ."/" . $upload->thumbnail;
    	//} else {
    		//$filename = $uploadPath ."/" . $upload->filename;
    	//}
    	header("Content-Type: image/jpeg");
    	readfile($filename);
    }
    
//************function used to select image in edit, add form of questions******************************************
//******************************************************************************************************************
    
    public function getImagefromimageuploadAction() {
    	// *******getting images from table **********************************************
    	$uploadImageTable = $this->getServiceLocator()->get('UploadImagTable');
    	$select = new Select();
    	$select->from('image_uploads');
    	$order_by = $this->params()->fromRoute('order_by') ?
    	$this->params()->fromRoute('order_by') : 'id';
    	$order = $this->params()->fromRoute('order') ?
    	$this->params()->fromRoute('order') : Select::ORDER_ASCENDING;
    	$page = $this->params()->fromRoute('page') ? (int) $this->params()->fromRoute('page') : 1;
    	$paginator = $uploadImageTable->fetchAll(true,$select->order($order_by . ' ' . $order));
    	// set the current page to what has been passed in query string, or to 1 if none set
    	$paginator->setCurrentPageNumber($page);
    	$paginator->setItemCountPerPage((int) $this->params()->fromQuery('il_wierszy', 10));
 
    	$this->layout('layout/testeditor');
    	$this->setstylevariable();
    	$viewModel = new ViewModel(array(
    			'paginator' => $paginator,
    			'page' => $page,
    			'order_by' => $order_by,
    			'order' => $order,
 
    	));
    	$viewModel->setTemplate('testeditor/questionedit/getimage');
    	return  $viewModel;
    }
    
    //***********************************************************************************************************************
    //***********************************************************************************************************************
    
    public function assignimageAction() 
    {   	
    	//*****************************getting request from session********************************
    	$container = new Container('tests');
    	$request = $container->questionformrequest;
    	//*****************************************************************************************
    	//$id_image = $this->params()->fromRoute('id');
    	$id_image = $this->params()->fromQuery('id');
    	//if any picture was choosen
    	if ($id_image!=NULL) { 
	    	$uploadImageTable = $this->getServiceLocator()->get('UploadImagTable');
	    	$image_record = $uploadImageTable->getUpload($id_image);
    	}
    	else { //if nothing was choosen
    		$image = $request->getPost('image');
    		$uploadTable = $this->getServiceLocator()->get('UploadImagTable');
    		$image_record = $uploadTable->getUploadfilename($image);
    		//it is needed to get id_image if nothing was choosen (id_image for image filename from post)
    		if ($image_record == NULL) {$id_image = "";} else {$id_image = $image_record->id;}
    	}
    	//************setting data passed to form**************************************************
    	$form  = new QuestionsForm();
    	$form->get('id')->setValue($request->getPost('id'));
    	$form->get('nr_pyt')->setValue($request->getPost('nr_pyt'));
    	$form->get('numer_testu')->setValue($request->getPost('numer_testu'));
    	$form->get('pytanie')->setValue($request->getPost('pytanie'));
    	$form->get('odp1')->setValue($request->getPost('odp1'));
    	$form->get('odp2')->setValue($request->getPost('odp2'));
    	$form->get('odp3')->setValue($request->getPost('odp3'));
    	$form->get('odp4')->setValue($request->getPost('odp4'));
    	$form->get('odp_praw')->setValue($request->getPost('odp_praw'));
    	
    	if ($image_record !=NULL) {
    		$form->get('image')->setValue($image_record->filename);
    	}
    	else {
    		$form->get('image')->setValue($request->getPost('image'));
    	}
    	$form->get('submit')->setAttribute('value', 'Zapisz pytanie');

    	$this->layout('layout/testeditor');
    	$this->setstylevariable();
    	$viewmodel = new viewModel(array(
    			'id' => $request->getPost('id'),
    			'numer_testu' => $request->getPost('numer_testu'),
    			'form' => $form,
    			'id_image' => $id_image,

    	));
    	
    	if ($request->getPost('id')!=NULL)
    	{
    		$viewmodel->setTemplate('testeditor/questionedit/edit');
    	}
    	else {
    		$viewmodel->setTemplate('testeditor/questionedit/add');
    	}
    	
    	return $viewmodel;
    	
    }
    
    public function exportquestionsblackboardAction()
    {
        //throw new \Zend\File\Transfer\Exception\InvalidArgumentException(var_dump('jest'));
        $id_testu = (int) $this->params()->fromRoute('numer_testu', 0);
        //$id_testu = $Id_testu;
        $nazwa_testu = $this->gettesteditTable()->getTest($id_testu)->nazwa_testu;
        $select = new Select('pytania_testowe');
        $select->where(array('numer_testu'=>$id_testu));
        $order_by = 'nr_pyt';
        $order = Select::ORDER_ASCENDING;
        $questions = $this->gettesteditTable()->fetchAllQuestions($id_testu, false, $select->order($order_by . ' ' . $order));
        //******************************************************************************
        $records[] = array();
        $subrecord[] = array();
        $number_of_questions = $questions->count();
        $i=0;
        foreach ($questions as $question) {
            $subrecord[0] = 'MC';
            $subrecord[1] = $question->pytanie;
            $subrecord[2] = $question->odp1;
            if ($question->odp_praw == 1)
            {
                $subrecord[3] = 'correct';
            }
            else {
                $subrecord[3] = 'incorrect';
            }
            //-------------------------------
            $subrecord[4] = $question->odp2;
            if ($question->odp_praw == 2)
            {
                $subrecord[5] = 'correct';
            }
            else {
                $subrecord[5] = 'incorrect';
            }
            //-------------------------------
            $subrecord[6] = $question->odp3;
            if ($question->odp_praw == 3)
            {
                $subrecord[7] = 'correct';
            }
            else {
                $subrecord[7] = 'incorrect';
            }
            //-------------------------------
            $subrecord[8] = $question->odp4;
            if ($question->odp_praw == 4)
            {
                $subrecord[9] = 'correct';
            }
            else {
                $subrecord[9] = 'incorrect';
            }
            //-------------------------------
            $records[$i] = $subrecord; $i++;
        }
        //******************************************************************************
        $date = new dateTime('NOW');
        $date_str = (string)$date->format('YmdHi');
        //$nazwa_pliku = $nazwa_testu . '_'.$id_testu.'_'.$date_str.'.csv';
        $nazwa_pliku = $nazwa_testu . '_'.$id_testu.'_'.$date_str;
        $header = array();
        for($i=0;$i<$number_of_questions;$i++) {
            $header[$i] =  ' ';
        }
        return $this->csvExport($nazwa_pliku, $header, $records,"\t",'"',false);
    }
    
    public function exportquestionsgiftAction()
    {
        $id_testu = (int) $this->params()->fromRoute('numer_testu', 0);
        $nazwa_testu = $this->gettesteditTable()->getTest($id_testu)->nazwa_testu;
        $select = new Select('pytania_testowe');
        $select->where(array('numer_testu'=>$id_testu));
        $order_by = 'nr_pyt';
        $order = Select::ORDER_ASCENDING;
        $questions = $this->gettesteditTable()->fetchAllQuestions($id_testu, false, $select->order($order_by . ' ' . $order));
        //******************************************************************************
        $records[] = array();
        $subrecord[] = array();
        $number_of_questions = $questions->count();
        $i=0;
        foreach ($questions as $question) {
            $subrecord[0] = $question->pytanie;
            $subrecord[1] = '{';
            if ($question->odp_praw == 1)
            {
                $subrecord[2] = '=';
            }
            else {
                $subrecord[2] = '~';
            }
            $subrecord[2] = $subrecord[2] . $question->odp1;
            //-------------------------------
            if ($question->odp_praw == 2)
            {
                $subrecord[3] = '=';
            }
            else {
                $subrecord[3] = '~';
            }
            $subrecord[3] = $subrecord[3] . $question->odp2;
            //-------------------------------
            if ($question->odp_praw == 3)
            {
                $subrecord[4] = '=';
            }
            else {
                $subrecord[4] = '~';
            }
            $subrecord[4] = $subrecord[4] . $question->odp3;
            //-------------------------------
            
            if ($question->odp_praw == 4)
            {
                $subrecord[5] = '=';
            }
            else {
                $subrecord[5] = '~';
            }
            $subrecord[5] = $subrecord[5] . $question->odp4;
            $subrecord[6] = '}';
            //-------------------------------
            $records[$i] = $subrecord; $i++;
        }
        //******************************************************************************
        $date = new dateTime('NOW');
        $date_str = (string)$date->format('YmdHi');
        //$nazwa_pliku = $nazwa_testu . '_'.$id_testu.'_'.$date_str.'.csv';
        $nazwa_pliku = $nazwa_testu . '_'.$id_testu.'_'.$date_str;
        $header = array();
        //for($i=0;$i<$number_of_questions;$i++) {
            //$pyt = 'Pyt '.($i-1);
            //$header[$i] =  ' ';
        //}
        return $this->csvExport($nazwa_pliku, $header, $records, " ", " ", false);
    }
    
    public function exportquestionsxmlAction()
    {
        $id_testu = (int) $this->params()->fromRoute('numer_testu', 0);
        $nazwa_testu = $this->gettesteditTable()->getTest($id_testu)->nazwa_testu;
        $select = new Select('pytania_testowe');
        $select->where(array('numer_testu'=>$id_testu));
        $order_by = 'nr_pyt';
        $order = Select::ORDER_ASCENDING;
        $questions = $this->gettesteditTable()->fetchAllQuestions($id_testu, false, $select->order($order_by . ' ' . $order));
        //*************************************************************************************************************
        //$doc = new SimpleXMLElement('<xml version="1.0"/>');
        //$quiz = $doc->addChild('quiz'); 
        //$question = $quiz->addChild('question1','');
        //*************************************************************************************************************       
        $domtree = new DOMDocument('1.0', 'UTF-8');    
        /* create the root element of the xml tree */
        $quiz = $domtree->createElement("quiz");
        /* append it to the document created */
        $quiz = $domtree->appendChild($quiz);
        $attributes_img[] = array(); 
        $quest_images[] = array();
        $i=1;
        $helper = $this->getServiceLocator()->get('ViewhelperManager')->get('basePath');
        $basePath = $helper('/');
        foreach ($questions as $question) {
            $quest = $domtree->createElement('question');
            $quest->setAttribute('type', 'multichoice');
            $quiz->appendChild($quest);
                $name = $domtree->createElement('name');
                $quest->appendChild($name);
                    $text = $domtree->createElement('text','Pytanie '.$i);
                    $name->appendChild($text);
                $questiontext = $domtree->createElement('questiontext');
                $questiontext->setAttribute('format', 'html');
                $quest->appendChild($questiontext);
                   ////////////creating text element with img or without///////////////////////////////
                   $domtree = $this->createCDATAelement($question->pytanie,$domtree, $questiontext);
                   ////////////////////////////////////////////////////////////////////////////////////
                $answernumbering = $domtree->createElement('answernumbering','ABCD');
                $quest->appendChild($answernumbering);
               
                $answer = $domtree->createElement('answer');
                if ($question->odp_praw == 1)
                {
                    $answer->setAttribute('fraction', '100');
                }
                else {
                    $answer->setAttribute('fraction', '0');
                }   
                $quest->appendChild($answer);
                    ////////////creating text element with img or without///////////////////////////////
                    $domtree = $this->createCDATAelement($question->odp1, $domtree, $answer);
                    ////////////////////////////////////////////////////////////////////////////////////
                    //$text = $domtree->createElement('text',$question->odp1);
                    //$answer->appendChild($text);
                    
                $answer = $domtree->createElement('answer');
                if ($question->odp_praw == 2)
                {
                    $answer->setAttribute('fraction', '100');
                }
                else {
                    $answer->setAttribute('fraction', '0');
                }
                $quest->appendChild($answer);
                    ////////////creating text element with img or without///////////////////////////////
                    $domtree = $this->createCDATAelement($question->odp2, $domtree, $answer);
                    ////////////////////////////////////////////////////////////////////////////////////
                    //$text = $domtree->createElement('text',$question->odp2);
                    // $answer->appendChild($text);
                
                $answer = $domtree->createElement('answer');
                if ($question->odp_praw == 3)
                {
                    $answer->setAttribute('fraction', '100');
                }
                else {
                    $answer->setAttribute('fraction', '0');
                }
                $quest->appendChild($answer);
                    ////////////creating text element with img or without///////////////////////////////
                    $domtree = $this->createCDATAelement($question->odp3, $domtree, $answer);
                    ////////////////////////////////////////////////////////////////////////////////////
                    //$text = $domtree->createElement('text',$question->odp3);
                    //$answer->appendChild($text);
                
                $answer = $domtree->createElement('answer');
                if ($question->odp_praw == 4)
                {
                    $answer->setAttribute('fraction', '100');
                }
                else {
                    $answer->setAttribute('fraction', '0');
                }
                $quest->appendChild($answer);
                    ////////////creating text element with img or without///////////////////////////////
                    $domtree = $this->createCDATAelement($question->odp4, $domtree, $answer);
                    ////////////////////////////////////////////////////////////////////////////////////
                    //$text = $domtree->createElement('text',$question->odp1);
                    //$answer->appendChild($text);
                $i++;
        }
        ////////////////////////////////////////////////////////////////
        $shuffleanswers = $domtree->createElement('shuffleanswers','0');
        $single = $domtree->createElement('single','true');
        $answernumbering = $domtree->createElement('answernumbering','ABC');
        ////////////////////////////////////////////////////////////////     
        $domtree->preserveWhiteSpace = false;
        $domtree->formatOutput = true;
        
        //throw new \Zend\File\Transfer\Exception\InvalidArgumentException(var_dump($rootpath));
        if (method_exists($this, 'getResponse'))
        {
            /** @var HttpResponse $response */
            $response = $this->getResponse();
        }
        else
        {
            $response = new HttpResponse;
        }
        ////////////////////////////////////////////////////////////////////////
        $fp = fopen('php://output', 'w');
        ob_start();
        fwrite($fp, $domtree->saveXML());
        fclose($fp);
        $response->setContent(ob_get_clean());
        $date = new dateTime('NOW');
        $date_str = (string)$date->format('YmdHi');
        $nazwa_pliku = $nazwa_testu . '_'.$id_testu.'_'.$date_str;
        $response->getHeaders()->addHeaders(array(
            'Content-Type' => 'text/xml',
            'Content-Disposition' => 'attachment;filename="'. str_replace('"', '\\"', $nazwa_pliku) .'.xml"',
        ));
        return $response;
       
    }
    
    private function base64_encode_image ($filename, $filetype) {
        if ($filename) {
            $imgbinary = fread(fopen($filename, "r"), filesize($filename));
            return  base64_encode($imgbinary);
        }
    }
    
    private function createCDATAelement($question, $domtree, $questiontext)
    {
        $helper = $this->getServiceLocator()->get('ViewhelperManager')->get('basePath');
        $basePath = $helper('/');
        $questhtml = new DOMDocument($question);
        $questhtml->loadHTML($question);
        $tags = $questhtml->getElementsByTagName('img');
        $is_image = false;
        foreach ($tags as $tag) {
            $path = $tag->getAttribute('src');
            $path = str_replace($basePath, "", $path);
            $path = '/public/'.$path;
            $rootpath = realpath(__DIR__.'/../../../../../');
            $rootpath = $rootpath . $path;
        
            if (($path != '') || ($path != NULL))
            {
                $is_image = true;
                $file_name = basename($path);
                $ext = new SplFileInfo($file_name);
                $tag->setAttribute('src','@@PLUGINFILE@@/'.$file_name);
            }
        }
        if ($is_image) {
            $questHTML = $questhtml->saveHTML();
            $text = $domtree->createelement('text');
            $questiontext->appendChild($text);
            $CDATA = $domtree->createCDATASection($questHTML);
            $text->appendChild($CDATA);
            $file = $domtree->createElement('file',$this->base64_encode_image ($rootpath,$ext->getExtension()));
            $file->setAttribute('name',$file_name);
            $file->setAttribute('path','/');
            $file->setAttribute('encoding','base64');
            $questiontext->appendChild($file);
            return $domtree;
        }
        else {
            $text = $domtree->createElement('text',$question);
            $questiontext->appendChild($text);
            return $domtree;;
        }
    }
    
    public function createquestionsfromxmlAction()
    {
        if ($this->params()->fromQuery('mode') == 'beginning') {
            $root = realpath(__DIR__.'/../../../../../');
            $root = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
            $root_uploads = $root.'/data/uploads';
            $pathandfilename = $this->params()->fromQuery('file');
            $path = $root_uploads.$pathandfilename;
            //arrays needed for storing data from xml
            $questions[] = array();
            $question_name[] = array();
            $question_text[] = array();
            $file_name[] = array();
            $answers[][] = array();
            $right_answers[] = array();
            $errors[] = array();
            //****************************************************************************************************
            //reading xml file
            //****************************************************************************************************
            libxml_use_internal_errors(true);
            $xml = simplexml_load_file($path,'SimpleXMLElement', LIBXML_NOCDATA);  
            if ($xml == false) {
                foreach(libxml_get_errors() as $error)
                {
                    array_push($errors,'Error parsing XML file ' . $path . ': ' . $error->message);
                }
                $response = $this->getResponse();
                $content = array('error' => $errors);
                $response->setContent(json_encode($content));
                return $response;
            }
          
            $quest_count = count($xml->question);
            if ($quest_count == 0) {
                $response = $this->getResponse();
                array_push($errors,'Nie znaleziono żadnych pytań');
                $content = array('error' => $errors);
                $response->setContent(json_encode($content));
                return $response;
            }
            $numer_testu = $this->params()->fromQuery('numertestu');
            $nazwa_testu = $this->gettesteditTable()->getTest($numer_testu)->nazwa_testu;
            $is_filename = FALSE;
            $nazwa_testu = preg_replace('/[^\w\-'. ($is_filename ? '~_\.' : ''). ']+/u', '-', $nazwa_testu);
            //$nazwa_testu = utf8_encode($nazwa_testu); //filename is not in the name of folder - problrm with polish chars
            //****************************************************************************************************
            //getting questions text
            //****************************************************************************************************
            $j=0;     
            for($i = 0; $i < $quest_count; $i++) {
                $questions[$i] = $xml->question[$i]; 
                if ($questions[$i]->category->text == null)
                {
                    $question_name[$j] = $xml->question[$i]->name->text->__toString();
                    $question_text[$j] = $xml->question[$i]->questiontext->text->__toString();
                    $basePath = $this->getRequest()->getBasePath();
                    $pathtoimage = $basePath . '/tinymce3511/uploads/images/numer-testu-'.$numer_testu;
                    $question_text[$j] = str_replace('@@PLUGINFILE@@', $pathtoimage, $question_text[$j]);
                    if (!empty($xml->question[$i]->questiontext->file))
                    {
                        $file_name[$j] = $xml->question[$i]->questiontext->file->attributes()->name;
                        $fileimagepath = $root . '/public/tinymce3511/uploads/images/numer-testu-'.$numer_testu.'/'.$file_name[$j];
                        if (!file_exists($root . '/public/tinymce3511/uploads/images/numer-testu-'.$numer_testu))
                        {
                            mkdir($root . '/public/tinymce3511/uploads/images/numer-testu-'.$numer_testu);
                        }
                        
                        $fileimage = fopen( $fileimagepath, 'wb' );
                        fwrite( $fileimage, base64_decode( $xml->question[$i]->questiontext->file->__toString()) );
                        fclose( $fileimage );
                    }
                    // getting answers from xml
                    $answer_count = count($xml->question[$i]->answer);
                    if ($answer_count>4) {$answer_count = 4;}
                        for ($a = 0; $a < $answer_count; $a++)
                        {
                            $answers[$j][$a] = $xml->question[$i]->answer[$a]->text->__toString();
                            $answers[$j][$a] = str_replace('@@PLUGINFILE@@', $pathtoimage, $answers[$j][$a]);
                            if ($xml->question[$i]->answer[$a]->attributes()->fraction != 0) {$right_answers[$j] = $a+1;}
                            //getting image files from xml
                            if (!empty($xml->question[$i]->answer[$a]->file))
                            {
                                $file_name[$j] = $xml->question[$i]->answer[$a]->file->attributes()->name;
                                $fileimagepath = $root . '/public/tinymce3511/uploads/images/numer-testu-'.$numer_testu.'/'.$file_name[$j];
                                if (!file_exists($root . '/public/tinymce3511/uploads/images/numer-testu-'.$numer_testu))
                                {
                                    mkdir($root . '/public/tinymce3511/uploads/images/numer-testu-'.$numer_testu);
                                }
                            
                                $fileimage = fopen( $fileimagepath, 'wb' );
                                fwrite( $fileimage, base64_decode( $xml->question[$i]->answer[$a]->file->__toString()) );
                                fclose( $fileimage );
                            }
                        }
                    $j++;
                }
            }
            
            //**********************************************************************************************
            //counting existing questions in test (finding max number of question witch is treated as last question)
            //**********************************************************************************************
            $select = new Select();
            $select->from('pytania_testowe')
            ->where(array('numer_testu' => $numer_testu));
            $existing_questions = $this->getTesteditTable()->fetchAllQuestions($numer_testu,false,$select);
            $number_record = count($existing_questions);
            if ($number_record != 0)
            {
                $Numbers_of_questions[] = array();
                $i = 0;
                foreach ($existing_questions as $question)
                {
                    $Numbers_of_questions[$i] = (int)$question->nr_pyt; $i++;
                }
                $last_question_number = max($Numbers_of_questions);
            }
            else {
                $last_question_number = 0;
            }
                   
            
            $number_of_xml_questions = count($question_text);
            $number_of_all_questions = $last_question_number + $number_of_xml_questions;
            if ($number_of_all_questions>40) {$number_of_xml_questions = 40 - $last_question_number;}
            $step = 100/($number_of_xml_questions);
            $step = round($step, 2);
            $container = new Container('questionedit');
            $container->question_text = $question_text;
            $container->answers = $answers;
            $container->right_answers = $right_answers;
            $container->last_question_number = $last_question_number;
            //$container->number_of_all_questions = $number_of_all_questions;
            $container->step = $step;
            
            $response = $this->getResponse();
            $content = array('last_question_number'=>$last_question_number,'number_of_xml_questions'=>$number_of_xml_questions, 'error' => '');
            $response->setContent(json_encode($content));
            return $response;
        }
        //***saving data***********************************************************************************
        if  ($this->params()->fromQuery('mode') == 'save'){
           
            $numer_testu = $this->params()->fromQuery('numertestu');
            
            $container = new Container('questionedit');
            $question_text = $container->question_text;
            $answers  = $container->answers;
            $right_answers = $container->right_answers;
            $last_question_number = $container->last_question_number;
            $step = $container->step;
            
            $datatosave = new Questions;
            $question = (int)$this->params()->fromQuery('question');
            $i = $question;
            $datatosave->nr_pyt = $i+1+$last_question_number;
            $datatosave->numer_testu = $numer_testu;
            $datatosave->pytanie = $question_text[$i];
            $datatosave->odp1 = $answers[$i]['0'];
            $datatosave->odp2 = $answers[$i]['1'];
            $datatosave->odp3 = $answers[$i]['2'];
            $datatosave->odp4 = $answers[$i]['3'];
            $datatosave->odp_praw = $right_answers[$i];
            $datatosave->mark = 0;
           
            //saving record to table
            $this->getTesteditTable()->saveQuestion($datatosave);
            
            $response = $this->getResponse();
            $persentage = $step*($question+1);
            $nr_pyt = $i+1;
            $message = 'Liczba zapisanych pytań: '.$nr_pyt;
            $content = array('persentage'=>$persentage, 'error' => '','message'=>$message);
            $response->setContent(json_encode($content));
            return $response;
        }  
   
        //throw new \Zend\File\Transfer\Exception\InvalidArgumentException((var_dump($datatosave)));
        //****redirecting to url via ajax****************************************************************************
        if ($this->params()->fromQuery('mode') == 'end') {
            $container = new Container('questionedit');
            $order_by = $container->order_by;
            $order = $container->order;
            $page = $container->page;
            $il_wierszy = $container->il_wierszy;
            $numer_testu = $container->numer_testu;
            // Redirect to list of questions
            $response = $this->getResponse();
            $content = $this->url()->fromRoute('questions',array('action'=>'index','id'=>'1','numer_testu' => $numer_testu,
                'order_by'=>$order_by,'order'=>$order,'page'=>$page),
                array('query' => array('il_wierszy'=>$il_wierszy)));
            $response->setContent($content);
            return $response;
        }
    }
        
    public function filetreeconnectorAction()
    {
    //
    // jQuery File Tree PHP Connector
    //
    // Version 1.01
    //
    // Cory S.N. LaViska
    // A Beautiful Site (http://abeautifulsite.net/)
    // 24 March 2008
    //
    // History:
    //
    // 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
    // 1.00 - released (24 March 2008)
    //
    // Output a list of files for jQuery File Tree
    //
    
        $request = $this->getRequest();
        if ($request->isPost()) {
            $dir = $request->getPost('dir');
            $root = realpath(__DIR__.'/../../../../../');
            $root = $root.'/data/uploads/';
            
            if( file_exists($root . $dir) ) {
                $files = scandir($root . $dir);
                //foreach (glob($root . $dir .'*.xml') as $file) {
                    //$files[] = $file;
                //}
                natcasesort($files);
                if( count($files) > 2 ) { /* The 2 accounts for . and .. */
                    //echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
                    $content = "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
                    // All dirs
                    foreach( $files as $file ) {
                        if( file_exists($root . $dir . $file) && $file != '.' && $file != '..' && is_dir($root . $dir . $file) ) {
                            //echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($dir . $file) . "/\">" . htmlentities($file) . "</a></li>";
                            $content .= "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($dir . $file) . "/\">" . htmlentities($file) . "</a></li>";
                        }
                    }
                    // All files
                    foreach( $files as $file ) {
                        if( file_exists($root . $dir . $file) && $file != '.' && $file != '..' && !is_dir($root . $dir . $file) && (preg_replace('/^.*\./', '', $file ) == 'xml')) {
                            $ext = preg_replace('/^.*\./', '', $file);
                            //echo "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($dir . $file) . "\">" . htmlentities($file) . "</a></li>";
                            $content .= "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($dir . $file) . "\">" . htmlentities($file) . "</a></li>";
                        }
                    }
                    //echo "</ul>";
                    $content .= "</ul>";
                    
                    $response = $this->getResponse();
                    $response->setContent($content);
                    return $response;
                }
            }
        }
    }
    
    //toggle functions
    public function togglemarkrecordAction()
    {
        $question_id = (int) $this->params()->fromRoute('id');
        
        // Load actual Question from 'TesteditTable' table

        $question_record = $this->getTesteditTable()->getQuestion($question_id);
        //*********************************************************************
        if ($question_record->mark == 1)
        {
            $question_record->mark = '0';
        }
        else
        {
            $question_record->mark = '1';
        }
        $this->getTesteditTable()->saveMark($question_record);
         
        return true;
         
    }
    
    public function markrecordAction()
    {
        $question_id = (int) $this->params()->fromRoute('id');
    
        // Load actual Question from 'TesteditTable' table
        $question_record = $this->getTesteditTable()->getQuestion($question_id);
        //*********************************************************************
        $question_record->mark = '1';
        $this->getTesteditTable()->saveMark($question_record);
    
        return true;
    }
    
    public function unmarkrecordAction()
    {
        $question_id = (int) $this->params()->fromRoute('id');
    
        // Load actual Question from 'TesteditTable' table
        $question_record = $this->getTesteditTable()->getQuestion($question_id);
        //*********************************************************************
        $question_record->mark = '0';
        $this->getTesteditTable()->saveMark($question_record);
    
        return true;
    }
    
    private function setstylevariable()
    {
        //**************************************************************
        $stylecontainer = new Container();
        $styleSheet = $stylecontainer->stylesheet;
        $logoImage = $stylecontainer->logoimage;
        $styleColor = $stylecontainer->stylecolor;
        if ($styleSheet != null) {
            $this->layout()->setVariable('styleurl', $styleSheet);
            $this->layout()->setVariable('logoimage', $logoImage);
        }
        else {
            $event = $this->getEvent();
            $request = $event->getRequest();
            $styleSheet = $request->getBaseUrl() . '/edukacja/styles.css';
            $this->layout()->setVariable('styleurl', $styleSheet);
            $logoImage = $request->getBaseUrl() . '/edukacja/logo-solving.png';
            $this->layout()->setVariable('logoimage', $logoImage);
        }
        //***************************************************************
    }
}
