<?php
namespace KGC\ClientBundle\Entity;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\File;

class SmartFocus
{
	protected $login    = 'kg_api';
	protected $password = '#1oc7q2MoK8v95mKfI6TmEATZj3mz3MM1';
	protected $apiKey   = 'CdX7CrpN6m6OgUk3e-0ZmrSxVyULYt62ygjsATuVqXDhxg';
	protected $apiKeyBatch   = 'CdX7CrpN6m6OgUk3e-0ZmrSxVyULYt62ygjs0Oh7NXDhoA';
	protected $wsdl     = 'https://emvapi.emv3.com/apimember/services/MemberService?wsdl';
	protected $wsdlBatch     = 'https://emvapi.emv3.com/apibatchmember/services/BatchMemberService?wsdl';
	protected $token;
	protected $tokenBatch;
	protected $jobId;
	protected $em;
	protected $connection;
	public $service;
	public $serviceBatch;
	const STATUS_OK     = 'Job_Done_Or_Does_Not_Exist';
	const STATUS_ERROR  = 'Error';
	const TABLE_TASKS   = 'emv_tasks';
	const TABLE_USERS   = 'landing_user';

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->connection = $this->em->getConnection();
		$this->connection->getConfiguration()->setSQLLogger(null);
		$this->service = new \SoapClient($this->wsdl, array(
			// Stuff for development.
			'trace' => 1,
			'exceptions' => true,
			'cache_wsdl' => WSDL_CACHE_NONE
		));

