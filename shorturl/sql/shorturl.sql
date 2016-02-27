/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50538
Source Host           : localhost:3306
Source Database       : shorturl

Target Server Type    : MYSQL
Target Server Version : 50538
File Encoding         : 65001

Date: 2015-06-08 16:40:07
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `surl`
-- ----------------------------
DROP TABLE IF EXISTS `surl`;
CREATE TABLE `surl` (
  `ID` int(8) NOT NULL AUTO_INCREMENT,
  `lurl` text NOT NULL,
  `surlhex` text,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of surl
-- ----------------------------
INSERT INTO `surl` VALUES ('1', 'www.baidu.com', 'ndnghx');
INSERT INTO `surl` VALUES ('2', 'www.163.com', 'xoxn3k');
