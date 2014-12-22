<?php
namespace MSTS\PTSBundle\Entity;

use MSTS\PTSBundle\Entity\PTSDatalog;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PTSUser
{
	protected $IDNum;
	protected $CardNum;
	protected $LName;
	protected $FName;
	protected $Active = "Y";
	protected $StatusDate;
	protected $RecNo = 0;

	public function getUser($which){
		// returns a user object
		$where['recno'] = $which;
		$eUsers = new PTSDatalog();
		$eUser = $eUsers->getUserRecords($where);
		// echo var_dump($eUser);
		// set protected values from associative array
		$this->setIDNum($eUser[0]['IDNum']);
		$this->setCardNum($eUser[0]['CardNum']);
		$this->setLName($eUser[0]['LName']);
		$this->setFName($eUser[0]['FName']);
		$this->setActive($eUser[0]['Active']);
		$this->setRecNo($eUser[0]['RecNo']);
		$this->setLastEvent($eUser[0]['LastEvent']);
		$this->setStatusDate($eUser[0]['StatusDate']);
		
		return $this;
	}
	public function saveUser(){
		// saves current record to database either by add or update
		$where['LName'] = $this->getLName();
		$where['FName'] = $this->getFName();
		$where['IDNum'] = $this->getIDNum();
		$where['CardNum'] = $this->getCardNum();
		$where['Active'] = $this->getActive();
		$where['RecNo'] = $this->getRecNo();
		echo " saveuser:".var_dump($where['Active']);
	
		$eUsers = new PTSDatalog();
		return $success = $eUsers->addUserRecord($where);	
	}
	
	public function getIDNum() {
		return $this->IDNum;
	}
	public function setIDNum($IDNum) {
		$this->IDNum = $IDNum;
	}

	public function getCardNum() {
		return $this->CardNum;
	}
	public function setCardNum($CardNum) {
		$this->CardNum = $CardNum;
	}

	public function getLName() {
		return $this->LName;
	}
	public function setLName($LName) {
		$this->LName = $LName;
	}

	public function getFName() {
		return $this->FName;
	}
	public function setFName($FName) {
		$this->FName = $FName;
	}

	public function getActive() {
		return $this->Active;
	}
	public function setActive($Active) {
		// echo " Active:".var_dump($Active);
		$this->Active = $Active; // == 'N' ? FALSE : TRUE;
	}

	public function getStatusDate() {
		return $this->StatusDate;
	}
	public function setStatusDate($StatusDate) {
		$this->StatusDate = $StatusDate;
	}

	public function getLastEvent() {
		return $this->LastEvent;
	}
	public function setLastEvent($LastEvent) {
		$this->LastEvent = $LastEvent;
	}

	public function getRecNo() {
		return $this->RecNo;
	}
	public function setRecNo($RecNo) {
		$this->RecNo = $RecNo;
	}
// ...
//class Document
//{
    
    private $file;

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }
//}


}

