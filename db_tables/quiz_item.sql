-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 03, 2018 at 03:03 PM
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
-- Table structure for table `quiz_item`
--

CREATE TABLE IF NOT EXISTS `quiz_item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `consumer_key` varchar(50) NOT NULL,
  `resource_id` varchar(50) NOT NULL,
  `item_title` varchar(200) NOT NULL,
  `item_text` text,
  `item_url` varchar(200) DEFAULT NULL,
  `max_rating` int(2) NOT NULL DEFAULT '5',
  `step` int(1) NOT NULL DEFAULT '1',
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `sequence` int(3) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `quiz_item_lti_context_FK1` (`consumer_key`,`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `quiz_item`
--
ALTER TABLE `quiz_item`
  ADD CONSTRAINT `quiz_item_lti_context_FK1` FOREIGN KEY (`consumer_key`, `resource_id`) REFERENCES `quiz_lti_context` (`consumer_key`, `context_id`) ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
