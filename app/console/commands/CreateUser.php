<?php
namespace Console\Command;

use Symfony\Component\Console\Input\InputArgument,
	Symfony\Component\Console\Input\InputOption,
	Symfony\Component\Console;

/**
 * Description of CreateUser
 *
 * @author Martin
 */
class CreateUser extends Console\Command\Command
{

	protected function configure()
	{
		$this
				->setName('app:user:create')
				->setDescription('Creates a user')
				->addArgument('login', InputArgument::OPTIONAL, 'login?')
				->addArgument('password', InputArgument::OPTIONAL, 'password?')
		;
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$output->writeln('creating new user...');
		$dm = $this->getHelper('dm')->getDocumentManager();

		$dialog = $this->getHelperSet()->get('dialog');
		
		$login = $input->getArgument('login');
		$password = $input->getArgument('password');
		
		if($login === null)
		{
			$login = $dialog->ask($output, '<question>please provide login for the new user: </question>', null);
			
			if($login === null)
			{
				$output->writeln('<error>you have to provide login. aborting.</error>');
				return;
			}
			
			$password = $dialog->ask($output, '<question>please provide password for the user '.$login.': </question>', null);
			
			if($password === null)
			{
				$output->writeln('<error>you have to provide password. aborting.</error>');
				return;
			}
		}
		
		if($login !== null and $password === null)
		{
			$password = $dialog->ask($output, '<question>please provide password for the user '.$login.': </question>', null);
			if($password === null)
			{
				$output->writeln('<error>you have to provide password. aborting.</error>');
				return;
			}
		}
		
		$passwordHasher = new \Security\PasswordHasher;
		
		if($dm->getRepository('User')->findOneByLogin($login) !== null)
		{
			$output->writeln('<error>user with login '.$login.' already exists. aborting.</error>');
			return;
		}
		$user = new \User;
		$user->setLogin($login)->setPassword($passwordHasher->hashPassword($password));
		
		$dm->persist($user);
		//$dm->flush(array('safe' => true)); //throws some bullshit error, thus checking by finding by login
		$dm->flush();
		$output->writeln('<info>user '.$login.' succesfully created</info>');
	}

}