-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 03, 2018 at 03:04 PM
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
-- Table structure for table `quiz_version`
--

CREATE TABLE IF NOT EXISTS `quiz_version` (
  `quizId` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` char(10) NOT NULL,
  `version` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `quizType` char(20) NOT NULL DEFAULT 'none',
  `seb` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`quizId`),
  UNIQUE KEY `course_code` (`course_code`,`version`,`quizType`),
  UNIQUE KEY `course_code_2` (`course_code`,`version`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=255 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
