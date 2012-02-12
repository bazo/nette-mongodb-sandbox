<?php
use Nette\Security as NS;
/**
 * Users authenticator.
 *
 * @author     Martin Bazik
 */
class Authenticator extends Nette\Object implements NS\IAuthenticator
{
	private 
		/** @var Doctrine\ODM\MongoDB\DocumentRepository */	
		$usersRepository,
			
		/** @var \Security\PasswordHasher */	
		$passwordHasher
	;

	public function __construct(Doctrine\ODM\MongoDB\DocumentRepository $usersRepository)
	{
		$this->usersRepository = $usersRepository;
		$this->passwordHasher = new \Security\PasswordHasher;
	}

	/**
	 * Performs an authentication
	 * @param  array
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($login, $password) = $credentials;
		$user = $this->usersRepository->findOneBy(array('login' => $login));

		if (!$user or !$this->passwordHasher->checkPassword($password, $user->getPassword())) 
		{
			throw new NS\AuthenticationException("Invalid credentials", self::FAILURE);
		}

		return $user;
	}
}
