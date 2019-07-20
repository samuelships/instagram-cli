<?php
namespace App\Command;

use \App\Command\Functions;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

class UnfollowCommand extends Command
{   
    use Functions;

    /** @var Username */
    protected $username = "thereelgwen";

    /** @var Password */
    protected $password = "gwen2000";

    /** @var WhiteList */
    protected $whiteList = array();

    /** @var userListFile */
    protected $userListFile;

    /** @var fileOutput */
    protected $fileOutput;

    /** @var pauseAfter */
    protected $pauseAfter;

    /** @var pauseAfter */
    protected $likeMedia;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = "unfollow";

    protected function configure()
    {
        $this->setDescription("Unfollows users not following you.")
            ->setHelp("This commands allow you to unfollow all users not following you back")
            ->setDefinition(
                new InputDefinition([
                    new InputOption("user_list_file", "u", InputOption::VALUE_OPTIONAL, "List of users to follow"),
                    new InputOption("file_output", "o", InputOption::VALUE_OPTIONAL, "New output to dump to"),
                    new InputOption("pause_after", "p", InputOption::VALUE_OPTIONAL, "Number of iterations to pause after", 5),
                ])
                
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {   
        // PROCESS OPTIONS
        $io = new SymfonyStyle($input, $output);
        $this->processFileOutput($input, $io);
        $this->processUserListFile($input, $io);
        $this->processPauseAfter($input, $io);

        //GET USERNAME AND PASSWORD  
        $io = new SymfonyStyle($input, $output);

        $io->ask('What is your username', "", function ($username) {
            $this->username = $username;
        });

        $io->askHidden('What is your password', function ($password) {
            $this->password = $password;
        });


        // CHECK FOR REQUIRED OPTIONS
        if ($this->username == null || $this->password == null) {
            $output->writeln('<error>Username or Password Required</error>');
            exit();
        }

        
        // TRY TO LOGIN
        $output->writeln("Loggin In...");
        $ig = new \InstagramAPI\Instagram(false, false);

        try {
            $ig->login($this->username, $this->password);
        } catch (\Exception $e) {
            $output->writeln('<error>Something Went Wrong</error>');
            exit();
        }

        $output->writeln('<fg=green>Logged In</>');

        // START    
        
        # load user list file
        $userListFile = fopen($this->userListFile, "r");
        $userListArray = json_decode(fread($userListFile, filesize($this->userListFile)), TRUE);
        $mutableArray = $userListArray;

        # iterate and follow

        $io = new SymfonyStyle($input, $output);
        $io->title("Starting To Unfollow " . count($userListArray) . " Users");

        $counter = 1;
        $unfollowCounter = 0;

        foreach ($userListArray as $userId => $username) {
           
            $output->writeln("$counter . $username ----------");
            try {

                # get user media and put in array
                $output->writeln("User ID : $userId");
                

                # follow user - check counter
                if ($unfollowCounter < $this->pauseAfter) {
                    $output->writeln("Unfollowing user..");
                    if ($ig->people->unfollow($userId)) {
                        $output->writeln("<fg=green>Unfollowed - $username</>");
                        $unfollowCounter++;
                        $output->writeln("Sleeping...");
                        sleep(rand(1,2));
                        $output->writeln("Waking Up...");
                    }

                }else {
                    $output->writeln("<fg=yellow>Taking deep sleep...</>");
                    sleep(rand(20,30));
                    $output->writeln("<fg=green>Waking Up...</>");
                    $unfollowCounter = 0;
                }

            

                $counter++;
                $io->newLine();

                # write to new file
                $newFile = fopen($input->getOption("file_output"), "w");
                unset($mutableArray[$userId]);
                fwrite($newFile, json_encode($mutableArray));
                
            } catch (\Exception $e) {
                $output->writeln('<error>Something Went Wrong</error> '. $e->getMessage());
                exit();
               
            }

            
        }
    }

    

    /**
     * Process user list file
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     */
    public function processUserListFile($input, $io) {
        if ($input->getOption("user_list_file") == "") {
            $io->error("user_list_file required");
            exit();
        }

        $this->userListFile = $input->getOption("user_list_file");
    }

    /**
     * Process pause after
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     */
    public function processPauseAfter($input, $io) {
        if ($input->getOption("pause_after") != "") {
            $this->pauseAfter = $input->getOption("pause_after");
        }     
    }

    /**
     * Process like media
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     */
    public function processLikeMedia($input, $io) {
        if ($input->getOption("like_media")) {
            $this->likeMedia = $input->getOption("like_media");
        }     
    }

    /**
     * Process like media number
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     */
    public function processLikeMediaNumber($input, $io) {
        if ($input->getOption("like_media_number")) {
            $this->likeMediaNumber = $input->getOption("like_media_number");
        }     
    }

    
}