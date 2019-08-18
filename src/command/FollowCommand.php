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

class FollowCommand extends Command
{   
    use Functions;

    /** @var Username */
    protected $username = "";

    /** @var Password */
    protected $password = "";

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

    /** @var TrackLikedMedia */
    protected $trackLikedMedia = "";

    // the name of the command (the part after "bin/console")
    protected static $defaultName = "follow";

    protected function configure()
    {
        $this->setDescription("Unfollows users not following you.")
            ->setHelp("This commands allow you to unfollow all users not following you back")
            ->setDefinition(
                new InputDefinition([
                    new InputOption("user_list_file", "u", InputOption::VALUE_OPTIONAL, "List of users to follow"),
                    new InputOption("file_output", "o", InputOption::VALUE_OPTIONAL, "New output to dump to"),
                    new InputOption("pause_after", "p", InputOption::VALUE_OPTIONAL, "Number of iterations to pause after", 5),
                    new InputOption("like_media", "l", InputOption::VALUE_OPTIONAL, "Like media of user after you follow"),
                    new InputOption("like_media_number", "ln", InputOption::VALUE_OPTIONAL, "Number of media to like", 5),
                    new InputOption("track_liked_media", "tl", InputOption::VALUE_OPTIONAL, "Track media you've liked")
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
        $this->processLikeMedia($input, $io);
        $this->processTrackLikedMedia($input, $io);

        // LOGIN
        $loginHandler = new LoginCore();
        $loginHandler->getUserCredentials($input, $output);
        $loginHandler->validateCredentials($input, $output);
        $loginHandler->login($input, $output);
        $ig = $loginHandler->login($input, $output);

        // START    
        # get following list
        $followingArray = $this->getFollowing($ig, $input, $output, $this->username);

        # load user list file
        $userListFile = fopen($this->userListFile, "r");
        $userListArray = json_decode(fread($userListFile, filesize($this->userListFile)), TRUE);
        $mutableArray = $userListArray;

        # iterate and follow

        $io = new SymfonyStyle($input, $output);
        $io->title("Starting To Follow Users");

        $counter = 1;
        $followCounter = 0;

        foreach ($userListArray as $userId => $username) {
            $likeCounter = 1;
            
            if (isset($followingArray[$username])) {

                # write to new file
                $newFile = fopen($input->getOption("file_output"), "w");
                unset($mutableArray[$userId]);
                fwrite($newFile, json_encode($mutableArray));

                $output->writeln("$counter . Already following $username");
                $counter++;
                continue;
            }

            $output->writeln("$counter . $username ----------");
            try {

                # get user media and put in array
                $output->writeln("getting user id...");
                $userId = $ig->people->getUserIdForName($username);
                $output->writeln("User ID : $userId");
                $feed = $ig->timeline->getUserFeed($userId);
                $feedItems = array();

                # check if like media is on
                if ($feed->hasItems($userId) && $this->likeMedia) {
                    foreach($feed->getItems() as $f) {
                        array_push($feedItems, $f->getId());
                    }
                }

                # follow user - check counter
                if ($followCounter < $this->pauseAfter) {
                    $output->writeln("Follwing user..");
                    if ($ig->people->follow($userId)) {
                        $output->writeln("<fg=green>Followed - $username</>");
                        $followCounter++;
                        $output->writeln("Sleeping...");
                        sleep(rand(1,2));
                        $output->writeln("Waking Up...");
                    }

                }else {
                    $output->writeln("<fg=yellow>Taking deep sleep...</>");
                    sleep(rand(20,30));
                    $output->writeln("<fg=green>Waking Up...</>");
                    $followCounter = 0;
                }

                # traverse array and like
                if (!empty($feedItems)) {
                    $output->writeln("Liking media..");
                    foreach ($feedItems as $f) {
                        if ($likeCounter > $this->likeMedia){
                            $output->writeln("<fg=yellow>Media liking exceded</>");
                            break;
                        }else {
                            if ($ig->media->like($f)) {

                                # check and track liked media
                                if ($this->trackLikedMedia) {
                                    $this->appendLikedMedia($f, $this->trackLikedMedia);
                                }

                                $output->writeln("<fg=green>Liked Media - $likeCounter</>");
                                $likeCounter++;
                                $output->writeln("Sleeping...");
                                sleep(rand(1,2));
                                $output->writeln("Waking Up...");
                            }
                        }

                        
                    }
                }

                # sleep for sometime
                $output->writeln("Sleeping...");
                sleep(rand(4,7));
                $output->writeln("Waking Up...");
                $counter++;
                $io->newLine();

                # write to new file
                $newFile = fopen($input->getOption("file_output"), "w");
                unset($mutableArray[$userId]);
                fwrite($newFile, json_encode($mutableArray));
                
            } catch (\Exception $e) {
                $output->writeln('<error>Something Went Wrong</error> '. $e->getMessage());
                if ($e->getMessage() == "InstagramAPI\Response\UserFeedResponse: Not authorized to view user.") {
                    
                }elseif($e->getMessage() == "InstagramAPI\Response\UserInfoResponse: User not found.") {

                }else {
                    exit();
                }
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


    
}