		try
		{
			$token = $this->service->openApiConnection(array('login'=>$this->login, 'pwd'=>$this->password, 'key' => $this->apiKey));
			$this->token = $token->return;
		}
		catch(\SoapFault $e){
			$this->error($e);
			return false;
		}
	}

	public function descMemberTable()
	{
		try
		{
			return $this->service->descMemberTable(array('token'=>$this->token))->return->fields;
		}
		catch (\SoapFault $e)
		{
			$this->error($e);
			return false;
		}
	}

	public function getMemberByEmail($email)
	{
		if (!$email)
			return;
		try
		{
			$params = new \SoapVar("<ns1:getListMembersByObj><token>".$this->token."</token><member><memberUID>email:".$email."</memberUID></member></ns1:getListMembersByObj>", XSD_ANYXML);
			$result = $this->service->getListMembersByObj($params)->return->attributes->entry;
			return $result;
		}
		catch (\SoapFault $e)
		{
			$this->error($e);
			return false;
		}
	}

	public function getMemberByObj($infos)
	{
		$entry = array();
		foreach($infos as $key => $value){
			$entry[] = $key.':'.$value;
		}
		try
		{
			$params = new \SoapVar("<ns1:getListMembersByObj><token>".$this->token."</token><member><memberUID>".join("|", $entry)."</memberUID></member></ns1:getListMembersByObj>", XSD_ANYXML);
			$result = $this->service->getListMembersByObj($params)->return->attributes->entry;
			return $result;
		}
		catch (\SoapFault $e)
		{
			$this->error($e);
			return false;
		}
	}

	public function getMemberById($idMember)
	{
		try
		{
			$res =  $this->service->getMemberById(array('token'=>$this->token,'id' => $idMember))->return->attributes->entry;
			return $res;
		}
		catch (\SoapFault $e)
		{
			$this->error($e);
			return false;
		}
	}

	public function insert($email, $kgestion_id, $infos)
	{
		$entry = array();
		foreach($infos as $key => $value){
			$entry[] = array('key' => $key, 'value' => $value);
		}

		try
		{
			$this->jobId = $this->service->insertMemberByObj(array('token'=>$this->token, 'member'=>array('memberUID'=>1157720366828, 'email' => $email,'dynContent'=>$entry)))->return;
			$this->addTask($email, $kgestion_id);
			return $this->jobId;
		}
		catch (\SoapFault $e)
		{
			$this->error($e);
			return false;
		}
	}

	public function updateClientMember($email, $infos)
	{
		$memberUID = 'email:'.$email.'|client:1';
		if($memberUID) {
			$entry = array();
			foreach($infos as $key => $value){
				$entry[] = "<entry><key>".addslashes(strtoupper($key))."</key><value>".addslashes($value)."</value></entry>";
			}
			try
			{
				$params = new \SoapVar("<ns1:insertOrUpdateMemberByObj><token>".$this->token."</token><member><dynContent>".join("|", $entry)."</dynContent><memberUID>".$memberUID."</memberUID></member></ns1:insertOrUpdateMemberByObj>", XSD_ANYXML);
				$result = $this->service->insertOrUpdateMemberByObj($params)->return;
				return $result;
			}
			catch (\SoapFault $e)
			{
				$this->error($e);
				return false;
			}
		}
	}

	public function updateMassClientsMembers($hearders, $arrayNames)
	{
		$entry = array();
		$idx = 1;
		foreach($hearders as $hearder){
			$entry[] = "<column><colNum>".$idx."</colNum><fieldName>".$hearder."</fieldName>".(($hearder == "DATEOFBIRTH")?"<dateFormat>dd/MM/yyyy</dateFormat>":"")."<toReplace>true</toReplace></column>";
			$idx++;
		}
		$this->serviceBatch = new MTOMSoapClient($this->wsdlBatch, array(
			// Stuff for development.
			'trace' => 1,
			'exceptions' => true,
			'cache_wsdl' => WSDL_CACHE_NONE
		));

		try
		{
			$tokenBatch = $this->serviceBatch->openApiConnection(array('login'=>$this->login, 'pwd'=>$this->password, 'key' => $this->apiKeyBatch));
			$this->tokenBatch = $tokenBatch->return;
			foreach ($arrayNames as $shortName => $file_name) {
				$contentCsv = file_get_contents($file_name);
				$params = new \SoapVar("<ns1:uploadFileMerge><token>".$this->tokenBatch."</token><file>".base64_encode($contentCsv)."</file><mergeUpload><fileName>".$shortName."</fileName><fileEncoding>UTF-8</fileEncoding><separator>;</separator><skipFirstLine>true</skipFirstLine><dateFormat>dd/MM/yyyy</dateFormat><criteria>LOWER(EMAIL),CLIENT</criteria><mapping>".join("", $entry)."</mapping></mergeUpload></ns1:uploadFileMerge>", XSD_ANYXML);
				$result = $this->serviceBatch->uploadFileMerge($params)->return;
			}
			return $result;
		}
		catch(\SoapFault $e){
			$this->error($e);
			return false;
		}
	}

	public function getStatus($job)
	{
		try{
			$response = $this->service->getMemberJobStatus(array('token'=>$this->token, 'synchroId'=>$job))->return->status;
			return $response;
		} catch(Exception $e) {
			$response = self::STATUS_ERROR;
			return $response;
		}
	}

	public function is_ok($job)
	{
		return ( $this->getStatus($job) == self::STATUS_OK) ? true : false;
	}

	private function error($e)
	{
		return;
	}

	public function addTask($email, $kgestion_id, $update = 0)
	{
		$connection = $this->em->getConnection();
		$connection->getConfiguration()->setSQLLogger(null);
		$connection->executeQuery('INSERT INTO `'.self::TABLE_TASKS.'` (`jobId`, `email`, `date`, `kgestion`, `update`) VALUES ('.$this->jobId.', \''.addslashes($email).'\', \''.date('Y-m-d H:i:s').'\', '.$kgestion_id.', '.$update.')');
	}

	public function processTask()
	{
		$connection = $this->connection;
		$query =  'SELECT * FROM '.self::TABLE_TASKS;
		$results = $connection->executeQuery($query);
		foreach ($results->fetchAll() as $res){
			if ($this->is_ok($res['jobId'])){
				$this->deleteTask($res['jobId']);
			} else {
				if ($this->getStatus($res['jobId']) == self::STATUS_ERROR){
					$this->renewInsert($res['email']);
					$this->deleteTask($res['jobId']);
				}
			}
		}
		die('OK');
	}

	public function deleteTask($jobId){
		$connection = $this->connection;
		$query =  'DELETE FROM '.self::TABLE_TASKS.' WHERE jobId ="'.$jobId.'"';
		return $connection->executeQuery($query);
	}

	public function renewInsert($email){
		$connection = $this->connection;
		$query =  'SELECT * FROM '.self::TABLE_USERS.' WHERE  email = "'.$email.'" LIMIT 0,1';
		$result = $connection->executeQuery($query);
		foreach ($result->fetchAll() as $user){
			$infos = array(
				'DATEJOIN'        => substr($user['createdAt'],0,10),
				'DATEMODIF'       => date('m/d/Y'),
				'SOURCE'          => $user['myastroSource'],
				'CLIENTURN'       => $user['questionContent'],
				'DATEOFBIRTH'     => $user['birthday'],
				'SEED3'           => $user['sign'],
				'FIRSTNAME'       => $user['firstName'],
				'EMVCELLPHONE'    => intval($user['phone']),
				'NUMEROTELEPHONE' => $user['phone'],
				'TITLE'           => $user['gender'],
				'CODE'            => base_convert($user['myastroId'], 10, 32),
				'FIRSTNAME2'      => ( isset($user['spouseName']) ) ? $user['spouseName'] : '',
			);

			$entry = array();
			foreach($infos as $key => $value){
				$entry[] = array('key' => $key, 'value' => $value);
			}

			try {
				$this->jobId = $this->service->insertMemberByObj(array('token'=>$this->token, 'member'=>array('email' => $email,'dynContent'=>$entry)))->return;
				$this->addTask($email);
				return true;
			} catch (\SoapFault $e){
				$this->error($e);
			}
		}
	}

}