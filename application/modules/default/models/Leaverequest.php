<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_Model_Leaverequest extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_leaverequest';
    //protected $_primary = 'id';
	
	
	public function getAvailableLeaves($loginUserId)
	{
	 	$select = $this->select()
    					   ->setIntegrityCheck(false)	
                           ->from(array('e'=>'main_employeeleaves'),array('leavelimit'=>'e.emp_leave_limit','remainingleaves'=>new Zend_Db_Expr('e.emp_leave_limit - e.used_leaves')))
						   ->where('e.user_id='.$loginUserId.' AND e.alloted_year = now() AND e.isactive = 1');  		   					   				
		return $this->fetchAll($select)->toArray();   
	
	}
	
	/*public function getsinglePendingLeavesData($id)
	{
		$row = $this->fetchRow("id = '".$id."'");
		if (!$row) {
			throw new Exception("Could not find row $id");
		}
		return $row->toArray();
	}*/
	public function getsinglePendingLeavesData($id)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leaverequest'),array('l.*'))
 	  				->where("l.isactive = 1 AND l.id = ".$id);
	//	echo "Result > ".$result ;die;			
    	return $this->fetchAll($result)->toArray();
	}
	
	public function getUserLeavesData($id)
	{
		$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leaverequest'),array('l.*'))
 	  				->where("l.isactive = 1 AND l.user_id = ".$id);
		//echo "Result > ".$result ;die;			
    	return $this->fetchAll($result)->toArray();
	}
	
	public function getUserApprovedOrPendingLeavesData($id)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
       
		
		$query = "SELECT `l`.* FROM `main_leaverequest` AS `l` WHERE (l.isactive = 1 AND l.user_id = '$id' and l.leavestatus IN(1,2))";
		
        $result = $db->query($query)->fetchAll();
	    return $result;
	}
	
	public function getReportingManagerId($id)
	{
	    $result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leaverequest'),array('repmanager'=>'l.rep_mang_id'))
 	  				->where("l.isactive = 1 AND l.id = ".$id);
	//	echo "Result > ".$result ;die;			
    	return $this->fetchAll($result)->toArray();
	}
	
	public function SaveorUpdateLeaveRequest($data, $where)
	{
	    if($where != '')
		{
			$this->update($data, $where);
			return 'update';
		}
		else
		{
			$this->insert($data);
			$id=$this->getAdapter()->lastInsertId('main_leaverequest');
			return $id;
		}
	}
	
	public function getLeaveStatusHistory($sort, $by, $pageNo, $perPage,$searchQuery,$queryflag='',$loggedinuser,$managerstring='')
	{	//echo "<br/>Manager str > ".$managerstring." > loggedin user > ".$loggedinuser."<br/>";
	    $auth = Zend_Auth::getInstance();
			if($auth->hasIdentity()){
				$loginUserId = $auth->getStorage()->read()->id;
		}  
		if($loggedinuser == '') 
		 $loggedinuser = $loginUserId;
		 
		/* Removing isactive checking from configuration table */ 
		if($managerstring !='')
		{
		  //$where = "l.isactive = 1 AND et.isactive = 1";
		  $where = "l.isactive = 1 ";
		}  
		else 
        {		
	      //$where = "l.isactive = 1 AND et.isactive = 1 AND l.user_id = ".$loggedinuser." ";
		  $where = "l.isactive = 1 AND l.user_id = ".$loggedinuser." ";
		}  
		if($queryflag !='')
		{
		   if($queryflag == 'pending')
		   {
		     $where .=" AND l.leavestatus = 1 ";
		   }
		   else if($queryflag == 'approved')
		   {
		     $where .=" AND l.leavestatus = 2 ";
		   }
		   else if($queryflag == 'cancel')
		   {
		     $where .=" AND l.leavestatus = 4 ";
		   }
		   else if($queryflag == 'rejected')
		   {
		     $where .=" AND l.leavestatus = 3 ";
		   }
		
		}else
		{
		  $where .=" AND l.leavestatus = 2 ";
		}
		
			
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		/*
			Added:	Date format taking from site_constants,which is configured in site preferences.
			Modified By:	Yamini.
			Modified Date:	22/10/2013.
		*/
		/*$leaveStatusData = $this->select()
    					   ->setIntegrityCheck(false)	
                           ->from(array('l'=>'main_leaverequest'),
						          array( 'l.*','DATE_FORMAT(l.from_date,"%m-%d-%Y")',
								         'DATE_FORMAT(l.to_date,"'.DATEFORMAT_MYSQL.'")',
										 'applieddate'=>'DATE(l.createddate)',
                                         'leaveday'=>'if(l.leaveday = 1,"Full Day","Half Day")', 										 
								       ))
						   ->joinLeft(array('et'=>'main_employeeleavetypes'), 'et.id=l.leavetypeid',array('leavetype'=>'et.leavetype'))	
                           ->joinLeft(array('u'=>'main_users'), 'u.id=l.rep_mang_id',array('reportingmanagername'=>'u.userfullname'))
                           ->joinLeft(array('mu'=>'main_users'), 'mu.id=l.user_id',array('employeename'=>'mu.userfullname'))						                 			   						   
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);*/
		$leaveStatusData = $this->select()
    					   ->setIntegrityCheck(false)	
                           ->from(array('l'=>'main_leaverequest'),
						          array( 'l.*','from_date'=>'DATE_FORMAT(l.from_date,"'.DATEFORMAT_MYSQL.'")',
								         'to_date'=>'DATE_FORMAT(l.to_date,"'.DATEFORMAT_MYSQL.'")',
										 'applieddate'=>'DATE_FORMAT(l.createddate,"'.DATEFORMAT_MYSQL.'")',
                                         'leaveday'=>'if(l.leaveday = 1,"Full Day","Half Day")', 										 
								       ))
						   ->joinLeft(array('et'=>'main_employeeleavetypes'), 'et.id=l.leavetypeid',array('leavetype'=>'et.leavetype'))	
                           ->joinLeft(array('u'=>'main_users'), 'u.id=l.rep_mang_id',array('reportingmanagername'=>'u.userfullname'))
                           ->joinLeft(array('mu'=>'main_users'), 'mu.id=l.user_id',array('employeename'=>'mu.userfullname'))						                 			   						   
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);
		//echo "<br/>Query Flag > ".$queryflag."<br/>";
		//echo $leaveStatusData->__toString()."<br/>"; 
		return $leaveStatusData;
		
	}
	
	
	public function getEmployeeLeaveRequest($sort, $by, $pageNo, $perPage,$searchQuery,$loginUserId)
	{	
		$where = "l.isactive = 1 AND l.leavestatus=1 AND u.isactive=1 AND l.rep_mang_id=".$loginUserId." ";
		
		if($searchQuery)
			$where .= " AND ".$searchQuery;
		$db = Zend_Db_Table::getDefaultAdapter();		
		/*
			Added:	Date format taking from site_constants,which is configured in site preferences.
			Modified By:	Yamini.
			Modified Date:	22/10/2013.
		*/
		/*$employeeleaveData = $this->select()
    					   ->setIntegrityCheck(false)	
                           ->from(array('l'=>'main_leaverequest'),
						          array( 'l.*','DATE_FORMAT(l.from_date,"'.DATEFORMAT_MYSQL.'")',
								         'DATE_FORMAT(l.to_date,"'.DATEFORMAT_MYSQL.'")',
										 'applieddate'=>'DATE(l.createddate)',
                                         'leaveday'=>'if(l.leaveday = 1,"Full Day","Half Day")', 										 
								       ))
						   ->joinLeft(array('et'=>'main_employeeleavetypes'), 'et.id=l.leavetypeid',array('leavetype'=>'et.leavetype'))	
						   ->joinLeft(array('u'=>'main_users'), 'u.id=l.user_id',array('userfullname'=>'u.userfullname'))						   						 		   						   
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);*/
		$employeeleaveData = $this->select()
    					   ->setIntegrityCheck(false)	
                           ->from(array('l'=>'main_leaverequest'),
						          array( 'l.*','from_date'=>'DATE_FORMAT(l.from_date,"'.DATEFORMAT_MYSQL.'")',
								         'to_date'=>'DATE_FORMAT(l.to_date,"'.DATEFORMAT_MYSQL.'")',
										 'applieddate'=>'DATE_FORMAT(l.createddate,"'.DATEFORMAT_MYSQL.'")',
                                         'leaveday'=>'if(l.leaveday = 1,"Full Day","Half Day")', 										 
								       ))
						   ->joinLeft(array('et'=>'main_employeeleavetypes'), 'et.id=l.leavetypeid',array('leavetype'=>'et.leavetype'))	
						   ->joinLeft(array('u'=>'main_users'), 'u.id=l.user_id',array('userfullname'=>'u.userfullname'))						   						 		   						   
						   ->where($where)
    					   ->order("$by $sort") 
    					   ->limitPage($pageNo, $perPage);
		//echo "<br/>".$employeeleaveData;// die;
		return $employeeleaveData;       		
	}
	
	public function updateemployeeleaves($appliedleavescount,$employeeid)
	{
		$db = Zend_Db_Table::getDefaultAdapter();
		$db->query("update main_employeeleaves  set used_leaves = used_leaves+".$appliedleavescount." where user_id = ".$employeeid." AND alloted_year = year(now()) AND isactive = 1 ");		
	
	}
	
	public function getUserID($id)
    {
    	$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leaverequest'),array('l.user_id'))
 	  				->where("l.isactive = 1 AND l.id = ".$id);
	//	echo "Result > ".$result ;die;			
    	return $this->fetchAll($result)->toArray();
    }
	
	public function getLeaveRequestDetails($id)
    {
    	$result =  $this->select()
    				->setIntegrityCheck(false) 	
    				->from(array('l'=>'main_leaverequest'),array('l.*'))
 	  				->where("l.isactive = 1 AND l.id = ".$id);
	//	echo "Result > ".$result ;die;			
    	return $this->fetchAll($result)->toArray();
    }
	
	public function checkdateexists($from_date, $to_date,$loginUserId)
	{
	    $db = Zend_Db_Table::getDefaultAdapter();
        //echo "select count(*) as dateexist from main_leaverequest l where l.isactive = 1 and l.user_id=".$loginUserId." and l.leavestatus IN(1,2) and 
		//('".$from_date."' between l.from_date and l.to_date or '".$to_date."' between l.from_date and l.to_date)";exit;
		
		
        //$query = "select count(l.id) as dateexist from main_leaverequest l where l.isactive = 1 and l.user_id=".$loginUserId." and l.leavestatus IN(1,2) and 
		//('".$from_date."' between l.from_date and l.to_date or '".$to_date."' between l.from_date and l.to_date)";
		
		$query = "select count(l.id) as dateexist from main_leaverequest l where l.user_id=".$loginUserId." and l.leavestatus IN(1,2) and l.isactive = 1
        and (l.from_date between '".$from_date."' and '".$to_date."' OR l.to_date between '".$from_date."' and '".$to_date."' )";
		
        $result = $db->query($query)->fetchAll();
	    return $result;
	
	}
	
	/* This function is common to manager employee leaves, employee leaves , approved,cancel,pending and rejected leaves
       Here differentiation is done based on objname. 
    */	   
	public function getGrid($sort,$by,$perPage,$pageNo,$searchData,$call,$dashboardcall,$objName,$queryflag,$unitId='',$statusidstring='')
	{	
        $auth = Zend_Auth::getInstance();
     	if($auth->hasIdentity()){
					$loginUserId = $auth->getStorage()->read()->id;
		}	
        $searchQuery = '';
        $searchArray = array();
        $data = array();
		if($objName == 'manageremployeevacations')
		{
		       if($searchData != '' && $searchData!='undefined')
				{
					$searchValues = json_decode($searchData);
					foreach($searchValues as $key => $val)
					{
						if($key == 'applieddate')
						 $searchQuery .= " l.createddate like '%".  sapp_Global::change_date($val,'database')."%' AND ";	
						else if($key == 'from_date' || $key == 'to_date')
						{
							$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
						} 
						else 
						  $searchQuery .= " ".$key." like '%".$val."%' AND ";
						$searchArray[$key] = $val;
					}
					$searchQuery = rtrim($searchQuery," AND");					
				}
				
				$tableFields = array('action'=>'Action','userfullname' => 'Employee name','leavetype' => 'Leave type',
                    'leaveday' => 'Leave duration','from_date' => 'From date','to_date' => 'To date','reason' => 'Reason',
                    'leavestatus' => 'Status','appliedleavescount' => 'Leave count','applieddate' => 'Applied On');
		
		        $leave_arr = array('' => 'All',1 =>'Full Day',2 => 'Half Day');

                $tablecontent = $this->getEmployeeLeaveRequest($sort, $by, $pageNo, $perPage,$searchQuery,$loginUserId);     				
				$dataTmp = array(
					'sort' => $sort,
					'by' => $by,
					'pageNo' => $pageNo,
					'perPage' => $perPage,				
					'tablecontent' => $tablecontent,
					'objectname' => $objName,
					'extra' => array(),
					'tableheader' => $tableFields,
					'jsGridFnName' => 'getAjaxgridData',
					'jsFillFnName' => '',
					'searchArray' => $searchArray,
					'add' =>'add',
					'call'=>$call,
					'dashboardcall'=>$dashboardcall,
								'search_filters' => array(
									'from_date' =>array('type'=>'datepicker'),
									'to_date' =>array('type'=>'datepicker'),
									'applieddate'=>array('type'=>'datepicker'),
									'leaveday' => array(
										'type' => 'select',
										'filter_data' => $leave_arr,
									),
								)
				);
		}
		else if($objName == 'empleavesummary')
		{
		        $managerstring= "true";
				 
		         if($searchData != '' && $searchData!='undefined')
					{
						$searchValues = json_decode($searchData);
						foreach($searchValues as $key => $val)
						{
						  if($key !='leavestatus')
						  {
							if($key == 'reportingmanagername')
							 $searchQuery .= " u.userfullname like '%".$val."%' AND ";
							else if($key == 'employeename')
							 $searchQuery .= " mu.userfullname like '%".$val."%' AND "; 
							else if($key == 'applieddate')
							{
							$searchQuery .= " l.createddate  like '%".  sapp_Global::change_date($val,'database')."%' AND ";
							}
							else if($key == 'from_date' || $key == 'to_date')
							{
								$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
							}
							else 
							 $searchQuery .= " ".$key." like '%".$val."%' AND ";
							
							}
							$searchArray[$key] = $val;
						}
						$searchQuery = rtrim($searchQuery," AND");					
					}
					
				    $statusid = '';				
			        if($queryflag !='')
					{
					   $statusid = $queryflag;
						   if($statusid == 1)
							  $queryflag = 'pending';
						   else if($statusid == 2)
							  $queryflag = 'approved'; 
						   else if($statusid == 3)
							  $queryflag = 'rejected'; 
						   else if($statusid == 4)
							  $queryflag = 'cancel'; 
					}
					else
					{
						$queryflag = 'approved';
					}
					

            $tableFields = array('action'=>'Action','employeename' => 'Leave applied by','leavetype' => 'Leave type','leaveday' => 'Leave duration','from_date' => 'From date','to_date' => 'To date','reason' => 'Reason','reportingmanagername'=>'Reporting Manager','appliedleavescount' => 'Leave count','applieddate' => 'Applied On');						 
				 
			$leave_arr = array('' => 'All',1 =>'Full Day',2 => 'Half Day');	 
			
			$search_filters = array(
										'from_date' =>array('type'=>'datepicker'),
										'to_date' =>array('type'=>'datepicker'),
										'applieddate'=>array('type'=>'datepicker'),
										'leaveday' => array(
															'type' => 'select',
															'filter_data' => $leave_arr,
														),
										);
										
			
            /* This is for dashboard call.
               Here one additional column Status is build by passing it to table fields
            */ 			   
			if($dashboardcall == 'Yes')
            {
					$tableFields['leavestatus'] = "Status";
					$search_filters['leavestatus'] = array(
					'type' => 'select',
					'filter_data' => array('pending' => 'Pending for approval','approved'=>'Approved','rejected'=>'Rejected','cancel'=>'Cancelled',),
				);
				if(isset($searchArray['leavestatus']))
				{
					$queryflag = $searchArray['leavestatus'];
					 if($queryflag =='')
					 {
						$queryflag = 'pending';
					 }	
				}
				
			}
			
			$tablecontent = $this->getLeaveStatusHistory($sort, $by, $pageNo, $perPage,$searchQuery,$queryflag,$loginUserId,$managerstring);    
			
			
			if(isset($queryflag) && $queryflag != '') 
		      $formgrid = 'true';
			else 
		      $formgrid = '';  
			  
			$dataTmp = array(
				'sort' => $sort,
				'by' => $by,
				'pageNo' => $pageNo,
				'perPage' => $perPage,				
				'tablecontent' => $tablecontent,
				'objectname' => $objName,
				'extra' => array(),
				'tableheader' => $tableFields,
				'jsGridFnName' => 'getAjaxgridData',
				'jsFillFnName' => '',
				'searchArray' => $searchArray,
				'add' =>'add',
				'formgrid' => $formgrid,
				'unitId'=>sapp_Global::_encrypt($statusid),
				'call'=>$call,
				'dashboardcall'=>$dashboardcall,
				'search_filters' => $search_filters
			);
		//echo "<pre>";print_r($dataTmp);exit();
		}
		else
		{
				if($searchData != '' && $searchData!='undefined')
					{
						$searchValues = json_decode($searchData);
						foreach($searchValues as $key => $val)
						{
							if($key == 'reportingmanagername')
							 $searchQuery .= " u.userfullname like '%".$val."%' AND ";					
							else if($key == 'applieddate')
							{
								//$searchQuery .= " DATE_FORMAT(l.createddate,'%m-%d-%Y') like '%".$val."%' AND "; 
								$searchQuery .= " l.createddate  like '%".  sapp_Global::change_date($val,'database')."%' AND ";
							}
							else if($key == 'from_date' || $key == 'to_date')
							{
								$searchQuery .= " ".$key." like '%".  sapp_Global::change_date($val,'database')."%' AND ";
							}
							else 
							 $searchQuery .= " ".$key." like '%".$val."%' AND ";
							$searchArray[$key] = $val;
						}
						$searchQuery = rtrim($searchQuery," AND");					
					}
				
				$tableFields = array('action'=>'Action','leavetype' => 'Leave type','leaveday' => 'Leave duration',
							'from_date' => 'From date','to_date' => 'To date','reason' => 'Reason',
							"reportingmanagername"=>"Reporting Manager",'appliedleavescount' => 'Leave count',
							'applieddate' => 'Applied On');
				$leave_arr = array('' => 'All',1 =>'Full Day',2 => 'Half Day');	
				
				$tablecontent = $this->getLeaveStatusHistory($sort, $by, $pageNo, $perPage,$searchQuery,$queryflag,$loginUserId);    
				
				$dataTmp = array(
					'sort' => $sort,
					'by' => $by,
					'pageNo' => $pageNo,
					'perPage' => $perPage,				
					'tablecontent' => $tablecontent,
					'objectname' => $objName,
					'extra' => array(),
					'tableheader' => $tableFields,
					'jsGridFnName' => 'getAjaxgridData',
					'jsFillFnName' => '',
					'searchArray' => $searchArray,
					'add' =>'add',
					'call'=> $call,
					'dashboardcall'=>$dashboardcall,
					'search_filters' => array(
									'from_date' =>array('type'=>'datepicker'),
									'to_date' =>array('type'=>'datepicker'),
									'applieddate'=>array('type'=>'datepicker'),
									'leaveday' => array(
										'type' => 'select',
										'filter_data' => $leave_arr,
									),
								)
				);
        }
		
		return $dataTmp;
	}
}