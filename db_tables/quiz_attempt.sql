-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 03, 2018 at 03:02 PM
-- Server version: 5.5.58-0ubuntu0.14.04.1-log
-- PHP Version: 5.5.9-1ubuntu4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ecpi_resource`
--

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempt`
--

CREATE TABLE IF NOT EXISTS `quiz_attempt` (
  `attemptId` int(11) NOT NULL AUTO_INCREMENT,
  `quizId` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `course_code` char(10) NOT NULL,
  `type` char(20) NOT NULL,
  `username` char(30) NOT NULL,
  `first_name` char(25) NOT NULL,
  `last_name` char(35) NOT NULL,
  `course_id` char(20) NOT NULL,
  `quiz_start` datetime NOT NULL,
  `quiz_stop` datetime NOT NULL,
  `grade` decimal(10,1) NOT NULL,
  `consumer_key` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `resource_id` varchar(50) NOT NULL,
  `user_sis_id` varchar(15) NOT NULL,
  PRIMARY KEY (`attemptId`),
  UNIQUE KEY `type` (`type`,`username`,`course_id`,`consumer_key`,`resource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=182018 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
