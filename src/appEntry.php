<?php
namespace App;
use Symfony\Component\Console\Application;
 
 class MyApp extends Application
 {
 private static $name = "MyApp";
 /**
 * @var string
 */
 private static $logo = <<<LOGO
 ___   __    _  _______  _______  _______  _______  ______    _______  __   __    _______  ___      ___  
|   | |  |  | ||       ||       ||   _   ||       ||    _ |  |   _   ||  |_|  |  |       ||   |    |   | 
|   | |   |_| ||  _____||_     _||  |_|  ||    ___||   | ||  |  |_|  ||       |  |       ||   |    |   | 
|   | |       || |_____   |   |  |       ||   | __ |   |_||_ |       ||       |  |       ||   |    |   | 
|   | |  _    ||_____  |  |   |  |       ||   ||  ||    __  ||       ||       |  |      _||   |___ |   | 
|   | | | |   | _____| |  |   |  |   _   ||   |_| ||   |  | ||   _   || ||_|| |  |     |_ |       ||   | 
|___| |_|  |__||_______|  |___|  |__| |__||_______||___|  |_||__| |__||_|   |_|  |_______||_______||___|   
 


LOGO;
 
 
 /**
 * MyApp constructor.
 *
 * @param string $name
 * @param string $version
 */
 public function __construct( $name = 'UNKNOWN', $version = 'UNKNOWN')
 {
 
 $this->setName(static::$name);
 $this->setVersion($version);
 
 parent::__construct($name, $version);
 
 }
 
 /**
 * @return string
 */
 public function getHelp()
 {
 return static::$logo . parent::getHelp();
 }
 }