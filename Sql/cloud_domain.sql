/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50713
 Source Host           : localhost
 Source Database       : ship2pv5

 Target Server Type    : MySQL
 Target Server Version : 50713
 File Encoding         : utf-8

 Date: 01/11/2017 11:20:10 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `cloud_domain`
-- ----------------------------
DROP TABLE IF EXISTS `cloud_domain`;
CREATE TABLE `cloud_domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(50) NOT NULL,
  `app_id` int(11) NOT NULL,
  `app_secret` varchar(30) NOT NULL,
  `add_time` int(11) DEFAULT NULL,
  `add_ip` varchar(15) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `type` tinyint(1) DEFAULT '1',
  `remark` varchar(255) DEFAULT NULL,
  `default` tinyint(1) DEFAULT '1' COMMENT '1默认域名，2用户域名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
--  Records of `cloud_domain`
-- ----------------------------
BEGIN;
INSERT INTO `cloud_domain` VALUES ('1', 'www.yihaojf.com', '10001', 'ssdddd', '1484104208', '0', '1', '5', 'fuck 发反反复复方法反反复复!', '1'), ('2', 'www.yihaojf.com', '10001', 'ssdddd', '1484104208', '0', '1', '5', 'fuck 发反反复复方法反反复复!', '1'), ('47', 'www.yihaojf.com', '10001', 'ssdddd', '1484104208', null, '1', '5', 'fuck 发反反复复方法反反复复!', '1'), ('48', 'www.yihaojf.com', '10001', 'ssdddd', '1484104208', null, '1', '5', 'fuck 发反反复复方法反反复复!', '1'), ('49', 'www.yihaojf.com', '10001', 'ssdddd', '1484104208', null, '1', '5', 'fuck 发反反复复方法反反复复!', '1'), ('50', 'www.yihaojf.com', '10001', 'ssdddd', '1484104208', null, '1', '5', 'fuck 发反反复复方法反反复复!', '1');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
