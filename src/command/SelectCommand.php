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

class SelectCommand extends Command
{   
    use Functions;

    /** @var Username */
    protected $username = "";

    /** @var Password */
    protected $password = "";

    /** @var WhiteList */
    protected $whiteList = array();

    /** @var LikersMediaCode */
    protected $likersMediaCode;

    /** @var FileOutput */
    protected $fileOutput;

    /** @var SelectType */
    protected $selectType = "";

    /** @var NotInLikers */
    protected $notInLikers = "";

    /** @var NotInLikersMediaCode */
    protected $notInLikersMediaCode = "";

    

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'select';

    protected function configure()
    {
        $this->setDescription('Selects users - followers, following, likers.')
            ->setHelp('This command selects objects - users, likers which can be used for purely analytics, unfollowed or followed')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument("type", InputArgument::OPTIONAL, "Type of select - likers, followers, following"),

                    # general options
                    new InputOption("file_output", "o", InputOption::VALUE_OPTIONAL, "Output of the file"),

                    # select likers options
                    new InputOption("likers_media_code", "l", InputOption::VALUE_OPTIONAL, "Media code of of post to select likers from"),

                    # select following options
                    new InputOption("not_in_likers", "", InputOption::VALUE_OPTIONAL, "Select users not in likers"),
                    new InputOption("not_in_likers_media_code", "", InputOption::VALUE_OPTIONAL, "Media code of post to select not in likers from - pass all to select all your posts"),
                ])
                
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {   
        // SELECT TYPE VALIDATION
        $types = ["likers", "following"];
        if (!in_array($input->getArgument("type"), $types)) {
            $output->writeln("<error>Select type must be one of - ". implode("," , $types) ."</error>");
            exit();
        }

        # set select type
        $this->selectType = $input->getArgument("type");


        // PROCESS OPTIONS
        $io = new SymfonyStyle($input, $output);
        $this->processLikersMediaCode($input, $io);
        $this->processFileOutput($input, $io);
        $this->processNotInLikers($input, $io);
         
        //GET USERNAME AND PASSWORD
        $io = new SymfonyStyle($input, $output);
        $io->ask('What is your username', "", function ($username) {
            $this->username = $username;
        });
        $io->askHidden('What is your password', function ($password) {
            $this->password = $password;
        });


        // VALIDATION 
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
        
        // LIKERS 
        if ($this->selectType == "likers") {
            echo "Selecting likers.. \n";
            # convert likers_media_code to a media Id
            $mediaId = $this->getIdFromCode($this->likersMediaCode);
            
            # use getLikers function to get the likers of the media
            $users = $this->getMediaLikers($ig, $input, $output, $mediaId);
            
        }

        // FOLLOWING 
        if ($this->selectType == "following") {
            echo "following";
            $users = $this->getFollowingRaw($ig, $input, $output, $this->username);
          
        }


        // GENERAL FILTERS
        $usersArray = array();
        
        # get info needed for filtering...
        $info = [
            "likers" => [
                "all" => []
            ]
        ];

        # 1. --no_in_likers
        if ($this->notInLikers) {
            if ($this->notInLikersMediaCode == "all") {
                # get all my media
                $media = $this->getUserMedia($ig, $input, $output, $this->username); 

                foreach($media as $m) {
                    # get all likers and put in array
                    $likers = $this->getMediaLikers($ig, $input, $output, $m->getId());
                    foreach($likers as $liker) {
                        $info["likers"]["all"][$liker->getPk()] = $liker->getUsername();
                    }
                }  
            }
        }
 

        foreach($users as $user) {
            $addStatus = 1;

            # custom filters --not_in_likers --not_in_likers_media_code all

            # 1. --not_in_likers
            if ($this->notInLikers) {
                if (isset($info["likers"]["all"][$user->getPk()])) {
                    $addStatus = 0;
                }
            }

            if ($addStatus) {
                $usersArray[$user->getPk()] = $user->getUsername();
            }
            
        }

    
        // WRITE TO FILE
        $file = fopen($this->fileOutput, "w");
        fwrite($file, json_encode($usersArray));
        $output->writeln("<fg=green>DONE</>");

    }

     

    /**
     * Process a whitelist if available
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     */
    public function processLikersMediaCode($input, $io) {
        # check if likers media post is required
        if ($input->getArgument("type") == "likers" && $input->getOption("likers_media_code") == "") {
            $io->error("likers_media_code required");
            exit();
        }
        $this->likersMediaCode = $input->getOption("likers_media_code");
    }

    /**
     * Process not in likers filter
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     */
    public function processNotInLikers($input, $io) {
        
        if ($input->getOption("not_in_likers")) {
           
            # check if not_in_likers_media_code is absent
            if (!$input->getOption("not_in_likers_media_code")) {
                $io->error("not_in_likers_media_code required");
                exit();
            }

            $this->notInLikers = $input->getOption("not_in_likers");
            $this->notInLikersMediaCode = $input->getOption("not_in_likers_media_code");
        }
        
    }

    /**
     * Gets the id of the media using its code
     * @param string $code
     * @return string
     */
    public function getIdFromCode($code) {
        $alphabet = [
            '-' => 62, '1' => 53, '0' => 52, '3' => 55, '2' => 54, '5' => 57, '4' => 56, '7' => 59, '6' => 58, '9' => 61,
            '8' => 60, 'A' => 0, 'C' => 2, 'B' => 1, 'E' => 4, 'D' => 3, 'G' => 6, 'F' => 5, 'I' => 8, 'H' => 7,
            'K' => 10, 'J' => 9, 'M' => 12, 'L' => 11, 'O' => 14, 'N' => 13, 'Q' => 16, 'P' => 15, 'S' => 18, 'R' => 17,
            'U' => 20, 'T' => 19, 'W' => 22, 'V' => 21, 'Y' => 24, 'X' => 23, 'Z' => 25, '_' => 63, 'a' => 26, 'c' => 28,
            'b' => 27, 'e' => 30, 'd' => 29, 'g' => 32, 'f' => 31, 'i' => 34, 'h' => 33, 'k' => 36, 'j' => 35, 'm' => 38,
            'l' => 37, 'o' => 40, 'n' => 39, 'q' => 42, 'p' => 41, 's' => 44, 'r' => 43, 'u' => 46, 't' => 45, 'w' => 48,
            'v' => 47, 'y' => 50, 'x' => 49, 'z' => 51
        ];
        $n = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $c = $code[$i];
            $n = $n * 64 + $alphabet[$c];
        }
        return $n;
    }

    
}