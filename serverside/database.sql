-- phpMyAdmin SQL Dump
-- version 3.5.8
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 06, 2013 at 05:31 AM
-- Server version: 5.5.32-cll
-- PHP Version: 5.3.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `recipes`
--

-- --------------------------------------------------------

--
-- Table structure for table `Images`
--

CREATE TABLE IF NOT EXISTS `Images` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Recipe_ID` int(10) unsigned NOT NULL,
  `Author_ID` int(10) unsigned NOT NULL,
  `Filename` varchar(255) NOT NULL,
  `FilenameServer` varchar(255) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Deleted` tinyint(1) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;

-- --------------------------------------------------------

--
-- Table structure for table `Ingredient`
--

CREATE TABLE IF NOT EXISTS `Ingredient` (
  `ID` int(10) unsigned NOT NULL,
  `Recipe_ID` int(10) unsigned NOT NULL,
  `Item` varchar(255) NOT NULL,
  `Amount` varchar(128) DEFAULT NULL,
  `Units` varchar(128) DEFAULT NULL,
  `Extra` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`,`Recipe_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Recipe`
--

CREATE TABLE IF NOT EXISTS `Recipe` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Hash` varchar(64) NOT NULL,
  `Title` varchar(128) NOT NULL,
  `Description` varchar(512) DEFAULT NULL,
  `Ingredients` varchar(4096) NOT NULL,
  `Method` varchar(4096) NOT NULL,
  `Notes` varchar(1024) DEFAULT NULL,
  `AuthorID` int(11) NOT NULL,
  `Source` varchar(255) DEFAULT NULL,
  `Deleted` tinyint(1) NOT NULL,
  `Visibility` tinyint(1) NOT NULL,
  `LastEdited` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Hash` (`Hash`),
  KEY `Title` (`Title`),
  KEY `LastEdited` (`LastEdited`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=178 ;

-- --------------------------------------------------------

--
-- Table structure for table `RecipeTags`
--

CREATE TABLE IF NOT EXISTS `RecipeTags` (
  `RecipeID` int(11) NOT NULL,
  `TagID` int(11) NOT NULL,
  PRIMARY KEY (`RecipeID`,`TagID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Tags`
--

CREATE TABLE IF NOT EXISTS `Tags` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Tag` varchar(64) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=41 ;

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE IF NOT EXISTS `User` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(32) NOT NULL,
  `Pass` varchar(64) NOT NULL,
  `Salt` varchar(64) NOT NULL,
  `Confirmation` varchar(32) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
