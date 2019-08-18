<?php
namespace App\Command;

use \App\Command\Functions;
use \App\Other\LoginCore;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

class UnfollowNonFollowersCommand extends Command
{   
    use Functions;

    /** @var Username */
    protected $username = "";

    /** @var Password */
    protected $password = "";

    /** @var WhiteList */
    protected $whiteList = array();

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'unfollow-nonfollowers';

    protected function configure()
    {
        $this->setDescription('Unfollows users not following you.')
            ->setHelp('This commands allow you to unfollow all users not following you back')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('white_list', 'w', InputOption::VALUE_OPTIONAL),
                    new InputOption('white_list_from_file', 'W', InputOption::VALUE_OPTIONAL),
                    new inputArgument('hello', InputArgument::OPTIONAL, "yo")
                ])
                
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {   
       
    
        // PROCESS OPTIONS
        $this->processWhiteList($input);

        // LOGIN
        $loginHandler = new LoginCore();
        $loginHandler->getUserCredentials($input, $output);
        $loginHandler->validateCredentials($input, $output);
        $loginHandler->login($input, $output);
        $ig = $loginHandler->login($input, $output);

        // GET ALL MY FOLLOWERS
        $followersArray = $this->getFollowers($ig, $input, $output, $this->username);
        

        // GET ALL MY FOLLOWING
        $followingArray = $this->getFollowing($ig, $input, $output, $this->username);
      

        // GET THOSE NOT FOLLOWING YOU
        $notFollowingArray = $this->getNotFollowing($followersArray, $followingArray);

        // START THE UNFOLLOWING PROCESS 
        $this->unfollowUsers($ig, $input, $output, $notFollowingArray, $this->whiteList);

    }

    

    

    /**
     * Gets the list of users who are not follow you
     * @param array $followersArray An array of your followers  
     * @param array $follwingArray An array of people you are following
     * 
     * @return array 
     */
    public function getNotFollowing($followersArray, $followingArray) {
      
        $notFollowing = [];

        foreach($followingArray as $key => $value) {
            if (!isset($followersArray[$key])) {
                $notFollowing[$key] = $value;
            }    
        }

        return $notFollowing;
    }

    
}