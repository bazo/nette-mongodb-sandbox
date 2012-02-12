<?php
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
/**
 * Description of User
 *
 * @author Martin
 * @ODM\Document
 * 
 */
class User implements Nette\Security\IIdentity
{
	private 
		/** 
		 * @ODM\Id 
		 */	
		$id,
		
		/**
		 * @ODM\String
		 * @ODM\Index(unique=true) 
		 */	
		$login,
		
		/**
		 * @ODM\String 
		 */	
		$password
	;
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getRoles()
	{
		return array(
			'user'
		);
	}
	
	public function getLogin()
	{
		return $this->login;
	}

	public function setLogin($login)
	{
		$this->login = $login;
		return $this;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function setPassword($password)
	{
		$this->password = $password;
		return $this;
	}
}