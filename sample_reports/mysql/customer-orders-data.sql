-- Install customer order data
-- This is sample customer order data.

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `customer_fname` varchar(100) DEFAULT NULL,
  `customer_lname` varchar(100) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `grand_total` float DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `orders` VALUES (1,'2016-04-02 19:50:53','first1','last',1,100,'paid'),(2,'2016-04-01 19:50:53','first2','last',2,110,'paid'),(3,'2016-03-30 19:50:53','first3','last',3,120,'paid'),(4,'2016-03-28 19:50:53','first1','last',1,130,'paid'),(5,'2016-03-23 19:50:54','first2','last',2,200,'paid');
