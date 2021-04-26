SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fr-database`
--

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

DROP TABLE IF EXISTS `collections`;
CREATE TABLE IF NOT EXISTS `collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `event_photos`
--

DROP TABLE IF EXISTS `event_photos`;
CREATE TABLE IF NOT EXISTS `event_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` varchar(50) NOT NULL,
  `event_image` varchar(250) NOT NULL,
  `face_records` smallint(6) NOT NULL,
  `unindexed_faces` smallint(6) NOT NULL,
  `face_id` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `faces_searched`
--

DROP TABLE IF EXISTS `faces_searched`;
CREATE TABLE IF NOT EXISTS `faces_searched` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` varchar(50) NOT NULL,
  `key_image` varchar(250) NOT NULL,
  `face_matches` smallint(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `face_search_breakdown`
--

DROP TABLE IF EXISTS `face_search_breakdown`;
CREATE TABLE IF NOT EXISTS `face_search_breakdown` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` varchar(50) NOT NULL,
  `event_image` varchar(250) NOT NULL,
  `key_image` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
