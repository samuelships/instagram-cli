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

use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;

class NotificationsCommand extends Command
{   
    use Functions;

    /** @var Username */
    protected $username = "";

    /** @var Password */
    protected $password = "";

    /** @var LogToFile Option */
    protected $logToFile;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'notifications';

    protected function configure()
    {
        $this->setDescription('Listens to notifications from instagram')
            ->setHelp('This commands listens to various forms of notifications from instagram like: when someone likes you
            // picture, when someone follows you, when someone comments on your picture, etc...')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('log_to_file', 'l', InputOption::VALUE_OPTIONAL)
                ])
                
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {   
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

        

        // PROCESS OPTIONS
        $this->processLogToFile($input);

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

        $loop = \React\EventLoop\Factory::create();
        $debug = false;

        if ($debug) {
            $logger = new \Monolog\Logger('push');
            $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::INFO));
        } else {
            $logger = null;
        }

        $push = new \InstagramAPI\Push($loop, $ig, $logger);
        $output->writeln('<fg=green>Listening For Notifications....</>');

        # dm notifications
        $push->on('incoming', function (\InstagramAPI\Push\Notification $push) use($output) {

            # log to a jsonfile
            if ($this->logToFile) {

                if (!file_exists("logs/notifications.txt")) {
               
                    if (!file_exists("logs")) {
                        mkdir("logs", 0777, true);
                    }
                    $noti = array();
                }else {
                    $file = fopen("logs/notifications.txt", "r");
                    $fileContent = file_get_contents("./logs/notifications.txt");
                    $noti = json_decode($fileContent, TRUE);
                }
                $file = fopen("logs/notifications.txt", "w");

                array_push($noti, [
                    "type" => $push->getTitle(),
                    "message" => $push->getMessage(),
                    "time" => date("Y-m-d H:i:s"),
                ]);

                fwrite($file, json_encode($noti));
            }

            $output->writeln($push->getMessage());
            // exec("cd src/command && sWavPlayer sound.mp3");
        });

        # like notifications
        $push->on('like', function (\InstagramAPI\Push\Notification $push) use($output) {
            $output->writeln($push->getMessage());
            exec("cd src/command && sWavPlayer sound.mp3");
        });

        # comment notifications
        $push->on('comment', function (\InstagramAPI\Push\Notification $push) use($output) {
            $output->writeln($push->getMessage());
            exec("cd src/command && sWavPlayer sound.mp3");
        
        });

        # dm notification
        $push->on('direct_v2_message', function (\InstagramAPI\Push\Notification $push) use($output) {
            $output->writeln($push->getMessage());
            exec("cd src/command && sWavPlayer sound.mp3");
        });

        $push->on('error', function (\Exception $e) use ($push, $loop) {
            printf('[!!!] Got fatal error from FBNS: %s%s', $e->getMessage(), PHP_EOL);
            $push->stop();
            $loop->stop();
        });

        $push->start();
        $loop->run();

    }

    

    

    /**
     * Logs all notifications to a file in json format
     * @param Symfony\Component\Console\Input\InputOption $input 
     * 
     * @return bool 
     */
    public function processLogToFile($input) {
        if ($input->getOption("log_to_file")) {
            $this->logToFile = true;
            return true;
        }
    }

    
}