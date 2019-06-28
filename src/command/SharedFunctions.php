<?php
namespace App\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

trait Functions {

    /**
     * Process a whitelist if available
     * @param Symfony\Component\Console\Input\InputOption $input
     */
    public function processWhiteList($input) {
        # inline
        if ($input->getOption("white_list") || $input->getOption("white_list_from_file")) {

            if ($input->getOption("white_list")) {
                $whiteListArray = explode(",", $input->getOption("white_list"));
            }
    
            # file
            if ($input->getOption("white_list_from_file")) {
                $files = file_get_contents("whitelist.txt");
                $whiteListArray = explode(",", $files);
            }

            $this->whiteList = $whiteListArray;
        }
    }

    /**
     * Process the path to write file to
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Style\SymfonyStyle $io
     */
    public function processFileOutput($input, $io) {
        # check if likers media post is required
        if ($input->getOption("file_output") == "") {
            $io->error("file_output required");
            exit();
        }

        $this->fileOutput = $input->getOption("file_output");
    }

    /**
     * Gets the list of users who follow you
     * @param \InstagramAPI\Instagram $ig 
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Output\OutputOption $ouput
     * @param string $username Username to get followers of
     * 
     * @return array 
     */
    public function getFollowers($ig, $input, $output, $username) {
        $io = new SymfonyStyle($input, $output);
        $io->title("Getting $username's Followers");

        $userId = $ig->people->getUserIdForName($username);

        //Generate a random rank token.
        $rankToken = \InstagramAPI\Signatures::generateUUID();

      
        // GET ALL FOLLOWERS
        # get follower count in order to display progress bar
        $output->writeln("Getting number of followers...");
        $followerCount = $ig->people->getInfoById($userId)->getUser()->getFollowerCount();
        $output->writeln("<fg=green>Followers - $followerCount</>");

        $output->writeln("Getting all $followerCount followers...");
        $progressBar = new ProgressBar($output, 100);
        $followersArray = [];
        $maxId = null;
        

        # calculate progress increment
        $increment = ceil($followerCount / 200);
        $realIncrement = 100 / $increment;

        $progressBar->start();
        do {
            $followers = $ig->people->getFollowers($userId,$rankToken, null, $maxId);
            $users = $followers->getUsers();

            foreach($users as $u) {
                $followersArray[$u->getUsername()] = $u->getPk();
            }
            
            $progressBar->advance($realIncrement);

            $maxId = $followers->getNextMaxId();

            if ($followers->getNextMaxId() == null) {
                $progressBar->finish();
            }
      
        }while($followers->getNextMaxId() !== null);

        $io->newLine();

        $output->writeln("<fg=green>All Followers Retrieved</>");

        return $followersArray;
    }

    /**
     * Gets the list of users who you are following
     * @param \InstagramAPI\Instagram $ig $ig 
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Output\OutputOption $ouput
     * 
     * @return array 
     */
    public function getFollowing($ig, $input, $output, $username) {
        $io = new SymfonyStyle($input, $output);
        $io->title("Getting $username's Following...");

        // Generate user id
        $userId = $ig->people->getUserIdForName($username);
        $output->writeln("Getting number of following...");
        
        $followingCount = $ig->people->getInfoById($userId)->getUser()->getFollowingCount();

        $output->writeln("<fg=green>Following - $followingCount</>");

        // Generate a random rank token.
        $rankToken = \InstagramAPI\Signatures::generateUUID();

        $output->writeln("\nGetting all $followingCount following...");
        $progressBar = new ProgressBar($output, 100);
        $followingArray = [];
        $maxId = null;
        

        do {
            $following = $ig->people->getFollowing($userId,$rankToken, null, $maxId);
            $users = $following->getUsers();

            foreach($users as $u) {
                $followingArray[$u->getUsername()] = $u->getPk();
            }
        

            $maxId = $following->getNextMaxId();      
      
        }while($following->getNextMaxId() !== null);

        $output->writeln("<fg=green>All Following Retrieved</>");
        return $followingArray;
    }

    /**
     * Unfollows an array of users
     * @param \InstagramAPI\Instagram $ig $ig 
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Output\OutputOption $ouput
     * 
     * @return array 
     */
    public function unfollowUsers($ig, $input, $output, $users, $whiteList) {
        $io = new SymfonyStyle($input, $output);
        $io->title("Starting To Unfollow Users");
        $c = 1;

        foreach($users as $username => $id) {
            try {
                # unfollow ..
                if (in_array($username, $whiteList)) {
                    $output->writeln("$c/".count($users));
                    $output->writeln("<fg=yellow>Sipped White List User {$username}</> \n");
                }else {
                    $output->writeln("$c/".count($users));
                    $ig->people->unfollow($id);
                    $output->writeln("<fg=green>Unfollowed {$username}</> \n");
                }

                $c++;
                sleep(.5);
            }catch(\Exception $e) {
                $output->writeln('<error>Something Went Wrong</error>');
                exit();
            }
        }
    }

    /**
     * Gets the list of users who liked a post
     * @param \InstagramAPI\Instagram $ig $ig 
     * @param Symfony\Component\Console\Input\InputOption $input
     * @param Symfony\Component\Console\Output\OutputOption $ouput
     * @param string $mediaId
     * 
     * @return array 
     */
    public function getMediaLikers($ig, $input, $output, $mediaId) {
        $likers = $ig->media->getLikers($mediaId)->getUsers();      
        $usersArray = array();
        foreach($likers as $key) {
            $usersArray[$key->getPk()] = $key->getUsername();
        }
        return $usersArray;
    }
}