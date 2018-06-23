-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 03, 2018 at 03:01 PM
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
-- Table structure for table `question`
--

CREATE TABLE IF NOT EXISTS `question` (
  `quesNumb` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` char(10) NOT NULL,
  `question` text NOT NULL,
  `qpic` varchar(75) NOT NULL,
  `qalt` varchar(75) NOT NULL,
  `qtype` char(5) NOT NULL,
  `points` int(11) NOT NULL,
  `lo` varchar(20) NOT NULL DEFAULT 'none',
  PRIMARY KEY (`quesNumb`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='question bank' AUTO_INCREMENT=7172 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
