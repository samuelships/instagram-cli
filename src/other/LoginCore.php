<?php
namespace App\Other;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoginCore {
    public $username = "";
    public $password = "";

    /**
     * Get user credentials for login 
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function getUserCredentials(InputInterface $input, OutputInterface $output) {

        $io = new SymfonyStyle($input, $output);

        //GET USERNAME AND PASSWORD
        $io = new SymfonyStyle($input, $output);
        $io->ask('What is your username', "", function ($username) {
            $this->username = $username;
        });
        $io->askHidden('What is your password', function ($password) {
            $this->password = $password;
        });
    }

    /**
     * Checks for available credentials
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function validateCredentials($input, $output) {
        
        if ($this->username == null || $this->password == null) {
            $output->writeln('<error>Username or Password Required</error>');
            exit();
        }
    }

    /**
     * Checks for available credentials
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function login($input, $output) {
        
        // TRY TO LOGIN
        $output->writeln("Loggin In...");
        $ig = new \InstagramAPI\Instagram(false, false);

        try {
            $ig->login($this->username, $this->password);
            return $ig;
        } catch (\Exception $e) {
            $output->writeln('<error>Something Went Wrong</error>');
            exit();
        }
    }


}